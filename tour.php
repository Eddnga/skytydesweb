<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$t = row('SELECT t.*, d.name dest_name, d.slug dest_slug FROM tours t LEFT JOIN destinations d ON d.id = t.destination_id WHERE t.slug = ? AND t.active = 1', [input('slug')]);
if (!$t) not_found();
$days = rows('SELECT * FROM itinerary_days WHERE tour_id = ? ORDER BY day_no', [$t['id']]);
$reviews = rows("SELECT * FROM reviews WHERE tour_id = ? AND status = 'approved' ORDER BY approved_at DESC LIMIT 6", [$t['id']]);
$related = tours_query('t.id <> ? AND (t.destination_id = ? OR t.featured = 1)', [$t['id'], $t['destination_id'] ?? 0], 'LIMIT 3');
$price = money($t['price_from'], $t['currency']);
$bestTime = $t['destination_id'] ? (string) val('SELECT best_time FROM destinations WHERE id = ?', [$t['destination_id']]) : '';
$qa = tour_answers($t, $days, $bestTime ?: null);
$tourOffer = array_values(array_filter(active_offers(), fn($o) => (int) $o['tour_id'] === (int) $t['id']))[0] ?? null;
site_header($t['title'], ['active' => 'tours', 'desc' => ($t['seo_description'] ?? '') ?: ($t['summary'] ?: excerpt($t['body'])), 'image' => $t['image'], 'ga_item' => ['name' => $t['title'], 'category' => $t['dest_name'] ?: 'Tour', 'price' => $t['price_from'], 'currency' => $t['currency'] ?: 'KES'], 'seo_title' => $t['seo_title'] ?? '',
    'schema' => [schema_tour($t, $days, $t['dest_name']), schema_breadcrumbs([['Tours', 'tours.php']], $t['title']), schema_faq($qa)]]);
?>
<section class="phero tall">
  <div class="phero-bg" style="background-image:url('<?= h(upload_url($t['image'])) ?>')"></div>
  <div class="phero-shade"></div>
  <div class="wrap phero-in">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= url('') ?>">Home</a> <span>/</span> <a href="<?= url('tours.php') ?>">Tours</a><?php if ($t['dest_name']): ?> <span>/</span> <a href="<?= url('destination.php?slug=' . rawurlencode($t['dest_slug'])) ?>"><?= h($t['dest_name']) ?></a><?php endif; ?></nav>
    <h1><?= h($t['title']) ?></h1>
    <?php if ($t['summary']): ?><p class="lead"><?= h($t['summary']) ?></p><?php endif; ?>
  </div>
</section>
<div class="facts wrap">
  <?php if ($t['duration_days']): ?><div><?= sicon('clock') ?><span>Duration</span><b><?= (int) $t['duration_days'] ?> day<?= $t['duration_days'] > 1 ? 's' : '' ?></b></div><?php endif; ?>
  <?php if ($t['dest_name']): ?><div><?= sicon('pin') ?><span>Destination</span><b><?= h($t['dest_name']) ?></b></div><?php endif; ?>
  <?php if ($t['tour_type']): ?><div><?= sicon('users') ?><span>Type</span><b><?= h($t['tour_type']) ?></b></div><?php endif; ?>
  <div><?= sicon('tag') ?><span>From</span><b><?= $price ? h($price) : 'On request' ?></b></div>
</div>

<section class="sec tour">
  <div class="wrap tour-grid">
    <div class="tour-main">
      <?php if ($t['body']): ?><h2 class="h3">Overview</h2><div class="prose"><?= md($t['body']) ?></div><?php endif; ?>
      <?php if ($hl = lines($t['highlights'])): ?>
        <h2 class="h3">Highlights</h2><ul class="ticks"><?php foreach ($hl as $l): ?><li><?= sicon('check', 18) ?><?= h($l) ?></li><?php endforeach; ?></ul>
      <?php endif; ?>
      <?php if ($days): ?>
        <h2 class="h3">Itinerary</h2>
        <ol class="itin">
          <?php foreach ($days as $d): ?>
          <li>
            <span class="itin-day">Day <?= (int) $d['day_no'] ?></span>
            <h3><?= h($d['title']) ?></h3>
            <?php if ($d['body']): ?><div class="prose"><?= md($d['body']) ?></div><?php endif; ?>
            <?php if ($d['meals'] || $d['stay']): ?><p class="itin-meta"><?php if ($d['meals']): ?><span><?= sicon('meal', 16) ?><?= h($d['meals']) ?></span><?php endif; ?><?php if ($d['stay'] && $d['stay'] !== '—'): ?><span><?= sicon('bed', 16) ?><?= h($d['stay']) ?></span><?php endif; ?></p><?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
      <?php $inc = lines($t['includes']); $exc = lines($t['excludes']); if ($inc || $exc): ?>
      <div class="incl">
        <?php if ($inc): ?><div><h2 class="h3">Included</h2><ul class="ticks"><?php foreach ($inc as $l): ?><li><?= sicon('check', 18) ?><?= h($l) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <?php if ($exc): ?><div><h2 class="h3">Not included</h2><ul class="ticks no"><?php foreach ($exc as $l): ?><li><?= sicon('x', 18) ?><?= h($l) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
      </div>
      <?php endif; ?>
      <?= answers_block($qa, 'Quick answers about this trip') ?>
      <?php if ($reviews): ?><h2 class="h3">Reviews</h2><?= reviews_slider($reviews) ?><?php endif; ?>
      <p class="tour-review"><a class="link-more" href="<?= url('reviews.php?write=1&tour=' . (int) $t['id'] . '#write') ?>"><?= sicon('star', 16) ?> Been on this trip? Write a review</a></p>
    </div>
    <aside class="book">
      <div class="book-card">
        <p class="eyebrow">Book this trip</p>
        <?php if ($tourOffer): ?><a class="book-offer" href="<?= url('offers.php') ?>"><?= sicon('tag', 16) ?><span><b><?= h($tourOffer['tag'] ?: 'Special offer') ?></b> <?= h($tourOffer['title']) ?></span></a><?php endif; ?>
        <div class="book-price"><?php if ($price): ?><span>From</span><b><?= h($price) ?></b><small><?= h($t['price_note']) ?></small><?php else: ?><b>Price on request</b><small>Get a quote for your dates on WhatsApp</small><?php endif; ?></div>
        <?= wa_btn('Book on WhatsApp', 'tour', $t['title'], 'btn wa block lg', (int) $t['id']) ?>
        <ul class="book-points"><li><?= sicon('check', 16) ?>Quick reply from our team</li><li><?= sicon('check', 16) ?>Private or group options</li><li><?= sicon('check', 16) ?>Tailored to your budget</li></ul>
        <p class="book-call">Prefer to talk? <a href="tel:<?= h(preg_replace('/\s+/', '', setting('phone1'))) ?>"><?= h(setting('phone1')) ?></a></p>
      </div>
    </aside>
  </div>
</section>
<?php if ($related): ?>
<section class="sec tint"><div class="wrap"><div class="sec-head"><div><p class="eyebrow">You may also like</p><h2 class="h2">More trips</h2></div></div><div class="grid3"><?php foreach ($related as $r) echo tour_card($r); ?></div></div></section>
<?php endif; ?>
<div class="mobile-book"><div><small>From</small><b><?= $price ? h($price) : 'On request' ?></b></div><?= wa_btn('Book on WhatsApp', 'tour', $t['title'], 'btn wa', (int) $t['id']) ?></div>
<?php site_footer();
