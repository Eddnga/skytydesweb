<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');
$day = fn(?string $d) => $d ? date('Y-m-d', strtotime($d)) : null;
$latest = fn(string $sql) => (function () use ($sql) { try { return val($sql); } catch (Throwable $e) { return null; } })();
// [address, last changed, priority]
$urls = [
    ['', $latest('SELECT MAX(COALESCE(updated_at, created_at)) FROM tours'), '1.0'],
    ['tours.php', $latest('SELECT MAX(COALESCE(updated_at, created_at)) FROM tours'), '0.9'],
    ['destinations.php', null, '0.8'],
    ['offers.php', $latest('SELECT MAX(created_at) FROM offers'), '0.7'],
    ['services.php', null, '0.6'], ['about.php', null, '0.6'], ['contact.php', null, '0.6'],
    ['blog.php', $latest('SELECT MAX(published_at) FROM posts WHERE published = 1'), '0.6'],
    ['reviews.php', $latest("SELECT MAX(approved_at) FROM reviews WHERE status = 'approved'"), '0.5'],
    ['gallery.php', $latest('SELECT MAX(created_at) FROM gallery'), '0.5'],
    ['faqs.php', null, '0.5'], ['documents.php', $latest('SELECT MAX(created_at) FROM documents'), '0.4'],
];
foreach (rows('SELECT slug, COALESCE(updated_at, created_at) d FROM tours WHERE active = 1') as $r) $urls[] = ['tour.php?slug=' . rawurlencode($r['slug']), $r['d'], '0.9'];
foreach (rows('SELECT slug FROM destinations WHERE active = 1') as $r) $urls[] = ['destination.php?slug=' . rawurlencode($r['slug']), null, '0.8'];
foreach (rows('SELECT slug, published_at d FROM posts WHERE published = 1 AND published_at <= ?', [now()]) as $r) $urls[] = ['post.php?slug=' . rawurlencode($r['slug']), $r['d'], '0.7'];
foreach (custom_types() as $ct) {
    if (!$ct['public']) continue;
    $urls[] = ['section.php?type=' . $ct['slug'], null, '0.5'];
    foreach (rows('SELECT slug, COALESCE(updated_at, created_at) d FROM cx_' . $ct['slug'] . ' WHERE active = 1') as $r) $urls[] = ['section.php?type=' . $ct['slug'] . '&slug=' . rawurlencode($r['slug']), $r['d'], '0.5'];
}
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as [$u, $d, $p]) {
    echo '  <url><loc>' . h(abs_url($u)) . '</loc>' . ($day($d) ? '<lastmod>' . $day($d) . '</lastmod>' : '') . '<priority>' . $p . "</priority></url>\n";
}
echo '</urlset>';
