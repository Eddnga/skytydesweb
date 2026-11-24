<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
$me = require_role('superadmin');

$days = in_array((int) input('d', '30'), [1, 7, 30, 90], true) ? (int) input('d', '30') : 30;
$since = date('Y-m-d H:i:s', $days === 1 ? strtotime('today') : time() - $days * 86400);
$prevSince = date('Y-m-d H:i:s', strtotime($since) - ($days === 1 ? 86400 : $days * 86400));

if (is_post()) {
    csrf_check();
    if (input('do') === 'geo') {
        $ips = array_column(rows('SELECT DISTINCT v.ip FROM page_views v LEFT JOIN ip_geo g ON g.ip = v.ip WHERE g.ip IS NULL AND v.created_at >= ? ORDER BY v.ip LIMIT 200', [$since]), 'ip');
        geo_resolve($ips, 60);
        audit('geo_lookup', 'database', null, ['rows' => count($ips)]);
        flash('ok', 'Looked up visitor locations.');
    }
    redirect('admin/insights.php?d=' . $days);
}

// look up a handful of new visitor locations on every visit (fast, cached)
geo_resolve(array_column(rows('SELECT DISTINCT v.ip FROM page_views v LEFT JOIN ip_geo g ON g.ip = v.ip WHERE g.ip IS NULL AND v.created_at >= ? LIMIT 40', [$since]), 'ip'), 15);

$k = fn(string $s) => row("SELECT COUNT(*) views, COUNT(DISTINCT visitor) visitors, AVG(ms) ms FROM page_views WHERE created_at >= ? AND created_at < ? AND (title IS NULL OR title <> '404 Not found')", [$s, $s === $since ? date('Y-m-d H:i:s', time() + 60) : $since]);
$now = $k($since);
$prev = $k($prevSince);
$leads = (int) val('SELECT COUNT(*) FROM leads WHERE created_at >= ?', [$since]);
$prevLeads = (int) val('SELECT COUNT(*) FROM leads WHERE created_at >= ? AND created_at < ?', [$prevSince, $since]);
$live = (int) val('SELECT COUNT(DISTINCT visitor) FROM page_views WHERE created_at >= ?', [date('Y-m-d H:i:s', time() - 300)]);
$conv = $now['visitors'] ? min(100, round($leads / $now['visitors'] * 100, 1)) : 0;

$byDay = [];
foreach (rows("SELECT SUBSTR(created_at, 1, 10) d, COUNT(*) v, COUNT(DISTINCT visitor) u FROM page_views WHERE created_at >= ? GROUP BY SUBSTR(created_at, 1, 10)", [$since]) as $r) $byDay[$r['d']] = $r;
$series = [];
if ($days === 1) {
    foreach (rows("SELECT SUBSTR(created_at, 12, 2) h, COUNT(*) v, COUNT(DISTINCT visitor) u FROM page_views WHERE created_at >= ? GROUP BY SUBSTR(created_at, 12, 2)", [$since]) as $r) $byDay[$r['h']] = $r;
    for ($h = 0; $h < 24; $h++) { $key = sprintf('%02d', $h); $series[] = [$key . ':00', (int) ($byDay[$key]['v'] ?? 0), (int) ($byDay[$key]['u'] ?? 0)]; }
} else {
    for ($i = $days - 1; $i >= 0; $i--) { $d = date('Y-m-d', time() - $i * 86400); $series[] = [date('j M', strtotime($d)), (int) ($byDay[$d]['v'] ?? 0), (int) ($byDay[$d]['u'] ?? 0)]; }
}
$max = max(1, ...array_column($series, 1));

$pages = rows("SELECT path, MAX(title) title, COUNT(*) n, COUNT(DISTINCT visitor) u FROM page_views WHERE created_at >= ? AND (title IS NULL OR title <> '404 Not found') GROUP BY path ORDER BY n DESC LIMIT 12", [$since]);
$refs = rows("SELECT COALESCE(referrer, '') r, COUNT(DISTINCT visitor) n FROM page_views WHERE created_at >= ? GROUP BY COALESCE(referrer, '') ORDER BY n DESC LIMIT 8", [$since]);
$devices = rows('SELECT device k, COUNT(DISTINCT visitor) n FROM page_views WHERE created_at >= ? GROUP BY device ORDER BY n DESC', [$since]);
$browsers = rows('SELECT browser k, COUNT(DISTINCT visitor) n FROM page_views WHERE created_at >= ? GROUP BY browser ORDER BY n DESC LIMIT 6', [$since]);
$countries = rows("SELECT COALESCE(g.country, '') c, MAX(g.country_code) cc, COUNT(DISTINCT v.visitor) n FROM page_views v LEFT JOIN ip_geo g ON g.ip = v.ip WHERE v.created_at >= ? GROUP BY COALESCE(g.country, '') ORDER BY n DESC LIMIT 10", [$since]);
$cities = rows("SELECT g.city, g.country, g.country_code cc, COUNT(DISTINCT v.visitor) n FROM page_views v JOIN ip_geo g ON g.ip = v.ip WHERE v.created_at >= ? AND g.city IS NOT NULL GROUP BY g.city, g.country, g.country_code ORDER BY n DESC LIMIT 10", [$since]);
$unknownIps = (int) val('SELECT COUNT(DISTINCT v.ip) FROM page_views v LEFT JOIN ip_geo g ON g.ip = v.ip WHERE g.ip IS NULL AND v.created_at >= ?', [$since]);
$recent = rows('SELECT visitor, MAX(ip) ip, MAX(device) device, MAX(browser) browser, MAX(referrer) ref, COUNT(*) n, MIN(created_at) first, MAX(created_at) last FROM page_views WHERE created_at >= ? GROUP BY visitor ORDER BY last DESC LIMIT 25', [$since]);
$entry = [];
foreach ($recent as $r) $entry[$r['visitor']] = (string) val('SELECT path FROM page_views WHERE visitor = ? ORDER BY id LIMIT 1', [$r['visitor']]);
$geo = geo_map(array_column($recent, 'ip'));
$hours = array_fill(0, 24, 0);
foreach (rows('SELECT SUBSTR(created_at, 12, 2) h, COUNT(*) n FROM page_views WHERE created_at >= ? GROUP BY SUBSTR(created_at, 12, 2)', [$since]) as $r) $hours[(int) $r['h']] = (int) $r['n'];
$hmax = max(1, ...$hours);
$topLeadItems = rows('SELECT COALESCE(item, \'General enquiry\') item, COUNT(*) n FROM leads WHERE created_at >= ? GROUP BY COALESCE(item, \'General enquiry\') ORDER BY n DESC LIMIT 6', [$since]);

function delta($a, $b): string
{
    $a = (float) $a; $b = (float) $b;
    if ($b <= 0) return $a > 0 ? '<span class="up">new</span>' : '';
    $p = round(($a - $b) / $b * 100);
    return $p === 0.0 ? '<span class="muted">±0%</span>' : '<span class="' . ($p > 0 ? 'up' : 'down') . '">' . ($p > 0 ? '▲ ' : '▼ ') . abs($p) . '%</span>';
}
function share_bars(array $list, string $labelKey, callable $label): void
{
    $tot = max(1, array_sum(array_column($list, 'n')));
    echo '<ul class="sbars">';
    foreach ($list as $r) {
        $p = round($r['n'] / $tot * 100);
        echo '<li><span class="sb-l">' . $label($r) . '</span><span class="sb-n">' . (int) $r['n'] . ' <small>' . $p . '%</small></span><i style="width:' . $p . '%"></i></li>';
    }
    echo '</ul>';
}

admin_header('Visitor insights', 'insights');
?>
<div class="toolbar">
  <nav class="seg"><?php foreach ([1 => 'Today', 7 => '7 days', 30 => '30 days', 90 => '90 days'] as $d => $l): ?><a href="<?= url('admin/insights.php?d=' . $d) ?>" class="<?= $days === $d ? 'on' : '' ?>"><?= $l ?></a><?php endforeach; ?></nav>
  <span class="live"><i></i><?= $live ?> on the site now</span>
</div>

<div class="stats">
  <div class="stat card"><?= icon('eye', 22) ?><b><?= number_format((int) $now['views']) ?></b><span>Page views <?= delta($now['views'], $prev['views']) ?></span></div>
  <div class="stat card"><?= icon('users', 22) ?><b><?= number_format((int) $now['visitors']) ?></b><span>Visitors <?= delta($now['visitors'], $prev['visitors']) ?></span></div>
  <div class="stat card"><?= icon('file', 22) ?><b><?= $now['visitors'] ? number_format($now['views'] / $now['visitors'], 1) : '0' ?></b><span>Pages per visitor</span></div>
  <div class="stat card hl"><?= icon('chat', 22) ?><b><?= $leads ?></b><span>WhatsApp leads <?= delta($leads, $prevLeads) ?></span></div>
  <div class="stat card"><?= icon('check', 22) ?><b><?= $conv ?>%</b><span>Visitors who enquired</span></div>
  <div class="stat card"><?= icon('pulse', 22) ?><b><?= $now['ms'] !== null ? (int) $now['ms'] . ' ms' : '—' ?></b><span>Avg. page build time</span></div>
</div>

<section class="card">
  <div class="card-head"><h2><?= $days === 1 ? 'Today, hour by hour' : 'Visits per day' ?></h2><span class="legend"><i class="lv"></i> Page views <i class="lu"></i> Visitors</span></div>
  <div class="vchart" style="--cols:<?= count($series) ?>">
    <?php foreach ($series as $s): ?>
      <div class="vcol" title="<?= h($s[0]) ?>: <?= $s[1] ?> views, <?= $s[2] ?> visitors"><span class="bv" style="height:<?= round($s[1] / $max * 100) ?>%"></span><span class="bu" style="height:<?= round($s[2] / $max * 100) ?>%"></span></div>
    <?php endforeach; ?>
  </div>
  <div class="vaxis"><span><?= h($series[0][0]) ?></span><span><?= h($series[(int) floor(count($series) / 2)][0]) ?></span><span><?= h(end($series)[0]) ?></span></div>
  <?php if (!(int) $now['views']): ?><p class="muted small center">No visits recorded in this period yet. Visits are counted from the moment this update went live; your own visits while logged in are not counted.</p><?php endif; ?>
</section>

<div class="cols">
  <section class="card table-wrap">
    <h2 class="card-title">Most viewed pages</h2>
    <?php if (!$pages): ?><p class="empty">No data yet.</p><?php else: ?>
    <table class="table"><thead><tr><th>Page</th><th class="right">Views</th><th class="right">Visitors</th></tr></thead><tbody>
    <?php foreach ($pages as $p): ?><tr><td><a href="<?= h(BASE_PATH . $p['path']) ?>" target="_blank" rel="noopener"><?= h($p['title'] ?: $p['path']) ?></a><br><span class="muted small"><?= h($p['path']) ?></span></td><td class="right"><?= (int) $p['n'] ?></td><td class="right"><?= (int) $p['u'] ?></td></tr><?php endforeach; ?>
    </tbody></table><?php endif; ?>
  </section>
  <section class="card">
    <h2 class="card-title">Where visitors come from</h2>
    <?php if (!$refs): ?><p class="empty">No data yet.</p><?php else: share_bars($refs, 'r', fn($r) => $r['r'] === '' ? '<b>Direct / typed / WhatsApp app</b>' : h($r['r'])); endif; ?>
    <h2 class="card-title" style="margin-top:22px">What people asked about</h2>
    <?php if (!$topLeadItems): ?><p class="empty">No WhatsApp leads in this period.</p><?php else: share_bars($topLeadItems, 'item', fn($r) => h(excerpt($r['item'], 50))); endif; ?>
  </section>
</div>

<div class="cols">
  <section class="card">
    <div class="card-head"><h2>Countries</h2>
      <?php if ($unknownIps): ?><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="do" value="geo"><button class="btn ghost sm"><?= icon('globe', 16) ?> Locate <?= $unknownIps ?> more</button></form><?php endif; ?></div>
    <?php if (!$countries): ?><p class="empty">No data yet.</p><?php else: share_bars($countries, 'c', fn($r) => $r['c'] === '' ? '<span class="muted">Unknown / not looked up yet</span>' : h(trim(flag($r['cc']) . ' ' . $r['c']))); endif; ?>
  </section>
  <section class="card">
    <h2 class="card-title">Cities</h2>
    <?php if (!$cities): ?><p class="empty">Cities appear once visitor locations are looked up.</p><?php else: share_bars($cities, 'city', fn($r) => h(trim(flag($r['cc']) . ' ' . $r['city'] . ', ' . $r['country']))); endif; ?>
  </section>
</div>

<div class="cols">
  <section class="card">
    <h2 class="card-title">Devices</h2>
    <?php if (!$devices): ?><p class="empty">No data yet.</p><?php else: share_bars($devices, 'k', fn($r) => h($r['k'] ?: 'Unknown')); endif; ?>
    <h2 class="card-title" style="margin-top:22px">Browsers</h2>
    <?php if (!$browsers): ?><p class="empty">No data yet.</p><?php else: share_bars($browsers, 'k', fn($r) => h($r['k'] ?: 'Unknown')); endif; ?>
  </section>
  <section class="card">
    <h2 class="card-title">Busiest hours <span class="muted small">(Nairobi time)</span></h2>
    <div class="hours"><?php foreach ($hours as $h => $n): ?><div title="<?= sprintf('%02d:00', $h) ?> — <?= $n ?> views"><i style="height:<?= max(3, round($n / $hmax * 100)) ?>%"></i><small><?= $h % 3 === 0 ? sprintf('%02d', $h) : '' ?></small></div><?php endforeach; ?></div>
  </section>
</div>

<section class="card table-wrap">
  <h2 class="card-title">Latest visitors</h2>
  <?php if (!$recent): ?><p class="empty">No visitors yet.</p><?php else: ?>
  <table class="table">
    <thead><tr><th>Last seen</th><th>Location</th><th>Device</th><th>Came from</th><th>First page</th><th class="right">Pages</th></tr></thead>
    <tbody>
    <?php foreach ($recent as $r): ?>
      <tr>
        <td data-l="Last seen" class="nowrap small"><?= h(time_ago($r['last'])) ?></td>
        <td data-l="Location"><strong><?= h(place_of($r['ip'], $geo)) ?></strong><br><span class="muted small"><?= h($r['ip']) ?><?= !empty($geo[$r['ip']]['org']) ? ' · ' . h($geo[$r['ip']]['org']) : '' ?></span></td>
        <td data-l="Device" class="small"><?= h($r['device'] . ' · ' . $r['browser']) ?></td>
        <td data-l="Came from" class="small"><?= h($r['ref'] ?: 'Direct') ?></td>
        <td data-l="First page" class="small"><?= h($entry[$r['visitor']] ?? '') ?></td>
        <td data-l="Pages" class="right"><?= (int) $r['n'] ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table><?php endif; ?>
  <p class="hint">Visits are counted privately on your own server — no cookies and no outside trackers. Your team's visits while logged in are not counted. Locations are approximate (city level) and come from free IP lookup services. Data older than <?= VIEWS_KEEP_DAYS ?> days is removed automatically.</p>
</section>
<?php admin_footer();
