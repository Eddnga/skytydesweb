<?php
declare(strict_types=1);
/**
 * /llms.txt and /llms-full.txt — a plain summary of SkyTydes Safaris for AI
 * assistants (ChatGPT, Claude, Perplexity, Gemini…). Built live from the
 * dashboard content, so it is always up to date.
 */
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex');
$full = input('full') === '1';
// HTML/markdown → readable plain text that keeps paragraphs, headings and list items
$plain = function (?string $s): string {
    $html = md((string) $s);
    $html = preg_replace('#<li[^>]*>#i', '- ', $html);
    $html = preg_replace('#</(p|li|h[1-6]|ul|ol|div|blockquote)>|<br\s*/?>#i', "\n", $html);
    $txt = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
    $lines = array_filter(array_map(fn($l) => trim(preg_replace('/[ \t]+/', ' ', $l)), explode("\n", $txt)), fn($l) => $l !== '');
    return implode("\n", $lines);
};
$name = setting('site_name', 'SkyTydes Safaris');
$L = [];
$L[] = "# $name";
$L[] = '';
$L[] = '> ' . $plain(setting('meta_description'));
$L[] = '';
$L[] = $plain(setting('about_body') ?: setting('intro_body'));
$L[] = '';
$L[] = '## Key facts';
$L[] = "- Business: safari and travel company (tour operator / travel agency) based in Nairobi, Kenya";
$L[] = '- Office: ' . (setting('address') ?: 'Mobil Plaza, Muthaiga, Nairobi, Kenya') . ' · P.O. Box 64364 – 00620, Nairobi';
$L[] = '- Directors: Samuel Mwangi (+254 723 022 702) and Susan Muthoni (+254 713 716 986)';
$L[] = '- Phone / WhatsApp: ' . implode(', ', array_filter([setting('phone1'), setting('phone2')]));
$L[] = '- Email: ' . setting('email', 'info@skytydessafaris.com');
if (setting('hours')) $L[] = '- Office hours: ' . setting('hours');
$L[] = '- Website: ' . abs_url('');
$L[] = '- How to book: every enquiry is handled personally on WhatsApp; no online payment. Nothing is booked until the trip is confirmed.';
$L[] = '- Tagline: ' . setting('tagline');
$L[] = '';

$tours = rows('SELECT t.*, d.name dest FROM tours t LEFT JOIN destinations d ON d.id = t.destination_id WHERE t.active = 1 ORDER BY t.featured DESC, t.sort, t.id');
$L[] = '## Safari packages';
foreach ($tours as $t) {
    $bits = array_filter([(int) $t['duration_days'] ? (int) $t['duration_days'] . ' days' : '', $t['dest'], $t['tour_type'], money($t['price_from'], $t['currency'] ?: 'KES') ? 'from ' . money($t['price_from'], $t['currency'] ?: 'KES') . ($t['price_note'] ? ' ' . $t['price_note'] : '') : 'price on request']);
    $L[] = '- [' . $t['title'] . '](' . abs_url('tour.php?slug=' . rawurlencode($t['slug'])) . '): ' . implode(' · ', $bits) . '. ' . $plain($t['summary']);
    if ($full) {
        foreach (rows('SELECT day_no, title, body FROM itinerary_days WHERE tour_id = ? ORDER BY day_no', [$t['id']]) as $d) $L[] = '  - Day ' . $d['day_no'] . ': ' . $d['title'] . ($d['body'] ? ' — ' . excerpt($plain($d['body']), 220) : '');
        if ($i = lines($t['includes'])) $L[] = '  - Included: ' . implode('; ', $i);
        if ($x = lines($t['excludes'])) $L[] = '  - Not included: ' . implode('; ', $x);
    }
}
$L[] = '';
$L[] = '## Destinations';
foreach (rows('SELECT * FROM destinations WHERE active = 1 ORDER BY sort, id') as $d) {
    $L[] = '- [' . $d['name'] . '](' . abs_url('destination.php?slug=' . rawurlencode($d['slug'])) . '): ' . $plain($d['summary'] ?: $d['tagline']) . ($d['best_time'] ? ' Best time to visit: ' . $d['best_time'] . '.' : '');
}
$L[] = '';
$L[] = '## Services';
foreach (rows('SELECT title, summary FROM services WHERE active = 1 ORDER BY sort, id') as $s) $L[] = '- ' . $s['title'] . ': ' . $plain($s['summary']);
$offers = active_offers();
if ($offers) {
    $L[] = '';
    $L[] = '## Current special offers';
    foreach ($offers as $o) $L[] = '- ' . $o['title'] . ($o['ends_at'] ?? null ? ' (until ' . fdate($o['ends_at']) . ')' : '') . ': ' . $plain($o['summary'] ?? '');
}
foreach (custom_types() as $ct) {
    if (!$ct['public']) continue;
    $items = rows('SELECT title, slug FROM cx_' . $ct['slug'] . ' WHERE active = 1 ORDER BY sort, id LIMIT 50');
    if (!$items) continue;
    $L[] = '';
    $L[] = '## ' . $ct['label'];
    foreach ($items as $i) $L[] = '- [' . $i['title'] . '](' . abs_url('section.php?type=' . $ct['slug'] . '&slug=' . rawurlencode($i['slug'])) . ')';
}
$faqs = rows('SELECT question, answer FROM faqs WHERE active = 1 ORDER BY sort, id');
if ($faqs) {
    $L[] = '';
    $L[] = '## Frequently asked questions';
    foreach ($faqs as $f) {
        if ($full) { $L[] = '### ' . $f['question']; $L[] = $plain($f['answer']); $L[] = ''; }
        else $L[] = '- ' . $f['question'] . ' ' . excerpt($plain($f['answer']), 220);
    }
}
$L[] = '';
$L[] = '## Pages';
foreach ([['About us', 'about.php'], ['All tours', 'tours.php'], ['Destinations', 'destinations.php'], ['Reviews', 'reviews.php'], ['Journal / travel guides', 'blog.php'], ['Company profile & brochures', 'documents.php'], ['FAQs', 'faqs.php'], ['Contact', 'contact.php']] as $p) $L[] = '- [' . $p[0] . '](' . abs_url($p[1]) . ')';
if (!$full) { $L[] = ''; $L[] = 'Full detail (itineraries, inclusions, all FAQ answers): ' . abs_url('') . 'llms-full.txt'; }
echo implode("\n", $L) . "\n";
