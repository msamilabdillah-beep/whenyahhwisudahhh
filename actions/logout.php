
<?php

/**
 * =====================================================
 * SIAGAKAMPUNG
 * LOGOUT PROCESS
 * Version : 1.0 MVP
 * =====================================================
 */



// =====================================================
// 1. LOAD AUTHENTICATION
// =====================================================

require_once __DIR__ . '/../includes/auth.php';


// =====================================================
// 2. SECURITY HEADERS
// =====================================================

header('X-Content-Type-Options: nosniff');

header('Cache-Control: no-store, no-cache, must-revalidate');


// =====================================================
// 3. GET PROJECT BASE URL
// =====================================================

$baseUrl = siaga_project_base_url();


// =====================================================
// 4. VALIDATE REQUEST METHOD
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    header('Allow: POST');

    exit('405 Method Not Allowed.');

}


// =====================================================
// 5. PROCESS LOGOUT
// =====================================================

if (siaga_is_logged_in()) {

    // Destroy user session and session cookie

    siaga_logout_user();

}


// =====================================================
// 6. REDIRECT TO LOGIN PAGE
// =====================================================

header(
    'Location: ' . $baseUrl . '/login.php?status=logged_out',
    true,
    303
);

exit;


// =====================================================
// END OF LOGOUT PROCESS
// =====================================================
