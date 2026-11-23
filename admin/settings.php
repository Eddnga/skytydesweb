<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
$user = require_login();

$groups = [
    'Contact details' => [
        ['whatsapp', 'text', 'WhatsApp number for bookings', 'Country code first, numbers only, e.g. 254723022702. All booking buttons go here.'],
        ['phone1', 'text', 'Phone 1', ''],
        ['phone2', 'text', 'Phone 2', ''],
        ['email', 'text', 'Email', ''],
        ['address', 'text', 'Address', ''],
        ['postal', 'text', 'Postal address', ''],
        ['hours', 'text', 'Opening hours', ''],
    ],
    'Home page' => [
        ['intro_title', 'text', 'Intro heading', ''],
        ['intro_body', 'md', 'Intro text', ''],
        ['why_us', 'lines', 'Why travel with us', 'One reason per line as  Title|Short text'],
        ['ways', 'lines', 'Ways to travel', 'One per line as  Title|Short text|Link (e.g. tours.php)'],
        ['impact_title', 'text', 'Impact heading', 'Wrap a word in *stars* to make it gold italic'],
        ['impact_body', 'textarea', 'Impact text', 'Leave empty to hide this section'],
        ['credentials', 'lines', 'Licences & memberships', 'One per line as  Name|Detail, e.g. KATO Member|Licence no. 1234. Only list ones you really hold. Leave empty to hide.'],
        ['cta_title', 'text', 'Closing banner heading', ''],
        ['cta_body', 'textarea', 'Closing banner text', ''],
    ],
    'About page' => [
        ['about_title', 'text', 'Heading', ''],
        ['about_body', 'md', 'Text', ''],
    ],
    'Social media & SEO' => [
        ['social_instagram', 'text', 'Instagram link', 'Full link, e.g. https://instagram.com/skytydessafaris'],
        ['social_facebook', 'text', 'Facebook link', ''],
        ['social_tiktok', 'text', 'TikTok link', ''],
        ['meta_description', 'textarea', 'Search engine description', 'About 150 characters. Shown under your site name on Google.'],
        ['tagline', 'text', 'Tagline', ''],
    ],
    'Offers & newsletter' => [
        ['offers_intro', 'textarea', 'Offers page intro', ''],
        ['newsletter_title', 'text', 'Newsletter heading', 'Wrap a word in *stars* to make it gold italic. Leave empty to hide the sign-up box.'],
        ['newsletter_body', 'textarea', 'Newsletter text', ''],
    ],
    'Reviews' => [
        ['review_notice', 'textarea', 'Message shown after a visitor submits a review', ''],
    ],
];

if (is_post()) {
    csrf_check();
    foreach ($groups as $fields) foreach ($fields as $f) {
        $v = trim((string) ($_POST[$f[0]] ?? ''));
        if ($f[0] === 'whatsapp') $v = preg_replace('/\D+/', '', $v);
        if (str_starts_with($f[0], 'social_') && $v !== '' && !preg_match('#^https://#i', $v)) {
            flash('error', 'Social links must start with https://');
            redirect('admin/settings.php');
        }
        if ($f[0] === 'email' && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Enter a valid email address.');
            redirect('admin/settings.php');
        }
        set_setting($f[0], mb_substr($v, 0, 20000));
    }
    audit('update', 'settings');
    flash('ok', 'Settings saved.');
    redirect('admin/settings.php');
}
admin_header('Site settings', 'settings');
?>
<form method="post" class="form">
  <?= csrf_field() ?>
  <?php foreach ($groups as $g => $fields): ?>
    <section class="card edit">
      <h2 class="sub"><?= h($g) ?></h2>
      <?php foreach ($fields as $f): $v = setting($f[0]); ?>
        <div class="field">
          <label for="s_<?= h($f[0]) ?>"><?= h($f[2]) ?></label>
          <?php if ($f[1] === 'text'): ?><input id="s_<?= h($f[0]) ?>" name="<?= h($f[0]) ?>" value="<?= h($v) ?>">
          <?php else: ?><textarea id="s_<?= h($f[0]) ?>" name="<?= h($f[0]) ?>" rows="<?= $f[1] === 'md' ? 8 : 4 ?>"><?= h($v) ?></textarea><?php endif; ?>
          <?php if ($f[3]): ?><p class="hint"><?= h($f[3]) ?></p><?php endif; ?>
          <?php if ($f[1] === 'md'): ?><p class="hint">Formatting: <code>## Heading</code>, <code>- bullet</code>, <code>**bold**</code>. Blank line between paragraphs.</p><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endforeach; ?>
  <div class="actions sticky"><button class="btn"><?= icon('check', 18) ?> Save settings</button></div>
</form>
<?php admin_footer();
