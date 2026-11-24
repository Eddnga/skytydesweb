<?php
declare(strict_types=1);

function icon(string $name, int $size = 20): string
{
    $p = [
        'home' => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'image' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M21 17l-5-5-9 8"/>',
        'map' => '<path d="M9 4L3 6v14l6-2 6 2 6-2V4l-6 2-6-2z"/><path d="M9 4v14M15 6v14"/>',
        'pin' => '<path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>',
        'pen' => '<path d="M4 20h4L19 9l-4-4L4 16v4z"/><path d="M14 6l4 4"/>',
        'star' => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9L12 3z"/>',
        'help' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6V14"/><circle cx="12" cy="17.5" r=".6" fill="currentColor"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'chat' => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>',
        'quote' => '<path d="M7 7h4v4c0 3-2 5-4 6M15 7h4v4c0 3-2 5-4 6"/>',
        'cog' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3.5 6"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'log' => '<path d="M5 4h14v16H5z"/><path d="M8 8h8M8 12h8M8 16h5"/>',
        'out' => '<path d="M15 4h4v16h-4"/><path d="M10 8l-4 4 4 4M6 12h10"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'eye' => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'edit' => '<path d="M4 20h4L19 9l-4-4L4 16v4z"/>',
        'check' => '<path d="M5 12.5l4.5 4.5L19 7"/>',
        'x' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'file' => '<path d="M14 3H6v18h12V7z"/><path d="M14 3v4h4M9 13h6M9 17h6"/>',
        'tag' => '<path d="M3 12V4h8l10 10-8 8L3 12z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'pulse' => '<path d="M3 12h4l3-7 4 14 3-7h4"/>',
        'db' => '<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/>',
        'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 3 2.5 15 0 18M12 3c-2.5 3-2.5 15 0 18"/>',
        'ext' => '<path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6"/>',
    ];
    return '<svg class="ic" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$name] ?? '') . '</svg>';
}

function admin_header(string $title, string $active = ''): void
{
    $u = current_user();
    $pending = (int) val("SELECT COUNT(*) FROM reviews WHERE status = 'pending'");
    $newLeads = (int) val("SELECT COUNT(*) FROM leads WHERE status = 'new'");
    $nav = [
        ['index.php', 'Dashboard', 'home', 'dashboard'],
        ['content.php?type=hero', 'Hero slides', 'image', 'hero'],
        ['content.php?type=tours', 'Tour packages', 'map', 'tours'],
        ['content.php?type=destinations', 'Destinations', 'pin', 'destinations'],
        ['content.php?type=posts', 'Blog', 'pen', 'posts'],
        ['content.php?type=offers', 'Special offers', 'tag', 'offers'],
        ['content.php?type=services', 'Services', 'star', 'services'],
        ['content.php?type=faqs', 'FAQs', 'help', 'faqs'],
        ['content.php?type=gallery', 'Gallery', 'grid', 'gallery'],
        ['content.php?type=documents', 'Documents', 'file', 'documents'],
        ['content.php?type=team', 'Your hosts', 'users', 'team'],
    ];
    foreach (custom_types() as $ct) $nav[] = ['content.php?type=sec_' . $ct['slug'], $ct['label'], 'grid', 'sec_' . $ct['slug']];
    $nav = array_merge($nav, [
        ['reviews.php', 'Reviews', 'quote', 'reviews', $pending],
        ['leads.php', 'WhatsApp leads', 'chat', 'leads', $newLeads],
        ['subscribers.php', 'Subscribers', 'mail', 'subscribers', (int) val('SELECT COUNT(*) FROM subscribers WHERE created_at >= ?', [date('Y-m-d H:i:s', time() - 7 * 86400)])],
        ['settings.php', 'Site settings', 'cog', 'settings'],
    ]);
    if (is_admin($u)) {
        $nav[] = ['users.php', 'Users', 'users', 'users'];
        $nav[] = ['activity.php', 'Activity log', 'log', 'activity'];
    }
    if (is_super($u)) {
        $nav[] = ['insights.php', 'Visitor insights', 'chart', 'insights'];
        $nav[] = ['health.php', 'Site health', 'pulse', 'health'];
        $nav[] = ['database.php', 'Database', 'db', 'database'];
        $nav[] = ['sections.php', 'Content sections', 'plus', 'sections'];
    }
    header('Cache-Control: no-store');
    ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= h($title) ?> · SkyTydes Dashboard</title>
<link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css">
<link rel="icon" href="<?= asset('img/favicon-32.png') ?>" type="image/png" sizes="32x32">
<link rel="icon" href="<?= asset('img/favicon-192.png') ?>" type="image/png" sizes="192x192">
<link rel="apple-touch-icon" href="<?= asset('img/apple-touch-icon.png') ?>">
</head><body>
<input type="checkbox" id="navtoggle" hidden>
<aside class="side">
  <a href="<?= url('admin/') ?>" class="side-logo"><img src="<?= asset('img/logo-white.svg') ?>" alt="SkyTydes Safaris"></a>
  <nav>
    <?php $groups = ['dashboard' => '', 'hero' => 'Website', 'reviews' => 'Customers', 'settings' => 'Settings', 'insights' => 'Super admin'];
    foreach ($nav as $n): ?>
      <?php if (!empty($groups[$n[3]])): ?><p class="side-h"><?= h($groups[$n[3]]) ?></p><?php endif; ?>
      <a href="<?= url('admin/' . $n[0]) ?>" class="<?= $active === $n[3] ? 'on' : '' ?>"><?= icon($n[2], 18) ?><span><?= h($n[1]) ?></span><?php if (!empty($n[4])): ?><b class="badge"><?= (int) $n[4] ?></b><?php endif; ?></a>
    <?php endforeach; ?>
  </nav>
</aside>
<label for="navtoggle" class="side-shade" aria-hidden="true"></label>
<main class="main">
  <header class="top">
    <label for="navtoggle" class="burger"><?= icon('menu', 24) ?></label>
    <h1><?= h($title) ?></h1>
    <a class="btn ghost sm" href="<?= url('') ?>" target="_blank" rel="noopener"><?= icon('ext', 16) ?> <span class="hide-sm">View site</span></a>
    <details class="usermenu">
      <summary><span class="avatar"><?= h(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></span><span class="hide-sm"><?= h(explode(' ', $u['name'])[0]) ?></span></summary>
      <div class="usermenu-pop">
        <p><strong><?= h($u['name']) ?></strong><br><span class="muted small"><?= h($u['email']) ?></span></p>
        <a href="<?= url('admin/account.php') ?>"><?= icon('user', 18) ?> My account &amp; password</a>
        <form method="post" action="<?= url('admin/logout.php') ?>"><?= csrf_field() ?><button><?= icon('out', 18) ?> Log out</button></form>
      </div>
    </details>
  </header>
  <div class="content">
  <?php if ($m = flash('ok')): ?><div class="notice ok"><?= h($m) ?></div><?php endif; ?>
  <?php if ($m = flash('error')): ?><div class="notice error"><?= h($m) ?></div><?php endif; ?>
<?php
}

function admin_footer(): void
{
    ?>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script src="<?= asset('js/admin.js') ?>"></script>
</body></html>
<?php
}
