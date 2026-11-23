<?php
declare(strict_types=1);

function h($s): string
{
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function url(string $path = ''): string
{
    return BASE_PATH . '/' . ltrim($path, '/');
}
function abs_url(string $path = ''): string
{
    global $CONFIG;
    return rtrim($CONFIG['base_url'], '/') . '/' . ltrim($path, '/');
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
function audit(string $action, string $entity = '', ?int $id = null): void
{
    try {
        insert('audit_log', ['user_id' => $_SESSION['uid'] ?? null, 'action' => $action, 'entity' => $entity, 'entity_id' => $id, 'ip' => client_ip(), 'created_at' => now()]);
    } catch (Throwable $e) {
    }
}
