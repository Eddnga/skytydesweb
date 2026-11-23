<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
$me = require_role('admin');

if (is_post()) {
    csrf_check();
    $do = input('do');
    $id = (int) input('id');
    if ($do === 'create') {
        $email = strtolower(input('email'));
        $pass = (string) ($_POST['password'] ?? '');
        $role = input('role') === 'admin' ? 'admin' : 'editor';
        if (input('name') === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) flash('error', 'Enter a name and a valid email.');
        elseif ($p = password_problems($pass)) flash('error', 'Password: ' . $p);
        elseif (val('SELECT COUNT(*) FROM users WHERE email = ?', [$email])) flash('error', 'A user with that email already exists.');
        else {
            $nid = insert('users', ['name' => mb_substr(input('name'), 0, 120), 'email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'role' => $role, 'active' => 1, 'created_at' => now()]);
            audit('create', 'users', $nid);
            flash('ok', 'User added. Share the password with them privately and ask them to change it.');
        }
    } elseif ($id && $id !== (int) $me['id']) {
        if ($do === 'toggle') q('UPDATE users SET active = 1 - active WHERE id = ?', [$id]);
        if ($do === 'role') q('UPDATE users SET role = ? WHERE id = ?', [input('role') === 'admin' ? 'admin' : 'editor', $id]);
        if ($do === 'reset') {
            $pass = (string) ($_POST['password'] ?? '');
            if ($p = password_problems($pass)) flash('error', 'Password: ' . $p);
            else {
                q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $id]);
                flash('ok', 'Password reset.');
            }
        }
        if ($do === 'delete') q('DELETE FROM users WHERE id = ?', [$id]);
        audit('user_' . $do, 'users', $id);
    }
    redirect('admin/users.php');
}
$list = rows('SELECT * FROM users ORDER BY name');
admin_header('Users', 'users');
?>
<div class="toolbar"><span></span><button type="button" class="btn" data-modal-open="addModal"><?= icon('plus', 18) ?> Add user</button></div>
<p class="muted"><strong>Admins</strong> can do everything. <strong>Editors</strong> can manage content, reviews and leads, but not users or the activity log.</p>
<div class="card table-wrap">
<table class="table">
  <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Last login</th><th>Status</th><th class="right">Actions</th></tr></thead>
  <tbody>
  <?php foreach ($list as $u): $self = (int) $u['id'] === (int) $me['id']; ?>
    <tr>
      <td data-l="Name"><strong><?= h($u['name']) ?></strong><?= $self ? ' <span class="pill">You</span>' : '' ?></td>
      <td data-l="Email"><?= h($u['email']) ?></td>
      <td data-l="Role"><?php if ($self): ?><?= h(ucfirst($u['role'])) ?><?php else: ?>
        <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="do" value="role"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><select name="role" class="sel-sm" data-autosubmit><option value="editor" <?= $u['role'] === 'editor' ? 'selected' : '' ?>>Editor</option><option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option></select></form>
      <?php endif; ?></td>
      <td data-l="Last login" class="muted small"><?= h($u['last_login_at'] ? fdate($u['last_login_at'], 'j M Y, H:i') : 'Never') ?></td>
      <td data-l="Status"><?= $u['active'] ? '<span class="pill ok">Active</span>' : '<span class="pill">Disabled</span>' ?></td>
      <td class="right nowrap"><?php if (!$self): ?>
        <details class="inline-details"><summary class="btn ghost sm">Reset password</summary>
          <form method="post" class="form mini"><?= csrf_field() ?><input type="hidden" name="do" value="reset"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><input type="password" name="password" placeholder="New password" minlength="10" required><button class="btn sm">Set</button></form></details>
        <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="do" value="toggle"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><button class="btn ghost sm"><?= $u['active'] ? 'Disable' : 'Enable' ?></button></form>
        <form method="post" class="inline" data-confirm="Delete this user?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><button class="ib danger" title="Delete"><?= icon('trash', 18) ?></button></form>
      <?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<div class="modal" id="addModal" hidden>
  <div class="modal-box narrow" role="dialog" aria-modal="true" aria-labelledby="addTitle">
  <div class="modal-head"><h2 id="addTitle">Add a user</h2><button type="button" class="ib" data-modal-close aria-label="Close"><?= icon('x', 22) ?></button></div>
  <div class="modal-body">
  <form method="post" class="form" autocomplete="off">
    <?= csrf_field() ?><input type="hidden" name="do" value="create">
    <div class="grid2">
      <label>Name<input name="name" required maxlength="120"></label>
      <label>Email<input type="email" name="email" required></label>
      <label>Temporary password<input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
      <label>Role<select name="role"><option value="editor">Editor</option><option value="admin">Admin</option></select></label>
    </div>
    <div class="actions"><button class="btn"><?= icon('plus', 18) ?> Add user</button><button type="button" class="btn ghost" data-modal-close>Cancel</button></div>
  </form>
  </div></div>
</div>
<?php admin_footer();
