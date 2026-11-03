# SIAGAKAMPUNG

Aplikasi MVP kesiapsiagaan bencana berbasis komunitas menggunakan PHP Native, MySQLi, MySQL/MariaDB, Bootstrap 5, dan JavaScript.

## 1. Persyaratan

- XAMPP dengan Apache dan MySQL/MariaDB aktif.
- PHP 8.1 atau lebih baru.
- Ekstensi PHP `mysqli` aktif.
- Browser modern.
- Koneksi internet untuk memuat Bootstrap 5 CDN.

## 2. Instalasi di XAMPP

1. Ekstrak folder `siagakampung` ke `C:/xampp/htdocs/`.
2. Jalankan Apache dan MySQL melalui XAMPP Control Panel.
3. Buka `http://localhost/phpmyadmin/`.
4. Pilih menu **Import** dan impor file `database/siagakampung.sql`.
5. Pastikan database `siagakampung_db` berhasil dibuat.
6. Periksa `includes/config.php`. Konfigurasi bawaan untuk XAMPP lokal adalah:
   - Host: `localhost`
   - User: `root`
   - Password: kosong
   - Database: `siagakampung_db`
   - Port: `3306`
7. Buka `http://localhost/siagakampung/`.

## 3. Akun demo

Semua akun demo menggunakan password `SiagaDemo2026!`.

| Role | Email |
|---|---|
| Admin | `admin@siagakampung.test` |
| Coordinator | `koordinator@siagakampung.test` |
| Volunteer | `relawan@siagakampung.test` |

Akun dan data bawaan hanya untuk simulasi lokal. Jangan gunakan kredensial demo untuk deployment publik.

## 4. Alur pengujian manual

1. Buka **Daftarkan Keluarga**, isi form, lalu simpan. Sistem harus membuat `family_code` dan checklist awal.
2. Buka kartu keluarga menggunakan kode yang dihasilkan. Ubah indikator checklist dan simpan.
3. Login sebagai Coordinator. Buat rencana evakuasi. Rencana baru harus berstatus `draft`.
4. Logout, lalu login sebagai Admin. Periksa rencana draft dan verifikasi hanya setelah informasi benar-benar diperiksa serta disahkan pihak berwenang.
5. Login sebagai Coordinator atau Admin. Buat tugas, pilih keluarga dan akun Volunteer.
6. Login sebagai Volunteer. Pastikan hanya tugas yang ditugaskan kepada akun tersebut yang terlihat. Ubah status tugas.
7. Periksa `dashboard.php` dan cocokkan statistik dengan data MySQL.

## 5. Data simulasi

Database menyertakan satu keluarga demo dengan kode `SK-2026-00125`, satu rencana evakuasi berstatus `draft`, dan satu tugas demo. Rencana dan rute demo bukan panduan evakuasi nyata. Jangan mempublikasikannya sebagai instruksi keselamatan.

## 6. Catatan audit

- File PHP telah diperiksa menggunakan `php -l`.
- File JavaScript eksternal dan skrip inline telah diperiksa menggunakan `node --check`.
- Kontrak form utama dan keberadaan endpoint action telah diperiksa secara statis.
- Pengujian transaksi dan query terhadap server MySQL/MariaDB harus dilakukan langsung di XAMPP.
- Implementasi ini merupakan MVP hackathon lokal, bukan aplikasi produksi. Sebelum deployment publik, lakukan hardening keamanan menyeluruh, termasuk perlindungan CSRF konsisten untuk seluruh form yang mengubah data, pembatasan percobaan login, kebijakan akses kartu keluarga, HTTPS, dan pengelolaan kredensial.
