<?php
// Mencegah akses langsung ke file ini
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Akses ditolak.');
}

// Mulai session dengan konfigurasi keamanan tambahan jika memungkinkan
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'use_only_cookies' => true,
        // 'cookie_secure' => isset($_SERVER['HTTPS']), // Aktifkan jika menggunakan HTTPS
    ]);
}

/**
 * Memeriksa apakah user sudah login sebagai admin
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Mewajibkan user untuk login. Jika tidak, akan diredirect ke halaman login.
 */
function require_login() {
    if (!is_logged_in()) {
        // Redirect ke halaman login admin
        header('Location: login.php');
        exit;
    }
}

/**
 * Membuat token CSRF baru jika belum ada di session
 * @return string
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Memvalidasi token CSRF
 * @param string $token
 * @return bool
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
