# Microsite Dinamis (Linktree Clone)

Sebuah aplikasi web *microsite* dinamis yang berfungsi mirip seperti Linktree. Dilengkapi dengan panel admin untuk mengelola profil, pengaturan tema, dan daftar tautan secara real-time tanpa perlu me-reload halaman.

## 🚀 Fitur Utama

### 1. Halaman Publik (Frontend)
*   **Desain Modern & Responsif**: Menggunakan Tailwind CSS untuk tampilan yang bersih, estetik, dan responsif di berbagai perangkat (Mobile, Tablet, Desktop).
*   **Profil Pengguna**: Menampilkan foto profil (avatar), nama pengguna, dan bio singkat.
*   **Daftar Tautan**: Menampilkan tombol-tombol tautan yang dilengkapi dengan efek *hover* mikro-animasi yang mulus.
*   **Ikon Otomatis**: Ikon sosial media (seperti Instagram, TikTok, WhatsApp, GitHub, YouTube, dll) akan otomatis muncul berdasarkan pencocokan nama label tautan.

### 2. Panel Admin (Backend)
*   **Autentikasi Terpusat**: Login admin yang dilindungi dengan *hashing* password.
*   **Manajemen Tautan (CRUD)**:
    *   Tambah tombol tautan baru dengan mudah.
    *   Edit informasi label dan URL.
    *   Hapus tombol yang sudah tidak relevan.
    *   Tombol *Toggle* untuk menyembunyikan/menampilkan tombol tanpa menghapusnya.
    *   Fungsi *Drag and Drop* untuk mengubah susunan/urutan tombol di halaman publik secara praktis.
*   **Manajemen Pengaturan Profil & Tema**:
    *   Mengubah Nama Profil, Bio, dan URL Gambar Avatar.
    *   Memilih variasi gradasi tema latar belakang (*Midnight Indigo, Emerald Forest, Sunset Purple, Dark Rose*).
*   **Pengaturan Akun Admin**:
    *   Fitur penggantian Username dan Password secara aman langsung dari *dashboard*.
*   **Interaksi AJAX & Toast**: Semua aksi pembaruan (simpan, edit, hapus, ubah urutan) berjalan secara *asynchronous* (tanpa memuat ulang halaman) dan menyajikan notifikasi aksi berupa *Toast*.

## 🛠️ Teknologi yang Digunakan

*   **Backend**: PHP Native (8.x direkomendasikan)
*   **Database**: MySQL / MariaDB
*   **Frontend**: HTML5, Tailwind CSS (via CDN), Font Google (Inter)
*   **Library Klien**: SortableJS (untuk fitur *drag & drop* penyusunan urutan)

## 📁 Struktur Direktori

```text
/microsite
├── admin/
│   ├── actions.php      # Pemroses backend untuk seluruh AJAX request
│   ├── dashboard.php    # Halaman User Interface (UI) utama panel admin
│   ├── index.php        # Redirect pintar ke halaman login atau dashboard
│   └── login.php        # Halaman autentikasi untuk admin
├── inc/
│   ├── auth.php         # Kumpulan fungsi bantuan autentikasi dan CSRF
│   └── db.php           # Konfigurasi koneksi ke database MySQL menggunakan PDO
├── .gitignore           # Konfigurasi mengabaikan file tertentu dari pelacakan Git
├── index.php            # Halaman landing page publik aplikasi microsite
└── setup.sql            # Skrip DDL/DML untuk instalasi struktur dan data tabel
```

## ⚙️ Panduan Instalasi (Server Lokal / Laragon / XAMPP)

1. **Persiapan File**
   *   *Clone* atau unduh *repository* ini.
   *   Pindahkan seluruh folder proyek ke dalam direktori publik server lokal Anda (Contoh di Laragon: `C:\laragon\www\microsite`, atau di XAMPP: `C:\xampp\htdocs\microsite`).

2. **Setup Database**
   *   Buka aplikasi manajer database Anda (HeidiSQL, phpMyAdmin, dll).
   *   Import atau eksekusi *script* `setup.sql` yang telah disediakan. Skrip tersebut akan otomatis membuat database `microsite` (jika belum ada), membuat struktur tabel (`users`, `buttons`, `settings`), dan memasukkan sampel data awal.

3. **Konfigurasi Koneksi**
   *   Buka file `inc/db.php` pada editor kode Anda.
   *   Sesuaikan konfigurasi koneksi server database Anda, terutama variabel kredensial (secara bawaan, konfigurasi ini disesuaikan untuk Laragon dengan `user = root` dan tanpa password):
       ```php
       $db_host = 'localhost';
       $db_user = 'root';
       $db_pass = '';
       $db_name = 'microsite';
       ```

4. **Menjalankan Aplikasi**
   *   **Halaman Publik**: Buka browser dan arahkan ke alamat lokal Anda, misal: `http://localhost/microsite` (atau `http://microsite.test` jika menggunakan sistem auto-virtual host Laragon).
   *   **Halaman Admin**: Kunjungi `http://localhost/microsite/admin`.

## 🔐 Kredensial Default Admin

Setelah proses instalasi database berhasil, Anda dapat melakukan login ke dalam Panel Admin menggunakan akun bawaan berikut:

*   **Username**: `admin`
*   **Password**: `admin123`

*(Catatan: Sangat disarankan untuk segera mengganti kredensial bawaan ini di menu "Pengaturan Akun Admin" setelah berhasil masuk ke dalam dashboard).*

## 🛡️ Praktik Keamanan yang Diterapkan

Proyek ini telah dibekali perlindungan standar industri:
*   **PDO Prepared Statements**: Pengamanan kueri database untuk memitigasi serangan SQL Injection.
*   **CSRF Tokens**: Token acak yang diimplementasikan untuk setiap *submit* form dan AJAX guna mencegah eksploitasi *Cross-Site Request Forgery*.
*   **Password Hashing**: Mengamankan kata sandi di level database menggunakan algoritma BCRYPT (`password_hash` dari PHP).
*   **Sesi Terproteksi**: Implementasi *HttpOnly* untuk meminimalisasi kerentanan penyadapan sesi melalui *Cross-Site Scripting* (XSS).
