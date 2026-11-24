<?php
declare(strict_types=1);

function sicon(string $n, int $s = 22): string
{
    $p = [
        'wa' => '<path d="M20.5 3.5A11 11 0 0 0 3.2 16.7L2 22l5.4-1.4A11 11 0 1 0 20.5 3.5zM12 20a8 8 0 0 1-4.1-1.1l-.3-.2-3.2.8.9-3.1-.2-.3A8 8 0 1 1 12 20zm4.4-6c-.2-.1-1.4-.7-1.6-.8-.2-.1-.4-.1-.5.1l-.7.9c-.1.2-.3.2-.5.1a6.6 6.6 0 0 1-3.3-2.9c-.2-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.7-1.7c-.2-.5-.4-.4-.5-.4h-.5a.9.9 0 0 0-.7.3 2.8 2.8 0 0 0-.9 2.1 4.9 4.9 0 0 0 1 2.6 11 11 0 0 0 4.3 3.8c1.6.7 2.2.7 3 .6a2.5 2.5 0 0 0 1.7-1.2 2 2 0 0 0 .1-1.2c0-.1-.2-.2-.4-.3z" fill="currentColor" stroke="none"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'pin' => '<path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>',
        'tag' => '<path d="M3 12V4h8l10 10-8 8L3 12z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3.5 6"/>',
        'check' => '<path d="M5 12.5l4.5 4.5L19 7"/>',
        'x' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'phone' => '<path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A17 17 0 0 1 3 5a2 2 0 0 1 2-2z"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'home' => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'map' => '<path d="M9 4L3 6v14l6-2 6 2 6-2V4l-6 2-6-2z"/><path d="M9 4v14M15 6v14"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'compass' => '<circle cx="12" cy="12" r="9"/><path d="M15.5 8.5l-2 5-5 2 2-5 5-2z"/>',
        'bed' => '<path d="M3 18V7M3 14h18v4M21 14v-2a3 3 0 0 0-3-3h-7v5"/><circle cx="7" cy="11" r="2"/>',
        'car' => '<path d="M5 16V11l2-5h10l2 5v5"/><path d="M3 16h18v3H3z"/><circle cx="7.5" cy="16" r="1.5"/><circle cx="16.5" cy="16" r="1.5"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'ticket' => '<path d="M3 8a2 2 0 0 0 2-2h14a2 2 0 0 0 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 0-2 2H5a2 2 0 0 0-2-2v-2a2 2 0 0 0 0-4z"/><path d="M14 6v12" stroke-dasharray="2 2"/>',
        'plane' => '<path d="M10 14l-7-3 2-2 7 1 5-5a2 2 0 0 1 3 3l-5 5 1 7-2 2-3-7-3 3v3l-2 1-1-4-4-1 1-2h3z"/>',
        'camera' => '<path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>',
        'heart' => '<path d="M12 20s-8-5-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 9c0 6-8 11-8 11z"/>',
        'star' => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9L12 3z"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'meal' => '<path d="M7 3v8M5 3v5a2 2 0 0 0 4 0V3M7 11v10M17 3c-2 0-3 2-3 5s1 4 3 4v9"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'x' => '<path d="M4 4l16 16M20 4L4 20" stroke-width="2"/>',
        'youtube' => '<rect x="2.5" y="5.5" width="19" height="13" rx="4"/><path d="M10 9.5v5l4.5-2.5z" fill="currentColor"/>',
        'linkedin' => '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 10v7M8 7v.01M12 17v-4a2 2 0 0 1 4 0v4M12 10v7"/>',
        'file' => '<path d="M14 3H6v18h12V7z"/><path d="M14 3v4h4M9 13h6M9 17h6"/>',
        'download' => '<path d="M12 4v11M7 10l5 5 5-5M5 20h14"/>',
        'insta' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/>',
        'fb' => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8z"/>',
        'tiktok' => '<path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5M14 3a5 5 0 0 0 5 5"/>',
    ];
    return '<svg class="si" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$n] ?? $p['star']) . '</svg>';
}

function stars(int $n): string
{
    $n = max(0, min(5, $n));
    return '<span class="stars" role="img" aria-label="' . $n . ' out of 5 stars">' . str_repeat('★', $n) . '<span class="off">' . str_repeat('★', 5 - $n) . '</span></span>';
}

/** A button that opens the WhatsApp booking sheet (works without JavaScript too). */
function wa_btn(string $label, string $source, ?string $item = '', string $class = 'btn wa', int $id = 0): string
{
    $item = (string) $item;
    $href = url('wa.php?src=' . rawurlencode($source) . ($id ? '&id=' . $id : '') . ($item !== '' && !$id ? '&item=' . rawurlencode($item) : ''));
    return '<a class="' . h($class) . '" href="' . h($href) . '" data-wa data-src="' . h($source) . '" data-id="' . $id . '" data-item="' . h($item) . '" rel="nofollow">' . sicon('wa', 20) . '<span>' . h($label) . '</span></a>';
}

function site_header(string $title, array $o = []): void
{
    pretty_redirect();
    $active = $o['active'] ?? '';
    $desc = $o['desc'] ?? setting('meta_description');
    $img = $o['image'] ?? null;
    $solid = !empty($o['solid']);
    $GLOBALS['__page_title'] = $title === '' ? 'Home' : $title;
    $full = $title === '' ? setting('site_name', 'SkyTydes Safaris') . ' · ' . setting('tagline') : $title . ' | ' . setting('site_name', 'SkyTydes Safaris');
    if (!empty($o['seo'])) {
        [$st, $sd] = page_seo($o['seo']);
        if ($st !== '') $full = $st;
        if ($sd !== '') $desc = $sd;
    }
    if (!empty($o['seo_title'])) $full = $o['seo_title'];
    $shareImg = abs_image($img ?: default_share_image());
    $schema = $o['schema'] ?? [];
    if ($active === 'home') array_unshift($schema, schema_business(), schema_website());
    if (in_array($o['seo'] ?? '', ['about', 'contact'], true)) $schema[] = schema_business();
    $gv = verification_code('google_verification');
    $bv = verification_code('bing_verification');
    $nav = [['', 'Home', 'home', ''], ['tours.php', 'Tours', 'tours', 'tours'], ['destinations.php', 'Destinations', 'destinations', 'dest'], ['offers.php', 'Offers', 'offers', ''], ['gallery.php', 'Gallery', 'gallery', ''], ['blog.php', 'Journal', 'blog', ''], ['reviews.php', 'Reviews', 'reviews', ''], ['about.php', 'About', 'about', 'about'], ['contact.php', 'Contact', 'contact', '']];
    $megaTours = rows('SELECT t.title, t.slug, t.duration_days, t.price_from, t.currency, d.name dest FROM tours t LEFT JOIN destinations d ON d.id = t.destination_id WHERE t.active = 1 ORDER BY t.featured DESC, t.sort, t.id DESC LIMIT 6');
    $megaDest = rows('SELECT name, slug, tagline, image FROM destinations WHERE active = 1 ORDER BY sort, name LIMIT 6');
    $hasOffers = (bool) count(active_offers(1));
    $canon = abs_url(ltrim(str_replace(BASE_PATH, '', strtok($_SERVER['REQUEST_URI'] ?? '/', '#')), '/'));
    ?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($full) ?></title>
<meta name="description" content="<?= h(excerpt($desc, 165)) ?>">
<link rel="canonical" href="<?= h($canon) ?>">
<meta name="robots" content="index,follow,max-image-preview:large">
<meta property="og:type" content="<?= h($o['og_type'] ?? 'website') ?>"><meta property="og:site_name" content="<?= h(setting('site_name', 'SkyTydes Safaris')) ?>"><meta property="og:locale" content="en_KE">
<meta property="og:title" content="<?= h($full) ?>"><meta property="og:description" content="<?= h(excerpt($desc, 165)) ?>">
<meta property="og:url" content="<?= h($canon) ?>">
<?php if ($shareImg): ?><meta property="og:image" content="<?= h($shareImg) ?>"><meta property="og:image:alt" content="<?= h($title ?: setting('site_name', 'SkyTydes Safaris')) ?>"><?php endif; ?>
<meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="<?= h($full) ?>"><meta name="twitter:description" content="<?= h(excerpt($desc, 165)) ?>"><?php if ($shareImg): ?><meta name="twitter:image" content="<?= h($shareImg) ?>"><?php endif; ?>
<?php if ($gv): ?><meta name="google-site-verification" content="<?= h($gv) ?>"><?php endif; ?>
<?php if ($bv): ?><meta name="msvalidate.01" content="<?= h($bv) ?>"><?php endif; ?>
<?php foreach ($schema as $sc) echo json_ld($sc); ?>
<?php if (($ga = ga_id()) && !current_user()): ?><script async src="https://www.googletagmanager.com/gtag/js?id=<?= h($ga) ?>"></script><script src="<?= asset('js/ga.js') ?>" data-ga="<?= h($ga) ?>" data-page-type="<?= h(ga_page_type()) ?>"<?php if (!empty($o['ga_item'])): ?> data-item-name="<?= h($o['ga_item']['name'] ?? '') ?>" data-item-category="<?= h($o['ga_item']['category'] ?? '') ?>" data-item-price="<?= h((string) ($o['ga_item']['price'] ?? '')) ?>" data-item-currency="<?= h($o['ga_item']['currency'] ?? 'KES') ?>"<?php endif; ?>></script><?php endif; ?>
<meta name="theme-color" content="#023F0A">
<link rel="icon" href="<?= asset('img/favicon-32.png') ?>" type="image/png" sizes="32x32">
<link rel="icon" href="<?= asset('img/favicon-192.png') ?>" type="image/png" sizes="192x192">
<link rel="apple-touch-icon" href="<?= asset('img/apple-touch-icon.png') ?>">
<link rel="manifest" href="<?= asset('img/site.webmanifest') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Poppins:wght@300;400;500;600&display=swap">
<link rel="stylesheet" href="<?= asset('css/site.css') ?>">
<?= $o['head'] ?? '' ?>
</head>
<body class="<?= $solid ? 'solid' : '' ?>">
<a class="skip" href="#main">Skip to content</a>
<header class="nav" id="top">
  <div class="wrap nav-in">
    <a class="brand" href="<?= url('') ?>" aria-label="SkyTydes Safaris home">
      <img class="logo-light" src="<?= asset('img/logo-dark.svg') ?>" alt="SkyTydes Safaris" width="150" height="77">
      <img class="logo-dark" src="<?= asset('img/logo-color.svg') ?>" alt="" width="150" height="77" aria-hidden="true">
    </a>
    <nav class="links" id="menu" aria-label="Main">
      <?php foreach ($nav as $n): ?>
        <?php if ($n[3] === ''): ?>
          <a href="<?= url($n[0]) ?>" class="<?= $active === $n[2] ? 'on' : '' ?>"><?= h($n[1]) ?><?php if ($n[2] === 'offers' && $hasOffers): ?><i class="dot" aria-label="new offers"></i><?php endif; ?></a>
        <?php else: ?>
          <div class="has-mega">
            <a href="<?= url($n[0]) ?>" class="<?= $active === $n[2] ? 'on' : '' ?>"><?= h($n[1]) ?></a>
            <div class="mega">
              <?php if ($n[3] === 'tours'): ?>
                <div class="mega-list">
                  <p class="mega-h">Popular trips</p>
                  <?php foreach ($megaTours as $mt): $mp = money($mt['price_from'], $mt['currency']); ?>
                    <a href="<?= url('tour.php?slug=' . rawurlencode($mt['slug'])) ?>"><b><?= h($mt['title']) ?></b><small><?= $mt['duration_days'] ? (int) $mt['duration_days'] . ' days' : '' ?><?= $mt['dest'] ? ' · ' . h($mt['dest']) : '' ?> · <?= $mp ? 'From ' . h($mp) : 'Price on request' ?></small></a>
                  <?php endforeach; ?>
                  <a class="mega-all" href="<?= url('tours.php') ?>">View all tours →</a>
                </div>
                <a class="mega-feature" href="<?= url('offers.php') ?>"><span class="eyebrow">Special offers</span><b>Limited-time deals on our favourite trips</b><em>See offers →</em></a>
              <?php elseif ($n[3] === 'dest'): ?>
                <div class="mega-grid">
                  <?php foreach ($megaDest as $md): ?>
                    <a href="<?= url('destination.php?slug=' . rawurlencode($md['slug'])) ?>"><img src="<?= h(upload_url($md['image'], 'sm')) ?>" alt="<?= h($md['name']) ?>, Kenya" loading="lazy"><b><?= h($md['name']) ?></b><small><?= h($md['tagline']) ?></small></a>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="mega-list narrow">
                  <a href="<?= url('about.php') ?>"><b>Our story</b><small>Who we are and how we plan</small></a>
                  <a href="<?= url('about.php#hosts') ?>"><b>Your hosts</b><small>Meet the people behind SkyTydes</small></a>
                  <a href="<?= url('services.php') ?>"><b>Services</b><small>Everything we can arrange for you</small></a>
                  <?php foreach (menu_sections() as $ms): ?><a href="<?= url('section.php?type=' . $ms['slug']) ?>"><b><?= h($ms['label']) ?></b><small><?= h(excerpt((string) ($ms['intro'] ?: 'Explore ' . mb_strtolower($ms['label'])), 48)) ?></small></a><?php endforeach; ?>
                  <a href="<?= url('documents.php') ?>"><b>Brochures &amp; rates</b><small>Company profile and downloads</small></a>
                  <a href="<?= url('faqs.php') ?>"><b>FAQs</b><small>Good to know before you travel</small></a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
      <?= wa_btn('Plan my safari', 'general', 'General enquiry', 'btn wa sm menu-cta') ?>
    </nav>
    <?= wa_btn('Plan my safari', 'general', 'General enquiry', 'btn gold sm nav-cta') ?>
    <button class="burger" aria-controls="drawer" aria-expanded="false" aria-label="Open menu"><?= sicon('menu', 26) ?></button>
  </div>
</header>
<div class="drawer-shade" data-close-menu></div>
<aside class="drawer" id="drawer" aria-label="Menu" aria-hidden="true">
  <div class="drawer-head">
    <a href="<?= url('') ?>"><img src="<?= asset('img/logo-white.svg') ?>" alt="SkyTydes Safaris" width="120" height="62"></a>
    <button class="drawer-x" data-close-menu aria-label="Close menu"><?= sicon('x', 22) ?></button>
  </div>
  <nav class="drawer-nav">
    <?php $dl = [['', 'Home', 'home', 'home'], ['tours.php', 'Tours', 'tours', 'map'], ['destinations.php', 'Destinations', 'destinations', 'compass'], ['offers.php', 'Special offers', 'offers', 'tag'], ['gallery.php', 'Gallery', 'gallery', 'camera'], ['blog.php', 'Journal', 'blog', 'file'], ['reviews.php', 'Reviews', 'reviews', 'star'], ['about.php', 'About us', 'about', 'users'], ['services.php', 'Services', 'services', 'sun'], ['documents.php', 'Brochures &amp; rates', 'documents', 'download'], ['faqs.php', 'FAQs', 'faqs', 'check'], ['contact.php', 'Contact', 'contact', 'phone']];
    foreach (menu_sections() as $ms) array_splice($dl, -3, 0, [['section.php?type=' . $ms['slug'], h($ms['label']), 'sec_' . $ms['slug'], 'grid']]);
    foreach ($dl as $d): ?>
      <a href="<?= url($d[0]) ?>" class="<?= $active === $d[2] ? 'on' : '' ?>"><?= sicon($d[3], 18) ?><span><?= $d[1] ?></span><?php if ($d[2] === 'offers' && $hasOffers): ?><i class="dot" aria-label="new offers"></i><?php endif; ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="drawer-foot"><?= wa_btn('Plan my safari', 'general', 'General enquiry', 'btn wa') ?></div>
</aside>
<main id="main">
<?php
}

function page_hero(?string $title, ?string $eyebrow = '', ?string $img = null, ?string $sub = '', array $crumbs = []): void
{
    $title = (string) $title; $eyebrow = (string) $eyebrow; $sub = (string) $sub;
    ?>
<section class="phero">
  <div class="phero-bg" style="background-image:url('<?= h(upload_url($img)) ?>')"></div>
  <div class="phero-shade"></div>
  <div class="wrap phero-in">
    <?= json_ld(schema_breadcrumbs($crumbs, strip_tags(str_replace('*', '', $title)))) ?>
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= url('') ?>">Home</a><?php foreach ($crumbs as $c): ?> <span>/</span> <a href="<?= url($c[1]) ?>"><?= h($c[0]) ?></a><?php endforeach; ?> <span>/</span> <span aria-current="page"><?= h(strip_tags(str_replace('*', '', $title))) ?></span></nav>
    <?php if ($eyebrow): ?><p class="eyebrow"><?= h($eyebrow) ?></p><?php endif; ?>
    <h1><?= hl($title) ?></h1>
    <?php if ($sub): ?><p class="lead"><?= h($sub) ?></p><?php endif; ?>
  </div>
</section>
<?php
}

function site_footer(): void
{
    $s = settings();
    $dests = rows('SELECT name, slug FROM destinations WHERE active = 1 ORDER BY sort, name LIMIT 6');
    ?>
</main>
<section class="cta-band">
  <div class="wrap cta-in">
    <div>
      <p class="eyebrow">Ready?</p>
      <h2><?= h(setting('cta_title', 'Ready to make it happen?')) ?></h2>
      <p><?= h(setting('cta_body')) ?></p>
    </div>
    <?= wa_btn('Plan on WhatsApp', 'general', 'Footer — plan my trip', 'btn wa lg') ?>
  </div>
</section>
<footer class="foot">
  <?php if (setting('newsletter_title')): $back = ltrim(str_replace(BASE_PATH, '', strtok($_SERVER['REQUEST_URI'] ?? '/', '#')), '/'); ?>
  <div class="wrap join" id="join">
    <div><h2><?= hl(setting('newsletter_title')) ?></h2><p><?= h(setting('newsletter_body')) ?></p></div>
    <form method="post" action="<?= url('subscribe.php') ?>" class="join-form">
      <?= csrf_field() ?><input type="hidden" name="back" value="<?= h($back) ?>">
      <div class="hp" aria-hidden="true"><input name="company" tabindex="-1" autocomplete="off"></div>
      <label class="sr" for="joinEmail">Email address</label>
      <input id="joinEmail" type="email" name="email" required placeholder="Your email address" autocomplete="email">
      <button class="btn gold">Join</button>
      <?php if ($m = flash('join_ok')): ?><p class="join-msg ok" role="status"><?= h($m) ?></p><?php endif; ?>
      <?php if ($m = flash('join_error')): ?><p class="join-msg err" role="alert"><?= h($m) ?></p><?php endif; ?>
    </form>
  </div>
  <?php endif; ?>
  <div class="wrap foot-grid">
    <div class="foot-brand">
      <img src="<?= asset('img/logo-dark.svg') ?>" alt="SkyTydes Safaris" width="190" height="98" loading="lazy">
      <p><?= h(setting('meta_description')) ?></p>
      <p class="follow-h">Follow the tides</p>
      <?= social_links() ?>
    </div>
    <div><h3>Explore</h3><ul><li><a href="<?= url('tours.php') ?>">Tour packages</a></li><li><a href="<?= url('destinations.php') ?>">Destinations</a></li><li><a href="<?= url('offers.php') ?>">Special offers</a></li><li><a href="<?= url('services.php') ?>">Services</a></li><li><a href="<?= url('gallery.php') ?>">Gallery</a></li><li><a href="<?= url('blog.php') ?>">Journal</a></li><li><a href="<?= url('documents.php') ?>">Brochures &amp; rates</a></li><li><a href="<?= url('faqs.php') ?>">FAQs</a></li></ul></div>
    <div><h3>Destinations</h3><ul><?php foreach ($dests as $d): ?><li><a href="<?= url('destination.php?slug=' . rawurlencode($d['slug'])) ?>"><?= h($d['name']) ?></a></li><?php endforeach; ?></ul></div>
    <div><h3>Contact</h3>
      <ul class="contact-list">
        <li><?= sicon('phone', 18) ?><span><?= h(setting('phone1')) ?><?= setting('phone2') ? '<br>' . h(setting('phone2')) : '' ?></span></li>
        <li><?= sicon('mail', 18) ?><span><?= h(setting('email')) ?></span></li>
        <li><?= sicon('pin', 18) ?><span><?= h(setting('postal')) ?><br><?= h(setting('address')) ?></span></li>
      </ul>
    </div>
  </div>
  <div class="wrap foot-base">
    <span>© <?= date('Y') ?> <?= h(setting('site_name', 'SkyTydes Safaris')) ?>. All rights reserved.</span>
    <span class="tag"><?= h(setting('tagline')) ?></span>
  </div>
</footer>

<a class="wa-float" href="<?= h(url('wa.php?src=general&item=' . rawurlencode('Floating button'))) ?>" data-wa data-src="general" data-id="0" data-item="General enquiry" aria-label="Chat with us on WhatsApp" rel="nofollow"><?= sicon('wa', 30) ?></a>

<dialog class="sheet" id="waSheet" aria-labelledby="waTitle">
  <form method="post" action="<?= url('wa.php') ?>" class="sheet-in" id="waForm">
    <?= csrf_field() ?>
    <input type="hidden" name="src" value="general"><input type="hidden" name="id" value="0"><input type="hidden" name="item" value="">
    <input type="hidden" name="page" value="">
    <button type="button" class="sheet-x" data-close aria-label="Close"><?= sicon('x', 22) ?></button>
    <p class="eyebrow">Book on WhatsApp</p>
    <h2 id="waTitle">Let's plan your trip</h2>
    <p class="sheet-item" id="waItem"></p>
    <div class="sheet-grid">
      <label>Your name<input name="name" maxlength="80" autocomplete="name" placeholder="e.g. Wanjiru"></label>
      <label>Travel date<input name="travel_date" maxlength="40" placeholder="e.g. 12 Dec or December"></label>
      <label>Travellers<input name="travellers" type="number" min="1" max="200" placeholder="2"></label>
    </div>
    <label>Anything else? <span class="opt">(optional)</span><textarea name="message" rows="2" maxlength="500" placeholder="Budget, special requests, kids…"></textarea></label>
    <button class="btn wa block lg"><?= sicon('wa', 20) ?> Continue to WhatsApp</button>
    <p class="fine">You'll be taken to WhatsApp with your message ready to send. Nothing is booked until we confirm.</p>
  </form>
</dialog>
<script src="<?= asset('js/site.js') ?>" defer></script>
</body></html>
<?php
    track_view($GLOBALS['__page_title'] ?? '');
}

function tour_card(array $t): string
{
    $price = money($t['price_from'] ?? null, $t['currency'] ?? 'KES');
    ob_start(); ?>
<article class="tcard">
  <a href="<?= url('tour.php?slug=' . rawurlencode($t['slug'])) ?>" class="tcard-img"><img src="<?= h(upload_url($t['image'], 'sm')) ?>" alt="<?= h($t['title']) ?>" loading="lazy">
    <?php if (!empty($t['duration_days'])): ?><span class="chip"><?= sicon('clock', 16) ?><?= (int) $t['duration_days'] ?> day<?= $t['duration_days'] > 1 ? 's' : '' ?></span><?php endif; ?></a>
  <div class="tcard-body">
    <?php if (!empty($t['dest_name'])): ?><p class="kicker"><?= sicon('pin', 14) ?><?= h($t['dest_name']) ?></p><?php endif; ?>
    <h3><a href="<?= url('tour.php?slug=' . rawurlencode($t['slug'])) ?>"><?= h($t['title']) ?></a></h3>
    <p><?= h(excerpt($t['summary'], 120)) ?></p>
    <div class="tcard-foot">
      <div class="price"><?php if ($price): ?><span>From</span><b><?= h($price) ?></b><?php else: ?><span>Price</span><b class="ask">On request</b><?php endif; ?></div>
      <a class="link-arrow" href="<?= url('tour.php?slug=' . rawurlencode($t['slug'])) ?>" aria-label="View <?= h($t['title']) ?>"><?= sicon('arrow', 20) ?></a>
    </div>
  </div>
</article>
<?php return ob_get_clean();
}

function tours_query(string $where = '1=1', array $params = [], string $limit = ''): array
{
    return rows("SELECT t.*, d.name dest_name, d.slug dest_slug FROM tours t LEFT JOIN destinations d ON d.id = t.destination_id WHERE t.active = 1 AND $where ORDER BY t.sort, t.id DESC $limit", $params);
}

function post_card(array $p): string
{
    ob_start(); ?>
<article class="pcard">
  <a href="<?= url('post.php?slug=' . rawurlencode($p['slug'])) ?>" class="pcard-img"><img src="<?= h(upload_url($p['image'], 'sm')) ?>" alt="<?= h($p['title']) ?>" loading="lazy"></a>
  <p class="kicker"><?= h($p['category'] ?: 'Journal') ?> · <?= h(fdate($p['published_at'])) ?></p>
  <h3><a href="<?= url('post.php?slug=' . rawurlencode($p['slug'])) ?>"><?= h($p['title']) ?></a></h3>
  <p><?= h(excerpt($p['excerpt'] ?: $p['body'], 130)) ?></p>
</article>
<?php return ob_get_clean();
}

function review_sources(): array
{
    return ['website' => 'SkyTydes website', 'google' => 'Google', 'tripadvisor' => 'Tripadvisor', 'facebook' => 'Facebook'];
}

/** Small "posted on" badge: the platform's uploaded logo (Site settings) or its name. */
function source_badge(string $src): string
{
    $name = $src === 'website' ? 'Verified guest' : (review_sources()[$src] ?? 'Website');
    $logo = $src !== 'website' ? setting('logo_' . $src) : '';
    if ($logo) return '<img class="rsrc-logo" src="' . h(upload_url($logo)) . '" alt="' . h($name) . '"><span>' . h($name) . '</span>';
    if ($src === 'website') return '<img class="rsrc-logo" src="' . h(asset('img/favicon-32.png')) . '" alt=""><span>' . h($name) . '</span>';
    return '<span>' . h($name) . '</span>';
}

function time_ago(?string $d): string
{
    if (!$d) return '';
    $s = time() - strtotime($d);
    if ($s < 86400) return 'today';
    $days = (int) floor($s / 86400);
    if ($days < 7) return $days === 1 ? 'yesterday' : $days . ' days ago';
    if ($days < 30) return (int) floor($days / 7) === 1 ? 'a week ago' : (int) floor($days / 7) . ' weeks ago';
    if ($days < 365) return (int) floor($days / 30) <= 1 ? 'a month ago' : (int) floor($days / 30) . ' months ago';
    return (int) floor($days / 365) === 1 ? 'a year ago' : (int) floor($days / 365) . ' years ago';
}

function review_card(array $r): string
{
    $src = $r['source'] ?? 'website';
    $srcName = review_sources()[$src] ?? 'Website';
    $ini = mb_strtoupper(implode('', array_map(fn($p) => mb_substr($p, 0, 1), array_slice(preg_split('/\s+/', trim($r['name'])), 0, 2))));
    $hue = abs(crc32($r['name'])) % 4;
    $long = mb_strlen((string) $r['body']) > 260;
    ob_start(); ?>
<figure class="rcard">
  <div class="rhead">
    <span class="ravatar c<?= $hue ?>" aria-hidden="true"><?= h($ini) ?></span>
    <div class="rwho"><b><?= h($r['name']) ?></b><small><?= $r['country'] ? h($r['country']) . ' · ' : '' ?><?= h(time_ago($r['created_at'])) ?></small></div>
    <?php if (!empty($r['source_url'])): ?><a class="rsrc s-<?= h($src) ?>" href="<?= h($r['source_url']) ?>" target="_blank" rel="noopener noreferrer nofollow" title="Read on <?= h($srcName) ?>"><?= source_badge($src) ?></a>
    <?php else: ?><span class="rsrc s-<?= h($src) ?>"><?= source_badge($src) ?></span><?php endif; ?>
  </div>
  <?= stars((int) $r['rating']) ?>
  <?php if ($r['title']): ?><h3><?= h($r['title']) ?></h3><?php endif; ?>
  <blockquote class="<?= $long ? 'clamp' : '' ?>"><?= nl2br(h($r['body'])) ?></blockquote>
  <?php if ($long): ?><button type="button" class="rmore" data-more>Read more</button><?php endif; ?>
  <?php if (!empty($r['tour'])): ?><figcaption><?= sicon('pin', 14) ?><?= h($r['tour']) ?></figcaption><?php endif; ?>
</figure>
<?php return ob_get_clean();
}

function review_stats(): array
{
    $rows = rows("SELECT rating, COUNT(*) n FROM reviews WHERE status = 'approved' GROUP BY rating");
    $dist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    foreach ($rows as $r) $dist[max(1, min(5, (int) $r['rating']))] += (int) $r['n'];
    $n = array_sum($dist);
    $avg = $n ? array_sum(array_map(fn($k, $v) => $k * $v, array_keys($dist), $dist)) / $n : 0;
    return ['n' => $n, 'avg' => $avg, 'dist' => $dist];
}

/** Google-style summary: big score, stars, bars, and platform buttons. */
function review_summary(bool $withWrite = true): string
{
    $st = review_stats();
    $links = [];
    if (setting('google_write_url')) $links[] = ['Review us on Google', setting('google_write_url'), 'google'];
    elseif (setting('google_reviews_url')) $links[] = ['See us on Google', setting('google_reviews_url'), 'google'];
    if (setting('tripadvisor_url')) $links[] = ['Review us on Tripadvisor', setting('tripadvisor_url'), 'tripadvisor'];
    if (setting('facebook_reviews_url')) $links[] = ['Review us on Facebook', setting('facebook_reviews_url'), 'facebook'];
    ob_start(); ?>
<div class="rsum">
  <?php if ($st['n']): ?>
  <div class="rsum-score"><b><?= h(number_format($st['avg'], 1)) ?></b><?= stars((int) round($st['avg'])) ?><span><?= $st['n'] ?> review<?= $st['n'] === 1 ? '' : 's' ?></span></div>
  <div class="rsum-bars">
    <?php foreach ($st['dist'] as $k => $v): $pct = $st['n'] ? round($v / $st['n'] * 100) : 0; ?>
      <div class="rbar"><span><?= $k ?></span><i><em style="width:<?= $pct ?>%"></em></i><small><?= $v ?></small></div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="rsum-empty"><b>Be the first to review</b><span>Travelled with us? Tell others how it went.</span></div>
  <?php endif; ?>
  <div class="rsum-cta">
    <?php if ($withWrite): ?><a class="btn gold" href="<?= url('reviews.php?write=1#write') ?>" data-review-open><?= sicon('star', 18) ?> Write a review</a><?php endif; ?>
    <?php foreach ($links as $l): $lg = setting('logo_' . $l[2]); ?><a class="btn ghost sm plat" href="<?= h($l[1]) ?>" target="_blank" rel="noopener noreferrer"><?php if ($lg): ?><img src="<?= h(upload_url($lg)) ?>" alt=""><?php endif; ?><?= h($l[0]) ?> ↗</a><?php endforeach; ?>
  </div>
</div>
<?php return ob_get_clean();
}

/** Sliding carousel of review cards. */
function reviews_slider(array $reviews): string
{
    if (!$reviews) return '';
    ob_start(); ?>
<div class="rslider" data-rslider>
  <div class="rtrack"><?php foreach ($reviews as $r) echo review_card($r); ?></div>
  <?php if (count($reviews) > 1): ?>
  <div class="rnav"><button type="button" class="rprev" aria-label="Previous reviews">‹</button><button type="button" class="rnext" aria-label="Next reviews">›</button></div>
  <?php endif; ?>
</div>
<?php return ob_get_clean();
}

function active_offers(int $limit = 50): array
{
    try {
        $n = now();
        return rows("SELECT o.*, t.title tour_title, t.slug tour_slug FROM offers o LEFT JOIN tours t ON t.id = o.tour_id
            WHERE o.active = 1 AND (o.valid_from IS NULL OR o.valid_from <= ?) AND (o.valid_until IS NULL OR o.valid_until >= ?)
            ORDER BY o.sort, o.id DESC LIMIT " . (int) $limit, [$n, $n]);
    } catch (Throwable $e) {
        return [];
    }
}

function offer_card(array $o, bool $full = false): string
{
    ob_start(); ?>
<article class="ocard">
  <div class="ocard-img"><img src="<?= h(upload_url($o['image'], 'sm')) ?>" alt="<?= h($o['title']) ?>" loading="lazy"><?php if ($o['tag']): ?><span class="otag"><?= h($o['tag']) ?></span><?php endif; ?></div>
  <div class="ocard-body">
    <?php if ($o['valid_until']): ?><p class="kicker"><?= sicon('calendar', 14) ?>Book by <?= h(fdate($o['valid_until'])) ?></p><?php endif; ?>
    <h3><?= h($o['title']) ?></h3>
    <p><?= h($o['summary']) ?></p>
    <?php if ($o['tour_slug']): ?><a class="otour" href="<?= url('tour.php?slug=' . rawurlencode($o['tour_slug'])) ?>"><?= sicon('arrow', 16) ?><?= h($o['tour_title']) ?></a><?php endif; ?>
    <?php if ($full && ($terms = lines($o['terms']))): ?><details class="terms"><summary>Terms &amp; conditions</summary><ul><?php foreach ($terms as $t): ?><li><?= h($t) ?></li><?php endforeach; ?></ul></details><?php endif; ?>
    <?= wa_btn('Claim this offer', 'offer', $o['title'], 'btn wa sm') ?>
  </div>
</article>
<?php return ob_get_clean();
}

function team_cards(): string
{
    $team = rows('SELECT * FROM team WHERE active = 1 ORDER BY sort, id');
    if (!$team) return '';
    ob_start(); ?>
<div class="hosts">
  <?php foreach ($team as $m): $ini = implode('', array_map(fn($p) => mb_substr($p, 0, 1), array_slice(explode(' ', $m['name']), 0, 2))); ?>
  <figure class="host">
    <?php if ($m['image']): ?><img src="<?= h(upload_url($m['image'], 'sm')) ?>" alt="<?= h($m['name']) ?>" loading="lazy"><?php else: ?><span class="host-ini" aria-hidden="true"><?= h($ini) ?></span><?php endif; ?>
    <figcaption><b><?= h($m['name']) ?></b><span><?= h($m['role']) ?></span><?php if ($m['bio']): ?><p><?= h($m['bio']) ?></p><?php endif; ?></figcaption>
  </figure>
  <?php endforeach; ?>
</div>
<?php return ob_get_clean();
}

/** Social media icons. Facebook, Instagram, TikTok, YouTube and WhatsApp always show (as plain icons until a link is added); X and LinkedIn appear once linked. */
function social_links(string $class = 'socials'): string
{
    $nets = [
        'social_facebook' => ['fb', 'Facebook'], 'social_instagram' => ['insta', 'Instagram'], 'social_tiktok' => ['tiktok', 'TikTok'],
        'social_x' => ['x', 'X (Twitter)'], 'social_youtube' => ['youtube', 'YouTube'], 'social_linkedin' => ['linkedin', 'LinkedIn'],
    ];
    $out = '<div class="' . h($class) . '">';
    $always = ['social_facebook', 'social_instagram', 'social_tiktok', 'social_youtube'];
    foreach ($nets as $k => [$ic, $label]) {
        $link = setting($k);
        if ($link !== '' && preg_match('#^https://#i', $link)) {
            $out .= '<a href="' . h($link) . '" target="_blank" rel="noopener noreferrer" aria-label="' . h($label) . '" title="' . h($label) . '">' . sicon($ic, 19) . '</a>';
        } elseif (in_array($k, $always, true)) {
            // no link yet: show the icon, but not as a clickable link
            $out .= '<span class="soon" role="img" aria-label="' . h($label) . ' (coming soon)" title="' . h($label) . ' — coming soon">' . sicon($ic, 19) . '</span>';
        }
    }
    $out .= '<a href="' . h(wa_link('Hello SkyTydes Safaris!')) . '" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp" title="WhatsApp">' . sicon('wa', 19) . '</a>';
    return $out . '</div>';
}

/** Google Map of the office (no API key needed). Address comes from Site settings. */
function office_map(): string
{
    $q = setting('map_address', setting('address', 'Mobil Plaza, Muthaiga, Nairobi, Kenya'));
    if ($q === '') return '';
    $embed = 'https://www.google.com/maps?q=' . rawurlencode($q) . '&z=16&output=embed';
    $open = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($q);
    ob_start(); ?>
<div class="map-wrap">
  <div class="map-card">
    <p class="eyebrow">Visit us</p>
    <h2 class="h3">Our office</h2>
    <p><?= h(setting('address')) ?><br><span class="muted"><?= h(setting('postal')) ?></span></p>
    <?php if (setting('hours')): ?><p class="muted small"><?= sicon('clock', 16) ?> <?= h(setting('hours')) ?></p><?php endif; ?>
    <div class="row-btns"><a class="btn dark sm" href="<?= h($open) ?>" target="_blank" rel="noopener noreferrer"><?= sicon('pin', 16) ?> Get directions</a><?= wa_btn('Book a visit', 'general', 'Office visit') ?></div>
  </div>
  <iframe class="map" src="<?= h($embed) ?>" title="Map showing the SkyTydes Safaris office at <?= h($q) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
</div>
<?php return ob_get_clean();
}

function doc_buttons(array $d, bool $big = false): string
{
    $sz = $big ? '' : ' sm';
    if ($d['access'] === 'request') return wa_btn('Request on WhatsApp', 'document', $d['title'], 'btn wa' . $sz);
    return '<div class="row-btns"><a class="btn dark' . $sz . '" href="' . h(url('doc.php?id=' . (int) $d['id'])) . '" target="_blank" rel="noopener">' . sicon('file', 16) . ' Read online</a>'
        . '<a class="link-more" href="' . h(url('doc.php?id=' . (int) $d['id'] . '&dl=1')) . '">' . sicon('download', 16) . ' Download' . ($d['file_size'] ? ' <small class="muted">(' . h(human_size((int) $d['file_size'])) . ')</small>' : '') . '</a></div>';
}

function doc_card(array $d): string
{
    ob_start(); ?>
<article class="doccard">
  <div class="doccard-img"><?php if ($d['image']): ?><img src="<?= h(upload_url($d['image'], 'sm')) ?>" alt="<?= h($d['title']) ?>" loading="lazy"><?php else: ?><span class="pdfmark"><?= sicon('file', 40) ?><b>PDF</b></span><?php endif; ?>
    <?php if ($d['access'] === 'request'): ?><span class="otag">On request</span><?php endif; ?></div>
  <div class="doccard-body">
    <h3><?= h($d['title']) ?></h3>
    <?php if ($d['summary']): ?><p><?= h(excerpt($d['summary'], 120)) ?></p><?php endif; ?>
    <?= doc_buttons($d) ?>
  </div>
</article>
<?php return ob_get_clean();
}

function not_found(): void
{
    http_response_code(404);
    require ROOT . '/404.php';
    exit;
}
