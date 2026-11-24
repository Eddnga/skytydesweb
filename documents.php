<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$docs = rows('SELECT * FROM documents WHERE active = 1 AND file IS NOT NULL ORDER BY featured DESC, sort, id DESC');
$featured = $docs && $docs[0]['featured'] ? array_shift($docs) : null;
$groups = [];
foreach ($docs as $d) $groups[$d['category'] ?: 'Other'][] = $d;
site_header('Brochures & documents', ['active' => 'about', 'seo' => 'documents', 'desc' => 'Read the SkyTydes Safaris company profile, brochures and rates.']);
page_hero('Brochures & *documents*', 'Read & download', val('SELECT image FROM hero_slides WHERE active = 1 ORDER BY sort LIMIT 1 OFFSET 1') ?: null, 'Our company profile, brochures and rates — read them online or save a copy.', [['About', 'about.php']]);
?>
<section class="sec">
  <div class="wrap">
    <?php if ($featured): ?>
      <article class="docfeat">
        <div class="docfeat-img"><?php if ($featured['image']): ?><img src="<?= h(upload_url($featured['image'], 'sm')) ?>" alt=""><?php else: ?><span class="pdfmark"><?= sicon('file', 54) ?><b>PDF</b></span><?php endif; ?></div>
        <div class="docfeat-txt">
          <p class="eyebrow"><?= h($featured['category']) ?></p>
          <h2 class="h2"><?= h($featured['title']) ?></h2>
          <?php if ($featured['summary']): ?><p class="muted"><?= h($featured['summary']) ?></p><?php endif; ?>
          <?= doc_buttons($featured, true) ?>
        </div>
      </article>
    <?php endif; ?>
    <?php foreach ($groups as $g => $list): ?>
      <h2 class="h3 doc-group"><?= h($g) ?></h2>
      <div class="docgrid"><?php foreach ($list as $d) echo doc_card($d); ?></div>
    <?php endforeach; ?>
    <?php if (!$featured && !$docs): ?>
      <div class="empty-state"><h2 class="h2">Documents are <em>on the way</em></h2><p class="muted">Ask us on WhatsApp and we'll send our company profile and current rates straight away.</p><?= wa_btn('Request our profile', 'general', 'Company profile request') ?></div>
    <?php endif; ?>
  </div>
</section>
<?php site_footer();
