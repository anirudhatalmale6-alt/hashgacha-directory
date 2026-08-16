<?php
/**
 * Renders the public page to a folder of plain HTML so it can be put on a
 * static host (GitHub Pages) for review. The admin panel needs PHP and is
 * deliberately not part of the export.
 *
 *   php tools/export_preview.php [target-dir]
 *
 * Defaults to build/preview. Re-running replaces the folder's contents.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/init.php';
require_once dirname(__DIR__) . '/src/helpers.php';

if (PHP_SAPI !== 'cli') {
    exit('Run this from the command line.');
}

$target = $argv[1] ?? dirname(__DIR__) . '/build/preview';

/** Copy a directory tree, skipping the files a static preview has no use for. */
function copy_tree(string $from, string $to, array $skip = []): int
{
    if (!is_dir($to) && !mkdir($to, 0775, true) && !is_dir($to)) {
        throw new RuntimeException("could not create {$to}");
    }

    $copied = 0;
    foreach (scandir($from) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..' || in_array($entry, $skip, true)) {
            continue;
        }
        $src = $from . '/' . $entry;
        $dst = $to . '/' . $entry;

        if (is_dir($src)) {
            $copied += copy_tree($src, $dst, $skip);
        } else {
            copy($src, $dst);
            $copied++;
        }
    }
    return $copied;
}

/** Render index.php the same way a browser would receive it. */
function render_page(): string
{
    ob_start();
    $GLOBALS['__preview_export'] = true;
    require PUBLIC_DIR . '/index.php';
    return (string) ob_get_clean();
}

if (is_dir($target)) {
    // Only ever clears a previous export of this same page.
    foreach (['index.html', '.nojekyll'] as $file) {
        if (is_file($target . '/' . $file)) {
            unlink($target . '/' . $file);
        }
    }
}
if (!is_dir($target) && !mkdir($target, 0775, true) && !is_dir($target)) {
    exit("could not create {$target}\n");
}

$html = render_page();

// Cache-busting query strings are pointless on a preview and confuse some
// static hosts, so strip them back to plain relative paths.
$html = preg_replace('/(assets\/[a-z.]+)\?v=[^"]*/', '$1', $html) ?? $html;

file_put_contents($target . '/index.html', $html);
file_put_contents($target . '/.nojekyll', '');

$assets = copy_tree(PUBLIC_DIR . '/assets', $target . '/assets');

// Only the logos the page actually references — the uploads folder can hold
// files left behind by earlier content that the preview has no use for.
$wanted = [setting('logo')];
foreach (active_businesses() as $row) {
    $wanted[] = $row['logo'];
}

if (!is_dir($target . '/uploads')) {
    mkdir($target . '/uploads', 0775, true);
}

$logos = 0;
foreach (array_unique(array_filter($wanted)) as $file) {
    if (is_file(UPLOAD_DIR . '/' . $file)) {
        copy(UPLOAD_DIR . '/' . $file, $target . '/uploads/' . $file);
        $logos++;
    }
}

printf(
    "Exported to %s\n  index.html (%d KB)\n  %d asset file(s)\n  %d logo file(s)\n",
    $target,
    (int) round(strlen($html) / 1024),
    $assets,
    $logos
);
