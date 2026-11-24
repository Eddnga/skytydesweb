<?php
declare(strict_types=1);

/**
 * Search-engine helpers: page titles/descriptions, share images and
 * structured data (JSON-LD) that tells Google what each page is about.
 */

/** Titles and descriptions for the main pages. Each can be overridden in Site settings → SEO. */
function page_seo(string $key): array
{
    $defaults = [
        'home' => ['Kenya Safaris & Tours from Nairobi | SkyTydes Safaris', ''],
        'tours' => ['Kenya Safari Packages & Tours | SkyTydes Safaris', 'Maasai Mara, Amboseli, Aberdares and bush-and-beach safari packages from Nairobi. Group or private trips, planned end to end — enquire on WhatsApp.'],
        'destinations' => ['Kenya Safari Destinations — Maasai Mara, Amboseli & More', 'Where to go on safari in Kenya: the Maasai Mara, Amboseli, the Aberdares and the coast — with tours to each, planned by a Nairobi team.'],
        'offers' => ['Kenya Safari Deals & Special Offers | SkyTydes Safaris', 'Current special offers on Kenya safaris and coast holidays from SkyTydes Safaris. Limited dates — check availability on WhatsApp.'],
        'gallery' => ['Kenya Safari Photo Gallery | SkyTydes Safaris', 'Photos from our Kenya safaris and coast holidays — wildlife, lodges, beaches and happy travellers.'],
        'blog' => ['Kenya Safari Tips & Travel Guides | SkyTydes Journal', 'Safari tips, park guides and travel stories from SkyTydes Safaris — plan a better trip to Kenya.'],
        'reviews' => ['Reviews from Our Safari Guests | SkyTydes Safaris', 'Read what travellers say about their safaris and coast holidays with SkyTydes Safaris — and share your own experience.'],
        'about' => ['About SkyTydes Safaris — Safari Company in Nairobi', 'SkyTydes Safaris is a Nairobi safari company in Muthaiga, led by directors Samuel Mwangi and Susan Muthoni. Tailor-made Kenya trips, planned for you.'],
        'contact' => ['Contact SkyTydes Safaris — Plan Your Kenya Safari', 'Call or WhatsApp SkyTydes Safaris on +254 723 022 702 or +254 713 716 986, email info@skytydessafaris.com, or visit us at Mobil Plaza, Muthaiga, Nairobi.'],
        'services' => ['Safari Services: Tailor-made Trips, Transfers & Lodges', 'Tailor-made safaris, group and corporate trips, lodge bookings, airport transfers, beach holidays and park fees — all arranged by SkyTydes Safaris.'],
        'faqs' => ['Kenya Safari FAQs — Booking, Prices & Travel Tips', 'Answers to common questions about booking a Kenya safari with SkyTydes Safaris: prices, what to pack, park fees, transport and more.'],
        'documents' => ['Company Profile, Brochures & Rates | SkyTydes Safaris', 'Read the SkyTydes Safaris company profile, safari brochures and rates online or download a copy.'],
    ];
    [$t, $d] = $defaults[$key] ?? ['', ''];
    $t = setting('seo_title_' . $key) ?: $t;
    $d = setting('seo_desc_' . $key) ?: ($d ?: setting('meta_description'));
    return [$t, $d];
}

/** Absolute URL of an uploaded image (for og:image and schema). */
function abs_image(?string $file): ?string
{
    if (!$file) return null;
    return abs_url(ltrim(str_replace(BASE_PATH, '', upload_url($file)), '/'));
}

/** The image used when a page has none of its own: Site settings share image, else the first hero slide. */
function default_share_image(): ?string
{
    $img = setting('og_image') ?: null;
    if (!$img) {
        try {
            $img = val('SELECT image FROM hero_slides WHERE active = 1 ORDER BY sort, id LIMIT 1') ?: null;
        } catch (Throwable $e) {
        }
    }
    return $img;
}

/** Search Console / Bing codes may be pasted as the whole <meta> tag or just the code. */
function verification_code(string $key): string
{
    $v = setting($key);
    if (preg_match('/content="([^"]+)"/', $v, $m)) $v = $m[1];
    return preg_replace('/[^A-Za-z0-9_\-=]/', '', $v) ?? '';
}

/** Google Analytics 4 measurement ID (G-XXXXXXX), or '' when not set. Pasting the whole snippet works too. */
function ga_id(): string
{
    return preg_match('/\bG-[A-Z0-9]{4,16}\b/', strtoupper(setting('ga_id')), $m) ? $m[0] : '';
}

/** What kind of page this is, for grouping in Analytics reports. */
function ga_page_type(): string
{
    if (http_response_code() === 404) return 'not_found';
    $p = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'), '.php');
    return ['index' => 'home', 'tour' => 'tour', 'tours' => 'tour_list', 'destination' => 'destination', 'destinations' => 'destination_list',
        'post' => 'blog_post', 'blog' => 'blog', 'section' => isset($_GET['slug']) ? 'section_item' : 'section_list', '404' => 'not_found'][$p] ?? $p;
}

function json_ld(array $data): string
{
    return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>' . "\n";
}

/** The business itself — shown on the home, About and Contact pages. */
function schema_business(): array
{
    $same = array_values(array_filter(array_map('setting', ['social_facebook', 'social_instagram', 'social_tiktok', 'social_youtube', 'social_x', 'social_linkedin', 'tripadvisor_url'])));
    $phones = array_values(array_filter([setting('phone1'), setting('phone2')]));
    $biz = [
        '@context' => 'https://schema.org',
        '@type' => 'TravelAgency',
        '@id' => abs_url('') . '#business',
        'name' => setting('site_name', 'SkyTydes Safaris'),
        'slogan' => setting('tagline'),
        'description' => setting('meta_description'),
        'url' => abs_url(''),
        'logo' => abs_url('assets/img/favicon-512.png'),
        'image' => abs_image(default_share_image()),
        'telephone' => $phones[0] ?? null,
        'email' => setting('email') ?: null,
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => 'Mobil Plaza, Muthaiga',
            'addressLocality' => 'Nairobi',
            'postOfficeBoxNumber' => '64364-00620',
            'addressCountry' => 'KE',
        ],
        'areaServed' => ['@type' => 'Country', 'name' => 'Kenya'],
        'founder' => [['@type' => 'Person', 'name' => 'Samuel Mwangi', 'jobTitle' => 'Director'], ['@type' => 'Person', 'name' => 'Susan Muthoni', 'jobTitle' => 'Director']],
        'contactPoint' => array_map(fn($p) => ['@type' => 'ContactPoint', 'telephone' => $p, 'contactType' => 'reservations', 'availableLanguage' => ['English', 'Swahili']], $phones),
    ];
    if ($same) $biz['sameAs'] = $same;
    return array_filter($biz, fn($v) => $v !== null && $v !== '' && $v !== []);
}

function schema_website(): array
{
    return ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => setting('site_name', 'SkyTydes Safaris'), 'url' => abs_url(''), 'publisher' => ['@id' => abs_url('') . '#business']];
}

/** Breadcrumb trail: [['Tours', 'tours.php'], …] plus the current page. */
function schema_breadcrumbs(array $crumbs, string $current): array
{
    $items = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => abs_url('')]];
    foreach ($crumbs as $i => $c) $items[] = ['@type' => 'ListItem', 'position' => $i + 2, 'name' => $c[0], 'item' => abs_url($c[1])];
    $items[] = ['@type' => 'ListItem', 'position' => count($items) + 1, 'name' => $current];
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
}

function schema_tour(array $t, array $days, ?string $dest): array
{
    $d = [
        '@context' => 'https://schema.org',
        '@type' => 'TouristTrip',
        'name' => $t['title'],
        'description' => $t['summary'] ?: excerpt(strip_tags((string) $t['body']), 300),
        'url' => abs_url('tour.php?slug=' . rawurlencode($t['slug'])),
        'image' => abs_image($t['image']),
        'touristType' => $t['tour_type'] ?: null,
        'provider' => ['@id' => abs_url('') . '#business', '@type' => 'TravelAgency', 'name' => setting('site_name', 'SkyTydes Safaris'), 'url' => abs_url('')],
    ];
    if ($dest) $d['itinerary'] = ['@type' => 'Place', 'name' => $dest . ', Kenya'];
    if ($days) {
        $d['itinerary'] = ['@type' => 'ItemList', 'numberOfItems' => count($days), 'itemListElement' => array_map(fn($x, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => 'Day ' . ($i + 1) . ': ' . $x['title']], $days, array_keys($days))];
    }
    if (!empty($t['price_from'])) {
        $d['offers'] = ['@type' => 'Offer', 'price' => (string) (float) $t['price_from'], 'priceCurrency' => $t['currency'] ?: 'KES', 'availability' => 'https://schema.org/InStock', 'url' => abs_url('tour.php?slug=' . rawurlencode($t['slug']))];
    }
    return array_filter($d, fn($v) => $v !== null && $v !== '');
}

function schema_destination(array $d): array
{
    return array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'TouristDestination',
        'name' => $d['name'],
        'description' => $d['summary'] ?: null,
        'url' => abs_url('destination.php?slug=' . rawurlencode($d['slug'])),
        'image' => abs_image($d['image']),
        'containedInPlace' => ['@type' => 'Country', 'name' => 'Kenya'],
    ], fn($v) => $v !== null && $v !== '');
}

function schema_post(array $p): array
{
    return array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => mb_substr($p['title'], 0, 110),
        'description' => $p['excerpt'] ?: excerpt(strip_tags((string) $p['body']), 200),
        'image' => abs_image($p['image']),
        'datePublished' => $p['published_at'] ? date('c', strtotime($p['published_at'])) : null,
        'dateModified' => date('c', strtotime($p['updated_at'] ?? $p['published_at'] ?? $p['created_at'])),
        'author' => ['@type' => $p['author'] ? 'Person' : 'Organization', 'name' => $p['author'] ?: setting('site_name', 'SkyTydes Safaris')],
        'publisher' => ['@type' => 'Organization', 'name' => setting('site_name', 'SkyTydes Safaris'), 'logo' => ['@type' => 'ImageObject', 'url' => abs_url('assets/img/favicon-512.png')]],
        'mainEntityOfPage' => abs_url('post.php?slug=' . rawurlencode($p['slug'])),
    ], fn($v) => $v !== null && $v !== '');
}

// ---------- Answer-engine (AEO/GEO) helpers ----------
// Short, factual question → answer pairs, shown on the page AND described as
// FAQPage data, so Google's AI Overviews, ChatGPT, Perplexity and others can
// quote SkyTydes directly.

function list_sentence(array $items, int $max = 8): string
{
    $items = array_slice(array_values(array_filter(array_map('trim', $items))), 0, $max);
    if (count($items) < 2) return $items[0] ?? '';
    $last = array_pop($items);
    return implode(', ', $items) . ' and ' . $last;
}

function contact_answer(): string
{
    $phones = array_values(array_filter([setting('phone1'), setting('phone2')]));
    return 'Message SkyTydes Safaris on WhatsApp' . ($phones ? ' (' . implode(' or ', $phones) . ')' : '') . ' or email ' . setting('email', 'info@skytydessafaris.com') . ' with your dates and group size. Nothing is booked or paid until your trip is confirmed.';
}

function tour_answers(array $t, array $days, ?string $bestTime): array
{
    $n = (int) $t['duration_days'];
    $name = $t['title'];
    $qa = [];
    if ($n) $qa[] = ["How long is the $name?", $n . ' day' . ($n > 1 ? 's' : '') . ($n > 1 ? ' and ' . ($n - 1) . ' night' . ($n > 2 ? 's' : '') : '') . ($t['dest_name'] ? ' in ' . $t['dest_name'] . ', Kenya' : ' in Kenya') . '.' . ($days ? ' Day by day: ' . list_sentence(array_map(fn($d) => 'Day ' . $d['day_no'] . ' – ' . $d['title'], $days), 10) . '.' : '')];
    $price = money($t['price_from'], $t['currency'] ?: 'KES');
    $qa[] = ["How much does the $name cost?", $price ? "From $price" . ($t['price_note'] ? ' ' . trim($t['price_note']) : '') . '. The final price depends on your dates, group size and where you stay.' : 'The price depends on your dates, group size and choice of lodge. SkyTydes Safaris sends a quote on WhatsApp.'];
    if ($inc = lines($t['includes'])) $qa[] = ["What is included in the $name?", list_sentence($inc, 12) . '.'];
    if ($exc = lines($t['excludes'])) $qa[] = ["What is not included?", list_sentence($exc, 10) . '.'];
    if ($bestTime) $qa[] = ['When is the best time to go' . ($t['dest_name'] ? ' to ' . $t['dest_name'] : '') . '?', rtrim($bestTime, '.') . '.'];
    if ($t['tour_type']) $qa[] = ["Is this a group or private safari?", 'It runs as ' . mb_strtolower($t['tour_type']) . '. SkyTydes Safaris can also arrange it privately on your own dates.'];
    $qa[] = ["How do I book the $name?", contact_answer()];
    return $qa;
}

function destination_answers(array $d, array $tours): array
{
    $qa = [];
    if ($d['region']) $qa[] = ['Where is ' . $d['name'] . '?', $d['name'] . ' is in ' . rtrim($d['region'], '.') . ', Kenya.'];
    if ($d['best_time']) $qa[] = ['When is the best time to visit ' . $d['name'] . '?', rtrim($d['best_time'], '.') . '.'];
    if ($d['summary']) $qa[] = ['What is ' . $d['name'] . ' known for?', strip_tags((string) $d['summary'])];
    if ($tours) $qa[] = ['Which SkyTydes Safaris trips go to ' . $d['name'] . '?', list_sentence(array_map(fn($t) => $t['title'] . ((int) $t['duration_days'] ? ' (' . (int) $t['duration_days'] . ' days)' : ''), $tours)) . '.'];
    $qa[] = ['How do I plan a trip to ' . $d['name'] . '?', contact_answer()];
    return $qa;
}

function schema_faq(array $qa): array
{
    return ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn($x) => ['@type' => 'Question', 'name' => $x[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $x[1]]], $qa)];
}

/** Visible "Quick answers" block. */
function answers_block(array $qa, string $heading = 'Quick answers'): string
{
    if (!$qa) return '';
    $out = '<section class="qa" aria-labelledby="qa-h"><h2 class="h3" id="qa-h">' . h($heading) . '</h2><dl>';
    foreach ($qa as $x) $out .= '<div><dt>' . h($x[0]) . '</dt><dd>' . h($x[1]) . '</dd></div>';
    return $out . '</dl></section>';
}
