<?php

declare(strict_types=1);

/**
 * =====================================================
 * SIAGAKAMPUNG
 * EVACUATION PLAN SAVE & VALIDATION PROCESS
 * Version : 1.1 MVP
 * =====================================================
 * Supported operations:
 * 1. create (default): Admin / Coordinator creates a draft.
 * 2. verify: Admin validates an existing draft.
 *
 * The frontend evacuationForm contract remains unchanged:
 * action="actions/evacuation_save.php"
 * method="POST"
 * inputs: neighborhood, hazard_type, primary_route,
 *         alternative_route, assembly_point,
 *         coordinator_contact
 *
 * Validation form (to be added to evacuation-plan.php):
 * operation=verify, plan_id, csrf_token
 * =====================================================
 */


// =====================================================
// 1. LOAD AUTHENTICATION AND DATABASE
// =====================================================

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


// =====================================================
// 2. SECURITY HEADERS
// =====================================================

header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate');


// =====================================================
// 3. REDIRECT AND FLASH MESSAGE HELPERS
// =====================================================

$baseUrl = rtrim(siaga_project_base_url(), '/');

$redirect = static function (array $query = []) use ($baseUrl): void {
    $url = $baseUrl . '/evacuation-plan.php';

    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    header('Location: ' . $url, true, 303);
    exit;
};

$fail = static function (string $message, int $status = 0) use ($redirect): void {
    $_SESSION['evacuation_error'] = $message;

    if ($status > 0) {
        http_response_code($status);
    }

    $redirect();
};


// =====================================================
// 4. REQUEST METHOD
// =====================================================

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('405 Method Not Allowed.');
}


// =====================================================
// 5. CURRENT USER AND OPERATION
// =====================================================

$user = siaga_current_user();

if ($user === null) {
    $fail('Silakan login terlebih dahulu.', 401);
}

$currentUserId = (int) $user['id'];
$currentRole = (string) $user['role'];

$operation = $_POST['operation'] ?? 'create';

if (!is_string($operation)) {
    $fail('Jenis permintaan tidak valid.', 400);
}

$operation = trim($operation);


// =====================================================
// 6. OPERATION: VERIFY DRAFT (ADMIN ONLY)
// =====================================================

if ($operation === 'verify') {
    // SPEC defines validation as a separate authorized process.
    // For this MVP, Admin is the final validator.
    siaga_require_role('admin');

    // Validate CSRF token for the state-changing validation action.
    $submittedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !is_string($sessionToken) ||
        $sessionToken === '' ||
        !hash_equals($sessionToken, $submittedToken)
    ) {
        $fail('Permintaan validasi tidak sah atau sesi telah kedaluwarsa. Muat ulang halaman dan coba kembali.', 403);
    }

    $planIdRaw = $_POST['plan_id'] ?? '';

    if (!is_string($planIdRaw)) {
        $fail('ID rencana evakuasi tidak valid.', 400);
    }

    $planId = filter_var(
        trim($planIdRaw),
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($planId === false) {
        $fail('ID rencana evakuasi tidak valid.', 400);
    }

    $transactionStarted = false;

    try {
        $conn->begin_transaction();
        $transactionStarted = true;

        // Lock the selected plan to prevent concurrent validation.
        $findStmt = $conn->prepare(
            "SELECT validation_status
             FROM evacuation_plans
             WHERE id = ?
             LIMIT 1
             FOR UPDATE"
        );

        $findStmt->bind_param('i', $planId);
        $findStmt->execute();
        $findStmt->bind_result($currentStatus);
        $planFound = $findStmt->fetch();
        $findStmt->close();

        if (!$planFound) {
            throw new DomainException('Rencana evakuasi tidak ditemukan.');
        }

        if ($currentStatus !== 'draft') {
            throw new DomainException('Rencana ini bukan berstatus draft atau sudah diverifikasi sebelumnya.');
        }

        // Only this authorized branch can set status to verified.
        $updateStmt = $conn->prepare(
            "UPDATE evacuation_plans
             SET validation_status = 'verified',
                 verified_at = CURRENT_TIMESTAMP
             WHERE id = ?
               AND validation_status = 'draft'"
        );

        $updateStmt->bind_param('i', $planId);
        $updateStmt->execute();

        if ($updateStmt->affected_rows !== 1) {
            $updateStmt->close();
            throw new RuntimeException('Status rencana tidak berhasil diperbarui.');
        }

        $updateStmt->close();

        $conn->commit();
        $transactionStarted = false;

        $_SESSION['evacuation_success'] = 'Rencana evakuasi berhasil diverifikasi dan sekarang dapat ditampilkan kepada warga.';
        $_SESSION['evacuation_plan_id'] = $planId;

        // Rotate the token after a successful state-changing operation.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $redirect(['status' => 'verified']);

    } catch (DomainException $e) {
        if ($transactionStarted) {
            $conn->rollback();
            $transactionStarted = false;
        }

        $fail($e->getMessage());

    } catch (Throwable $e) {
        if ($transactionStarted) {
            $conn->rollback();
            $transactionStarted = false;
        }

        error_log('[SIAGAKAMPUNG EVACUATION VERIFY ERROR] ' . $e->getMessage());
        $fail('Validasi rencana evakuasi gagal. Silakan periksa database dan coba kembali.');
    }
}


// =====================================================
// 7. OPERATION: CREATE DRAFT (ADMIN / COORDINATOR)
// =====================================================

if ($operation !== 'create' && $operation !== '') {
    $fail('Operasi rencana evakuasi tidak dikenal.', 400);
}

siaga_require_role(['admin', 'coordinator']);

$createdBy = $currentUserId;


// =====================================================
// 8. INPUT READER
// =====================================================

$readInput = static function (string $key): string {
    $value = $_POST[$key] ?? '';

    if (!is_string($value)) {
        return '';
    }

    return trim($value);
};

$neighborhood = $readInput('neighborhood');
$hazardType = $readInput('hazard_type');
$primaryRoute = $readInput('primary_route');
$alternativeRoute = $readInput('alternative_route');
$assemblyPoint = $readInput('assembly_point');
$coordinatorContact = $readInput('coordinator_contact');

$stringLength = static function (string $value): int {
    return function_exists('mb_strlen')
        ? mb_strlen($value, 'UTF-8')
        : strlen($value);
};


// =====================================================
// 9. VALIDATE FORM INPUT
// =====================================================

$errors = [];

if ($neighborhood === '') {
    $errors[] = 'Wilayah wajib diisi.';
} elseif ($stringLength($neighborhood) > 50) {
    $errors[] = 'Wilayah maksimal 50 karakter.';
}

if ($hazardType === '') {
    $errors[] = 'Jenis ancaman wajib dipilih.';
} elseif ($stringLength($hazardType) > 50) {
    $errors[] = 'Jenis ancaman maksimal 50 karakter.';
}

if ($primaryRoute === '') {
    $errors[] = 'Jalur evakuasi utama wajib diisi.';
} elseif (strlen($primaryRoute) > 60000) {
    $errors[] = 'Deskripsi jalur utama terlalu panjang.';
}

if ($alternativeRoute === '') {
    $errors[] = 'Jalur evakuasi alternatif wajib diisi.';
} elseif (strlen($alternativeRoute) > 60000) {
    $errors[] = 'Deskripsi jalur alternatif terlalu panjang.';
}

if ($assemblyPoint === '') {
    $errors[] = 'Titik kumpul wajib diisi.';
} elseif ($stringLength($assemblyPoint) > 150) {
    $errors[] = 'Titik kumpul maksimal 150 karakter.';
}

if ($coordinatorContact === '') {
    $errors[] = 'Kontak koordinator wajib diisi.';
} elseif (
    !preg_match('/^[0-9+\s().-]{6,25}$/D', $coordinatorContact) ||
    !preg_match('/[0-9]/', $coordinatorContact)
) {
    $errors[] = 'Nomor kontak koordinator tidak valid.';
}

if ($errors !== []) {
    $_SESSION['evacuation_error'] = implode(' ', $errors);
    $redirect();
}


// =====================================================
// 10. INSERT NEW DRAFT
// =====================================================

try {
    $stmt = $conn->prepare(
        "INSERT INTO evacuation_plans
        (
            neighborhood,
            hazard_type,
            primary_route,
            alternative_route,
            assembly_point,
            coordinator_contact,
            validation_status,
            verified_at,
            created_by
        )
        VALUES (?, ?, ?, ?, ?, ?, 'draft', NULL, ?)"
    );

    $stmt->bind_param(
        'ssssssi',
        $neighborhood,
        $hazardType,
        $primaryRoute,
        $alternativeRoute,
        $assemblyPoint,
        $coordinatorContact,
        $createdBy
    );

    $stmt->execute();
    $planId = (int) $conn->insert_id;
    $stmt->close();

    $_SESSION['evacuation_success'] = 'Rencana evakuasi berhasil disimpan sebagai draft dan menunggu proses validasi.';
    $_SESSION['evacuation_plan_id'] = $planId;

    $redirect(['status' => 'draft-saved']);

} catch (Throwable $e) {
    error_log('[SIAGAKAMPUNG EVACUATION SAVE ERROR] ' . $e->getMessage());
    $_SESSION['evacuation_error'] = 'Rencana evakuasi gagal disimpan. Silakan periksa data dan coba kembali.';
    $redirect();
}


// =====================================================
// END OF EVACUATION SAVE & VALIDATION PROCESS
// =====================================================
