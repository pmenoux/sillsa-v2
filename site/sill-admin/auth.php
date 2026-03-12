<?php
// sill-admin/auth.php — Authentication, CSRF, rate limiting
// SILL SA v2 — PHP 8.2 vanilla

session_start();

const AUTH_TIMEOUT    = 1800; // 30 minutes
const RATE_MAX        = 5;    // max attempts
const RATE_WINDOW     = 900;  // 15 minutes in seconds

// ---------------------------------------------------------------------------
// Session auth
// ---------------------------------------------------------------------------

/**
 * Require authentication. Redirect to login on failure or timeout.
 */
function requireAuth(): void
{
    if (empty($_SESSION['admin_user_id'])) {
        header('Location: ?page=login');
        exit;
    }

    // 30-minute inactivity timeout
    if (!empty($_SESSION['admin_last_active']) && (time() - $_SESSION['admin_last_active']) > AUTH_TIMEOUT) {
        session_unset();
        session_destroy();
        header('Location: ?page=login&timeout=1');
        exit;
    }

    $_SESSION['admin_last_active'] = time();
}

/**
 * Attempt login. Returns true on success, false on failure.
 */
function attemptLogin(string $username, string $password): bool
{
    if (isRateLimited($username)) {
        return false;
    }

    $user = queryOne(
        'SELECT id, username, password_hash FROM sill_users WHERE username = ? AND is_active = 1',
        [$username]
    );

    if (!$user || !password_verify($password, $user['password_hash'])) {
        recordLoginAttempt($username);
        return false;
    }

    // Success
    clearLoginAttempts($username);
    session_regenerate_id(true);

    $_SESSION['admin_user_id']    = $user['id'];
    $_SESSION['admin_username']   = $user['username'];
    $_SESSION['admin_last_active'] = time();

    return true;
}

/**
 * Destroy session and redirect to login.
 */
function logout(): void
{
    session_unset();
    session_destroy();
    header('Location: ?page=login');
    exit;
}

// ---------------------------------------------------------------------------
// CSRF
// ---------------------------------------------------------------------------

/**
 * Generate or return the current CSRF token (stored in session).
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate the _csrf field from POST against the session token.
 * Terminates with 403 on failure.
 */
function csrfCheck(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        exit('CSRF validation failed.');
    }
}

/**
 * Return a hidden <input> containing the CSRF token.
 */
function csrfField(): string
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

// ---------------------------------------------------------------------------
// Rate limiting
// ---------------------------------------------------------------------------

/**
 * Check if a username is rate-limited (5 failed attempts within 15 min).
 */
function isRateLimited(string $username): bool
{
    $ip        = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $since     = date('Y-m-d H:i:s', time() - RATE_WINDOW);

    $count = queryOne(
        'SELECT COUNT(*) AS cnt
           FROM sill_login_attempts
          WHERE (username = ? OR ip_address = ?)
            AND attempted_at > ?',
        [$username, $ip, $since]
    );

    return ($count['cnt'] ?? 0) >= RATE_MAX;
}

/**
 * Record a failed login attempt.
 */
function recordLoginAttempt(string $username): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    db()->prepare(
        'INSERT INTO sill_login_attempts (username, ip_address, attempted_at)
         VALUES (?, ?, NOW())'
    )->execute([$username, $ip]);
}

/**
 * Clear all login attempts for a username.
 */
function clearLoginAttempts(string $username): void
{
    db()->prepare(
        'DELETE FROM sill_login_attempts WHERE username = ?'
    )->execute([$username]);
}

// ---------------------------------------------------------------------------
// Flash messages
// ---------------------------------------------------------------------------

/**
 * Store a flash message in the session.
 * $type: 'success' | 'error' | 'info' | 'warning'
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieve and clear the flash message from the session.
 * Returns null if no flash message is set.
 */
function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
