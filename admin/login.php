<?php
// Include database and auth helpers
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/auth.php';

// Jika sudah login, langsung alihkan ke dashboard
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';

// Proses form login jika dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validasi CSRF Token
    if (!verify_csrf_token($csrf_token)) {
        $error_message = 'Token keamanan tidak valid. Silakan coba lagi.';
    } elseif (empty($username) || empty($password)) {
        $error_message = 'Username dan password wajib diisi.';
    } else {
        try {
            // Cari user berdasarkan username menggunakan prepared statement
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            // Verifikasi password jika user ditemukan
            if ($user && password_verify($password, $user['password'])) {
                // Set session data
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                
                // Regenerate session ID untuk keamanan dari session fixation
                session_regenerate_id(true);
                
                // Redirect ke dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $error_message = 'Username atau password salah.';
            }
        } catch (PDOException $e) {
            $error_message = 'Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.';
        }
    }
}

// Generate CSRF Token untuk form
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Microsite</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-md">
        <!-- Card Container dengan efek Glassmorphism -->
        <div class="bg-slate-900/60 backdrop-blur-xl border border-slate-800/80 rounded-2xl shadow-2xl p-8 transition-all duration-300 hover:shadow-indigo-500/10">
            
            <!-- Logo / Header -->
            <div class="text-center mb-8">
                <div class="inline-flex p-3 bg-indigo-500/10 rounded-xl text-indigo-400 mb-3 border border-indigo-500/20">
                    <!-- Icon Lock -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Admin Panel Login</h1>
                <p class="text-slate-400 text-sm mt-1">Masuk untuk mengelola microsite Anda</p>
            </div>

            <!-- Pesan Error -->
            <?php if (!empty($error_message)): ?>
                <div class="mb-6 p-4 bg-red-500/10 border border-red-500/30 rounded-xl flex items-start space-x-3 text-red-400 animate-fade-in-down">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 mt-0.5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <span class="text-sm font-medium"><?= htmlspecialchars($error_message) ?></span>
                </div>
            <?php endif; ?>

            <!-- Form Login -->
            <form action="login.php" method="POST" class="space-y-5">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <!-- Input Username -->
                <div>
                    <label for="username" class="block text-slate-300 text-xs font-semibold uppercase tracking-wider mb-2">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </div>
                        <input type="text" id="username" name="username" required 
                               class="block w-full pl-10 pr-4 py-3 bg-slate-950/50 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm" 
                               placeholder="Masukkan username admin"
                               value="<?= isset($username) ? htmlspecialchars($username) : '' ?>">
                    </div>
                </div>

                <!-- Input Password -->
                <div>
                    <label for="password" class="block text-slate-300 text-xs font-semibold uppercase tracking-wider mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        </div>
                        <input type="password" id="password" name="password" required 
                               class="block w-full pl-10 pr-4 py-3 bg-slate-950/50 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm" 
                               placeholder="Masukkan password admin">
                    </div>
                </div>

                <!-- Button Submit -->
                <button type="submit" 
                        class="w-full py-3 px-4 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 active:scale-[0.98] text-white font-medium rounded-xl shadow-lg shadow-indigo-600/20 hover:shadow-indigo-600/30 transition-all text-sm flex justify-center items-center space-x-2">
                    <span>Masuk Ke Dashboard</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </button>
            </form>
            
            <!-- Footer Link -->
            <div class="mt-6 text-center">
                <a href="../index.php" class="text-xs text-indigo-400 hover:text-indigo-300 font-medium inline-flex items-center space-x-1 hover:underline transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    <span>Kembali ke Halaman Utama</span>
                </a>
            </div>
        </div>
    </div>

</body>
</html>
