<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
$me = require_login();

if (is_post()) {
    csrf_check();
    $full = row('SELECT * FROM users WHERE id = ?', [$me['id']]);
    $cur = (string) ($_POST['current'] ?? '');
    $new = (string) ($_POST['new'] ?? '');
    if (!password_verify($cur, $full['password_hash'])) flash('error', 'Your current password is not correct.');
    elseif ($p = password_problems($new)) flash('error', 'New password: ' . $p);
    elseif ($new !== (string) ($_POST['new2'] ?? '')) flash('error', 'The new passwords do not match.');
    else {
        q('UPDATE users SET password_hash = ?, name = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), mb_substr(input('name') ?: $me['name'], 0, 120), $me['id']]);
        session_regenerate_id(true);
        audit('password_change', 'users', (int) $me['id']);
        flash('ok', 'Password changed.');
    }
    redirect('admin/account.php');
}
admin_header('My account', 'account');
?>
<section class="card edit narrow">
  <p><strong><?= h($me['name']) ?></strong><br><span class="muted"><?= h($me['email']) ?> · <?= h(ucfirst($me['role'])) ?></span></p>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <label>Display name<input name="name" value="<?= h($me['name']) ?>" maxlength="120"></label>
    <label>Current password<input type="password" name="current" required autocomplete="current-password"></label>
    <label>New password<input type="password" name="new" required minlength="10" autocomplete="new-password"></label>
    <label>Repeat new password<input type="password" name="new2" required minlength="10" autocomplete="new-password"></label>
    <p class="hint">At least 10 characters with letters and numbers. You will stay logged in.</p>
    <button class="btn">Change password</button>
  </form>
</section>
<?php admin_footer();
