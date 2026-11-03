
<?php

/**
 * =====================================================
 * SIAGAKAMPUNG
 * AUTHENTICATION & AUTHORIZATION
 * Version : 1.0 MVP
 * =====================================================
 */


// =====================================================
// 1. START SESSION
// =====================================================

function siaga_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    if (headers_sent($file, $line)) {

        error_log(
            '[SIAGAKAMPUNG] Session cannot start. ' .
            'Output started at ' . $file . ':' . $line
        );

        http_response_code(500);

        exit('Session error. Silakan hubungi administrator.');

    }

    // Session security configuration

    ini_set('session.use_strict_mode', '1');

    ini_set('session.use_only_cookies', '1');

    ini_set('session.cookie_httponly', '1');

    ini_set('session.cookie_samesite', 'Lax');


    // Detect HTTPS

    $secure = (
        isset($_SERVER['HTTPS']) &&
        $_SERVER['HTTPS'] !== 'off'
    );


    // Set unique session name

    session_name('SIAGAKAMPUNGSESSID');


    // Configure session cookie

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);


    // Start session

    if (!session_start()) {

        http_response_code(500);

        exit('Gagal memulai session.');

    }
}


// Start session when this file is included

siaga_start_session();


// =====================================================
// 2. USER ROLE CONFIGURATION
// =====================================================

function siaga_allowed_roles(): array
{
    return [
        'admin',
        'coordinator',
        'volunteer'
    ];
}


// =====================================================
// 3. CHECK LOGIN STATUS
// =====================================================

function siaga_is_logged_in(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }

    $userId = filter_var(
        $_SESSION['user_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $userRole = $_SESSION['user_role'] ?? '';

    return (
        $userId !== false &&
        $userId > 0 &&
        in_array(
            $userRole,
            siaga_allowed_roles(),
            true
        )
    );
}


// =====================================================
// 4. GET CURRENT USER
// =====================================================

function siaga_current_user(): ?array
{
    if (!siaga_is_logged_in()) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['user_id'],

        'name' => (string) (
            $_SESSION['user_name'] ?? ''
        ),

        'email' => (string) (
            $_SESSION['user_email'] ?? ''
        ),

        'role' => (string) (
            $_SESSION['user_role'] ?? ''
        )
    ];
}


// =====================================================
// 5. PROCESS USER LOGIN
// =====================================================

function siaga_login_user(array $user): bool
{
    // Validate user ID

    $userId = filter_var(
        $user['id'] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($userId === false || $userId < 1) {
        return false;
    }


    // Validate role

    $role = strtolower(
        trim((string) ($user['role'] ?? ''))
    );

    if (!in_array(
        $role,
        siaga_allowed_roles(),
        true
    )) {
        return false;
    }


    // Regenerate session ID to prevent fixation

    if (!session_regenerate_id(true)) {
        return false;
    }


    // Store authenticated user information

    $_SESSION['user_id'] = $userId;

    $_SESSION['user_name'] = trim(
        (string) ($user['name'] ?? '')
    );

    $_SESSION['user_email'] = trim(
        (string) ($user['email'] ?? '')
    );

    $_SESSION['user_role'] = $role;

    $_SESSION['logged_in_at'] = time();


    return true;
}


// =====================================================
// 6. DETERMINE PROJECT ROOT URL
// =====================================================

function siaga_project_base_url(): string
{
    $scriptName = str_replace(
        '\\',
        '/',
        $_SERVER['SCRIPT_NAME'] ?? '/'
    );

    // Remove known internal directories

    $baseUrl = preg_replace(
        '~/(actions|includes|pages)/[^/]*$~',
        '',
        $scriptName
    );

    // If no internal directory was removed,
    // use the directory of the current page.

    if ($baseUrl === $scriptName) {
        $baseUrl = dirname($scriptName);
    }

    $baseUrl = rtrim($baseUrl, '/');

    return $baseUrl === '.' ? '' : $baseUrl;
}


// =====================================================
// 7. REQUIRE LOGIN
// =====================================================

function siaga_require_login(
    ?string $loginUrl = null
): void {

    if (siaga_is_logged_in()) {
        return;
    }


    // Determine login page URL

    if ($loginUrl === null) {

        $baseUrl = siaga_project_base_url();

        $loginUrl = $baseUrl . '/login.php';

    }


    // Redirect unauthorized visitor

    header('Location: ' . $loginUrl);

    exit;

}


// =====================================================
// 8. CHECK USER ROLE
// =====================================================

function siaga_has_role($roles): bool
{
    if (!siaga_is_logged_in()) {
        return false;
    }

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    $currentRole = $_SESSION['user_role'];

    return in_array(
        $currentRole,
        $roles,
        true
    );
}


// =====================================================
// 9. REQUIRE SPECIFIC ROLE
// =====================================================

function siaga_require_role($roles): void
{
    // User must be logged in first

    siaga_require_login();


    // Check role permission

    if (!siaga_has_role($roles)) {

        http_response_code(403);

        header(
            'Content-Type: text/plain; charset=UTF-8'
        );

        exit(
            '403 Forbidden: ' .
            'Anda tidak memiliki hak akses ' .
            'untuk halaman ini.'
        );

    }

}


// =====================================================
// 10. LOGOUT USER
// =====================================================

function siaga_logout_user(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }


    // Remove all session data

    $_SESSION = [];


    // Expire session cookie

    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax'
            ]
        );

    }


    // Destroy session

    session_destroy();

}


// =====================================================
// 11. AUTHENTICATION READY
// =====================================================
//
// Available functions:
//
// siaga_is_logged_in()
// siaga_current_user()
// siaga_login_user($user)
// siaga_require_login()
// siaga_has_role($roles)
// siaga_require_role($roles)
// siaga_logout_user()
//
// Allowed roles:
//
// admin
// coordinator
// volunteer
//
// =====================================================
