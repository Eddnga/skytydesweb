<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$dests = rows('SELECT d.id, d.name, d.slug FROM destinations d WHERE d.active = 1 AND EXISTS (SELECT 1 FROM tours t WHERE t.destination_id = d.id AND t.active = 1) ORDER BY d.sort, d.name');
$f = input('d');
$cur = $f !== '' ? row('SELECT * FROM destinations WHERE slug = ? AND active = 1', [$f]) : null;
$tours = $cur ? tours_query('t.destination_id = ?', [$cur['id']]) : tours_query();
site_header($cur ? $cur['name'] . ' tours' : 'Tour packages', ['active' => 'tours', 'desc' => 'Kenya safari packages and coast holidays by SkyTydes Safaris — book on WhatsApp.']);
page_hero($cur ? 'Tours in ' . $cur['name'] : 'Tour packages', 'Find your trip', $cur['image'] ?? (val('SELECT image FROM tours WHERE active = 1 AND image IS NOT NULL ORDER BY featured DESC, sort LIMIT 1') ?: null), 'Every trip can be made private and shaped around your dates and budget.');
?>
<section class="sec">
  <div class="wrap">
    <?php if ($dests): ?>
    <nav class="chips" aria-label="Filter by destination">
      <a href="<?= url('tours.php') ?>" class="<?= !$cur ? 'on' : '' ?>">All</a>
      <?php foreach ($dests as $d): ?><a href="<?= url('tours.php?d=' . rawurlencode($d['slug'])) ?>" class="<?= $cur && $cur['id'] == $d['id'] ? 'on' : '' ?>"><?= h($d['name']) ?></a><?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <?php if ($tours): ?><div class="grid3"><?php foreach ($tours as $t) echo tour_card($t); ?></div>
    <?php else: ?><div class="empty-state"><h2>New trips are on the way</h2><p>Tell us where you'd like to go and we'll plan it for you.</p><?= wa_btn('Ask on WhatsApp', 'general', 'Custom trip request') ?></div><?php endif; ?>
    <div class="custom-trip">
      <div><h2 class="h3">Don't see what you're looking for?</h2><p>We design trips from scratch — honeymoons, family holidays, corporate retreats and group getaways.</p></div>
      <?= wa_btn('Plan a custom trip', 'general', 'Custom trip request') ?>
    </div>
  </div>
</section>
<?php site_footer();
