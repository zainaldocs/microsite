<?php
// Include auth helper
require_once __DIR__ . '/../inc/auth.php';

// Jika sudah login, arahkan ke dashboard.php
// Jika belum, arahkan ke login.php
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
} else {
    header('Location: login.php');
    exit;
}
