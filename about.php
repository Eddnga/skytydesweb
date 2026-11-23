<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$why = array_map(fn($l) => array_map('trim', explode('|', $l, 2)) + [1 => ''], lines(setting('why_us')));
site_header('About us', ['active' => 'about']);
page_hero(setting('about_title', 'About us'), 'Our story', val('SELECT image FROM gallery WHERE active = 1 ORDER BY sort LIMIT 1') ?: null);
?>
<section class="sec">
  <div class="wrap narrow prose big"><?= md(setting('about_body')) ?></div>
  <?php $profile = row("SELECT * FROM documents WHERE active = 1 AND file IS NOT NULL ORDER BY (category = 'Company profile') DESC, featured DESC, sort LIMIT 1"); ?>
  <div class="wrap narrow"><div class="profile-cta">
    <div><?= sicon('file', 30) ?><span><b><?= $profile ? h($profile['title']) : 'Our company profile' ?></b><small><?= $profile ? 'Read it online or download a copy' : 'Rates, brochures and more' ?></small></span></div>
    <?php if ($profile): ?><?= doc_buttons($profile) ?><?php else: ?><a class="btn dark sm" href="<?= url('documents.php') ?>">Brochures &amp; rates</a><?php endif; ?>
  </div></div>
</section>
<?php if ($hosts = team_cards()): ?>
<section class="sec" id="hosts"><div class="wrap"><div class="sec-head center"><div><p class="eyebrow">Your hosts</p><h2 class="h2">The people behind <em>SkyTydes</em></h2></div></div><?= $hosts ?></div></section>
<?php endif; ?>
<?php if (setting('impact_body')): ?>
<section class="sec tint"><div class="wrap narrow center"><p class="eyebrow">Travel with purpose</p><h2 class="h2"><?= hl(setting('impact_title')) ?></h2><p class="lead dark" style="margin:20px auto 0"><?= h(setting('impact_body')) ?></p></div></section>
<?php endif; ?>
<?php if ($why): ?>
<section class="sec tint"><div class="wrap"><div class="why"><?php foreach ($why as $i => $w): ?><div class="why-item"><span class="why-n"><?= sprintf('%02d', $i + 1) ?></span><h3><?= h($w[0]) ?></h3><p><?= h($w[1]) ?></p></div><?php endforeach; ?></div></div></section>
<?php endif; ?>
<section class="sec map-sec" id="visit"><div class="wrap"><?= office_map() ?></div></section>
<?php site_footer();
