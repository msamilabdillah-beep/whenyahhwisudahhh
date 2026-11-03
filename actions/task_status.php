<?php


/**
 * =====================================================
 * SIAGAKAMPUNG
 * TASK STATUS UPDATE PROCESS
 * Version: 1.0 MVP
 * =====================================================
 */


// =====================================================
// 1. LOAD AUTHENTICATION
// =====================================================

require_once __DIR__ . '/../includes/auth.php';


// =====================================================
// 2. PROJECT URL
// =====================================================

$baseUrl = rtrim(
    siaga_project_base_url(),
    '/'
);


// =====================================================
// 3. REDIRECT HELPER
// =====================================================

$redirect = static function (
    string $page
) use ($baseUrl): void {

    header(
        'Location: ' . $baseUrl . '/' . ltrim($page, '/'),
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
// 5. CHECK USER ROLE
// =====================================================

// Admin, coordinator, and volunteer can access.

siaga_require_role([
    'admin',
    'coordinator',
    'volunteer'
]);


// =====================================================
// 6. GET CURRENT USER
// =====================================================

$currentUserId = filter_var(
    $_SESSION['user_id'] ?? null,
    FILTER_VALIDATE_INT,
    [
        'options' => [
            'min_range' => 1
        ]
    ]
);

$currentRole = $_SESSION['user_role'] ?? '';


if (
    $currentUserId === false ||
    !in_array(
        $currentRole,
        ['admin', 'coordinator', 'volunteer'],
        true
    )
) {

    http_response_code(403);

    exit('Unauthorized access.');

}


// =====================================================
// 7. LOAD DATABASE
// =====================================================

require_once __DIR__ . '/../includes/config.php';


// Enable strict MySQLi errors.

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);


// =====================================================
// 8. READ INPUT
// =====================================================

$taskIdRaw = $_POST['task_id'] ?? '';

$statusRaw = $_POST['status'] ?? '';


// Reject array or unexpected input types.

if (
    !is_string($taskIdRaw) ||
    !is_string($statusRaw)
) {

    $_SESSION['task_error'] =
        'Data perubahan status tidak valid.';

    $redirect('community-tasks.php');

}


// =====================================================
// 9. VALIDATE TASK ID
// =====================================================

$taskId = filter_var(
    trim($taskIdRaw),
    FILTER_VALIDATE_INT,
    [
        'options' => [
            'min_range' => 1
        ]
    ]
);


if ($taskId === false) {

    $_SESSION['task_error'] =
        'ID tugas tidak valid.';

    $redirect('community-tasks.php');

}


// =====================================================
// 10. VALIDATE STATUS
// =====================================================

$allowedStatuses = [
    'pending',
    'in_progress',
    'completed'
];


$newStatus = trim($statusRaw);


if (
    !in_array(
        $newStatus,
        $allowedStatuses,
        true
    )
) {

    $_SESSION['task_error'] =
        'Status tugas tidak diperbolehkan.';

    $redirect('community-tasks.php');

}


// =====================================================
// 11. START TRANSACTION
// =====================================================

$transactionStarted = false;

$errorMessage = '';

try {

    $conn->begin_transaction();

    $transactionStarted = true;


    // =================================================
    // 12. FIND TASK
    // =================================================

    $findStmt = $conn->prepare(

        "SELECT
            id,
            assigned_to,
            status

        FROM community_tasks

        WHERE id = ?

        LIMIT 1

        FOR UPDATE"

    );


    $findStmt->bind_param(
        'i',
        $taskId
    );


    $findStmt->execute();


    $findStmt->bind_result(
        $foundTaskId,
        $assignedTo,
        $oldStatus
    );


    $taskExists = $findStmt->fetch();


    $findStmt->close();


    // =================================================
    // 13. CHECK TASK EXISTENCE
    // =================================================

    if (!$taskExists) {

        throw new DomainException(
            'Tugas tidak ditemukan.'
        );

    }


    // =================================================
    // 14. CHECK VOLUNTEER OWNERSHIP
    // =================================================

    if (
        $currentRole === 'volunteer' &&
        (int) $assignedTo !== (int) $currentUserId
    ) {

        throw new DomainException(
            'Anda tidak memiliki izin untuk mengubah tugas ini.'
        );

    }


    // =================================================
    // 15. UPDATE TASK STATUS
    // =================================================

    $updateStmt = $conn->prepare(

        "UPDATE community_tasks

        SET

            status = ?,

            completed_at =

                CASE

                    WHEN ? = 'completed'

                    THEN COALESCE(
                        completed_at,
                        CURRENT_TIMESTAMP
                    )

                    ELSE NULL

                END

        WHERE id = ?"

    );


    $updateStmt->bind_param(

        'ssi',

        $newStatus,

        $newStatus,

        $taskId

    );


    $updateStmt->execute();


    $updateStmt->close();


    // =================================================
    // 16. COMMIT TRANSACTION
    // =================================================

    $conn->commit();

    $transactionStarted = false;


    // =================================================
    // 17. SUCCESS NOTIFICATION
    // =================================================

    $_SESSION['task_success'] =
        'Status tugas berhasil diperbarui.';


    // =================================================
    // 18. REDIRECT
    // =================================================

    $redirect('community-tasks.php');


} catch (DomainException $e) {

    // Rollback failed operation.

    if ($transactionStarted) {

        $conn->rollback();

        $transactionStarted = false;

    }


    $errorMessage = $e->getMessage();


} catch (Throwable $e) {

    // Rollback database transaction.

    if ($transactionStarted) {

        $conn->rollback();

        $transactionStarted = false;

    }


    // Record technical error.

    error_log(
        '[SIAGAKAMPUNG TASK STATUS ERROR] ' .
        $e->getMessage()
    );


    $errorMessage =
        'Gagal memperbarui status tugas. Silakan coba kembali.';

}


// =====================================================
// 19. HANDLE ERROR
// =====================================================

if ($errorMessage !== '') {

    $_SESSION['task_error'] = $errorMessage;

    $redirect('community-tasks.php');

}


// =====================================================
// END OF TASK STATUS PROCESS
// =====================================================
