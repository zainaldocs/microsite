<?php
// Mencegah akses langsung ke file ini
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Akses ditolak.');
}

// Konfigurasi Database (Sesuaikan dengan setelan Laragon Anda jika berbeda)
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'microsite';

try {
    // Membuat koneksi PDO
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // Tampilkan pesan error yang ramah jika gagal terhubung
    die("Koneksi database gagal: " . $e->getMessage() . "<br><br><strong>Petunjuk:</strong> Pastikan MySQL di Laragon sudah aktif dan file <code>setup.sql</code> telah diimport.");
}
