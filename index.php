
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SIAGAKAMPUNG | Kesiapsiagaan Bencana Komunitas</title>

    <meta name="description" content="SIAGAKAMPUNG adalah platform digital untuk membantu masyarakat mengelola kesiapsiagaan keluarga, rencana evakuasi, dan pendampingan relawan.">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<!-- ================= NAVBAR ================= -->

<nav class="navbar navbar-expand-lg bg-white sticky-top border-bottom">
    <div class="container py-2">

        <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2"
           href="index.php">

            <span class="bg-primary text-white rounded-3 p-2">
                <svg xmlns="http://www.w3.org/2000/svg"
                     width="25" height="25"
                     fill="currentColor"
                     viewBox="0 0 16 16">
                    <path d="M8 1 1.5 4v4.5c0 3.2 2.3 5.4 6.5 6.5 4.2-1.1 6.5-3.3 6.5-6.5V4L8 1zm0 1.2 5.5 2.5v3.8c0 2.4-1.7 4.2-5.5 5.3-3.8-1.1-5.5-2.9-5.5-5.3V4.7L8 2.2z"/>
                    <path d="M8 4.2 5.2 9h1.7l-.5 2.8L10.8 7H9l.7-2.8H8z"/>
                </svg>
            </span>

            <span>
                SIAGA<span class="text-dark">KAMPUNG</span>
                <small class="d-block text-secondary fw-normal"
                       style="font-size: 10px; letter-spacing: 1px;">
                    COMMUNITY DISASTER READINESS
                </small>
            </span>

        </a>

        <button class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNavbar"
                aria-controls="mainNavbar"
                aria-expanded="false"
                aria-label="Buka navigasi">

            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">

            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">

                <li class="nav-item">
                    <a class="nav-link active fw-semibold"
                       href="index.php">
                        Beranda
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link"
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

                <li class="nav-item mt-2 mt-lg-0">
                    <a href="login.php"
                       class="btn btn-outline-primary rounded-pill px-4">
                        Masuk
                    </a>
                </li>

            </ul>

        </div>

    </div>
</nav>


<!-- ================= HERO SECTION ================= -->

<section class="py-5 bg-body-tertiary">

    <div class="container py-lg-5">

        <div class="row align-items-center g-5">

            <!-- Hero Content -->

            <div class="col-lg-6">

                <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2 mb-3">
                    PLATFORM KESIAPSIAGAAN KOMUNITAS
                </span>

                <h1 class="display-4 fw-bold lh-sm mb-4">
                    Siaga Dimulai
                    dari <span class="text-primary">Keluarga.</span>
                </h1>

                <p class="lead text-secondary mb-4">
                    Bangun lingkungan yang lebih siap menghadapi bencana
                    melalui pendataan keluarga, perencanaan evakuasi,
                    dan pendampingan masyarakat secara terintegrasi.
                </p>

                <div class="d-flex flex-wrap gap-3">

                    <a href="family-form.php"
                       class="btn btn-primary btn-lg rounded-pill px-4">
                        Buat Kartu Siaga
                        <span class="ms-2">&rarr;</span>
                    </a>

                    <a href="evacuation-plan.php"
                       class="btn btn-outline-secondary btn-lg rounded-pill px-4">
                        Lihat Rencana Evakuasi
                    </a>

                </div>

                <div class="d-flex align-items-center gap-2 mt-4 text-secondary small">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         width="18" height="18"
                         fill="currentColor"
                         viewBox="0 0 16 16">
                        <path d="M8 0a5 5 0 0 0-5 5c0 3.7 5 11 5 11s5-7.3 5-11a5 5 0 0 0-5-5zm0 7.5A2.5 2.5 0 1 1 8 2.5a2.5 2.5 0 0 1 0 5z"/>
                    </svg>

                    <span>Berbasis kesiapsiagaan masyarakat.</span>

                </div>

            </div>


            <!-- Hero Illustration -->

            <div class="col-lg-6">

                <div class="bg-white rounded-4 shadow-sm p-4 p-lg-5">

                    <div class="d-flex justify-content-between align-items-center mb-4">

                        <div>
                            <span class="text-secondary small">
                                SIAGA KAMPUNG
                            </span>

                            <h4 class="fw-bold mb-0">
                                Sistem Kesiapsiagaan
                            </h4>
                        </div>

                        <div class="bg-primary-subtle text-primary rounded-3 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 width="32" height="32"
                                 fill="currentColor"
                                 viewBox="0 0 16 16">
                                <path d="M8 1 1.5 4v4.5c0 3.2 2.3 5.4 6.5 6.5 4.2-1.1 6.5-3.3 6.5-6.5V4L8 1zm0 1.2 5.5 2.5v3.8c0 2.4-1.7 4.2-5.5 5.3-3.8-1.1-5.5-2.9-5.5-5.3V4.7L8 2.2z"/>
                            </svg>
                        </div>

                    </div>


                    <!-- Feature Overview -->

                    <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-body-tertiary mb-3">

                        <div class="bg-primary-subtle text-primary rounded-3 p-3">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                 width="26" height="26"
                                 fill="currentColor"
                                 viewBox="0 0 16 16">
                                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm4-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM2 13c0-2.2 2.7-3.5 6-3.5s6 1.3 6 3.5v1H2v-1z"/>
                            </svg>

                        </div>

                        <div>
                            <h6 class="fw-bold mb-1">
                                Kartu Siaga Keluarga
                            </h6>

                            <p class="small text-secondary mb-0">
                                Pendataan dan checklist kesiapsiagaan.
                            </p>
                        </div>

                        <span class="ms-auto text-primary fw-bold">&rarr;</span>

                    </div>


                    <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-body-tertiary mb-3">

                        <div class="bg-success-subtle text-success rounded-3 p-3">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                 width="26" height="26"
                                 fill="currentColor"
                                 viewBox="0 0 16 16">
                                <path d="M8 0a5 5 0 0 0-5 5c0 3.7 5 11 5 11s5-7.3 5-11a5 5 0 0 0-5-5zm0 7.5A2.5 2.5 0 1 1 8 2.5a2.5 2.5 0 0 1 0 5z"/>
                            </svg>

                        </div>

                        <div>
                            <h6 class="fw-bold mb-1">
                                Rencana Evakuasi
                            </h6>

                            <p class="small text-secondary mb-0">
                                Informasi jalur dan titik kumpul.
                            </p>
                        </div>

                        <span class="ms-auto text-success fw-bold">&rarr;</span>

                    </div>


                    <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-body-tertiary">

                        <div class="bg-warning-subtle text-warning rounded-3 p-3">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                 width="26" height="26"
                                 fill="currentColor"
                                 viewBox="0 0 16 16">
                                <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zM7 4h2v4H7V4zm0 5h2v2H7V9z"/>
                            </svg>

                        </div>

                        <div>
                            <h6 class="fw-bold mb-1">
                                Readiness Gap Radar
                            </h6>

                            <p class="small text-secondary mb-0">
                                Identifikasi kekurangan dan tugas relawan.
                            </p>
                        </div>

                        <span class="ms-auto text-warning fw-bold">&rarr;</span>

                    </div>


                    <div class="alert alert-info mt-4 mb-0 small">
                        <strong>Catatan:</strong>
                        Tampilan ini merupakan ilustrasi simulasi,
                        bukan informasi kondisi kesiapsiagaan aktual.
                    </div>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- ================= SIMULATION NOTICE ================= -->

<section class="py-3">

    <div class="container">

        <div class="alert alert-warning border-0 rounded-3 mb-0 d-flex gap-3 align-items-start">

            <span class="fs-4">&#9888;</span>

            <div>
                <strong>Informasi Simulasi</strong>

                <p class="mb-0 small">
                    Website ini menggunakan data simulasi untuk demonstrasi.
                    Informasi rencana evakuasi yang belum diverifikasi
                    tidak boleh digunakan sebagai panduan evakuasi aktual.
                </p>
            </div>

        </div>

    </div>

</section>


<!-- ================= CORE FEATURES ================= -->

<section class="py-5">

    <div class="container py-4">

        <div class="text-center mb-5">

            <span class="text-primary fw-semibold small text-uppercase">
                Fitur Utama
            </span>

            <h2 class="fw-bold mt-2">
                Kesiapsiagaan dalam Satu Platform
            </h2>

            <p class="text-secondary mx-auto"
               style="max-width: 650px;">
                Tiga fitur utama yang menghubungkan pendataan keluarga,
                perencanaan evakuasi, dan tindakan pendampingan masyarakat.
            </p>

        </div>


        <div class="row g-4">

            <!-- Feature 1 -->

            <div class="col-md-6 col-lg-4">

                <div class="card h-100 border-0 shadow-sm rounded-4 p-3">

                    <div class="card-body">

                        <div class="bg-primary-subtle text-primary rounded-3 p-3 d-inline-flex mb-4">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                 width="32" height="32"
                                 fill="currentColor"
                                 viewBox="0 0 16 16">
                                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm4-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM2 13c0-2.2 2.7-3.5 6-3.5s6 1.3 6 3.5v1H2v-1z"/>
                            </svg>

                        </div>

                        <h5 class="fw-bold mb-3">
                            Kartu Siaga Keluarga
                        </h5>

                        <p class="text-secondary">
                            Catat data keluarga dan periksa kelengkapan
                            kebutuhan kesiapsiagaan melalui lima indikator.
                        </p>

                        <ul class="text-secondary small ps-3 mb-4">
                            <li>Data keluarga</li>
                            <li>Kontak darurat</li>
                            <li>Tas siaga dan P3K</li>
                            <li>Dokumen penting</li>
                            <li>Titik kumpul keluarga</li>
                        </ul>

                        <a href="family-form.php"
                           class="text-primary fw-semibold text-decoration-none">
                            Buat Kartu Siaga &rarr;
                        </a>

                    </div>

                </div>

            </div>


            <!-- Feature 2 -->

            <div class="col-md-6 col-lg-4">

                <div class="card h-100 border-0 shadow-sm rounded-4 p-3">

                    <div class="card-body">

                        <div class="bg-success-subtle text-success rounded-3 p-3 d-inline-flex mb-4">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                 width="32" height="32"
                                 fill="currentColor"
                                 viewBox="0 0 16 16">
                                <path d="M8 0a5 5 0 0 0-5 5c0 3.7 5 11 5 11s5-7.3 5-11a5 5 0 0 0-5-5zm0 7.5A2.5 2.5 0 1 1 8 2.5a2.5 2.5 0 0 1 0 5z"/>
                            </svg>

                        </div>

                        <h5 class="fw-bold mb-3">
                            Rencana Evakuasi Komunitas
                        </h5>

                        <p class="text-secondary">
                            Kelola informasi ancaman dan rencana evakuasi
                            agar masyarakat dapat mengakses informasi
                            yang telah diverifikasi.
                        </p>

                        <ul class="text-secondary small ps-3 mb-4">
                            <li>Jenis ancaman</li>
                            <li>Jalur utama</li>
                            <li>Jalur alternatif</li>
                            <li>Titik kumpul</li>
                            <li>Kontak koordinator</li>
                        </ul>

                        <a href="evacuation-plan.php"
                           class="text-success fw-semibold text-decoration-none">
                            Lihat Rencana &rarr;
                        </a>

                    </div>

                </div>

            </div>


            <!-- Feature 3 -->

            <div class="col-md-6 col-lg-4">

                <div class="card h-100 border-0 shadow-sm rounded-4 p-3">

                    <div class="card-body">

                        <div class="bg-warning-subtle text-warning rounded-3 p-3 d-inline-flex mb-4">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                 width="32" height="32"
                                 fill="currentColor"
                                 viewBox="0 0 16 16">
                                <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zM7 4h2v4H7V4zm0 5h2v2H7V9z"/>
                            </svg>

                        </div>

                        <h5 class="fw-bold mb-3">
                            Readiness Gap Radar
                        </h5>

                        <p class="text-secondary">
                            Identifikasi indikator kesiapsiagaan yang
                            belum terpenuhi dan ubah menjadi tugas
                            pendampingan oleh relawan.
                        </p>

                        <ul class="text-secondary small ps-3 mb-4">
                            <li>Identifikasi kekurangan</li>
                            <li>Monitoring checklist</li>
                            <li>Pembuatan tugas</li>
                            <li>Penugasan relawan</li>
                            <li>Pemantauan status tugas</li>
                        </ul>

                        <a href="login.php"
                           class="text-warning fw-semibold text-decoration-none">
                            Masuk ke Sistem &rarr;
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- ================= HOW IT WORKS ================= -->

<section class="py-5 bg-body-tertiary">

    <div class="container py-4">

        <div class="text-center mb-5">

            <span class="text-primary fw-semibold small text-uppercase">
                Alur Kesiapsiagaan
            </span>

            <h2 class="fw-bold mt-2">
                Mulai dari Langkah Sederhana
            </h2>

            <p class="text-secondary">
                Setiap peran memiliki kontribusi dalam membangun
                kesiapsiagaan lingkungan.
            </p>

        </div>


        <div class="row g-4">

            <div class="col-md-4 text-center">

                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                     style="width: 65px; height: 65px; font-size: 25px; font-weight: bold;">
                    01
                </div>

                <h5 class="fw-bold">
                    Warga Mendata Keluarga
                </h5>

                <p class="text-secondary small">
                    Warga mengisi Kartu Siaga Keluarga,
                    memperoleh kode keluarga, dan melengkapi
                    checklist kesiapsiagaan.
                </p>

            </div>


            <div class="col-md-4 text-center">

                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                     style="width: 65px; height: 65px; font-size: 25px; font-weight: bold;">
                    02
                </div>

                <h5 class="fw-bold">
                    Koordinator Mengelola
                </h5>

                <p class="text-secondary small">
                    Koordinator memantau kekurangan kesiapsiagaan,
                    mengelola rencana evakuasi, dan membuat
                    tugas pendampingan.
                </p>

            </div>


            <div class="col-md-4 text-center">

                <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                     style="width: 65px; height: 65px; font-size: 25px; font-weight: bold;">
                    03
                </div>

                <h5 class="fw-bold">
                    Relawan Mendampingi
                </h5>

                <p class="text-secondary small">
                    Relawan melaksanakan tugas pendampingan
                    dan memperbarui status penyelesaian
                    tugas keluarga.
                </p>

            </div>

        </div>

    </div>

</section>


<!-- ================= CALL TO ACTION ================= -->

<section class="py-5">

    <div class="container py-4">

        <div class="bg-primary text-white rounded-4 p-4 p-lg-5 text-center">

            <h2 class="fw-bold mb-3">
                Mari Bangun Kampung yang Lebih Siaga
            </h2>

            <p class="mb-4 opacity-75">
                Mulai dari keluarga, bergerak bersama komunitas,
                dan tingkatkan kesiapsiagaan lingkungan.
            </p>

            <a href="family-form.php"
               class="btn btn-light btn-lg text-primary fw-semibold rounded-pill px-4">
                Mulai Pendataan Keluarga &rarr;
            </a>

        </div>

    </div>

</section>


<!-- ================= FOOTER ================= -->

<footer class="bg-dark text-white pt-5 pb-4">

    <div class="container">

        <div class="row g-4">

            <div class="col-lg-5">

                <h5 class="fw-bold">
                    SIAGA<span class="text-info">KAMPUNG</span>
                </h5>

                <p class="text-white-50 small mt-3"
                   style="max-width: 400px;">
                    Platform kesiapsiagaan bencana berbasis komunitas
                    untuk membantu keluarga dan lingkungan
                    membangun kesiapan menghadapi bencana.
                </p>

            </div>


            <div class="col-6 col-lg-3">

                <h6 class="fw-bold mb-3">
                    Navigasi
                </h6>

                <ul class="list-unstyled small">

                    <li class="mb-2">
                        <a href="index.php"
                           class="text-white-50 text-decoration-none">
                            Beranda
                        </a>
                    </li>

                    <li class="mb-2">
                        <a href="family-form.php"
                           class="text-white-50 text-decoration-none">
                            Kartu Siaga
                        </a>
                    </li>

                    <li class="mb-2">
                        <a href="evacuation-plan.php"
                           class="text-white-50 text-decoration-none">
                            Rencana Evakuasi
                        </a>
                    </li>

                </ul>

            </div>


            <div class="col-6 col-lg-4">

                <h6 class="fw-bold mb-3">
                    Akses Pengguna
                </h6>

                <ul class="list-unstyled small">

                    <li class="mb-2">
                        <a href="login.php"
                           class="text-white-50 text-decoration-none">
                            Login Koordinator
                        </a>
                    </li>

                    <li class="mb-2">
                        <a href="login.php"
                           class="text-white-50 text-decoration-none">
                            Login Relawan
                        </a>
                    </li>

                </ul>

            </div>

        </div>


        <hr class="border-secondary my-4">

        <div class="d-flex flex-column flex-md-row justify-content-between gap-2">

            <p class="small text-white-50 mb-0">
                &copy; 2026 SIAGAKAMPUNG. All Rights Reserved.
            </p>

            <p class="small text-white-50 mb-0">
                Satu Kampung, Satu Rencana Kesiapsiagaan.
            </p>

        </div>

    </div>

</footer>


<!-- Bootstrap 5 JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
