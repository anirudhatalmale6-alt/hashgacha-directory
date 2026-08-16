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
        'site_title'        => 'Hashgacha Certification',
        'site_tagline'      => 'Trusted Kosher Supervision',
        'logo'              => '',
        'brand_initials'    => 'HC',
        'hero_heading'      => 'Certified with care. Trusted by the community.',
        'hero_subheading'   => 'Our Hashgacha provides reliable kosher supervision for restaurants, bakeries, caterers and food producers across the community.',
        'about_title'       => 'About Our Hashgacha',
        'about_text'        => "Our Hashgacha was established to provide the community with dependable, transparent kosher supervision. Every establishment under our certification is visited regularly by our mashgichim, who verify ingredients, review suppliers and oversee food preparation from start to finish.\n\nWe work closely with business owners to make certification straightforward, and we are always available to answer questions from the community about any establishment on our list.",
        'about_point_1'     => 'Regular on-site inspections by trained mashgichim',
        'about_point_2'     => 'Full ingredient and supplier verification',
        'about_point_3'     => 'Direct line to our office for any question',
        'request_heading'   => 'Looking for certification?',
        'request_text'      => 'If you own a food business and would like to be certified under our Hashgacha, fill in the short request form and our office will be in touch.',
        'request_btn_text'  => 'Request Hashgacha',
        'google_form_url'   => 'https://docs.google.com/forms/',
        'businesses_title'  => 'Certified Businesses',
        'businesses_intro'  => 'The establishments below hold a current certificate under our supervision. Tap any logo to see contact details.',
        'contact_title'     => 'Contact the Hashgacha Office',
        'contact_intro'     => 'Questions about a certificate, a product or a business? Get in touch — we are happy to help.',
        'contact_phone'     => '+1 (555) 010-2030',
        'contact_whatsapp'  => '+15550102030',
        'contact_email'     => 'office@example.org',
        'contact_address'   => '120 Community Way, Suite 4',
        'contact_hours'     => 'Sunday–Thursday, 9:00am – 5:00pm',
        'footer_note'       => 'All certificates are valid for the period stated on the certificate itself.',
        'show_search'       => '1',
        'theme_accent'      => '#1f4b8e',
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
