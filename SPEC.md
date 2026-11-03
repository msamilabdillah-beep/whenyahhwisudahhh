# SPEC.md — SIAGAKAMPUNG

**Product:** Community Disaster Readiness Platform
**Tagline:** Satu Kampung, Satu Rencana Kesiapsiagaan.
**Stack:** HTML5, CSS3, JavaScript, Bootstrap 5 CDN, PHP Native, MySQL, XAMPP.
**Duration:** 8 Jam
**Method:** FRONTEND FIRST

---

# 1. PRODUCT OVERVIEW

SIAGAKAMPUNG adalah website pengelolaan kesiapsiagaan bencana berbasis komunitas untuk membantu keluarga dan koordinator lingkungan mendata kesiapsiagaan, mengelola rencana evakuasi, serta menindaklanjuti kekurangan kesiapsiagaan masyarakat.

**Masalah utama:**

1. Data kesiapsiagaan keluarga belum terdokumentasi secara terpusat.
2. Informasi rencana evakuasi belum mudah diakses warga.
3. Kekurangan kesiapsiagaan belum dikelola menjadi tindakan bersama.

**Target pengguna:**

* Warga/keluarga.
* Koordinator RT/RW.
* Relawan.
* Admin.

**Tujuan:** Menghubungkan pendataan, perencanaan, dan tindakan kesiapsiagaan dalam satu website.

---

# 2. THREE CORE FEATURES

## Fitur 1 — Kartu Siaga Keluarga

**Fungsi:** Mendata kesiapsiagaan setiap keluarga.

Input:

* Nama keluarga.
* Wilayah/RT.
* Jumlah anggota.
* Nomor kontak darurat.
* Status tas siaga.
* Status kontak darurat.
* Status P3K.
* Status dokumen penting.
* Status titik kumpul keluarga.

Proses:

1. Warga mengisi formulir.
2. Sistem membuat kode keluarga otomatis.
3. Data disimpan ke database.
4. Sistem menghitung kelengkapan checklist.

Output:

* Kode keluarga.
* Kartu kesiapsiagaan.
* Status setiap checklist.
* Persentase kelengkapan.

Rumus: (Checklist lengkap / 5) × 100%.

Persentase hanya menunjukkan kelengkapan checklist, bukan jaminan keselamatan.

## Fitur 2 — Rencana Evakuasi Komunitas

**Fungsi:** Mengelola dan menampilkan informasi rencana evakuasi lingkungan.

Input:

* Jenis ancaman.
* Wilayah.
* Jalur utama.
* Jalur alternatif.
* Titik kumpul.
* Kontak koordinator.
* Status validasi.

Proses:

1. Koordinator mengisi rencana.
2. Sistem menyimpan data.
3. Rencana divalidasi oleh pihak berwenang yang berhak.
4. Warga dapat melihat rencana yang sudah diverifikasi.

Output:

* Informasi ancaman.
* Jalur utama dan alternatif.
* Titik kumpul.
* Kontak koordinator.
* Status validasi.

Data simulasi wajib diberi label simulasi. Rencana draft tidak boleh ditampilkan sebagai panduan tervalidasi.

## Fitur 3 — Readiness Gap Radar

**Fungsi:** Mengidentifikasi kekurangan checklist keluarga dan mengubahnya menjadi tugas pendampingan relawan.

Proses:

1. Sistem membaca checklist keluarga.
2. Menghitung kekurangan setiap indikator.
3. Menampilkan hasil pada dashboard.
4. Koordinator membuat tugas pendampingan.
5. Koordinator menetapkan relawan.
6. Relawan memperbarui status tugas.
7. Dashboard diperbarui berdasarkan database.

Output:

* Total keluarga.
* Checklist lengkap dan belum lengkap.
* Kekurangan setiap indikator.
* Daftar tugas relawan.
* Status tugas.

**Inovasi utama: GAP-TO-ACTION SYSTEM:** Mengubah data kekurangan kesiapsiagaan menjadi tindakan pendampingan nyata oleh relawan.

---

# 3. USER FLOW

**Warga:**
Membuka website → Mengisi Kartu Siaga → Mendapat kode keluarga → Melihat dan memperbarui checklist → Melihat rencana evakuasi.

**Koordinator:**
Login → Dashboard → Melihat Readiness Gap → Mengelola rencana evakuasi → Membuat tugas → Menetapkan relawan → Memantau hasil.

**Relawan:**
Login → Melihat tugas → Melaksanakan pendampingan → Memperbarui status tugas.

---

# 4. PAGE STRUCTURE

| File                | Fungsi                             |
| ------------------- | ---------------------------------- |
| index.php           | Landing page                       |
| login.php           | Login pengguna                     |
| family-form.php     | Form data keluarga                 |
| family-card.php     | Kartu dan checklist keluarga       |
| evacuation-plan.php | Informasi dan pengelolaan evakuasi |
| dashboard.php       | Dashboard Readiness Gap            |
| community-tasks.php | Pengelolaan tugas relawan          |

**Hak akses:**

* Warga: mengisi dan melihat data keluarga serta rencana evakuasi.
* Koordinator: mengelola rencana, dashboard, dan tugas.
* Relawan: melihat dan memperbarui tugas miliknya.
* Admin: mengelola sistem.

MVP menggunakan formulir keluarga tanpa akun. Login difokuskan pada koordinator, relawan, dan admin.

---

# 5. FOLDER STRUCTURE

Lokasi: `C:/xampp/htdocs/siagakampung/`

```text
siagakampung/
├── index.php
├── login.php
├── family-form.php
├── family-card.php
├── evacuation-plan.php
├── dashboard.php
├── community-tasks.php
│
├── assets/
│   ├── css/style.css
│   ├── js/script.js
│   └── images/
│
├── includes/
│   ├── config.php
│   ├── auth.php
│   ├── header.php
│   └── footer.php
│
├── actions/
│   ├── login_process.php
│   ├── logout.php
│   ├── family_save.php
│   ├── checklist_save.php
│   ├── evacuation_save.php
│   ├── task_save.php
│   └── task_status.php
│
├── database/
│   └── siagakampung.sql
│
└── README.md
```

---

# 6. FILE RESPONSIBILITIES

**FRONTEND DEVELOPER**

Mengerjakan seluruh tampilan terlebih dahulu:

* 7 halaman PHP.
* assets/css/style.css.
* assets/js/script.js.
* assets/images/.

Ketentuan:

* Gunakan Bootstrap 5 CDN.
* Gunakan data dummy.
* Semua form harus mengikuti kontrak Bagian 8.
* Semua halaman responsif.
* Tidak membuat query database.

**BACKEND DEVELOPER**

Mengerjakan:

* includes/config.php: koneksi database.
* includes/auth.php: session dan hak akses.
* includes/header.php dan footer.php: komponen bersama.
* Seluruh file dalam actions/: proses PHP.
* database/siagakampung.sql: struktur dan data awal database.

Backend bertanggung jawab atas validasi, autentikasi, database, penyimpanan, dan pengambilan data.

**PROJECT LEADER**

Mengunci spesifikasi, menyiapkan folder, mengawasi kontrak, mengintegrasikan frontend-backend, testing, dan demo.

---

# 7. DATABASE SCHEMA

**Database:** `siagakampung_db`
**Engine:** InnoDB
**Charset:** utf8mb4

### Tabel 1 — users

| Kolom      | Tipe                                    | Keterangan                |
| ---------- | --------------------------------------- | ------------------------- |
| id         | INT                                     | PK, AUTO_INCREMENT        |
| name       | VARCHAR(100)                            | NOT NULL                  |
| email      | VARCHAR(100)                            | UNIQUE                    |
| password   | VARCHAR(255)                            | NOT NULL                  |
| role       | ENUM('admin','coordinator','volunteer') | NOT NULL                  |
| created_at | TIMESTAMP                               | DEFAULT CURRENT_TIMESTAMP |

### Tabel 2 — families

| Kolom             | Tipe         | Keterangan                |
| ----------------- | ------------ | ------------------------- |
| id                | INT          | PK, AUTO_INCREMENT        |
| family_code       | VARCHAR(20)  | UNIQUE                    |
| family_name       | VARCHAR(100) | NOT NULL                  |
| neighborhood      | VARCHAR(50)  | NOT NULL                  |
| member_count      | INT          | NOT NULL                  |
| emergency_contact | VARCHAR(25)  | NOT NULL                  |
| created_at        | TIMESTAMP    | DEFAULT CURRENT_TIMESTAMP |

### Tabel 3 — readiness_checklists

| Kolom               | Tipe       | Keterangan         |
| ------------------- | ---------- | ------------------ |
| id                  | INT        | PK, AUTO_INCREMENT |
| family_id           | INT        | FK, UNIQUE         |
| emergency_bag       | TINYINT(1) | DEFAULT 0          |
| emergency_contacts  | TINYINT(1) | DEFAULT 0          |
| first_aid_kit       | TINYINT(1) | DEFAULT 0          |
| important_documents | TINYINT(1) | DEFAULT 0          |
| assembly_point      | TINYINT(1) | DEFAULT 0          |
| updated_at          | TIMESTAMP  | Auto update        |

### Tabel 4 — evacuation_plans

| Kolom               | Tipe                     | Keterangan                |
| ------------------- | ------------------------ | ------------------------- |
| id                  | INT                      | PK, AUTO_INCREMENT        |
| neighborhood        | VARCHAR(50)              | NOT NULL                  |
| hazard_type         | VARCHAR(50)              | NOT NULL                  |
| primary_route       | TEXT                     | NOT NULL                  |
| alternative_route   | TEXT                     | NOT NULL                  |
| assembly_point      | VARCHAR(150)             | NOT NULL                  |
| coordinator_contact | VARCHAR(25)              | NOT NULL                  |
| validation_status   | ENUM('draft','verified') | DEFAULT 'draft'           |
| verified_at         | DATETIME                 | NULL                      |
| created_by          | INT                      | FK users.id               |
| created_at          | TIMESTAMP                | DEFAULT CURRENT_TIMESTAMP |

### Tabel 5 — community_tasks

| Kolom            | Tipe                                      | Keterangan                |
| ---------------- | ----------------------------------------- | ------------------------- |
| id               | INT                                       | PK, AUTO_INCREMENT        |
| family_id        | INT                                       | FK families.id            |
| assigned_to      | INT                                       | FK users.id               |
| task_title       | VARCHAR(150)                              | NOT NULL                  |
| task_description | TEXT                                      | NULL                      |
| status           | ENUM('pending','in_progress','completed') | DEFAULT 'pending'         |
| created_at       | TIMESTAMP                                 | DEFAULT CURRENT_TIMESTAMP |
| completed_at     | DATETIME                                  | NULL                      |

### Relasi

* families 1 : 1 readiness_checklists.
* families 1 : N community_tasks.
* users 1 : N community_tasks.
* users 1 : N evacuation_plans.

Gunakan foreign key sesuai relasi, password_hash(), dan prepared statements. Data kontak keluarga tidak boleh ditampilkan secara publik.

---

# 8. FRONTEND-BACKEND CONTRACT

**Kontrak ini wajib diikuti kedua programmer. Jangan mengganti nama input, action, method, atau tabel tanpa persetujuan leader.**

| Form           | Action                      | Method | Input name                                                                                         | Database                       |
| -------------- | --------------------------- | ------ | -------------------------------------------------------------------------------------------------- | ------------------------------ |
| loginForm      | actions/login_process.php   | POST   | email, password                                                                                    | users                          |
| familyForm     | actions/family_save.php     | POST   | family_name, neighborhood, member_count, emergency_contact                                         | families, readiness_checklists |
| checklistForm  | actions/checklist_save.php  | POST   | family_code, emergency_bag, emergency_contacts, first_aid_kit, important_documents, assembly_point | readiness_checklists           |
| evacuationForm | actions/evacuation_save.php | POST   | neighborhood, hazard_type, primary_route, alternative_route, assembly_point, coordinator_contact   | evacuation_plans               |
| taskForm       | actions/task_save.php       | POST   | family_id, assigned_to, task_title, task_description                                               | community_tasks                |
| taskStatusForm | actions/task_status.php     | POST   | task_id, status                                                                                    | community_tasks                |

### Backend Processing

**Login**

* Verifikasi email dan password.
* Gunakan password_verify().
* Buat session dan arahkan berdasarkan role.

**Family**

* Validasi data.
* Buat family_code otomatis.
* Simpan keluarga dan checklist awal (semua 0).

**Checklist**

* Cari keluarga melalui family_code.
* Update checklist.
* Checkbox tidak dicentang diproses sebagai 0.
* Hitung persentase kelengkapan.

**Evacuation**

* Hanya koordinator/admin yang dapat mengelola.
* Simpan rencana dengan status draft.
* Status verified hanya ditetapkan melalui proses validasi yang berwenang.

**Community Task**

* Hanya koordinator/admin yang membuat tugas.
* Pastikan keluarga tersedia dan penerima adalah relawan.
* Status awal pending.

**Task Status**

* Hanya relawan penerima tugas atau koordinator/admin yang boleh mengubah status.
* Status: pending, in_progress, completed.
* completed_at diisi ketika tugas selesai.

### Dashboard Data

dashboard.php harus membaca langsung dari MySQL:

* Total keluarga.
* Total checklist lengkap.
* Total checklist belum lengkap.
* Jumlah kekurangan setiap indikator.
* Total tugas pending.
* Total tugas completed.

Dashboard tidak boleh menggunakan angka dummy setelah integrasi backend selesai.

---

# 9. DEVELOPMENT ORDER — FRONTEND FIRST

**Tahap 1 — Project Leader**

1. Kunci SPEC.md.
2. Buat folder project.
3. Tetapkan database dan kontrak.
4. Serahkan spesifikasi kepada frontend.

**Tahap 2 — Frontend**

1. index.php
2. login.php
3. family-form.php
4. family-card.php
5. evacuation-plan.php
6. dashboard.php
7. community-tasks.php
8. style.css
9. script.js

Selesaikan seluruh tampilan dengan data dummy sebelum diserahkan kepada backend.

**Tahap 3 — Backend**

1. Baca SPEC.md dan seluruh frontend aktual.
2. Buat database dan tabel.
3. Buat config dan autentikasi.
4. Buat seluruh proses form.
5. Hubungkan dashboard dengan database.
6. Ganti data dummy dengan data asli.

**Tahap 4 — Leader**

1. Integrasikan seluruh file.
2. Jalankan melalui XAMPP.
3. Uji fitur dan hak akses.
4. Perbaiki error.
5. Siapkan data simulasi dan demo.

**Dilarang:** Pergantian pengerjaan frontend-backend per fitur. Seluruh frontend harus selesai dahulu.

---

# 10. TESTING REQUIREMENTS

| Area       | Pengujian                                         |
| ---------- | ------------------------------------------------- |
| Login      | Akun benar, password salah, logout, session       |
| Family     | Simpan keluarga, kode unik, checklist, persentase |
| Evacuation | Simpan, validasi, tampilkan rencana               |
| Dashboard  | Angka sesuai database dan berubah otomatis        |
| Tasks      | Buat tugas, penugasan relawan, update status      |
| Security   | Role access, validasi input, prepared statements  |
| UI         | Responsif, navigasi, tidak ada error PHP/JS       |

### MVP DEFINITION OF DONE

* Semua halaman tampil.
* Seluruh form berfungsi.
* Database terhubung.
* Ketiga fitur utama berjalan.
* Dashboard menggunakan data database.
* Relawan dapat memperbarui tugas.
* Hak akses berjalan.
* Website dapat diuji melalui XAMPP.

---

# FINAL RULES

1. SPEC.md adalah acuan utama seluruh tim.
2. Maksimal tiga fitur utama, tidak menambah fitur baru.
3. Jangan mengubah nama tabel, kolom, file, action, atau input tanpa persetujuan leader.
4. Frontend harus selesai terlebih dahulu.
5. Backend wajib mengikuti frontend aktual.
6. Leader bertanggung jawab atas integrasi dan testing.
7. Prioritaskan fungsi daripada dekorasi.
8. Semua data demo harus berlabel simulasi.
9. Target MVP selesai dalam 8 jam.

**SIAGAKAMPUNG — Satu Kampung, Satu Rencana Kesiapsiagaan.**

**END OF SPEC.md**
