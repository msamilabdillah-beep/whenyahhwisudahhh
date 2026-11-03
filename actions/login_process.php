
<?php

/**
 * =====================================================
 * SIAGAKAMPUNG
 * LOGIN PROCESS
 * Version : 1.0 MVP
 * =====================================================
 */




// =====================================================
// 1. LOAD CONFIGURATION AND AUTHENTICATION
// =====================================================

require_once __DIR__ . '/../includes/config.php';

require_once __DIR__ . '/../includes/auth.php';


// =====================================================
// 2. SECURITY HEADERS
// =====================================================

header('X-Content-Type-Options: nosniff');

header('Cache-Control: no-store, no-cache, must-revalidate');


// =====================================================
// 3. PROJECT BASE URL
// =====================================================

$baseUrl = siaga_project_base_url();


// =====================================================
// 4. REDIRECT HELPERS
// =====================================================

$redirectToLogin = static function (
    string $message
) use ($baseUrl): void {

    $_SESSION['login_error'] = $message;

    header(
        'Location: ' . $baseUrl . '/login.php',
        true,
        303
    );

    exit;

};


$redirectByRole = static function (
    string $role
) use ($baseUrl): void {

    // Coordinator and admin go to dashboard.
    // Volunteer goes to community tasks.

    if ($role === 'volunteer') {

        $destination = '/community-tasks.php';

    } else {

        $destination = '/dashboard.php';

    }


    header(
        'Location: ' . $baseUrl . $destination,
        true,
        303
    );

    exit;

};


// =====================================================
// 5. CHECK EXISTING LOGIN SESSION
// =====================================================

if (siaga_is_logged_in()) {

    $currentUser = siaga_current_user();

    $redirectByRole($currentUser['role']);

}


// =====================================================
// 6. CHECK REQUEST METHOD
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    header('Allow: POST');

    exit('Method Not Allowed.');

}


// =====================================================
// 7. GET FORM INPUT
// =====================================================

$email = strtolower(
    trim((string) ($_POST['email'] ?? ''))
);

$password = (string) (
    $_POST['password'] ?? ''
);


// =====================================================
// 8. SERVER-SIDE VALIDATION
// =====================================================

// Email must not be empty.

if ($email === '') {

    $redirectToLogin(
        'Email wajib diisi.'
    );

}


// Validate email format.

if (
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($email) > 100
) {

    $redirectToLogin(
        'Email atau password tidak valid.'
    );

}


// Password must not be empty.

if ($password === '') {

    $redirectToLogin(
        'Password wajib diisi.'
    );

}


// Prevent excessively large password input.

if (strlen($password) > 4096) {

    $redirectToLogin(
        'Email atau password tidak valid.'
    );

}


// =====================================================
// 9. DATABASE AUTHENTICATION
// =====================================================

try {

    // Prepared statement

    $stmt = $conn->prepare(
        "SELECT
            id,
            name,
            email,
            password,
            role
         FROM users
         WHERE email = ?
         LIMIT 1"
    );


    // Bind email parameter

    $stmt->bind_param(
        's',
        $email
    );


    // Execute query

    $stmt->execute();


    // Retrieve database result

    $stmt->bind_result(
        $userId,
        $userName,
        $userEmail,
        $storedPassword,
        $userRole
    );


    // Fetch user record

    $userFound = $stmt->fetch();


    // Close statement

    $stmt->close();


    // =================================================
    // 10. VERIFY USER CREDENTIALS
    // =================================================

    if (
        !$userFound ||
        !is_string($storedPassword) ||
        !password_verify($password, $storedPassword)
    ) {

        $redirectToLogin(
            'Email atau password salah.'
        );

    }


    // =================================================
    // 11. VALIDATE USER ROLE
    // =================================================

    $userRole = strtolower(
        trim((string) $userRole)
    );


    if (!in_array(
        $userRole,
        siaga_allowed_roles(),
        true
    )) {

        error_log(
            '[SIAGAKAMPUNG] Invalid user role detected.'
        );

        $redirectToLogin(
            'Akun tidak memiliki hak akses yang valid.'
        );

    }


    // =================================================
    // 12. CREATE AUTHENTICATED SESSION
    // =================================================

    $loginSuccess = siaga_login_user([

        'id' => (int) $userId,

        'name' => (string) $userName,

        'email' => (string) $userEmail,

        'role' => $userRole

    ]);


    if (!$loginSuccess) {

        error_log(
            '[SIAGAKAMPUNG] Failed to create authenticated session.'
        );

        $redirectToLogin(
            'Login gagal. Silakan coba kembali.'
        );

    }


    // =================================================
    // 13. CLEAR LOGIN ERROR
    // =================================================

    unset($_SESSION['login_error']);


    // =================================================
    // 14. REDIRECT BASED ON USER ROLE
    // =================================================

    $redirectByRole($userRole);


} catch (Throwable $e) {

    // Record technical error

    error_log(
        '[SIAGAKAMPUNG LOGIN ERROR] ' .
        $e->getMessage()
    );


    // Return safe error message

    $redirectToLogin(
        'Terjadi gangguan sistem. Silakan coba kembali.'
    );

}


// =====================================================
// END OF LOGIN PROCESS
// =====================================================
