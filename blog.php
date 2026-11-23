<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$per = 9;
$page = max(1, (int) input('p', '1'));
$cat = input('c');
$where = 'published = 1 AND published_at <= ?';
$params = [now()];
if ($cat !== '') { $where .= ' AND category = ?'; $params[] = $cat; }
$total = (int) val("SELECT COUNT(*) FROM posts WHERE $where", $params);
$posts = rows("SELECT * FROM posts WHERE $where ORDER BY published_at DESC LIMIT $per OFFSET " . (($page - 1) * $per), $params);
$cats = array_column(rows('SELECT DISTINCT category FROM posts WHERE published = 1 AND category IS NOT NULL ORDER BY category'), 'category');
site_header('Journal', ['active' => 'blog', 'desc' => 'Safari tips, destination guides and stories from SkyTydes Safaris.']);
page_hero('The journal', 'Tips & stories', $posts[0]['image'] ?? null);
?>
<section class="sec">
  <div class="wrap">
    <?php if ($cats): ?><nav class="chips"><a href="<?= url('blog.php') ?>" class="<?= $cat === '' ? 'on' : '' ?>">All</a><?php foreach ($cats as $c): ?><a href="<?= url('blog.php?c=' . rawurlencode($c)) ?>" class="<?= $cat === $c ? 'on' : '' ?>"><?= h($c) ?></a><?php endforeach; ?></nav><?php endif; ?>
    <?php if ($posts): ?><div class="grid3"><?php foreach ($posts as $p) echo post_card($p); ?></div><?php else: ?><p class="muted">No articles yet.</p><?php endif; ?>
    <?php if ($total > $per): ?><nav class="pager"><?php for ($i = 1; $i <= ceil($total / $per); $i++): ?><a class="<?= $i === $page ? 'on' : '' ?>" href="<?= url('blog.php?p=' . $i . ($cat !== '' ? '&c=' . rawurlencode($cat) : '')) ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
  </div>
</section>
<?php site_footer();
