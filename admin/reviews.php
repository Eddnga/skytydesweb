<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
$user = require_login();

if (is_post()) {
    csrf_check();
    $id = (int) input('id');
    $do = input('do');
    if ($id && row('SELECT id FROM reviews WHERE id = ?', [$id])) {
        if ($do === 'approve') {
            q("UPDATE reviews SET status = 'approved', approved_at = ? WHERE id = ?", [now(), $id]);
            flash('ok', 'Review approved — it is now on the website.');
        } elseif ($do === 'reject') {
            q("UPDATE reviews SET status = 'rejected' WHERE id = ?", [$id]);
            flash('ok', 'Review rejected. It will not be shown.');
        } elseif ($do === 'pending') {
            q("UPDATE reviews SET status = 'pending' WHERE id = ?", [$id]);
            flash('ok', 'Review moved back to pending.');
        } elseif ($do === 'delete') {
            q('DELETE FROM reviews WHERE id = ?', [$id]);
            flash('ok', 'Review deleted.');
        } elseif ($do === 'save') {
            $rating = max(1, min(5, (int) input('rating')));
            q('UPDATE reviews SET name = ?, country = ?, rating = ?, title = ?, body = ?, tour_id = ? WHERE id = ?', [
                mb_substr(input('name'), 0, 120), mb_substr(input('country'), 0, 80) ?: null, $rating,
                mb_substr(input('title'), 0, 160) ?: null, mb_substr(input('body'), 0, 4000), ((int) input('tour_id')) ?: null, $id]);
            flash('ok', 'Review updated.');
        }
        audit('review_' . $do, 'reviews', $id);
    }
    $back = input('back', 'admin/reviews.php');
    redirect(str_starts_with($back, 'admin/') ? $back : 'admin/reviews.php');
}

$tab = in_array(input('status'), ['pending', 'approved', 'rejected'], true) ? input('status') : 'pending';
$counts = [];
foreach (['pending', 'approved', 'rejected'] as $s) $counts[$s] = (int) val('SELECT COUNT(*) FROM reviews WHERE status = ?', [$s]);
$editId = (int) input('edit');
$list = rows('SELECT r.*, t.title tour FROM reviews r LEFT JOIN tours t ON t.id = r.tour_id WHERE r.status = ? ORDER BY r.created_at DESC LIMIT 200', [$tab]);
$tours = rows('SELECT id, title FROM tours ORDER BY title');
admin_header('Reviews', 'reviews');
?>
<p class="muted">Reviews sent from the website wait here until you approve them. Only approved reviews are shown to visitors.</p>
<nav class="tabs">
  <?php foreach ($counts as $s => $n): ?><a href="<?= url('admin/reviews.php?status=' . $s) ?>" class="<?= $tab === $s ? 'on' : '' ?>"><?= ucfirst($s) ?> <b><?= $n ?></b></a><?php endforeach; ?>
</nav>
<?php if (!$list): ?><div class="card"><p class="empty">No <?= h($tab) ?> reviews.</p></div><?php endif; ?>
<?php foreach ($list as $r): $back = 'admin/reviews.php?status=' . $tab; ?>
  <article class="card rev big">
    <?php if ($editId === (int) $r['id']): ?>
      <form method="post" class="form">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="do" value="save"><input type="hidden" name="back" value="<?= h($back) ?>">
        <div class="grid2">
          <label>Name<input name="name" value="<?= h($r['name']) ?>" required maxlength="120"></label>
          <label>Country<input name="country" value="<?= h($r['country']) ?>" maxlength="80"></label>
          <label>Rating<select name="rating"><?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>" <?= (int) $r['rating'] === $i ? 'selected' : '' ?>><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option><?php endfor; ?></select></label>
          <label>Tour<select name="tour_id"><option value="">— None —</option><?php foreach ($tours as $t): ?><option value="<?= (int) $t['id'] ?>" <?= (int) $r['tour_id'] === (int) $t['id'] ? 'selected' : '' ?>><?= h($t['title']) ?></option><?php endforeach; ?></select></label>
        </div>
        <label>Title<input name="title" value="<?= h($r['title']) ?>" maxlength="160"></label>
        <label>Review<textarea name="body" rows="5" required><?= h($r['body']) ?></textarea></label>
        <div class="row-gap"><button class="btn sm">Save</button><a class="btn ghost sm" href="<?= url($back) ?>">Cancel</a></div>
      </form>
    <?php else: ?>
      <div class="rev-top">
        <div><strong><?= h($r['name']) ?></strong><?= $r['country'] ? ' <span class="muted">· ' . h($r['country']) . '</span>' : '' ?>
          <div class="stars"><?= str_repeat('★', (int) $r['rating']) . str_repeat('☆', 5 - (int) $r['rating']) ?></div></div>
        <div class="muted small right"><?= h(fdate($r['created_at'], 'j M Y, H:i')) ?><?= $r['tour'] ? '<br>' . h($r['tour']) : '' ?></div>
      </div>
      <?php if ($r['title']): ?><h3><?= h($r['title']) ?></h3><?php endif; ?>
      <p class="pre"><?= h($r['body']) ?></p>
      <div class="row-gap">
        <?php foreach (['approve' => 'Approve', 'reject' => 'Reject', 'pending' => 'Move to pending'] as $do => $lab): if (($do === 'approve' && $tab === 'approved') || ($do === 'reject' && $tab === 'rejected') || ($do === 'pending' && $tab === 'pending')) continue; ?>
          <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="do" value="<?= $do ?>"><input type="hidden" name="back" value="<?= h($back) ?>"><button class="btn sm <?= $do === 'approve' ? '' : 'ghost' ?>"><?= h($lab) ?></button></form>
        <?php endforeach; ?>
        <a class="btn ghost sm" href="<?= url($back . '&edit=' . $r['id']) ?>"><?= icon('edit', 16) ?> Edit</a>
        <form method="post" class="inline" data-confirm="Delete this review permanently?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="do" value="delete"><input type="hidden" name="back" value="<?= h($back) ?>"><button class="btn sm danger"><?= icon('trash', 16) ?></button></form>
      </div>
    <?php endif; ?>
  </article>
<?php endforeach; ?>
<?php admin_footer();
