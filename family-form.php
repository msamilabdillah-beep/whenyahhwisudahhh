<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Daftar Keluarga | SIAGAKAMPUNG</title>

    <meta name="description" content="Daftarkan keluarga Anda dalam sistem Kartu Siaga Keluarga SIAGAKAMPUNG.">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>

        :root {
            --primary: #174a78;
            --primary-dark: #102d49;
            --secondary: #087f8c;
            --muted: #718096;
            --background: #f4f7fb;
            --border: #e4eaf1;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: var(--background);
            color: #24364b;
            font-family: Inter, "Segoe UI", Arial, sans-serif;
        }

        /* NAVBAR */

        .main-navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
        }

        .brand-logo {
            width: 43px;
            height: 43px;
            background: var(--primary);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-name {
            font-weight: 800;
            color: var(--primary);
            font-size: 17px;
            letter-spacing: 0.3px;
        }

        .brand-subtitle {
            display: block;
            font-size: 9px;
            color: #8a97a8;
            letter-spacing: 1.2px;
        }

        /* HERO */

        .form-hero {
            background:
                radial-gradient(
                    circle at 85% 20%,
                    rgba(51, 190, 187, 0.18),
                    transparent 30%
                ),
                linear-gradient(
                    120deg,
                    #174672,
                    #174a78 65%,
                    #087f8c
                );

            color: white;
            padding: 55px 0 85px;
        }

        .hero-badge {
            display: inline-block;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 50px;
            padding: 9px 16px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .form-hero h1 {
            font-weight: 800;
            font-size: clamp(30px, 4vw, 43px);
            line-height: 1.2;
            margin-top: 20px;
            color: white;
        }

        .form-hero p {
            color: rgba(255,255,255,0.75);
            max-width: 650px;
            font-size: 15px;
            line-height: 1.8;
        }

        /* MAIN CONTENT */

        .form-wrapper {
            margin-top: -45px;
            position: relative;
            z-index: 2;
            padding-bottom: 65px;
        }

        .main-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 19px;
            padding: 35px;
            box-shadow: 0 12px 35px rgba(16,45,73,0.06);
        }

        .section-heading {
            font-size: 20px;
            font-weight: 800;
            color: var(--primary-dark);
        }

        .section-description {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.7;
        }

        .section-number {
            width: 40px;
            height: 40px;
            flex-shrink: 0;
            border-radius: 12px;
            background: #eaf2fa;
            color: var(--primary);
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 800;
        }

        /* FORM INPUT */

        .form-label {
            color: #34445a;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 9px;
        }

        .required-star {
            color: #dc3545;
        }

        .form-control {
            height: 52px;
            border-radius: 10px;
            border: 1px solid var(--border);
            padding: 12px 15px;
            font-size: 14px;
            background: #fff;
            box-shadow: none;
            transition: 0.2s;
        }

        .form-control:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 4px rgba(8,127,140,0.09);
        }

        .form-control::placeholder {
            color: #a6b1be;
        }

        .input-help {
            font-size: 11px;
            color: #8996a5;
            margin-top: 7px;
            line-height: 1.6;
        }

        /* INFO CARD */

        .info-card {
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 23px;
            background: white;
            margin-bottom: 20px;
        }

        .info-title {
            font-size: 15px;
            font-weight: 800;
            color: var(--primary-dark);
        }

        .info-description {
            font-size: 12px;
            color: var(--muted);
            line-height: 1.7;
        }

        /* INDICATORS */

        .indicator-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #edf0f4;
        }

        .indicator-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .indicator-icon {
            width: 36px;
            height: 36px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #edf5fa;
            color: var(--primary);
            border-radius: 10px;
        }

        .indicator-name {
            font-size: 12px;
            font-weight: 700;
            color: #34445a;
        }

        .indicator-description {
            font-size: 10px;
            color: #8996a5;
            margin-top: 3px;
        }

        /* PRIVACY */

        .privacy-card {
            background: #eaf5f8;
            border: 1px solid #d6e9ee;
            border-radius: 13px;
            padding: 17px;
        }

        .privacy-card h6 {
            font-size: 12px;
            color: var(--primary);
            font-weight: 800;
        }

        .privacy-card p {
            font-size: 11px;
            line-height: 1.7;
            color: #597186;
            margin-bottom: 0;
        }

        /* SUBMIT BUTTON */

        .submit-button {
            background: linear-gradient(
                110deg,
                var(--primary),
                var(--secondary)
            );

            color: white;
            border: none;
            border-radius: 11px;
            min-height: 54px;
            font-weight: 700;
            font-size: 14px;
            padding: 12px 25px;
            transition: 0.25s;
        }

        .submit-button:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(23,74,120,0.2);
        }

        .back-button {
            border: 1px solid var(--border);
            background: white;
            color: #536477;
            border-radius: 11px;
            min-height: 54px;
            padding: 12px 22px;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .back-button:hover {
            background: #f4f7fb;
            color: var(--primary);
        }

        /* FOOTER */

        .page-footer {
            background: #102d49;
            color: rgba(255,255,255,0.65);
            padding: 30px 0;
            font-size: 11px;
        }

        /* RESPONSIVE */

        @media (max-width: 767px) {

            .form-hero {
                padding: 40px 0 75px;
            }

            .main-card {
                padding: 23px 19px;
                border-radius: 15px;
            }

            .form-wrapper {
                margin-top: -40px;
            }

            .section-heading {
                font-size: 17px;
            }

            .form-hero p {
                font-size: 13px;
            }

        }

    </style>

</head>

<body>


<!-- ================================= -->
<!-- NAVIGATION -->
<!-- ================================= -->

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
                    <a class="nav-link"
                       href="index.php">
                        Beranda
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link active fw-semibold text-primary"
                       href="family-form.php">
                        Kartu Siaga
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link"
                       href="evacuation-plan.php">
                        Rencana Evakuasi
                    </a>
                </li>

                <li class="nav-item">
                    <a href="login.php"
                       class="btn btn-outline-primary rounded-pill px-4 mt-2 mt-lg-0">
                        Masuk
                    </a>
                </li>

            </ul>

        </div>

    </div>

</nav>


<!-- ================================= -->
<!-- HERO SECTION -->
<!-- ================================= -->

<section class="form-hero">

    <div class="container">

        <div class="hero-badge">

            <span class="me-2">●</span>

            FAMILY READINESS REGISTRATION

        </div>

        <h1>
            Mulai Kesiapsiagaan<br>
            dari Keluarga Anda.
        </h1>

        <p class="mt-3 mb-0">

            Daftarkan keluarga Anda dalam Kartu Siaga Keluarga.
            Lengkapi informasi dasar untuk membantu membangun
            lingkungan yang lebih siap menghadapi bencana.

        </p>

    </div>

</section>


<!-- ================================= -->
<!-- MAIN FORM CONTENT -->
<!-- ================================= -->

<main class="container form-wrapper">

    <div class="row g-4">


        <!-- ================================= -->
        <!-- LEFT : REGISTRATION FORM -->
        <!-- ================================= -->

        <div class="col-lg-8">

            <div class="main-card">


                <!-- FORM HEADER -->

                <div class="d-flex align-items-start gap-3 mb-4">

                    <div class="section-number">
                        01
                    </div>

                    <div>

                        <h2 class="section-heading mb-1">
                            Formulir Data Keluarga
                        </h2>

                        <p class="section-description mb-0">
                            Lengkapi informasi berikut dengan benar.
                            Seluruh kolom bertanda bintang wajib diisi.
                        </p>

                    </div>

                </div>


                <hr class="border-light mb-4">


                <!-- ================================= -->
                <!-- FAMILY FORM -->
                <!-- ================================= -->

                <form id="familyForm"
                      action="actions/family_save.php"
                      method="POST">


                    <!-- SECTION 1 -->

                    <div class="mb-4">

                        <h6 class="fw-bold text-dark mb-3">
                            A. Identitas Keluarga
                        </h6>


                        <!-- FAMILY NAME -->

                        <div class="mb-4">

                            <label for="family_name" class="form-label">

                                Nama Keluarga / Kepala Keluarga

                                <span class="required-star">*</span>

                            </label>

                            <input type="text"
                                   class="form-control"
                                   id="family_name"
                                   name="family_name"
                                   placeholder="Contoh: Keluarga Bapak Ahmad"
                                   maxlength="100"
                                   autocomplete="name"
                                   required>

                            <div class="input-help">
                                Masukkan nama keluarga atau nama kepala keluarga.
                            </div>

                        </div>


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

                            <div class="input-help">
                                Masukkan identitas wilayah tempat keluarga tinggal.
                            </div>

                        </div>


                        <!-- MEMBER COUNT -->

                        <div class="mb-4">

                            <label for="member_count" class="form-label">

                                Jumlah Anggota Keluarga

                                <span class="required-star">*</span>

                            </label>

                            <div class="input-group">

                                <input type="number"
                                       class="form-control"
                                       id="member_count"
                                       name="member_count"
                                       placeholder="Contoh: 4"
                                       min="1"
                                       step="1"
                                       required>

                                <span class="input-group-text bg-light border-0 px-3">

                                    Orang

                                </span>

                            </div>

                            <div class="input-help">
                                Masukkan jumlah seluruh anggota keluarga dalam satu keluarga.
                            </div>

                        </div>

                    </div>


                    <hr class="border-light my-4">


                    <!-- SECTION 2 -->

                    <div class="mb-4">

                        <h6 class="fw-bold text-dark mb-3">
                            B. Kontak Darurat
                        </h6>


                        <div class="mb-3">

                            <label for="emergency_contact" class="form-label">

                                Nomor Telepon Darurat Keluarga

                                <span class="required-star">*</span>

                            </label>

                            <input type="tel"
                                   class="form-control"
                                   id="emergency_contact"
                                   name="emergency_contact"
                                   placeholder="Contoh: 081234567890"
                                   maxlength="25"
                                   inputmode="tel"
                                   autocomplete="tel"
                                   required>

                            <div class="input-help">
                                Gunakan nomor telepon aktif yang dapat dihubungi
                                ketika terjadi kondisi darurat.
                            </div>

                        </div>

                    </div>


                    <hr class="border-light my-4">


                    <!-- INFORMATION -->

                    <div class="privacy-card mb-4">

                        <div class="d-flex align-items-start gap-3">

                            <div class="text-primary fs-4">
                                &#128274;
                            </div>

                            <div>

                                <h6 class="mb-2">
                                    Perlindungan Data Keluarga
                                </h6>

                                <p>

                                    Informasi keluarga dan nomor kontak darurat
                                    digunakan untuk pengelolaan kesiapsiagaan.
                                    Data kontak keluarga tidak boleh ditampilkan
                                    secara publik.

                                </p>

                            </div>

                        </div>

                    </div>


                    <!-- SUBMIT ACTION -->

                    <div class="d-flex flex-column flex-sm-row gap-3">

                        <a href="index.php" class="back-button">

                            &larr; Kembali

                        </a>


                        <button type="submit"
                                class="submit-button flex-grow-1">

                            Simpan Data &amp; Buat Kartu Siaga

                            <span class="ms-2">&rarr;</span>

                        </button>

                    </div>


                    <p class="text-secondary text-center mt-3 mb-0"
                       style="font-size:10px;">

                        Kode keluarga akan dibuat otomatis
                        setelah data berhasil disimpan.

                    </p>


                </form>

            </div>

        </div>


        <!-- ================================= -->
        <!-- RIGHT : INFORMATION PANEL -->
        <!-- ================================= -->

        <div class="col-lg-4">


            <!-- INDICATOR CARD -->

            <div class="info-card">

                <div class="d-flex align-items-center gap-2 mb-2">

                    <div class="section-number">
                        05
                    </div>

                    <div>

                        <h5 class="info-title mb-0">
                            Indikator Kesiapsiagaan
                        </h5>

                        <small class="text-secondary"
                               style="font-size:10px;">
                            Checklist Kartu Siaga
                        </small>

                    </div>

                </div>


                <p class="info-description mt-3 mb-3">

                    Setelah pendaftaran, keluarga dapat
                    melengkapi lima indikator berikut.

                </p>


                <!-- INDICATOR 1 -->

                <div class="indicator-item">

                    <div class="indicator-icon">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="18"
                             height="18"
                             fill="currentColor"
                             viewBox="0 0 16 16">

                            <path d="M8 1a2 2 0 0 0-2 2v1H4a2 2 0 0 0-2 2v7h12V6a2 2 0 0 0-2-2h-2V3a2 2 0 0 0-2-2z"/>

                        </svg>

                    </div>

                    <div>

                        <div class="indicator-name">
                            Tas Siaga
                        </div>

                        <div class="indicator-description">
                            Perlengkapan darurat keluarga
                        </div>

                    </div>

                </div>


                <!-- INDICATOR 2 -->

                <div class="indicator-item">

                    <div class="indicator-icon">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="18"
                             height="18"
                             fill="currentColor"
                             viewBox="0 0 16 16">

                            <path d="M3.654 1.328a.678.678 0 0 1 .738-.058l2.522 1.26c.329.164.489.543.382.894l-.53 1.766a.678.678 0 0 0 .17.69l2.183 2.183a.678.678 0 0 0 .69.17l1.766-.53a.678.678 0 0 1 .894.382l1.26 2.522a.678.678 0 0 1-.058.738l-1.17 1.17c-.54.54-1.33.75-2.06.55-2.02-.55-4.15-2.09-6.17-4.11S1.59 4.81 1.04 2.79c-.2-.73.01-1.52.55-2.06l2.064.598z"/>

                        </svg>

                    </div>

                    <div>

                        <div class="indicator-name">
                            Kontak Darurat
                        </div>

                        <div class="indicator-description">
                            Daftar nomor penting
                        </div>

                    </div>

                </div>


                <!-- INDICATOR 3 -->

                <div class="indicator-item">

                    <div class="indicator-icon">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="18"
                             height="18"
                             fill="currentColor"
                             viewBox="0 0 16 16">

                            <path d="M8 1.5 1 14h14L8 1.5zm0 3.1 3.8 7H4.2L8 4.6zM7.25 7v2.5h1.5V7h-1.5zm0 3.2v1h1.5v-1h-1.5z"/>

                        </svg>

                    </div>

                    <div>

                        <div class="indicator-name">
                            Perlengkapan P3K
                        </div>

                        <div class="indicator-description">
                            Pertolongan pertama
                        </div>

                    </div>

                </div>


                <!-- INDICATOR 4 -->

                <div class="indicator-item">

                    <div class="indicator-icon">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="18"
                             height="18"
                             fill="currentColor"
                             viewBox="0 0 16 16">

                            <path d="M4 1h6l3 3v10H3V1h1zm5 1v3h3L9 2zm-4 5v1h6V7H5zm0 2v1h6V9H5z"/>

                        </svg>

                    </div>

                    <div>

                        <div class="indicator-name">
                            Dokumen Penting
                        </div>

                        <div class="indicator-description">
                            Dokumen keluarga terlindungi
                        </div>

                    </div>

                </div>


                <!-- INDICATOR 5 -->

                <div class="indicator-item">

                    <div class="indicator-icon">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="18"
                             height="18"
                             fill="currentColor"
                             viewBox="0 0 16 16">

                            <path d="M8 0a5 5 0 0 0-5 5c0 3.7 5 11 5 11s5-7.3 5-11a5 5 0 0 0-5-5zm0 7.5A2.5 2.5 0 1 1 8 2.5a2.5 2.5 0 0 1 0 5z"/>

                        </svg>

                    </div>

                    <div>

                        <div class="indicator-name">
                            Titik Kumpul Keluarga
                        </div>

                        <div class="indicator-description">
                            Lokasi berkumpul saat darurat
                        </div>

                    </div>

                </div>


            </div>


            <!-- PROCESS CARD -->

            <div class="info-card">

                <h5 class="info-title mb-3">
                    Bagaimana Prosesnya?
                </h5>


                <div class="d-flex gap-3 mb-3">

                    <div class="section-number"
                         style="width:32px;height:32px;font-size:12px;">
                        01
                    </div>

                    <div>

                        <div class="indicator-name">
                            Isi Data Keluarga
                        </div>

                        <p class="info-description mb-0">
                            Lengkapi formulir pendaftaran.
                        </p>

                    </div>

                </div>


                <div class="d-flex gap-3 mb-3">

                    <div class="section-number"
                         style="width:32px;height:32px;font-size:12px;">
                        02
                    </div>

                    <div>

                        <div class="indicator-name">
                            Dapatkan Kode Keluarga
                        </div>

                        <p class="info-description mb-0">
                            Sistem membuat kode identifikasi keluarga.
                        </p>

                    </div>

                </div>


                <div class="d-flex gap-3">

                    <div class="section-number"
                         style="width:32px;height:32px;font-size:12px;">
                        03
                    </div>

                    <div>

                        <div class="indicator-name">
                            Lengkapi Checklist
                        </div>

                        <p class="info-description mb-0">
                            Periksa dan perbarui lima indikator
                            kesiapsiagaan keluarga.
                        </p>

                    </div>

                </div>

            </div>


            <!-- DISCLAIMER -->

            <div class="alert alert-warning border-0 rounded-4">

                <div class="d-flex gap-2">

                    <span class="fs-5">
                        &#9888;
                    </span>

                    <div>

                        <h6 class="fw-bold mb-1"
                            style="font-size:12px;">

                            Informasi Penting

                        </h6>

                        <p class="mb-0"
                           style="font-size:11px;line-height:1.7;">

                            Persentase checklist menunjukkan
                            kelengkapan indikator, bukan jaminan
                            keselamatan keluarga dari bencana.

                        </p>

                    </div>

                </div>

            </div>


        </div>

    </div>

</main>


<!-- ================================= -->
<!-- FOOTER -->
<!-- ================================= -->

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


<!-- BOOTSTRAP JS -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
