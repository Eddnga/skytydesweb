<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
global $CONFIG;
$errors = [];
$old = [];
$sign = fn(string $ts) => hash_hmac('sha256', 'review|' . $ts, (string) $CONFIG['app_key']);

if (is_post()) {
    csrf_check();
    $old = $_POST;
    $ts = (string) ($_POST['ts'] ?? '');
    $age = time() - (int) $ts;
    $bot = ($_POST['website'] ?? '') !== '' || !hash_equals($sign($ts), (string) ($_POST['sig'] ?? '')) || $age < 4 || $age > 7200;
    $name = mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 120);
    $body = mb_substr(trim((string) ($_POST['body'] ?? '')), 0, 3000);
    $rating = (int) ($_POST['rating'] ?? 0);
    if ($name === '') $errors[] = 'Please add your name.';
    if (mb_strlen($body) < 20) $errors[] = 'Please write at least a sentence or two about your trip.';
    if ($rating < 1 || $rating > 5) $errors[] = 'Please choose a star rating.';
    if (preg_match_all('#https?://#i', $body) > 1) $errors[] = 'Please remove the links from your review.';
    if (!$errors && !$bot && !throttle('review:' . client_ip(), 3, 3600)) $errors[] = 'You have sent several reviews recently. Please try again later.';
    if (!$errors) {
        if (!$bot) {
            $tid = (int) ($_POST['tour_id'] ?? 0);
            insert('reviews', ['name' => $name, 'country' => mb_substr(trim((string) ($_POST['country'] ?? '')), 0, 80) ?: null, 'rating' => $rating,
                'title' => mb_substr(trim((string) ($_POST['title'] ?? '')), 0, 160) ?: null, 'body' => $body,
                'tour_id' => $tid && val('SELECT id FROM tours WHERE id = ?', [$tid]) ? $tid : null,
                'status' => 'pending', 'ip' => client_ip(), 'created_at' => now()]);
        }
        flash('review', setting('review_notice', 'Thank you! Your review will appear once approved.'));
        redirect('reviews.php#write');
    }
}

$reviews = rows("SELECT r.*, t.title tour FROM reviews r LEFT JOIN tours t ON t.id = r.tour_id WHERE r.status = 'approved' ORDER BY r.approved_at DESC LIMIT 60");
$n = count($reviews);
$avg = $n ? round(array_sum(array_column($reviews, 'rating')) / $n, 1) : 0;
$tours = rows('SELECT id, title FROM tours WHERE active = 1 ORDER BY title');
$ts = (string) time();
site_header('Reviews', ['active' => 'reviews', 'desc' => 'Read reviews from SkyTydes Safaris travellers and share your own experience.']);
page_hero('Guest reviews', 'In their words', val('SELECT image FROM gallery WHERE active = 1 ORDER BY sort LIMIT 1 OFFSET 1') ?: null);
?>
<section class="sec">
  <div class="wrap">
    <?php if ($n): ?>
      <p class="rating-sum big"><?= stars((int) round($avg)) ?> <b><?= h(number_format($avg, 1)) ?></b> average from <?= $n ?> review<?= $n === 1 ? '' : 's' ?></p>
      <div class="rgrid"><?php foreach ($reviews as $r) echo review_card($r); ?></div>
    <?php else: ?>
      <p class="muted center">No reviews yet — be the first to share your SkyTydes experience.</p>
    <?php endif; ?>
  </div>
</section>
<section class="sec tint" id="write">
  <div class="wrap narrow">
    <div class="sec-head center"><div><p class="eyebrow">Travelled with us?</p><h2 class="h2">Write a review</h2><p class="muted">Reviews are checked by our team before they appear on the site.</p></div></div>
    <?php if ($m = flash('review')): ?><div class="alert ok" role="status"><?= h($m) ?></div><?php endif; ?>
    <?php foreach ($errors as $e): ?><div class="alert error" role="alert"><?= h($e) ?></div><?php endforeach; ?>
    <form method="post" action="<?= url('reviews.php#write') ?>" class="rform">
      <?= csrf_field() ?>
      <input type="hidden" name="ts" value="<?= h($ts) ?>"><input type="hidden" name="sig" value="<?= h($sign($ts)) ?>">
      <div class="hp" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
      <fieldset class="rate"><legend>Your rating</legend>
        <?php for ($i = 5; $i >= 1; $i--): ?><input type="radio" id="r<?= $i ?>" name="rating" value="<?= $i ?>" <?= (int) ($old['rating'] ?? 0) === $i ? 'checked' : '' ?> required><label for="r<?= $i ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">★</label><?php endfor; ?>
      </fieldset>
      <div class="sheet-grid two">
        <label>Your name<input name="name" required maxlength="120" value="<?= h($old['name'] ?? '') ?>" autocomplete="name"></label>
        <label>Country <span class="opt">(optional)</span><input name="country" maxlength="80" value="<?= h($old['country'] ?? '') ?>" autocomplete="country-name"></label>
      </div>
      <?php if ($tours): ?><label>Which trip? <span class="opt">(optional)</span><select name="tour_id"><option value="">— Select a trip —</option><?php foreach ($tours as $t): ?><option value="<?= (int) $t['id'] ?>" <?= (int) ($old['tour_id'] ?? 0) === (int) $t['id'] ? 'selected' : '' ?>><?= h($t['title']) ?></option><?php endforeach; ?></select></label><?php endif; ?>
      <label>Title <span class="opt">(optional)</span><input name="title" maxlength="160" value="<?= h($old['title'] ?? '') ?>" placeholder="e.g. The best weekend ever"></label>
      <label>Your review<textarea name="body" rows="5" required minlength="20" maxlength="3000" placeholder="Tell others about your trip…"><?= h($old['body'] ?? '') ?></textarea></label>
      <button class="btn dark lg">Submit review</button>
    </form>
  </div>
</section>
<?php site_footer();
