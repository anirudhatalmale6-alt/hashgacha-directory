<?php
/**
 * Loads demo businesses (with generated placeholder logos) so the site can be
 * previewed before the real content arrives. Run from the command line:
 *
 *   php tools/seed_demo.php
 *
 * Safe to re-run: it clears the demo rows it created and re-inserts them.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/init.php';
require dirname(__DIR__) . '/src/helpers.php';

if (PHP_SAPI !== 'cli') {
    exit('Run this from the command line.');
}

$demo = [
    ['Golden Crust Bakery',      'Bakery',     '#b5651d', '+1 (555) 210-4488', '+15552104488', 'hello@goldencrust.example', 'www.goldencrust.example', '18 Orchard Street', 'Fresh challah, pastries and breads baked daily on the premises.'],
    ['Shalom Grill House',       'Restaurant', '#8b1e3f', '+1 (555) 210-7712', '+15552107712', 'info@shalomgrill.example', 'www.shalomgrill.example', '212 Maple Avenue', 'Family grill house serving lunch and dinner seven days a week.'],
    ['Ben Ami Catering',         'Caterer',    '#1f4b8e', '+1 (555) 210-9931', '+15552109931', 'events@benami.example', 'www.benami.example', '5 Industrial Park Road', 'Simchas, weddings and corporate events, on-site or delivered.'],
    ['Kedem Fine Foods',         'Grocery',    '#256b4f', '+1 (555) 210-3320', '+15552103320', 'shop@kedemfoods.example', 'www.kedemfoods.example', '77 Market Square', 'Full-service grocery with a supervised deli and butcher counter.'],
    ['Har Sinai Dairy',          'Producer',   '#3a5ea8', '+1 (555) 210-6604', '+15552106604', 'orders@harsinai.example', 'www.harsinai.example', 'Unit 9, Riverside Estate', 'Milk, cheeses and yoghurts produced under continuous supervision.'],
    ['Cafe Techelet',            'Cafe',       '#2b6f8e', '+1 (555) 210-1177', '+15552101177', 'hi@cafetechelet.example', 'www.cafetechelet.example', '40 Station Road', 'Coffee house and light dairy menu, open from early morning.'],
    ['Levy Butchery',            'Butcher',    '#7a2e2e', '+1 (555) 210-8845', '+15552108845', 'counter@levybutchery.example', 'www.levybutchery.example', '3 Northgate Parade', 'Glatt kosher butcher with in-house preparation and delivery.'],
    ['Simcha Sweets',            'Bakery',     '#a5457c', '+1 (555) 210-5529', '+15552105529', 'orders@simchasweets.example', 'www.simchasweets.example', '129 High Street', 'Celebration cakes, cookie platters and dessert tables to order.'],
    ['Emek Produce Market',      'Grocery',    '#4a7c2f', '+1 (555) 210-2246', '+15552102246', 'fresh@emekproduce.example', 'www.emekproduce.example', '61 Vine Lane', 'Fruit and vegetables checked and packed under our supervision.'],
    ['Nachlas Hotel Kitchen',    'Hospitality', '#6b4c9a', '+1 (555) 210-4090', '+15552104090', 'kitchen@nachlas.example', 'www.nachlas.example', '1 Garden Terrace', 'Hotel kitchen and banqueting suite serving guests year round.'],
];

/**
 * Draw a simple wordmark so the demo grid is not full of empty boxes.
 * Real logos replace these from the admin panel.
 */
function make_placeholder_logo(string $name, string $hex, string $file): void
{
    $serif = '/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf';
    $sans  = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
    $hasTtf = function_exists('imagettftext') && is_file($serif) && is_file($sans);

    $w = 460;
    $h = 300;
    $img = imagecreatetruecolor($w, $h);
    imagealphablending($img, false);
    imagesavealpha($img, true);
    imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
    imagealphablending($img, true);
    imageantialias($img, true);

    [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');
    $brand = imagecolorallocate($img, $r, $g, $b);
    $ring  = imagecolorallocatealpha($img, $r, $g, $b, 100);
    $white = imagecolorallocate($img, 255, 255, 255);

    $cx = (int) ($w / 2);
    $cy = 108;
    imagefilledellipse($img, $cx, $cy, 172, 172, $ring);
    imagefilledellipse($img, $cx, $cy, 150, 150, $brand);

    $mark = '';
    foreach (preg_split('/\s+/', $name) ?: [] as $word) {
        $mark .= strtoupper(substr($word, 0, 1));
        if (strlen($mark) >= 2) {
            break;
        }
    }

    /** Draw horizontally-centred TTF text and return the baseline used. */
    $centred = function (string $text, string $font, int $size, int $y, int $colour) use ($img, $w, $hasTtf): void {
        if ($hasTtf) {
            $box = imagettfbbox($size, 0, $font, $text);
            $x = (int) (($w - ($box[2] - $box[0])) / 2);
            imagettftext($img, $size, 0, $x, $y, $colour, $font, $text);
            return;
        }
        imagestring($img, 5, (int) (($w - strlen($text) * 9) / 2), $y - 14, $text, $colour);
    };

    $centred($mark, $serif, 56, $cy + 22, $white);

    $lines = explode("\n", wordwrap(strtoupper($name), 20, "\n", true));
    $y = 236;
    foreach (array_slice($lines, 0, 2) as $line) {
        $centred($line, $sans, 21, $y, $brand);
        $y += 30;
    }

    imagepng($img, $file);
    imagedestroy($img);
}

$pdo = db();

// Remove any previous demo rows and their generated logos.
$old = $pdo->query('SELECT logo FROM businesses WHERE logo LIKE "demo-%"')->fetchAll(PDO::FETCH_COLUMN);
foreach ($old as $logo) {
    $path = UPLOAD_DIR . '/' . $logo;
    if (is_file($path)) {
        unlink($path);
    }
}
$pdo->exec('DELETE FROM businesses WHERE logo LIKE "demo-%"');

$insert = $pdo->prepare(
    'INSERT INTO businesses (name, category, description, address, phone, whatsapp, email, website, logo, sort_order, is_active)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)'
);

foreach ($demo as $i => [$name, $category, $colour, $phone, $whatsapp, $email, $website, $address, $description]) {
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name) ?? 'biz');
    $logo = 'demo-' . trim($slug, '-') . '.png';
    make_placeholder_logo($name, $colour, UPLOAD_DIR . '/' . $logo);

    $insert->execute([
        $name, $category, $description, $address, $phone, $whatsapp,
        $email, 'https://' . $website, $logo, $i,
    ]);
}

echo 'Seeded ' . count($demo) . " demo businesses.\n";
