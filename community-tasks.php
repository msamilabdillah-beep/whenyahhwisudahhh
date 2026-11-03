<?php

declare(strict_types=1);

/**
 * =====================================================
 * SIAGAKAMPUNG - COMMUNITY TASKS
 * Database-integrated version
 * =====================================================
 */

require_once __DIR__ . '/includes/auth.php';

// Community Tasks is available only to authenticated system users.
siaga_require_role(['admin', 'coordinator', 'volunteer']);

require_once __DIR__ . '/includes/config.php';

if (!function_exists('h')) {
    function h($value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$currentUser = siaga_current_user();

if ($currentUser === null) {
    http_response_code(401);
    exit('Sesi pengguna tidak valid. Silakan login kembali.');
}

$currentRole = $currentUser['role'];
$currentUserId = (int) $currentUser['id'];
$currentUserName = $currentUser['name'];

$roleLabels = [
    'admin' => 'Administrator',
    'coordinator' => 'Koordinator',
    'volunteer' => 'Relawan'
];

$canManageTasks = in_array($currentRole, ['admin', 'coordinator'], true);

// Read and consume one-time notifications set by action handlers.
$successMessage = isset($_SESSION['task_success']) && is_string($_SESSION['task_success'])
    ? $_SESSION['task_success']
    : '';
$errorMessage = isset($_SESSION['task_error']) && is_string($_SESSION['task_error'])
    ? $_SESSION['task_error']
    : '';
unset($_SESSION['task_success'], $_SESSION['task_error']);

$families = [];
$volunteers = [];
$visibleTasks = [];
$pageLoadError = '';

try {
    // Managers need real family and volunteer options for task creation.
    if ($canManageTasks) {
        $familyResult = $conn->query(
            "SELECT id, family_name, neighborhood
             FROM families
             ORDER BY family_name ASC, id ASC"
        );

        while ($family = $familyResult->fetch_assoc()) {
            $families[] = [
                'id' => (int) $family['id'],
                'name' => $family['family_name'],
                'area' => $family['neighborhood']
            ];
        }
        $familyResult->free();

        $volunteerResult = $conn->query(
            "SELECT id, name
             FROM users
             WHERE role = 'volunteer'
             ORDER BY name ASC, id ASC"
        );

        while ($volunteer = $volunteerResult->fetch_assoc()) {
            $volunteers[] = [
                'id' => (int) $volunteer['id'],
                'name' => $volunteer['name']
            ];
        }
        $volunteerResult->free();
    }

    $taskSql = "SELECT
                    ct.id,
                    ct.family_id,
                    ct.assigned_to,
                    ct.task_title,
                    ct.task_description,
                    ct.status,
                    ct.created_at,
                    ct.completed_at,
                    f.family_name,
                    f.neighborhood AS area,
                    u.name AS volunteer
                FROM community_tasks ct
                INNER JOIN families f ON f.id = ct.family_id
                INNER JOIN users u ON u.id = ct.assigned_to";

    if ($currentRole === 'volunteer') {
        // Volunteers can only retrieve tasks assigned to their own account.
        $taskSql .= " WHERE ct.assigned_to = ?";
        $taskSql .= " ORDER BY ct.created_at DESC, ct.id DESC";

        $taskStmt = $conn->prepare($taskSql);
        $taskStmt->bind_param('i', $currentUserId);
        $taskStmt->execute();
        $taskStmt->bind_result(
            $taskId,
            $taskFamilyId,
            $taskAssignedTo,
            $taskTitle,
            $taskDescription,
            $taskStatus,
            $taskCreatedAt,
            $taskCompletedAt,
            $familyName,
            $familyArea,
            $volunteerName
        );

        while ($taskStmt->fetch()) {
            $timestamp = strtotime((string) $taskCreatedAt);
            $visibleTasks[] = [
                'id' => (int) $taskId,
                'family_id' => (int) $taskFamilyId,
                'assigned_to' => (int) $taskAssignedTo,
                'task_title' => (string) $taskTitle,
                'task_description' => (string) ($taskDescription ?? ''),
                'status' => (string) $taskStatus,
                'created_at' => (string) $taskCreatedAt,
                'created_at_label' => $timestamp !== false ? date('d M Y', $timestamp) : '-',
                'completed_at' => $taskCompletedAt,
                'family_name' => (string) $familyName,
                'area' => (string) $familyArea,
                'volunteer' => (string) $volunteerName
            ];
        }
        $taskStmt->close();
    } else {
        // Admin and Coordinator can monitor all tasks.
        $taskSql .= " ORDER BY ct.created_at DESC, ct.id DESC";
        $taskResult = $conn->query($taskSql);

        while ($task = $taskResult->fetch_assoc()) {
            $timestamp = strtotime((string) $task['created_at']);
            $visibleTasks[] = [
                'id' => (int) $task['id'],
                'family_id' => (int) $task['family_id'],
                'assigned_to' => (int) $task['assigned_to'],
                'task_title' => (string) $task['task_title'],
                'task_description' => (string) ($task['task_description'] ?? ''),
                'status' => (string) $task['status'],
                'created_at' => (string) $task['created_at'],
                'created_at_label' => $timestamp !== false ? date('d M Y', $timestamp) : '-',
                'completed_at' => $task['completed_at'],
                'family_name' => (string) $task['family_name'],
                'area' => (string) $task['area'],
                'volunteer' => (string) $task['volunteer']
            ];
        }
        $taskResult->free();
    }
} catch (Throwable $e) {
    error_log('[SIAGAKAMPUNG COMMUNITY TASKS LOAD ERROR] ' . $e->getMessage());
    $pageLoadError = 'Data tugas belum dapat dimuat. Periksa koneksi database dan struktur tabel, lalu muat ulang halaman.';
    $families = [];
    $volunteers = [];
    $visibleTasks = [];
}

// Statistics are calculated from the database rows visible to this user.
$totalTasks = count($visibleTasks);
$pendingTasks = 0;
$progressTasks = 0;
$completedTasks = 0;

foreach ($visibleTasks as $task) {
    if ($task['status'] === 'pending') {
        $pendingTasks++;
    } elseif ($task['status'] === 'in_progress') {
        $progressTasks++;
    } elseif ($task['status'] === 'completed') {
        $completedTasks++;
    }
}

$completionRate = $totalTasks > 0
    ? (int) round(($completedTasks / $totalTasks) * 100)
    : 0;

$statusLabels = [
    'pending' => 'Menunggu',
    'in_progress' => 'Sedang Berjalan',
    'completed' => 'Selesai'
];

$statusClasses = [
    'pending' => 'status-pending',
    'in_progress' => 'status-progress',
    'completed' => 'status-completed'
];

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pengelolaan Tugas Relawan | SIAGAKAMPUNG</title>

    <meta name="description" content="Pengelolaan tugas pendampingan relawan SIAGAKAMPUNG.">

    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CUSTOM CSS -->
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

        /* HEADER */

        .top-header {
            height: 76px;
            background: white;
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

        /* LAYOUT */

        .dashboard-layout {
            display: grid;
            grid-template-columns: 250px minmax(0, 1fr);
            min-height: calc(100vh - 76px);
        }

        /* SIDEBAR */

        .sidebar {
            background: var(--primary-dark);
            color: white;
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

        .sidebar-divider {
            border-color: rgba(255,255,255,.12);
            margin: 22px 0;
        }

        .sidebar-bottom {
            margin-top: auto;
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

        /* MAIN */

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

        .page-title {
            color: var(--primary-dark);
            font-size: clamp(23px, 3vw, 29px);
            font-weight: 800;
            letter-spacing: -.5px;
        }

        .page-description {
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
        }

        /* SECTION */

        .section {
            margin-top: 27px;
        }

        .section-title {
            color: var(--primary-dark);
            font-size: 15px;
            font-weight: 800;
        }

        .section-description {
            color: var(--muted);
            font-size: 11px;
            line-height: 1.7;
        }

        /* BUTTONS */

        .primary-button {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--primary);
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 9px;
            font-size: 11px;
            font-weight: 700;
            transition: .2s;
        }

        .primary-button:hover {
            color: white;
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .secondary-button {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: white;
            border: 1px solid var(--border);
            color: var(--primary);
            padding: 9px 14px;
            border-radius: 9px;
            font-size: 10px;
            font-weight: 700;
        }

        /* STAT CARDS */

        .stat-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 19px;
            height: 100%;
            transition: .2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 22px rgba(16,45,73,.06);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .stat-label {
            color: var(--muted);
            font-size: 10px;
            font-weight: 700;
        }

        .stat-icon {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .icon-blue {
            background: #eaf2fa;
            color: #174a78;
        }

        .icon-orange {
            background: #fff3df;
            color: #bd821b;
        }

        .icon-teal {
            background: #e4f4f2;
            color: #087f8c;
        }

        .icon-green {
            background: #e6f6ef;
            color: #15966b;
        }

        .stat-number {
            color: var(--primary-dark);
            font-size: 29px;
            font-weight: 800;
            line-height: 1.2;
        }

        .stat-caption {
            color: var(--muted);
            font-size: 10px;
            margin-top: 6px;
        }

        /* PANELS */

        .panel-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 22px;
        }

        /* FORM */

        .form-label {
            color: #34445a;
            font-size: 10px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            min-height: 43px;
            border: 1px solid #e1e8ef;
            border-radius: 9px;
            color: #34445a;
            font-size: 11px;
            box-shadow: none !important;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #7ba5c5;
            box-shadow: 0 0 0 3px rgba(23,74,120,.08) !important;
        }

        textarea.form-control {
            min-height: 95px;
            resize: vertical;
        }

        .form-hint {
            color: #8b99a9;
            font-size: 9px;
            line-height: 1.6;
            margin-top: 6px;
        }

        /* FILTER */

        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .filter-btn {
            padding: 9px 14px;
            border: 1px solid var(--border);
            background: white;
            color: #718096;
            border-radius: 30px;
            font-size: 10px;
            font-weight: 700;
            transition: .2s;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        /* STATUS */

        .task-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 30px;
            padding: 7px 10px;
            font-size: 9px;
            font-weight: 800;
            white-space: nowrap;
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

        /* TASK TABLE */

        .task-table {
            min-width: 900px;
            margin-bottom: 0;
        }

        .task-table thead th {
            color: #8492a3;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .6px;
            text-transform: uppercase;
            padding: 14px 12px;
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

        .task-name {
            display: block;
            color: var(--primary-dark);
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .task-description {
            color: var(--muted);
            font-size: 9px;
            line-height: 1.6;
            max-width: 220px;
        }

        .family-name {
            color: #34445a;
            font-size: 10px;
            font-weight: 700;
        }

        .family-area {
            display: block;
            color: var(--muted);
            font-size: 9px;
            margin-top: 4px;
        }

        .volunteer-avatar {
            width: 29px;
            height: 29px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eaf2fa;
            color: var(--primary);
            border-radius: 9px;
            font-size: 10px;
            font-weight: 800;
            margin-right: 6px;
        }

        .update-form {
            min-width: 155px;
        }

        .update-form .form-select {
            min-height: 34px;
            font-size: 9px;
            margin-bottom: 5px;
        }

        .update-form button {
            width: 100%;
            border: none;
            background: var(--primary);
            color: white;
            border-radius: 7px;
            padding: 7px;
            font-size: 9px;
            font-weight: 700;
        }

        .update-form button:hover {
            background: var(--primary-dark);
        }

        /* INFO PANEL */

        .info-panel {
            background: linear-gradient(110deg, #102d49, #174a78, #087f8c);
            color: white;
            border-radius: 15px;
            padding: 22px;
        }

        .info-panel h3 {
            color: white;
            font-size: 16px;
            font-weight: 800;
        }

        .info-panel p {
            color: rgba(255,255,255,.75);
            font-size: 10px;
            line-height: 1.8;
        }

        .info-step {
            padding: 13px;
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.13);
            border-radius: 11px;
            height: 100%;
        }

        .info-step strong {
            display: block;
            font-size: 10px;
            margin-bottom: 5px;
        }

        .info-step span {
            display: block;
            color: rgba(255,255,255,.7);
            font-size: 9px;
            line-height: 1.6;
        }

        /* EMPTY STATE */

        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: var(--muted);
            font-size: 11px;
        }

        .empty-state strong {
            display: block;
            color: var(--primary-dark);
            font-size: 14px;
            margin-bottom: 7px;
        }

        /* FOOTER */

        .dashboard-footer {
            color: #98a4b2;
            border-top: 1px solid var(--border);
            padding-top: 20px;
            margin-top: 35px;
            font-size: 10px;
        }

        /* RESPONSIVE */

        @media(max-width:1199px) {

            .main-panel {
                padding: 25px 20px;
            }

        }

        @media(max-width:991px) {

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

        @media(max-width:767px) {

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

            .brand-subtitle,
            .profile-details,
            .role-badge {
                display: none;
            }

            .main-panel {
                padding: 22px 15px;
            }

            .page-title {
                font-size: 23px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-number {
                font-size: 25px;
            }

            .panel-card {
                padding: 17px;
            }

        }

    </style>

</head>


<body>


<!-- ==========================================
     HEADER
========================================== -->

<header class="top-header">

    <a href="index.php" class="brand">

        <div class="brand-logo">

            <svg width="25" height="25" fill="currentColor" viewBox="0 0 16 16">

                <path d="M8 1 1.5 4v4.5c0 3.2 2.3 5.4 6.5 6.5 4.2-1.1 6.5-3.3 6.5-6.5V4L8 1z"/>

                <path d="m8 3.2-2.5 4h1.6l-.4 2.7 2.8-4.1H7.8l.8-2.6z" fill="#174a78"/>

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
            <?= h(strtoupper($roleLabels[$currentRole] ?? $currentRole)) ?>
        </span>

        <div class="text-end">

            <div class="profile-name">
                <?= h($currentUserName) ?>
            </div>

            <span class="profile-role">
                <?= h($roleLabels[$currentRole] ?? $currentRole) ?>
            </span>

        </div>

        <div class="profile-avatar">
            <?= h(strtoupper(substr($currentUserName, 0, 1))) ?>
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


            <li>

                <a href="dashboard.php" class="sidebar-link">

                    <span>▦</span>

                    Dashboard

                </a>

            </li>


            <li>

                <a href="evacuation-plan.php" class="sidebar-link">

                    <span>⌖</span>

                    Rencana Evakuasi

                </a>

            </li>


            <li>

                <a href="community-tasks.php" class="sidebar-link active">

                    <span>☑</span>

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

                <a href="index.php" class="sidebar-link">

                    <span>⌂</span>

                    Halaman Utama

                </a>

            </li>


            <li>

                <a href="family-form.php" class="sidebar-link">

                    <span>♙</span>

                    Pendataan Keluarga

                </a>

            </li>


            <li>

                <a href="family-card.php" class="sidebar-link">

                    <span>▤</span>

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
                    Satu Kampung, Satu Rencana Kesiapsiagaan.
                </p>

            </div>


            <form action="actions/logout.php" method="POST" class="mt-3">

                <button type="submit"
                        class="sidebar-link"
                        style="width:100%;border:0;background:transparent;text-align:left;font:inherit;cursor:pointer;">

                    <span>⇥</span>

                    Keluar

                </button>

            </form>

        </div>

    </aside>


    <!-- ======================================
         MAIN CONTENT
    ====================================== -->

    <main class="main-panel">


        <!-- PAGE HEADER -->

        <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-end gap-3">

            <div>

                <div class="breadcrumb-text">

                    SIAGAKAMPUNG /
                    <span>Pengelolaan Tugas Relawan</span>

                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">

                    <h1 class="page-title mb-0">
                        Community Tasks
                    </h1>

                    <span class="demo-badge">
                        ● DATA DATABASE
                    </span>

                </div>

                <p class="page-description mb-0">

                    Kelola tugas pendampingan keluarga,
                    tetapkan relawan, dan pantau progres
                    kesiapsiagaan masyarakat.

                </p>

            </div>


            <div class="d-flex gap-2 flex-wrap">

                <a href="dashboard.php" class="secondary-button">
                    &larr; Dashboard
                </a>

                <?php if ($canManageTasks): ?>

                    <a href="#createTask" class="primary-button">
                        + Buat Tugas Baru
                    </a>

                <?php endif; ?>

            </div>

        </div>

        <?php if ($successMessage !== ''): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3 mb-0" role="alert">
                <?= h($successMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-3 mb-0" role="alert">
                <?= h($errorMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        <?php endif; ?>

        <?php if ($pageLoadError !== ''): ?>
            <div class="alert alert-warning mt-3 mb-0" role="alert">
                <?= h($pageLoadError) ?>
            </div>
        <?php endif; ?>


        <!-- ======================================
             STATISTICS
        ====================================== -->

        <section class="section">


            <div class="d-flex justify-content-between align-items-center mb-3">

                <h2 class="section-title mb-0">
                    Ringkasan Tugas
                </h2>

                <span style="font-size:9px;color:#8b99a9;">
                    <?= h($roleLabels[$currentRole]) ?> VIEW
                </span>

            </div>


            <div class="row row-cols-2 row-cols-md-4 g-3">


                <!-- TOTAL -->

                <div class="col">

                    <div class="stat-card">

                        <div class="stat-header">

                            <span class="stat-label">
                                Total Tugas
                            </span>

                            <div class="stat-icon icon-blue">
                                <span class="fs-5">▤</span>
                            </div>

                        </div>

                        <div class="stat-number">
                            <?= $totalTasks ?>
                        </div>

                        <div class="stat-caption">
                            Seluruh tugas terdaftar
                        </div>

                    </div>

                </div>


                <!-- PENDING -->

                <div class="col">

                    <div class="stat-card">

                        <div class="stat-header">

                            <span class="stat-label">
                                Pending
                            </span>

                            <div class="stat-icon icon-orange">
                                <span class="fs-5">◷</span>
                            </div>

                        </div>

                        <div class="stat-number">
                            <?= $pendingTasks ?>
                        </div>

                        <div class="stat-caption">
                            Menunggu pelaksanaan
                        </div>

                    </div>

                </div>


                <!-- IN PROGRESS -->

                <div class="col">

                    <div class="stat-card">

                        <div class="stat-header">

                            <span class="stat-label">
                                In Progress
                            </span>

                            <div class="stat-icon icon-blue">
                                <span class="fs-5">↻</span>
                            </div>

                        </div>

                        <div class="stat-number">
                            <?= $progressTasks ?>
                        </div>

                        <div class="stat-caption">
                            Sedang dikerjakan
                        </div>

                    </div>

                </div>


                <!-- COMPLETED -->

                <div class="col">

                    <div class="stat-card">

                        <div class="stat-header">

                            <span class="stat-label">
                                Completed
                            </span>

                            <div class="stat-icon icon-green">
                                <span class="fs-5">✓</span>
                            </div>

                        </div>

                        <div class="stat-number">
                            <?= $completedTasks ?>
                        </div>

                        <div class="stat-caption">
                            Tugas telah diselesaikan
                        </div>

                    </div>

                </div>


            </div>

        </section>


        <!-- ======================================
             COMPLETION PROGRESS
        ====================================== -->

        <section class="section">

            <div class="panel-card">

                <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">

                    <div>

                        <h2 class="section-title mb-1">
                            Progres Penyelesaian Tugas
                        </h2>

                        <p class="section-description mb-0">
                            Persentase tugas selesai dari seluruh tugas yang ditampilkan.
                        </p>

                    </div>

                    <strong style="color:#15966b;font-size:23px;">
                        <?= $completionRate ?>%
                    </strong>

                </div>


                <div class="progress"
                     style="height:11px;border-radius:30px;background:#edf1f5;">

                    <div class="progress-bar"
                         role="progressbar"
                         style="width:<?= $completionRate ?>%;background:#15966b;border-radius:30px;"
                         aria-valuenow="<?= $completionRate ?>"
                         aria-valuemin="0"
                         aria-valuemax="100">

                    </div>

                </div>


                <div class="d-flex justify-content-between mt-2">

                    <span style="font-size:9px;color:#8b99a9;">
                        <?= $completedTasks ?> tugas selesai
                    </span>

                    <span style="font-size:9px;color:#8b99a9;">
                        <?= $totalTasks ?> total tugas
                    </span>

                </div>

            </div>

        </section>


        <!-- ======================================
             CREATE TASK FORM
        ====================================== -->

        <?php if ($canManageTasks): ?>

        <section class="section" id="createTask">


            <div class="panel-card">


                <div class="d-flex justify-content-between align-items-start gap-3 mb-4">

                    <div>

                        <h2 class="section-title mb-1">
                            Buat Tugas Pendampingan
                        </h2>

                        <p class="section-description mb-0">

                            Buat tugas berdasarkan kebutuhan
                            kesiapsiagaan keluarga dan tetapkan
                            relawan yang bertanggung jawab.

                        </p>

                    </div>

                    <span class="stat-icon icon-teal">
                        <span class="fs-5">+</span>
                    </span>

                </div>


                <!--
                KONTRAK BACKEND:
                ACTION: actions/task_save.php
                METHOD: POST

                INPUT:
                family_id
                assigned_to
                task_title
                task_description
                -->


                <form id="taskForm"
                      action="actions/task_save.php"
                      method="POST">


                    <div class="row g-3">


                        <!-- FAMILY -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Pilih Keluarga
                                <span class="text-danger">*</span>
                            </label>

                            <select class="form-select"
                                    name="family_id"
                                    required>

                                <option value="">
                                    Pilih keluarga yang akan didampingi
                                </option>

                                <?php if (empty($families)): ?>
                                    <option value="" disabled>Belum ada data keluarga</option>
                                <?php else: ?>
                                    <?php foreach ($families as $family): ?>
                                        <option value="<?= h($family['id']) ?>">
                                            <?= h($family['name']) ?> (<?= h($family['area']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                            </select>

                            <div class="form-hint">
                                Pilih keluarga yang membutuhkan pendampingan.
                            </div>

                        </div>


                        <!-- VOLUNTEER -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Tetapkan Relawan
                                <span class="text-danger">*</span>
                            </label>

                            <select class="form-select"
                                    name="assigned_to"
                                    required>

                                <option value="">
                                    Pilih relawan pelaksana
                                </option>

                                <?php if (empty($volunteers)): ?>
                                    <option value="" disabled>Belum ada akun relawan</option>
                                <?php else: ?>
                                    <?php foreach ($volunteers as $volunteer): ?>
                                        <option value="<?= h($volunteer['id']) ?>">
                                            <?= h($volunteer['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                            </select>

                            <div class="form-hint">
                                Pilih relawan yang bertanggung jawab.
                            </div>

                        </div>


                        <!-- TASK TITLE -->

                        <div class="col-12">

                            <label class="form-label">
                                Judul Tugas
                                <span class="text-danger">*</span>
                            </label>

                            <input type="text"
                                   class="form-control"
                                   name="task_title"
                                   maxlength="150"
                                   placeholder="Contoh: Pendampingan persiapan tas siaga"
                                   required>

                        </div>


                        <!-- DESCRIPTION -->

                        <div class="col-12">

                            <label class="form-label">
                                Deskripsi Tugas
                            </label>

                            <textarea class="form-control"
                                      name="task_description"
                                      placeholder="Jelaskan kegiatan pendampingan yang harus dilakukan relawan..."></textarea>

                            <div class="form-hint">
                                Jelaskan target dan kegiatan yang perlu dilaksanakan.
                            </div>

                        </div>


                        <!-- SUBMIT -->

                        <div class="col-12">

                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pt-2">

                                <span style="font-size:9px;color:#8b99a9;">

                                    Status awal tugas akan menjadi PENDING.

                                </span>

                                <button type="reset"
                                        class="secondary-button">

                                    Reset Form

                                </button>

                                <button type="submit"
                                        class="primary-button"
                                        <?= (empty($families) || empty($volunteers) || $pageLoadError !== '') ? 'disabled' : '' ?>>

                                    + Simpan dan Tetapkan Tugas

                                </button>

                            </div>

                        </div>


                    </div>

                </form>


            </div>

        </section>

        <?php endif; ?>


        <!-- ======================================
             TASK LIST
        ====================================== -->

        <section class="section">


            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">


                <div>

                    <h2 class="section-title mb-1">
                        Daftar Tugas Relawan
                    </h2>

                    <p class="section-description mb-0">

                        Pantau tugas pendampingan berdasarkan
                        keluarga, relawan, dan status pelaksanaan.

                    </p>

                </div>


                <div class="demo-badge align-self-start">
                    DATA DATABASE
                </div>


            </div>


            <div class="panel-card p-0 overflow-hidden">


                <!-- FILTER AND SEARCH -->

                <div class="p-3 border-bottom">


                    <div class="d-flex flex-column flex-xl-row justify-content-between gap-3">


                        <div class="filter-bar">

                            <button type="button"
                                    class="filter-btn active"
                                    data-filter="all">

                                Semua

                            </button>

                            <button type="button"
                                    class="filter-btn"
                                    data-filter="pending">

                                Pending

                            </button>

                            <button type="button"
                                    class="filter-btn"
                                    data-filter="in_progress">

                                In Progress

                            </button>

                            <button type="button"
                                    class="filter-btn"
                                    data-filter="completed">

                                Completed

                            </button>

                        </div>


                        <div style="min-width:230px;">

                            <input type="search"
                                   id="taskSearch"
                                   class="form-control"
                                   placeholder="Cari tugas, keluarga, relawan...">

                        </div>


                    </div>

                </div>


                <!-- TABLE -->

                <div class="table-responsive">


                    <table class="table task-table align-middle"
                           id="taskTable">


                        <thead>

                            <tr>

                                <th>
                                    TUGAS PENDAMPINGAN
                                </th>

                                <th>
                                    KELUARGA
                                </th>

                                <th>
                                    RELAWAN
                                </th>

                                <th>
                                    TANGGAL
                                </th>

                                <th>
                                    STATUS
                                </th>

                                <th>
                                    PEMBARUAN
                                </th>

                            </tr>

                        </thead>


                        <tbody id="taskTableBody">


                            <?php foreach ($visibleTasks as $task): ?>


                                <?php

                                $canUpdateTask = (
                                    in_array($currentRole, ['admin', 'coordinator'], true)
                                    ||
                                    (
                                        $currentRole === 'volunteer'
                                        &&
                                        (int)$task['assigned_to'] === (int)$currentUserId
                                    )
                                );

                                ?>


                                <tr class="task-row"
                                    data-status="<?= h($task['status']) ?>"
                                    data-search="<?= h(strtolower(
                                        $task['task_title'] . ' ' .
                                        $task['task_description'] . ' ' .
                                        $task['family_name'] . ' ' .
                                        $task['volunteer']
                                    )) ?>">


                                    <!-- TASK -->

                                    <td>

                                        <span class="task-name">

                                            <?= h($task['task_title']) ?>

                                        </span>

                                        <div class="task-description">

                                            <?= h($task['task_description']) ?>

                                        </div>

                                        <span style="display:block;font-size:9px;color:#a0aab6;margin-top:5px;">

                                            ID Tugas #<?= h($task['id']) ?>

                                        </span>

                                    </td>


                                    <!-- FAMILY -->

                                    <td>

                                        <span class="family-name">

                                            <?= h($task['family_name']) ?>

                                        </span>

                                        <span class="family-area">

                                            <?= h($task['area']) ?>

                                        </span>

                                    </td>


                                    <!-- VOLUNTEER -->

                                    <td>

                                        <span class="volunteer-avatar">

                                            <?= h(strtoupper(substr($task['volunteer'], 0, 1))) ?>

                                        </span>

                                        <?= h($task['volunteer']) ?>

                                    </td>


                                    <!-- DATE -->

                                    <td>

                                        <?= h($task['created_at']) ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span class="task-badge <?= h($statusClasses[$task['status']]) ?>">

                                            <?= h($statusLabels[$task['status']]) ?>

                                        </span>

                                    </td>


                                    <!-- UPDATE STATUS -->

                                    <td>


                                        <?php if ($canUpdateTask): ?>


                                            <!--
                                            KONTRAK BACKEND:
                                            ACTION: actions/task_status.php
                                            METHOD: POST

                                            INPUT:
                                            task_id
                                            status
                                            -->


                                            <form class="update-form"
                                                  action="actions/task_status.php"
                                                  method="POST">


                                                <input type="hidden"
                                                       name="task_id"
                                                       value="<?= h($task['id']) ?>">


                                                <select name="status"
                                                        class="form-select"
                                                        required>

                                                    <option value="pending"
                                                        <?= $task['status'] === 'pending' ? 'selected' : '' ?>>

                                                        Pending

                                                    </option>


                                                    <option value="in_progress"
                                                        <?= $task['status'] === 'in_progress' ? 'selected' : '' ?>>

                                                        In Progress

                                                    </option>


                                                    <option value="completed"
                                                        <?= $task['status'] === 'completed' ? 'selected' : '' ?>>

                                                        Completed

                                                    </option>


                                                </select>


                                                <button type="submit">

                                                    Perbarui Status

                                                </button>


                                            </form>


                                        <?php else: ?>


                                            <span style="font-size:9px;color:#9aa5b1;">
                                                Tidak memiliki akses
                                            </span>


                                        <?php endif; ?>


                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>


                    </table>


                    <!-- EMPTY STATE -->

                    <div class="empty-state <?= empty($visibleTasks) ? '' : 'd-none' ?>"
                         id="emptyState">

                        <strong>
                            Tugas tidak ditemukan
                        </strong>

                        Coba gunakan kata kunci atau filter lainnya.

                    </div>


                </div>


                <!-- TABLE FOOTER -->

                <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">

                    <span style="font-size:9px;color:#8b99a9;">

                        Menampilkan
                        <strong id="visibleCount">
                            <?= count($visibleTasks) ?>
                        </strong>
                        tugas.

                    </span>

                    <span style="font-size:9px;color:#8b99a9;">

                        SIAGAKAMPUNG Community Task Management

                    </span>

                </div>


            </div>

        </section>


        <!-- ======================================
             GAP TO ACTION
        ====================================== -->

        <section class="section">


            <div class="info-panel">


                <span style="font-size:9px;letter-spacing:1.5px;color:#8ed8d1;font-weight:800;">

                    CORE INNOVATION

                </span>


                <h3 class="mt-2 mb-2">

                    Gap-to-Action System

                </h3>


                <p>

                    Setiap kekurangan kesiapsiagaan keluarga
                    dapat ditindaklanjuti melalui tugas
                    pendampingan yang terstruktur dan terukur.

                </p>


                <div class="row g-3 mt-2">


                    <div class="col-md-4">

                        <div class="info-step">

                            <strong>
                                01. Identify
                            </strong>

                            <span>
                                Identifikasi kekurangan kesiapsiagaan keluarga.
                            </span>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="info-step">

                            <strong>
                                02. Assign
                            </strong>

                            <span>
                                Koordinator membuat tugas dan menetapkan relawan.
                            </span>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="info-step">

                            <strong>
                                03. Follow Up
                            </strong>

                            <span>
                                Relawan melakukan pendampingan dan memperbarui status.
                            </span>

                        </div>

                    </div>


                </div>


            </div>


        </section>


        <!-- ======================================
             INFORMATION
        ====================================== -->

        <section class="section">


            <div class="p-3 rounded-3"
                 style="background:#fff8e8;border:1px solid #f0e3bf;">

                <div class="d-flex gap-2 align-items-start">

                    <span style="color:#bd821b;font-size:17px;">
                        &#9432;
                    </span>

                    <div>

                        <strong style="display:block;color:#946819;font-size:11px;margin-bottom:5px;">

                            Informasi Data Database

                        </strong>

                        <p style="color:#8c754c;font-size:10px;line-height:1.7;margin:0;">

                            Data keluarga, akun relawan, daftar tugas, dan statistik
                            pada halaman ini diambil langsung dari database MySQL.
                            Relawan hanya melihat tugas yang ditugaskan kepada akunnya,
                            sedangkan Admin dan Koordinator dapat memantau seluruh tugas.
                            Setiap perubahan status tetap diperiksa kembali oleh server.

                        </p>

                    </div>

                </div>

            </div>


        </section>


        <!-- FOOTER -->

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
     JAVASCRIPT
========================================== -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<script>

/*
|--------------------------------------------------------------------------
| TASK FILTER
|--------------------------------------------------------------------------
*/

const filterButtons = document.querySelectorAll('[data-filter]');

const taskRows = document.querySelectorAll('.task-row');

const searchInput = document.getElementById('taskSearch');

const emptyState = document.getElementById('emptyState');

const visibleCount = document.getElementById('visibleCount');

let currentFilter = 'all';


function filterTasks() {

    const keyword = searchInput.value.toLowerCase().trim();

    let visible = 0;

    taskRows.forEach(row => {

        const status = row.dataset.status;

        const searchText = row.dataset.search;

        const matchesStatus =
            currentFilter === 'all' ||
            status === currentFilter;

        const matchesSearch =
            searchText.includes(keyword);

        const shouldShow =
            matchesStatus && matchesSearch;

        row.style.display = shouldShow ? '' : 'none';

        if (shouldShow) {
            visible++;
        }

    });


    visibleCount.textContent = visible;

    emptyState.classList.toggle(
        'd-none',
        visible !== 0
    );

}


filterButtons.forEach(button => {

    button.addEventListener('click', function() {

        filterButtons.forEach(item => {
            item.classList.remove('active');
        });

        this.classList.add('active');

        currentFilter = this.dataset.filter;

        filterTasks();

    });

});


searchInput.addEventListener('input', filterTasks);


/*
|--------------------------------------------------------------------------
| TASK FORM VALIDATION
|--------------------------------------------------------------------------
*/

const taskForm = document.getElementById('taskForm');

if (taskForm) {

    taskForm.addEventListener('submit', function(event) {

        const title = this.querySelector('[name="task_title"]').value.trim();

        const family = this.querySelector('[name="family_id"]').value;

        const volunteer = this.querySelector('[name="assigned_to"]').value;

        if (!title || !family || !volunteer) {

            event.preventDefault();

            alert('Mohon lengkapi keluarga, relawan, dan judul tugas.');

            return;

        }

    });

}


/*
|--------------------------------------------------------------------------
| STATUS UPDATE CONFIRMATION
|--------------------------------------------------------------------------
*/

document.querySelectorAll('.update-form').forEach(form => {

    form.addEventListener('submit', function(event) {

        const status = this.querySelector('[name="status"]').value;

        const labels = {
            pending: 'Pending',
            in_progress: 'In Progress',
            completed: 'Completed'
        };

        const confirmed = confirm(
            'Apakah Anda yakin ingin mengubah status tugas menjadi ' +
            labels[status] + '?'
        );

        if (!confirmed) {
            event.preventDefault();
        }

    });

});

</script>


</body>
</html>
