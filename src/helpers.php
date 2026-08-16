<?php
/**
 * Shared view/format helpers.
 */

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Turn stored multi-line text into paragraphs. */
function paragraphs(?string $text): string
{
    $blocks = preg_split('/\R{2,}/', trim((string) $text)) ?: [];
    $out = '';
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') {
            continue;
        }
        $out .= '<p>' . nl2br(e($block)) . '</p>';
    }
    return $out;
}

/** Digits-only phone, usable in tel: and wa.me links. */
function phone_digits(?string $value): string
{
    $digits = preg_replace('/\D+/', '', (string) $value) ?? '';
    return $digits;
}

/** Make sure a user-entered website has a scheme. */
function normalize_url(?string $url): string
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }
    if (!preg_match('~^https?://~i', $url)) {
        $url = 'https://' . $url;
    }
    return $url;
}

/** Strip scheme / trailing slash for display. */
function pretty_url(?string $url): string
{
    $url = preg_replace('~^https?://~i', '', trim((string) $url)) ?? '';
    return rtrim($url, '/');
}

/** Initials fallback when a business has no logo uploaded. */
function initials(string $name): string
{
    $words = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach ($words as $word) {
        $first = mb_substr($word, 0, 1);
        if ($first !== '' && preg_match('/\p{L}/u', $first)) {
            $letters .= mb_strtoupper($first);
        }
        if (mb_strlen($letters) >= 2) {
            break;
        }
    }
    return $letters !== '' ? $letters : '?';
}

function asset_url(string $relative): string
{
    return $relative . '?v=' . APP_VERSION;
}

/** All businesses shown on the public page. */
function active_businesses(): array
{
    return db()->query(
        'SELECT * FROM businesses WHERE is_active = 1 ORDER BY sort_order ASC, name COLLATE NOCASE ASC'
    )->fetchAll();
}

/** Distinct non-empty categories, for the filter chips. */
function business_categories(): array
{
    $rows = db()->query(
        'SELECT DISTINCT category FROM businesses
         WHERE is_active = 1 AND TRIM(category) <> ""
         ORDER BY category COLLATE NOCASE ASC'
    )->fetchAll(PDO::FETCH_COLUMN);

    return array_values(array_filter(array_map('trim', $rows)));
}

/**
 * Build the payload the front-end modal reads. Everything is pre-formatted here
 * so the JavaScript only has to place strings into the DOM.
 */
function business_payload(array $row): array
{
    $website = normalize_url($row['website']);
    $waDigits = phone_digits($row['whatsapp']);

    return [
        'id'          => (int) $row['id'],
        'name'        => (string) $row['name'],
        'category'    => (string) $row['category'],
        'description' => (string) $row['description'],
        'address'     => (string) $row['address'],
        'phone'       => (string) $row['phone'],
        'phoneHref'   => $row['phone'] !== '' ? 'tel:' . phone_digits($row['phone']) : '',
        'whatsapp'    => (string) $row['whatsapp'],
        'whatsappHref' => $waDigits !== '' ? 'https://wa.me/' . $waDigits : '',
        'email'       => (string) $row['email'],
        'emailHref'   => $row['email'] !== '' ? 'mailto:' . $row['email'] : '',
        'website'     => pretty_url($website),
        'websiteHref' => $website,
        'logo'        => $row['logo'] !== '' ? 'uploads/' . $row['logo'] : '',
        'initials'    => initials((string) $row['name']),
    ];
}
