<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
if (current_user()) redirect('admin/');
$error = '';
if (is_post()) {
    csrf_check();
    $r = attempt_login(input('email'), (string) ($_POST['password'] ?? ''));
    if ($r === true) {
        $to = $_SESSION['after_login'] ?? '';
        unset($_SESSION['after_login']);
        redirect(is_string($to) && str_starts_with($to, BASE_PATH . '/admin/') ? $to : 'admin/');
    }
    $error = $r === 'locked'
        ? 'Too many failed attempts. For your security, login is paused for ' . LOGIN_LOCK_MINUTES . ' minutes.'
        : 'That email and password do not match.';
}
header('Cache-Control: no-store');
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Log in · SkyTydes Dashboard</title>
<link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
<link rel="icon" href="<?= asset('img/favicon-32.png') ?>" type="image/png" sizes="32x32">
<link rel="icon" href="<?= asset('img/favicon-192.png') ?>" type="image/png" sizes="192x192">
<link rel="apple-touch-icon" href="<?= asset('img/apple-touch-icon.png') ?>">
</head><body class="auth">
<main class="auth-card">
  <img src="<?= asset('img/logo-color.svg') ?>" alt="SkyTydes Safaris" class="auth-logo">
  <h1>Dashboard login</h1>
  <?php if ($error): ?><div class="notice error"><?= h($error) ?></div><?php endif; ?>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <label>Email<input type="email" name="email" required autocomplete="username" value="<?= h(input('email')) ?>"></label>
    <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn block">Log in</button>
  </form>
  <p class="muted small center">Forgot your password? Ask another administrator to reset it from Users.</p>
</main>
</body></html>
