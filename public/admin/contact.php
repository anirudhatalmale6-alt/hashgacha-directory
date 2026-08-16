<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require_login();

$textFields = [
    'site_title', 'site_tagline', 'brand_initials', 'theme_accent',
    'contact_title', 'contact_intro', 'contact_phone', 'contact_whatsapp',
    'contact_email', 'contact_address', 'contact_hours', 'footer_note',
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $email = trim((string) ($_POST['contact_email'] ?? ''));
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['contact_email'] = 'That does not look like a valid email address.';
    }

    $accent = trim((string) ($_POST['theme_accent'] ?? ''));
    if ($accent !== '' && !preg_match('/^#[0-9a-f]{6}$/i', $accent)) {
        $errors['theme_accent'] = 'Use a hex colour such as #1f4b8e.';
    }

    $newLogo = null;
    try {
        $newLogo = handle_logo_upload('logo', 'site');
    } catch (RuntimeException $exception) {
        $errors['logo'] = $exception->getMessage();
    }

    if (!$errors) {
        foreach ($textFields as $key) {
            save_setting($key, trim((string) ($_POST[$key] ?? '')));
        }

        $oldLogo = setting('logo');
        if ($newLogo !== null) {
            save_setting('logo', $newLogo);
            delete_upload($oldLogo);
        } elseif (!empty($_POST['remove_logo'])) {
            save_setting('logo', '');
            delete_upload($oldLogo);
        }

        flash('Contact details and branding saved.');
        redirect('contact.php');
    }
}

$s = settings();
$pageTitle = 'Contact & Branding';
$activeNav = 'contact';
require __DIR__ . '/layout.php';
?>

<div class="a-head">
  <div>
    <h1>Contact &amp; Branding</h1>
    <p class="a-sub">Your logo, name and the contact details shown at the bottom of the page.</p>
  </div>
</div>

<?php if ($errors): ?>
  <div class="a-flash a-flash-error">Please correct the highlighted fields.</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="a-form">
  <?= csrf_field() ?>

  <div class="a-card a-card-pad">
    <h2 class="a-card-title">Hashgacha logo</h2>
    <p class="a-hint">Shown in the header and at the top of the page. PNG with a transparent background works best.</p>

    <div class="a-logo-row">
      <div class="a-thumb a-thumb-lg" id="logoPreview">
        <?php if ($s['logo'] !== '' && is_file(UPLOAD_DIR . '/' . $s['logo'])): ?>
          <img src="../uploads/<?= e($s['logo']) ?>" alt="">
        <?php else: ?>
          <span><?= e($s['brand_initials'] ?: 'H') ?></span>
        <?php endif; ?>
      </div>
      <div class="a-logo-controls">
        <input type="file" name="logo" id="logoInput" accept="image/*">
        <?php if ($s['logo'] !== ''): ?>
          <label class="a-check">
            <input type="checkbox" name="remove_logo" value="1">
            <span>Remove the current logo</span>
          </label>
        <?php endif; ?>
        <?php if (isset($errors['logo'])): ?><small class="a-err"><?= e($errors['logo']) ?></small><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="a-card a-card-pad">
    <h2 class="a-card-title">Name &amp; appearance</h2>

    <div class="a-grid-2">
      <label class="a-field">
        <span>Hashgacha name</span>
        <input type="text" name="site_title" value="<?= e($s['site_title']) ?>">
      </label>

      <label class="a-field">
        <span>Short tagline <em>under the name in the header</em></span>
        <input type="text" name="site_tagline" value="<?= e($s['site_tagline']) ?>">
      </label>

      <label class="a-field">
        <span>Initials <em>used only when no logo is uploaded</em></span>
        <input type="text" name="brand_initials" value="<?= e($s['brand_initials']) ?>" maxlength="3">
      </label>

      <label class="a-field">
        <span>Accent colour</span>
        <span class="a-color">
          <input type="color" name="theme_accent" value="<?= e(preg_match('/^#[0-9a-f]{6}$/i', $s['theme_accent']) ? $s['theme_accent'] : '#1f4b8e') ?>">
          <code><?= e($s['theme_accent']) ?></code>
        </span>
        <?php if (isset($errors['theme_accent'])): ?><small class="a-err"><?= e($errors['theme_accent']) ?></small><?php endif; ?>
      </label>
    </div>
  </div>

  <div class="a-card a-card-pad">
    <h2 class="a-card-title">Hashgacha contact information</h2>

    <label class="a-field">
      <span>Section title</span>
      <input type="text" name="contact_title" value="<?= e($s['contact_title']) ?>">
    </label>

    <label class="a-field">
      <span>Intro text</span>
      <textarea name="contact_intro" rows="2"><?= e($s['contact_intro']) ?></textarea>
    </label>

    <div class="a-grid-2">
      <label class="a-field">
        <span>Phone</span>
        <input type="text" name="contact_phone" value="<?= e($s['contact_phone']) ?>">
      </label>

      <label class="a-field">
        <span>WhatsApp <em>with country code</em></span>
        <input type="text" name="contact_whatsapp" value="<?= e($s['contact_whatsapp']) ?>">
      </label>

      <label class="a-field">
        <span>Email</span>
        <input type="text" name="contact_email" value="<?= e($s['contact_email']) ?>">
        <?php if (isset($errors['contact_email'])): ?><small class="a-err"><?= e($errors['contact_email']) ?></small><?php endif; ?>
      </label>

      <label class="a-field">
        <span>Office hours</span>
        <input type="text" name="contact_hours" value="<?= e($s['contact_hours']) ?>">
      </label>
    </div>

    <label class="a-field">
      <span>Office address</span>
      <input type="text" name="contact_address" value="<?= e($s['contact_address']) ?>">
    </label>

    <p class="a-hint">Leave any field empty to hide that card on the website.</p>
  </div>

  <div class="a-card a-card-pad">
    <h2 class="a-card-title">Footer</h2>
    <label class="a-field">
      <span>Footer note</span>
      <textarea name="footer_note" rows="2"><?= e($s['footer_note']) ?></textarea>
    </label>
  </div>

  <div class="a-form-actions">
    <button class="a-btn a-btn-primary" type="submit">Save changes</button>
    <a class="a-btn a-btn-quiet" href="../index.php" target="_blank" rel="noopener">Preview site</a>
  </div>
</form>

<?php require __DIR__ . '/layout_end.php'; ?>
