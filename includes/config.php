
<?php

/**
 * =====================================================
 * SIAGAKAMPUNG
 * DATABASE CONFIGURATION
 * Version : 1.0 MVP
 * Database: MySQL
 * Driver  : MySQLi
 * =====================================================
 */



// =====================================================
// 1. DATABASE CONFIGURATION
// =====================================================

define('DB_HOST', 'localhost');

define('DB_USER', 'root');

define('DB_PASS', '');

define('DB_NAME', 'siagakampung_db');

define('DB_PORT', 3306);


// =====================================================
// 2. CHECK MYSQLI EXTENSION
// =====================================================

if (!extension_loaded('mysqli')) {

    error_log(
        '[SIAGAKAMPUNG] MySQLi extension is not enabled.'
    );

    http_response_code(500);

    exit(
        'Database error: MySQLi extension tidak tersedia. ' .
        'Periksa konfigurasi PHP XAMPP.'
    );

}


// =====================================================
// 3. MYSQLI ERROR REPORTING
// =====================================================

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);


// =====================================================
// 4. DATABASE CONNECTION
// =====================================================

try {

    $conn = new mysqli(
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME,
        DB_PORT
    );


    // Set database character encoding

    $conn->set_charset('utf8mb4');


} catch (mysqli_sql_exception $e) {

    // Record technical error in PHP error log

    error_log(
        '[SIAGAKAMPUNG DATABASE ERROR] ' .
        $e->getMessage()
    );


    // Return server error

    http_response_code(500);


    // Display safe error message

    exit(
        'Koneksi database gagal. ' .
        'Pastikan MySQL aktif dan konfigurasi database benar.'
    );

}


// =====================================================
// 5. CONNECTION READY
// =====================================================

// Database connection variable:
// $conn
//
// Database:
// siagakampung_db
//
// Charset:
// utf8mb4
//
// Connection:
// MySQLi
//
// All backend files can use:
//
// require_once __DIR__ . '/../includes/config.php';
//
// =====================================================

