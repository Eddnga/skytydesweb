<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
$user = require_login();

if (is_post()) {
    csrf_check();
    $id = (int) input('id');
    $do = input('do');
    if ($do === 'status' && $id) {
        $st = in_array(input('status'), ['new', 'contacted', 'booked', 'closed'], true) ? input('status') : 'new';
        q('UPDATE leads SET status = ? WHERE id = ?', [$st, $id]);
    } elseif ($do === 'delete' && $id) {
        q('DELETE FROM leads WHERE id = ?', [$id]);
        flash('ok', 'Lead deleted.');
    } elseif ($do === 'purge' && $user['role'] === 'admin') {
        q('DELETE FROM leads WHERE created_at < ?', [date('Y-m-d H:i:s', time() - 365 * 86400)]);
        flash('ok', 'Leads older than a year were removed.');
    }
    audit('lead_' . $do, 'leads', $id ?: null);
    redirect('admin/leads.php' . (input('f') ? '?f=' . rawurlencode(input('f')) : ''));
}

$f = in_array(input('f'), ['new', 'contacted', 'booked', 'closed'], true) ? input('f') : '';
$where = $f ? 'WHERE status = ?' : '';
$params = $f ? [$f] : [];

if (input('export') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="skytydes-leads-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Source', 'Item', 'Name', 'Travel date', 'Travellers', 'Message', 'Status', 'Page']);
    foreach (rows("SELECT * FROM leads $where ORDER BY created_at DESC", $params) as $l) {
        // prevent spreadsheet formula injection
        $safe = array_map(fn($v) => preg_match('/^[=+\-@]/', (string) $v) ? "'" . $v : $v, [$l['created_at'], $l['source'], $l['item'], $l['name'], $l['travel_date'], $l['travellers'], $l['message'], $l['status'], $l['page']]);
        fputcsv($out, $safe);
    }
    exit;
}

$list = rows("SELECT * FROM leads $where ORDER BY created_at DESC LIMIT 300", $params);
admin_header('WhatsApp leads', 'leads');
?>
<p class="muted">Every time a visitor taps a WhatsApp booking button, we log what they asked about here — the chat itself continues in WhatsApp. Use the status to track follow-ups.</p>
<div class="toolbar">
  <nav class="tabs">
    <a href="<?= url('admin/leads.php') ?>" class="<?= $f === '' ? 'on' : '' ?>">All</a>
    <?php foreach (['new', 'contacted', 'booked', 'closed'] as $s): ?><a href="<?= url('admin/leads.php?f=' . $s) ?>" class="<?= $f === $s ? 'on' : '' ?>"><?= ucfirst($s) ?> <b><?= (int) val('SELECT COUNT(*) FROM leads WHERE status = ?', [$s]) ?></b></a><?php endforeach; ?>
  </nav>
  <a class="btn ghost sm" href="<?= url('admin/leads.php?export=csv' . ($f ? '&f=' . $f : '')) ?>">Export CSV</a>
</div>
<div class="card table-wrap">
<?php if (!$list): ?><p class="empty">No leads yet.</p><?php else: ?>
<table class="table">
  <thead><tr><th>Date</th><th>Asked about</th><th>Name</th><th>Travel date</th><th>Travellers</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($list as $l): ?>
    <tr>
      <td data-l="Date" class="nowrap"><?= h(fdate($l['created_at'], 'j M Y, H:i')) ?></td>
      <td data-l="Asked about"><strong><?= h($l['item'] ?: ucfirst($l['source'])) ?></strong><br><span class="muted small"><?= h(ucfirst($l['source'])) ?><?= $l['message'] ? ' · ' . h(excerpt($l['message'], 80)) : '' ?></span></td>
      <td data-l="Name"><?= h($l['name']) ?></td>
      <td data-l="Travel date"><?= h($l['travel_date']) ?></td>
      <td data-l="Travellers"><?= h($l['travellers']) ?></td>
      <td data-l="Status">
        <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="do" value="status"><input type="hidden" name="id" value="<?= (int) $l['id'] ?>"><input type="hidden" name="f" value="<?= h($f) ?>">
          <select name="status" class="sel-sm st-<?= h($l['status']) ?>" data-autosubmit><?php foreach (['new', 'contacted', 'booked', 'closed'] as $s): ?><option value="<?= $s ?>" <?= $l['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select>
        </form>
      </td>
      <td class="right"><form method="post" class="inline" data-confirm="Delete this lead?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= (int) $l['id'] ?>"><button class="ib danger" title="Delete"><?= icon('trash', 18) ?></button></form></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
</div>
<?php if ($user['role'] === 'admin'): ?>
<form method="post" data-confirm="Remove all leads older than one year?"><?= csrf_field() ?><input type="hidden" name="do" value="purge"><button class="btn ghost sm">Remove leads older than a year</button></form>
<?php endif; ?>
<?php admin_footer();
