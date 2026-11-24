<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
$me = require_role('admin');

// ---------- how each action reads ----------
$entities = [
    'tours' => ['tour', 'title'], 'destinations' => ['destination', 'name'], 'posts' => ['blog post', 'title'],
    'services' => ['service', 'title'], 'faqs' => ['FAQ', 'question'], 'offers' => ['offer', 'title'],
    'team' => ['host', 'name'], 'gallery' => ['photo', 'caption'], 'hero_slides' => ['hero slide', 'title'],
    'documents' => ['document', 'title'], 'reviews' => ['review', 'name'], 'users' => ['user', 'name'], 'user' => ['user', 'name'],
    'leads' => ['lead', 'item'], 'subscribers' => ['subscriber', 'email'], 'settings' => ['site settings', ''],
    'custom_types' => ['content section', 'label'], 'database' => ['database', ''], 'page' => ['page', ''], 'form' => ['form', ''],
];
$verbs = [
    'create' => 'Added', 'update' => 'Edited', 'delete' => 'Deleted', 'toggle' => 'Showed / hid', 'bulk_upload' => 'Uploaded photos to', 'export' => 'Exported',
    'login' => 'Logged in', 'logout' => 'Logged out', 'password_change' => 'Changed their password',
    'login_failed' => 'Failed login', 'login_locked' => 'Login locked after too many tries', 'session_expired' => 'Was logged out (inactive)',
    'session_blocked' => 'Session blocked', 'access_denied' => 'Was refused access to', 'csrf_blocked' => 'Blocked an expired / forged form on',
    'review_add' => 'Added', 'review_approve' => 'Approved', 'review_reject' => 'Rejected', 'review_pending' => 'Moved to pending', 'review_delete' => 'Deleted', 'review_save' => 'Edited',
    'lead_status' => 'Updated status of', 'lead_delete' => 'Deleted', 'lead_purge' => 'Cleared old leads',
    'user_create' => 'Added', 'user_toggle' => 'Enabled / disabled', 'user_role' => 'Changed the role of', 'user_reset' => 'Reset the password of', 'user_delete' => 'Deleted',
    'visitor_review' => 'Visitor wrote a review', 'visitor_subscribe' => 'Visitor joined the newsletter', 'visitor_lead' => 'Visitor opened WhatsApp about', 'visitor_document' => 'Visitor opened',
    'db_backup' => 'Downloaded a database backup', 'db_optimize' => 'Optimised the database', 'db_migrate' => 'Ran database updates', 'db_purge' => 'Cleared old data',
    'section_create' => 'Created content section', 'section_update' => 'Changed content section', 'section_delete' => 'Deleted content section',
    'pretty_check' => 'Re-tested clean web addresses', 'geo_lookup' => 'Looked up visitor locations',
];
$kinds = [
    '' => 'Everything',
    'content' => 'Content changes',
    'security' => 'Logins & security',
    'visitors' => 'Visitor activity',
    'admin' => 'Users, settings & system',
];
$kindSql = [
    'content' => "a.action IN ('create','update','delete','toggle','bulk_upload','review_add','review_approve','review_reject','review_pending','review_delete','review_save','lead_status','lead_delete','lead_purge') AND a.entity NOT IN ('users','settings')",
    'security' => "a.action IN ('login','logout','password_change','login_failed','login_locked','session_expired','session_blocked','access_denied','csrf_blocked','user_reset')",
    'visitors' => "SUBSTR(a.action, 1, 8) = 'visitor_'",
    'admin' => "(a.entity IN ('users','settings','database','custom_types') OR SUBSTR(a.action, 1, 3) = 'db_' OR SUBSTR(a.action, 1, 8) = 'section_' OR a.action IN ('export','pretty_check','geo_lookup'))",
];
$alarm = ['login_failed', 'login_locked', 'session_blocked', 'access_denied', 'csrf_blocked'];

function describe(array $a, array $entities, array $verbs): array
{
    $d = $a['details'] ? (json_decode($a['details'], true) ?: []) : [];
    $verb = $verbs[$a['action']] ?? ucfirst(str_replace('_', ' ', $a['action']));
    $extra = [];
    if (!empty($d['changed'])) $extra[] = 'Changed: ' . implode(', ', (array) $d['changed']);
    if (isset($d['from'], $d['to'])) $extra[] = $d['from'] . ' → ' . $d['to'];
    if (!empty($d['now'])) $extra[] = 'Now ' . $d['now'];
    if (!empty($d['email']) && str_starts_with($a['action'], 'login')) $extra[] = $d['email'] . (!empty($d['reason']) ? ' (' . $d['reason'] . ')' : '');
    if (!empty($d['browser'])) $extra[] = $d['browser'];
    if (!empty($d['page'])) $extra[] = $d['page'];
    if (!empty($d['added'])) $extra[] = $d['added'] . ' added' . (!empty($d['failed']) ? ', ' . $d['failed'] . ' failed' : '');
    if (!empty($d['rows'])) $extra[] = $d['rows'] . ' rows';
    if (!empty($d['what'])) $extra[] = $d['what'];
    if (!empty($d['via'])) $extra[] = 'from a ' . $d['via'] . ' button';
    if (!empty($d['rating'])) $extra[] = $d['rating'];
    if (!empty($d['note'])) $extra[] = $d['note'];
    if (in_array($a['action'], ['login', 'logout', 'password_change', 'lead_purge', 'login_failed', 'login_locked', 'session_expired', 'session_blocked', 'csrf_blocked', 'visitor_subscribe', 'db_backup', 'db_optimize', 'db_migrate', 'db_purge', 'pretty_check', 'geo_lookup'], true)) {
        return [$verb, $a['action'] === 'visitor_subscribe' ? (string) ($d['name'] ?? '') : '', implode(' · ', $extra)];
    }
    [$label, $col] = $entities[$a['entity']] ?? [str_replace('_', ' ', (string) $a['entity']), ''];
    if (!empty($d['type'])) $label = $d['type'];
    if (str_starts_with($a['action'], 'visitor_') || str_starts_with($a['action'], 'section_')) $label = '';
    if ($a['action'] === 'bulk_upload') $label = 'the gallery';
    $name = $d['name'] ?? null;
    if ($name === null && $a['entity_id'] && $col && preg_match('/^[a-z_]+$/', (string) $a['entity'])) {
        $table = $a['entity'] === 'user' ? 'users' : $a['entity'];
        try {
            $name = val("SELECT $col FROM $table WHERE id = ?", [(int) $a['entity_id']]);
        } catch (Throwable $e) {
            $name = null;
        }
    }
    $what = trim($label . ($name ? ($label ? ': ' : '') . excerpt((string) $name, 60) : ($a['entity_id'] && $label ? ' #' . (int) $a['entity_id'] : '')));
    return [$verb, $what, implode(' · ', $extra)];
}

// ---------- filters ----------
$who = input('who');
$kind = array_key_exists(input('kind'), $kinds) ? input('kind') : '';
$from = preg_match('/^\d{4}-\d{2}-\d{2}$/', input('from')) ? input('from') : '';
$to = preg_match('/^\d{4}-\d{2}-\d{2}$/', input('to')) ? input('to') : '';
$search = mb_substr(input('q'), 0, 80);
$where = []; $args = [];
if ($who === 'visitors') $where[] = 'a.user_id IS NULL';
elseif (ctype_digit($who)) { $where[] = 'a.user_id = ?'; $args[] = (int) $who; }
if ($kind) $where[] = $kindSql[$kind];
if ($from) { $where[] = 'a.created_at >= ?'; $args[] = $from . ' 00:00:00'; }
if ($to) { $where[] = 'a.created_at <= ?'; $args[] = $to . ' 23:59:59'; }
if ($search !== '') { $where[] = '(a.details LIKE ? OR a.action LIKE ? OR a.ip LIKE ? OR u.name LIKE ?)'; array_push($args, "%$search%", "%$search%", "%$search%", "%$search%"); }
$W = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$per = 50;
$total = (int) val("SELECT COUNT(*) FROM audit_log a LEFT JOIN users u ON u.id = a.user_id $W", $args);
$pages = max(1, (int) ceil($total / $per));
$page = max(1, min($pages, (int) input('p', '1')));
$qs = fn(array $o = []) => http_build_query(array_filter(['who' => $who, 'kind' => $kind, 'from' => $from, 'to' => $to, 'q' => $search] + $o, fn($v) => $v !== '' && $v !== null));

// ---------- CSV export ----------
if (input('export') === 'csv') {
    audit('export', 'audit_log', null, ['what' => 'activity log (CSV)', 'rows' => $total]);
    $all = rows("SELECT a.*, u.name FROM audit_log a LEFT JOIN users u ON u.id = a.user_id $W ORDER BY a.id DESC LIMIT 20000", $args);
    $geo = geo_map(array_column($all, 'ip'));
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="skytydes-activity-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['When', 'Who', 'What happened', 'Item', 'Details', 'IP', 'Location']);
    foreach ($all as $a) {
        [$verb, $what, $extra] = describe($a, $entities, $verbs);
        $row = [$a['created_at'], $a['name'] ?? 'Visitor / system', $verb, $what, $extra, $a['ip'], $geo[$a['ip']]['place'] ?? ''];
        fputcsv($out, array_map(fn($v) => preg_match('/^[=+\-@]/', (string) $v) ? "'" . $v : $v, $row));
    }
    exit;
}

$list = rows("SELECT a.*, u.name, u.role FROM audit_log a LEFT JOIN users u ON u.id = a.user_id $W ORDER BY a.id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $args);
geo_resolve(array_column($list, 'ip'), 12);
$geo = geo_map(array_column($list, 'ip'));
$users = rows('SELECT id, name FROM users ORDER BY name');
$since = date('Y-m-d H:i:s', time() - 86400);
$fails24 = (int) val('SELECT COUNT(*) FROM login_attempts WHERE success = 0 AND attempted_at >= ?', [$since]);
$topFailIps = rows('SELECT ip, COUNT(*) n, MAX(attempted_at) last FROM login_attempts WHERE success = 0 AND attempted_at >= ? GROUP BY ip ORDER BY n DESC LIMIT 6', [date('Y-m-d H:i:s', time() - 7 * 86400)]);
$failGeo = geo_map(array_column($topFailIps, 'ip'));

admin_header('Activity log', 'activity');
?>
<p class="muted">Everything that happens on the website and in this dashboard — changes, logins, blocked attempts and visitor actions — newest first.</p>

<form class="card filters" method="get">
  <label>Who<select name="who">
    <option value="">Everyone</option><option value="visitors" <?= $who === 'visitors' ? 'selected' : '' ?>>Visitors &amp; system</option>
    <?php foreach ($users as $u): ?><option value="<?= (int) $u['id'] ?>" <?= $who === (string) $u['id'] ? 'selected' : '' ?>><?= h($u['name']) ?></option><?php endforeach; ?>
  </select></label>
  <label>Show<select name="kind"><?php foreach ($kinds as $k => $l): ?><option value="<?= h($k) ?>" <?= $kind === $k ? 'selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?></select></label>
  <label>From<input type="date" name="from" value="<?= h($from) ?>"></label>
  <label>To<input type="date" name="to" value="<?= h($to) ?>"></label>
  <label class="grow">Search<input name="q" value="<?= h($search) ?>" placeholder="Name, email, IP…"></label>
  <div class="filter-btns"><button class="btn sm">Filter</button><?php if ($W): ?><a class="btn ghost sm" href="<?= url('admin/activity.php') ?>">Clear</a><?php endif; ?>
  <a class="btn ghost sm" href="<?= url('admin/activity.php?' . $qs(['export' => 'csv'])) ?>"><?= icon('file', 16) ?> CSV</a></div>
</form>

<div class="cols wide-first">
<section class="card table-wrap">
  <h2 class="card-title"><?= number_format($total) ?> event<?= $total === 1 ? '' : 's' ?><?= $W ? ' match' : '' ?></h2>
  <?php if (!$list): ?><p class="empty">Nothing here.</p><?php else: ?>
  <table class="table activity">
    <thead><tr><th>When</th><th>Who</th><th>What happened</th><th>From</th></tr></thead>
    <tbody>
    <?php foreach ($list as $a): [$verb, $what, $extra] = describe($a, $entities, $verbs); $bad = in_array($a['action'], $alarm, true); ?>
      <tr class="<?= $bad ? 'alarm' : '' ?>">
        <td data-l="When" class="nowrap muted small" title="<?= h($a['created_at']) ?>"><?= h(fdate($a['created_at'], 'j M, H:i')) ?></td>
        <td data-l="Who" class="nowrap"><?php if ($a['name']): ?><strong><?= h($a['name']) ?></strong><?php elseif (str_starts_with($a['action'], 'visitor_')): ?><span class="pill">Visitor</span><?php else: ?><span class="muted">—</span><?php endif; ?></td>
        <td data-l="What happened"><?php if ($bad): ?><span class="pill warn">Security</span> <?php endif; ?><?= h($verb) ?><?= $what !== '' ? ' <strong>' . h($what) . '</strong>' : '' ?>
          <?php if ($extra !== ''): ?><br><span class="muted small"><?= h($extra) ?></span><?php endif; ?></td>
        <td data-l="From" class="small"><?= h(place_of($a['ip'], $geo)) ?><?php if (isset($geo[$a['ip']]) && $geo[$a['ip']]['flag'] !== '' || is_private_ip($a['ip'])): ?><br><span class="muted"><?= h((string) $a['ip']) ?></span><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php if ($pages > 1): ?>
  <nav class="pager">
    <?php if ($page > 1): ?><a class="btn ghost sm" href="<?= url('admin/activity.php?' . $qs(['p' => $page - 1])) ?>">← Newer</a><?php endif; ?>
    <span class="muted small">Page <?= $page ?> of <?= $pages ?></span>
    <?php if ($page < $pages): ?><a class="btn ghost sm" href="<?= url('admin/activity.php?' . $qs(['p' => $page + 1])) ?>">Older →</a><?php endif; ?>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</section>
<section class="card table-wrap">
  <h2 class="card-title">Login security</h2>
  <p class="muted small"><?= $fails24 ?> failed login<?= $fails24 === 1 ? '' : 's' ?> in the last 24 hours. After <?= LOGIN_MAX_FAILS ?> wrong tries an email is locked for <?= LOGIN_LOCK_MINUTES ?> minutes.</p>
  <?php if (!$topFailIps): ?><p class="empty">No failed logins this week — all good.</p><?php else: ?>
  <table class="table">
    <thead><tr><th>Where from (7 days)</th><th class="right">Tries</th></tr></thead>
    <tbody>
    <?php foreach ($topFailIps as $f): ?><tr><td><strong><?= h(place_of($f['ip'], $failGeo)) ?></strong><br><span class="muted small"><?= h($f['ip']) ?> · last <?= h(fdate($f['last'], 'j M, H:i')) ?></span></td><td class="right"><b><?= (int) $f['n'] ?></b></td></tr><?php endforeach; ?>
    </tbody>
  </table><?php endif; ?>
</section>
</div>
<?php admin_footer();
