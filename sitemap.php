<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');
$urls = ['', 'tours.php', 'destinations.php', 'services.php', 'gallery.php', 'blog.php', 'reviews.php', 'faqs.php', 'about.php', 'contact.php', 'offers.php'];
foreach (rows('SELECT slug FROM tours WHERE active = 1') as $r) $urls[] = 'tour.php?slug=' . rawurlencode($r['slug']);
foreach (rows('SELECT slug FROM destinations WHERE active = 1') as $r) $urls[] = 'destination.php?slug=' . rawurlencode($r['slug']);
foreach (rows('SELECT slug FROM posts WHERE published = 1') as $r) $urls[] = 'post.php?slug=' . rawurlencode($r['slug']);
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) echo '  <url><loc>' . h(abs_url($u)) . "</loc></url>\n";
echo '</urlset>';
