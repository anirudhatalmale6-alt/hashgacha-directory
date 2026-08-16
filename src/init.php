<?php
/**
 * Bootstrap: paths, database connection, schema, defaults.
 * Included by every entry point.
 */

declare(strict_types=1);

define('APP_VERSION', '1.0.0');
define('APP_ROOT', dirname(__DIR__));

// Optional: create config.php next to this folder to point the app at a
// different data or uploads directory (see README).
if (is_file(APP_ROOT . '/config.php')) {
    require APP_ROOT . '/config.php';
}

defined('DATA_DIR')   || define('DATA_DIR', APP_ROOT . '/data');
defined('PUBLIC_DIR') || define('PUBLIC_DIR', APP_ROOT . '/public');
defined('UPLOAD_DIR') || define('UPLOAD_DIR', PUBLIC_DIR . '/uploads');

foreach ([DATA_DIR, UPLOAD_DIR] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

/** @return PDO */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO('sqlite:' . DATA_DIR . '/site.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');

    install_schema($pdo);

    return $pdo;
}

function install_schema(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS settings (
        key   TEXT PRIMARY KEY,
        value TEXT NOT NULL DEFAULT ""
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS businesses (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        name        TEXT NOT NULL,
        category    TEXT NOT NULL DEFAULT "",
        description TEXT NOT NULL DEFAULT "",
        address     TEXT NOT NULL DEFAULT "",
        phone       TEXT NOT NULL DEFAULT "",
        whatsapp    TEXT NOT NULL DEFAULT "",
        email       TEXT NOT NULL DEFAULT "",
        website     TEXT NOT NULL DEFAULT "",
        logo        TEXT NOT NULL DEFAULT "",
        sort_order  INTEGER NOT NULL DEFAULT 0,
        is_active   INTEGER NOT NULL DEFAULT 1,
        created_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS admins (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        username      TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL
    )');

    // Default admin — the password is changed from the admin panel on first use.
    $count = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
        $stmt->execute(['admin', password_hash('hashgacha2026', PASSWORD_DEFAULT)]);
    }

    seed_default_settings($pdo);
}

function default_settings(): array
{
    return [
        'site_title'        => 'Ramat Eshkol Kosher',
        'logo'              => '',
        'brand_initials'    => 'REK',
        'nav_home'          => 'Home',
        'nav_about'         => 'About',
        'nav_businesses'    => 'Certified Businesses',
        'nav_contact'       => 'Contact',
        'hero_btn_text'     => 'Certified Businesses',
        'about_title'       => 'About Ramat Eshkol Kosher',
        'about_text'        => "Ramat Eshkol Kosher provides professional, reliable, and personal kosher supervision for businesses and home-based food producers. We work with each business to maintain the highest standards of kashrus while ensuring that our guidelines are practical, clear, and easy to follow.\n\nRamat Eshkol Kosher operates under the leadership of Rabbi Akiva Dershowitz, Rav of Khal Nachlas Yaakov in Ramat Eshkol, with a commitment to maintaining the highest standards of kashrus and providing businesses and their customers with confidence, trust, and peace of mind.",
        'request_btn_text'  => 'Request Hashgacha',
        'google_form_url'   => '',
        'businesses_title'  => 'Certified Businesses',
        'businesses_intro'  => '',
        'contact_title'     => 'Contact',
        'contact_intro'     => '',
        'contact_name'      => 'R\' Aryeh Frankel',
        'contact_role'      => 'Kashrus Administrator',
        'contact_phone'     => '053-384-2614',
        'contact_whatsapp'  => '053-384-2614',
        'contact_email'     => 'eshkolkosher@gmail.com',
        'contact_address'   => '',
        'contact_hours'     => '',
        'footer_note'       => '',
        'show_search'       => '1',
        'dial_code'         => '972',
        'theme_accent'      => '#b29228',
        'theme_button'      => '#2a6a9a',
    ];
}

function seed_default_settings(PDO $pdo): void
{
    $existing = $pdo->query('SELECT key FROM settings')->fetchAll(PDO::FETCH_COLUMN);
    $existing = array_flip($existing);

    $stmt = $pdo->prepare('INSERT INTO settings (key, value) VALUES (?, ?)');
    foreach (default_settings() as $key => $value) {
        if (!isset($existing[$key])) {
            $stmt->execute([$key, $value]);
        }
    }
}

function settings(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = [];
    foreach (db()->query('SELECT key, value FROM settings') as $row) {
        $cache[$row['key']] = $row['value'];
    }
    return $cache;
}

function setting(string $key, string $fallback = ''): string
{
    $all = settings();
    return $all[$key] ?? $fallback;
}

function save_setting(string $key, string $value): void
{
    $stmt = db()->prepare('INSERT INTO settings (key, value) VALUES (?, ?)
                           ON CONFLICT(key) DO UPDATE SET value = excluded.value');
    $stmt->execute([$key, $value]);
}
