<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$d = row('SELECT * FROM destinations WHERE slug = ? AND active = 1', [input('slug')]);
if (!$d) not_found();
$tours = tours_query('t.destination_id = ?', [$d['id']]);
site_header($d['name'] . ' Safaris', ['active' => 'destinations', 'desc' => ($d['seo_description'] ?? '') ?: ($d['summary'] ?: ''), 'image' => $d['image'], 'ga_item' => ['name' => $d['name'], 'category' => 'Destination'], 'seo_title' => $d['seo_title'] ?? '', 'schema' => [schema_destination($d)]]);
page_hero($d['name'], $d['tagline'] ?: $d['region'], $d['image'], (string) $d['summary'], [['Destinations', 'destinations.php']]);
?>
<section class="sec">
  <div class="wrap tour-grid">
    <div class="tour-main prose big"><?= md($d['body']) ?></div>
    <aside class="book">
      <div class="book-card">
        <?php if ($d['region']): ?><p class="kv"><span>Region</span><b><?= h($d['region']) ?></b></p><?php endif; ?>
        <?php if ($d['best_time']): ?><p class="kv"><span>Best time to visit</span><b><?= h($d['best_time']) ?></b></p><?php endif; ?>
        <?php if ($d['coords']): ?><p class="kv"><span>Coordinates</span><b><?= h($d['coords']) ?></b></p><?php endif; ?>
        <?= wa_btn('Plan a trip here', 'destination', $d['name'], 'btn wa block lg') ?>
      </div>
    </aside>
  </div>
</section>
<?php if ($tours): ?>
<section class="sec tint"><div class="wrap"><div class="sec-head"><div><p class="eyebrow">Tours</p><h2 class="h2">Trips to <?= h($d['name']) ?></h2></div></div><div class="grid3"><?php foreach ($tours as $t) echo tour_card($t); ?></div></div></section>
<?php endif; ?>
<?php site_footer();
