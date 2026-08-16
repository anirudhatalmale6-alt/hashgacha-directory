<?php
/**
 * Loads the starting content for Ramat Eshkol Kosher: the Hashgacha seal, the
 * three businesses supplied by the client, and the office contact details.
 *
 *   php tools/seed_site.php
 *
 * Safe to re-run. Businesses are matched by name, so re-running updates them
 * rather than creating duplicates, and anything added from the admin panel is
 * left alone. Settings are only filled in where they are still empty unless
 * --force is passed, so it will not overwrite wording edited in the admin.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/init.php';
require dirname(__DIR__) . '/src/helpers.php';

if (PHP_SAPI !== 'cli') {
    exit('Run this from the command line.');
}

$force = in_array('--force', $argv, true);

$logoDir = dirname(__DIR__) . '/content/logos';

/** Copy a starting logo into the uploads folder if it is not there yet. */
function install_logo(string $file, string $logoDir): string
{
    $source = $logoDir . '/' . $file;
    if (!is_file($source)) {
        fwrite(STDERR, "missing starting logo: {$file}\n");
        return '';
    }

    $target = UPLOAD_DIR . '/' . $file;
    if (!is_file($target) || filesize($target) !== filesize($source)) {
        copy($source, $target);
        @chmod($target, 0644);
    }

    return $file;
}

$settings = [
    'site_title'       => 'Ramat Eshkol Kosher',
    'logo'             => install_logo('rek-logo-gold.png', $logoDir),
    'brand_initials'   => 'REK',
    'nav_home'         => 'Home',
    'nav_about'        => 'About',
    'nav_businesses'   => 'Under Our Hashgacha',
    'nav_contact'      => 'Contact',
    'hero_btn_text'    => 'Under Our Hashgacha',
    'about_title'      => 'About',
    'request_btn_text' => 'Request Hashgacha',
    'google_form_url'  => 'https://docs.google.com/forms/d/e/1FAIpQLSdaZPEf7ZbULwVtnXVmKvz_gEgR7tFOxLJ5ZqnFc7ieLB81vA/viewform',
    'businesses_title' => 'Under Our Hashgacha',
    'businesses_intro' => 'The establishments below are under our supervision. Tap any logo for their contact details.',
    'contact_title'    => 'Contact',
    'contact_intro'    => 'Questions about a certificate, a product or a business? Get in touch — we are happy to help.',
    'contact_name'     => 'R\' Aryeh Frankel',
    'contact_role'     => 'Kashrus Administrator',
    'contact_phone'    => '053-384-2614',
    'contact_whatsapp' => '053-384-2614',
    'contact_email'    => 'eshkolkosher@gmail.com',
    'dial_code'        => '972',
    'theme_accent'     => '#b29228',
    'theme_button'     => '#2a6a9a',
];

$current = settings();
foreach ($settings as $key => $value) {
    if ($force || trim((string) ($current[$key] ?? '')) === '') {
        save_setting($key, $value);
    }
}

$businesses = [
    [
        'name'     => 'The Shabbos Chef',
        'category' => '',
        'phone'    => '055-339-5884',
        'whatsapp' => '+1 484-521-1252',
        'email'    => 'orders@theshabboschef.com',
        'website'  => 'https://theshabboschef.com',
        'logo'     => 'biz-shabbos-chef.png',
    ],
    [
        'name'     => 'BÜRNT',
        'category' => '',
        'phone'    => '053-397-1418',
        'whatsapp' => '053-397-1418',
        'email'    => 'burntmeatboards@gmail.com',
        'website'  => 'https://burntmeatboards.com',
        'logo'     => 'biz-burnt.png',
    ],
    [
        'name'     => 'Debbie Levy Catering',
        'category' => '',
        'phone'    => '052-761-0093',
        'whatsapp' => '058-781-2141',
        'email'    => 'rlevy@ohr.edu',
        'website'  => 'https://debbielevycatering.com',
        'logo'     => 'biz-debbie-levy.png',
    ],
];

$find   = db()->prepare('SELECT id FROM businesses WHERE name = ?');
$update = db()->prepare(
    'UPDATE businesses SET category = ?, phone = ?, whatsapp = ?, email = ?, website = ?, logo = ?, sort_order = ?, is_active = 1
     WHERE id = ?'
);
$insert = db()->prepare(
    'INSERT INTO businesses (name, category, description, address, phone, whatsapp, email, website, logo, sort_order, is_active)
     VALUES (?, ?, "", "", ?, ?, ?, ?, ?, ?, 1)'
);

foreach ($businesses as $i => $b) {
    $logo = install_logo($b['logo'], $logoDir);

    $find->execute([$b['name']]);
    $existing = $find->fetchColumn();

    if ($existing !== false) {
        $update->execute([$b['category'], $b['phone'], $b['whatsapp'], $b['email'], $b['website'], $logo, $i, (int) $existing]);
        echo "updated  {$b['name']}\n";
    } else {
        $insert->execute([$b['name'], $b['category'], $b['phone'], $b['whatsapp'], $b['email'], $b['website'], $logo, $i]);
        echo "added    {$b['name']}\n";
    }
}

echo "Done.\n";
