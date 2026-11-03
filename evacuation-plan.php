<?php

declare(strict_types=1);

/**
 * =====================================================
 * SIAGAKAMPUNG
 * EVACUATION PLAN PAGE
 * Database-integrated version
 * =====================================================
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/config.php';

$currentUser = siaga_current_user();
$currentRole = $currentUser['role'] ?? null;
$isLoggedIn = $currentUser !== null;
$canManage = in_array($currentRole, ['admin', 'coordinator'], true);
$isAdmin = $currentRole === 'admin';

// Create a session-bound CSRF token for all authorized management forms.
// Admin uses it for verification; Admin and Coordinator use it for draft creation.
if ($canManage) {
    if (
        !isset($_SESSION['csrf_token']) ||
        !is_string($_SESSION['csrf_token']) ||
        !preg_match('/^[a-f0-9]{64}$/', $_SESSION['csrf_token'])
    ) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

$csrfToken = ($canManage && isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token']))
    ? $_SESSION['csrf_token']
    : '';

$verifiedPlans = [];
$draftPlans = [];
$pageLoadError = null;

$escape = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$formatDate = static function ($value): string {
    if (empty($value)) {
        return '-';
    }

    $timestamp = strtotime((string) $value);
    return $timestamp ? date('d M Y, H:i', $timestamp) : '-';
};

try {
    // Public users can only retrieve plans that have been verified.
    $verifiedStmt = $conn->prepare(
        "SELECT
            ep.id,
            ep.neighborhood,
            ep.hazard_type,
            ep.primary_route,
            ep.alternative_route,
            ep.assembly_point,
            ep.coordinator_contact,
            ep.verified_at,
            ep.created_at,
            u.name AS creator_name
         FROM evacuation_plans ep
         LEFT JOIN users u ON u.id = ep.created_by
         WHERE ep.validation_status = 'verified'
         ORDER BY ep.verified_at DESC, ep.created_at DESC"
    );

    $verifiedStmt->execute();
    $verifiedResult = $verifiedStmt->get_result();

    while ($row = $verifiedResult->fetch_assoc()) {
        $verifiedPlans[] = $row;
    }

    $verifiedStmt->close();

    // Draft records are restricted to administrators and coordinators.
    if ($canManage) {
        $draftStmt = $conn->prepare(
            "SELECT
                ep.id,
                ep.neighborhood,
                ep.hazard_type,
                ep.primary_route,
                ep.alternative_route,
                ep.assembly_point,
                ep.coordinator_contact,
                ep.created_at,
                u.name AS creator_name
             FROM evacuation_plans ep
             LEFT JOIN users u ON u.id = ep.created_by
             WHERE ep.validation_status = 'draft'
             ORDER BY ep.created_at DESC"
        );

        $draftStmt->execute();
        $draftResult = $draftStmt->get_result();

        while ($row = $draftResult->fetch_assoc()) {
            $draftPlans[] = $row;
        }

        $draftStmt->close();
    }
} catch (Throwable $e) {
    error_log('[SIAGAKAMPUNG EVACUATION PAGE ERROR] ' . $e->getMessage());
    $pageLoadError = 'Data rencana evakuasi belum dapat dimuat. Periksa koneksi database dan coba kembali.';
}

$flashSuccess = $_SESSION['evacuation_success'] ?? '';
$flashError = $_SESSION['evacuation_error'] ?? '';
unset($_SESSION['evacuation_success'], $_SESSION['evacuation_error']);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rencana Evakuasi | SIAGAKAMPUNG</title>

    <meta name="description" content="Informasi dan pengelolaan rencana evakuasi komunitas SIAGAKAMPUNG.">

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
            --background: #f4f7fb;
            --muted: #718096;
            --border: #e5eaf0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: var(--background);
            color: #26384b;
            font-family: Inter, "Segoe UI", Arial, sans-serif;
        }

        /* =========================
           NAVBAR
        ========================= */

        .main-navbar {
            background: #fff;
            border-bottom: 1px solid var(--border);
        }

        .brand-logo {
            width: 43px;
            height: 43px;
            border-radius: 12px;
            background: var(--primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-name {
            color: var(--primary);
            font-size: 17px;
            font-weight: 800;
        }

        .brand-subtitle {
            display: block;
            color: #8a97a8;
            font-size: 9px;
            letter-spacing: 1.2px;
        }

        /* =========================
           HERO
        ========================= */

        .page-hero {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(
                    circle at 85% 15%,
                    rgba(51,190,187,.2),
                    transparent 32%
                ),
                linear-gradient(
                    120deg,
                    #102d49,
                    #174a78 65%,
                    #087f8c
                );

            padding: 48px 0 90px;
            color: white;
        }

        .page-hero::after {
            content: "";
            position: absolute;
            width: 320px;
            height: 320px;
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 50%;
            right: -100px;
            bottom: -190px;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-block;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.17);
            padding: 9px 15px;
            border-radius: 50px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .page-hero h1 {
            font-size: clamp(30px, 4vw, 43px);
            font-weight: 800;
            margin-top: 19px;
            color: white;
        }

        .page-hero p {
            max-width: 650px;
            color: rgba(255,255,255,.75);
            font-size: 14px;
            line-height: 1.8;
        }

        /* =========================
           MAIN CONTENT
        ========================= */

        .main-content {
            position: relative;
            margin-top: -45px;
            padding-bottom: 65px;
        }

        .content-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 17px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(16,45,73,.045);
        }

        .section-title {
            color: var(--primary-dark);
            font-size: 19px;
            font-weight: 800;
        }

        .section-description {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.8;
        }

        /* =========================
           SAFETY NOTICE
        ========================= */

        .safety-notice {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            background: #fff8e7;
            border: 1px solid #f3e4bc;
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 25px;
        }

        .notice-icon {
            width: 39px;
            height: 39px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff0c9;
            color: #a16b11;
            border-radius: 11px;
        }

        .safety-notice h6 {
            font-size: 13px;
            color: #8d641a;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .safety-notice p {
            color: #8c754c;
            font-size: 11px;
            line-height: 1.7;
            margin: 0;
        }

        /* =========================
           THREAT CARDS
        ========================= */

        .threat-card {
            height: 100%;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 20px;
            transition: .2s;
        }

        .threat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 9px 22px rgba(16,45,73,.07);
        }

        .threat-icon {
            width: 45px;
            height: 45px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #edf4fa;
            color: var(--primary);
            border-radius: 12px;
            margin-bottom: 14px;
        }

        .threat-card h6 {
            font-size: 13px;
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 6px;
        }

        .threat-card p {
            font-size: 11px;
            line-height: 1.7;
            color: var(--muted);
            margin: 0;
        }

        /* =========================
           PLAN CARD
        ========================= */

        .plan-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(16,45,73,.045);
        }

        .plan-card-header {
            padding: 23px 25px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
        }

        .plan-card-body {
            padding: 25px;
        }

        .plan-code {
            color: var(--muted);
            font-size: 10px;
            letter-spacing: 1px;
            font-weight: 700;
        }

        .plan-name {
            color: var(--primary-dark);
            font-size: 20px;
            font-weight: 800;
            margin-top: 7px;
            margin-bottom: 5px;
        }

        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #e5f6ee;
            color: #12825c;
            border-radius: 30px;
            padding: 7px 12px;
            font-size: 10px;
            font-weight: 800;
        }

        .draft-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff2d7;
            color: #98630b;
            border-radius: 30px;
            padding: 7px 12px;
            font-size: 10px;
            font-weight: 800;
        }

        .simulation-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #eaf2fa;
            color: var(--primary);
            border-radius: 30px;
            padding: 7px 12px;
            font-size: 10px;
            font-weight: 800;
        }

        /* =========================
           ROUTE INFORMATION
        ========================= */

        .route-section {
            border: 1px solid var(--border);
            border-radius: 13px;
            padding: 18px;
            margin-bottom: 15px;
        }

        .route-heading {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .route-icon {
            width: 39px;
            height: 39px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .route-primary-icon {
            background: #e7f4ef;
            color: #15966b;
        }

        .route-alternative-icon {
            background: #edf4fa;
            color: var(--primary);
        }

        .route-heading h6 {
            color: var(--primary-dark);
            font-size: 13px;
            font-weight: 800;
            margin: 0 0 3px;
        }

        .route-heading small {
            color: var(--muted);
            font-size: 10px;
        }

        .route-description {
            color: #526579;
            font-size: 12px;
            line-height: 1.8;
            margin: 0;
        }

        /* =========================
           ASSEMBLY POINT
        ========================= */

        .assembly-card {
            background: linear-gradient(
                110deg,
                #edf6fa,
                #e9f5f2
            );
            border: 1px solid #d9eae9;
            border-radius: 14px;
            padding: 19px;
        }

        .assembly-card h6 {
            color: var(--primary-dark);
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 7px;
        }

        .assembly-card p {
            color: #526579;
            font-size: 12px;
            line-height: 1.7;
            margin-bottom: 0;
        }

        /* =========================
           CONTACT
        ========================= */

        .contact-card {
            display: flex;
            align-items: center;
            gap: 13px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 13px;
            padding: 16px;
        }

        .contact-icon {
            width: 40px;
            height: 40px;
            border-radius: 11px;
            background: #eaf2fa;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .contact-card small {
            display: block;
            color: var(--muted);
            font-size: 10px;
            margin-bottom: 4px;
        }

        .contact-card strong {
            color: var(--primary-dark);
            font-size: 12px;
        }

        /* =========================
           VALIDATION INFORMATION
        ========================= */

        .validation-box {
            background: #eaf6f0;
            border: 1px solid #d4eadf;
            border-radius: 13px;
            padding: 16px;
        }

        .validation-box h6 {
            color: #12825c;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 7px;
        }

        .validation-box p {
            color: #547966;
            font-size: 10px;
            line-height: 1.7;
            margin: 0;
        }

        /* =========================
           EMPTY STATE
        ========================= */

        .empty-state {
            text-align: center;
            background: #fff;
            border: 1px dashed #ccd6e1;
            border-radius: 16px;
            padding: 35px 20px;
        }

        .empty-state-icon {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #edf4fa;
            color: var(--primary);
            margin: 0 auto 15px;
        }

        .empty-state h6 {
            color: var(--primary-dark);
            font-size: 14px;
            font-weight: 800;
        }

        .empty-state p {
            color: var(--muted);
            font-size: 11px;
            line-height: 1.7;
            max-width: 390px;
            margin: 0 auto;
        }

        /* =========================
           FORM
        ========================= */

        .form-section {
            border: 1px solid var(--border);
            border-radius: 17px;
            padding: 26px;
            background: #fff;
        }

        .form-section-heading {
            color: var(--primary-dark);
            font-size: 18px;
            font-weight: 800;
        }

        .form-section-description {
            color: var(--muted);
            font-size: 11px;
            line-height: 1.7;
        }

        .section-number {
            width: 39px;
            height: 39px;
            flex-shrink: 0;
            border-radius: 11px;
            background: #eaf2fa;
            color: var(--primary);
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 800;
        }

        .form-label {
            color: #34445a;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 9px;
        }

        .required-star {
            color: #dc3545;
        }

        .form-control,
        .form-select {
            min-height: 49px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 13px;
            padding: 11px 14px;
            box-shadow: none;
        }

        textarea.form-control {
            min-height: 105px;
            resize: vertical;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 4px rgba(8,127,140,.09);
        }

        .form-help {
            font-size: 10px;
            color: #8996a5;
            line-height: 1.6;
            margin-top: 6px;
        }

        .submit-button {
            min-height: 52px;
            border: none;
            border-radius: 11px;
            background: linear-gradient(
                110deg,
                var(--primary),
                var(--secondary)
            );
            color: #fff;
            padding: 12px 22px;
            font-size: 12px;
            font-weight: 800;
            transition: .2s;
        }

        .submit-button:hover {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(23,74,120,.18);
        }

        /* =========================
           SIDEBAR
        ========================= */

        .side-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 21px;
            margin-bottom: 18px;
        }

        .side-title {
            color: var(--primary-dark);
            font-size: 14px;
            font-weight: 800;
            margin-bottom: 14px;
        }

        .side-text {
            color: var(--muted);
            font-size: 11px;
            line-height: 1.8;
        }

        .side-step {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            margin-bottom: 16px;
        }

        .side-step:last-child {
            margin-bottom: 0;
        }

        .side-step-number {
            width: 27px;
            height: 27px;
            flex-shrink: 0;
            background: #eaf2fa;
            color: var(--primary);
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 10px;
            font-weight: 800;
        }

        .side-step strong {
            display: block;
            color: #34445a;
            font-size: 11px;
            margin-bottom: 3px;
        }

        .side-step p {
            color: var(--muted);
            font-size: 10px;
            line-height: 1.6;
            margin: 0;
        }

        /* =========================
           FOOTER
        ========================= */

        .page-footer {
            background: #102d49;
            color: rgba(255,255,255,.65);
            padding: 28px 0;
            font-size: 11px;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 767px) {

            .page-hero {
                padding: 36px 0 75px;
            }

            .content-card,
            .plan-card-body,
            .form-section {
                padding: 19px;
            }

            .plan-card-header {
                padding: 19px;
            }

            .section-title {
                font-size: 17px;
            }

        }

    </style>

</head>

<body>


<!-- =================================
     NAVBAR
================================= -->

<nav class="navbar navbar-expand-lg main-navbar sticky-top">

    <div class="container py-2">

        <a href="index.php"
           class="navbar-brand d-flex align-items-center gap-2">

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


        <button class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNavbar"
                aria-label="Buka navigasi">

            <span class="navbar-toggler-icon"></span>

        </button>


        <div class="collapse navbar-collapse" id="mainNavbar">

            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">

                <li class="nav-item">
                    <a href="index.php" class="nav-link">
                        Beranda
                    </a>
                </li>

                <li class="nav-item">
                    <a href="family-form.php" class="nav-link">
                        Kartu Siaga
                    </a>
                </li>

                <li class="nav-item">
                    <a href="evacuation-plan.php"
                       class="nav-link active fw-semibold text-primary">
                        Rencana Evakuasi
                    </a>
                </li>

                <?php if ($canManage): ?>
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">Dashboard</a>
                    </li>
                <?php endif; ?>

                <?php if ($isLoggedIn): ?>
                    <?php if ($currentRole === 'volunteer'): ?>
                        <li class="nav-item">
                            <a href="community-tasks.php" class="nav-link">Tugas Saya</a>
                        </li>
                    <?php elseif ($canManage): ?>
                        <li class="nav-item">
                            <a href="community-tasks.php" class="nav-link">Tugas Relawan</a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <span class="nav-link small text-secondary">
                            <?= $escape($currentUser['name'] ?? 'Pengguna') ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <form action="actions/logout.php" method="POST" class="d-inline">
                            <button type="submit"
                                    class="btn btn-outline-danger rounded-pill px-4 mt-2 mt-lg-0">
                                Keluar
                            </button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="login.php"
                           class="btn btn-outline-primary rounded-pill px-4 mt-2 mt-lg-0">
                            Masuk
                        </a>
                    </li>
                <?php endif; ?>

            </ul>

        </div>

    </div>

</nav>


<!-- =================================
     HERO
================================= -->

<section class="page-hero">

    <div class="container hero-content">

        <div class="hero-badge">
            COMMUNITY EVACUATION PLANNING
        </div>

        <h1>
            Rencana Evakuasi<br>
            Komunitas
        </h1>

        <p class="mt-3 mb-0">

            Akses informasi rencana evakuasi lingkungan,
            kenali jalur utama dan alternatif, serta ketahui
            titik kumpul dan kontak koordinator komunitas.

        </p>

    </div>

</section>


<!-- =================================
     MAIN CONTENT
================================= -->

<main class="container main-content">


    <!-- SAFETY NOTICE -->

    <div class="safety-notice">

        <div class="notice-icon">

            <svg xmlns="http://www.w3.org/2000/svg"
                 width="22"
                 height="22"
                 fill="currentColor"
                 viewBox="0 0 16 16">

                <path d="M8 1.5 1 14h14L8 1.5zm0 3.1 3.8 7H4.2L8 4.6zM7.25 7v2.5h1.5V7h-1.5zm0 3.2v1h1.5v-1h-1.5z"/>

            </svg>

        </div>

        <div>

            <h6>
                Perhatikan Status Validasi Rencana Evakuasi
            </h6>

            <p>

                Hanya rencana dengan status VERIFIED yang dapat
                ditampilkan sebagai informasi rencana evakuasi
                untuk warga. Rencana DRAFT belum merupakan
                panduan evakuasi yang dapat digunakan.

            </p>

        </div>

    </div>


    <?php if ($pageLoadError !== null): ?>
        <div class="alert alert-danger rounded-3" role="alert">
            <?= $escape($pageLoadError) ?>
        </div>
    <?php endif; ?>

    <?php if ($flashSuccess !== ''): ?>
        <div class="alert alert-success rounded-3" role="status">
            <?= $escape($flashSuccess) ?>
        </div>
    <?php endif; ?>

    <?php if ($flashError !== ''): ?>
        <div class="alert alert-danger rounded-3" role="alert">
            <?= $escape($flashError) ?>
        </div>
    <?php endif; ?>

    <!-- =================================
         THREAT OVERVIEW
    ================================= -->

    <section class="mb-5">

        <div class="mb-4">

            <h2 class="section-title mb-1">
                Informasi Jenis Ancaman
            </h2>

            <p class="section-description mb-0">

                Rencana evakuasi disusun dengan mempertimbangkan
                potensi ancaman yang terdapat di wilayah komunitas.

            </p>

        </div>


        <div class="row g-3">


            <!-- FLOOD -->

            <div class="col-6 col-md-3">

                <div class="threat-card">

                    <div class="threat-icon">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="24"
                             height="24"
                             fill="currentColor"
                             viewBox="0 0 16 16">

                            <path d="M7.5 1.5a.5.5 0 0 1 1 0v7.1a2.5 2.5 0 1 1-1 0V1.5z"/>

                            <path d="M3 12c.5-.7 1.2-.7 1.7 0 .5.7 1.2.7 1.7 0 .5-.7 1.2-.7 1.7 0 .5.7 1.2.7 1.7 0 .5-.7 1.2-.7 1.7 0 .5.7 1.2.7 1.7 0"/>

                        </svg>

                    </div>

                    <h6>Banjir</h6>

                    <p>
                        Ancaman genangan dan kenaikan muka air.
                    </p>

                </div>

            </div>


            <!-- EARTHQUAKE -->

            <div class="col-6 col-md-3">

                <div class="threat-card">

                    <div class="threat-icon">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="24"
                             height="24"
                             fill="currentColor"
                             viewBox="0 0 16 16">

                            <path d="m8 1 1.5 4H13l-3 2.5 1 4L8 9.5 5 11.5l1-4L3 5h3.5L8 1z"/>

                            <path d="M1 13h14v1H1z"/>

                        </svg>

                    </div>

                    <h6>Gempa Bumi</h6>

                    <p>
                        Ancaman guncangan dan kerusakan bangunan.
                    </p>

                </div>

            </div>


            <!-- FIRE -->

            <div class="col-6 col-md-3">

                <div class="threat-card">

                    <div class="threat-icon">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="24"
                             height="24"
                             fill="currentColor"
                             viewBox="0 0 16 16">

                            <path d="M8 0c.5 3-2 4.5-1 7 1-1 2-2 2-4 3 3 4 5.2 3 8-1 2.5-3.1 4-5.5 4C3.5 15 1 12.5 2 9c.6-2.2 2.5-3.5 3-6 .7 2 1.5 2.5 1.5 2.5C7.5 4 8 2 8 0z"/>

                        </svg>

                    </div>

                    <h6>Kebakaran</h6>

                    <p>
                        Ancaman api dan penyebaran asap.
                    </p>

                </div>

            </div>


            <!-- LANDSLIDE -->

            <div class="col-6 col-md-3">

                <div class="threat-card">

                    <div class="threat-icon">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="24"
                             height="24"
                             fill="currentColor"
                             viewBox="0 0 16 16">

                            <path d="M1 13 5 5l2 3 2-5 6 10H1z"/>

                            <path d="M1 14h14v1H1z"/>

                        </svg>

                    </div>

                    <h6>Tanah Longsor</h6>

                    <p>
                        Ancaman pergerakan tanah dan material lereng.
                    </p>

                </div>

            </div>

        </div>

        <p class="text-secondary mt-3 mb-0"
           style="font-size:10px;">

            Ilustrasi jenis ancaman. Jenis ancaman pada setiap
            rencana mengikuti data wilayah yang dimasukkan koordinator.

        </p>

    </section>


    <!-- =================================
         VERIFIED EVACUATION PLANS
    ================================= -->

    <section class="mb-5">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">

            <div>

                <span class="text-success fw-bold text-uppercase"
                      style="font-size:10px;letter-spacing:1px;">

                    PUBLIC EVACUATION INFORMATION

                </span>

                <h2 class="section-title mt-2 mb-1">
                    Rencana Evakuasi Tervalidasi
                </h2>

                <p class="section-description mb-0">

                    Informasi rencana evakuasi yang telah
                    melewati proses validasi berwenang.

                </p>

            </div>

            <span class="verified-badge">

                <span>●</span>
                VERIFIED ONLY

            </span>

        </div>


        <?php if (!empty($verifiedPlans) && $pageLoadError === null): ?>
            <?php foreach ($verifiedPlans as $plan): ?>
                <article class="plan-card mb-4">
                    <div class="plan-card-header">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                            <div>
                                <div class="plan-code">
                                    RENCANA #<?= (int) $plan['id'] ?>
                                </div>
                                <h3 class="plan-name">
                                    Rencana Evakuasi Wilayah <?= $escape($plan['neighborhood']) ?>
                                </h3>
                                <p class="section-description mb-0">
                                    Informasi rencana yang telah berstatus verified.
                                </p>
                            </div>
                            <div class="d-flex flex-wrap align-items-start gap-2">
                                <span class="verified-badge">✓ VERIFIED</span>
                            </div>
                        </div>
                    </div>

                    <div class="plan-card-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center p-3 rounded-3 bg-light h-100">
                                    <div>
                                        <small class="text-secondary d-block mb-1" style="font-size:10px;">JENIS ANCAMAN</small>
                                        <strong style="font-size:13px;"><?= $escape($plan['hazard_type']) ?></strong>
                                    </div>
                                    <span class="text-primary fs-4">&#9888;</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center p-3 rounded-3 bg-light h-100">
                                    <div>
                                        <small class="text-secondary d-block mb-1" style="font-size:10px;">WILAYAH</small>
                                        <strong style="font-size:13px;"><?= $escape($plan['neighborhood']) ?></strong>
                                    </div>
                                    <span class="text-primary fs-4">&#128205;</span>
                                </div>
                            </div>
                        </div>

                        <div class="route-section">
                            <div class="route-heading">
                                <div class="route-icon route-primary-icon">&#10148;</div>
                                <div>
                                    <h6>Jalur Evakuasi Utama</h6>
                                    <small>PRIMARY EVACUATION ROUTE</small>
                                </div>
                            </div>
                            <p class="route-description"><?= nl2br($escape($plan['primary_route'])) ?></p>
                        </div>

                        <div class="route-section">
                            <div class="route-heading">
                                <div class="route-icon route-alternative-icon">&#8644;</div>
                                <div>
                                    <h6>Jalur Evakuasi Alternatif</h6>
                                    <small>ALTERNATIVE EVACUATION ROUTE</small>
                                </div>
                            </div>
                            <p class="route-description"><?= nl2br($escape($plan['alternative_route'])) ?></p>
                        </div>

                        <div class="assembly-card mb-4">
                            <div class="d-flex align-items-start gap-3">
                                <div class="route-icon route-primary-icon">&#128205;</div>
                                <div>
                                    <h6>Titik Kumpul Komunitas</h6>
                                    <p><?= $escape($plan['assembly_point']) ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="contact-card mb-4">
                            <div class="contact-icon">&#9742;</div>
                            <div>
                                <small>KONTAK KOORDINATOR</small>
                                <strong><?= $escape($plan['coordinator_contact']) ?></strong>
                            </div>
                        </div>

                        <div class="validation-box">
                            <h6>✓ Status Validasi: VERIFIED</h6>
                            <p>
                                Diverifikasi pada <?= $escape($formatDate($plan['verified_at'])) ?>.
                                Rencana ini ditampilkan berdasarkan status validasi yang tersimpan di sistem.
                            </p>
                        </div>

                        <div class="text-secondary mt-3" style="font-size:10px;">
                            Disusun oleh <?= $escape($plan['creator_name'] ?: 'Pengelola SIAGAKAMPUNG') ?>.
                            Tanggal pencatatan: <?= $escape($formatDate($plan['created_at'])) ?>.
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php elseif ($pageLoadError === null): ?>
            <div class="empty-state">
                <div class="empty-state-icon">&#128203;</div>
                <h6>Belum Ada Rencana Evakuasi Terverifikasi</h6>
                <p>
                    Informasi rencana evakuasi akan muncul di sini setelah rencana
                    diperiksa dan ditetapkan berstatus VERIFIED oleh pihak berwenang.
                    Rencana draft tidak ditampilkan sebagai panduan publik.
                </p>
            </div>
        <?php endif; ?>

    </section>


    <?php if ($canManage): ?>
        <!-- =================================
             DRAFT PLAN INFORMATION
        ================================= -->
        <section class="mb-5">
            <div class="mb-4">
                <span class="text-warning fw-bold text-uppercase" style="font-size:10px;letter-spacing:1px;">
                    COORDINATOR MANAGEMENT
                </span>
                <h2 class="section-title mt-2 mb-1">Status Rencana Draft</h2>
                <p class="section-description mb-0">
                    Daftar rencana yang masih menunggu pemeriksaan. Bagian ini hanya dapat dilihat Admin dan Coordinator.
                    Hanya Admin yang dapat mengubah status draft menjadi verified.
                </p>
            </div>

            <?php if (!empty($draftPlans) && $pageLoadError === null): ?>
                <?php foreach ($draftPlans as $plan): ?>
                    <div class="content-card mb-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                            <div>
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <span class="draft-badge">● DRAFT</span>
                                    <span class="simulation-badge">MENUNGGU VALIDASI</span>
                                </div>
                                <div class="plan-code">PENGAJUAN #<?= (int) $plan['id'] ?></div>
                                <h5 class="section-title mt-2 mb-2">
                                    Rencana Evakuasi Wilayah <?= $escape($plan['neighborhood']) ?>
                                </h5>
                                <p class="section-description mb-1">
                                    Jenis ancaman: <strong><?= $escape($plan['hazard_type']) ?></strong>
                                </p>
                                <p class="section-description mb-0">
                                    Dibuat oleh <?= $escape($plan['creator_name'] ?: 'Pengguna') ?>
                                    pada <?= $escape($formatDate($plan['created_at'])) ?>.
                                </p>
                            </div>
                            <div class="text-md-end">
                                <span class="draft-badge">Belum diverifikasi</span>
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <small class="text-secondary d-block mb-1">JALUR UTAMA</small>
                                <p class="section-description mb-0"><?= nl2br($escape($plan['primary_route'])) ?></p>
                            </div>
                            <div class="col-md-6">
                                <small class="text-secondary d-block mb-1">JALUR ALTERNATIF</small>
                                <p class="section-description mb-0"><?= nl2br($escape($plan['alternative_route'])) ?></p>
                            </div>
                            <div class="col-md-6">
                                <small class="text-secondary d-block mb-1">TITIK KUMPUL</small>
                                <p class="section-description mb-0"><?= $escape($plan['assembly_point']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <small class="text-secondary d-block mb-1">KONTAK KOORDINATOR</small>
                                <p class="section-description mb-0"><?= $escape($plan['coordinator_contact']) ?></p>
                            </div>
                        </div>

                        <?php if ($isAdmin): ?>
                            <div class="safety-notice mt-4 mb-3">
                                <div class="notice-icon">&#9888;</div>
                                <div>
                                    <h6>Menunggu keputusan validasi Admin</h6>
                                    <p class="mb-0">
                                        Periksa kembali jalur utama, jalur alternatif, titik kumpul,
                                        dan kontak koordinator. Verifikasi di aplikasi hanya boleh
                                        dilakukan setelah informasi diperiksa dan disahkan oleh pihak
                                        berwenang kebencanaan.
                                    </p>
                                </div>
                            </div>

                            <form action="actions/evacuation_save.php"
                                  method="POST"
                                  class="d-flex justify-content-end"
                                  onsubmit="return confirm('Pastikan rencana ini telah diperiksa dan disahkan oleh pihak berwenang kebencanaan. Verifikasi akan membuat rencana dapat dilihat oleh warga. Lanjutkan?');">
                                <input type="hidden" name="operation" value="verify">
                                <input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                <button type="submit"
                                        class="btn btn-success rounded-pill px-4 py-2 fw-bold">
                                    <span class="me-2">&#10003;</span>
                                    Verifikasi dan Publikasikan
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="safety-notice mt-4 mb-0">
                                <div class="notice-icon">&#9888;</div>
                                <div>
                                    <h6>Menunggu validasi</h6>
                                    <p>Rencana ini masih berstatus DRAFT dan belum dapat ditampilkan sebagai panduan publik.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php elseif ($pageLoadError === null): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">&#128203;</div>
                    <h6>Belum Ada Rencana Draft</h6>
                    <p>Rencana yang disimpan oleh koordinator akan muncul di bagian ini sampai proses validasi dilakukan.</p>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>


    <!-- =================================
         COORDINATOR FORM
    ================================= -->

    <?php if ($canManage): ?>
    <section class="mb-5" id="coordinator-form">

        <div class="mb-4">

            <span class="text-primary fw-bold text-uppercase"
                  style="font-size:10px;letter-spacing:1px;">

                COORDINATOR MANAGEMENT

            </span>

            <h2 class="section-title mt-2 mb-1">
                Kelola Rencana Evakuasi
            </h2>

            <p class="section-description mb-0">

                Formulir untuk koordinator dalam menyusun
                informasi rencana evakuasi komunitas.

            </p>

        </div>


        <div class="form-section">


            <div class="d-flex align-items-start gap-3 mb-4">

                <div class="section-number">
                    01
                </div>

                <div>

                    <h3 class="form-section-heading mb-1">
                        Formulir Rencana Evakuasi
                    </h3>

                    <p class="form-section-description mb-0">

                        Masukkan informasi wilayah, ancaman,
                        jalur evakuasi, titik kumpul, dan
                        kontak koordinator.

                    </p>

                </div>

            </div>


            <hr class="border-light mb-4">


            <!-- EVACUATION FORM CONTRACT -->

            <form id="evacuationForm"
                  action="actions/evacuation_save.php"
                  method="POST">

                <input type="hidden" name="operation" value="create">
                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">

                <!-- NEIGHBORHOOD -->

                <div class="mb-4">

                    <label for="neighborhood" class="form-label">

                        Wilayah / RT / RW

                        <span class="required-star">*</span>

                    </label>

                    <input type="text"
                           class="form-control"
                           id="neighborhood"
                           name="neighborhood"
                           placeholder="Contoh: RT 02 / RW 05"
                           maxlength="50"
                           required>

                    <div class="form-help">
                        Masukkan wilayah yang menjadi cakupan rencana evakuasi.
                    </div>

                </div>


                <!-- HAZARD TYPE -->

                <div class="mb-4">

                    <label for="hazard_type" class="form-label">

                        Jenis Ancaman Bencana

                        <span class="required-star">*</span>

                    </label>

                    <select class="form-select"
                            id="hazard_type"
                            name="hazard_type"
                            required>

                        <option value="" selected disabled>
                            Pilih jenis ancaman
                        </option>

                        <option value="Banjir">
                            Banjir
                        </option>

                        <option value="Gempa Bumi">
                            Gempa Bumi
                        </option>

                        <option value="Kebakaran">
                            Kebakaran
                        </option>

                        <option value="Tanah Longsor">
                            Tanah Longsor
                        </option>

                        <option value="Angin Kencang">
                            Angin Kencang
                        </option>

                        <option value="Lainnya">
                            Lainnya
                        </option>

                    </select>

                    <div class="form-help">
                        Pilih ancaman yang menjadi dasar penyusunan rencana.
                    </div>

                </div>


                <!-- PRIMARY ROUTE -->

                <div class="mb-4">

                    <label for="primary_route" class="form-label">

                        Jalur Evakuasi Utama

                        <span class="required-star">*</span>

                    </label>

                    <textarea class="form-control"
                              id="primary_route"
                              name="primary_route"
                              placeholder="Tuliskan deskripsi jalur evakuasi utama secara jelas..."
                              required></textarea>

                    <div class="form-help">
                        Jelaskan rute utama yang direncanakan.
                    </div>

                </div>


                <!-- ALTERNATIVE ROUTE -->

                <div class="mb-4">

                    <label for="alternative_route" class="form-label">

                        Jalur Evakuasi Alternatif

                        <span class="required-star">*</span>

                    </label>

                    <textarea class="form-control"
                              id="alternative_route"
                              name="alternative_route"
                              placeholder="Tuliskan jalur alternatif apabila jalur utama tidak dapat digunakan..."
                              required></textarea>

                    <div class="form-help">
                        Tuliskan alternatif rute yang direncanakan.
                    </div>

                </div>


                <!-- ASSEMBLY POINT -->

                <div class="mb-4">

                    <label for="assembly_point" class="form-label">

                        Titik Kumpul

                        <span class="required-star">*</span>

                    </label>

                    <input type="text"
                           class="form-control"
                           id="assembly_point"
                           name="assembly_point"
                           placeholder="Contoh: Lapangan komunitas wilayah RT 02"
                           maxlength="150"
                           required>

                    <div class="form-help">
                        Masukkan nama atau lokasi titik kumpul yang direncanakan.
                    </div>

                </div>


                <!-- COORDINATOR CONTACT -->

                <div class="mb-4">

                    <label for="coordinator_contact" class="form-label">

                        Kontak Koordinator

                        <span class="required-star">*</span>

                    </label>

                    <input type="tel"
                           class="form-control"
                           id="coordinator_contact"
                           name="coordinator_contact"
                           placeholder="Contoh: 081234567890"
                           maxlength="25"
                           inputmode="tel"
                           required>

                    <div class="form-help">
                        Gunakan nomor kontak koordinator yang aktif.
                    </div>

                </div>


                <!-- DRAFT NOTICE -->

                <div class="safety-notice mb-4">

                    <div class="notice-icon">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="20"
                             height="20"
                             fill="currentColor"
                             viewBox="0 0 16 16">

                            <path d="M8 1.5 1 14h14L8 1.5zm0 3.1 3.8 7H4.2L8 4.6zM7.25 7v2.5h1.5V7h-1.5zm0 3.2v1h1.5v-1h-1.5z"/>

                        </svg>

                    </div>

                    <div>

                        <h6>
                            Ketentuan Status Rencana
                        </h6>

                        <p>

                            Rencana yang disimpan akan memiliki
                            status awal DRAFT. Status VERIFIED
                            hanya dapat ditetapkan melalui proses
                            validasi oleh pihak berwenang.

                        </p>

                    </div>

                </div>


                <!-- SUBMIT -->

                <div class="d-flex flex-column flex-sm-row gap-3">

                    <a href="index.php"
                       class="btn btn-outline-secondary rounded-3 px-4 py-3"
                       style="font-size:12px;font-weight:700;">

                        Kembali

                    </a>

                    <button type="submit"
                            class="submit-button flex-grow-1">

                        Simpan Rencana Evakuasi

                        <span class="ms-2">&rarr;</span>

                    </button>

                </div>


                <p class="text-secondary text-center mt-3 mb-0"
                   style="font-size:10px;">

                    Formulir ini merupakan antarmuka pengelolaan
                    untuk demonstrasi SIAGAKAMPUNG.

                </p>


            </form>

        </div>

    </section>


    <?php endif; ?>

    <!-- =================================
         VALIDATION WORKFLOW
    ================================= -->

    <section class="mb-5">

        <div class="mb-4">

            <h2 class="section-title mb-1">
                Tahapan Validasi Rencana
            </h2>

            <p class="section-description mb-0">

                Rencana evakuasi harus melalui proses validasi
                sebelum dapat ditampilkan kepada warga.

            </p>

        </div>


        <div class="content-card">

            <div class="row g-4">


                <div class="col-md-4">

                    <div class="d-flex gap-3">

                        <div class="section-number">
                            01
                        </div>

                        <div>

                            <h6 class="fw-bold"
                                style="font-size:12px;">

                                Penyusunan Rencana

                            </h6>

                            <p class="side-text mb-0">

                                Koordinator memasukkan informasi
                                ancaman, jalur, titik kumpul,
                                dan kontak.

                            </p>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="d-flex gap-3">

                        <div class="section-number">
                            02
                        </div>

                        <div>

                            <h6 class="fw-bold"
                                style="font-size:12px;">

                                Pemeriksaan dan Validasi

                            </h6>

                            <p class="side-text mb-0">

                                Pihak berwenang melakukan
                                pemeriksaan dan validasi
                                terhadap rencana.

                            </p>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="d-flex gap-3">

                        <div class="section-number">
                            03
                        </div>

                        <div>

                            <h6 class="fw-bold"
                                style="font-size:12px;">

                                Publikasi Rencana

                            </h6>

                            <p class="side-text mb-0">

                                Rencana berstatus VERIFIED
                                dapat ditampilkan sebagai
                                informasi untuk warga.

                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- =================================
         FINAL DISCLAIMER
    ================================= -->

    <div class="validation-box">

        <div class="d-flex gap-3 align-items-start">

            <span class="fs-4">
                &#10003;
            </span>

            <div>

                <h6>
                    Kesiapsiagaan Dimulai dari Informasi yang Benar
                </h6>

                <p>

                    Informasi rencana evakuasi harus disusun
                    berdasarkan kondisi wilayah dan melalui
                    validasi pihak berwenang. Dalam kondisi
                    darurat, ikuti arahan petugas dan informasi
                    resmi kebencanaan.

                </p>

            </div>

        </div>

    </div>


</main>


<!-- =================================
     FOOTER
================================= -->

<footer class="page-footer">

    <div class="container">

        <div class="d-flex flex-column flex-md-row justify-content-between gap-2">

            <div>
                &copy; 2026 SIAGAKAMPUNG. All Rights Reserved.
            </div>

            <div>
                Satu Kampung, Satu Rencana Kesiapsiagaan.
            </div>

        </div>

    </div>

</footer>


<!-- =================================
     BOOTSTRAP JS
================================= -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
