<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
$me = require_role('superadmin');
global $CONFIG;
$sqlite = ($CONFIG['db']['driver'] ?? 'mysql') === 'sqlite';

function table_list(bool $sqlite): array
{
    if ($sqlite) return array_column(rows("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"), 'name');
    return array_map(fn($r) => array_values($r)[0], rows('SHOW TABLES'));
}
function qi(string $name, bool $sqlite): string
{
    return $sqlite ? '"' . str_replace('"', '""', $name) . '"' : '`' . str_replace('`', '``', $name) . '`';
}

/** Streams a full SQL backup (structure + data) that phpMyAdmin can import. */
function dump_database(bool $sqlite): void
{
    $pdo = db();
    $name = 'skytydes-backup-' . date('Y-m-d-Hi') . '.sql';
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Cache-Control: no-store');
    echo "-- SkyTydes Safaris database backup\n-- Made " . date('Y-m-d H:i') . " (Africa/Nairobi)\n-- Restore with phpMyAdmin → Import\n\n";
    if (!$sqlite) echo "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";
    foreach (table_list($sqlite) as $t) {
        $q = qi($t, $sqlite);
        echo "-- ----- $t -----\nDROP TABLE IF EXISTS $q;\n";
        if ($sqlite) echo val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$t]) . ";\n";
        else echo array_values(row("SHOW CREATE TABLE $q"))[1] . ";\n";
        $st = $pdo->query("SELECT * FROM $q");
        $batch = [];
        while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
            $batch[] = '(' . implode(',', array_map(fn($v) => $v === null ? 'NULL' : (is_int($v) || is_float($v) ? (string) $v : $pdo->quote((string) $v)), $r)) . ')';
            if (count($batch) === 200) {
                echo "INSERT INTO $q (" . implode(',', array_map(fn($c) => qi($c, $sqlite), array_keys($r))) . ") VALUES\n" . implode(",\n", $batch) . ";\n";
                $batch = [];
                flush();
            }
            $cols = array_keys($r);
        }
        if ($batch) echo "INSERT INTO $q (" . implode(',', array_map(fn($c) => qi($c, $sqlite), $cols)) . ") VALUES\n" . implode(",\n", $batch) . ";\n";
        echo "\n";
    }
    if (!$sqlite) echo "SET FOREIGN_KEY_CHECKS = 1;\n";
}

if (is_post()) {
    csrf_check();
    $do = input('do');
    if ($do === 'backup') {
        audit('db_backup', 'database', null, ['what' => 'full SQL backup']);
        @set_time_limit(300);
        dump_database($sqlite);
        exit;
    }
    if ($do === 'migrate') {
        set_setting('db_version', '1');
        $GLOBALS['__settings'] = null;
        unset($GLOBALS['__settings']);
        maybe_migrate();
        audit('db_migrate', 'database', null, ['note' => 'now version ' . setting('db_version')]);
        flash('ok', 'Database checked and brought up to date (version ' . h(setting('db_version')) . ').');
    }
    if ($do === 'optimize' && !$sqlite) {
        foreach (table_list(false) as $t) q('OPTIMIZE TABLE ' . qi($t, false));
        audit('db_optimize', 'database');
        flash('ok', 'Tables optimised.');
    } elseif ($do === 'optimize') {
        db()->exec('VACUUM');
        audit('db_optimize', 'database');
        flash('ok', 'Database compacted.');
    }
    if ($do === 'purge') {
        $n = 0;
        $n += q('DELETE FROM throttle WHERE created_at < ?', [date('Y-m-d H:i:s', time() - 2 * 86400)])->rowCount();
        $n += q('DELETE FROM login_attempts WHERE attempted_at < ?', [date('Y-m-d H:i:s', time() - 90 * 86400)])->rowCount();
        $n += q('DELETE FROM page_views WHERE created_at < ?', [date('Y-m-d H:i:s', time() - VIEWS_KEEP_DAYS * 86400)])->rowCount();
        $n += q('DELETE FROM audit_log WHERE created_at < ? AND SUBSTR(action, 1, 8) = ?', [date('Y-m-d H:i:s', time() - 365 * 86400), 'visitor_'])->rowCount();
        audit('db_purge', 'database', null, ['rows' => $n]);
        flash('ok', "$n old rows removed (rate-limit records, old failed logins, visits over " . VIEWS_KEEP_DAYS . ' days, visitor events over a year).');
    }
    redirect('admin/database.php');
}

$tables = [];
if ($sqlite) {
    foreach (table_list(true) as $t) $tables[] = ['name' => $t, 'rows' => (int) val('SELECT COUNT(*) FROM ' . qi($t, true)), 'size' => null];
    $total = is_file($CONFIG['db']['sqlite'] ?? '') ? filesize($CONFIG['db']['sqlite']) : 0;
} else {
    $total = 0;
    foreach (rows('SELECT table_name n, table_rows r, (data_length + index_length) s FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY table_name') as $r) {
        $tables[] = ['name' => $r['n'], 'rows' => (int) val('SELECT COUNT(*) FROM ' . qi($r['n'], false)), 'size' => (int) $r['s']];
        $total += (int) $r['s'];
    }
}
$labels = [
    'users' => 'Dashboard users', 'login_attempts' => 'Login attempts', 'throttle' => 'Spam / rate limits', 'settings' => 'Site settings', 'hero_slides' => 'Hero slides',
    'destinations' => 'Destinations', 'tours' => 'Tour packages', 'itinerary_days' => 'Itinerary days', 'posts' => 'Blog posts', 'services' => 'Services', 'faqs' => 'FAQs',
    'gallery' => 'Gallery photos', 'reviews' => 'Reviews', 'leads' => 'WhatsApp leads', 'audit_log' => 'Activity log', 'offers' => 'Special offers', 'team' => 'Your hosts',
    'subscribers' => 'Newsletter subscribers', 'documents' => 'Documents', 'page_views' => 'Visitor page views', 'ip_geo' => 'Visitor locations (cache)', 'custom_types' => 'Content sections',
];
foreach (custom_types() as $ct) $labels['cx_' . $ct['slug']] = $ct['label'] . ' (content section)';
$server = $sqlite ? 'SQLite ' . val('SELECT sqlite_version()') : (string) val('SELECT VERSION()');
$dbName = $sqlite ? basename((string) ($CONFIG['db']['sqlite'] ?? '')) : (string) val('SELECT DATABASE()');

admin_header('Database', 'database');
?>
<div class="stats">
  <div class="stat card"><?= icon('db', 22) ?><b><?= h($dbName) ?></b><span><?= h($server) ?></span></div>
  <div class="stat card"><?= icon('grid', 22) ?><b><?= count($tables) ?></b><span>Tables</span></div>
  <div class="stat card"><?= icon('file', 22) ?><b><?= h(human_size((int) $total)) ?></b><span>Total size</span></div>
  <div class="stat card"><?= icon('check', 22) ?><b>v<?= h(setting('db_version')) ?></b><span>Database version (latest: v<?= DB_VERSION ?>)</span></div>
</div>

<div class="cols wide-first">
<section class="card table-wrap">
  <h2 class="card-title">Tables</h2>
  <table class="table">
    <thead><tr><th>What it holds</th><th>Table</th><th class="right">Rows</th><?php if (!$sqlite): ?><th class="right">Size</th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($tables as $t): ?>
      <tr><td><?= h($labels[$t['name']] ?? '—') ?></td><td><code><?= h($t['name']) ?></code></td><td class="right"><?= number_format($t['rows']) ?></td><?php if (!$sqlite): ?><td class="right muted small"><?= h(human_size((int) $t['size'])) ?></td><?php endif; ?></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<div class="stack">
  <section class="card">
    <h2 class="card-title">Backup</h2>
    <p class="muted small">Downloads everything — content, settings, users, leads and reviews — as one .sql file. Keep it somewhere safe (not on the server). Photos and PDFs are files: back those up from MyPanel → Backups.</p>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="backup"><button class="btn"><?= icon('file', 18) ?> Download database backup</button></form>
    <p class="hint">To restore: phpMyAdmin → select <?= h($dbName) ?> → Import → choose the file.</p>
  </section>
  <section class="card">
    <h2 class="card-title">Maintenance</h2>
    <form method="post" class="tool"><?= csrf_field() ?><input type="hidden" name="do" value="migrate"><div><b>Run database updates</b><span>Adds any new tables or columns a website update needs. Safe to run any time.</span></div><button class="btn ghost sm">Run</button></form>
    <form method="post" class="tool"><?= csrf_field() ?><input type="hidden" name="do" value="optimize"><div><b>Optimise tables</b><span>Tidies the database after lots of deleting.</span></div><button class="btn ghost sm">Optimise</button></form>
    <form method="post" class="tool" data-confirm="Remove old log data? Content, leads, reviews and users are not touched."><?= csrf_field() ?><input type="hidden" name="do" value="purge"><div><b>Clear old data</b><span>Old rate-limit records, failed logins over 90 days, visits over <?= VIEWS_KEEP_DAYS ?> days.</span></div><button class="btn ghost sm">Clear</button></form>
  </section>
  <section class="card">
    <h2 class="card-title">Add your own content type</h2>
    <p class="muted small">Need a new kind of item — lodges, vehicles, partners? Create it in <a href="<?= url('admin/sections.php') ?>">Content sections</a>; the table is created for you.</p>
  </section>
</div>
</div>
<?php admin_footer();
