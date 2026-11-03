<?php
/**
 * =====================================================
 * SIAGAKAMPUNG
 * LOGIN PAGE INTEGRATION
 * Version : 1.1 MVP
 * =====================================================
 */

require_once __DIR__ . '/includes/auth.php';

// Prevent authenticated users from returning to the login form.
$baseUrl = siaga_project_base_url();

if (siaga_is_logged_in()) {
    $currentUser = siaga_current_user();

    $destination = ($currentUser['role'] === 'volunteer')
        ? '/community-tasks.php'
        : '/dashboard.php';

    header('Location: ' . $baseUrl . $destination, true, 303);
    exit;
}

// Prevent cached login pages from showing stale session information.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

// Read and clear one-time session notifications.
$loginError = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

$loginNotice = '';
if (($_GET['status'] ?? '') === 'logged_out') {
    $loginNotice = 'Anda berhasil keluar dari akun SIAGAKAMPUNG.';
}

// Escape messages before rendering them in HTML.
$escapeLoginMessage = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | SIAGAKAMPUNG</title>

    <meta name="description" content="Login SIAGAKAMPUNG untuk koordinator, relawan, dan administrator.">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        :root {
            --login-primary: #174a78;
            --login-secondary: #087f8c;
            --login-dark: #102d49;
            --login-muted: #718096;
            --login-bg: #f4f7fb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            background: var(--login-bg);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
        }

        /* MAIN LAYOUT */

        .login-layout {
            min-height: 100vh;
        }

        /* LEFT PANEL */

        .login-visual {
            min-height: 100vh;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 7%;
            color: #fff;

            background:
                radial-gradient(
                    circle at 85% 15%,
                    rgba(51, 190, 187, 0.25),
                    transparent 35%
                ),
                linear-gradient(
                    145deg,
                    #102c49 0%,
                    #174a78 55%,
                    #087f8c 100%
                );
        }

        .login-visual::before {
            content: "";
            position: absolute;
            width: 420px;
            height: 420px;
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 50%;
            right: -150px;
            bottom: 60px;
        }

        .login-visual::after {
            content: "";
            position: absolute;
            width: 280px;
            height: 280px;
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 50%;
            right: -80px;
            bottom: 130px;
        }

        .visual-content,
        .visual-brand,
        .visual-footer {
            position: relative;
            z-index: 2;
        }

        .visual-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 14px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .brand-title {
            font-size: 19px;
            font-weight: 800;
            letter-spacing: 0.4px;
        }

        .brand-subtitle {
            display: block;
            font-size: 9px;
            letter-spacing: 1.5px;
            opacity: 0.75;
            margin-top: 2px;
        }

        .visual-content {
            max-width: 560px;
            margin: 60px 0;
        }

        .visual-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.15);
            padding: 9px 15px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            margin-bottom: 25px;
        }

        .visual-content h1 {
            font-size: clamp(36px, 4vw, 55px);
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 22px;
            color: #fff;
        }

        .visual-content h1 span {
            color: #72e2d5;
        }

        .visual-description {
            font-size: 15px;
            line-height: 1.9;
            color: rgba(255,255,255,0.75);
            max-width: 460px;
        }

        /* VISUAL FEATURE CARDS */

        .visual-feature {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(255,255,255,0.09);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 15px;
            margin-top: 13px;
            backdrop-filter: blur(10px);
        }

        .visual-feature-icon {
            width: 43px;
            height: 43px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
        }

        .visual-feature h6 {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 4px;
            color: white;
        }

        .visual-feature p {
            font-size: 11px;
            color: rgba(255,255,255,0.7);
            margin: 0;
        }

        .visual-footer {
            color: rgba(255,255,255,0.6);
            font-size: 11px;
        }

        /* RIGHT PANEL */

        .login-main {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 30px;
        }

        .login-content {
            width: 100%;
            max-width: 440px;
        }

        .mobile-brand {
            display: none;
            margin-bottom: 35px;
        }

        .back-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--login-muted);
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 38px;
            transition: 0.2s;
        }

        .back-home:hover {
            color: var(--login-primary);
        }

        .login-heading {
            margin-bottom: 30px;
        }

        .login-heading .eyebrow {
            color: var(--login-secondary);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 13px;
        }

        .login-heading h2 {
            color: var(--login-dark);
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.8px;
            margin-bottom: 10px;
        }

        .login-heading p {
            color: var(--login-muted);
            font-size: 14px;
            line-height: 1.7;
        }

        /* FORM */

        .login-form .form-label {
            font-size: 13px;
            font-weight: 700;
            color: #34445a;
            margin-bottom: 9px;
        }

        .login-form .form-control {
            height: 54px;
            border: 1px solid #e0e7ef;
            border-radius: 11px;
            background: #fff;
            font-size: 14px;
            padding: 12px 15px;
            box-shadow: none;
            transition: 0.2s;
        }

        .login-form .form-control:focus {
            border-color: var(--login-secondary);
            box-shadow: 0 0 0 4px rgba(8,127,140,0.10);
        }

        .input-group-password {
            position: relative;
        }

        .input-group-password .form-control {
            padding-right: 55px;
        }

        .password-toggle {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #718096;
            cursor: pointer;
            padding: 5px;
            z-index: 5;
        }

        .password-toggle:hover {
            color: var(--login-primary);
        }

        .login-submit {
            height: 54px;
            width: 100%;
            border: none;
            border-radius: 11px;
            background: linear-gradient(
                110deg,
                var(--login-primary),
                var(--login-secondary)
            );
            color: white;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.3px;
            transition: 0.25s;
        }

        .login-submit:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 9px 20px rgba(23,74,120,0.2);
        }

        .login-submit:active {
            transform: translateY(0);
        }

        /* ACCESS INFORMATION */

        .access-information {
            background: #eaf5f8;
            border: 1px solid #d5eaf0;
            border-radius: 12px;
            padding: 15px;
            margin-top: 25px;
        }

        .access-information h6 {
            color: #174a78;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .access-information p {
            color: #597186;
            font-size: 11px;
            line-height: 1.7;
            margin: 0;
        }

        .login-bottom {
            margin-top: 32px;
            text-align: center;
            color: #98a3b1;
            font-size: 11px;
        }

        /* RESPONSIVE */

        @media (max-width: 991.98px) {

            .login-visual {
                display: none;
            }

            .login-main {
                min-height: 100vh;
                padding: 35px 22px;
            }

            .mobile-brand {
                display: block;
            }

            .back-home {
                margin-bottom: 30px;
            }

        }

        @media (max-width: 575.98px) {

            .login-main {
                padding: 25px 20px;
            }

            .login-heading h2 {
                font-size: 27px;
            }

            .login-content {
                max-width: 100%;
            }

        }

    </style>

</head>

<body>

<main class="container-fluid p-0">

    <div class="row g-0 login-layout">

        <!-- ================================= -->
        <!-- LEFT PANEL : BRANDING -->
        <!-- ================================= -->

        <div class="col-lg-6 login-visual">

            <!-- BRAND -->

            <a href="index.php" class="visual-brand">

                <div class="brand-icon">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         width="27"
                         height="27"
                         fill="currentColor"
                         viewBox="0 0 16 16">

                        <path d="M8 1 1.5 4v4.5c0 3.2 2.3 5.4 6.5 6.5 4.2-1.1 6.5-3.3 6.5-6.5V4L8 1z"/>

                        <path d="m8 3.2-2.5 4h1.6l-.4 2.7 2.8-4.1H7.8l.8-2.6z"
                              fill="#174a78"/>

                    </svg>

                </div>

                <div>

                    <div class="brand-title">
                        SIAGA<span style="color:#72e2d5;">KAMPUNG</span>
                    </div>

                    <span class="brand-subtitle">
                        COMMUNITY DISASTER READINESS
                    </span>

                </div>

            </a>


            <!-- BRANDING CONTENT -->

            <div class="visual-content">

                <div class="visual-badge">

                    <span style="color:#72e2d5;">●</span>

                    COMMUNITY READINESS PLATFORM

                </div>

                <h1>
                    Bersama Membangun
                    Kampung yang
                    <span>Lebih Siaga.</span>
                </h1>

                <p class="visual-description">
                    Kelola kesiapsiagaan keluarga, pantau rencana
                    evakuasi, dan koordinasikan tindakan pendampingan
                    masyarakat melalui satu platform terintegrasi.
                </p>


                <!-- FEATURE 1 -->

                <div class="visual-feature">

                    <div class="visual-feature-icon">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="23"
                             height="23"
                             fill="currentColor"
                             viewBox="0 0 16 16">

                            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm4-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM2 13c0-2.2 2.7-3.5 6-3.5s6 1.3 6 3.5v1H2v-1z"/>

                        </svg>

                    </div>

                    <div>

                        <h6>Kartu Siaga Keluarga</h6>

                        <p>Pendataan dan monitoring kesiapsiagaan.</p>

                    </div>

                </div>


                <!-- FEATURE 2 -->

                <div class="visual-feature">

                    <div class="visual-feature-icon">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="23"
                             height="23"
                             fill="currentColor"
                             viewBox="0 0 16 16">

                            <path d="M8 0a5 5 0 0 0-5 5c0 3.7 5 11 5 11s5-7.3 5-11a5 5 0 0 0-5-5zm0 7.5A2.5 2.5 0 1 1 8 2.5a2.5 2.5 0 0 1 0 5z"/>

                        </svg>

                    </div>

                    <div>

                        <h6>Rencana Evakuasi Komunitas</h6>

                        <p>Informasi jalur dan titik kumpul.</p>

                    </div>

                </div>


                <!-- FEATURE 3 -->

                <div class="visual-feature">

                    <div class="visual-feature-icon">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="23"
                             height="23"
                             fill="currentColor"
                             viewBox="0 0 16 16">

                            <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zM7 4h2v4H7V4zm0 5h2v2H7V9z"/>

                        </svg>

                    </div>

                    <div>

                        <h6>Readiness Gap Radar</h6>

                        <p>Identifikasi kekurangan dan tugas relawan.</p>

                    </div>

                </div>

            </div>


            <!-- FOOTER LEFT -->

            <div class="visual-footer">

                &copy; 2026 SIAGAKAMPUNG. Satu Kampung,
                Satu Rencana Kesiapsiagaan.

            </div>

        </div>


        <!-- ================================= -->
        <!-- RIGHT PANEL : LOGIN FORM -->
        <!-- ================================= -->

        <div class="col-lg-6 login-main">

            <div class="login-content">


                <!-- MOBILE BRAND -->

                <div class="mobile-brand">

                    <a href="index.php"
                       class="text-decoration-none d-inline-flex align-items-center gap-2">

                        <div class="bg-primary text-white rounded-3 p-2">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                 width="26"
                                 height="26"
                                 fill="currentColor"
                                 viewBox="0 0 16 16">

                                <path d="M8 1 1.5 4v4.5c0 3.2 2.3 5.4 6.5 6.5 4.2-1.1 6.5-3.3 6.5-6.5V4L8 1z"/>

                            </svg>

                        </div>

                        <div>

                            <div class="fw-bold text-primary">
                                SIAGA<span class="text-dark">KAMPUNG</span>
                            </div>

                            <small class="text-secondary"
                                   style="font-size:9px;letter-spacing:1px;">
                                COMMUNITY READINESS
                            </small>

                        </div>

                    </a>

                </div>


                <!-- BACK HOME -->

                <a href="index.php" class="back-home">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         width="17"
                         height="17"
                         fill="currentColor"
                         viewBox="0 0 16 16">

                        <path fill-rule="evenodd"
                              d="M15 8a.5.5 0 0 0-.5-.5H2.707l4.147-4.146a.5.5 0 1 0-.708-.708l-5 5a.5.5 0 0 0 0 .708l5 5a.5.5 0 1 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>

                    </svg>

                    Kembali ke Beranda

                </a>


                <!-- LOGIN HEADING -->

                <div class="login-heading">

                    <div class="eyebrow">
                        SECURE ACCOUNT ACCESS
                    </div>

                    <h2>
                        Selamat Datang!
                    </h2>

                    <p>
                        Silakan masuk menggunakan akun resmi Anda
                        untuk mengakses sistem pengelolaan
                        kesiapsiagaan komunitas.
                    </p>

                </div>


                <!-- LOGIN NOTIFICATIONS -->

                <?php if ($loginError !== ''): ?>
                    <div class="alert alert-danger d-flex align-items-start gap-2 mb-4"
                         role="alert" aria-live="polite">
                        <span aria-hidden="true">&#9888;</span>
                        <div><?= $escapeLoginMessage($loginError); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($loginNotice !== ''): ?>
                    <div class="alert alert-success d-flex align-items-start gap-2 mb-4"
                         role="status" aria-live="polite">
                        <span aria-hidden="true">&#10003;</span>
                        <div><?= $escapeLoginMessage($loginNotice); ?></div>
                    </div>
                <?php endif; ?>


                <!-- LOGIN FORM -->

                <form id="loginForm"
                      action="actions/login_process.php"
                      method="POST"
                      class="login-form">


                    <!-- EMAIL -->

                    <div class="mb-4">

                        <label for="email" class="form-label">
                            Alamat Email
                        </label>

                        <input type="email"
                               class="form-control"
                               id="email"
                               name="email"
                               placeholder="nama@email.com"
                               autocomplete="username"
                               maxlength="100"
                               required>

                    </div>


                    <!-- PASSWORD -->

                    <div class="mb-4">

                        <label for="password" class="form-label">
                            Password
                        </label>

                        <div class="input-group-password">

                            <input type="password"
                                   class="form-control"
                                   id="password"
                                   name="password"
                                   placeholder="Masukkan password Anda"
                                   autocomplete="current-password"
                                   required>

                            <button type="button"
                                    class="password-toggle"
                                    id="togglePassword"
                                    aria-label="Tampilkan password"
                                    aria-pressed="false">

                                <svg id="eyeIcon"
                                     xmlns="http://www.w3.org/2000/svg"
                                     width="20"
                                     height="20"
                                     fill="currentColor"
                                     viewBox="0 0 16 16">

                                    <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM8 12c-3.5 0-6-3.2-7-4 1-1.2 3.5-4 7-4s6 2.8 7 4c-1 1.2-3.5 4-7 4z"/>

                                    <path d="M8 5a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/>

                                </svg>

                            </button>

                        </div>

                    </div>


                    <!-- SUBMIT -->

                    <div class="d-grid mt-4">

                        <button type="submit" class="login-submit">

                            Masuk ke Dashboard

                            <span class="ms-2">&rarr;</span>

                        </button>

                    </div>

                </form>


                <!-- ACCESS INFORMATION -->

                <div class="access-information">

                    <h6>

                        <span class="me-2">ⓘ</span>

                        INFORMASI AKSES PENGGUNA

                    </h6>

                    <p>
                        Halaman ini ditujukan untuk koordinator,
                        relawan, dan administrator. Hak akses
                        ditentukan berdasarkan peran akun setelah
                        proses autentikasi berhasil.
                    </p>

                </div>


                <!-- PUBLIC ACCESS INFORMATION -->

                <div class="text-center mt-4">

                    <p class="text-secondary small mb-2">
                        Anda warga yang ingin mendata kesiapsiagaan keluarga?
                    </p>

                    <a href="family-form.php"
                       class="text-primary fw-bold small text-decoration-none">

                        Isi Kartu Siaga Keluarga &rarr;

                    </a>

                </div>


                <!-- FOOTER -->

                <div class="login-bottom">

                    &copy; 2026 SIAGAKAMPUNG

                    <br>

                    Satu Kampung, Satu Rencana Kesiapsiagaan.

                </div>

            </div>

        </div>

    </div>

</main>


<!-- PASSWORD VISIBILITY SCRIPT -->

<script>

    const togglePassword = document.getElementById("togglePassword");

    const passwordInput = document.getElementById("password");

    const eyeIcon = document.getElementById("eyeIcon");

    togglePassword.addEventListener("click", function () {

        const isPassword = passwordInput.type === "password";

        passwordInput.type = isPassword ? "text" : "password";

        togglePassword.setAttribute(
            "aria-pressed",
            isPassword ? "true" : "false"
        );

        togglePassword.setAttribute(
            "aria-label",
            isPassword ? "Sembunyikan password" : "Tampilkan password"
        );

        eyeIcon.innerHTML = isPassword

            ? '<path d="M13.359 11.238C15.06 9.64 16 8 16 8s-3-5.5-8-5.5c-1.24 0-2.36.3-3.34.75l.8.8C6.3 3.7 7.1 3.5 8 3.5c3.5 0 6 3.2 7 4-.4.5-1.1 1.3-2.1 2.1l.46 1.64z"/><path d="m1.393.646-.707.708 2.1 2.1C.9 4.9 0 8 0 8s3 5.5 8 5.5c1.5 0 2.8-.4 3.9-1l2.45 2.45.707-.707L1.393.646zM8 12c-3.5 0-6-3.2-7-4 .4-.5 1.2-1.4 2.3-2.3l1.1 1.1a3 3 0 0 0 4.2 4.2l1.2 1.2c-.6.2-1.2.3-1.8.3z"/><path d="M8 5a3 3 0 0 0-2.9 3.7l3.6-3.6A3 3 0 0 0 8 5z"/>'

            : '<path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM8 12c-3.5 0-6-3.2-7-4 1-1.2 3.5-4 7-4s6 2.8 7 4c-1 1.2-3.5 4-7 4z"/><path d="M8 5a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/>';

    });

</script>

</body>

</html>
