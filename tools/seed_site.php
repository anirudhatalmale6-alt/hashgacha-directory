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

require_once dirname(__DIR__) . '/src/init.php';
require_once dirname(__DIR__) . '/src/helpers.php';

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
    'logo'             => install_logo('rek-logo-black.png', $logoDir),
    'brand_initials'   => 'REK',
    'nav_home'         => 'Home',
    'nav_about'        => 'About',
    'nav_businesses'   => 'Certified Businesses',
    'nav_contact'      => 'Contact',
    'hero_btn_text'    => 'Certified Businesses',
    'about_title'      => 'About Ramat Eshkol Kosher',
    'about_text'       => "Ramat Eshkol Kosher provides professional, reliable, and personal kosher supervision for businesses and home-based food producers. We work with each business to maintain the highest standards of kashrus while ensuring that our guidelines are practical, clear, and easy to follow.\n\nRamat Eshkol Kosher operates under the leadership of Rabbi Akiva Dershowitz, Rav of Khal Nachlas Yaakov in Ramat Eshkol, with a commitment to maintaining the highest standards of kashrus and providing businesses and their customers with confidence, trust, and peace of mind.",
    'request_btn_text' => 'Request Hashgacha',
    'google_form_url'  => 'https://docs.google.com/forms/d/e/1FAIpQLSdaZPEf7ZbULwVtnXVmKvz_gEgR7tFOxLJ5ZqnFc7ieLB81vA/viewform',
    'businesses_title' => 'Certified Businesses',
    'businesses_intro' => '',
    'contact_title'    => 'Contact',
    'contact_intro'    => '',
    'contact_name'     => 'R\' Aryeh Frankel',
    'contact_role'     => 'Kashrus Administrator',
    'contact_phone'    => '053-384-2614',
    'contact_whatsapp' => '053-384-2614',
    'contact_email'    => 'eshkolkosher@gmail.com',
    'business_order'   => 'alpha',
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
        'name'     => 'The LoxSmith',
        'category' => '',
        'phone'    => '055-338-1191',
        // NYC area code — written with a + so it is never given the Israeli code.
        'whatsapp' => '+1 718-645-6368',
        'email'    => 'theloxsmithisrael@gmail.com',
        'website'  => 'https://theloxsmithisrael.com',
        'logo'     => 'biz-loxsmith.png',
    ],
    [
        'name'     => 'The Challery',
        'category' => '',
        'phone'    => '058-320-4637',
        'whatsapp' => '053-711-5976',
        'email'    => 'thechallery@gmail.com',
        'website'  => 'https://thechallery.com',
        'logo'     => 'biz-challery.png',
    ],
    [
        'name'     => 'Cup Of Cake Israel',
        'category' => '',
        'phone'    => '053-419-8279',
        'whatsapp' => '053-419-8279',
        'email'    => 'cupofcakeisrael@gmail.com',
        'website'  => '',
        'logo'     => 'biz-cup-of-cake.png',
    ],
    [
        'name'     => 'Exclusive Catering',
        'category' => '',
        'phone'    => '053-413-6719',
        'whatsapp' => '',
        'email'    => 'atfrankel1@gmail.com',
        'website'  => 'https://glattcooking.com',
        'logo'     => 'biz-exclusive-catering.png',
    ],
    [
        'name'     => 'POP JLM',
        'category' => '',
        'phone'    => '055-334-8059',
        // 929 is a New York area code, so it carries its own country code.
        'whatsapp' => '+1 929-753-9587',
        'email'    => 'eliherman1999@gmail.com',
        'website'  => '',
        'logo'     => 'biz-pops-jlm.png',
    ],
    [
        'name'     => 'Scaled',
        'category' => '',
        'phone'    => '058-733-4655',
        'whatsapp' => '+972 53-362-4820',
        'email'    => 'leahamsel101@gmail.com',
        'website'  => '',
        'logo'     => 'biz-scaled.png',
    ],
    [
        // The number was read off the logo artwork and the owner's own form
        // response then matched it exactly. Their website answer is a WhatsApp
        // catalogue link that the shared spreadsheet cut off mid-URL.
        'name'     => 'The Shalosh Seudos Spot',
        'category' => '',
        'phone'    => '055-333-8650',
        'whatsapp' => '055-333-8650',
        'email'    => 'Rivkyy03@gmail.com',
        'website'  => '',
        'logo'     => 'biz-shalosh-seudos.png',
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

    /* --------------------------------------------------------------------
     * Second batch. These arrived as logos only, so anything printed on the
     * artwork was read off it and flagged for the client to confirm. The
     * client later shared their Google Form responses, and where an owner
     * answered for themselves that answer wins over the artwork.
     *
     * The responses came as a PDF print of the sheet, which clips each cell to
     * its column width — so several emails end mid-address. A truncated value
     * is only completed where the domain is unmistakable; where the local part
     * itself was cut the field is left empty rather than invented. The rest
     * still show "No contact details listed" in the popup.
     * ----------------------------------------------------------------- */

    [
        'name'     => 'Arush Chocolate',
        'category' => '',
        'phone'    => '',
        'whatsapp' => '',
        'email'    => '',
        'website'  => '',
        'logo'     => 'biz-arush-chocolate.png',
    ],
    [
        'name'     => 'Baked By Gail',
        'category' => '',
        'phone'    => '',
        'whatsapp' => '',
        'email'    => '',
        'website'  => '',
        'logo'     => 'biz-baked-by-gail.png',
    ],
    [
        'name'     => 'Bawk Bawk Chicken',
        'category' => '',
        'phone'    => '',
        'whatsapp' => '',
        'email'    => '',
        'website'  => '',
        'logo'     => 'biz-bawk-bawk-chicken.png',
    ],
    [
        // Printed on the logo beside a WhatsApp icon, so it is set as WhatsApp
        // only rather than assuming the line also takes calls.
        'name'     => 'Blazin\' Boards',
        'category' => '',
        'phone'    => '',
        'whatsapp' => '+972 53-727-0990',
        'email'    => '',
        'website'  => '',
        'logo'     => 'biz-blazin-boards.png',
    ],
    [
        'name'     => 'Buddy Bites',
        'category' => '',
        'phone'    => '',
        'whatsapp' => '',
        'email'    => '',
        'website'  => '',
        'logo'     => 'biz-buddy-bites.png',
    ],
    [
        // One number for both, so the popup shows a single combined row.
        'name'     => 'Butcher\'s Cut',
        'category' => '',
        'phone'    => '058-424-3674',
        'whatsapp' => '058-424-3674',
        'email'    => 'butcherscutisrael@gmail.com',
        'website'  => 'https://butcherscutisrael.com',
        'logo'     => 'biz-butchers-cut.png',
    ],
    [
        // Their form gave a US number to call and an Israeli one for WhatsApp —
        // the reverse of the usual pairing here, but that is what they wrote.
        'name'     => 'Chicken Munch',
        'category' => '',
        'phone'    => '+1 929-474-8871',
        'whatsapp' => '055-331-6975',
        'email'    => 'chickenmunch26@gmail.com',
        'website'  => '',
        'logo'     => 'biz-chicken-munch.png',
    ],
    [
        'name'     => 'Cookie and Dough',
        'category' => '',
        'phone'    => '',
        'whatsapp' => '',
        'email'    => '',
        'website'  => '',
        'logo'     => 'biz-cookie-and-dough.png',
    ],
    [
        // The owner's form response gives a different number from the one
        // printed on their label (055-332-1308), so their own answer wins and
        // the discrepancy has been raised with the client.
        'name'     => 'Crumbz',
        'category' => '',
        'phone'    => '058-323-1201',
        'whatsapp' => '058-323-1201',
        'email'    => 'Shaindyhershman@gmail.com',
        'website'  => '',
        'logo'     => 'biz-crumbz.png',
    ],
    [
        'name'     => 'Crust and Co',
        'category' => '',
        'phone'    => '',
        'whatsapp' => '',
        'email'    => '',
        'website'  => '',
        'logo'     => 'biz-crust-and-co.png',
    ],
    [
        // Their form response calls the business simply "Dipped".
        'name'     => 'Dipped Artisan Dips',
        'category' => '',
        'phone'    => '055-238-1191',
        'whatsapp' => '055-337-3043',
        'email'    => 'Artisandippedisrael@gmail.com',
        'website'  => '',
        'logo'     => 'biz-dipped-artisan-dips.png',
    ],
    [
        // The artwork spells it "Heimishe"; the file the client sent was named
        // "Heimish Cookies".
        'name'     => 'Heimishe Cookies',
        'category' => '',
        'phone'    => '',
        'whatsapp' => '',
        'email'    => '',
        'website'  => '',
        'logo'     => 'biz-heimishe-cookies.png',
    ],
    [
        // Their form response spells it "Homemade for You".
        'name'     => 'Home Made For You',
        'category' => '',
        'phone'    => '053-963-3594',
        'whatsapp' => '+1 410-805-1423',
        'email'    => 'tovaambush@gmail.com',
        'website'  => '',
        'logo'     => 'biz-home-made-for-you.png',
    ],
    [
        // Both printed on the label, under "Hand made by: E.C. Nussbaum".
        'name'     => 'Home Made Sourdough',
        'category' => '',
        'phone'    => '053-962-5506',
        'whatsapp' => '',
        'email'    => 'sourdough.homemade@gmail.com',
        'website'  => '',
        'logo'     => 'biz-home-made-sourdough.png',
    ],
    [
        // Both printed across the bottom of the logo.
        'name'     => 'Home Sweet Home Baked Goods',
        'category' => '',
        'phone'    => '058-325-2037',
        'whatsapp' => '',
        'email'    => 'homesweethomebakeryisrael@gmail.com',
        'website'  => '',
        'logo'     => 'biz-home-sweet-home.png',
    ],
    [
        'name'     => 'Jerusalem Salad Box',
        'category' => '',
        'phone'    => '',
        'whatsapp' => '',
        'email'    => '',
        'website'  => '',
        'logo'     => 'biz-jerusalem-salad-box.png',
    ],
    [
        'name'     => 'Krunchies',
        'category' => '',
        'phone'    => '',
        'whatsapp' => '',
        'email'    => '',
        'website'  => '',
        'logo'     => 'biz-krunchies.png',
    ],
    [
        'name'     => 'Leibler Catering',
        'category' => '',
        'phone'    => '',
        'whatsapp' => '',
        'email'    => '',
        'website'  => '',
        'logo'     => 'biz-leibler-catering.png',
    ],
    [
        // No phone anywhere on the artwork, only the Instagram handle.
        'name'     => 'Lettuce Eat Fresh',
        'category' => '',
        'phone'    => '',
        'whatsapp' => '',
        'email'    => '',
        'website'  => 'https://instagram.com/lettuce_eat_fresh_jlm',
        'logo'     => 'biz-lettuce-eat-fresh.png',
    ],
    [
        // The number was read off the foot of the logo and their form response
        // matched it, and gave the same number again for WhatsApp — so this one
        // shows as a single combined row.
        'name'     => 'LVR',
        'category' => '',
        'phone'    => '055-330-2580',
        'whatsapp' => '055-330-2580',
        'email'    => 'Pinnyabc@gmail.com',
        'website'  => '',
        'logo'     => 'biz-lvr.png',
    ],
    [
        'name'     => 'MK Bakes',
        'category' => '',
        'phone'    => '',
        'whatsapp' => '',
        'email'    => '',
        'website'  => '',
        'logo'     => 'biz-mk-bakes.png',
    ],
    [
        'name'     => 'Naturelle By Yaelle',
        'category' => '',
        'phone'    => '',
        'whatsapp' => '',
        'email'    => '',
        'website'  => '',
        'logo'     => 'biz-naturelle-by-yaelle.png',
    ],
    [
        // Their email was cut off at the "@" where the spreadsheet clipped the
        // column, so it is left blank rather than guessed at.
        'name'     => 'Nine by Thirteen Catering',
        'category' => '',
        'phone'    => '053-319-6164',
        'whatsapp' => '+1 718-564-6570',
        'email'    => '',
        'website'  => '',
        'logo'     => 'biz-nine-by-thirteen.png',
    ],
    [
        // Printed under the wordmark.
        'name'     => 'Scored',
        'category' => '',
        'phone'    => '058-753-2807',
        'whatsapp' => '',
        'email'    => '',
        'website'  => '',
        'logo'     => 'biz-scored.png',
    ],
    [
        // They gave two numbers; the spreadsheet clipped the second one part
        // way through, and their email with it, so only the first is set.
        'name'     => 'Something Special Catering',
        'category' => '',
        'phone'    => '02-540-1236',
        'whatsapp' => '',
        'email'    => '',
        'website'  => '',
        'logo'     => 'biz-something-special.png',
    ],
    [
        // One number for calls and WhatsApp, so it shows as a combined row.
        'name'     => 'The Cholent Guy',
        'category' => '',
        'phone'    => '053-437-7192',
        'whatsapp' => '053-437-7192',
        'email'    => 'as0548429965@gmail.com',
        'website'  => '',
        'logo'     => 'biz-cholent-guy.png',
    ],
    [
        // The logo gives "Whatsapp 972 55-337-5481 or Call 055-337-5481" —
        // the same line written two ways, so it collapses to one row.
        'name'     => 'The Kugel Korner',
        'category' => '',
        'phone'    => '055-337-5481',
        'whatsapp' => '055-337-5481',
        'email'    => '',
        'website'  => '',
        'logo'     => 'biz-kugel-korner.png',
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
