<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
$me = require_role('admin');

// ---------- preview of the welcome email (opens in a new tab) ----------
if (input('preview') === 'welcome') {
    [$subj, $html] = render_welcome((string) $me['email'], (string) $me['name']);
    header("Content-Security-Policy: default-src 'none'; img-src * data:; style-src 'unsafe-inline'; frame-ancestors 'self'", true);
    echo $html;
    exit;
}

if (is_post()) {
    csrf_check();
    $do = input('do');

    if ($do === 'server') {
        $host = strtolower(trim(input('smtp_host')));
        $port = (int) input('smtp_port');
        $secure = in_array(input('smtp_secure'), ['ssl', 'tls'], true) ? input('smtp_secure') : 'ssl';
        $from = strtolower(trim(input('mail_from_email')));
        $reply = strtolower(trim(input('mail_reply_to')));
        if ($host !== '' && !preg_match('/^[a-z0-9.-]+$/', $host)) flash('error', 'The mail server name looks wrong (e.g. mail.skytydessafaris.com).');
        elseif ($from !== '' && !filter_var($from, FILTER_VALIDATE_EMAIL)) flash('error', 'The "send from" address is not a valid email.');
        elseif ($reply !== '' && !filter_var($reply, FILTER_VALIDATE_EMAIL)) flash('error', 'The reply-to address is not a valid email.');
        else {
            set_setting('smtp_host', $host);
            set_setting('smtp_port', (string) ($port > 0 && $port < 65536 ? $port : ($secure === 'ssl' ? 465 : 587)));
            set_setting('smtp_secure', $secure);
            set_setting('smtp_user', mb_substr(trim(input('smtp_user')), 0, 190));
            $pass = (string) ($_POST['smtp_pass'] ?? '');
            if ($pass !== '') set_setting('smtp_pass', encrypt_secret($pass));
            set_setting('mail_from_email', $from);
            set_setting('mail_from_name', mb_substr(trim(input('mail_from_name')), 0, 80));
            set_setting('mail_reply_to', $reply);
            audit('update', 'settings', null, ['changed' => ['mail server' . ($pass !== '' ? ' (password changed)' : '')]]);
            flash('ok', 'Mail server saved. Send yourself a test email to check it works.');
        }
    }

    if ($do === 'template') {
        foreach (['subject' => 200, 'preheader' => 200, 'heading' => 160, 'cta_label' => 60, 'wa_text' => 400, 'contacts' => 1000, 'signoff' => 300] as $k => $max) {
            set_setting('welcome_' . $k, mb_substr(trim(input('welcome_' . $k)), 0, $max));
        }
        set_setting('welcome_body', sanitize_html((string) ($_POST['welcome_body'] ?? '')));
        set_setting('welcome_enabled', isset($_POST['welcome_enabled']) ? '1' : '0');
        audit('update', 'settings', null, ['changed' => ['welcome email' . (isset($_POST['welcome_enabled']) ? '' : ' (turned off)')]]);
        flash('ok', 'Welcome email saved. Use Preview to see it, or send a test.');
    }

    if ($do === 'reset') {
        foreach (array_keys(welcome_defaults()) as $k) set_setting('welcome_' . $k, '');
        audit('update', 'settings', null, ['changed' => ['welcome email reset to the original text']]);
        flash('ok', 'Welcome email reset to the original text.');
    }

    if ($do === 'test') {
        $to = strtolower(trim(input('test_to')));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) flash('error', 'Enter a valid email address to send the test to.');
        else {
            [$s, $h, $t] = render_welcome($to, (string) $me['name']);
            $err = send_mail($to, '[Test] ' . $s, $h, $t, 'test');
            audit('email_test', 'settings', null, ['to' => $to, 'result' => $err ? 'failed' : 'sent']);
            $err ? flash('error', 'Test email failed: ' . $err) : flash('ok', "Test email sent to $to. If it isn't in the inbox within a minute, check the spam folder.");
        }
    }

    if ($do === 'catchup') {
        $todo = rows('SELECT email, name FROM subscribers WHERE welcomed_at IS NULL ORDER BY id LIMIT 40');
        $ok = 0; $bad = 0;
        @set_time_limit(180);
        foreach ($todo as $s) {
            [$sub, $h, $t] = render_welcome($s['email'], $s['name']);
            if (send_mail($s['email'], $sub, $h, $t, 'welcome') === null) {
                q('UPDATE subscribers SET welcomed_at = ? WHERE email = ?', [now(), $s['email']]);
                $ok++;
            } else $bad++;
        }
        audit('email_catchup', 'subscribers', null, ['added' => $ok, 'failed' => $bad]);
        flash($bad && !$ok ? 'error' : 'ok', "Welcome email sent to $ok subscriber(s)" . ($bad ? ", $bad failed — see the log below." : '.'));
    }
    redirect('admin/email.php');
}

$ready = mail_ready();
$pending = (int) val('SELECT COUNT(*) FROM subscribers WHERE welcomed_at IS NULL');
$sent30 = (int) val("SELECT COUNT(*) FROM email_log WHERE status = 'sent' AND created_at >= ?", [date('Y-m-d H:i:s', time() - 30 * 86400)]);
$failed30 = (int) val("SELECT COUNT(*) FROM email_log WHERE status = 'failed' AND created_at >= ?", [date('Y-m-d H:i:s', time() - 30 * 86400)]);
$log = rows('SELECT * FROM email_log ORDER BY id DESC LIMIT 40');
$def = welcome_defaults();
$v = fn(string $k) => setting('welcome_' . $k) !== '' ? setting('welcome_' . $k) : $def[$k];

admin_header('Emails', 'email');
?>
<div class="stats">
  <div class="stat card <?= $ready ? '' : 'hl' ?>"><?= icon('send', 22) ?><b><?= $ready ? 'Connected' : 'Not set up' ?></b><span>Mail server</span></div>
  <div class="stat card"><?= icon('check', 22) ?><b><?= welcome_enabled() ? 'On' : 'Off' ?></b><span>Welcome email</span></div>
  <div class="stat card"><?= icon('mail', 22) ?><b><?= $sent30 ?></b><span>Sent (30 days)</span></div>
  <div class="stat card"><?= icon('x', 22) ?><b><?= $failed30 ?></b><span>Failed (30 days)</span></div>
</div>

<div class="cols wide-first">
<section class="card">
  <div class="card-head"><h2>Welcome email</h2><a class="btn ghost sm" href="<?= url('admin/email.php?preview=welcome') ?>" target="_blank" rel="noopener"><?= icon('eye', 16) ?> Preview</a></div>
  <p class="muted small">Sent automatically when someone joins the newsletter on the website. You can use <code>{first_name}</code>, <code>{name}</code> and <code>{email}</code> anywhere below — they're filled in for each person ("there" when no name was given).</p>
  <form method="post" class="form">
    <?= csrf_field() ?><input type="hidden" name="do" value="template">
    <label class="check"><input type="checkbox" name="welcome_enabled" value="1" <?= welcome_enabled() ? 'checked' : '' ?>> Send this email to new subscribers</label>
    <label>Subject line<input name="welcome_subject" value="<?= h($v('subject')) ?>" maxlength="200" required></label>
    <label>Preview text <span class="opt">(the grey line inboxes show after the subject)</span><input name="welcome_preheader" value="<?= h($v('preheader')) ?>" maxlength="200"></label>
    <label>Heading<input name="welcome_heading" value="<?= h($v('heading')) ?>" maxlength="160"></label>
    <label>Message<textarea name="welcome_body" rows="10" class="rich" data-rich><?= h(editor_value($v('body'))) ?></textarea></label>
    <label>WhatsApp buttons <span class="opt">(one per line: Button text | phone number)</span><textarea name="welcome_contacts" rows="3"><?= h($v('contacts')) ?></textarea></label>
    <label>Message that opens in WhatsApp<input name="welcome_wa_text" value="<?= h($v('wa_text')) ?>" maxlength="400"></label>
    <div class="grid2">
      <label>Second button text <span class="opt">(links to your tours)</span><input name="welcome_cta_label" value="<?= h($v('cta_label')) ?>" maxlength="60"></label>
      <label>Sign-off<textarea name="welcome_signoff" rows="2"><?= h($v('signoff')) ?></textarea></label>
    </div>
    <div class="actions"><button class="btn"><?= icon('check', 18) ?> Save welcome email</button></div>
  </form>
  <form method="post" class="inline" data-confirm="Put back the original welcome email text? Your edits will be replaced."><?= csrf_field() ?><input type="hidden" name="do" value="reset"><button class="btn ghost sm">Reset to original text</button></form>
</section>

<div class="stack">
  <section class="card">
    <div class="card-head"><h2>Send a test</h2></div>
    <form method="post" class="form">
      <?= csrf_field() ?><input type="hidden" name="do" value="test">
      <label>Send the welcome email to<input type="email" name="test_to" value="<?= h($me['email']) ?>" required></label>
      <button class="btn" <?= $ready ? '' : 'disabled' ?>><?= icon('send', 18) ?> Send test email</button>
      <?php if (!$ready): ?><p class="hint">Set up the mail server below first.</p><?php endif; ?>
    </form>
  </section>

  <?php if ($pending && $ready): ?>
  <section class="card">
    <div class="card-head"><h2>Subscribers without a welcome</h2></div>
    <p class="muted small"><?= $pending ?> subscriber<?= $pending === 1 ? ' has' : 's have' ?> not received the welcome email (for example, people who joined before email was set up).</p>
    <form method="post" data-confirm="Send the welcome email to up to 40 of these subscribers now?"><?= csrf_field() ?><input type="hidden" name="do" value="catchup"><button class="btn ghost sm"><?= icon('send', 16) ?> Send to them now</button></form>
  </section>
  <?php endif; ?>

  <section class="card">
    <div class="card-head"><h2>Mail server</h2></div>
    <p class="muted small">Use the mailbox you created in MyPanel → <b>Emails</b> (for example info@skytydessafaris.com). The server name and port are in that mailbox's <b>email client / SMTP settings</b>.</p>
    <form method="post" class="form" autocomplete="off">
      <?= csrf_field() ?><input type="hidden" name="do" value="server">
      <label>Mail server (SMTP host)<input name="smtp_host" value="<?= h(setting('smtp_host')) ?>" placeholder="e.g. mail.skytydessafaris.com"></label>
      <div class="grid2">
        <label>Security<select name="smtp_secure"><option value="ssl" <?= setting('smtp_secure') !== 'tls' ? 'selected' : '' ?>>SSL (port 465)</option><option value="tls" <?= setting('smtp_secure') === 'tls' ? 'selected' : '' ?>>STARTTLS (port 587)</option></select></label>
        <label>Port<input name="smtp_port" inputmode="numeric" value="<?= h(setting('smtp_port') ?: '465') ?>"></label>
      </div>
      <label>Username <span class="opt">(usually the full email address)</span><input name="smtp_user" value="<?= h(setting('smtp_user')) ?>" placeholder="info@skytydessafaris.com" autocomplete="off"></label>
      <label>Password<input type="password" name="smtp_pass" placeholder="<?= setting('smtp_pass') !== '' ? '•••••••• saved — leave empty to keep' : 'Mailbox password' ?>" autocomplete="new-password"></label>
      <div class="grid2">
        <label>Send from (email)<input name="mail_from_email" value="<?= h(setting('mail_from_email')) ?>" placeholder="info@skytydessafaris.com"></label>
        <label>Send from (name)<input name="mail_from_name" value="<?= h(setting('mail_from_name') ?: 'SkyTydes Safaris') ?>"></label>
      </div>
      <label>Replies go to <span class="opt">(optional)</span><input name="mail_reply_to" value="<?= h(setting('mail_reply_to')) ?>" placeholder="Same as send-from"></label>
      <button class="btn"><?= icon('check', 18) ?> Save mail server</button>
      <p class="hint">The password is stored encrypted. "Send from" should be the same mailbox you log in with, so emails don't land in spam.</p>
    </form>
  </section>
</div>
</div>

<section class="card table-wrap">
  <div class="card-head"><h2>Email log</h2></div>
  <?php if (!$log): ?><p class="empty">No emails sent yet.</p><?php else: ?>
  <table class="table">
    <thead><tr><th>When</th><th>To</th><th>Subject</th><th>Result</th></tr></thead>
    <tbody>
    <?php foreach ($log as $l): ?>
      <tr><td data-l="When" class="nowrap small"><?= h(fdate($l['created_at'], 'j M, H:i')) ?></td><td data-l="To"><?= h($l['to_email']) ?></td><td data-l="Subject" class="small"><?= h($l['subject']) ?></td>
      <td data-l="Result"><?php if ($l['status'] === 'sent'): ?><span class="pill ok">Sent</span><?php else: ?><span class="pill warn">Failed</span><br><span class="small muted"><?= h((string) $l['error']) ?></span><?php endif; ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table><?php endif; ?>
</section>
<?php admin_footer();
