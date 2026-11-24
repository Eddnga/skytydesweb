<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
$me = require_role('superadmin');
global $CONFIG;

if (is_post()) {
    csrf_check();
    $do = input('do');
    if ($do === 'pretty') {
        $ok = check_pretty_urls();
        audit('pretty_check', 'settings', null, ['note' => $ok ? 'clean addresses on' : 'clean addresses not working']);
        flash($ok ? 'ok' : 'error', $ok ? 'Clean web addresses work — pages now show as /tours instead of /tours.php.' : 'The server did not answer the clean-address test. Press "Restart container" in MyPanel, then test again.');
    }
    if ($do === 'pretty_off') {
        set_setting('pretty_urls', '0');
        audit('update', 'settings', null, ['changed' => ['clean web addresses turned off']]);
        flash('ok', 'Clean web addresses turned off.');
    }
    if ($do === 'clear_log' && is_file(STORAGE . '/logs/php-error.log')) {
        file_put_contents(STORAGE . '/logs/php-error.log', '');
        audit('update', 'settings', null, ['changed' => ['error log cleared']]);
        flash('ok', 'Error log cleared.');
    }
    redirect('admin/health.php');
}

/** Measures how long the home page takes to answer (asks the live server). */
function speed_test(string $url): ?array
{
    if (!function_exists('curl_init') || $url === '') return null;
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_USERAGENT => 'SkyTydes-selfcheck', CURLOPT_ENCODING => '', CURLOPT_HEADER => true]);
    $res = curl_exec($ch);
    if ($res === false) { curl_close($ch); return null; }
    $info = curl_getinfo($ch);
    curl_close($ch);
    $headers = strtolower(substr((string) $res, 0, (int) $info['header_size']));
    return ['code' => (int) $info['http_code'], 'ttfb' => (int) round(($info['starttransfer_time'] ?? 0) * 1000), 'total' => (int) round($info['total_time'] * 1000),
        'kb' => round(($info['size_download'] ?? 0) / 1024), 'gzip' => str_contains($headers, 'content-encoding: gzip') || str_contains($headers, 'content-encoding: br'),
        'hsts' => str_contains($headers, 'strict-transport-security'), 'csp' => str_contains($headers, 'content-security-policy')];
}
function dir_size(string $dir): array
{
    $size = 0; $n = 0; $big = [];
    if (!is_dir($dir)) return [0, 0, []];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!$f->isFile()) continue;
        $size += $f->getSize(); $n++;
        if ($f->getSize() > 900 * 1024 && preg_match('/\.(jpe?g|png|webp)$/i', $f->getFilename())) $big[] = [str_replace(ROOT . '/', '', $f->getPathname()), $f->getSize()];
    }
    usort($big, fn($a, $b) => $b[1] <=> $a[1]);
    return [$size, $n, array_slice($big, 0, 8)];
}
function ini_bytes(string $v): int
{
    $v = trim($v); $n = (int) $v;
    return match (strtolower(substr($v, -1))) { 'g' => $n * 1073741824, 'm' => $n * 1048576, 'k' => $n * 1024, default => $n };
}

$checks = []; // [group, status ok|warn|bad, title, detail, fix]
$add = function (string $g, string $st, string $t, string $d = '', string $fix = '') use (&$checks) { $checks[] = [$g, $st, $t, $d, $fix]; };

// ---- security ----
$w = php_patch_warning();
$add('Security', $w ? 'bad' : 'ok', 'PHP ' . PHP_VERSION, $w ?: 'Has the latest security patches.', $w ? 'Ask Chajio to update PHP.' : '');
$add('Security', is_https() ? 'ok' : 'bad', 'HTTPS', is_https() ? 'This page loaded over a secure connection.' : 'The dashboard is not on HTTPS.', is_https() ? '' : 'Issue SSL in MyPanel → Domains, then tick "Always use HTTPS".');
$add('Security', !empty($CONFIG['force_https']) ? 'ok' : 'warn', 'Always use HTTPS', !empty($CONFIG['force_https']) ? 'Visitors on http:// are moved to https://.' : 'Visitors can still open the site on http://.', !empty($CONFIG['force_https']) ? '' : "Set 'force_https' => true in config.php once SSL works.");
$add('Security', is_dir(ROOT . '/install') ? 'warn' : 'ok', 'Installer folder', is_dir(ROOT . '/install') ? 'The /install folder is still on the server (it is locked, but it shouldn\'t be there).' : 'Removed.', is_dir(ROOT . '/install') ? 'Delete public_html/install in the file manager.' : '');
$add('Security', ini_get('display_errors') && ini_get('display_errors') !== 'Off' && ini_get('display_errors') !== '0' ? 'warn' : 'ok', 'Error messages hidden from visitors', ($CONFIG['env'] ?? 'production') === 'production' ? 'Production mode.' : 'Development mode is on.', '');
$cfgPerm = is_file(ROOT . '/config.php') ? substr(sprintf('%o', fileperms(ROOT . '/config.php')), -3) : '';
$add('Security', $cfgPerm && (int) $cfgPerm[2] > 0 ? 'warn' : 'ok', 'config.php permissions', $cfgPerm ? "Currently $cfgPerm." : '', $cfgPerm && (int) $cfgPerm[2] > 0 ? 'Set config.php to 640 or 600 in the file manager.' : '');
$admins = (int) val("SELECT COUNT(*) FROM users WHERE role IN ('admin','superadmin') AND active = 1");
$add('Security', 'ok', 'Dashboard users', $admins . ' admin account(s), ' . (int) val("SELECT COUNT(*) FROM users WHERE role = 'editor' AND active = 1") . ' editor(s). Sessions end after ' . SESSION_IDLE_MINUTES . ' minutes idle; logins lock after ' . LOGIN_MAX_FAILS . ' wrong tries.');
$fails = (int) val('SELECT COUNT(*) FROM login_attempts WHERE success = 0 AND attempted_at >= ?', [date('Y-m-d H:i:s', time() - 86400)]);
$add('Security', $fails > 20 ? 'warn' : 'ok', 'Failed logins (24 h)', (string) $fails, $fails > 20 ? 'Someone may be guessing passwords — check the Activity log.' : '');

// ---- speed & setup ----
$speed = speed_test(rtrim($CONFIG['base_url'] ?? '', '/') . '/');
if ($speed) {
    $add('Speed', $speed['ttfb'] < 600 ? 'ok' : ($speed['ttfb'] < 1500 ? 'warn' : 'bad'), 'Home page response', "First byte in {$speed['ttfb']} ms, fully loaded HTML in {$speed['total']} ms ({$speed['kb']} KB).", $speed['ttfb'] >= 600 ? 'Turn on OPcache / LiteSpeed cache in MyPanel, and keep hero photos under 500 KB.' : '');
    $add('Speed', $speed['gzip'] ? 'ok' : 'warn', 'Compression', $speed['gzip'] ? 'Pages are sent compressed.' : 'Pages are sent uncompressed.', $speed['gzip'] ? '' : 'Enable gzip/brotli compression for the website in MyPanel.');
    $add('Security', $speed['hsts'] && $speed['csp'] ? 'ok' : 'warn', 'Security headers', ($speed['csp'] ? 'Content-Security-Policy ✓ ' : 'No CSP ') . ($speed['hsts'] ? '· HSTS ✓' : '· no HSTS'), '');
} else {
    $add('Speed', 'warn', 'Home page response', 'The server could not test itself (no outgoing connection to its own domain).', '');
}
$opc = function_exists('opcache_get_status') && @opcache_get_status(false);
$add('Speed', $opc ? 'ok' : 'warn', 'OPcache', $opc ? 'On — PHP code is kept in memory.' : 'Off or unavailable.', $opc ? '' : 'Turn on OPcache in MyPanel\'s PHP settings for faster pages.');
$add('Speed', pretty_on() ? 'ok' : 'warn', 'Clean web addresses', pretty_on() ? 'On — e.g. /tours/big-five instead of tour.php?slug=big-five.' : 'Off — pages show as /tours.php.', pretty_on() ? '' : 'Press "Test clean addresses" below.');
$avgMs = val('SELECT AVG(ms) FROM page_views WHERE created_at >= ?', [date('Y-m-d H:i:s', time() - 7 * 86400)]);
if ($avgMs !== null) $add('Speed', $avgMs < 300 ? 'ok' : 'warn', 'Page build time (real visitors, 7 days)', round((float) $avgMs) . ' ms on average.', $avgMs >= 300 ? 'Usually means a slow database or disk; ask Chajio or turn on OPcache.' : '');

$maxUp = min(ini_bytes((string) ini_get('upload_max_filesize')), ini_bytes((string) ini_get('post_max_size')));
$add('Server', $maxUp >= 24 * 1048576 ? 'ok' : 'warn', 'Upload limit', 'Largest upload: ' . human_size($maxUp) . ' (upload_max_filesize ' . ini_get('upload_max_filesize') . ', post_max_size ' . ini_get('post_max_size') . ').', $maxUp >= 24 * 1048576 ? '' : 'Set upload_max_filesize = 32M and post_max_size = 40M in MyPanel\'s PHP settings so brochures upload.');
$add('Server', 'ok', 'Memory & time limits', 'memory_limit ' . ini_get('memory_limit') . ', max_execution_time ' . ini_get('max_execution_time') . 's.');
foreach (['pdo_mysql' => 'Database', 'gd' => 'Image resizing', 'fileinfo' => 'Upload checks', 'mbstring' => 'Text', 'dom' => 'Rich-text cleaning', 'curl' => 'Locations & self-tests', 'zip' => 'Backups (optional)'] as $ext => $why) {
    $on = extension_loaded($ext);
    $add('Server', $on ? 'ok' : ($ext === 'zip' ? 'warn' : 'bad'), "PHP extension: $ext", $on ? "On ($why)." : "Missing — needed for $why.", $on ? '' : "Enable $ext in MyPanel's PHP extensions.");
}
$webp = function_exists('imagewebp');
$add('Server', $webp ? 'ok' : 'warn', 'WebP images', $webp ? 'Supported.' : 'GD can\'t write WebP.', '');
$free = @disk_free_space(ROOT);
$add('Server', $free === false || $free > 1073741824 ? 'ok' : 'warn', 'Free disk space', $free === false ? 'Unknown.' : human_size((int) $free) . ' free.', '');
[$upSize, $upCount, $bigImgs] = dir_size(ROOT . '/uploads');
$add('Server', 'ok', 'Uploads folder', $upCount . ' files, ' . human_size($upSize) . '.');
$add('Server', is_writable(STORAGE) && is_writable(ROOT . '/uploads') ? 'ok' : 'bad', 'Writable folders', is_writable(STORAGE) && is_writable(ROOT . '/uploads') ? 'storage/ and uploads/ are writable.' : 'storage/ or uploads/ can\'t be written to.', 'Set both folders to 755 in the file manager.');

// ---- content & SEO ----
$seo = [
    ['Tours without a photo', 'SELECT COUNT(*) FROM tours WHERE active = 1 AND (image IS NULL OR image = \'\')', 'content.php?type=tours'],
    ['Tours without a summary (used by Google)', 'SELECT COUNT(*) FROM tours WHERE active = 1 AND (summary IS NULL OR summary = \'\')', 'content.php?type=tours'],
    ['Tours without a price', 'SELECT COUNT(*) FROM tours WHERE active = 1 AND price_from IS NULL', 'content.php?type=tours'],
    ['Destinations without a photo', 'SELECT COUNT(*) FROM destinations WHERE active = 1 AND (image IS NULL OR image = \'\')', 'content.php?type=destinations'],
    ['Blog posts without an excerpt', 'SELECT COUNT(*) FROM posts WHERE published = 1 AND (excerpt IS NULL OR excerpt = \'\')', 'content.php?type=posts'],
    ['Gallery photos without a caption', 'SELECT COUNT(*) FROM gallery WHERE active = 1 AND (caption IS NULL OR caption = \'\')', 'content.php?type=gallery'],
    ['Example offers still in the list', "SELECT COUNT(*) FROM offers WHERE title LIKE '[Example]%'", 'content.php?type=offers'],
    ['Reviews waiting for approval', "SELECT COUNT(*) FROM reviews WHERE status = 'pending'", 'reviews.php'],
];
foreach ($seo as $s) {
    try { $n = (int) val($s[1]); } catch (Throwable $e) { continue; }
    $add('Content & SEO', $n ? 'warn' : 'ok', $s[0], $n ? "$n to fix." : 'None.', $n ? 'admin/' . $s[2] : '');
}
$md = setting('meta_description');
$add('Content & SEO', mb_strlen($md) >= 70 && mb_strlen($md) <= 170 ? 'ok' : 'warn', 'Home page description (Google snippet)', $md === '' ? 'Missing.' : mb_strlen($md) . ' characters.', mb_strlen($md) >= 70 && mb_strlen($md) <= 170 ? '' : 'admin/settings.php');
$add('Content & SEO', setting('whatsapp') !== '' ? 'ok' : 'bad', 'WhatsApp number', setting('whatsapp') !== '' ? '+' . setting('whatsapp') : 'Missing — booking buttons won\'t work.', setting('whatsapp') !== '' ? '' : 'admin/settings.php');
$socials = count(array_filter(['social_facebook', 'social_instagram', 'social_tiktok', 'social_youtube'], fn($k) => setting($k) !== ''));
$add('Content & SEO', $socials ? 'ok' : 'warn', 'Social links', "$socials of 4 added.", $socials < 4 ? 'admin/settings.php' : '');
$add('Content & SEO', 'ok', 'Sitemap for Google', abs_url('sitemap.xml'), '');
$notFound = rows("SELECT path, COUNT(*) n, MAX(created_at) last FROM page_views WHERE title = '404 Not found' AND created_at >= ? GROUP BY path ORDER BY n DESC LIMIT 8", [date('Y-m-d H:i:s', time() - 30 * 86400)]);

$log = is_file(STORAGE . '/logs/php-error.log') ? (string) file_get_contents(STORAGE . '/logs/php-error.log', false, null, max(0, filesize(STORAGE . '/logs/php-error.log') - 6000)) : '';
$logLines = array_slice(array_filter(explode("\n", $log)), -25);

$score = ['ok' => 0, 'warn' => 0, 'bad' => 0];
foreach ($checks as $c) $score[$c[1]]++;
$groups = [];
foreach ($checks as $c) $groups[$c[0]][] = $c;

admin_header('Site health', 'health');
?>
<div class="health-sum card">
  <div><b class="ok-n"><?= $score['ok'] ?></b><span>All good</span></div>
  <div><b class="warn-n"><?= $score['warn'] ?></b><span>Worth improving</span></div>
  <div><b class="bad-n"><?= $score['bad'] ?></b><span>Needs fixing</span></div>
  <div class="health-actions">
    <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="do" value="pretty"><button class="btn ghost sm"><?= icon('globe', 16) ?> Test clean addresses</button></form>
    <?php if (pretty_on()): ?><form method="post" class="inline" data-confirm="Turn clean addresses off? Pages will show as /tours.php again."><?= csrf_field() ?><input type="hidden" name="do" value="pretty_off"><button class="btn ghost sm">Turn off</button></form><?php endif; ?>
    <a class="btn ghost sm" href="<?= url('admin/health.php') ?>">↻ Re-check</a>
  </div>
</div>

<div class="cols">
<?php foreach ($groups as $g => $list): ?>
  <section class="card">
    <h2 class="card-title"><?= h($g) ?></h2>
    <ul class="checks">
    <?php foreach ($list as $c): ?>
      <li class="chk <?= $c[1] ?>"><i></i><div><b><?= h($c[2]) ?></b><?php if ($c[3] !== ''): ?><span><?= h($c[3]) ?></span><?php endif; ?>
        <?php if ($c[4] !== ''): ?><em><?php if (str_starts_with($c[4], 'admin/')): ?><a href="<?= url($c[4]) ?>">Fix this →</a><?php else: ?><?= h($c[4]) ?><?php endif; ?></em><?php endif; ?></div></li>
    <?php endforeach; ?>
    </ul>
  </section>
<?php endforeach; ?>
</div>

<div class="cols">
  <section class="card table-wrap">
    <h2 class="card-title">Broken links visitors hit (30 days)</h2>
    <?php if (!$notFound): ?><p class="empty">None — every page visitors opened worked.</p><?php else: ?>
    <table class="table"><tbody><?php foreach ($notFound as $n): ?><tr><td><code><?= h($n['path']) ?></code><br><span class="muted small">last <?= h(time_ago($n['last'])) ?></span></td><td class="right"><?= (int) $n['n'] ?>×</td></tr><?php endforeach; ?></tbody></table>
    <p class="hint">If one of these is an old link shared on social media, recreate the page or ask me to add a redirect.</p><?php endif; ?>
  </section>
  <section class="card">
    <h2 class="card-title">Large images (over 900 KB)</h2>
    <?php if (!$bigImgs): ?><p class="empty">None — photos are a good size.</p><?php else: ?>
    <ul class="plain small"><?php foreach ($bigImgs as $b): ?><li><code><?= h($b[0]) ?></code> — <?= h(human_size($b[1])) ?></li><?php endforeach; ?></ul>
    <p class="hint">New uploads are resized automatically; these are older or seed files. Re-upload them to shrink them.</p><?php endif; ?>
  </section>
</div>

<section class="card">
  <div class="card-head"><h2>Error log</h2><?php if ($logLines): ?><form method="post" class="inline" data-confirm="Clear the error log?"><?= csrf_field() ?><input type="hidden" name="do" value="clear_log"><button class="btn ghost sm">Clear</button></form><?php endif; ?></div>
  <?php if (!$logLines): ?><p class="empty">No errors recorded.</p><?php else: ?><pre class="log"><?= h(implode("\n", $logLines)) ?></pre><?php endif; ?>
</section>
<?php admin_footer();
