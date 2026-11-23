<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
require_role('admin');
$list = rows('SELECT a.*, u.name FROM audit_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 300');
$fails = rows('SELECT email, ip, attempted_at FROM login_attempts WHERE success = 0 ORDER BY id DESC LIMIT 20');
admin_header('Activity log', 'activity');
?>
<div class="cols">
<section class="card table-wrap">
  <h2 class="sub">Recent changes</h2>
  <table class="table compact"><thead><tr><th>When</th><th>Who</th><th>Action</th><th>What</th><th>IP</th></tr></thead><tbody>
  <?php foreach ($list as $a): ?>
    <tr><td class="nowrap muted small"><?= h(fdate($a['created_at'], 'j M, H:i')) ?></td><td><?= h($a['name'] ?? '—') ?></td><td><?= h(str_replace('_', ' ', $a['action'])) ?></td><td class="muted"><?= h($a['entity']) ?><?= $a['entity_id'] ? ' #' . (int) $a['entity_id'] : '' ?></td><td class="muted small"><?= h($a['ip']) ?></td></tr>
  <?php endforeach; ?>
  </tbody></table>
</section>
<section class="card table-wrap">
  <h2 class="sub">Failed logins</h2>
  <?php if (!$fails): ?><p class="empty">No failed logins.</p><?php else: ?>
  <table class="table compact"><tbody>
  <?php foreach ($fails as $f): ?><tr><td><?= h($f['email']) ?><br><span class="muted small"><?= h($f['ip']) ?></span></td><td class="right muted small"><?= h(fdate($f['attempted_at'], 'j M, H:i')) ?></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?>
</section>
</div>
<?php admin_footer();
