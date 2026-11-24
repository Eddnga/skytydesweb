<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$p = row('SELECT * FROM posts WHERE slug = ? AND published = 1 AND published_at <= ?', [input('slug'), now()]);
if (!$p) not_found();
$more = rows('SELECT * FROM posts WHERE published = 1 AND id <> ? AND published_at <= ? ORDER BY published_at DESC LIMIT 3', [$p['id'], now()]);
site_header($p['title'], ['active' => 'blog', 'desc' => ($p['seo_description'] ?? '') ?: ($p['excerpt'] ?: excerpt($p['body'])), 'image' => $p['image'], 'ga_item' => ['name' => $p['title'], 'category' => $p['category'] ?: 'Journal'], 'seo_title' => $p['seo_title'] ?? '', 'og_type' => 'article', 'schema' => [schema_post($p)]]);
page_hero($p['title'], ($p['category'] ?: 'Journal') . ' · ' . fdate($p['published_at']), $p['image'], '', [['Journal', 'blog.php']]);
?>
<article class="sec">
  <div class="wrap narrow">
    <?php if ($p['excerpt']): ?><p class="lead dark"><?= h($p['excerpt']) ?></p><?php endif; ?>
    <div class="prose big"><?= md($p['body']) ?></div>
    <div class="post-foot"><span>By <?= h($p['author'] ?: 'SkyTydes Safaris') ?></span><?= wa_btn('Plan a trip on WhatsApp', 'blog', $p['title'], 'btn wa') ?></div>
  </div>
</article>
<?php if ($more): ?><section class="sec tint"><div class="wrap"><div class="sec-head"><div><p class="eyebrow">Keep reading</p><h2 class="h2">More from the journal</h2></div></div><div class="grid3"><?php foreach ($more as $m) echo post_card($m); ?></div></div></section><?php endif; ?>
<?php site_footer();
