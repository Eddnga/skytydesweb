<?php
declare(strict_types=1);

/**
 * Visitor insights: a light, private page-view log (no cookies, no outside
 * trackers) plus IP → location lookups that the super admin pages use.
 */

const VIEWS_KEEP_DAYS = 180;

function is_bot(string $ua): bool
{
    return $ua === '' || (bool) preg_match('/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|preview|monitor|curl|wget|python|httpclient|headless|lighthouse|pingdom|uptime|SkyTydes-selfcheck/i', $ua);
}

function device_type(string $ua): string
{
    if (preg_match('/iPad|Tablet|(Android(?!.*Mobile))/i', $ua)) return 'Tablet';
    if (preg_match('/Mobi|iPhone|Android/i', $ua)) return 'Mobile';
    return 'Desktop';
}

/** Called once at the end of every public page. Staff and bots are not counted. */
function track_view(string $title = ''): void
{
    $code = http_response_code();
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' || !in_array($code, [200, 404], true)) return;
    if ($code === 404) $title = '404 Not found';
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (is_bot($ua) || current_user()) return;
    try {
        $ip = client_ip();
        $ref = (string) parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_HOST);
        $own = (string) parse_url($GLOBALS['CONFIG']['base_url'] ?? '', PHP_URL_HOST);
        if ($ref !== '' && ($ref === $own || $ref === ($_SERVER['HTTP_HOST'] ?? ''))) $ref = '';
        $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if (BASE_PATH && str_starts_with($path, BASE_PATH)) $path = substr($path, strlen(BASE_PATH)) ?: '/';
        insert('page_views', [
            'path' => mb_substr($path, 0, 255),
            'title' => mb_substr($title, 0, 160) ?: null,
            'ip' => $ip,
            'visitor' => substr(hash('sha256', $ip . '|' . $ua . '|' . date('Y-m-d') . '|' . ($GLOBALS['CONFIG']['app_key'] ?? 'sky')), 0, 16),
            'referrer' => mb_substr(preg_replace('/^www\./', '', $ref), 0, 120) ?: null,
            'device' => device_type($ua),
            'browser' => explode(' on ', ua_summary($ua))[0],
            'ms' => isset($_SERVER['REQUEST_TIME_FLOAT']) ? (int) round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000) : null,
            'created_at' => now(),
        ]);
        // tidy up now and then
        if (random_int(1, 500) === 1) q('DELETE FROM page_views WHERE created_at < ?', [date('Y-m-d H:i:s', time() - VIEWS_KEEP_DAYS * 86400)]);
    } catch (Throwable $e) {
        // insights must never break the website
    }
}

function is_private_ip(?string $ip): bool
{
    if (!$ip) return true;
    return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
}

/** Looks up a few IP addresses we haven't located yet (cached forever after). */
function geo_resolve(array $ips, int $max = 25): void
{
    $ips = array_values(array_unique(array_filter($ips, fn($ip) => $ip && !is_private_ip($ip))));
    if (!$ips || !function_exists('curl_init')) return;
    $known = [];
    foreach (array_chunk($ips, 200) as $chunk) {
        $in = implode(',', array_fill(0, count($chunk), '?'));
        foreach (rows("SELECT ip FROM ip_geo WHERE ip IN ($in)", $chunk) as $r) $known[$r['ip']] = true;
    }
    $todo = array_slice(array_values(array_filter($ips, fn($ip) => !isset($known[$ip]))), 0, $max);
    $started = microtime(true);
    foreach ($todo as $ip) {
        if (microtime(true) - $started > 8) break; // never keep the page waiting long
        $g = geo_fetch($ip);
        if ($g === null) continue;
        try {
            insert('ip_geo', $g + ['ip' => $ip, 'looked_up_at' => now()]);
        } catch (Throwable $e) {
        }
    }
}

/** Free lookup services (no key needed): ipwho.is first, ip-api.com as a fallback. */
function geo_fetch(string $ip): ?array
{
    $get = function (string $url): ?array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 4, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_USERAGENT => 'SkyTydes-insights']);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200 || !is_string($body)) return null;
        $j = json_decode($body, true);
        return is_array($j) ? $j : null;
    };
    $j = $get('https://ipwho.is/' . rawurlencode($ip) . '?fields=success,country,country_code,region,city,connection');
    if ($j && !empty($j['success'])) {
        return ['country' => mb_substr((string) ($j['country'] ?? ''), 0, 80) ?: null, 'country_code' => substr((string) ($j['country_code'] ?? ''), 0, 2) ?: null,
            'region' => mb_substr((string) ($j['region'] ?? ''), 0, 80) ?: null, 'city' => mb_substr((string) ($j['city'] ?? ''), 0, 80) ?: null,
            'org' => mb_substr((string) ($j['connection']['org'] ?? $j['connection']['isp'] ?? ''), 0, 120) ?: null];
    }
    $j = $get('http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,country,countryCode,regionName,city,isp');
    if ($j && ($j['status'] ?? '') === 'success') {
        return ['country' => mb_substr((string) $j['country'], 0, 80) ?: null, 'country_code' => substr((string) $j['countryCode'], 0, 2) ?: null,
            'region' => mb_substr((string) $j['regionName'], 0, 80) ?: null, 'city' => mb_substr((string) $j['city'], 0, 80) ?: null,
            'org' => mb_substr((string) $j['isp'], 0, 120) ?: null];
    }
    return null;
}

/** ip → ['place' => 'Nairobi, Kenya', 'flag' => '🇰🇪', 'org' => 'Safaricom'] for a list of IPs. */
function geo_map(array $ips): array
{
    $ips = array_values(array_unique(array_filter($ips)));
    $out = [];
    foreach ($ips as $ip) if (is_private_ip($ip)) $out[$ip] = ['place' => in_array($ip, ['127.0.0.1', '::1'], true) ? 'This computer' : 'Local network', 'flag' => '', 'org' => ''];
    $public = array_values(array_filter($ips, fn($ip) => !isset($out[$ip])));
    foreach (array_chunk($public, 200) as $chunk) {
        $in = implode(',', array_fill(0, count($chunk), '?'));
        foreach (rows("SELECT * FROM ip_geo WHERE ip IN ($in)", $chunk) as $g) {
            $place = implode(', ', array_filter([$g['city'], $g['country']]));
            $out[$g['ip']] = ['place' => $place ?: 'Unknown', 'flag' => flag($g['country_code']), 'org' => (string) $g['org'], 'country' => (string) $g['country']];
        }
    }
    return $out;
}

function flag(?string $cc): string
{
    $cc = strtoupper((string) $cc);
    if (!preg_match('/^[A-Z]{2}$/', $cc)) return '';
    return mb_chr(0x1F1E6 + ord($cc[0]) - 65) . mb_chr(0x1F1E6 + ord($cc[1]) - 65);
}

/** "Nairobi, Kenya" (or the bare IP until it has been looked up). */
function place_of(?string $ip, array $geo): string
{
    if (!$ip) return '—';
    $g = $geo[$ip] ?? null;
    return $g ? trim($g['flag'] . ' ' . $g['place']) : $ip;
}
