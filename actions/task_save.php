
<?php


/**
 * =====================================================
 * SIAGAKAMPUNG
 * COMMUNITY TASK SAVE PROCESS
 * Version : 1.0 MVP
 * =====================================================
 */


// =====================================================
// 1. LOAD AUTHENTICATION
// =====================================================

require_once __DIR__ . '/../includes/auth.php';


// =====================================================
// 2. PROJECT URL
// =====================================================

$baseUrl = siaga_project_base_url();


// =====================================================
// 3. REDIRECT HELPER
// =====================================================

$redirect = static function (
    string $page
) use ($baseUrl): void {

    header(
        'Location: ' .
        rtrim($baseUrl, '/') . '/' .
        ltrim($page, '/'),
        true,
        303
    );

    exit;
};


// =====================================================
// 4. VALIDATE REQUEST METHOD
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    header('Allow: POST');

    exit('405 Method Not Allowed.');

}


// =====================================================
// 5. CHECK USER AUTHORIZATION
// =====================================================

// Only admin and coordinator can create tasks.

siaga_require_role([
    'admin',
    'coordinator'
]);


// =====================================================
// 6. GET CURRENT USER
// =====================================================

$user = siaga_current_user();

if ($user === null || !isset($user['id'])) {

    http_response_code(401);

    exit('Unauthorized.');

}


// =====================================================
// 7. LOAD DATABASE CONNECTION
// =====================================================

require_once __DIR__ . '/../includes/config.php';


// Enable strict MySQLi error handling.

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);


// =====================================================
// 8. SECURITY HEADERS
// =====================================================

header('X-Content-Type-Options: nosniff');

header('Cache-Control: no-store, no-cache, must-revalidate');


// =====================================================
// 9. INPUT READER
// =====================================================

$readInput = static function (
    string $key
): string {

    $value = $_POST[$key] ?? '';

    if (!is_string($value)) {
        return '';
    }

    return trim($value);

};


// =====================================================
// 10. READ FORM INPUT
// =====================================================

$familyIdRaw = $readInput('family_id');

$assignedToRaw = $readInput('assigned_to');

$taskTitle = $readInput('task_title');

$taskDescriptionRaw = $readInput('task_description');


// =====================================================
// 11. VALIDATE FAMILY ID
// =====================================================

$familyId = filter_var(
    $familyIdRaw,
    FILTER_VALIDATE_INT,
    [
        'options' => [
            'min_range' => 1
        ]
    ]
);


// =====================================================
// 12. VALIDATE VOLUNTEER ID
// =====================================================

$assignedTo = filter_var(
    $assignedToRaw,
    FILTER_VALIDATE_INT,
    [
        'options' => [
            'min_range' => 1
        ]
    ]
);


// =====================================================
// 13. VALIDATE TASK TITLE
// =====================================================

$titleLength = function_exists('mb_strlen')
    ? mb_strlen($taskTitle, 'UTF-8')
    : strlen($taskTitle);


$errors = [];


if ($familyId === false) {

    $errors[] = 'Keluarga yang dipilih tidak valid.';

}


if ($assignedTo === false) {

    $errors[] = 'Relawan yang dipilih tidak valid.';

}


if ($taskTitle === '') {

    $errors[] = 'Judul tugas wajib diisi.';

} elseif ($titleLength > 150) {

    $errors[] = 'Judul tugas maksimal 150 karakter.';

}


if (strlen($taskDescriptionRaw) > 60000) {

    $errors[] = 'Deskripsi tugas terlalu panjang.';

}


// =====================================================
// 14. HANDLE VALIDATION ERRORS
// =====================================================

if (!empty($errors)) {

    $_SESSION['task_error'] = implode(' ', $errors);

    $redirect('community-tasks.php');

}


// =====================================================
// 15. PREPARE TASK DESCRIPTION
// =====================================================

// Description is optional.

$taskDescription = $taskDescriptionRaw === ''
    ? null
    : $taskDescriptionRaw;


// =====================================================
// 16. DATABASE TRANSACTION
// =====================================================

$transactionStarted = false;

$failureMessage = '';

try {

    // Start transaction.

    $conn->begin_transaction();

    $transactionStarted = true;


    // =================================================
    // 17. VERIFY FAMILY
    // =================================================

    $familyStmt = $conn->prepare(

        "SELECT id

        FROM families

        WHERE id = ?

        LIMIT 1

        FOR UPDATE"

    );


    $familyStmt->bind_param(
        'i',
        $familyId
    );


    $familyStmt->execute();

    $familyStmt->store_result();


    $familyExists = $familyStmt->num_rows > 0;


    $familyStmt->close();


    if (!$familyExists) {

        throw new DomainException(
            'Data keluarga tidak ditemukan. Silakan pilih keluarga yang tersedia.'
        );

    }


    // =================================================
    // 18. VERIFY VOLUNTEER
    // =================================================

    $volunteerStmt = $conn->prepare(

        "SELECT id

        FROM users

        WHERE id = ?

        AND role = 'volunteer'

        LIMIT 1

        FOR UPDATE"

    );


    $volunteerStmt->bind_param(
        'i',
        $assignedTo
    );


    $volunteerStmt->execute();

    $volunteerStmt->store_result();


    $volunteerExists = $volunteerStmt->num_rows > 0;


    $volunteerStmt->close();


    if (!$volunteerExists) {

        throw new DomainException(
            'Pengguna yang dipilih bukan relawan yang terdaftar.'
        );

    }


    // =================================================
    // 19. INSERT COMMUNITY TASK
    // =================================================

    $stmt = $conn->prepare(

        "INSERT INTO community_tasks

        (
            family_id,
            assigned_to,
            task_title,
            task_description,
            status,
            completed_at
        )

        VALUES (?, ?, ?, ?, 'pending', NULL)"

    );


    $stmt->bind_param(

        'iiss',

        $familyId,

        $assignedTo,

        $taskTitle,

        $taskDescription

    );


    $stmt->execute();


    // Get generated task ID.

    $taskId = (int) $conn->insert_id;


    $stmt->close();


    // =================================================
    // 20. COMMIT TRANSACTION
    // =================================================

    $conn->commit();

    $transactionStarted = false;


    // =================================================
    // 21. SAVE SUCCESS NOTIFICATION
    // =================================================

    $_SESSION['task_success'] =
        'Tugas pendampingan berhasil dibuat dan diberikan kepada relawan.';


    $_SESSION['task_id'] = $taskId;


    // =================================================
    // 22. REDIRECT
    // =================================================

    $redirect('community-tasks.php');


} catch (DomainException $e) {

    // Rollback transaction.

    if ($transactionStarted) {

        $conn->rollback();

        $transactionStarted = false;

    }


    $failureMessage = $e->getMessage();


} catch (Throwable $e) {

    // Rollback transaction.

    if ($transactionStarted) {

        $conn->rollback();

        $transactionStarted = false;

    }


    // Record technical error.

    error_log(
        '[SIAGAKAMPUNG TASK SAVE ERROR] ' .
        $e->getMessage()
    );


    $failureMessage =
        'Tugas gagal disimpan. Silakan periksa database dan coba kembali.';

}


// =====================================================
// 23. HANDLE FAILED TRANSACTION
// =====================================================

if ($failureMessage !== '') {

    $_SESSION['task_error'] = $failureMessage;

    $redirect('community-tasks.php');

}


// =====================================================
// END OF TASK SAVE PROCESS
// =====================================================
