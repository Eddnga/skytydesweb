<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
$user = require_login();

if (is_post()) {
    csrf_check();
    if (input('do') === 'delete' && ($id = (int) input('id'))) {
        $em = val('SELECT email FROM subscribers WHERE id = ?', [$id]);
        q('DELETE FROM subscribers WHERE id = ?', [$id]);
        audit('delete', 'subscribers', $id, ['name' => (string) $em, 'type' => 'subscriber']);
        flash('ok', 'Subscriber removed.');
    }
    redirect('admin/subscribers.php');
}
if (input('export') === 'csv') {
    audit('export', 'subscribers', null, ['what' => 'subscriber list (CSV)', 'rows' => (int) val('SELECT COUNT(*) FROM subscribers')]);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="skytydes-subscribers-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email', 'Name', 'Joined']);
    foreach (rows('SELECT * FROM subscribers ORDER BY created_at DESC') as $s) {
        fputcsv($out, array_map(fn($v) => preg_match('/^[=+\-@]/', (string) $v) ? "'" . $v : $v, [$s['email'], $s['name'], $s['created_at']]));
    }
    exit;
}
$list = rows('SELECT * FROM subscribers ORDER BY created_at DESC LIMIT 500');
$total = (int) val('SELECT COUNT(*) FROM subscribers');
admin_header('Newsletter subscribers', 'subscribers');
?>
<div class="toolbar">
  <p class="muted" style="margin:0"><strong><?= $total ?></strong> people have joined from the website. Export the list to use in Mailchimp, Brevo or Gmail.</p>
  <a class="btn ghost sm" href="<?= url('admin/subscribers.php?export=csv') ?>">Export CSV</a>
</div>
<div class="card table-wrap">
<?php if (!$list): ?><p class="empty">No subscribers yet. The sign-up box is in the website footer.</p><?php else: ?>
<table class="table"><thead><tr><th>Email</th><th>Name</th><th>Joined</th><th></th></tr></thead><tbody>
<?php foreach ($list as $s): ?>
  <tr><td data-l="Email"><?= h($s['email']) ?></td><td data-l="Name"><?= h($s['name']) ?></td><td data-l="Joined" class="muted small"><?= h(fdate($s['created_at'], 'j M Y')) ?></td>
  <td class="right"><form method="post" class="inline" data-confirm="Remove this subscriber?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><button class="ib danger" title="Remove"><?= icon('trash', 18) ?></button></form></td></tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>
</div>
<?php admin_footer();
