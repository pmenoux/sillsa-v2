<?php
// sill-admin/auth.php — Authentication, CSRF, rate limiting
// SILL SA v2 — PHP 8.2 vanilla

session_start();

// Load Azure AD config if available
if (file_exists(__DIR__ . '/azure-config.php')) {
    require_once __DIR__ . '/azure-config.php';
}

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
        'SELECT id, username, password_hash, role FROM sill_users WHERE username = ? AND is_active = 1',
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
    $_SESSION['admin_role']       = $user['role'] ?? 'editor';
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

// ---------------------------------------------------------------------------
// Role helpers
// ---------------------------------------------------------------------------

/**
 * Check if current user can delete (admin role only).
 */
function canDelete(): bool
{
    return ($_SESSION['admin_role'] ?? 'editor') === 'admin';
}

// ---------------------------------------------------------------------------
// Azure AD (Entra ID) OAuth2
// ---------------------------------------------------------------------------

/**
 * Build the Azure AD authorization URL and redirect.
 */
function azureRedirect(): void
{
    if (!defined('AZURE_CLIENT_ID')) {
        flash('error', 'Azure AD non configuré.');
        header('Location: ?page=login');
        exit;
    }

    $state = bin2hex(random_bytes(16));
    $_SESSION['azure_state'] = $state;

    $params = http_build_query([
        'client_id'     => AZURE_CLIENT_ID,
        'response_type' => 'code',
        'redirect_uri'  => AZURE_REDIRECT_URI,
        'response_mode' => 'query',
        'scope'         => 'openid profile email User.Read GroupMember.Read.All',
        'state'         => $state,
    ]);

    $url = 'https://login.microsoftonline.com/' . AZURE_TENANT_ID . '/oauth2/v2.0/authorize?' . $params;
    header('Location: ' . $url);
    exit;
}

/**
 * Handle the Azure AD callback: exchange code for token, fetch profile + groups, create session.
 */
function azureCallback(): void
{
    // Debug log (temporary)
    $logfile = __DIR__ . '/azure-debug.log';
    $log = function(string $msg) use ($logfile) {
        file_put_contents($logfile, date('H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
    };
    $log('=== CALLBACK START ===');
    $log('GET params: ' . json_encode($_GET));
    $log('Session ID: ' . session_id());
    $log('Session azure_state: ' . ($_SESSION['azure_state'] ?? 'EMPTY'));

    // Validate state
    $state = $_GET['state'] ?? '';
    if (empty($state) || $state !== ($_SESSION['azure_state'] ?? '')) {
        $log('STATE MISMATCH — expected: ' . ($_SESSION['azure_state'] ?? 'EMPTY') . ' got: ' . $state);
        flash('error', 'Erreur de sécurité (state invalide). Session ID: ' . session_id());
        header('Location: ?page=login');
        exit;
    }
    unset($_SESSION['azure_state']);

    // Check for errors from Azure
    if (!empty($_GET['error'])) {
        $desc = $_GET['error_description'] ?? $_GET['error'];
        flash('error', 'Erreur Microsoft : ' . $desc);
        header('Location: ?page=login');
        exit;
    }

    $code = $_GET['code'] ?? '';
    if ($code === '') {
        flash('error', 'Code d\'autorisation manquant.');
        header('Location: ?page=login');
        exit;
    }

    // Exchange code for token
    $tokenUrl = 'https://login.microsoftonline.com/' . AZURE_TENANT_ID . '/oauth2/v2.0/token';
    $tokenData = [
        'client_id'     => AZURE_CLIENT_ID,
        'client_secret' => AZURE_CLIENT_SECRET,
        'code'          => $code,
        'redirect_uri'  => AZURE_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
        'scope'         => 'openid profile email User.Read GroupMember.Read.All',
    ];

    $tokenResponse = azurePost($tokenUrl, $tokenData);
    $log('Token response keys: ' . json_encode(array_keys($tokenResponse ?? [])));
    if (!$tokenResponse || empty($tokenResponse['access_token'])) {
        $err = $tokenResponse['error_description'] ?? $tokenResponse['error'] ?? 'Échec de l\'échange du token.';
        $log('TOKEN FAIL: ' . $err);
        flash('error', 'Erreur Azure token : ' . $err);
        header('Location: ?page=login');
        exit;
    }

    $accessToken = $tokenResponse['access_token'];
    $log('Token OK');

    // Fetch user profile
    $profile = azureGraphGet('https://graph.microsoft.com/v1.0/me', $accessToken);
    $log('Profile: ' . json_encode($profile));
    if (!$profile || (empty($profile['mail']) && empty($profile['userPrincipalName']))) {
        flash('error', 'Impossible de récupérer le profil Microsoft. Réponse: ' . json_encode($profile));
        header('Location: ?page=login');
        exit;
    }

    // Fetch group memberships (with pagination)
    $groupIds = [];
    $groupUrl = 'https://graph.microsoft.com/v1.0/me/memberOf?$select=id,displayName';
    $pageCount = 0;
    while ($groupUrl && $pageCount < 10) {
        $groups = azureGraphGet($groupUrl, $accessToken);
        if (!empty($groups['value'])) {
            foreach ($groups['value'] as $g) {
                $groupIds[] = $g['id'] ?? '';
            }
        }
        $groupUrl = $groups['@odata.nextLink'] ?? null;
        $pageCount++;
    }
    $log('Total groups fetched: ' . count($groupIds) . ' (pages: ' . $pageCount . ')');

    // Determine role from groups
    $role = null;
    if (in_array(AZURE_GROUP_ADMIN, $groupIds, true)) {
        $role = 'admin';
    } elseif (in_array(AZURE_GROUP_EDITOR, $groupIds, true)) {
        $role = 'editor';
    }

    $log('Group IDs found: ' . json_encode($groupIds) . ' → role: ' . ($role ?? 'NULL'));

    if ($role === null) {
        flash('error', 'Accès refusé. Votre compte n\'est dans aucun groupe autorisé. Groupes trouvés: ' . json_encode($groupIds));
        header('Location: ?page=login');
        exit;
    }

    // Auto-provision or update user in sill_users
    $email       = strtolower($profile['mail'] ?? $profile['userPrincipalName'] ?? '');
    $displayName = $profile['displayName'] ?? $email;
    $username    = explode('@', $email)[0]; // e.g. sylvie.traimond

    $existing = queryOne('SELECT id, role FROM sill_users WHERE email = ?', [$email]);

    if ($existing) {
        query(
            'UPDATE sill_users SET display_name = ?, role = ?, last_login = NOW() WHERE id = ?',
            [$displayName, $role, $existing['id']]
        );
        $userId = $existing['id'];
    } else {
        query(
            'INSERT INTO sill_users (username, password_hash, display_name, email, role, last_login, is_active)
             VALUES (?, ?, ?, ?, ?, NOW(), 1)',
            [$username, '', $displayName, $email, $role]
        );
        $userId = db()->lastInsertId();
    }

    // Create session
    session_regenerate_id(true);
    $_SESSION['admin_user_id']    = $userId;
    $_SESSION['admin_username']   = $displayName;
    $_SESSION['admin_role']       = $role;
    $_SESSION['admin_last_active'] = time();

    flash('success', 'Bienvenue, ' . $displayName . ' (' . $role . ').');
    header('Location: ?page=dashboard');
    exit;
}

/**
 * POST request to Azure endpoint. Returns decoded JSON or null.
 */
function azurePost(string $url, array $data): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode >= 500) {
        return null;
    }
    return json_decode($response, true);
}

/**
 * GET request to Microsoft Graph API. Returns decoded JSON or null.
 */
function azureGraphGet(string $url, string $accessToken): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        return null;
    }
    return json_decode($response, true);
}
