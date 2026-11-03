<?php
declare(strict_types=1);

/**
 * =====================================================
 * SIAGAKAMPUNG
 * DASHBOARD READINESS GAP - DATABASE INTEGRATION
 * =====================================================
 */

require_once __DIR__ . '/includes/auth.php';

// Dashboard is intended for administrators and coordinators.
siaga_require_login();
$currentUser = siaga_current_user();

if ($currentUser === null) {
    http_response_code(401);
    exit('Silakan login terlebih dahulu.');
}

if ($currentUser['role'] === 'volunteer') {
    header('Location: community-tasks.php', true, 303);
    exit;
}

if (!in_array($currentUser['role'], ['admin', 'coordinator'], true)) {
    http_response_code(403);
    exit('403 Forbidden: Anda tidak memiliki akses ke dashboard.');
}

require_once __DIR__ . '/includes/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/** Escape dynamic values before rendering HTML. */
$dashboardEscape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

/** Format percentages using Indonesian decimal separators. */
$dashboardPercent = static function (int $value, int $total): float {
    return $total > 0 ? ($value / $total) * 100 : 0.0;
};

$dashboardPercentText = static function (float $value): string {
    return number_format($value, 1, ',', '.');
};

$dashboardWidth = static function (float $value): string {
    return number_format(max(0, min(100, $value)), 2, '.', '');
};

try {
    // Aggregate family readiness. LEFT JOIN ensures families without a
    // checklist are counted as incomplete and as having all five gaps.
    $summarySql = "
        SELECT
            COUNT(f.id) AS total_families,
            COALESCE(SUM(CASE
                WHEN COALESCE(rc.emergency_bag, 0) = 1
                 AND COALESCE(rc.emergency_contacts, 0) = 1
                 AND COALESCE(rc.first_aid_kit, 0) = 1
                 AND COALESCE(rc.important_documents, 0) = 1
                 AND COALESCE(rc.assembly_point, 0) = 1
                THEN 1 ELSE 0 END), 0) AS complete_families,
            COALESCE(SUM(CASE WHEN COALESCE(rc.emergency_bag, 0) = 0 THEN 1 ELSE 0 END), 0) AS gap_emergency_bag,
            COALESCE(SUM(CASE WHEN COALESCE(rc.emergency_contacts, 0) = 0 THEN 1 ELSE 0 END), 0) AS gap_emergency_contacts,
            COALESCE(SUM(CASE WHEN COALESCE(rc.first_aid_kit, 0) = 0 THEN 1 ELSE 0 END), 0) AS gap_first_aid_kit,
            COALESCE(SUM(CASE WHEN COALESCE(rc.important_documents, 0) = 0 THEN 1 ELSE 0 END), 0) AS gap_important_documents,
            COALESCE(SUM(CASE WHEN COALESCE(rc.assembly_point, 0) = 0 THEN 1 ELSE 0 END), 0) AS gap_assembly_point
        FROM families f
        LEFT JOIN readiness_checklists rc ON rc.family_id = f.id
    ";

    $summaryResult = $conn->query($summarySql);
    $summary = $summaryResult->fetch_assoc() ?: [];
    $summaryResult->free();

    $totalFamilies = (int) ($summary['total_families'] ?? 0);
    $completeFamilies = (int) ($summary['complete_families'] ?? 0);
    $incompleteFamilies = max(0, $totalFamilies - $completeFamilies);
    $readinessPercent = $dashboardPercent($completeFamilies, $totalFamilies);
    $incompletePercent = $dashboardPercent($incompleteFamilies, $totalFamilies);

    $gapIndicators = [
        [
            'key' => 'emergency_bag',
            'label' => '01. Tas Siaga',
            'count' => (int) ($summary['gap_emergency_bag'] ?? 0),
        ],
        [
            'key' => 'emergency_contacts',
            'label' => '02. Kontak Darurat',
            'count' => (int) ($summary['gap_emergency_contacts'] ?? 0),
        ],
        [
            'key' => 'first_aid_kit',
            'label' => '03. Perlengkapan P3K',
            'count' => (int) ($summary['gap_first_aid_kit'] ?? 0),
        ],
        [
            'key' => 'important_documents',
            'label' => '04. Dokumen Penting',
            'count' => (int) ($summary['gap_important_documents'] ?? 0),
        ],
        [
            'key' => 'assembly_point',
            'label' => '05. Titik Kumpul Keluarga',
            'count' => (int) ($summary['gap_assembly_point'] ?? 0),
        ],
    ];

    $largestGap = $totalFamilies > 0
        ? max(array_column($gapIndicators, 'count'))
        : 0;

    $largestGapLabels = [];
    if ($largestGap > 0) {
        foreach ($gapIndicators as $indicator) {
            if ($indicator['count'] === $largestGap) {
                $largestGapLabels[] = preg_replace('/^\d+\.\s*/', '', $indicator['label']);
            }
        }
    }
    $largestGapLabel = $largestGapLabels
        ? implode(', ', $largestGapLabels)
        : '';

    // Task counts are initialized to zero so empty statuses render correctly.
    $taskCounts = [
        'pending' => 0,
        'in_progress' => 0,
        'completed' => 0,
    ];

    $taskCountResult = $conn->query(
        "SELECT status, COUNT(*) AS total
         FROM community_tasks
         GROUP BY status"
    );

    while ($taskCountRow = $taskCountResult->fetch_assoc()) {
        if (array_key_exists($taskCountRow['status'], $taskCounts)) {
            $taskCounts[$taskCountRow['status']] = (int) $taskCountRow['total'];
        }
    }
    $taskCountResult->free();

    // Latest five task records. Emergency contact details are intentionally
    // excluded from the dashboard query.
    $recentTaskResult = $conn->query(
        "SELECT
            ct.id,
            ct.task_title,
            ct.task_description,
            ct.status,
            ct.created_at,
            f.family_name,
            u.name AS volunteer_name
         FROM community_tasks ct
         INNER JOIN families f ON f.id = ct.family_id
         INNER JOIN users u ON u.id = ct.assigned_to
         ORDER BY ct.created_at DESC, ct.id DESC
         LIMIT 5"
    );

    $recentTasks = [];
    while ($taskRow = $recentTaskResult->fetch_assoc()) {
        $recentTasks[] = $taskRow;
    }
    $recentTaskResult->free();

} catch (Throwable $e) {
    error_log('[SIAGAKAMPUNG DASHBOARD ERROR] ' . $e->getMessage());
    http_response_code(500);
    exit('Dashboard gagal memuat data. Periksa koneksi database dan struktur tabel SIAGAKAMPUNG.');
}

$roleLabels = [
    'admin' => 'Administrator Sistem',
    'coordinator' => 'Koordinator Komunitas',
];
$currentRoleLabel = $roleLabels[$currentUser['role']] ?? 'Pengguna';
$roleBadgeLabel = $currentUser['role'] === 'admin' ? 'PANEL ADMIN' : 'PANEL KOORDINATOR';

$nameParts = preg_split('/\s+/', trim($currentUser['name'])) ?: [];
$avatarInitials = '';
foreach (array_slice($nameParts, 0, 2) as $namePart) {
    $avatarInitials .= function_exists('mb_substr')
        ? mb_substr($namePart, 0, 1, 'UTF-8')
        : substr($namePart, 0, 1);
}
$avatarInitials = strtoupper($avatarInitials ?: 'SK');

$statusPresentation = [
    'pending' => ['label' => 'PENDING', 'class' => 'status-pending'],
    'in_progress' => ['label' => 'IN PROGRESS', 'class' => 'status-progress'],
    'completed' => ['label' => 'COMPLETED', 'class' => 'status-completed'],
];
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Readiness Gap | SIAGAKAMPUNG</title>

    <meta name="description" content="Dashboard pemantauan kesiapsiagaan keluarga dan tugas relawan SIAGAKAMPUNG.">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>

        :root {
            --primary: #174a78;
            --primary-dark: #102d49;
            --secondary: #087f8c;
            --success: #15966b;
            --warning: #d69a24;
            --danger: #d9534f;
            --background: #f3f6fa;
            --muted: #77869a;
            --border: #e7ecf2;
            --sidebar-width: 250px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--background);
            color: #26384b;
            font-family: Inter, "Segoe UI", Arial, sans-serif;
        }

        a {
            text-decoration: none;
        }

        /* ==========================
           TOP HEADER
        ========================== */

        .top-header {
            height: 76px;
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .brand-logo {
            width: 43px;
            height: 43px;
            border-radius: 12px;
            background: var(--primary);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .brand-name {
            color: var(--primary);
            font-size: 17px;
            font-weight: 800;
            letter-spacing: .3px;
        }

        .brand-subtitle {
            display: block;
            color: #94a0ae;
            font-size: 9px;
            letter-spacing: 1.1px;
        }

        .header-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .profile-avatar {
            width: 39px;
            height: 39px;
            border-radius: 12px;
            background: #eaf2fa;
            color: var(--primary);
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-name {
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 800;
        }

        .profile-role {
            display: block;
            color: var(--muted);
            font-size: 10px;
            margin-top: 3px;
        }

        .role-badge {
            color: #087f8c;
            background: #e5f4f3;
            border-radius: 30px;
            padding: 7px 11px;
            font-size: 9px;
            font-weight: 800;
        }

        /* ==========================
           DASHBOARD LAYOUT
        ========================== */

        .dashboard-layout {
            display: grid;
            grid-template-columns: var(--sidebar-width) minmax(0, 1fr);
            min-height: calc(100vh - 76px);
        }

        /* ==========================
           SIDEBAR
        ========================== */

        .sidebar {
            background: var(--primary-dark);
            color: #ffffff;
            padding: 25px 17px;
            min-height: calc(100vh - 76px);
            display: flex;
            flex-direction: column;
        }

        .sidebar-label {
            color: #8fa8be;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 1.5px;
            padding: 10px 12px;
            margin-bottom: 7px;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li {
            margin-bottom: 5px;
        }

        .sidebar-link {
            min-height: 45px;
            padding: 11px 13px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #b8c8d8;
            font-size: 12px;
            font-weight: 600;
            border-radius: 10px;
            transition: .2s;
        }

        .sidebar-link:hover {
            color: white;
            background: rgba(255,255,255,.08);
        }

        .sidebar-link.active {
            color: white;
            background: #205b83;
            box-shadow: inset 3px 0 #42c3b3;
        }

        .sidebar-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-divider {
            border-color: rgba(255,255,255,.12);
            margin: 22px 0;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding: 15px 11px 5px;
        }

        .sidebar-info {
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.09);
            border-radius: 12px;
            padding: 15px;
        }

        .sidebar-info strong {
            display: block;
            color: white;
            font-size: 11px;
            margin-bottom: 6px;
        }

        .sidebar-info p {
            color: #a8bacb;
            font-size: 10px;
            line-height: 1.7;
            margin: 0;
        }

        /* ==========================
           MAIN PANEL
        ========================== */

        .main-panel {
            min-width: 0;
            padding: 30px;
        }

        .breadcrumb-text {
            color: var(--muted);
            font-size: 11px;
            margin-bottom: 12px;
        }

        .breadcrumb-text span {
            color: var(--primary);
            font-weight: 700;
        }

        .dashboard-title {
            color: var(--primary-dark);
            font-size: clamp(23px, 3vw, 29px);
            font-weight: 800;
            letter-spacing: -.5px;
        }

        .dashboard-subtitle {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.7;
        }

        .demo-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #98630b;
            background: #fff3d9;
            border: 1px solid #f4e4bd;
            border-radius: 30px;
            padding: 7px 12px;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .5px;
        }

        .header-action {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--primary);
            color: white;
            padding: 10px 16px;
            border-radius: 9px;
            font-size: 11px;
            font-weight: 700;
            transition: .2s;
        }

        .header-action:hover {
            color: white;
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .secondary-action {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: white;
            border: 1px solid var(--border);
            color: var(--primary);
            padding: 10px 15px;
            border-radius: 9px;
            font-size: 11px;
            font-weight: 700;
        }

        .secondary-action:hover {
            background: #f5f8fb;
            color: var(--primary-dark);
        }

        /* ==========================
           SECTION
        ========================== */

        .dashboard-section {
            margin-top: 28px;
        }

        .section-heading {
            color: var(--primary-dark);
            font-size: 15px;
            font-weight: 800;
        }

        .section-description {
            color: var(--muted);
            font-size: 11px;
            line-height: 1.7;
        }

        /* ==========================
           STATISTIC CARDS
        ========================== */

        .stat-card {
            position: relative;
            height: 100%;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 19px;
            transition: .2s;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 22px rgba(16,45,73,.06);
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 17px;
        }

        .stat-label {
            color: var(--muted);
            font-size: 10px;
            font-weight: 700;
            line-height: 1.5;
        }

        .stat-icon {
            width: 38px;
            height: 38px;
            flex-shrink: 0;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon-blue {
            background: #eaf2fa;
            color: #174a78;
        }

        .icon-green {
            background: #e6f6ef;
            color: #15966b;
        }

        .icon-orange {
            background: #fff3df;
            color: #bd821b;
        }

        .icon-red {
            background: #fceceb;
            color: #d9534f;
        }

        .icon-teal {
            background: #e4f4f2;
            color: #087f8c;
        }

        .stat-number {
            color: var(--primary-dark);
            font-size: 29px;
            line-height: 1.2;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .stat-caption {
            color: var(--muted);
            font-size: 10px;
            margin-top: 6px;
            line-height: 1.5;
        }

        .stat-indicator {
            display: inline-block;
            margin-top: 12px;
            font-size: 9px;
            font-weight: 700;
        }

        .indicator-positive {
            color: var(--success);
        }

        .indicator-warning {
            color: #b47b19;
        }

        /* ==========================
           CONTENT CARDS
        ========================== */

        .panel-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 22px;
            height: 100%;
        }

        /* ==========================
           OVERALL READINESS
        ========================== */

        .readiness-circle {
            width: 145px;
            height: 145px;
            border-radius: 50%;
            background: conic-gradient(
                #15966b 0% 23.3%,
                #eaf0f5 23.3% 100%
            );
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 8px auto 18px;
            position: relative;
        }

        .readiness-circle::before {
            content: "";
            position: absolute;
            inset: 12px;
            background: white;
            border-radius: 50%;
        }

        .readiness-circle-content {
            position: relative;
            text-align: center;
            z-index: 2;
        }

        .readiness-circle-content strong {
            display: block;
            color: var(--primary-dark);
            font-size: 29px;
            font-weight: 800;
        }

        .readiness-circle-content span {
            display: block;
            color: var(--muted);
            font-size: 9px;
        }

        .readiness-summary {
            background: #f6f8fb;
            border-radius: 11px;
            padding: 13px;
            text-align: center;
        }

        .readiness-summary strong {
            display: block;
            font-size: 19px;
            color: var(--primary-dark);
        }

        .readiness-summary span {
            font-size: 9px;
            color: var(--muted);
        }

        .summary-note {
            color: var(--muted);
            font-size: 10px;
            line-height: 1.7;
        }

        /* ==========================
           GAP RADAR
        ========================== */

        .radar-item {
            padding: 15px 0;
            border-bottom: 1px solid #edf0f4;
        }

        .radar-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .radar-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 9px;
        }

        .radar-name {
            color: #34445a;
            font-size: 11px;
            font-weight: 700;
        }

        .radar-value {
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 800;
        }

        .radar-progress {
            height: 9px;
            border-radius: 30px;
            background: #edf1f5;
            overflow: hidden;
        }

        .radar-progress-bar {
            height: 100%;
            border-radius: 30px;
            background: linear-gradient(
                90deg,
                #087f8c,
                #36b9a3
            );
        }

        .radar-progress-bar.high-gap {
            background: linear-gradient(
                90deg,
                #d18b22,
                #e8b34f
            );
        }

        .radar-caption {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            color: var(--muted);
            font-size: 9px;
            margin-top: 7px;
        }

        .priority-label {
            display: inline-block;
            color: #9a6816;
            background: #fff3df;
            border-radius: 30px;
            padding: 4px 8px;
            font-size: 8px;
            font-weight: 800;
        }

        /* ==========================
           GAP TO ACTION
        ========================== */

        .action-flow {
            background: linear-gradient(
                110deg,
                #102d49,
                #174a78 70%,
                #087f8c
            );
            border-radius: 15px;
            color: white;
            padding: 23px;
            overflow: hidden;
            position: relative;
        }

        .action-flow-title {
            color: white;
            font-size: 17px;
            font-weight: 800;
        }

        .action-flow-description {
            color: rgba(255,255,255,.73);
            font-size: 11px;
            line-height: 1.8;
        }

        .flow-step {
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.13);
            border-radius: 12px;
            padding: 15px;
            height: 100%;
        }

        .flow-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 27px;
            height: 27px;
            background: rgba(255,255,255,.17);
            color: white;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .flow-step strong {
            display: block;
            font-size: 11px;
            margin-bottom: 6px;
        }

        .flow-step span {
            color: rgba(255,255,255,.68);
            font-size: 9px;
            line-height: 1.6;
        }

        /* ==========================
           TASK STATUS
        ========================== */

        .task-status-box {
            background: #f7f9fb;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 15px;
        }

        .task-status-number {
            display: block;
            font-size: 23px;
            color: var(--primary-dark);
            font-weight: 800;
        }

        .task-status-label {
            display: block;
            color: var(--muted);
            font-size: 9px;
            margin-top: 4px;
        }

        .status-pending {
            color: #a36e16;
            background: #fff3df;
        }

        .status-progress {
            color: #1a6597;
            background: #e8f2fa;
        }

        .status-completed {
            color: #12825c;
            background: #e5f6ee;
        }

        .task-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 30px;
            padding: 6px 10px;
            font-size: 9px;
            font-weight: 800;
            white-space: nowrap;
        }

        /* ==========================
           TABLE
        ========================== */

        .task-table {
            min-width: 650px;
            margin-bottom: 0;
        }

        .task-table thead th {
            color: #8492a3;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .6px;
            text-transform: uppercase;
            padding: 13px 12px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        .task-table tbody td {
            color: #526579;
            font-size: 10px;
            padding: 15px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #edf0f4;
        }

        .task-table tbody tr:last-child td {
            border-bottom: none;
        }

        .task-title {
            display: block;
            color: var(--primary-dark);
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .task-subtitle {
            display: block;
            color: var(--muted);
            font-size: 9px;
        }

        .volunteer-avatar {
            width: 27px;
            height: 27px;
            border-radius: 8px;
            background: #eaf2fa;
            color: var(--primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            font-weight: 800;
            margin-right: 6px;
        }

        /* ==========================
           NOTE
        ========================== */

        .demo-notice {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            padding: 14px 16px;
            border: 1px solid #f0e3bf;
            background: #fff8e8;
            border-radius: 12px;
        }

        .demo-notice strong {
            display: block;
            color: #946819;
            font-size: 11px;
            margin-bottom: 4px;
        }

        .demo-notice p {
            color: #8c754c;
            font-size: 10px;
            line-height: 1.7;
            margin: 0;
        }

        /* ==========================
           FOOTER
        ========================== */

        .dashboard-footer {
            color: #98a4b2;
            border-top: 1px solid var(--border);
            padding-top: 20px;
            margin-top: 35px;
            font-size: 10px;
        }

        /* ==========================
           RESPONSIVE
        ========================== */

        @media (max-width: 1199px) {

            :root {
                --sidebar-width: 220px;
            }

            .main-panel {
                padding: 25px 20px;
            }

        }

        @media (max-width: 991px) {

            .dashboard-layout {
                display: block;
            }

            .sidebar {
                min-height: auto;
                padding: 12px 15px;
                display: block;
            }

            .sidebar-label,
            .sidebar-divider,
            .sidebar-bottom {
                display: none;
            }

            .sidebar-nav {
                display: flex;
                gap: 6px;
                overflow-x: auto;
                scrollbar-width: thin;
            }

            .sidebar-nav li {
                flex-shrink: 0;
                margin: 0;
            }

            .sidebar-link {
                min-height: 40px;
                padding: 10px 13px;
                font-size: 10px;
            }

            .main-panel {
                padding: 25px;
            }

        }

        @media (max-width: 767px) {

            .top-header {
                height: 66px;
                padding: 0 16px;
            }

            .brand-logo {
                width: 38px;
                height: 38px;
            }

            .brand-name {
                font-size: 14px;
            }

            .brand-subtitle {
                font-size: 8px;
            }

            .profile-details,
            .role-badge {
                display: none;
            }

            .profile-avatar {
                width: 36px;
                height: 36px;
            }

            .main-panel {
                padding: 22px 15px;
            }

            .dashboard-title {
                font-size: 23px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-number {
                font-size: 25px;
            }

            .panel-card {
                padding: 18px;
            }

            .action-flow {
                padding: 19px;
            }

        }

    </style>

</head>

<body>


<!-- ==========================================
     TOP HEADER
========================================== -->

<header class="top-header">

    <a href="index.php" class="brand">

        <div class="brand-logo">

            <svg xmlns="http://www.w3.org/2000/svg"
                 width="25"
                 height="25"
                 fill="currentColor"
                 viewBox="0 0 16 16">

                <path d="M8 1 1.5 4v4.5c0 3.2 2.3 5.4 6.5 6.5 4.2-1.1 6.5-3.3 6.5-6.5V4L8 1z"/>

                <path d="m8 3.2-2.5 4h1.6l-.4 2.7 2.8-4.1H7.8l.8-2.6z"
                      fill="#174a78"/>

            </svg>

        </div>

        <div>

            <span class="brand-name">
                SIAGA<span class="text-dark">KAMPUNG</span>
            </span>

            <span class="brand-subtitle">
                COMMUNITY DISASTER READINESS
            </span>

        </div>

    </a>


    <div class="header-profile">

        <span class="role-badge">
            <?= $dashboardEscape($roleBadgeLabel) ?>
        </span>

        <div class="profile-details text-end">

            <div class="profile-name">
                <?= $dashboardEscape($currentUser['name']) ?>
            </div>

            <span class="profile-role">
                <?= $dashboardEscape($currentRoleLabel) ?>
            </span>

        </div>

        <div class="profile-avatar" aria-label="Inisial pengguna">
            <?= $dashboardEscape($avatarInitials) ?>
        </div>

    </div>

</header>


<!-- ==========================================
     DASHBOARD LAYOUT
========================================== -->

<div class="dashboard-layout">


    <!-- ======================================
         SIDEBAR
    ====================================== -->

    <aside class="sidebar">

        <div class="sidebar-label">
            MENU UTAMA
        </div>


        <ul class="sidebar-nav">


            <!-- DASHBOARD -->

            <li>

                <a href="dashboard.php"
                   class="sidebar-link active">

                    <span class="sidebar-icon">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="19"
                             height="19"
                             fill="currentColor"
                             viewBox="0 0 16 16">

                            <path d="M8 1 1 6v9h5v-5h4v5h5V6L8 1z"/>

                        </svg>

                    </span>

                    Dashboard

                </a>

            </li>


            <!-- EVACUATION -->

            <li>

                <a href="evacuation-plan.php"
                   class="sidebar-link">

                    <span class="sidebar-icon">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="19"
                             height="19"
                             fill="currentColor"
                             viewBox="0 0 16 16">

                            <path d="M8 0a5 5 0 0 0-5 5c0 3.7 5 11 5 11s5-7.3 5-11a5 5 0 0 0-5-5zm0 7.5A2.5 2.5 0 1 1 8 2.5a2.5 2.5 0 0 1 0 5z"/>

                        </svg>

                    </span>

                    Rencana Evakuasi

                </a>

            </li>


            <!-- TASKS -->

            <li>

                <a href="community-tasks.php"
                   class="sidebar-link">

                    <span class="sidebar-icon">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="19"
                             height="19"
                             fill="currentColor"
                             viewBox="0 0 16 16">

                            <path d="M2 2h12v12H2V2zm2 2v1h8V4H4zm0 3v1h8V7H4zm0 3v1h5v-1H4z"/>

                        </svg>

                    </span>

                    Tugas Relawan

                </a>

            </li>


        </ul>


        <hr class="sidebar-divider">


        <div class="sidebar-label">
            NAVIGASI WEBSITE
        </div>


        <ul class="sidebar-nav">


            <li>

                <a href="index.php"
                   class="sidebar-link">

                    <span class="sidebar-icon">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="19"
                             height="19"
                             fill="currentColor"
                             viewBox="0 0 16 16">

                            <path d="M8 1 1 6v9h5v-5h4v5h5V6L8 1z"/>

                        </svg>

                    </span>

                    Halaman Utama

                </a>

            </li>


            <li>

                <a href="family-form.php"
                   class="sidebar-link">

                    <span class="sidebar-icon">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="19"
                             height="19"
                             fill="currentColor"
                             viewBox="0 0 16 16">

                            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM2 14s0-4 6-4 6 4 6 4H2z"/>

                        </svg>

                    </span>

                    Pendataan Keluarga

                </a>

            </li>


            <li>

                <a href="family-card.php"
                   class="sidebar-link">

                    <span class="sidebar-icon">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="19"
                             height="19"
                             fill="currentColor"
                             viewBox="0 0 16 16">

                            <path d="M2 3h12v10H2V3zm2 2v1h8V5H4zm0 3v1h5V8H4z"/>

                        </svg>

                    </span>

                    Kartu Siaga Keluarga

                </a>

            </li>


        </ul>


        <div class="sidebar-bottom">

            <div class="sidebar-info">

                <strong>
                    SIAGA KAMPUNG
                </strong>

                <p>

                    Satu Kampung, Satu Rencana
                    Kesiapsiagaan.

                </p>

            </div>

            <form action="actions/logout.php" method="POST" class="d-inline"
      onsubmit="return confirm('Apakah Anda yakin ingin keluar?');">

    <button type="submit" class="btn btn-outline-danger">
        Logout
    </button>

</form>

        </div>

    </aside>


    <!-- ======================================
         MAIN PANEL
    ====================================== -->

    <main class="main-panel">


        <!-- PAGE HEADER -->

        <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-end gap-3">

            <div>

                <div class="breadcrumb-text">

                    SIAGAKAMPUNG / <span>Dashboard</span>

                </div>

                <div class="d-flex align-items-center flex-wrap gap-2 mb-2">

                    <h1 class="dashboard-title mb-0">
                        Readiness Gap Radar
                    </h1>

                    <span class="demo-badge">
                        ● DATA DATABASE
                    </span>

                </div>

                <p class="dashboard-subtitle mb-0">

                    Pantau tingkat kesiapsiagaan keluarga,
                    identifikasi kekurangan, dan tindak lanjuti
                    melalui pendampingan relawan.

                </p>

            </div>


            <div class="d-flex flex-wrap gap-2">

                <a href="evacuation-plan.php"
                   class="secondary-action">

                    Lihat Evakuasi

                </a>

                <a href="community-tasks.php"
                   class="header-action">

                    + Kelola Tugas Relawan

                </a>

            </div>

        </div>


        <!-- ======================================
             STATISTIC CARDS
        ====================================== -->

        <section class="dashboard-section">


            <div class="d-flex justify-content-between align-items-center mb-3">

                <h2 class="section-heading mb-0">
                    Ringkasan Kesiapsiagaan
                </h2>

                <span class="text-secondary"
                      style="font-size:9px;">
                    DATA DATABASE
                </span>

            </div>


            <div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3">


                <!-- TOTAL FAMILIES -->

                <div class="col">

                    <div class="stat-card">

                        <div class="stat-top">

                            <span class="stat-label">
                                Total Keluarga
                            </span>

                            <div class="stat-icon icon-blue">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                     width="19"
                                     height="19"
                                     fill="currentColor"
                                     viewBox="0 0 16 16">

                                    <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM2 14s0-4 6-4 6 4 6 4H2z"/>

                                </svg>

                            </div>

                        </div>

                        <div class="stat-number">
                            <?= number_format($totalFamilies, 0, ',', '.') ?>
                        </div>

                        <div class="stat-caption">
                            Keluarga terdata
                        </div>

                        <span class="stat-indicator indicator-positive">
                            ● Data keluarga
                        </span>

                    </div>

                </div>


                <!-- COMPLETE CHECKLIST -->

                <div class="col">

                    <div class="stat-card">

                        <div class="stat-top">

                            <span class="stat-label">
                                Checklist Lengkap
                            </span>

                            <div class="stat-icon icon-green">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                     width="19"
                                     height="19"
                                     fill="currentColor"
                                     viewBox="0 0 16 16">

                                    <path d="M13.5 2 6 9.5 2.5 6 1 7.5l5 5 9-9L13.5 2z"/>

                                </svg>

                            </div>

                        </div>

                        <div class="stat-number">
                            <?= number_format($completeFamilies, 0, ',', '.') ?>
                        </div>

                        <div class="stat-caption">
                            Seluruh 5 indikator terpenuhi
                        </div>

                        <span class="stat-indicator indicator-positive">
                            ● <?= $dashboardPercentText($readinessPercent) ?>% dari keluarga
                        </span>

                    </div>

                </div>


                <!-- INCOMPLETE CHECKLIST -->

                <div class="col">

                    <div class="stat-card">

                        <div class="stat-top">

                            <span class="stat-label">
                                Checklist Belum Lengkap
                            </span>

                            <div class="stat-icon icon-orange">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                     width="19"
                                     height="19"
                                     fill="currentColor"
                                     viewBox="0 0 16 16">

                                    <path d="M8 1.5 1 14h14L8 1.5zm0 3.1 3.8 7H4.2L8 4.6zM7.25 7v2.5h1.5V7h-1.5zm0 3.2v1h1.5v-1h-1.5z"/>

                                </svg>

                            </div>

                        </div>

                        <div class="stat-number">
                            <?= number_format($incompleteFamilies, 0, ',', '.') ?>
                        </div>

                        <div class="stat-caption">
                            Masih memiliki kekurangan
                        </div>

                        <span class="stat-indicator indicator-warning">
                            ● Perlu pendampingan
                        </span>

                    </div>

                </div>


                <!-- PENDING TASKS -->

                <div class="col">

                    <div class="stat-card">

                        <div class="stat-top">

                            <span class="stat-label">
                                Tugas Pending
                            </span>

                            <div class="stat-icon icon-orange">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                     width="19"
                                     height="19"
                                     fill="currentColor"
                                     viewBox="0 0 16 16">

                                    <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm0 2a5 5 0 1 1 0 10A5 5 0 0 1 8 3zm-.5 1.5v4l3 1.8.8-1.3L9 7.8V4.5H7.5z"/>

                                </svg>

                            </div>

                        </div>

                        <div class="stat-number">
                            <?= number_format($taskCounts['pending'], 0, ',', '.') ?>
                        </div>

                        <div class="stat-caption">
                            Menunggu pelaksanaan
                        </div>

                        <span class="stat-indicator indicator-warning">
                            ● Perlu ditindaklanjuti
                        </span>

                    </div>

                </div>


                <!-- COMPLETED TASKS -->

                <div class="col">

                    <div class="stat-card">

                        <div class="stat-top">

                            <span class="stat-label">
                                Tugas Selesai
                            </span>

                            <div class="stat-icon icon-teal">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                     width="19"
                                     height="19"
                                     fill="currentColor"
                                     viewBox="0 0 16 16">

                                    <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm3.5 5-4 4-2-2L6.6 7.4l.9.9 2.9-2.9L11.5 6z"/>

                                </svg>

                            </div>

                        </div>

                        <div class="stat-number">
                            <?= number_format($taskCounts['completed'], 0, ',', '.') ?>
                        </div>

                        <div class="stat-caption">
                            Pendampingan selesai
                        </div>

                        <span class="stat-indicator indicator-positive">
                            ● Tugas completed
                        </span>

                    </div>

                </div>


            </div>

        </section>


        <!-- ======================================
             READINESS OVERVIEW + GAP RADAR
        ====================================== -->

        <section class="dashboard-section">

            <div class="row g-3">


                <!-- OVERALL READINESS -->

                <div class="col-xl-4">

                    <div class="panel-card">

                        <div class="mb-2">

                            <h2 class="section-heading mb-1">
                                Tingkat Kesiapsiagaan
                            </h2>

                            <p class="section-description mb-0">
                                Proporsi keluarga dengan seluruh checklist lengkap.
                            </p>

                        </div>


                        <div class="readiness-circle">

                            <div class="readiness-circle-content">

                                <strong>
                                    <?= $dashboardPercentText($readinessPercent) ?>%
                                </strong>

                                <span>
                                    Checklist lengkap
                                </span>

                            </div>

                        </div>


                        <div class="row g-2">

                            <div class="col-6">

                                <div class="readiness-summary">

                                    <strong class="text-success">
                                        <?= number_format($completeFamilies, 0, ',', '.') ?>
                                    </strong>

                                    <span>
                                        Keluarga lengkap
                                    </span>

                                </div>

                            </div>


                            <div class="col-6">

                                <div class="readiness-summary">

                                    <strong class="text-warning">
                                        <?= number_format($incompleteFamilies, 0, ',', '.') ?>
                                    </strong>

                                    <span>
                                        Belum lengkap
                                    </span>

                                </div>

                            </div>

                        </div>


                        <div class="mt-3">

                            <div class="d-flex justify-content-between mb-2">

                                <span class="text-secondary"
                                      style="font-size:10px;">
                                    Distribusi keluarga
                                </span>

                                <strong style="font-size:10px;">
                                    <?= number_format($totalFamilies, 0, ',', '.') ?> keluarga
                                </strong>

                            </div>


                            <div class="progress"
                                 style="height:9px;border-radius:30px;">

                                <div class="progress-bar bg-success"
                                     style="width:<?= $dashboardWidth($readinessPercent) ?>%;"
                                     role="progressbar"
                                     aria-valuenow="<?= $dashboardWidth($readinessPercent) ?>"
                                     aria-valuemin="0"
                                     aria-valuemax="100">
                                </div>

                                <div class="progress-bar"
                                     style="width:<?= $dashboardWidth($incompletePercent) ?>%;background:#e9b65b;"
                                     role="progressbar"
                                     aria-valuenow="<?= $dashboardWidth($incompletePercent) ?>"
                                     aria-valuemin="0"
                                     aria-valuemax="100">
                                </div>

                            </div>

                        </div>


                        <p class="summary-note mt-3 mb-0">

                            Angka menunjukkan keluarga yang telah
                            memenuhi seluruh lima indikator,
                            bukan jaminan bebas risiko bencana.

                        </p>

                    </div>

                </div>


                <!-- READINESS GAP RADAR -->

                <div class="col-xl-8">

                    <div class="panel-card">

                        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">

                            <div>

                                <h2 class="section-heading mb-1">
                                    Readiness Gap Radar
                                </h2>

                                <p class="section-description mb-0">

                                    Jumlah keluarga yang belum memenuhi
                                    masing-masing indikator kesiapsiagaan.

                                </p>

                            </div>

                            <span class="demo-badge align-self-start">
                                DATA DATABASE
                            </span>

                        </div>


                        <?php foreach ($gapIndicators as $indicator): ?>

                            <?php
                                $indicatorPercent = $dashboardPercent($indicator['count'], $totalFamilies);
                                $isLargestGap = $totalFamilies > 0
                                    && $largestGap > 0
                                    && $indicator['count'] === $largestGap;
                            ?>

                            <div class="radar-item">

                                <div class="radar-top">

                                    <div class="d-flex flex-wrap align-items-center gap-2">

                                        <span class="radar-name">
                                            <?= $dashboardEscape($indicator['label']) ?>
                                        </span>

                                        <?php if ($isLargestGap): ?>
                                            <span class="priority-label">
                                                GAP TERBESAR
                                            </span>
                                        <?php endif; ?>

                                    </div>

                                    <span class="radar-value">
                                        <?= number_format($indicator['count'], 0, ',', '.') ?> keluarga
                                    </span>

                                </div>

                                <div class="radar-progress">

                                    <div class="radar-progress-bar<?= $isLargestGap ? ' high-gap' : '' ?>"
                                         style="width:<?= $dashboardWidth($indicatorPercent) ?>%;">
                                    </div>

                                </div>

                                <div class="radar-caption">

                                    <span>
                                        <?= $dashboardPercentText($indicatorPercent) ?>% dari total keluarga
                                    </span>

                                    <span>
                                        <?= number_format($indicator['count'], 0, ',', '.') ?> / <?= number_format($totalFamilies, 0, ',', '.') ?>
                                    </span>

                                </div>

                            </div>

                        <?php endforeach; ?>

                        <div class="mt-3 p-3 rounded-3"
                             style="background:#fff8e8;">

                            <div class="d-flex gap-2 align-items-start">

                                <span style="color:#bd821b;">
                                    &#9888;
                                </span>

                                <p class="mb-0"
                                   style="font-size:10px;color:#8c754c;line-height:1.7;">

                                    <?php if ($largestGapLabel !== ''): ?>
                                        <?= $dashboardEscape($largestGapLabel) ?> memiliki jumlah kekurangan tertinggi,
                                        yaitu <?= number_format($largestGap, 0, ',', '.') ?> keluarga.
                                        Indikator ini dapat menjadi fokus awal pendampingan relawan.
                                    <?php elseif ($totalFamilies > 0): ?>
                                        Seluruh keluarga telah memenuhi lima indikator checklist kesiapsiagaan.
                                    <?php else: ?>
                                        Belum ada data keluarga. Tambahkan data keluarga untuk mulai melihat Readiness Gap.
                                    <?php endif; ?>

                                </p>

                            </div>

                        </div>


                    </div>

                </div>

            </div>

        </section>


        <!-- ======================================
             GAP TO ACTION SYSTEM
        ====================================== -->

        <section class="dashboard-section">

            <div class="action-flow">

                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">

                    <div>

                        <span style="font-size:9px;letter-spacing:1.5px;color:#8ed8d1;font-weight:800;">

                            CORE INNOVATION

                        </span>

                        <h2 class="action-flow-title mt-2 mb-2">
                            Gap-to-Action System
                        </h2>

                        <p class="action-flow-description mb-0">

                            Mengubah data kekurangan kesiapsiagaan
                            menjadi tindakan pendampingan nyata
                            melalui kolaborasi koordinator dan relawan.

                        </p>

                    </div>


                    <div>

                        <a href="community-tasks.php"
                           class="btn btn-light rounded-3 px-4 py-3 fw-bold"
                           style="font-size:11px;">

                            Kelola Tugas &rarr;

                        </a>

                    </div>

                </div>


                <div class="row g-3">


                    <div class="col-md-4">

                        <div class="flow-step">

                            <div class="flow-number">
                                01
                            </div>

                            <strong>
                                Identifikasi Gap
                            </strong>

                            <span>

                                Sistem mengidentifikasi indikator
                                kesiapsiagaan yang belum dipenuhi
                                keluarga.

                            </span>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="flow-step">

                            <div class="flow-number">
                                02
                            </div>

                            <strong>
                                Buat Tugas Pendampingan
                            </strong>

                            <span>

                                Koordinator membuat tugas berdasarkan
                                kebutuhan keluarga dan menetapkan relawan.

                            </span>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="flow-step">

                            <div class="flow-number">
                                03
                            </div>

                            <strong>
                                Pantau Hasil
                            </strong>

                            <span>

                                Relawan memperbarui status tugas
                                dan dashboard mengikuti data terbaru.

                            </span>

                        </div>

                    </div>


                </div>

            </div>

        </section>


        <!-- ======================================
             VOLUNTEER TASK MONITORING
        ====================================== -->

        <section class="dashboard-section">


            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">

                <div>

                    <h2 class="section-heading mb-1">
                        Monitoring Tugas Relawan
                    </h2>

                    <p class="section-description mb-0">

                        Ringkasan status tugas pendampingan
                        kesiapsiagaan keluarga.

                    </p>

                </div>


                <a href="community-tasks.php"
                   class="secondary-action align-self-start">

                    Lihat Semua Tugas &rarr;

                </a>

            </div>


            <!-- TASK STATUS SUMMARY -->

            <div class="row g-3 mb-3">


                <div class="col-md-4">

                    <div class="task-status-box">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <span class="task-status-number">
                                    <?= number_format($taskCounts['pending'], 0, ',', '.') ?>
                                </span>

                                <span class="task-status-label">
                                    Tugas menunggu pelaksanaan
                                </span>

                            </div>

                            <span class="task-badge status-pending">
                                PENDING
                            </span>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="task-status-box">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <span class="task-status-number">
                                    <?= number_format($taskCounts['in_progress'], 0, ',', '.') ?>
                                </span>

                                <span class="task-status-label">
                                    Tugas sedang berjalan
                                </span>

                            </div>

                            <span class="task-badge status-progress">
                                IN PROGRESS
                            </span>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="task-status-box">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <span class="task-status-number">
                                    <?= number_format($taskCounts['completed'], 0, ',', '.') ?>
                                </span>

                                <span class="task-status-label">
                                    Tugas telah diselesaikan
                                </span>

                            </div>

                            <span class="task-badge status-completed">
                                COMPLETED
                            </span>

                        </div>

                    </div>

                </div>


            </div>


            <!-- TASK TABLE -->

            <div class="panel-card p-0 overflow-hidden">


                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-3 border-bottom">

                    <div>

                        <h3 class="section-heading mb-1">
                            Daftar Aktivitas Pendampingan
                        </h3>

                        <p class="section-description mb-0">
                            Lima aktivitas pendampingan terbaru dari database.
                        </p>

                    </div>

                    <span class="demo-badge">
                        DATA DATABASE
                    </span>

                </div>


                <div class="table-responsive">

                    <table class="table task-table align-middle">

                        <thead>

                            <tr>

                                <th>
                                    TUGAS
                                </th>

                                <th>
                                    KELUARGA
                                </th>

                                <th>
                                    RELAWAN
                                </th>

                                <th>
                                    STATUS
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php if (empty($recentTasks)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-secondary">
                                        Belum ada aktivitas pendampingan yang tercatat.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentTasks as $task): ?>
                                    <?php
                                        $taskStatus = $task['status'];
                                        $statusView = $statusPresentation[$taskStatus]
                                            ?? ['label' => strtoupper(str_replace('_', ' ', $taskStatus)), 'class' => 'status-pending'];
                                        $volunteerName = (string) $task['volunteer_name'];
                                        $volunteerInitial = function_exists('mb_substr')
                                            ? mb_substr($volunteerName, 0, 1, 'UTF-8')
                                            : substr($volunteerName, 0, 1);
                                        $taskDescription = trim((string) ($task['task_description'] ?? ''));
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="task-title">
                                                <?= $dashboardEscape($task['task_title']) ?>
                                            </span>
                                            <span class="task-subtitle">
                                                <?= $dashboardEscape($taskDescription !== '' ? $taskDescription : 'Tugas pendampingan kesiapsiagaan keluarga') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= $dashboardEscape($task['family_name']) ?>
                                        </td>
                                        <td>
                                            <span class="volunteer-avatar">
                                                <?= $dashboardEscape(strtoupper($volunteerInitial)) ?>
                                            </span>
                                            <?= $dashboardEscape($volunteerName) ?>
                                        </td>
                                        <td>
                                            <span class="task-badge <?= $dashboardEscape($statusView['class']) ?>">
                                                <?= $dashboardEscape($statusView['label']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>


                <div class="p-3 border-top text-center">

                    <a href="community-tasks.php"
                       class="text-primary fw-bold"
                       style="font-size:11px;">

                        Buka Pengelolaan Tugas Relawan &rarr;

                    </a>

                </div>


            </div>

        </section>


        <!-- ======================================
             DEMO NOTICE
        ====================================== -->

        <section class="dashboard-section">

            <div class="demo-notice">

                <span class="fs-5">
                    &#9432;
                </span>

                <div>

                    <strong>
                        Informasi Data Dashboard
                    </strong>

                    <p>

                        Statistik kesiapsiagaan, Readiness Gap, dan status tugas
                        pada halaman ini dihitung langsung dari database SIAGAKAMPUNG
                        setiap kali dashboard dimuat. Keluarga tanpa checklist
                        dihitung sebagai belum lengkap. Data kontak darurat keluarga
                        tidak ditampilkan pada dashboard.

                    </p>

                </div>

            </div>

        </section>


        <!-- ======================================
             FOOTER
        ====================================== -->

        <footer class="dashboard-footer">

            <div class="d-flex flex-column flex-md-row justify-content-between gap-2">

                <div>
                    &copy; 2026 SIAGAKAMPUNG. All Rights Reserved.
                </div>

                <div>
                    Satu Kampung, Satu Rencana Kesiapsiagaan.
                </div>

            </div>

        </footer>


    </main>

</div>


<!-- ==========================================
     BOOTSTRAP JAVASCRIPT
========================================== -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
