<?php
declare(strict_types=1);

require __DIR__ . '/../src/init.php';
require __DIR__ . '/../src/helpers.php';

$s          = settings();
$businesses = active_businesses();
$categories = business_categories();
$payload    = array_map('business_payload', $businesses);
$showSearch = $s['show_search'] === '1' && count($businesses) > 5;
$formUrl    = normalize_url($s['google_form_url']);
$waDigits   = intl_digits($s['contact_whatsapp']);
$telDigits  = intl_digits($s['contact_phone']);
$hasLogo    = $s['logo'] !== '' && is_file(UPLOAD_DIR . '/' . $s['logo']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($s['site_title']) ?></title>
<meta name="description" content="<?= e(mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($s['about_text'])) ?? ''), 0, 160)) ?>">
<?php if ($hasLogo): ?>
  <link rel="icon" href="<?= e('uploads/' . $s['logo']) ?>">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset_url('assets/style.css') ?>">
<style>:root{--accent:<?= e($s['theme_accent'] ?: '#b29228') ?>;--button:<?= e($s['theme_button'] ?: '#2a6a9a') ?>;}</style>
</head>
<body>

<a class="skip-link" href="#about">Skip to content</a>

<header class="topbar" id="topbar">
  <div class="wrap topbar-inner">
    <a class="brand" href="#top"><?= e($s['site_title']) ?></a>

    <nav class="tabs" id="tabs" aria-label="Sections">
      <a href="#home" class="tab is-active"><?= e($s['nav_home']) ?></a>
      <a href="#about" class="tab"><?= e($s['nav_about']) ?></a>
      <a href="#businesses" class="tab"><?= e($s['nav_businesses']) ?></a>
      <a href="#contact" class="tab"><?= e($s['nav_contact']) ?></a>
      <span class="tab-indicator" aria-hidden="true"></span>
    </nav>

    <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="tabs" aria-label="Open menu">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<main id="top">

  <section class="hero" id="home">
    <div class="wrap hero-inner">
      <?php if ($hasLogo): ?>
        <img class="hero-logo" src="<?= e('uploads/' . $s['logo']) ?>" alt="<?= e($s['site_title']) ?>">
      <?php else: ?>
        <div class="hero-logo hero-logo-fallback"><?= e($s['brand_initials'] ?: 'H') ?></div>
      <?php endif; ?>

      <h1 class="hero-name"><?= e($s['site_title']) ?></h1>

      <div class="hero-actions">
        <a class="btn btn-primary" href="#businesses"><?= e($s['hero_btn_text']) ?></a>
        <?php if ($formUrl !== ''): ?>
          <a class="btn btn-outline" href="<?= e($formUrl) ?>" target="_blank" rel="noopener"><?= e($s['request_btn_text']) ?></a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="section section-about" id="about">
    <div class="wrap about-inner">
      <h2><?= e($s['about_title']) ?></h2>
      <div class="prose"><?= paragraphs($s['about_text']) ?></div>

      <?php if ($formUrl !== ''): ?>
        <a class="btn btn-primary btn-lg request-btn" href="<?= e($formUrl) ?>" target="_blank" rel="noopener">
          <?= e($s['request_btn_text']) ?>
          <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M11 3v2h2.6l-6.3 6.3 1.4 1.4L15 6.4V9h2V3z"/><path d="M15 15H5V5h4V3H3v14h14v-6h-2z"/></svg>
        </a>
      <?php endif; ?>
    </div>
  </section>

  <section class="section" id="businesses">
    <div class="wrap">
      <div class="section-head section-head-center">
        <h2><?= e($s['businesses_title']) ?></h2>
        <?php if (trim($s['businesses_intro']) !== ''): ?>
          <p class="section-intro"><?= e($s['businesses_intro']) ?></p>
        <?php endif; ?>
      </div>

      <?php if ($showSearch || $categories): ?>
        <div class="filters">
          <?php if ($showSearch): ?>
            <div class="search">
              <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M8.5 3a5.5 5.5 0 0 1 4.38 8.84l3.64 3.64-1.41 1.41-3.64-3.64A5.5 5.5 0 1 1 8.5 3zm0 2a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7z"/></svg>
              <input type="search" id="bizSearch" placeholder="Search businesses…" aria-label="Search businesses">
            </div>
          <?php endif; ?>
          <?php if ($categories): ?>
            <div class="chips" role="group" aria-label="Filter by category">
              <button class="chip is-active" data-cat="">All</button>
              <?php foreach ($categories as $cat): ?>
                <button class="chip" data-cat="<?= e($cat) ?>"><?= e($cat) ?></button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if (!$businesses): ?>
        <p class="empty">No businesses have been published yet.</p>
      <?php else: ?>
        <ul class="grid" id="bizGrid">
          <?php foreach ($businesses as $row): ?>
            <?php $b = business_payload($row); ?>
            <li class="grid-item"
                data-name="<?= e(mb_strtolower($b['name'])) ?>"
                data-cat="<?= e($b['category']) ?>">
              <button class="card" data-id="<?= $b['id'] ?>" aria-haspopup="dialog">
                <span class="card-logo">
                  <?php if ($b['logo'] !== '' && is_file(PUBLIC_DIR . '/' . $b['logo'])): ?>
                    <img src="<?= e($b['logo']) ?>" alt="<?= e($b['name']) ?> logo" loading="lazy">
                  <?php else: ?>
                    <span class="logo-fallback"><?= e($b['initials']) ?></span>
                  <?php endif; ?>
                </span>
                <span class="card-name"><?= e($b['name']) ?></span>
                <?php if ($b['category'] !== ''): ?>
                  <span class="card-cat"><?= e($b['category']) ?></span>
                <?php endif; ?>
              </button>
            </li>
          <?php endforeach; ?>
        </ul>
        <p class="empty" id="noResults" hidden>No businesses match that search.</p>
      <?php endif; ?>
    </div>
  </section>

  <section class="section section-contact" id="contact">
    <div class="wrap">
      <div class="section-head section-head-center">
        <?php if (mb_strtolower(trim($s['nav_contact'])) !== mb_strtolower(trim($s['contact_title']))): ?>
          <p class="eyebrow"><?= e($s['nav_contact']) ?></p>
        <?php endif; ?>
        <h2><?= e($s['contact_title']) ?></h2>
        <?php if (trim($s['contact_intro']) !== ''): ?>
          <p class="section-intro"><?= e($s['contact_intro']) ?></p>
        <?php endif; ?>
      </div>

      <?php if (trim($s['contact_name']) !== ''): ?>
        <p class="contact-person">
          <strong><?= e($s['contact_name']) ?></strong>
          <?php if (trim($s['contact_role']) !== ''): ?>
            <span><?= e($s['contact_role']) ?></span>
          <?php endif; ?>
        </p>
      <?php endif; ?>

      <div class="contact-grid">
        <?php if ($s['contact_phone'] !== ''): ?>
          <a class="contact-card" href="tel:+<?= e($telDigits) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.58 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.46.57 3.6a1 1 0 0 1-.25 1z"/></svg>
            <span class="contact-label">Call or text</span>
            <span class="contact-value"><?= e($s['contact_phone']) ?></span>
          </a>
        <?php endif; ?>

        <?php if ($waDigits !== ''): ?>
          <a class="contact-card" href="https://wa.me/<?= e($waDigits) ?>" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15l-1.3 4.7 4.8-1.26A10 10 0 1 0 12 2zm5.1 13.9c-.22.6-1.3 1.18-1.8 1.22-.46.05-1.05.07-1.7-.1a15.4 15.4 0 0 1-6.6-5.5c-.5-.75-.83-1.6-.83-2.4 0-.8.42-1.2.57-1.36a.86.86 0 0 1 .62-.28h.44c.14 0 .33-.05.52.4l.7 1.7c.06.12.1.27.02.43l-.28.44-.4.44c-.13.13-.27.27-.12.53.15.25.66 1.1 1.42 1.77.97.87 1.8 1.14 2.05 1.27.26.13.4.1.55-.06l.8-.93c.18-.22.34-.17.56-.1l1.6.76c.24.1.4.16.46.25.06.1.06.55-.16 1.15z"/></svg>
            <span class="contact-label">WhatsApp</span>
            <span class="contact-value"><?= e($s['contact_whatsapp']) ?></span>
          </a>
        <?php endif; ?>

        <?php if ($s['contact_email'] !== ''): ?>
          <a class="contact-card" href="mailto:<?= e($s['contact_email']) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm9 7.2L4.6 7H4v1l8 5.6L20 8V7h-.6z"/></svg>
            <span class="contact-label">Email</span>
            <span class="contact-value"><?= e($s['contact_email']) ?></span>
          </a>
        <?php endif; ?>

        <?php if ($s['contact_address'] !== ''): ?>
          <div class="contact-card contact-card-static">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg>
            <span class="contact-label">Office</span>
            <span class="contact-value"><?= e($s['contact_address']) ?></span>
          </div>
        <?php endif; ?>

        <?php if ($s['contact_hours'] !== ''): ?>
          <div class="contact-card contact-card-static">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
            <span class="contact-label">Office hours</span>
            <span class="contact-value"><?= e($s['contact_hours']) ?></span>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</main>

<footer class="footer">
  <div class="wrap footer-inner">
    <p class="footer-brand"><?= e($s['site_title']) ?></p>
    <?php if (trim($s['footer_note']) !== ''): ?>
      <p class="footer-note"><?= e($s['footer_note']) ?></p>
    <?php endif; ?>
    <p class="footer-copy">&copy; <?= date('Y') ?></p>
  </div>
</footer>

<!-- Business detail modal -->
<div class="modal" id="modal" hidden>
  <div class="modal-backdrop" data-close></div>
  <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="modalName">
    <button class="modal-close" data-close aria-label="Close">
      <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m5.6 4.2 4.4 4.4 4.4-4.4 1.4 1.4-4.4 4.4 4.4 4.4-1.4 1.4-4.4-4.4-4.4 4.4-1.4-1.4 4.4-4.4-4.4-4.4z"/></svg>
    </button>

    <div class="modal-head">
      <div class="modal-logo" id="modalLogo"></div>
      <div>
        <h3 id="modalName"></h3>
        <p class="modal-cat" id="modalCat" hidden></p>
      </div>
    </div>

    <p class="modal-desc" id="modalDesc" hidden></p>

    <ul class="detail-list" id="modalDetails"></ul>
  </div>
</div>

<script>window.BUSINESSES = <?= json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="<?= asset_url('assets/app.js') ?>"></script>
</body>
</html>
