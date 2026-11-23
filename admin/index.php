<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
$user = require_login();

$c = fn($sql) => (int) val($sql);
$stats = [
    ['Tour packages', $c('SELECT COUNT(*) FROM tours WHERE active = 1'), 'content.php?type=tours', 'map'],
    ['Destinations', $c('SELECT COUNT(*) FROM destinations WHERE active = 1'), 'content.php?type=destinations', 'pin'],
    ['Blog posts', $c('SELECT COUNT(*) FROM posts WHERE published = 1'), 'content.php?type=posts', 'pen'],
    ['Gallery photos', $c('SELECT COUNT(*) FROM gallery WHERE active = 1'), 'content.php?type=gallery', 'grid'],
    ['Newsletter subscribers', $c('SELECT COUNT(*) FROM subscribers'), 'subscribers.php', 'mail'],
];
$subs = rows('SELECT email, name, created_at FROM subscribers ORDER BY created_at DESC LIMIT 6');
$pending = rows("SELECT * FROM reviews WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5");
$leads = rows('SELECT * FROM leads ORDER BY created_at DESC LIMIT 8');
$leads30 = (int) val('SELECT COUNT(*) FROM leads WHERE created_at >= ?', [date('Y-m-d H:i:s', time() - 30 * 86400)]);
$top = rows('SELECT item, COUNT(*) n FROM leads WHERE item IS NOT NULL AND created_at >= ? GROUP BY item ORDER BY n DESC LIMIT 5', [date('Y-m-d H:i:s', time() - 30 * 86400)]);

admin_header('Welcome back, ' . explode(' ', $user['name'])[0], 'dashboard');
?>
<div class="stats">
  <?php foreach ($stats as $s): ?>
    <a class="stat card" href="<?= url('admin/' . $s[2]) ?>"><?= icon($s[3], 22) ?><b><?= $s[1] ?></b><span><?= h($s[0]) ?></span></a>
  <?php endforeach; ?>
  <a class="stat card hl" href="<?= url('admin/leads.php') ?>"><?= icon('chat', 22) ?><b><?= $leads30 ?></b><span>WhatsApp leads (30 days)</span></a>
</div>

<div class="cols">
  <section class="card">
    <div class="card-head"><h2>Reviews waiting for approval</h2><a href="<?= url('admin/reviews.php') ?>">All reviews →</a></div>
    <?php if (!$pending): ?><p class="empty">No reviews waiting. New reviews from the website will appear here.</p><?php endif; ?>
    <?php foreach ($pending as $r): ?>
      <div class="rev">
        <div><strong><?= h($r['name']) ?></strong> <span class="stars"><?= str_repeat('★', (int) $r['rating']) . str_repeat('☆', 5 - (int) $r['rating']) ?></span><span class="muted small"> · <?= h(fdate($r['created_at'], 'j M, H:i')) ?></span></div>
        <p><?= h(excerpt($r['body'], 160)) ?></p>
        <div class="row-gap">
          <form method="post" action="<?= url('admin/reviews.php') ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="do" value="approve"><button class="btn sm"><?= icon('check', 16) ?> Approve</button></form>
          <form method="post" action="<?= url('admin/reviews.php') ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="do" value="reject"><button class="btn ghost sm">Reject</button></form>
        </div>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="card">
    <div class="card-head"><h2>Latest WhatsApp leads</h2><a href="<?= url('admin/leads.php') ?>">All leads →</a></div>
    <?php if (!$leads): ?><p class="empty">When visitors tap "Book on WhatsApp", the trip they asked about is logged here.</p><?php else: ?>
    <table class="table compact"><tbody>
      <?php foreach ($leads as $l): ?>
        <tr><td><strong><?= h($l['item'] ?: ucfirst($l['source'])) ?></strong><br><span class="muted small"><?= h(trim(($l['name'] ? $l['name'] . ' · ' : '') . ($l['travellers'] ? $l['travellers'] . ' traveller(s)' : ''), ' ·')) ?></span></td>
          <td class="right muted small nowrap"><?= h(fdate($l['created_at'], 'j M, H:i')) ?></td></tr>
      <?php endforeach; ?>
    </tbody></table>
    <?php endif; ?>
    <?php if ($top): ?>
      <h3 class="sub">Most requested (30 days)</h3>
      <ol class="toplist"><?php foreach ($top as $t): ?><li><?= h($t['item']) ?> <b><?= (int) $t['n'] ?></b></li><?php endforeach; ?></ol>
    <?php endif; ?>
  </section>
</div>

<section class="card">
  <div class="card-head"><h2>Newest subscribers</h2><a href="<?= url('admin/subscribers.php') ?>">All subscribers →</a></div>
  <?php if (!$subs): ?><p class="empty">No subscribers yet. Visitors join from the sign-up box in the website footer.</p><?php else: ?>
  <table class="table compact"><tbody>
    <?php foreach ($subs as $s): ?><tr><td><strong><?= h($s['email']) ?></strong><?= $s['name'] ? ' <span class="muted">· ' . h($s['name']) . '</span>' : '' ?></td><td class="right muted small nowrap"><?= h(fdate($s['created_at'], 'j M, H:i')) ?></td></tr><?php endforeach; ?>
  </tbody></table>
  <?php endif; ?>
</section>

<section class="card quick">
  <h2>Quick actions</h2>
  <div class="row-gap">
    <a class="btn ghost" href="<?= url('admin/content.php?type=tours&add=1') ?>"><?= icon('plus', 16) ?> New tour</a>
    <a class="btn ghost" href="<?= url('admin/content.php?type=posts&add=1') ?>"><?= icon('plus', 16) ?> New blog post</a>
    <a class="btn ghost" href="<?= url('admin/content.php?type=gallery') ?>"><?= icon('plus', 16) ?> Upload photos</a>
    <a class="btn ghost" href="<?= url('admin/content.php?type=hero') ?>"><?= icon('image', 16) ?> Change hero images</a>
  </div>
</section>
<?php admin_footer();
