<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$offers = active_offers();
site_header('Special offers', ['active' => 'offers', 'seo' => 'offers', 'desc' => 'Current special offers on Kenya safaris and coast holidays from SkyTydes Safaris.']);
page_hero('Current *special offers*', 'Limited time', $offers[0]['image'] ?? (val('SELECT image FROM hero_slides WHERE active = 1 ORDER BY sort LIMIT 1') ?: null), setting('offers_intro'));
?>
<section class="sec">
  <div class="wrap">
    <?php if ($offers): ?>
      <div class="ogrid"><?php foreach ($offers as $o) echo offer_card($o, true); ?></div>
      <p class="note"><strong>Please note:</strong> offers apply to new bookings only, depend on your travel dates and availability, and cannot be combined unless stated. Message us on WhatsApp and we'll confirm what applies to your trip.</p>
    <?php else: ?>
      <div class="empty-state"><h2 class="h2">New offers are <em>on the way</em></h2><p class="muted">There are no special offers right now. Join our newsletter below to hear first, or ask us for the best price on your dates.</p><?= wa_btn('Ask for the best price', 'general', 'Best price request') ?></div>
    <?php endif; ?>
  </div>
</section>
<?php site_footer();
