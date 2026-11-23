<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$items = rows('SELECT * FROM gallery WHERE active = 1 ORDER BY sort, id DESC');
$cats = array_values(array_unique(array_filter(array_column($items, 'category'))));
site_header('Gallery', ['active' => 'gallery']);
page_hero('Gallery', 'Moments from the wild', $items[0]['image'] ?? null);
?>
<section class="sec">
  <div class="wrap">
    <?php if ($cats): ?><nav class="chips" data-filter><button class="on" data-cat="">All</button><?php foreach ($cats as $c): ?><button data-cat="<?= h($c) ?>"><?= h($c) ?></button><?php endforeach; ?></nav><?php endif; ?>
    <div class="masonry">
      <?php foreach ($items as $g): ?>
      <a class="gitem" href="<?= h(upload_url($g['image'])) ?>" data-cat="<?= h($g['category']) ?>" data-lightbox data-caption="<?= h($g['caption']) ?>">
        <img src="<?= h(upload_url($g['image'], 'sm')) ?>" alt="<?= h($g['caption']) ?>" loading="lazy">
        <?php if ($g['caption']): ?><span><?= h($g['caption']) ?></span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<div class="lightbox" id="lightbox" hidden><button class="lb-x" aria-label="Close"><?= sicon('x', 28) ?></button><button class="lb-prev" aria-label="Previous">‹</button><figure><img alt=""><figcaption></figcaption></figure><button class="lb-next" aria-label="Next">›</button></div>
<?php site_footer();
