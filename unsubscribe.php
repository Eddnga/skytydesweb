<?php
declare(strict_types=1);
/** One-click unsubscribe from newsletter emails (link in every email). */
require __DIR__ . '/includes/bootstrap.php';
$email = strtolower(trim((string) input('e')));
$ok = $email !== '' && hash_equals(unsubscribe_token($email), (string) input('t'));
$done = false;
if ($ok && (is_post() || ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && input('confirm') === '1')) {
    q('DELETE FROM subscribers WHERE email = ?', [$email]);
    $done = true;
    audit('visitor_unsubscribe', 'subscribers', null, ['name' => $email]);
    if (is_post() && !isset($_POST['_web'])) { http_response_code(200); exit('Unsubscribed'); } // one-click from the inbox
}
site_header('Unsubscribe', ['desc' => 'Manage your SkyTydes Safaris newsletter subscription.']);
page_hero('Newsletter', 'SkyTydes Safaris');
?>
<section class="sec"><div class="wrap narrow" style="text-align:center">
<?php if (!$ok): ?>
  <h2 class="h3">This link doesn't look right</h2>
  <p class="muted">Please use the unsubscribe link from your most recent email, or message us on WhatsApp and we'll remove you.</p>
<?php elseif ($done): ?>
  <h2 class="h3">You've been unsubscribed</h2>
  <p class="muted"><?= h($email) ?> won't receive our newsletter any more. You're always welcome back.</p>
  <p><a class="btn" href="<?= url('') ?>">Back to the website</a></p>
<?php else: ?>
  <h2 class="h3">Unsubscribe <?= h($email) ?>?</h2>
  <p class="muted">You'll stop receiving SkyTydes Safaris emails.</p>
  <form method="post"><input type="hidden" name="_web" value="1"><input type="hidden" name="e" value="<?= h($email) ?>"><input type="hidden" name="t" value="<?= h((string) input('t')) ?>"><button class="btn">Yes, unsubscribe me</button></form>
<?php endif; ?>
</div></section>
<?php site_footer();
