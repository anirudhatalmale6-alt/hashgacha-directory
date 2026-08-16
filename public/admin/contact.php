<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require_login();

$textFields = [
    'site_title', 'brand_initials', 'theme_accent', 'theme_button', 'dial_code',
    'contact_title', 'contact_intro', 'contact_name', 'contact_role',
    'contact_phone', 'contact_whatsapp', 'contact_email', 'contact_address',
    'contact_hours', 'footer_note',
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $email = trim((string) ($_POST['contact_email'] ?? ''));
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['contact_email'] = 'That does not look like a valid email address.';
    }

    foreach (['theme_accent', 'theme_button'] as $key) {
        $colour = trim((string) ($_POST[$key] ?? ''));
        if ($colour !== '' && !preg_match('/^#[0-9a-f]{6}$/i', $colour)) {
            $errors[$key] = 'Use a hex colour such as #b29228.';
        }
    }

    $dial = trim((string) ($_POST['dial_code'] ?? ''));
    if ($dial !== '' && !preg_match('/^\+?\d{1,4}$/', $dial)) {
        $errors['dial_code'] = 'Use just the country code digits, for example 972.';
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
    <p class="a-sub">Your logo, your name, and the contact details at the bottom of the page.</p>
  </div>
</div>

<?php if ($errors): ?>
  <div class="a-flash a-flash-error">Please correct the highlighted fields.</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="a-form">
  <?= csrf_field() ?>

  <div class="a-card a-card-pad">
    <h2 class="a-card-title">Hashgacha logo</h2>
    <p class="a-hint">This is the large logo at the top of the page. PNG with a transparent background works best.</p>

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
    <h2 class="a-card-title">Name &amp; colours</h2>

    <div class="a-grid-2">
      <label class="a-field">
        <span>Hashgacha name <em>shown in the menu bar and under the logo</em></span>
        <input type="text" name="site_title" value="<?= e($s['site_title']) ?>">
      </label>

      <label class="a-field">
        <span>Initials <em>used only when no logo is uploaded</em></span>
        <input type="text" name="brand_initials" value="<?= e($s['brand_initials']) ?>" maxlength="3">
      </label>

      <label class="a-field">
        <span>Accent colour <em>headings, underlines</em></span>
        <span class="a-color">
          <input type="color" name="theme_accent" value="<?= e(preg_match('/^#[0-9a-f]{6}$/i', $s['theme_accent']) ? $s['theme_accent'] : '#b29228') ?>">
          <code><?= e($s['theme_accent']) ?></code>
        </span>
        <?php if (isset($errors['theme_accent'])): ?><small class="a-err"><?= e($errors['theme_accent']) ?></small><?php endif; ?>
      </label>

      <label class="a-field">
        <span>Button colour</span>
        <span class="a-color">
          <input type="color" name="theme_button" value="<?= e(preg_match('/^#[0-9a-f]{6}$/i', $s['theme_button']) ? $s['theme_button'] : '#2a6a9a') ?>">
          <code><?= e($s['theme_button']) ?></code>
        </span>
        <?php if (isset($errors['theme_button'])): ?><small class="a-err"><?= e($errors['theme_button']) ?></small><?php endif; ?>
      </label>
    </div>
  </div>

  <div class="a-card a-card-pad">
    <h2 class="a-card-title">Phone numbers</h2>
    <p class="a-hint">
      Numbers are shown on the site exactly as you type them here. For the tap-to-call
      and WhatsApp links to work from abroad they need a country code, so a number
      starting with <code>0</code> gets the code below added automatically. A number that
      already starts with <code>+</code> is left alone — use that for numbers in other
      countries.
    </p>

    <label class="a-field" style="max-width:220px">
      <span>Default country code</span>
      <input type="text" name="dial_code" value="<?= e($s['dial_code']) ?>" placeholder="972">
      <?php if (isset($errors['dial_code'])): ?><small class="a-err"><?= e($errors['dial_code']) ?></small><?php endif; ?>
    </label>
  </div>

  <div class="a-card a-card-pad">
    <h2 class="a-card-title">Contact section</h2>

    <div class="a-grid-2">
      <label class="a-field">
        <span>Section title</span>
        <input type="text" name="contact_title" value="<?= e($s['contact_title']) ?>">
      </label>

      <label class="a-field">
        <span>Intro text</span>
        <input type="text" name="contact_intro" value="<?= e($s['contact_intro']) ?>">
      </label>

      <label class="a-field">
        <span>Contact person</span>
        <input type="text" name="contact_name" value="<?= e($s['contact_name']) ?>">
      </label>

      <label class="a-field">
        <span>Their title</span>
        <input type="text" name="contact_role" value="<?= e($s['contact_role']) ?>">
      </label>

      <label class="a-field">
        <span>Phone</span>
        <input type="text" name="contact_phone" value="<?= e($s['contact_phone']) ?>">
      </label>

      <label class="a-field">
        <span>WhatsApp</span>
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
      <span>Address</span>
      <input type="text" name="contact_address" value="<?= e($s['contact_address']) ?>">
    </label>

    <p class="a-hint">Leave any field empty and its card disappears from the website.</p>
  </div>

  <div class="a-card a-card-pad">
    <h2 class="a-card-title">Footer</h2>
    <label class="a-field">
      <span>Footer note <em>optional</em></span>
      <textarea name="footer_note" rows="2"><?= e($s['footer_note']) ?></textarea>
    </label>
  </div>

  <div class="a-form-actions">
    <button class="a-btn a-btn-primary" type="submit">Save changes</button>
    <a class="a-btn a-btn-quiet" href="../index.php" target="_blank" rel="noopener">Preview site</a>
  </div>
</form>

<?php require __DIR__ . '/layout_end.php'; ?>
