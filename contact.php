<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
site_header('Contact', ['active' => 'contact', 'seo' => 'contact']);
page_hero('Get in touch', 'Contact us', val('SELECT image FROM destinations WHERE active = 1 ORDER BY sort DESC LIMIT 1') ?: null, 'The fastest way to reach us is WhatsApp — we reply quickly during working hours.');
$tel = fn($p) => 'tel:' . preg_replace('/[^\d+]/', '', $p);
?>
<section class="sec">
  <div class="wrap contact-grid">
    <div class="contact-wa">
      <p class="eyebrow">Fastest reply</p>
      <h2 class="h2">Chat with us on WhatsApp</h2>
      <p>Tell us where you'd like to go, your dates and how many people are travelling. We'll put together a plan and a price.</p>
      <?= wa_btn('Start a WhatsApp chat', 'contact', 'Contact page', 'btn wa lg') ?>
      <p class="muted small"><?= h(setting('hours')) ?></p>
    </div>
    <div class="contact-cards">
      <div class="ccard"><?= sicon('phone') ?><div><span>Call us</span><?php foreach (['phone1', 'phone2'] as $k): if (setting($k)): ?><a href="<?= h($tel(setting($k))) ?>"><?= h(setting($k)) ?></a><?php endif; endforeach; ?></div></div>
      <div class="ccard"><?= sicon('mail') ?><div><span>Email</span><a href="mailto:<?= h(setting('email')) ?>"><?= h(setting('email')) ?></a></div></div>
      <div class="ccard"><?= sicon('pin') ?><div><span>Office</span><b><?= h(setting('address')) ?></b><small><?= h(setting('postal')) ?></small></div></div>
      <div class="ccard follow"><?= sicon('heart') ?><div><span>Follow us</span><?= social_links('socials dark') ?></div></div>
    </div>
  </div>
</section>
<?php site_footer();
