<?php
declare(strict_types=1);
/** Public pages for the content sections the super admin creates (e.g. /sections/lodges). */
require __DIR__ . '/includes/bootstrap.php';
$t = custom_types()[input('type')] ?? null;
if (!$t || !$t['public']) not_found();
$T = 'cx_' . $t['slug'];
$base = 'section.php?type=' . $t['slug'];
$imgCol = null;
foreach ($t['fields'] as $f) if ($f['type'] === 'image') { $imgCol = section_col($t, $f); break; }

/** One field's value, ready to show. */
function show_value(array $t, array $f, array $r): string
{
    $v = $r[section_col($t, $f)] ?? null;
    if ($v === null || $v === '') return '';
    return match ($f['type']) {
        'money' => h(money($v, 'KES')),
        'bool' => $v ? 'Yes' : 'No',
        'date_opt' => h(fdate((string) $v)),
        'lines' => '<ul class="ticks">' . implode('', array_map(fn($l) => '<li>' . sicon('check', 16) . h($l) . '</li>', lines((string) $v))) . '</ul>',
        'md' => '<div class="prose">' . md((string) $v) . '</div>',
        'textarea' => nl2br(h((string) $v)),
        'image' => '',
        default => h((string) $v),
    };
}

$slug = input('slug');
if ($slug !== '') {
    $r = row("SELECT * FROM $T WHERE slug = ? AND active = 1", [$slug]);
    if (!$r) not_found();
    $desc = '';
    foreach ($t['fields'] as $f) if (in_array($f['type'], ['md', 'textarea'], true) && !empty($r[section_col($t, $f)])) { $desc = excerpt(strip_tags((string) $r[section_col($t, $f)]), 160); break; }
    site_header($r['title'], ['active' => 'sec_' . $t['slug'], 'desc' => $desc ?: $r['title'] . ' — one of the ' . mb_strtolower($t['label']) . ' we arrange at SkyTydes Safaris, Nairobi. Ask us on WhatsApp.', 'image' => $imgCol ? $r[$imgCol] : null, 'ga_item' => ['name' => $r['title'], 'category' => $t['label']]]);
    page_hero($r['title'], $t['one_label'], $imgCol ? $r[$imgCol] : null, '', [[$t['label'], $base]]);
    $facts = []; $blocks = []; $gallery = [];
    foreach ($t['fields'] as $f) {
        $col = section_col($t, $f);
        if ($f['type'] === 'image') { if ($col !== $imgCol && !empty($r[$col])) $gallery[] = $r[$col]; continue; }
        $html = show_value($t, $f, $r);
        if ($html === '') continue;
        if (in_array($f['type'], ['md', 'textarea', 'lines'], true)) $blocks[] = [$f['label'], $html];
        else $facts[] = [$f['label'], $html];
    }
    ?>
<section class="sec">
  <div class="wrap narrow">
    <?php if ($facts): ?><dl class="facts-list"><?php foreach ($facts as $x): ?><div><dt><?= h($x[0]) ?></dt><dd><?= $x[1] ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
    <?php foreach ($blocks as $i => $b): ?>
      <?php if (count($blocks) > 1 || $facts): ?><h2 class="h3"><?= h($b[0]) ?></h2><?php endif; ?>
      <div class="sec-block"><?= $b[1] ?></div>
    <?php endforeach; ?>
    <?php if ($gallery): ?><div class="grid3 sec-gallery"><?php foreach ($gallery as $g): ?><img src="<?= h(upload_url($g)) ?>" alt="" loading="lazy"><?php endforeach; ?></div><?php endif; ?>
    <div class="post-foot"><a class="link-more" href="<?= url($base) ?>">← All <?= h(mb_strtolower($t['label'])) ?></a><?= wa_btn('Ask on WhatsApp', 'general', $t['one_label'] . ': ' . $r['title'], 'btn wa') ?></div>
  </div>
</section>
<?php
    site_footer();
    exit;
}

$items = rows("SELECT * FROM $T WHERE active = 1 ORDER BY sort ASC, id DESC");
site_header($t['label'], ['active' => 'sec_' . $t['slug'], 'desc' => $t['intro'] ? excerpt((string) $t['intro'], 160) : 'Explore ' . mb_strtolower($t['label']) . ' with SkyTydes Safaris — Kenya safaris and coast holidays planned for you from Nairobi.']);
page_hero($t['label'], 'SkyTydes Safaris', $imgCol && $items ? $items[0][$imgCol] : null);
?>
<section class="sec">
  <div class="wrap">
    <?php if ($t['intro']): ?><p class="lead dark narrow-text"><?= h($t['intro']) ?></p><?php endif; ?>
    <?php if (!$items): ?><p class="muted">Nothing here yet — check back soon.</p><?php else: ?>
    <div class="grid3">
      <?php foreach ($items as $r):
          $href = url($base . '&slug=' . rawurlencode($r['slug']));
          $sub = []; $text = '';
          foreach ($t['fields'] as $f) {
              if (in_array($f['type'], ['text', 'select', 'number', 'money'], true) && count($sub) < 2 && ($v = show_value($t, $f, $r)) !== '') $sub[] = $v;
              if ($text === '' && in_array($f['type'], ['md', 'textarea'], true) && !empty($r[section_col($t, $f)])) $text = excerpt(strip_tags((string) $r[section_col($t, $f)]), 120);
          } ?>
        <article class="pcard">
          <?php if ($imgCol): ?><a href="<?= h($href) ?>" class="pcard-img"><img src="<?= h(upload_url($r[$imgCol], 'sm')) ?>" alt="" loading="lazy"></a><?php endif; ?>
          <?php if ($sub): ?><p class="kicker"><?= implode(' · ', $sub) ?></p><?php endif; ?>
          <h3><a href="<?= h($href) ?>"><?= h($r['title']) ?></a></h3>
          <?php if ($text): ?><p><?= h($text) ?></p><?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php site_footer();
