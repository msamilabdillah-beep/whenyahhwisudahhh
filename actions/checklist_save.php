
<?php


/**
 * =====================================================
 * SIAGAKAMPUNG
 * CHECKLIST SAVE PROCESS
 * Version : 1.0 MVP
 * =====================================================
 */


// =====================================================
// 1. LOAD AUTHENTICATION AND SESSION
// =====================================================

require_once __DIR__ . '/../includes/auth.php';


// =====================================================
// 2. PROJECT URL AND REDIRECT HELPER
// =====================================================

$baseUrl = siaga_project_base_url();

$redirect = static function (
    string $page,
    array $params = []
) use ($baseUrl): void {

    $url = rtrim($baseUrl, '/') . '/' . ltrim($page, '/');

    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    header('Location: ' . $url, true, 303);

    exit;
};


// =====================================================
// 3. VALIDATE REQUEST METHOD
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    header('Allow: POST');

    exit('405 Method Not Allowed.');

}


// =====================================================
// 4. LOAD DATABASE CONNECTION
// =====================================================

require_once __DIR__ . '/../includes/config.php';


// =====================================================
// 5. READ FAMILY CODE
// =====================================================

$familyCodeRaw = $_POST['family_code'] ?? '';

if (!is_string($familyCodeRaw)) {

    $_SESSION['family_error'] = 'Kode keluarga tidak valid.';

    $redirect('family-form.php');

}

$familyCode = strtoupper(trim($familyCodeRaw));


// =====================================================
// 6. VALIDATE FAMILY CODE
// =====================================================

if (
    $familyCode === '' ||
    strlen($familyCode) > 20 ||
    !preg_match('/^[A-Z0-9-]+$/D', $familyCode)
) {

    $_SESSION['family_error'] = 'Format kode keluarga tidak valid.';

    $redirect('family-form.php');

}


// =====================================================
// 7. READ CHECKLIST INPUTS
// =====================================================

$checklistFields = [

    'emergency_bag',

    'emergency_contacts',

    'first_aid_kit',

    'important_documents',

    'assembly_point'

];


$checklist = [];

$errors = [];


foreach ($checklistFields as $field) {

    // Hidden input supplies 0 when checkbox is unchecked.

    $value = $_POST[$field] ?? '0';


    // Reject arrays or unexpected values.

    if (
        !is_string($value) ||
        !in_array($value, ['0', '1'], true)
    ) {

        $errors[] = 'Data checklist tidak valid.';

        break;

    }


    $checklist[$field] = (int) $value;

}


// =====================================================
// 8. VALIDATION ERROR
// =====================================================

if (!empty($errors)) {

    $_SESSION['family_error'] = implode(' ', $errors);

    $redirect(
        'family-card.php',
        ['code' => $familyCode]
    );

}


// =====================================================
// 9. CALCULATE READINESS PERCENTAGE
// =====================================================

$completeCount = array_sum($checklist);

$totalIndicators = 5;

$percentage = (int) (
    ($completeCount / $totalIndicators) * 100
);


// =====================================================
// 10. DATABASE TRANSACTION
// =====================================================

$transactionStarted = false;

try {

    // Begin transaction

    $conn->begin_transaction();

    $transactionStarted = true;


    // =================================================
    // 11. FIND FAMILY AND EXISTING CHECKLIST
    // =================================================

    $findStmt = $conn->prepare(

        "SELECT
            f.id,
            rc.id

        FROM families f

        INNER JOIN readiness_checklists rc
            ON rc.family_id = f.id

        WHERE f.family_code = ?

        LIMIT 1

        FOR UPDATE"

    );


    $findStmt->bind_param(
        's',
        $familyCode
    );


    $findStmt->execute();


    $findStmt->bind_result(
        $familyId,
        $checklistId
    );


    $familyExists = $findStmt->fetch();


    $findStmt->close();


    // =================================================
    // 12. CHECK FAMILY EXISTENCE
    // =================================================

    if (!$familyExists) {

        throw new RuntimeException(
            'Family or readiness checklist not found.'
        );

    }


    // =================================================
    // 13. UPDATE CHECKLIST
    // =================================================

    $updateStmt = $conn->prepare(

        "UPDATE readiness_checklists

        SET

            emergency_bag = ?,

            emergency_contacts = ?,

            first_aid_kit = ?,

            important_documents = ?,

            assembly_point = ?,

            updated_at = CURRENT_TIMESTAMP

        WHERE family_id = ?"

    );


    $updateStmt->bind_param(

        'iiiiii',

        $checklist['emergency_bag'],

        $checklist['emergency_contacts'],

        $checklist['first_aid_kit'],

        $checklist['important_documents'],

        $checklist['assembly_point'],

        $familyId

    );


    $updateStmt->execute();


    $updateStmt->close();


    // =================================================
    // 14. COMMIT TRANSACTION
    // =================================================

    $conn->commit();

    $transactionStarted = false;


    // =================================================
    // 15. SAVE SUCCESS NOTIFICATION
    // =================================================

    $_SESSION['family_success'] =
        'Checklist kesiapsiagaan berhasil diperbarui.';

    $_SESSION['family_code'] = $familyCode;

    $_SESSION['checklist_percentage'] = $percentage;

    $_SESSION['checklist_complete_count'] = $completeCount;


    // =================================================
    // 16. REDIRECT TO FAMILY CARD
    // =================================================

    $redirect(

        'family-card.php',

        ['code' => $familyCode]

    );


} catch (Throwable $e) {

    // Rollback if transaction is still active

    if ($transactionStarted) {

        $conn->rollback();

        $transactionStarted = false;

    }


    // Log technical error

    error_log(

        '[SIAGAKAMPUNG CHECKLIST SAVE ERROR] ' .

        $e->getMessage()

    );


    // User-friendly error message

    $_SESSION['family_error'] =

        'Gagal memperbarui checklist. Pastikan kode keluarga benar dan coba kembali.';


    // Return to family card

    $redirect(

        'family-card.php',

        ['code' => $familyCode]

    );

}


// =====================================================
// END OF CHECKLIST SAVE
// =====================================================
