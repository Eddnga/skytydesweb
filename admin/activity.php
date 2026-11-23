<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
require_role('admin');

// friendly names for what was changed
$entities = [
    'tours' => ['tour', 'title'], 'destinations' => ['destination', 'name'], 'posts' => ['blog post', 'title'],
    'services' => ['service', 'title'], 'faqs' => ['FAQ', 'question'], 'offers' => ['offer', 'title'],
    'team' => ['host', 'name'], 'gallery' => ['photo', 'caption'], 'hero_slides' => ['hero slide', 'title'],
    'reviews' => ['review', 'name'], 'users' => ['user', 'name'], 'user' => ['user', 'name'], 'leads' => ['lead', 'item'],
    'subscribers' => ['subscriber', 'email'], 'settings' => ['site settings', ''],
];
$verbs = [
    'create' => 'Added', 'update' => 'Edited', 'delete' => 'Deleted', 'toggle' => 'Showed / hid', 'bulk_upload' => 'Uploaded photos to',
    'login' => 'Logged in', 'logout' => 'Logged out', 'password_change' => 'Changed their password',
    'review_approve' => 'Approved', 'review_reject' => 'Rejected', 'review_pending' => 'Moved to pending', 'review_delete' => 'Deleted', 'review_save' => 'Edited',
    'lead_status' => 'Updated status of', 'lead_delete' => 'Deleted', 'lead_purge' => 'Cleared old leads',
    'user_create' => 'Added', 'user_toggle' => 'Enabled / disabled', 'user_role' => 'Changed the role of', 'user_reset' => 'Reset the password of', 'user_delete' => 'Deleted',
];
function describe(array $a, array $entities, array $verbs): array
{
    $verb = $verbs[$a['action']] ?? ucfirst(str_replace('_', ' ', $a['action']));
    if (in_array($a['action'], ['login', 'logout', 'password_change', 'lead_purge'], true)) return [$verb, ''];
    [$label, $col] = $entities[$a['entity']] ?? [str_replace('_', ' ', (string) $a['entity']), ''];
    $what = $label;
    if ($a['entity_id'] && $col && preg_match('/^[a-z_]+$/', (string) $a['entity'])) {
        $table = $a['entity'] === 'user' ? 'users' : $a['entity'];
        try {
            $name = val("SELECT $col FROM $table WHERE id = ?", [(int) $a['entity_id']]);
        } catch (Throwable $e) {
            $name = null;
        }
        $what .= $name ? ': ' . excerpt((string) $name, 50) : ' #' . (int) $a['entity_id'] . (str_contains($a['action'], 'delete') ? '' : ' (since deleted)');
    }
    return [$verb, $what];
}
function where_from(?string $ip): string
{
    if (in_array($ip, ['127.0.0.1', '::1'], true)) return 'This computer';
    return (string) $ip;
}

$list = rows('SELECT a.*, u.name FROM audit_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 300');
$fails = rows('SELECT email, ip, attempted_at FROM login_attempts WHERE success = 0 ORDER BY id DESC LIMIT 20');
admin_header('Activity log', 'activity');
?>
<p class="muted">Every change made in the dashboard, newest first — useful when several people manage the site.</p>
<div class="cols wide-first">
<section class="card table-wrap">
  <h2 class="card-title">Recent changes</h2>
  <?php if (!$list): ?><p class="empty">Nothing yet.</p><?php else: ?>
  <table class="table">
    <thead><tr><th>When</th><th>Who</th><th>What happened</th><th>From</th></tr></thead>
    <tbody>
    <?php foreach ($list as $a): [$verb, $what] = describe($a, $entities, $verbs); ?>
      <tr>
        <td data-l="When" class="nowrap muted small"><?= h(fdate($a['created_at'], 'j M, H:i')) ?></td>
        <td data-l="Who" class="nowrap"><strong><?= h($a['name'] ?? '—') ?></strong></td>
        <td data-l="What happened"><?= h($verb) ?><?= $what !== '' ? ' <span class="muted">' . h($what) . '</span>' : '' ?></td>
        <td data-l="From" class="muted small nowrap"><?= h(where_from($a['ip'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>
<section class="card table-wrap">
  <h2 class="card-title">Failed logins</h2>
  <?php if (!$fails): ?><p class="empty">No failed logins — all good.</p><?php else: ?>
  <table class="table">
    <tbody>
    <?php foreach ($fails as $f): ?><tr><td><strong><?= h($f['email']) ?></strong><br><span class="muted small"><?= h(where_from($f['ip'])) ?></span></td><td class="right muted small nowrap"><?= h(fdate($f['attempted_at'], 'j M, H:i')) ?></td></tr><?php endforeach; ?>
    </tbody>
  </table><?php endif; ?>
</section>
</div>
<?php admin_footer();
