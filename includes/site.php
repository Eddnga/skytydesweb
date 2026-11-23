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
    $active = $o['active'] ?? '';
    $desc = $o['desc'] ?? setting('meta_description');
    $img = $o['image'] ?? null;
    $solid = !empty($o['solid']);
    $full = $title === '' ? setting('site_name', 'SkyTydes Safaris') . ' · ' . setting('tagline') : $title . ' · ' . setting('site_name', 'SkyTydes Safaris');
    $nav = [['tours.php', 'Tours', 'tours', 'tours'], ['destinations.php', 'Destinations', 'destinations', 'dest'], ['offers.php', 'Offers', 'offers', ''], ['gallery.php', 'Gallery', 'gallery', ''], ['blog.php', 'Journal', 'blog', ''], ['reviews.php', 'Reviews', 'reviews', ''], ['about.php', 'About', 'about', 'about'], ['contact.php', 'Contact', 'contact', '']];
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
<meta property="og:type" content="website"><meta property="og:title" content="<?= h($full) ?>"><meta property="og:description" content="<?= h(excerpt($desc, 165)) ?>">
<meta property="og:url" content="<?= h($canon) ?>">
<?php if ($img): ?><meta property="og:image" content="<?= h(abs_url(ltrim(str_replace(BASE_PATH, '', upload_url($img)), '/'))) ?>"><?php endif; ?>
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
                    <a href="<?= url('destination.php?slug=' . rawurlencode($md['slug'])) ?>"><img src="<?= h(upload_url($md['image'], 'sm')) ?>" alt="" loading="lazy"><b><?= h($md['name']) ?></b><small><?= h($md['tagline']) ?></small></a>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="mega-list narrow">
                  <a href="<?= url('about.php') ?>"><b>Our story</b><small>Who we are and how we plan</small></a>
                  <a href="<?= url('about.php#hosts') ?>"><b>Your hosts</b><small>Meet the people behind SkyTydes</small></a>
                  <a href="<?= url('services.php') ?>"><b>Services</b><small>Everything we can arrange for you</small></a>
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
    <button class="burger" aria-controls="menu" aria-expanded="false" aria-label="Open menu"><?= sicon('menu', 26) ?></button>
  </div>
</header>
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
      <div class="socials">
        <?php foreach (['social_instagram' => ['insta', 'Instagram'], 'social_facebook' => ['fb', 'Facebook'], 'social_tiktok' => ['tiktok', 'TikTok']] as $k => $v): if (!empty($s[$k])): ?>
          <a href="<?= h($s[$k]) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= $v[1] ?>"><?= sicon($v[0], 20) ?></a>
        <?php endif; endforeach; ?>
      </div>
    </div>
    <div><h3>Explore</h3><ul><li><a href="<?= url('tours.php') ?>">Tour packages</a></li><li><a href="<?= url('destinations.php') ?>">Destinations</a></li><li><a href="<?= url('offers.php') ?>">Special offers</a></li><li><a href="<?= url('services.php') ?>">Services</a></li><li><a href="<?= url('gallery.php') ?>">Gallery</a></li><li><a href="<?= url('blog.php') ?>">Journal</a></li><li><a href="<?= url('faqs.php') ?>">FAQs</a></li></ul></div>
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
  <a href="<?= url('post.php?slug=' . rawurlencode($p['slug'])) ?>" class="pcard-img"><img src="<?= h(upload_url($p['image'], 'sm')) ?>" alt="" loading="lazy"></a>
  <p class="kicker"><?= h($p['category'] ?: 'Journal') ?> · <?= h(fdate($p['published_at'])) ?></p>
  <h3><a href="<?= url('post.php?slug=' . rawurlencode($p['slug'])) ?>"><?= h($p['title']) ?></a></h3>
  <p><?= h(excerpt($p['excerpt'] ?: $p['body'], 130)) ?></p>
</article>
<?php return ob_get_clean();
}

function review_card(array $r): string
{
    ob_start(); ?>
<figure class="rcard">
  <?= stars((int) $r['rating']) ?>
  <?php if ($r['title']): ?><h3><?= h($r['title']) ?></h3><?php endif; ?>
  <blockquote><?= nl2br(h(excerpt($r['body'], 420))) ?></blockquote>
  <figcaption><b><?= h($r['name']) ?></b><?= $r['country'] ? ' · ' . h($r['country']) : '' ?><?= !empty($r['tour']) ? '<span>' . h($r['tour']) . '</span>' : '' ?></figcaption>
</figure>
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

function not_found(): void
{
    http_response_code(404);
    require ROOT . '/404.php';
    exit;
}
