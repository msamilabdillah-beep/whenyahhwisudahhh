<?php


/**
 * =====================================================
 * SIAGAKAMPUNG
 * FAMILY REGISTRATION PROCESS
 * Version : 1.0 MVP
 * =====================================================
 */


// =====================================================
// 1. LOAD AUTHENTICATION AND SESSION
// =====================================================

require_once __DIR__ . '/../includes/auth.php';


// =====================================================
// 2. SECURITY HEADERS
// =====================================================

header('X-Content-Type-Options: nosniff');

header('Cache-Control: no-store, no-cache, must-revalidate');


// =====================================================
// 3. PROJECT URL
// =====================================================

$baseUrl = siaga_project_base_url();


// =====================================================
// 4. REDIRECT HELPER
// =====================================================

$redirect = static function (
    string $page,
    array $params = []
) use ($baseUrl): void {

    $url = $baseUrl . '/' . ltrim($page, '/');

    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    header('Location: ' . $url, true, 303);

    exit;
};


// =====================================================
// 5. VALIDATE REQUEST METHOD
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    header('Allow: POST');

    exit('405 Method Not Allowed.');

}


// =====================================================
// 6. LOAD DATABASE CONNECTION
// =====================================================

require_once __DIR__ . '/../includes/config.php';


// =====================================================
// 7. READ FORM INPUT
// =====================================================

$readInput = static function (string $key): string {

    if (!isset($_POST[$key]) || !is_string($_POST[$key])) {
        return '';
    }

    return trim($_POST[$key]);

};


$familyName = $readInput('family_name');

$neighborhood = $readInput('neighborhood');

$memberCountRaw = $readInput('member_count');

$emergencyContact = $readInput('emergency_contact');


// =====================================================
// 8. INPUT VALIDATION
// =====================================================

$errors = [];


// UTF-8 character length helper

$stringLength = static function (string $value): int {

    return function_exists('mb_strlen')
        ? mb_strlen($value, 'UTF-8')
        : strlen($value);

};


// Validate family name

if ($familyName === '') {

    $errors[] = 'Nama keluarga wajib diisi.';

} elseif ($stringLength($familyName) > 100) {

    $errors[] = 'Nama keluarga maksimal 100 karakter.';

}


// Validate neighborhood

if ($neighborhood === '') {

    $errors[] = 'Wilayah atau RT/RW wajib diisi.';

} elseif ($stringLength($neighborhood) > 50) {

    $errors[] = 'Wilayah maksimal 50 karakter.';

}


// Validate member count

$memberCount = filter_var(
    $memberCountRaw,
    FILTER_VALIDATE_INT,
    [
        'options' => [
            'min_range' => 1
        ]
    ]
);

if ($memberCount === false) {

    $errors[] = 'Jumlah anggota keluarga minimal 1 orang.';

}


// Validate emergency contact

if ($emergencyContact === '') {

    $errors[] = 'Nomor kontak darurat wajib diisi.';

} elseif (
    !preg_match('/^[0-9+\s().-]{6,25}$/D', $emergencyContact) ||
    !preg_match('/[0-9]/', $emergencyContact)
) {

    $errors[] = 'Format nomor kontak darurat tidak valid.';

}


// =====================================================
// 9. HANDLE VALIDATION ERRORS
// =====================================================

if (!empty($errors)) {

    $_SESSION['family_error'] = implode(' ', $errors);

    $redirect('family-form.php');

}


// =====================================================
// 10. DATABASE TRANSACTION
// =====================================================

try {

    $familyCode = '';

    $familyId = 0;

    $saved = false;

    // Retry if a generated family code already exists.

    $maxAttempts = 5;


    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {

        // Generate unique family code

        $familyCode = 'SK-' .
            date('Y') .
            '-' .
            str_pad(
                (string) random_int(1, 99999),
                5,
                '0',
                STR_PAD_LEFT
            );


        $transactionStarted = false;


        try {

            // Start transaction

            $conn->begin_transaction();

            $transactionStarted = true;


            // =================================================
            // 11. INSERT FAMILY DATA
            // =================================================

            $stmt = $conn->prepare(
                "INSERT INTO families
                (
                    family_code,
                    family_name,
                    neighborhood,
                    member_count,
                    emergency_contact
                )
                VALUES (?, ?, ?, ?, ?)"
            );


            $stmt->bind_param(
                'sssis',
                $familyCode,
                $familyName,
                $neighborhood,
                $memberCount,
                $emergencyContact
            );


            $stmt->execute();


            // Get newly created family ID

            $familyId = (int) $conn->insert_id;


            $stmt->close();


            // =================================================
            // 12. CREATE INITIAL READINESS CHECKLIST
            // =================================================

            $checklistStmt = $conn->prepare(
                "INSERT INTO readiness_checklists
                (
                    family_id,
                    emergency_bag,
                    emergency_contacts,
                    first_aid_kit,
                    important_documents,
                    assembly_point
                )
                VALUES (?, 0, 0, 0, 0, 0)"
            );


            $checklistStmt->bind_param(
                'i',
                $familyId
            );


            $checklistStmt->execute();

            $checklistStmt->close();


            // =================================================
            // 13. COMMIT TRANSACTION
            // =================================================

            $conn->commit();

            $transactionStarted = false;

            $saved = true;

            break;


        } catch (mysqli_sql_exception $e) {

            // Rollback failed transaction

            if ($transactionStarted) {

                $conn->rollback();

                $transactionStarted = false;

            }


            // Retry only when family code is duplicated

            if (
                (int) $e->getCode() === 1062 &&
                $attempt < $maxAttempts
            ) {

                continue;

            }


            throw $e;


        } catch (Throwable $e) {

            if ($transactionStarted) {

                $conn->rollback();

                $transactionStarted = false;

            }

            throw $e;

        }

    }


    // =====================================================
    // 14. CHECK REGISTRATION RESULT
    // =====================================================

    if (!$saved) {

        throw new RuntimeException(
            'Unable to generate a unique family code.'
        );

    }


    // =====================================================
    // 15. SET SUCCESS NOTIFICATION
    // =====================================================

    $_SESSION['family_success'] =
        'Data keluarga berhasil didaftarkan.';

    $_SESSION['family_code'] = $familyCode;

    $_SESSION['family_id'] = $familyId;


    // =====================================================
    // 16. REDIRECT TO FAMILY CARD
    // =====================================================

    $redirect(
        'family-card.php',
        [
            'code' => $familyCode
        ]
    );


} catch (Throwable $e) {

    // Record technical error without exposing private data

    error_log(
        '[SIAGAKAMPUNG FAMILY SAVE ERROR] ' .
        $e->getMessage()
    );


    // Safe error notification

    $_SESSION['family_error'] =
        'Pendaftaran keluarga gagal. Silakan coba kembali.';


    $redirect('family-form.php');

}


// =====================================================
// END OF FAMILY REGISTRATION
// =====================================================
