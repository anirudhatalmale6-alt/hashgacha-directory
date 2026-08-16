<?php
/**
 * Admin session, CSRF and file-upload handling.
 */

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/init.php';
require dirname(__DIR__, 2) . '/src/helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('hashgacha_admin');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

function is_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function check_csrf(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(400);
        exit('Your session expired. Please go back and try again.');
    }
}

function flash(string $message, string $type = 'ok'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function take_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

/**
 * Validate and store an uploaded logo. Returns the stored filename,
 * or null when nothing usable was uploaded.
 *
 * @throws RuntimeException on a rejected file
 */
function handle_logo_upload(string $field, string $prefix = 'logo'): ?string
{
    if (empty($_FILES[$field]) || !is_array($_FILES[$field])) {
        return null;
    }

    $file = $_FILES[$field];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed (error code ' . $file['error'] . '). The file may be larger than the server allows.');
    }
    if ($file['size'] > 4 * 1024 * 1024) {
        throw new RuntimeException('That image is larger than 4 MB. Please upload a smaller file.');
    }

    $allowed = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_GIF  => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];

    $info = @getimagesize($file['tmp_name']);
    $ext = null;

    if ($info !== false && isset($allowed[$info[2]])) {
        $ext = $allowed[$info[2]];
    } elseif (str_starts_with((string) mime_content_type($file['tmp_name']), 'image/svg')) {
        // SVG has no raster dimensions; accept it but never execute it.
        $ext = 'svg';
    }

    if ($ext === null) {
        throw new RuntimeException('Only JPG, PNG, GIF, WEBP or SVG images are accepted.');
    }

    $name = $prefix . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = UPLOAD_DIR . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Could not save the uploaded file. Check that the uploads folder is writable.');
    }
    @chmod($dest, 0644);

    return $name;
}

/** Delete a previously stored upload, ignoring anything outside the uploads folder. */
function delete_upload(?string $name): void
{
    $name = trim((string) $name);
    if ($name === '' || str_contains($name, '/') || str_contains($name, '\\')) {
        return;
    }
    $path = UPLOAD_DIR . '/' . $name;
    if (is_file($path)) {
        @unlink($path);
    }
}
