<?php
declare(strict_types=1);

function h($s): string
{
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
// ---------- clean URLs ----------
// Public pages that get a clean address: /tours instead of /tours.php
const PRETTY_PAGES = ['tours', 'destinations', 'offers', 'gallery', 'blog', 'reviews', 'about', 'contact', 'services', 'faqs', 'documents'];
// Detail pages: tour.php?slug=x → /tours/x
const PRETTY_DETAIL = ['tour' => 'tours', 'destination' => 'destinations', 'post' => 'blog'];

function pretty_on(): bool
{
    return (settings()['pretty_urls'] ?? '') === '1';
}
/** Turns 'tour.php?slug=big-five#days' into 'tours/big-five#days' when clean URLs are on. */
function pretty_path(string $path): string
{
    $path = ltrim($path, '/');
    if (!pretty_on() || !preg_match('#^([a-z]+)\.php(.*)$#', $path, $m)) return $path;
    [, $page, $rest] = $m;
    if ($page === 'index') return $rest;
    if ($page === 'section' && preg_match('#^\?type=([a-z0-9_]+)(?:&slug=([^&\#]+))?(.*)$#', $rest, $r)) {
        $tail = $r[3];
        if ($tail !== '' && $tail[0] === '&') $tail = '?' . substr($tail, 1);
        return 'sections/' . $r[1] . ($r[2] !== '' ? '/' . $r[2] : '') . $tail;
    }
    if (isset(PRETTY_DETAIL[$page]) && preg_match('#^\?slug=([^&\#]+)(.*)$#', $rest, $r)) {
        $tail = $r[2];
        if ($tail !== '' && $tail[0] === '&') $tail = '?' . substr($tail, 1);
        return PRETTY_DETAIL[$page] . '/' . $r[1] . $tail;
    }
    if (in_array($page, PRETTY_PAGES, true)) return $page . $rest;
    return $path;
}
function url(string $path = ''): string
{
    return BASE_PATH . '/' . pretty_path($path);
}
function abs_url(string $path = ''): string
{
    global $CONFIG;
    return rtrim($CONFIG['base_url'], '/') . '/' . pretty_path($path);
}
/** Old address (/tours.php, /tour.php?slug=x) → permanent redirect to the clean one. */
function pretty_redirect(): void
{
    if (!pretty_on() || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $path = (string) parse_url($uri, PHP_URL_PATH);
    if (BASE_PATH && str_starts_with($path, BASE_PATH)) $path = substr($path, strlen(BASE_PATH));
    if (!preg_match('#^/([a-z]+)\.php$#', $path, $m)) return;
    if ($m[1] !== 'index' && $m[1] !== 'section' && !in_array($m[1], PRETTY_PAGES, true) && !isset(PRETTY_DETAIL[$m[1]])) return;
    $q = (string) parse_url($uri, PHP_URL_QUERY);
    $to = pretty_path($m[1] . '.php' . ($q !== '' ? '?' . $q : ''));
    if (str_ends_with($to, '.php') || str_contains($to, '.php?')) return; // nothing to tidy (e.g. tour.php without slug)
    header('Location: ' . BASE_PATH . '/' . $to, true, 301);
    exit;
}
/**
 * Checks that the server understands the clean-URL rules (it asks itself for /tours).
 * Turns clean URLs on when it works, off when it doesn't.
 */
function check_pretty_urls(): bool
{
    global $CONFIG;
    $ok = false;
    $base = rtrim($CONFIG['base_url'] ?? '', '/');
    if ($base !== '' && function_exists('curl_init')) {
        $ch = curl_init($base . '/tours');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 6, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_FOLLOWLOCATION => false, CURLOPT_USERAGENT => 'SkyTydes-selfcheck']);
        $body = (string) curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $ok = $code === 200 && str_contains($body, 'class="nav"');
    }
    set_setting('pretty_urls', $ok ? '1' : '0');
    set_setting('pretty_checked_at', now());
    return $ok;
}
function asset(string $path): string
{
    $file = ROOT . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? substr((string) filemtime($file), -6) : '1';
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}
function upload_url(?string $file, string $size = 'lg'): string
{
    if (!$file) return asset('img/placeholder.svg');
    if (strpos($file, 'seed/') === 0) return url('uploads/' . $file);
    $f = $size === 'sm' ? preg_replace('/\.(\w+)$/', '_sm.$1', $file) : $file;
    return url('uploads/' . $f);
}
function redirect(string $to): void
{
    header('Location: ' . (preg_match('#^https?://#', $to) ? $to : url($to)));
    exit;
}
function flash(string $type, ?string $msg = null)
{
    if ($msg === null) {
        $m = $_SESSION['flash'][$type] ?? null;
        unset($_SESSION['flash'][$type]);
        return $m;
    }
    $_SESSION['flash'][$type] = $msg;
}

// ---------- CSRF ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}
function csrf_check(): void
{
    $t = $_POST['_csrf'] ?? '';
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        audit('csrf_blocked', 'form', null, ['page' => strtok($_SERVER['REQUEST_URI'] ?? '', '?')]);
        http_response_code(419);
        exit('Your session expired. Please go back, refresh the page and try again.');
    }
}
function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

// ---------- settings ----------
function settings(): array
{
    if (!isset($GLOBALS['__settings'])) {
        $GLOBALS['__settings'] = [];
        try {
            foreach (rows('SELECT k, v FROM settings') as $r) $GLOBALS['__settings'][$r['k']] = $r['v'];
        } catch (Throwable $e) {
        }
    }
    return $GLOBALS['__settings'];
}
function setting(string $k, string $default = ''): string
{
    $s = settings();
    return isset($s[$k]) && $s[$k] !== '' ? $s[$k] : $default;
}
function set_setting(string $k, string $v): void
{
    if (val('SELECT COUNT(*) FROM settings WHERE k = ?', [$k])) q('UPDATE settings SET v = ? WHERE k = ?', [$v, $k]);
    else q('INSERT INTO settings (k, v) VALUES (?, ?)', [$k, $v]);
    if (isset($GLOBALS['__settings'])) $GLOBALS['__settings'][$k] = $v;
}

/** Escape a heading and turn *word* into an italic gold accent, e.g. "Current *special offers*". */
function hl(?string $s): string
{
    return preg_replace('/\*(.+?)\*/', '<em>$1</em>', h($s));
}

// ---------- text ----------
function slugify(string $s): string
{
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $s) ?: $s), '-'));
    return $s ?: 'item';
}
function unique_slug(string $table, string $title, ?int $exceptId = null): string
{
    $base = slugify($title);
    $slug = $base;
    $i = 2;
    while (val("SELECT COUNT(*) FROM $table WHERE slug = ? AND id <> ?", [$slug, $exceptId ?? 0])) $slug = $base . '-' . $i++;
    return $slug;
}
function lines(?string $s): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $s)), 'strlen'));
}
function excerpt(?string $s, int $n = 160): string
{
    $s = trim(preg_replace('/\s+/', ' ', strip_tags(str_replace(['#', '*', '_'], '', (string) $s))));
    return mb_strlen($s) > $n ? rtrim(mb_substr($s, 0, $n - 1)) . '…' : $s;
}
function money($amount, string $cur = 'KES'): string
{
    if ($amount === null || $amount === '' || (float) $amount <= 0) return '';
    return $cur . ' ' . number_format((float) $amount, 0);
}
function fdate(?string $d, string $fmt = 'j M Y'): string
{
    return $d ? date($fmt, strtotime($d)) : '';
}

// ---------- WhatsApp ----------
function wa_number(): string
{
    return preg_replace('/\D+/', '', setting('whatsapp', '254723022702'));
}
function wa_link(string $msg = ''): string
{
    return 'https://wa.me/' . wa_number() . ($msg !== '' ? '?text=' . rawurlencode($msg) : '');
}

// ---------- request ----------
function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}
/** Simple sliding-window limiter. Returns false when the limit is exceeded. */
function throttle(string $key, int $max, int $seconds): bool
{
    $since = date('Y-m-d H:i:s', time() - $seconds);
    q('DELETE FROM throttle WHERE created_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
    $n = (int) val('SELECT COUNT(*) FROM throttle WHERE k = ? AND created_at >= ?', [$key, $since]);
    if ($n >= $max) return false;
    q('INSERT INTO throttle (k, created_at) VALUES (?, ?)', [$key, now()]);
    return true;
}
function input(string $k, string $default = ''): string
{
    $v = $_POST[$k] ?? $_GET[$k] ?? $default;
    return is_string($v) ? trim($v) : $default;
}
/**
 * Records something in the activity log. $details is a small array of facts
 * (the item's name, which fields changed, the email tried…) kept as JSON.
 */
function audit(string $action, string $entity = '', ?int $id = null, ?array $details = null): void
{
    try {
        $row = ['user_id' => $_SESSION['uid'] ?? null, 'action' => mb_substr($action, 0, 60), 'entity' => mb_substr($entity, 0, 60), 'entity_id' => $id, 'ip' => client_ip(), 'created_at' => now()];
        if ($details) $row['details'] = mb_substr(json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 4000);
        insert('audit_log', $row);
    } catch (Throwable $e) {
        // before v5 the details column doesn't exist yet
        if ($details) {
            try {
                insert('audit_log', ['user_id' => $_SESSION['uid'] ?? null, 'action' => $action, 'entity' => $entity, 'entity_id' => $id, 'ip' => client_ip(), 'created_at' => now()]);
            } catch (Throwable $e) {
            }
        }
    }
}
/** "Chrome on Android", "Safari on iPhone"… */
function ua_summary(?string $ua = null): string
{
    $ua = $ua ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
    $b = 'Browser';
    foreach (['Edg/' => 'Edge', 'OPR/' => 'Opera', 'SamsungBrowser' => 'Samsung Internet', 'Brave' => 'Brave', 'Firefox/' => 'Firefox', 'Chrome/' => 'Chrome', 'Safari/' => 'Safari'] as $k => $n) {
        if (stripos($ua, $k) !== false) { $b = $n; break; }
    }
    $os = 'unknown device';
    foreach (['iPhone' => 'iPhone', 'iPad' => 'iPad', 'Android' => 'Android', 'Windows' => 'Windows', 'Mac OS X' => 'Mac', 'Linux' => 'Linux'] as $k => $n) {
        if (stripos($ua, $k) !== false) { $os = $n; break; }
    }
    return "$b on $os";
}

/**
 * Checks the server's PHP against the November 2026 security releases
 * (fixes PHP2Shell CVE-2026-109618/109619 and the 19 Nov 2026 critical bugs).
 * Returns null when patched, otherwise a warning for the admin.
 */
function php_patch_warning(?string $v = null): ?string
{
    $v = $v ?? PHP_VERSION;
    $min = ['8.2' => '8.2.36', '8.3' => '8.3.37', '8.4' => '8.4.28', '8.5' => '8.5.13'];
    $branch = implode('.', array_slice(explode('.', $v), 0, 2));
    if (version_compare($v, '8.6.0', '>=')) return null;
    if (!isset($min[$branch])) {
        return "This server runs PHP $v, which no longer gets security fixes. Switch the site to PHP 8.4 in your hosting panel.";
    }
    if (version_compare($v, $min[$branch], '<')) {
        return "This server runs PHP $v, which is missing critical security patches. Ask your host to update to PHP {$min[$branch]} or newer" . ($branch === '8.4' ? '.' : ' (or switch to PHP 8.4).');
    }
    return null;
}
