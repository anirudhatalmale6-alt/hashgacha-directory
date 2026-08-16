<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require_login();

$fields = [
    'hero_heading', 'hero_subheading',
    'about_title', 'about_text', 'about_point_1', 'about_point_2', 'about_point_3',
    'request_heading', 'request_text', 'request_btn_text', 'google_form_url',
    'businesses_title', 'businesses_intro',
];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $formUrl = trim((string) ($_POST['google_form_url'] ?? ''));
    if ($formUrl !== '' && !filter_var(normalize_url($formUrl), FILTER_VALIDATE_URL)) {
        $error = 'The Google Form link does not look like a valid web address.';
    }

    if ($error === '') {
        foreach ($fields as $key) {
            $value = (string) ($_POST[$key] ?? '');
            if ($key === 'google_form_url') {
                $value = normalize_url($value);
            }
            save_setting($key, trim($value));
        }
        save_setting('show_search', isset($_POST['show_search']) ? '1' : '0');
        flash('Page content saved.');
        redirect('content.php');
    }
}

$s = settings();
$pageTitle = 'Page Content';
$activeNav = 'content';
require __DIR__ . '/layout.php';
?>

<div class="a-head">
  <div>
    <h1>Page Content</h1>
    <p class="a-sub">Everything the visitor reads on the page, in the order it appears.</p>
  </div>
</div>

<?php if ($error !== ''): ?>
  <div class="a-flash a-flash-error"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" class="a-form">
  <?= csrf_field() ?>

  <div class="a-card a-card-pad">
    <h2 class="a-card-title">Top of the page</h2>

    <label class="a-field">
      <span>Main heading</span>
      <input type="text" name="hero_heading" value="<?= e($s['hero_heading']) ?>">
    </label>

    <label class="a-field">
      <span>Sub-heading</span>
      <textarea name="hero_subheading" rows="2"><?= e($s['hero_subheading']) ?></textarea>
    </label>
  </div>

  <div class="a-card a-card-pad">
    <h2 class="a-card-title">About the Hashgacha</h2>

    <label class="a-field">
      <span>Section title</span>
      <input type="text" name="about_title" value="<?= e($s['about_title']) ?>">
    </label>

    <label class="a-field">
      <span>About text <em>leave a blank line between paragraphs</em></span>
      <textarea name="about_text" rows="8"><?= e($s['about_text']) ?></textarea>
    </label>

    <p class="a-hint">The three short points shown in the box beside the text. Leave one blank to hide it.</p>
    <?php foreach ([1, 2, 3] as $n): ?>
      <label class="a-field">
        <span>Point <?= $n ?></span>
        <input type="text" name="about_point_<?= $n ?>" value="<?= e($s['about_point_' . $n]) ?>">
      </label>
    <?php endforeach; ?>
  </div>

  <div class="a-card a-card-pad">
    <h2 class="a-card-title">Request Hashgacha</h2>

    <label class="a-field">
      <span>Heading</span>
      <input type="text" name="request_heading" value="<?= e($s['request_heading']) ?>">
    </label>

    <label class="a-field">
      <span>Text</span>
      <textarea name="request_text" rows="3"><?= e($s['request_text']) ?></textarea>
    </label>

    <div class="a-grid-2">
      <label class="a-field">
        <span>Button label</span>
        <input type="text" name="request_btn_text" value="<?= e($s['request_btn_text']) ?>">
      </label>

      <label class="a-field">
        <span>Google Form link</span>
        <input type="text" name="google_form_url" value="<?= e($s['google_form_url']) ?>" placeholder="https://forms.gle/...">
      </label>
    </div>

    <?php if (trim($s['google_form_url']) !== ''): ?>
      <p class="a-hint">
        Current link:
        <a href="<?= e(normalize_url($s['google_form_url'])) ?>" target="_blank" rel="noopener"><?= e(pretty_url($s['google_form_url'])) ?></a>
        — open it to check it works.
      </p>
    <?php endif; ?>
  </div>

  <div class="a-card a-card-pad">
    <h2 class="a-card-title">Certified Businesses section</h2>

    <label class="a-field">
      <span>Section title</span>
      <input type="text" name="businesses_title" value="<?= e($s['businesses_title']) ?>">
    </label>

    <label class="a-field">
      <span>Intro text</span>
      <textarea name="businesses_intro" rows="2"><?= e($s['businesses_intro']) ?></textarea>
    </label>

    <label class="a-check">
      <input type="checkbox" name="show_search" value="1" <?= $s['show_search'] === '1' ? 'checked' : '' ?>>
      <span>Show a search box (appears once there are more than 5 businesses)</span>
    </label>
  </div>

  <div class="a-form-actions">
    <button class="a-btn a-btn-primary" type="submit">Save changes</button>
    <a class="a-btn a-btn-quiet" href="../index.php" target="_blank" rel="noopener">Preview site</a>
  </div>
</form>

<?php require __DIR__ . '/layout_end.php'; ?>
