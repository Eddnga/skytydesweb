<?php
declare(strict_types=1);

const SESSION_IDLE_MINUTES = 30;
const LOGIN_MAX_FAILS = 5;
const LOGIN_LOCK_MINUTES = 15;

function current_user(): ?array
{
    static $u = false;
    if ($u !== false) return $u;
    $u = null;
    if (!empty($_SESSION['uid'])) {
        if (time() - ($_SESSION['last_seen'] ?? 0) > SESSION_IDLE_MINUTES * 60) {
            audit('session_expired', 'user', (int) $_SESSION['uid'], ['after_minutes' => SESSION_IDLE_MINUTES]);
            logout();
            return null;
        }
        // bind the session to the browser that created it
        if (($_SESSION['ua'] ?? '') !== hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '')) {
            audit('session_blocked', 'user', (int) $_SESSION['uid'], ['reason' => 'session used from a different browser']);
            logout();
            return null;
        }
        $_SESSION['last_seen'] = time();
        $u = row('SELECT id, name, email, role FROM users WHERE id = ? AND active = 1', [(int) $_SESSION['uid']]);
        if (!$u) logout();
    }
    return $u;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        redirect('admin/login.php');
    }
    return $u;
}

/** Roles, highest first: superadmin (owner, sees everything) → admin → editor. */
function role_rank(?string $role): int
{
    return ['superadmin' => 3, 'admin' => 2, 'editor' => 1][$role ?? ''] ?? 0;
}
function is_admin(?array $u = null): bool
{
    $u = $u ?? current_user();
    return $u && role_rank($u['role']) >= 2;
}
function is_super(?array $u = null): bool
{
    $u = $u ?? current_user();
    return $u && $u['role'] === 'superadmin';
}
function role_label(string $role): string
{
    return ['superadmin' => 'Super admin', 'admin' => 'Admin', 'editor' => 'Editor'][$role] ?? ucfirst($role);
}
function require_role(string $role): array
{
    $u = require_login();
    if (role_rank($u['role']) < role_rank($role)) {
        audit('access_denied', 'page', null, ['page' => strtok($_SERVER['REQUEST_URI'] ?? '', '?'), 'needs' => role_label($role)]);
        http_response_code(403);
        exit($role === 'superadmin' ? 'Only the super admin can open this page.' : 'Only administrators can open this page.');
    }
    return $u;
}

function login_locked(string $email): bool
{
    $since = date('Y-m-d H:i:s', time() - LOGIN_LOCK_MINUTES * 60);
    $byIp = (int) val('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND success = 0 AND attempted_at >= ?', [client_ip(), $since]);
    $byEmail = (int) val('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND success = 0 AND attempted_at >= ?', [strtolower($email), $since]);
    return $byIp >= LOGIN_MAX_FAILS * 2 || $byEmail >= LOGIN_MAX_FAILS;
}

function attempt_login(string $email, string $password): bool|string
{
    $email = strtolower(trim($email));
    if (login_locked($email)) return 'locked';
    $u = row('SELECT * FROM users WHERE email = ? AND active = 1', [$email]);
    // always run a hash check so response time does not reveal valid emails
    $hash = $u['password_hash'] ?? '$2y$10$GiRYQ1f9YfYiB2ODaIrebOhHohaPUMzhnJkO6x2.Bbz44XIeIUqfC';
    $ok = password_verify($password, $hash) && $u;
    insert('login_attempts', ['email' => $email, 'ip' => client_ip(), 'success' => $ok ? 1 : 0, 'attempted_at' => now()]);
    if (!$ok) {
        audit('login_failed', 'user', $u ? (int) $u['id'] : null, ['email' => $email, 'reason' => $u ? 'wrong password' : 'unknown email']);
        if (login_locked($email)) audit('login_locked', 'user', $u ? (int) $u['id'] : null, ['email' => $email, 'minutes' => LOGIN_LOCK_MINUTES]);
        return false;
    }

    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $u['id']]);
    }
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    $_SESSION['last_seen'] = time();
    $_SESSION['ua'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
    unset($_SESSION['csrf']);
    q('UPDATE users SET last_login_at = ? WHERE id = ?', [now(), $u['id']]);
    audit('login', 'user', (int) $u['id'], ['browser' => ua_summary()]);
    return true;
}

function logout(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

function password_problems(string $p): ?string
{
    if (strlen($p) < 10) return 'Use at least 10 characters.';
    if (!preg_match('/[A-Za-z]/', $p) || !preg_match('/\d/', $p)) return 'Mix letters and numbers.';
    return null;
}
