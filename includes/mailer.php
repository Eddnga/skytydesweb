<?php
declare(strict_types=1);

/**
 * Sending email: a small SMTP client (SSL on 465 or STARTTLS on 587) so the
 * site can send from info@skytydessafaris.com through Chajio's mail server,
 * plus the branded welcome email that the dashboard lets you edit.
 */

// ---------- secrets (the SMTP password is stored encrypted with config.php's app_key) ----------
function secret_key(): string
{
    global $CONFIG;
    return hash('sha256', 'skytydes-mail|' . ($CONFIG['app_key'] ?? 'sky'), true);
}
function encrypt_secret(string $plain): string
{
    if ($plain === '') return '';
    $iv = random_bytes(12);
    $ct = openssl_encrypt($plain, 'aes-256-gcm', secret_key(), OPENSSL_RAW_DATA, $iv, $tag);
    return 'v1:' . base64_encode($iv . $tag . $ct);
}
function decrypt_secret(string $stored): string
{
    if (!str_starts_with($stored, 'v1:')) return '';
    $raw = base64_decode(substr($stored, 3), true);
    if ($raw === false || strlen($raw) < 29) return '';
    $out = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', secret_key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
    return $out === false ? '' : $out;
}

function mail_ready(): bool
{
    return setting('smtp_host') !== '' && setting('smtp_user') !== '' && setting('smtp_pass') !== '';
}
function mail_from(): array
{
    return [setting('mail_from_email') ?: setting('smtp_user') ?: setting('email'), setting('mail_from_name') ?: setting('site_name', 'SkyTydes Safaris')];
}

/**
 * Sends one email. Returns null on success or a short error message.
 * Every attempt is written to the email log.
 */
function send_mail(string $to, string $subject, string $html, string $text = '', string $template = '', ?string $replyTo = null, bool $listUnsub = true): ?string
{
    $err = null;
    try {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Invalid recipient address.');
        if (!mail_ready()) throw new RuntimeException('The mail server is not set up yet (Dashboard → Emails).');
        [$fromEmail, $fromName] = mail_from();
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('The "send from" address is not valid.');
        $text = $text !== '' ? $text : html_to_text($html);
        $msg = build_mime($to, $subject, $html, $text, $fromEmail, $fromName, $replyTo ?: (setting('mail_reply_to') ?: $fromEmail), $listUnsub);
        smtp_deliver(setting('smtp_host'), (int) (setting('smtp_port') ?: 465), setting('smtp_secure') ?: 'ssl', setting('smtp_user'), decrypt_secret(setting('smtp_pass')), $fromEmail, $to, $msg);
    } catch (Throwable $e) {
        $err = mb_substr($e->getMessage(), 0, 300);
    }
    try {
        insert('email_log', ['to_email' => mb_substr($to, 0, 190), 'subject' => mb_substr($subject, 0, 200), 'template' => $template ?: null, 'status' => $err ? 'failed' : 'sent', 'error' => $err, 'created_at' => now()]);
    } catch (Throwable $e) {
    }
    return $err;
}

function header_encode(string $s): string
{
    return preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
}
function build_mime(string $to, string $subject, string $html, string $text, string $fromEmail, string $fromName, string $replyTo, bool $listUnsub = true): string
{
    $b = 'b_' . bin2hex(random_bytes(12));
    $domain = substr(strrchr($fromEmail, '@') ?: '@skytydessafaris.com', 1);
    $h = [
        'Date: ' . date('r'),
        'From: ' . header_encode($fromName) . ' <' . $fromEmail . '>',
        'To: <' . $to . '>',
        'Reply-To: <' . $replyTo . '>',
        'Subject: ' . header_encode(str_replace(["\r", "\n"], ' ', $subject)),
        'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $domain . '>',
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $b . '"',
    ];
    $u = $listUnsub ? unsubscribe_url($to) : '';
    if ($u) {
        $h[] = 'List-Unsubscribe: <' . $u . '>';
        $h[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
    }
    $body = "--$b\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text))
        . "--$b\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html))
        . "--$b--\r\n";
    return implode("\r\n", $h) . "\r\n\r\n" . $body;
}

/** Minimal SMTP: connect, (STARTTLS), AUTH LOGIN, send, quit. Throws on any error. */
function smtp_deliver(string $host, int $port, string $secure, string $user, string $pass, string $from, string $to, string $msg): void
{
    // certificates are always checked, except for a mail server on this same machine
    $verify = !in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
    $ctx = stream_context_create(['ssl' => ['verify_peer' => $verify, 'verify_peer_name' => $verify, 'allow_self_signed' => !$verify, 'peer_name' => $host, 'SNI_enabled' => true]]);
    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $fp = @stream_socket_client($remote, $errno, $errstr, 12, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) throw new RuntimeException("Could not connect to $host:$port ($errstr).");
    stream_set_timeout($fp, 20);
    $read = function () use ($fp): array {
        $data = '';
        while (($line = fgets($fp, 1024)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }
        return [(int) substr($data, 0, 3), trim($data)];
    };
    $cmd = function (string $c, array $ok, string $label = '') use ($fp, $read): string {
        fwrite($fp, $c . "\r\n");
        [$code, $res] = $read();
        if (!in_array($code, $ok, true)) throw new RuntimeException(($label ?: strtok($c, ' ')) . ' failed: ' . mb_substr($res, 0, 160));
        return $res;
    };
    try {
        [$code, $res] = $read();
        if ($code !== 220) throw new RuntimeException('Mail server did not greet: ' . $res);
        $me = parse_url($GLOBALS['CONFIG']['base_url'] ?? '', PHP_URL_HOST) ?: 'localhost';
        $ehlo = $cmd('EHLO ' . $me, [250], 'EHLO');
        if ($secure === 'tls') {
            if (stripos($ehlo, 'STARTTLS') === false) throw new RuntimeException('The server does not offer STARTTLS on this port.');
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) throw new RuntimeException('Could not start a secure (TLS) connection.');
            $cmd('EHLO ' . $me, [250], 'EHLO');
        }
        $cmd('AUTH LOGIN', [334], 'Login');
        $cmd(base64_encode($user), [334], 'Login (username)');
        $cmd(base64_encode($pass), [235], 'Login (password)');
        $cmd('MAIL FROM:<' . $from . '>', [250], 'Sender');
        $cmd('RCPT TO:<' . $to . '>', [250, 251], 'Recipient');
        $cmd('DATA', [354]);
        // dot-stuffing: a line that starts with "." gets another "."
        $data = preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], ["\n", "\n"], $msg));
        $cmd(str_replace("\n", "\r\n", $data) . "\r\n.", [250], 'Sending');
        @fwrite($fp, "QUIT\r\n");
    } finally {
        fclose($fp);
    }
}

function html_to_text(string $html): string
{
    $html = preg_replace('#<a[^>]+href="([^"]+)"[^>]*>(.*?)</a>#is', '$2 ($1)', $html);
    $html = preg_replace('#<(br|/p|/h[1-6]|/li|/tr|/div)[^>]*>#i', "\n", $html);
    $txt = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
    return trim(preg_replace("/\n{3,}/", "\n\n", preg_replace('/[ \t]+/', ' ', $txt)));
}

// ---------- unsubscribe links ----------
function unsubscribe_token(string $email): string
{
    return substr(hash_hmac('sha256', strtolower($email), secret_key()), 0, 32);
}
function unsubscribe_url(string $email): string
{
    return abs_url('unsubscribe.php?e=' . rawurlencode(strtolower($email)) . '&t=' . unsubscribe_token($email));
}

/**
 * Lets the visitor's browser get its answer now and does slow work (sending mail)
 * afterwards, so a form never waits for the mail server.
 */
function after_response(callable $fn): void
{
    $GLOBALS['__after'][] = $fn;
    static $hooked = false;
    if ($hooked) return;
    $hooked = true;
    register_shutdown_function(function () {
        if (function_exists('litespeed_finish_request')) litespeed_finish_request();
        elseif (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
        ignore_user_abort(true);
        @set_time_limit(60);
        foreach ($GLOBALS['__after'] ?? [] as $f) {
            try { $f(); } catch (Throwable $e) { error_log('after_response: ' . $e->getMessage()); }
        }
    });
}

// ---------- editable emails (Dashboard → Emails) ----------
// Each template has the same editable parts; settings keys are "<template>_<part>".

function email_templates(): array
{
    return [
        'welcome' => [
            'label' => 'Welcome email',
            'when' => 'Sent automatically when someone joins the newsletter on the website.',
            'switch' => 'Send this email to new subscribers',
            'placeholders' => ['{first_name}', '{name}', '{email}'],
            'defaults' => [
                'subject' => 'Welcome to SkyTydes Safaris, {first_name}!',
                'preheader' => 'Thanks for joining us — here is how to reach us on WhatsApp.',
                'heading' => 'Karibu, {first_name}!',
                'body' => "<p>Thank you for joining the SkyTydes Safaris family. We'll send you new trips, special offers and travel tips from across Kenya — never too often, and only the good stuff.</p>\n<p>Planning something already? The fastest way to reach us is on WhatsApp. Tell us your dates, how many of you are travelling and what you'd love to see, and we'll put a plan together for you.</p>",
                'contacts' => "Chat with Samuel | +254 723 022 702\nChat with Susan | +254 713 716 986",
                'wa_text' => "Hi SkyTydes! I just joined your newsletter and I'd like to plan a safari.",
                'cta_label' => 'Browse our safaris',
                'signoff' => "Warm regards,\nThe SkyTydes Safaris team",
            ],
        ],
        'enquiry' => [
            'label' => 'Booking request email',
            'when' => 'Sent when someone fills in the "Book on WhatsApp" form and gives their email. Their request details are added automatically below your message.',
            'switch' => 'Send this email when a booking request includes an email',
            'placeholders' => ['{first_name}', '{name}', '{email}', '{item}', '{travel_date}', '{travellers}'],
            'defaults' => [
                'subject' => "We've got your request, {first_name} — {item}",
                'preheader' => "Thanks for getting in touch. Here's a copy of your request and how to reach us.",
                'heading' => 'Asante, {first_name}!',
                'body' => "<p>Thank you for your enquiry about <strong>{item}</strong>. We've received your request and one of our team will reply on WhatsApp shortly with availability and a quote.</p>\n<p>If WhatsApp didn't open on your device, or you'd like to add anything, just tap one of the buttons below and send us a message — it goes straight to our directors.</p>",
                'contacts' => "Chat with Samuel | +254 723 022 702\nChat with Susan | +254 713 716 986",
                'wa_text' => "Hi SkyTydes! I sent a request about {item} on your website.",
                'cta_label' => 'See more safaris',
                'signoff' => "Warm regards,\nThe SkyTydes Safaris team",
            ],
        ],
    ];
}
function tpl(string $t, string $k): string
{
    $v = setting($t . '_' . $k);
    return $v !== '' ? $v : (email_templates()[$t]['defaults'][$k] ?? '');
}
function tpl_enabled(string $t): bool
{
    return setting($t . '_enabled') !== '0';
}
// kept for older code paths
function welcome_defaults(): array { return email_templates()['welcome']['defaults']; }
function welcome_tpl(string $k): string { return tpl('welcome', $k); }
function welcome_enabled(): bool { return tpl_enabled('welcome'); }

/** Fills {first_name}, {item}… — values are escaped when $html is true. */
function fill_placeholders(string $s, array $vars, bool $html): string
{
    return preg_replace_callback('/\{([a-z_]+)\}/', fn($m) => array_key_exists($m[1], $vars) ? ($html ? h((string) $vars[$m[1]]) : (string) $vars[$m[1]]) : $m[0], $s);
}

/**
 * Builds one email. $extra holds anything beyond name/email (e.g. item, travel_date).
 * Returns [subject, html, text].
 */
function render_email(string $t, string $email, ?string $name = null, array $extra = []): array
{
    $name = trim((string) $name);
    $vars = ['name' => $name ?: 'there', 'first_name' => $name ? explode(' ', $name)[0] : 'there', 'email' => $email]
        + array_map(fn($v) => (string) $v, $extra)
        + ['item' => 'your trip', 'travel_date' => '', 'travellers' => ''];
    if ($vars['item'] === '') $vars['item'] = 'your trip';
    $subject = trim(fill_placeholders(tpl($t, 'subject'), $vars, false));
    $pre = fill_placeholders(tpl($t, 'preheader'), $vars, true);
    $heading = fill_placeholders(tpl($t, 'heading'), $vars, true);
    $body = fill_placeholders(sanitize_html(md(tpl($t, 'body'))), $vars, true);
    $waText = fill_placeholders(tpl($t, 'wa_text'), $vars, false);

    // the visitor's request, shown as a neat summary (booking request email only)
    $summary = '';
    if ($t === 'enquiry') {
        $rows = array_filter([
            'Trip' => $extra['item'] ?? '', 'Travel date' => $extra['travel_date'] ?? '', 'Travellers' => $extra['travellers'] ?? '',
            'Your notes' => $extra['message'] ?? '',
        ], fn($v) => trim((string) $v) !== '' && $v !== '0');
        if ($rows) {
            $summary = '<tr><td style="padding:4px 36px 12px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #E2E6DC;border-radius:12px">'
                . '<tr><td colspan="2" style="padding:14px 18px 6px;font-family:Arial,Helvetica,sans-serif;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#B07A02;font-weight:bold">Your request</td></tr>';
            foreach ($rows as $k => $val) {
                $summary .= '<tr><td style="padding:6px 18px;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#6B776F;width:120px;vertical-align:top">' . h($k) . '</td>'
                    . '<td style="padding:6px 18px 6px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#1B241D;font-weight:bold">' . nl2br(h((string) $val)) . '</td></tr>';
            }
            $summary .= '<tr><td colspan="2" style="height:8px;font-size:0;line-height:0">&nbsp;</td></tr></table></td></tr>';
        }
    }

    $buttons = '';
    foreach (lines(tpl($t, 'contacts')) as $line) {
        [$label, $num] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
        $digits = preg_replace('/\D+/', '', $num);
        if ($digits === '') continue;
        if (str_starts_with($digits, '0')) $digits = '254' . substr($digits, 1);
        $href = 'https://wa.me/' . $digits . '?text=' . rawurlencode($waText);
        $buttons .= '<tr><td style="padding:0 0 10px"><a href="' . h($href) . '" style="display:block;background:#0C7A43;color:#ffffff;text-decoration:none;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:bold;text-align:center;padding:14px 18px;border-radius:10px">'
            . '&#128172;&nbsp; ' . h($label ?: 'Chat on WhatsApp') . '</a><div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#6B776F;text-align:center;padding-top:5px">' . h($num) . '</div></td></tr>';
    }
    $signoff = nl2br(h(fill_placeholders(tpl($t, 'signoff'), $vars, false)));
    $logo = abs_url('assets/img/email-logo.png');
    $tours = abs_url('tours.php') . '?utm_source=email&utm_medium=' . $t;
    $site = abs_url('');
    $addr = h(setting('address') ?: 'Mobil Plaza, Muthaiga, Nairobi, Kenya');
    $cta = h(tpl($t, 'cta_label'));
    $socials = [];
    foreach (['social_instagram' => 'Instagram', 'social_facebook' => 'Facebook', 'social_tiktok' => 'TikTok', 'social_youtube' => 'YouTube'] as $k => $n) if (setting($k)) $socials[] = '<a href="' . h(setting($k)) . '" style="color:#023F0A">' . $n . '</a>';
    $socialRow = $socials ? '<p style="margin:0 0 10px">' . implode(' &nbsp;·&nbsp; ', $socials) . '</p>' : '';
    $why = $t === 'welcome'
        ? 'You\'re receiving this because you joined our newsletter at <a href="' . $site . '" style="color:#023F0A">skytydessafaris.com</a>. <a href="' . h(unsubscribe_url($email)) . '" style="color:#6B776F">Unsubscribe</a>'
        : 'You\'re receiving this because you sent a trip request on <a href="' . $site . '" style="color:#023F0A">skytydessafaris.com</a>. This is a one-off email, not a newsletter.';
    $ctaRow = $cta !== '' ? '<tr><td align="center" style="padding:18px 36px 6px"><a href="' . $tours . '" style="display:inline-block;border:2px solid #023F0A;color:#023F0A;text-decoration:none;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;padding:12px 26px;border-radius:999px">' . $cta . ' &rarr;</a></td></tr>' : '';
    $waBox = $buttons !== '' ? '<tr><td style="padding:10px 36px 6px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F6F9F2;border-radius:14px"><tr><td style="padding:22px 22px 12px">'
        . '<p style="margin:0 0 14px;font-family:Arial,Helvetica,sans-serif;font-size:13px;letter-spacing:2px;text-transform:uppercase;color:#2D7A0C;font-weight:bold;text-align:center">Talk to us on WhatsApp</p>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $buttons . '</table></td></tr></table></td></tr>' : '';
    $html = <<<HTML
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{$heading}</title></head>
<body style="margin:0;padding:0;background:#F4F1E8">
<span style="display:none!important;visibility:hidden;opacity:0;color:transparent;height:0;width:0;overflow:hidden">{$pre}</span>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F4F1E8"><tr><td align="center" style="padding:28px 12px">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden">
  <tr><td align="center" style="background:#023F0A;padding:30px 24px 26px"><a href="{$site}"><img src="{$logo}" width="180" alt="SkyTydes Safaris" style="display:block;width:180px;max-width:60%;height:auto;border:0"></a></td></tr>
  <tr><td style="height:4px;background:#ECA403;font-size:0;line-height:0">&nbsp;</td></tr>
  <tr><td style="padding:34px 36px 8px;font-family:Arial,Helvetica,sans-serif;color:#1B241D">
    <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-weight:normal;font-size:30px;line-height:1.2;color:#023F0A">{$heading}</h1>
    <div style="font-size:16px;line-height:1.65;color:#33403A">{$body}</div>
  </td></tr>
  {$summary}
  {$waBox}
  {$ctaRow}
  <tr><td style="padding:22px 36px 34px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#33403A">{$signoff}</td></tr>
  <tr><td style="background:#FBFAF6;border-top:1px solid #E6E9E1;padding:22px 30px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#6B776F;text-align:center">
    {$socialRow}
    <p style="margin:0 0 6px">SkyTydes Safaris · {$addr}</p>
    <p style="margin:0">{$why}</p>
  </td></tr>
</table>
</td></tr></table>
</body></html>
HTML;
    return [$subject, $html, html_to_text($html)];
}
function render_welcome(string $email, ?string $name = null): array
{
    return render_email('welcome', $email, $name);
}

/** Sample details for previews and tests of the booking request email. */
function sample_enquiry(): array
{
    $t = val('SELECT title FROM tours WHERE active = 1 ORDER BY featured DESC, sort, id LIMIT 1') ?: '3-Day Maasai Mara Safari';
    return ['item' => $t, 'travel_date' => 'Mid December', 'travellers' => '4', 'message' => 'Two adults and two kids (8 and 11).'];
}

/** Sends the welcome email to a new subscriber (after the page has been answered). */
function queue_welcome(string $email, ?string $name = null): void
{
    if (!tpl_enabled('welcome') || !mail_ready()) return;
    after_response(function () use ($email, $name) {
        [$s, $h, $t] = render_email('welcome', $email, $name);
        $err = send_mail($email, $s, $h, $t, 'welcome');
        try {
            q('UPDATE subscribers SET welcomed_at = ? WHERE email = ?', [$err ? null : now(), $email]);
        } catch (Throwable $e) {
        }
    });
}

/** Sends the "we've got your request" email after the visitor has been sent to WhatsApp. */
function queue_enquiry(string $email, ?string $name, array $details): void
{
    if (!tpl_enabled('enquiry') || !mail_ready()) return;
    after_response(function () use ($email, $name, $details) {
        [$s, $h, $t] = render_email('enquiry', $email, $name, $details);
        send_mail($email, $s, $h, $t, 'enquiry', null, false);
    });
}
