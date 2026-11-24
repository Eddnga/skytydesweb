<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$dests = rows('SELECT d.*, (SELECT COUNT(*) FROM tours t WHERE t.destination_id = d.id AND t.active = 1) n FROM destinations d WHERE d.active = 1 ORDER BY d.sort, d.name');
site_header('Destinations', ['active' => 'destinations', 'seo' => 'destinations']);
page_hero('Destinations', 'Where the tides take you', $dests[0]['image'] ?? null, 'From the plains of the Mara to the white sands of the coast.');
?>
<section class="sec">
  <div class="wrap dlist">
    <?php foreach ($dests as $i => $d): ?>
    <article class="drow<?= $i % 2 ? ' flip' : '' ?>">
      <a class="drow-img" href="<?= url('destination.php?slug=' . rawurlencode($d['slug'])) ?>"><img src="<?= h(upload_url($d['image'], 'sm')) ?>" alt="<?= h($d['name']) ?>" loading="lazy"></a>
      <div class="drow-txt">
        <span class="dnum big"><?= sprintf('%02d', $i + 1) ?></span>
        <p class="eyebrow"><?= h($d['tagline'] ?: $d['region']) ?></p>
        <h2 class="h2"><?= h($d['name']) ?></h2>
        <p><?= h($d['summary']) ?></p>
        <?php if ($d['coords']): ?><p class="coords-inline"><?= sicon('pin', 15) ?><?= h($d['coords']) ?></p><?php endif; ?>
        <div class="row-btns"><a class="btn dark" href="<?= url('destination.php?slug=' . rawurlencode($d['slug'])) ?>">Discover <?= sicon('arrow', 18) ?></a><?php if ($d['n']): ?><a class="link-more" href="<?= url('tours.php?d=' . rawurlencode($d['slug'])) ?>"><?= (int) $d['n'] ?> tour<?= $d['n'] == 1 ? '' : 's' ?></a><?php endif; ?></div>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</section>
<?php site_footer();
