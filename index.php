<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$slides = rows('SELECT * FROM hero_slides WHERE active = 1 ORDER BY sort, id');
$featured = tours_query('t.featured = 1', [], 'LIMIT 6');
if (!$featured) $featured = tours_query('1=1', [], 'LIMIT 6');
$dests = rows('SELECT d.*, (SELECT COUNT(*) FROM tours t WHERE t.destination_id = d.id AND t.active = 1) n FROM destinations d WHERE d.active = 1 ORDER BY d.sort, d.name LIMIT 8');
$services = rows('SELECT * FROM services WHERE active = 1 ORDER BY sort, id LIMIT 6');
$reviews = rows("SELECT r.*, t.title tour FROM reviews r LEFT JOIN tours t ON t.id = r.tour_id WHERE r.status = 'approved' ORDER BY r.created_at DESC LIMIT 12");
$avg = $reviews ? round((float) val("SELECT AVG(rating) FROM reviews WHERE status = 'approved'"), 1) : 0;
$nrev = (int) val("SELECT COUNT(*) FROM reviews WHERE status = 'approved'");
$gallery = rows('SELECT * FROM gallery WHERE active = 1 ORDER BY sort, id DESC LIMIT 8');
$posts = rows('SELECT * FROM posts WHERE published = 1 AND published_at <= ? ORDER BY published_at DESC LIMIT 3', [now()]);
$offers = active_offers(3);
$ways = array_map(fn($l) => array_map('trim', explode('|', $l)) + [1 => '', 2 => 'tours.php'], lines(setting('ways')));
$creds = array_map(fn($l) => array_map('trim', explode('|', $l, 2)) + [1 => ''], lines(setting('credentials')));
$quote = row("SELECT r.*, t.title tour FROM reviews r LEFT JOIN tours t ON t.id = r.tour_id WHERE r.status = 'approved' AND r.rating = 5 ORDER BY LENGTH(r.body) DESC LIMIT 1");
$wayImgs = array_column(rows('SELECT image FROM tours WHERE active = 1 AND image IS NOT NULL ORDER BY featured DESC, sort LIMIT 3'), 'image');
$why = array_map(fn($l) => array_map('trim', explode('|', $l, 2)) + [1 => ''], lines(setting('why_us')));

site_header('', ['image' => $slides[0]['image'] ?? null]);
?>
<section class="hero" data-slider>
  <?php foreach ($slides as $i => $s): ?>
  <div class="slide<?= $i === 0 ? ' on' : '' ?>">
    <div class="slide-bg" style="background-image:url('<?= h(upload_url($s['image'])) ?>')"></div>
    <div class="slide-shade"></div>
    <div class="wrap slide-in">
      <?php if ($s['eyebrow']): ?><p class="eyebrow"><?= h($s['eyebrow']) ?></p><?php endif; ?>
      <<?= $i === 0 ? 'h1' : 'h2' ?> class="display"><?= h($s['title']) ?></<?= $i === 0 ? 'h1' : 'h2' ?>>
      <?php if ($s['subtitle']): ?><p class="lead"><?= h($s['subtitle']) ?></p><?php endif; ?>
      <div class="hero-btns">
        <?php if ($s['cta_label'] && $s['cta_link']): ?><a class="btn gold lg" href="<?= h(preg_match('#^https?://#', $s['cta_link']) ? $s['cta_link'] : url($s['cta_link'])) ?>"><?= h($s['cta_label']) ?> <?= sicon('arrow', 18) ?></a><?php endif; ?>
        <?= wa_btn('Plan on WhatsApp', 'general', 'Home hero', 'btn glass lg') ?>
      </div>
    </div>
    <?php if ($s['caption']): ?><p class="coords"><?= sicon('pin', 15) ?><?= h($s['caption']) ?></p><?php endif; ?>
  </div>
  <?php endforeach; ?>
  <?php if (count($slides) > 1): ?>
  <div class="hero-ctrl wrap"><span class="count"><b>01</b> / <?= sprintf('%02d', count($slides)) ?></span>
    <div class="bars"><?php foreach ($slides as $i => $s): ?><button class="bar<?= $i === 0 ? ' on' : '' ?>" aria-label="Show slide <?= $i + 1 ?>"><i></i></button><?php endforeach; ?></div></div>
  <?php endif; ?>
  <svg class="hero-wave" viewBox="0 0 1440 90" preserveAspectRatio="none" aria-hidden="true"><path d="M0 60 C 360 0, 720 100, 1080 40 S 1440 30, 1440 30 V90 H0Z" fill="#FBFAF6"/></svg>
</section>

<section class="sec intro">
  <div class="wrap intro-grid">
    <div><p class="eyebrow">Welcome to SkyTydes</p><h2 class="h2"><?= h(setting('intro_title')) ?></h2></div>
    <div class="prose"><?= md(setting('intro_body')) ?><a class="link-more" href="<?= url('about.php') ?>">Our story <?= sicon('arrow', 18) ?></a></div>
  </div>
  <?php if ($creds): ?><div class="wrap creds"><?php foreach ($creds as $c): ?><div><b><?= h($c[0]) ?></b><span><?= h($c[1]) ?></span></div><?php endforeach; ?></div><?php endif; ?>
</section>

<?php if ($ways): ?>
<section class="sec ways-sec">
  <div class="wrap">
    <div class="sec-head center"><div><p class="eyebrow">Ways to travel</p><h2 class="h2"><?= count($ways) === 3 ? 'Three ways' : 'Ways' ?> to <em>safari</em></h2></div></div>
    <div class="ways">
      <?php foreach ($ways as $i => $w): ?>
      <a class="way" href="<?= h(preg_match('#^https?://#', $w[2]) ? $w[2] : url($w[2])) ?>">
        <img src="<?= h(upload_url($wayImgs[$i % max(1, count($wayImgs))] ?? null, 'sm')) ?>" alt="" loading="lazy">
        <span class="way-in"><span class="way-n"><?= sprintf('%02d', $i + 1) ?></span><b><?= h($w[0]) ?></b><small><?= h($w[1]) ?></small><em>Explore <?= sicon('arrow', 16) ?></em></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($featured): ?>
<section class="sec">
  <div class="wrap">
    <div class="sec-head"><div><p class="eyebrow">Tour packages</p><h2 class="h2">Trips <em>ready to go</em></h2></div><a class="link-more" href="<?= url('tours.php') ?>">All tours <?= sicon('arrow', 18) ?></a></div>
    <div class="grid3"><?php foreach ($featured as $t) echo tour_card($t); ?></div>
  </div>
</section>
<?php endif; ?>

<?php if ($offers): ?>
<section class="sec tint">
  <div class="wrap">
    <div class="sec-head"><div><p class="eyebrow">Limited time</p><h2 class="h2">Special <em>offers</em></h2></div><a class="link-more" href="<?= url('offers.php') ?>">All offers <?= sicon('arrow', 18) ?></a></div>
    <div class="ogrid"><?php foreach ($offers as $o) echo offer_card($o); ?></div>
  </div>
</section>
<?php endif; ?>

<?php if ($dests): ?>
<section class="sec dark">
  <div class="wrap">
    <div class="sec-head"><div><p class="eyebrow">Destinations</p><h2 class="h2">Where the tides <em>take you</em></h2></div><a class="link-more" href="<?= url('destinations.php') ?>">All destinations <?= sicon('arrow', 18) ?></a></div>
    <div class="dgrid">
      <?php foreach ($dests as $i => $d): ?>
      <a class="dcard" href="<?= url('destination.php?slug=' . rawurlencode($d['slug'])) ?>">
        <img src="<?= h(upload_url($d['image'], 'sm')) ?>" alt="<?= h($d['name']) ?>" loading="lazy">
        <span class="dnum"><?= sprintf('%02d', $i + 1) ?></span>
        <span class="dtxt"><small><?= h($d['tagline'] ?: $d['region']) ?></small><b><?= h($d['name']) ?></b><em><?= (int) $d['n'] ?> tour<?= $d['n'] == 1 ? '' : 's' ?> <?= sicon('arrow', 16) ?></em></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($why): ?>
<section class="sec">
  <div class="wrap">
    <div class="sec-head center"><div><p class="eyebrow">Why SkyTydes</p><h2 class="h2">You pack the bag. <em>We handle the rest.</em></h2></div></div>
    <div class="why">
      <?php foreach ($why as $i => $w): ?><div class="why-item"><span class="why-n"><?= sprintf('%02d', $i + 1) ?></span><h3><?= h($w[0]) ?></h3><p><?= h($w[1]) ?></p></div><?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($quote): ?>
<section class="pullquote">
  <div class="wrap narrow center">
    <span class="pq-mark" aria-hidden="true">“</span>
    <blockquote><?= h(excerpt($quote['body'], 260)) ?></blockquote>
    <p class="pq-by"><?= stars(5) ?><b><?= h($quote['name']) ?></b><?= $quote['country'] ? ' · ' . h($quote['country']) : '' ?><?= $quote['tour'] ? ' · ' . h($quote['tour']) : '' ?></p>
  </div>
</section>
<?php endif; ?>

<?php if ($services): ?>
<section class="sec tint">
  <div class="wrap">
    <div class="sec-head"><div><p class="eyebrow">What we do</p><h2 class="h2">Services</h2></div><a class="link-more" href="<?= url('services.php') ?>">All services <?= sicon('arrow', 18) ?></a></div>
    <div class="sgrid"><?php foreach ($services as $s): ?><div class="scard"><span class="sic"><?= sicon($s['icon'] ?: 'star', 26) ?></span><h3><?= h($s['title']) ?></h3><p><?= h($s['summary']) ?></p></div><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>

<?php if ($hosts = team_cards()): ?>
<section class="sec hosts-sec">
  <div class="wrap hosts-grid">
    <div><p class="eyebrow">Your hosts</p><h2 class="h2">Real people, <em>planning your trip</em></h2><p class="muted">When you message SkyTydes, you talk to the team that plans every detail — from the first quote to the drive home.</p><a class="link-more" href="<?= url('about.php') ?>">Our story <?= sicon('arrow', 18) ?></a></div>
    <?= $hosts ?>
  </div>
</section>
<?php endif; ?>

<?php if (setting('impact_body')): ?>
<section class="impact">
  <div class="impact-bg" style="background-image:url('<?= h(upload_url(val('SELECT image FROM destinations WHERE active = 1 ORDER BY sort LIMIT 1 OFFSET 1') ?: null)) ?>')"></div>
  <div class="wrap impact-in">
    <p class="eyebrow">Travel with purpose</p>
    <h2 class="h2"><?= hl(setting('impact_title')) ?></h2>
    <p><?= h(setting('impact_body')) ?></p>
  </div>
</section>
<?php endif; ?>

<section class="sec">
  <div class="wrap">
    <div class="sec-head"><div><p class="eyebrow">Guest reviews</p><h2 class="h2">What our <em>travellers say</em></h2></div><a class="link-more" href="<?= url('reviews.php') ?>">All reviews <?= sicon('arrow', 18) ?></a></div>
    <div class="reviews-layout">
      <?= review_summary() ?>
      <?php if ($reviews): ?><?= reviews_slider($reviews) ?><?php else: ?><div class="rempty"><p>We'd love to hear about your trip — your review will appear here once our team has approved it.</p></div><?php endif; ?>
    </div>
  </div>
</section>

<?php if ($gallery): ?>
<section class="sec gal-strip">
  <div class="wrap"><div class="sec-head"><div><p class="eyebrow">Gallery</p><h2 class="h2">Moments from <em>the wild</em></h2></div><a class="link-more" href="<?= url('gallery.php') ?>">View gallery <?= sicon('arrow', 18) ?></a></div></div>
  <div class="strip"><?php foreach ($gallery as $g): ?><a href="<?= url('gallery.php') ?>"><img src="<?= h(upload_url($g['image'], 'sm')) ?>" alt="<?= h($g['caption']) ?>" loading="lazy"></a><?php endforeach; ?></div>
</section>
<?php endif; ?>

<?php if ($posts): ?>
<section class="sec">
  <div class="wrap">
    <div class="sec-head"><div><p class="eyebrow">The journal</p><h2 class="h2">Tips &amp; <em>stories</em></h2></div><a class="link-more" href="<?= url('blog.php') ?>">All articles <?= sicon('arrow', 18) ?></a></div>
    <div class="grid3"><?php foreach ($posts as $p) echo post_card($p); ?></div>
  </div>
</section>
<?php endif; ?>
<?php site_footer();
