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
        insert('subscribers', ['email' => $email, 'name' => mb_substr(input('name'), 0, 120) ?: null, 'ip' => client_ip(), 'created_at' => now()]);
        audit('visitor_subscribe', 'subscribers', null, ['name' => $email]);
    }
    flash('join_ok', "You're in! Look out for our next update.");
}
redirect(ltrim($back, '/') . '#join');
