<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
if (!is_post()) redirect('');
csrf_check();
$email = strtolower(mb_substr(input('email'), 0, 190));
$back = input('back');
$back = (is_string($back) && preg_match('#^[a-z0-9_\-./?=&%]*$#i', $back)) ? $back : '';
if (($_POST['company'] ?? '') !== '') redirect($back . '#join'); // spam trap
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('join_error', 'Please enter a valid email address.');
} elseif (!throttle('join:' . client_ip(), 5, 3600)) {
    flash('join_error', 'Please try again a little later.');
} else {
    if (!val('SELECT COUNT(*) FROM subscribers WHERE email = ?', [$email])) {
        $name = trim(preg_replace('/[^\p{L}\p{M}\s\'\-.]/u', '', mb_substr(input('name'), 0, 60)));
        insert('subscribers', ['email' => $email, 'name' => $name ?: null, 'ip' => client_ip(), 'created_at' => now()]);
        audit('visitor_subscribe', 'subscribers', null, ['name' => $email]);
        queue_welcome($email, $name ?: null); // sent after the page has answered
    }
    flash('join_ok', welcome_enabled() && mail_ready() ? "You're in! Check your inbox for a welcome note from us." : "You're in! Look out for our next update.");
}
redirect(ltrim($back, '/') . '#join');
