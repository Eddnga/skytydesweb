<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$services = rows('SELECT * FROM services WHERE active = 1 ORDER BY sort, id');
site_header('Services', ['active' => 'services', 'seo' => 'services']);
page_hero('Our services', 'What we do', val('SELECT image FROM hero_slides WHERE active = 1 ORDER BY sort LIMIT 1 OFFSET 1') ?: null, 'Everything you need for a smooth, unforgettable trip.');
?>
<section class="sec">
  <div class="wrap">
    <div class="sgrid big">
      <?php foreach ($services as $s): ?>
      <article class="scard">
        <span class="sic"><?= sicon($s['icon'] ?: 'star', 28) ?></span>
        <h2 class="h3"><?= h($s['title']) ?></h2>
        <p><?= h($s['summary']) ?></p>
        <?php if ($s['body']): ?><div class="prose small"><?= md($s['body']) ?></div><?php endif; ?>
        <?= wa_btn('Ask about this', 'service', $s['title'], 'link-wa') ?>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php site_footer();
