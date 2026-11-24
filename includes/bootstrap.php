<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('STORAGE', ROOT . '/storage');
date_default_timezone_set('Africa/Nairobi');

if (!is_file(ROOT . '/config.php')) {
    // Not installed yet → send to the installer
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($script, '/install/') === false) {
        $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
        $base = preg_replace('#/admin$#', '', $base);
        header('Location: ' . $base . '/install/');
        exit;
    }
}

if (is_file(ROOT . '/config.php')) {
    $CONFIG = require ROOT . '/config.php';
} else {
    $CONFIG = require ROOT . '/config.sample.php';
    $CONFIG['force_https'] = false;
    $CONFIG['base_url'] = '';
}

// ---------- errors ----------
$isDev = ($CONFIG['env'] ?? 'production') !== 'production';
error_reporting(E_ALL);
ini_set('display_errors', $isDev ? '1' : '0');
ini_set('log_errors', '1');
if (!is_dir(STORAGE . '/logs')) @mkdir(STORAGE . '/logs', 0750, true);
ini_set('error_log', STORAGE . '/logs/php-error.log');

// ---------- HTTPS ----------
function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);
}
if (!empty($CONFIG['force_https']) && !is_https() && PHP_SAPI !== 'cli') {
    header('Location: https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}

// ---------- base path ----------
$bp = parse_url($CONFIG['base_url'] ?? '', PHP_URL_PATH) ?: '';
define('BASE_PATH', rtrim($bp, '/'));

// ---------- security headers ----------
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self'; connect-src 'self'; frame-src https://www.google.com https://maps.google.com; frame-ancestors 'none'; base-uri 'self'; form-action 'self' https://wa.me https://api.whatsapp.com; object-src 'none'");
    if (is_https()) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// ---------- session ----------
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_name('sky_sess');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => (BASE_PATH ?: '') . '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/upload.php';
require __DIR__ . '/markdown.php';
require __DIR__ . '/site.php';
require __DIR__ . '/insights.php';
require __DIR__ . '/sections.php';
require __DIR__ . '/seo.php';
require __DIR__ . '/migrate.php';
maybe_migrate();
