<?php
declare(strict_types=1);
/**
 * Every booking button leads here: we log the lead, then send the visitor to WhatsApp
 * with a ready-made message. Works for the booking sheet (POST) and plain links (GET).
 */
require __DIR__ . '/includes/bootstrap.php';

$post = is_post();
if ($post) csrf_check();
$src = preg_replace('/[^a-z]/', '', input('src', 'general')) ?: 'general';
$id = (int) input('id', '0');
$item = mb_substr(input('item'), 0, 200);
$name = $post ? mb_substr(input('name'), 0, 80) : '';
$date = $post ? mb_substr(input('travel_date'), 0, 40) : '';
$pax = $post ? (string) min(200, max(0, (int) input('travellers'))) : '';
$msg = $post ? mb_substr(input('message'), 0, 500) : '';
$email = $post ? strtolower(mb_substr(trim(input('email')), 0, 190)) : '';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $email = '';

$link = '';
if ($src === 'tour' && $id && ($t = row('SELECT title, slug, duration_days, price_from, currency FROM tours WHERE id = ? AND active = 1', [$id]))) {
    $item = $t['title'];
    $link = abs_url('tour.php?slug=' . rawurlencode($t['slug']));
}

$lines = ['Hello SkyTydes Safaris! 👋'];
if ($src === 'tour' && $item !== '') $lines[] = "I'd like to book: *{$item}*";
elseif ($src === 'destination' && $item !== '') $lines[] = "I'd like to plan a trip to *{$item}*.";
elseif ($src === 'service' && $item !== '') $lines[] = "I'd like to ask about: *{$item}*";
elseif ($src === 'document' && $item !== '') $lines[] = "Please send me your *{$item}*.";
elseif ($src === 'offer' && $item !== '') $lines[] = "I'm interested in your special offer: *{$item}*";
else $lines[] = "I'd like to plan a trip.";
if ($name !== '') $lines[] = "Name: {$name}";
if ($date !== '') $lines[] = "Travel date: {$date}";
if ($pax !== '' && $pax !== '0') $lines[] = "Travellers: {$pax}";
if ($email !== '') $lines[] = "Email: {$email}";
if ($msg !== '') $lines[] = "Notes: {$msg}";
if ($link) $lines[] = $link;
$text = implode("\n", $lines);

// log the lead (limited per visitor so bots cannot flood the dashboard)
try {
    if (throttle('lead:' . client_ip(), 30, 3600)) {
        $ref = parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_PATH) ?: '';
        insert('leads', ['source' => $src, 'item' => $item !== '' ? $item : null, 'name' => $name ?: null, 'travel_date' => $date ?: null,
            'travellers' => ($pax !== '' && $pax !== '0') ? $pax : null, 'message' => $msg ?: null, 'page' => mb_substr($ref, 0, 255),
            'ip' => client_ip(), 'status' => 'new', 'created_at' => now()] + ($email !== '' ? ['email' => $email] : []));
        audit('visitor_lead', 'leads', null, ['name' => $item !== '' ? $item : 'General enquiry', 'via' => $src] + ($email !== '' ? ['email' => $email] : []));
        if ($email !== '') {
            // a copy of the request by email (sent after the visitor is on their way to WhatsApp)
            queue_enquiry($email, $name ?: null, ['item' => $item !== '' ? $item : 'a safari with us', 'travel_date' => $date, 'travellers' => ($pax !== '' && $pax !== '0') ? $pax : '', 'message' => $msg]);
            if (input('news') === '1' && !val('SELECT COUNT(*) FROM subscribers WHERE email = ?', [$email])) {
                insert('subscribers', ['email' => $email, 'name' => $name ?: null, 'ip' => client_ip(), 'created_at' => now()]);
                q('UPDATE subscribers SET welcomed_at = ? WHERE email = ?', [now(), $email]); // they already get the request email
                audit('visitor_subscribe', 'subscribers', null, ['name' => $email, 'note' => 'from the booking form']);
            }
        }
    }
} catch (Throwable $e) {
    error_log('lead log failed: ' . $e->getMessage());
}

header('Cache-Control: no-store');
header('Location: ' . wa_link($text), true, 303);
exit;
