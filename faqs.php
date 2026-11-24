<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$faqs = rows('SELECT * FROM faqs WHERE active = 1 ORDER BY sort, id');
$groups = [];
foreach ($faqs as $f) $groups[$f['category'] ?: 'General'][] = $f;
$ld = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn($f) => ['@type' => 'Question', 'name' => $f['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags(md($f['answer']))]], $faqs)];
site_header('FAQs', ['active' => 'faqs', 'seo' => 'faqs', 'desc' => 'Answers to common questions about booking a safari with SkyTydes Safaris.', 'schema' => [$ld]]);
page_hero('Frequently asked questions', 'Good to know', val('SELECT image FROM hero_slides WHERE active = 1 ORDER BY sort DESC LIMIT 1') ?: null);
?>
<section class="sec">
  <div class="wrap narrow">
    <?php foreach ($groups as $g => $list): ?>
      <h2 class="h3 faq-group"><?= h($g) ?></h2>
      <div class="faq">
        <?php foreach ($list as $f): ?><details><summary><?= h($f['question']) ?></summary><div class="prose"><?= md($f['answer']) ?></div></details><?php endforeach; ?>
      </div>
    <?php endforeach; ?>
    <div class="custom-trip"><div><h2 class="h3">Still have a question?</h2><p>We usually reply on WhatsApp within minutes during working hours.</p></div><?= wa_btn('Ask us on WhatsApp', 'general', 'FAQ question') ?></div>
  </div>
</section>
<?php site_footer();
