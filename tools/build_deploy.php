<?php
/**
 * Packs the site into one folder that can be dropped straight into a shared
 * host's public_html — no build tools, no Composer, nothing to run on the
 * server.
 *
 *   php tools/build_deploy.php [--admin-pass=SECRET]
 *
 * The working copy keeps the code out of the document root by putting the
 * document root in /public. Shared hosts fix their document root at
 * public_html, so the deploy build turns that inside out:
 *
 *   public_html/            <- everything from /public
 *   public_html/_app/       <- src, data and the database, blocked by .htaccess
 *
 * Nothing depends on mod_rewrite, so it behaves the same on Apache, LiteSpeed
 * or anything else that runs PHP.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit('Run this from the command line.');
}

$root   = dirname(__DIR__);
$deploy = $root . '/build/deploy';
$zip    = $root . '/build/ramat-eshkol-kosher-site.zip';

$adminPass = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--admin-pass=')) {
        $adminPass = substr($arg, 13);
    }
}

/* ---------- helpers ---------- */

function rrmdir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}

function copy_tree(string $from, string $to, array $skip = []): void
{
    if (!is_dir($to)) {
        mkdir($to, 0755, true);
    }
    foreach (scandir($from) as $entry) {
        if ($entry === '.' || $entry === '..' || in_array($entry, $skip, true)) {
            continue;
        }
        $src = $from . '/' . $entry;
        $dst = $to . '/' . $entry;
        if (is_dir($src)) {
            copy_tree($src, $dst, $skip);
        } else {
            copy($src, $dst);
            chmod($dst, 0644);
        }
    }
}

/** Rewrite an include path, and stop the build if the line ever moves. */
function repoint(string $file, string $search, string $replace): void
{
    $code = file_get_contents($file);
    if (!str_contains($code, $search)) {
        fwrite(STDERR, "build failed: expected to find in " . basename($file) . "\n  {$search}\n");
        exit(1);
    }
    file_put_contents($file, str_replace($search, $replace, $code));
}

/* ---------- lay the folders out ---------- */

rrmdir($deploy);
@unlink($zip);
mkdir($deploy . '/_app', 0755, true);

// Document root: everything the browser is allowed to ask for.
copy_tree($root . '/public', $deploy, ['.gitkeep']);

// Private: application code and the database, one level down and blocked off.
copy_tree($root . '/src', $deploy . '/_app/src');
mkdir($deploy . '/_app/data', 0775, true);

/* ---------- point the entry files at _app ---------- */

repoint($deploy . '/index.php',
    "require_once __DIR__ . '/../src/init.php';\nrequire_once __DIR__ . '/../src/helpers.php';",
    "require_once __DIR__ . '/_app/src/init.php';\nrequire_once __DIR__ . '/_app/src/helpers.php';");

repoint($deploy . '/admin/auth.php',
    "require_once dirname(__DIR__, 2) . '/src/init.php';\nrequire_once dirname(__DIR__, 2) . '/src/helpers.php';",
    "require_once dirname(__DIR__) . '/_app/src/init.php';\nrequire_once dirname(__DIR__) . '/_app/src/helpers.php';");

// "View site" in the admin header climbs out of /admin, which is now the
// document root rather than /public.
repoint($deploy . '/admin/layout.php', 'href="../index.php"', 'href="../"');

/* ---------- config and access rules ---------- */

file_put_contents($deploy . '/_app/config.php', <<<'PHP'
<?php
/**
 * Hosting layout: this folder sits inside the document root rather than
 * beside it, so the public files are one level up.
 */
declare(strict_types=1);

define('PUBLIC_DIR', dirname(__DIR__));
define('DATA_DIR', __DIR__ . '/data');
define('UPLOAD_DIR', PUBLIC_DIR . '/uploads');

PHP);

$denyAll = <<<'TXT'
# Application code and data. Nothing in here is meant to be reachable
# from a browser.
<IfModule mod_authz_core.c>
  Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
  Order allow,deny
  Deny from all
</IfModule>

TXT;

file_put_contents($deploy . '/_app/.htaccess', $denyAll);
file_put_contents($deploy . '/_app/data/.htaccess', $denyAll);

// The document-root rules: the public ones, plus a belt-and-braces block on
// the database in case a host ignores the folder rule above.
$publicRules = file_get_contents($root . '/public/.htaccess');
file_put_contents($deploy . '/.htaccess', $publicRules . <<<'TXT'

# The database is never downloadable, wherever it ends up.
<FilesMatch "\.(sqlite|sqlite-wal|sqlite-shm|db|log)$">
  <IfModule mod_authz_core.c>
    Require all denied
  </IfModule>
  <IfModule !mod_authz_core.c>
    Order allow,deny
    Deny from all
  </IfModule>
</FilesMatch>

# index.php answers for the folder itself.
DirectoryIndex index.php index.html

TXT);

/* ---------- fill the database ---------- */

define('DATA_DIR', $deploy . '/_app/data');
define('PUBLIC_DIR', $deploy);
define('UPLOAD_DIR', $deploy . '/uploads');

require_once $root . '/src/init.php';
require_once $root . '/src/helpers.php';

$argv[] = '--force';           // a fresh database: write every setting
ob_start();
require $root . '/tools/seed_site.php';
$seedLog = ob_get_clean();

if ($adminPass !== null && $adminPass !== '') {
    db()->prepare('UPDATE admins SET password_hash = ? WHERE username = ?')
        ->execute([password_hash($adminPass, PASSWORD_DEFAULT), 'admin']);
    echo "admin password set for this package\n";
}

// WAL files are a running database's scratch space; check-pointing folds them
// back in so the package carries one self-contained file.
db()->exec('PRAGMA wal_checkpoint(TRUNCATE)');
db()->exec('VACUUM');
@unlink(DATA_DIR . '/site.sqlite-wal');
@unlink(DATA_DIR . '/site.sqlite-shm');
@chmod(DATA_DIR . '/site.sqlite', 0664);

/* ---------- zip it ---------- */

$archive = new ZipArchive();
if ($archive->open($zip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "could not create {$zip}\n");
    exit(1);
}

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($deploy, FilesystemIterator::SKIP_DOTS)
);
$count = 0;
foreach ($files as $file) {
    if ($file->isDir()) {
        continue;
    }
    $archive->addFile($file->getPathname(), substr($file->getPathname(), strlen($deploy) + 1));
    $count++;
}
$archive->close();

echo trim($seedLog), "\n";
echo "\nPackaged {$count} files\n";
echo "  folder: {$deploy}\n";
echo "  zip:    {$zip} (" . round(filesize($zip) / 1024) . " KB)\n";
