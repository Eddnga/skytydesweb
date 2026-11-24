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
        ['map_address', 'text', 'Address for the Google Map', 'What Google Maps should search for, e.g. Mobil Plaza, Muthaiga Road, Nairobi. Leave empty to use the address above.'],
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
        ['social_x', 'text', 'X (Twitter) link', ''],
        ['social_youtube', 'text', 'YouTube link', ''],
        ['social_linkedin', 'text', 'LinkedIn link', 'Facebook, Instagram, TikTok and YouTube icons always show and become clickable once a link is added. X and LinkedIn show only when linked.'],
        ['meta_description', 'textarea', 'Search engine description', 'About 150 characters. Shown under your site name on Google.'],
        ['tagline', 'text', 'Tagline', ''],
    ],
    'SEO & Google' => [
        ['ga_id', 'text', 'Google Analytics Measurement ID', 'From Google Analytics → Admin → Data streams → your website, e.g. G-AB12CD34EF. Your own visits while logged in to the dashboard are not counted.'],
        ['google_verification', 'text', 'Google Search Console verification', 'In Search Console choose "HTML tag" and paste the whole tag or just the code here.'],
        ['bing_verification', 'text', 'Bing Webmaster verification', 'Optional. Paste the msvalidate.01 tag or code.'],
        ['og_image', 'image', 'Default share image', 'Shown when a page is shared on WhatsApp, Facebook or X and has no photo of its own. Use a landscape photo, 1200×630 px works best.'],
        ['seo_title_home', 'text', 'Home page title on Google', 'Leave empty for: Kenya Safaris & Tours from Nairobi | SkyTydes Safaris'],
        ['seo_title_tours', 'text', 'Tours page title on Google', 'Leave empty for: Kenya Safari Packages & Tours | SkyTydes Safaris'],
        ['seo_desc_tours', 'textarea', 'Tours page description on Google', 'About 150 characters. Leave empty to use the built-in one.'],
        ['seo_title_destinations', 'text', 'Destinations page title on Google', ''],
        ['seo_desc_destinations', 'textarea', 'Destinations page description on Google', ''],
        ['seo_title_about', 'text', 'About page title on Google', ''],
        ['seo_desc_about', 'textarea', 'About page description on Google', ''],
        ['seo_title_contact', 'text', 'Contact page title on Google', ''],
        ['seo_desc_contact', 'textarea', 'Contact page description on Google', ''],
    ],
    'Offers & newsletter' => [
        ['offers_intro', 'textarea', 'Offers page intro', ''],
        ['newsletter_title', 'text', 'Newsletter heading', 'Wrap a word in *stars* to make it gold italic. Leave empty to hide the sign-up box.'],
        ['newsletter_body', 'textarea', 'Newsletter text', ''],
    ],
    'Reviews' => [
        ['google_reviews_url', 'text', 'Google reviews link', 'Your Google Business Profile reviews page. Shows a "See us on Google" button.'],
        ['google_write_url', 'text', 'Google "write a review" link', 'From Google Business Profile → Ask for reviews. Shows a "Review us on Google" button.'],
        ['tripadvisor_url', 'text', 'TripAdvisor link', 'Your TripAdvisor listing.'],
        ['facebook_reviews_url', 'text', 'Facebook reviews link', ''],
        ['review_notice', 'textarea', 'Message shown after a visitor submits a review', ''],
    ],
    'Review platform logos' => [
        ['logo_google', 'image', 'Google logo', 'Upload the official Google "G" logo (PNG with a transparent background). It appears on Google review cards and buttons.'],
        ['logo_tripadvisor', 'image', 'Tripadvisor logo', 'Upload the official Tripadvisor logo or owl icon (PNG).'],
        ['logo_facebook', 'image', 'Facebook logo', 'Upload the official Facebook "f" logo (PNG).'],
    ],
];

if (is_post()) {
    csrf_check();
    $changed = [];
    foreach ($groups as $fields) foreach ($fields as $f) {
        if ($f[1] === 'image') {
            if (!empty($_FILES[$f[0]]) && $_FILES[$f[0]]['error'] !== UPLOAD_ERR_NO_FILE) {
                try {
                    $new = store_image($_FILES[$f[0]]);
                    delete_image(setting($f[0]) ?: null);
                    set_setting($f[0], $new);
                    $changed[] = $f[2];
                } catch (RuntimeException $e) {
                    flash('error', $f[2] . ': ' . $e->getMessage());
                    redirect('admin/settings.php');
                }
            } elseif (isset($_POST[$f[0] . '_remove'])) {
                delete_image(setting($f[0]) ?: null);
                set_setting($f[0], '');
                $changed[] = $f[2] . ' (removed)';
            }
            continue;
        }
        $v = trim((string) ($_POST[$f[0]] ?? ''));
        if ($f[1] === 'md') $v = sanitize_html($v);
        if ($f[0] === 'whatsapp') $v = preg_replace('/\D+/', '', $v);
        if ((str_starts_with($f[0], 'social_') || str_ends_with($f[0], '_url')) && $v !== '' && !preg_match('#^https://#i', $v)) {
            flash('error', 'Social links must start with https://');
            redirect('admin/settings.php');
        }
        if ($f[0] === 'email' && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Enter a valid email address.');
            redirect('admin/settings.php');
        }
        $v = mb_substr($v, 0, 20000);
        if ($v !== setting($f[0])) $changed[] = $f[2];
        set_setting($f[0], $v);
    }
    audit('update', 'settings', null, ['changed' => $changed ?: ['nothing (saved again)']]);
    flash('ok', 'Settings saved.');
    redirect('admin/settings.php');
}
admin_header('Site settings', 'settings');
?>
<form method="post" class="form" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <?php foreach ($groups as $g => $fields): ?>
    <section class="card edit">
      <h2 class="sub"><?= h($g) ?></h2>
      <?php foreach ($fields as $f): $v = setting($f[0]); ?>
        <div class="field">
          <label for="s_<?= h($f[0]) ?>"><?= h($f[2]) ?></label>
          <?php if ($f[1] === 'image'): ?>
            <div class="imgfield logo-field">
              <?php if ($v): ?><img src="<?= h(upload_url($v)) ?>" alt="" class="preview logo-prev" data-preview="s_<?= h($f[0]) ?>"><?php else: ?><span class="preview logo-prev empty" data-preview="s_<?= h($f[0]) ?>">No logo yet</span><?php endif; ?>
              <div><input id="s_<?= h($f[0]) ?>" type="file" name="<?= h($f[0]) ?>" accept="image/png,image/webp,image/jpeg">
                <?php if ($v): ?><label class="check small"><input type="checkbox" name="<?= h($f[0]) ?>_remove" value="1"> Remove logo</label><?php endif; ?></div>
            </div>
          <?php elseif ($f[1] === 'text'): ?><input id="s_<?= h($f[0]) ?>" name="<?= h($f[0]) ?>" value="<?= h($v) ?>">
          <?php elseif ($f[1] === 'md'): ?><textarea id="s_<?= h($f[0]) ?>" name="<?= h($f[0]) ?>" rows="8" class="rich" data-rich><?= h(editor_value($v)) ?></textarea>
          <?php else: ?><textarea id="s_<?= h($f[0]) ?>" name="<?= h($f[0]) ?>" rows="4"><?= h($v) ?></textarea><?php endif; ?>
          <?php if ($f[3]): ?><p class="hint"><?= h($f[3]) ?></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endforeach; ?>
  <div class="actions sticky"><button class="btn"><?= icon('check', 18) ?> Save settings</button></div>
</form>
<?php admin_footer();
