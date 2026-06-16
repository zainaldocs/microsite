<?php
// Include database and auth helpers
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/auth.php';

// Proteksi halaman: Harus login
require_login();

// Proses Logout
if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header('Location: login.php');
    exit;
}

// Ambil semua tombol, diurutkan berdasarkan order_index
$buttons = [];
try {
    $stmt = $pdo->query('SELECT * FROM buttons ORDER BY order_index ASC');
    $buttons = $stmt->fetchAll();
} catch (PDOException $e) {
    // Penanganan error database
}

// Ambil pengaturan profil & tema
$settings = [
    'profile_name' => '',
    'profile_bio' => '',
    'profile_avatar' => '',
    'theme_gradient' => 'from-slate-900 via-indigo-950 to-slate-900'
];
try {
    $stmt = $pdo->query('SELECT * FROM settings');
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Penanganan error database
}

// Ambil username admin saat ini
$admin_username = '';
if (isset($_SESSION['admin_id'])) {
    try {
        $stmt = $pdo->prepare('SELECT username FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['admin_id']]);
        $user_row = $stmt->fetch();
        if ($user_row) {
            $admin_username = $user_row['username'];
        }
    } catch (PDOException $e) {
        $admin_username = $_SESSION['admin_username'] ?? 'admin';
    }
} else {
    $admin_username = $_SESSION['admin_username'] ?? 'admin';
}

// Generate CSRF Token untuk request AJAX
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Microsite</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- SortableJS CDN untuk Drag & Drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .sortable-ghost {
            opacity: 0.4;
            background-color: rgb(79, 70, 229, 0.1);
            border: 2px dashed rgb(99, 102, 241);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">

    <!-- Top Navigation Bar -->
    <nav class="sticky top-0 z-40 bg-slate-900/80 backdrop-blur-md border-b border-slate-800 px-6 py-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <span class="p-2 bg-indigo-600/10 rounded-lg text-indigo-400 border border-indigo-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                    </svg>
                </span>
                <span class="text-lg font-bold text-white tracking-wide">Microsite Admin</span>
            </div>
            
            <div class="flex items-center space-x-4">
                <a href="../index.php" target="_blank" 
                   class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-lg border border-slate-700 transition-all inline-flex items-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <span>Lihat Halaman Publik</span>
                </a>
                
                <a href="dashboard.php?logout=1" 
                   class="px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-xs font-semibold rounded-lg border border-red-500/20 transition-all inline-flex items-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                    </svg>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main class="max-w-7xl mx-auto px-6 py-8">
        
        <!-- Welcome banner -->
        <div class="mb-8 p-6 bg-gradient-to-r from-indigo-900/40 via-purple-900/30 to-indigo-900/10 border border-indigo-500/10 rounded-2xl">
            <h2 class="text-2xl font-bold text-white">Halo, Admin! 👋</h2>
            <p class="text-slate-400 text-sm mt-1">Kelola tautan microsite Anda dan sesuaikan tampilannya agar terlihat profesional.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left & Middle: Button List (Col span 2) -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-slate-900/40 border border-slate-800 rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-white">Daftar Tombol Tautan</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Tarik dan letakkan (drag & drop) tombol untuk mengubah urutan di halaman utama.</p>
                        </div>
                        <button onclick="openAddModal()" 
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 active:scale-[0.98] text-white text-xs font-semibold rounded-xl shadow-lg shadow-indigo-600/20 transition-all inline-flex items-center space-x-1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            <span>Tambah Tombol</span>
                        </button>
                    </div>

                    <!-- Sortable Button List -->
                    <div id="button-list" class="space-y-3">
                        <?php if (empty($buttons)): ?>
                            <div id="empty-state" class="text-center py-12 border-2 border-dashed border-slate-800 rounded-xl">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-slate-600 mx-auto mb-3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                                </svg>
                                <p class="text-slate-400 text-sm">Belum ada tombol tautan. Klik tombol di atas untuk membuat.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($buttons as $button): ?>
                                <div data-id="<?= $button['id'] ?>" 
                                     class="flex items-center justify-between p-4 bg-slate-900 border border-slate-800 rounded-xl hover:border-slate-700 transition-all group">
                                    
                                    <!-- Left: Drag handle & info -->
                                    <div class="flex items-center space-x-3 min-w-0">
                                        <!-- Handle -->
                                        <div class="cursor-grab text-slate-500 hover:text-slate-300 p-1 rounded hover:bg-slate-800 drag-handle">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                            </svg>
                                        </div>
                                        
                                        <!-- Title and URL -->
                                        <div class="min-w-0">
                                            <h4 class="font-semibold text-white text-sm truncate"><?= htmlspecialchars($button['label']) ?></h4>
                                            <p class="text-xs text-slate-500 truncate mt-0.5"><?= htmlspecialchars($button['url']) ?></p>
                                        </div>
                                    </div>

                                    <!-- Right: Action elements (Toggle status, edit, delete) -->
                                    <div class="flex items-center space-x-4">
                                        <!-- Status Toggle -->
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" 
                                                   class="sr-only peer" 
                                                   onchange="toggleActive(<?= $button['id'] ?>, this.checked)"
                                                   <?= $button['is_active'] ? 'checked' : '' ?>>
                                            <div class="w-9 h-5 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-slate-400 after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600 peer-checked:after:bg-white"></div>
                                        </label>

                                        <!-- Edit button -->
                                        <button onclick="openEditModal(<?= $button['id'] ?>)" 
                                                class="p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-all" 
                                                title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                            </svg>
                                        </button>

                                        <!-- Delete button -->
                                        <button onclick="deleteButton(<?= $button['id'] ?>)" 
                                                class="p-2 text-slate-500 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-all" 
                                                title="Hapus">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                    
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Settings & Profile (Col span 1) -->
            <div class="space-y-6">
                <div class="bg-slate-900/40 border border-slate-800 rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-white mb-6">Pengaturan Profil & Tema</h3>
                    
                    <form id="profile-form" onsubmit="saveProfile(event)" class="space-y-5">
                        
                        <!-- Nama Profil -->
                        <div>
                            <label for="profile_name" class="block text-slate-400 text-xs font-semibold uppercase tracking-wider mb-2">Nama Profil</label>
                            <input type="text" id="profile_name" name="profile_name" required 
                                   class="block w-full px-4 py-3 bg-slate-950/50 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm" 
                                   placeholder="Nama Anda" 
                                   value="<?= htmlspecialchars($settings['profile_name']) ?>">
                        </div>

                        <!-- Bio Singkat -->
                        <div>
                            <label for="profile_bio" class="block text-slate-400 text-xs font-semibold uppercase tracking-wider mb-2">Bio Singkat</label>
                            <textarea id="profile_bio" name="profile_bio" rows="3"
                                      class="block w-full px-4 py-3 bg-slate-950/50 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm" 
                                      placeholder="Bio singkat Anda..."><?= htmlspecialchars($settings['profile_bio']) ?></textarea>
                        </div>

                        <!-- URL Avatar -->
                        <div>
                            <label for="profile_avatar" class="block text-slate-400 text-xs font-semibold uppercase tracking-wider mb-2">URL Foto Profil</label>
                            <input type="url" id="profile_avatar" name="profile_avatar" 
                                   class="block w-full px-4 py-3 bg-slate-950/50 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm" 
                                   placeholder="https://example.com/avatar.jpg" 
                                   value="<?= htmlspecialchars($settings['profile_avatar']) ?>">
                        </div>

                        <!-- Pilihan Tema Gradasi -->
                        <div>
                            <label class="block text-slate-400 text-xs font-semibold uppercase tracking-wider mb-3">Tema Background Microsite</label>
                            <div class="grid grid-cols-2 gap-3">
                                
                                <!-- Theme 1: Indigo -->
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="theme_gradient" value="from-slate-900 via-indigo-950 to-slate-900" 
                                           class="sr-only peer" <?= $settings['theme_gradient'] === 'from-slate-900 via-indigo-950 to-slate-900' ? 'checked' : '' ?>>
                                    <div class="p-3 border border-slate-800 rounded-xl bg-slate-900 hover:border-slate-700 peer-checked:border-indigo-500 peer-checked:ring-2 peer-checked:ring-indigo-500/20 text-xs text-center transition-all">
                                        <div class="w-full h-8 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-md mb-1.5"></div>
                                        <span class="text-slate-300 font-medium">Midnight Indigo</span>
                                    </div>
                                </label>

                                <!-- Theme 2: Emerald -->
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="theme_gradient" value="from-slate-900 via-emerald-950 to-slate-900" 
                                           class="sr-only peer" <?= $settings['theme_gradient'] === 'from-slate-900 via-emerald-950 to-slate-900' ? 'checked' : '' ?>>
                                    <div class="p-3 border border-slate-800 rounded-xl bg-slate-900 hover:border-slate-700 peer-checked:border-emerald-500 peer-checked:ring-2 peer-checked:ring-emerald-500/20 text-xs text-center transition-all">
                                        <div class="w-full h-8 bg-gradient-to-r from-slate-900 via-emerald-950 to-slate-900 rounded-md mb-1.5"></div>
                                        <span class="text-slate-300 font-medium">Emerald Forest</span>
                                    </div>
                                </label>

                                <!-- Theme 3: Purple Sunset -->
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="theme_gradient" value="from-slate-900 via-purple-950 to-slate-900" 
                                           class="sr-only peer" <?= $settings['theme_gradient'] === 'from-slate-900 via-purple-950 to-slate-900' ? 'checked' : '' ?>>
                                    <div class="p-3 border border-slate-800 rounded-xl bg-slate-900 hover:border-slate-700 peer-checked:border-purple-500 peer-checked:ring-2 peer-checked:ring-purple-500/20 text-xs text-center transition-all">
                                        <div class="w-full h-8 bg-gradient-to-r from-slate-900 via-purple-950 to-slate-900 rounded-md mb-1.5"></div>
                                        <span class="text-slate-300 font-medium">Sunset Purple</span>
                                    </div>
                                </label>

                                <!-- Theme 4: Rose Dark -->
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="theme_gradient" value="from-slate-900 via-rose-950 to-slate-900" 
                                           class="sr-only peer" <?= $settings['theme_gradient'] === 'from-slate-900 via-rose-950 to-slate-900' ? 'checked' : '' ?>>
                                    <div class="p-3 border border-slate-800 rounded-xl bg-slate-900 hover:border-slate-700 peer-checked:border-rose-500 peer-checked:ring-2 peer-checked:ring-rose-500/20 text-xs text-center transition-all">
                                        <div class="w-full h-8 bg-gradient-to-r from-slate-900 via-rose-950 to-slate-900 rounded-md mb-1.5"></div>
                                        <span class="text-slate-300 font-medium">Dark Rose</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Button Submit Profile -->
                        <button type="submit" 
                                class="w-full py-3 px-4 bg-slate-800 hover:bg-slate-700 active:scale-[0.98] text-white font-medium rounded-xl border border-slate-700 shadow-md transition-all text-sm flex justify-center items-center space-x-2">
                            <span>Simpan Profil & Tema</span>
                        </button>
                    </form>
                </div>

                <!-- Card Pengaturan Akun Admin -->
                <div class="bg-slate-900/40 border border-slate-800 rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-white mb-6">Pengaturan Akun Admin</h3>
                    
                    <form id="account-form" onsubmit="saveAccount(event)" class="space-y-5">
                        
                        <!-- Username Admin -->
                        <div>
                            <label for="admin_username" class="block text-slate-400 text-xs font-semibold uppercase tracking-wider mb-2">Username Baru</label>
                            <input type="text" id="admin_username" name="username" required 
                                   class="block w-full px-4 py-3 bg-slate-950/50 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm" 
                                   placeholder="Username baru" 
                                   value="<?= htmlspecialchars($admin_username) ?>">
                        </div>

                        <!-- Password Baru -->
                        <div>
                            <label for="admin_new_password" class="block text-slate-400 text-xs font-semibold uppercase tracking-wider mb-2">Password Baru (Opsional)</label>
                            <input type="password" id="admin_new_password" name="new_password" 
                                   class="block w-full px-4 py-3 bg-slate-950/50 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm" 
                                   placeholder="Kosongkan jika tidak ingin diubah">
                        </div>

                        <!-- Password Saat Ini -->
                        <div>
                            <label for="admin_current_password" class="block text-slate-400 text-xs font-semibold uppercase tracking-wider mb-2">Password Saat Ini (Konfirmasi)</label>
                            <input type="password" id="admin_current_password" name="current_password" required 
                                   class="block w-full px-4 py-3 bg-slate-950/50 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm" 
                                   placeholder="Masukkan password saat ini">
                        </div>

                        <!-- Button Submit Account -->
                        <button type="submit" 
                                class="w-full py-3 px-4 bg-slate-800 hover:bg-slate-700 active:scale-[0.98] text-white font-medium rounded-xl border border-slate-700 shadow-md transition-all text-sm flex justify-center items-center space-x-2">
                            <span>Simpan Perubahan Akun</span>
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </main>

    <!-- Modal Form Tambah / Edit Tombol -->
    <div id="button-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden transition-all duration-300">
        <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-6 transform scale-95 transition-all duration-300">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modal-title" class="text-lg font-bold text-white">Tambah Tombol</h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="button-form" onsubmit="saveButton(event)" class="space-y-4">
                <input type="hidden" id="button_id" name="id" value="0">
                
                <!-- Label Tombol -->
                <div>
                    <label for="button_label" class="block text-slate-400 text-xs font-semibold uppercase tracking-wider mb-2">Label Tombol</label>
                    <input type="text" id="button_label" name="label" required 
                           class="block w-full px-4 py-3 bg-slate-950/50 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm" 
                           placeholder="Contoh: Instagram, Portofolio">
                </div>

                <!-- URL Tombol -->
                <div>
                    <label for="button_url" class="block text-slate-400 text-xs font-semibold uppercase tracking-wider mb-2">URL Tautan</label>
                    <input type="text" id="button_url" name="url" required 
                           class="block w-full px-4 py-3 bg-slate-950/50 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm" 
                           placeholder="Contoh: https://instagram.com/username">
                </div>

                <!-- Status Aktif (Checkbox didalam Form Modal) -->
                <div class="flex items-center space-x-3 pt-2">
                    <input type="checkbox" id="button_is_active" name="is_active" value="1" checked
                           class="w-4 h-4 text-indigo-600 bg-slate-950 border-slate-800 rounded focus:ring-indigo-500 focus:ring-offset-slate-900 focus:ring-2">
                    <label for="button_is_active" class="text-sm font-medium text-slate-300">Tampilkan tombol ini di microsite</label>
                </div>

                <!-- Button Aksi Modal -->
                <div class="flex space-x-3 pt-4 border-t border-slate-800">
                    <button type="button" onclick="closeModal()" 
                            class="flex-1 py-3 bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold rounded-xl text-sm transition-all text-center">
                        Batal
                    </button>
                    <button type="submit" 
                            class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-sm transition-all text-center">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 space-y-2"></div>

    <!-- AJAX Client Operations -->
    <script>
        // Global CSRF Token
        const csrfToken = "<?= htmlspecialchars($csrf_token) ?>";

        // Fungsi menampilkan Toast notification
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            const bgClass = type === 'success' ? 'bg-slate-900 border-indigo-500/30 text-indigo-400' : 'bg-slate-900 border-red-500/30 text-red-400';
            
            toast.className = `flex items-center space-x-3 px-4 py-3 border rounded-xl shadow-2xl transition-all duration-300 transform translate-y-2 opacity-0 ${bgClass}`;
            toast.innerHTML = `
                <span class="p-1 rounded-lg ${type === 'success' ? 'bg-indigo-500/10' : 'bg-red-500/10'}">
                    ${type === 'success' ? 
                        `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>` : 
                        `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>`
                    }
                </span>
                <span class="text-xs font-semibold text-white">${message}</span>
            `;
            
            container.appendChild(toast);
            
            // Animate In
            setTimeout(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
            }, 10);
            
            // Animate Out
            setTimeout(() => {
                toast.classList.add('translate-y-2', 'opacity-0');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
        }

        // Initialize SortableJS
        const buttonList = document.getElementById('button-list');
        if (buttonList && document.querySelectorAll('#button-list > [data-id]').length > 0) {
            Sortable.create(buttonList, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function (evt) {
                    const ids = Array.from(buttonList.querySelectorAll('[data-id]')).map(el => el.getAttribute('data-id'));
                    
                    fetch('actions.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrfToken
                        },
                        body: JSON.stringify({
                            action: 'update_order',
                            ids: ids
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message);
                        } else {
                            showToast(data.message, 'error');
                        }
                    })
                    .catch(err => {
                        showToast('Gagal terhubung ke server.', 'error');
                    });
                }
            });
        }

        // Modal Handlers
        const modal = document.getElementById('button-modal');
        const modalContent = modal.querySelector('.transform');

        function openAddModal() {
            document.getElementById('modal-title').textContent = 'Tambah Tombol Baru';
            document.getElementById('button_id').value = '0';
            document.getElementById('button-form').reset();
            document.getElementById('button_is_active').checked = true;
            
            modal.classList.remove('hidden');
            setTimeout(() => {
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
            }, 10);
        }

        function openEditModal(id) {
            document.getElementById('modal-title').textContent = 'Edit Tombol';
            document.getElementById('button-form').reset();

            // Ambil data detail tombol dari actions.php via AJAX
            const formData = new FormData();
            formData.append('action', 'get_button');
            formData.append('id', id);
            formData.append('csrf_token', csrfToken);

            fetch('actions.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const button = data.data;
                    document.getElementById('button_id').value = button.id;
                    document.getElementById('button_label').value = button.label;
                    document.getElementById('button_url').value = button.url;
                    document.getElementById('button_is_active').checked = parseInt(button.is_active) === 1;

                    modal.classList.remove('hidden');
                    setTimeout(() => {
                        modalContent.classList.remove('scale-95');
                        modalContent.classList.add('scale-100');
                    }, 10);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(() => {
                showToast('Gagal memuat data tombol.', 'error');
            });
        }

        function closeModal() {
            modalContent.classList.remove('scale-100');
            modalContent.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 150);
        }

        // Save Button via AJAX
        function saveButton(e) {
            e.preventDefault();
            const form = document.getElementById('button-form');
            const formData = new FormData(form);
            formData.append('action', 'save_button');
            formData.append('csrf_token', csrfToken);

            fetch('actions.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    closeModal();
                    // Reload data setelah delay sedikit biar user melihat transisi
                    setTimeout(() => {
                        window.location.reload();
                    }, 800);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(() => {
                showToast('Gagal menyimpan tombol.', 'error');
            });
        }

        // Delete Button via AJAX
        function deleteButton(id) {
            if (!confirm('Apakah Anda yakin ingin menghapus tombol ini?')) return;

            const formData = new FormData();
            formData.append('action', 'delete_button');
            formData.append('id', id);
            formData.append('csrf_token', csrfToken);

            fetch('actions.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    // Hapus element baris di DOM
                    const row = document.querySelector(`[data-id="${id}"]`);
                    if (row) {
                        row.classList.add('opacity-0', 'scale-90');
                        setTimeout(() => {
                            row.remove();
                            if (document.querySelectorAll('#button-list > [data-id]').length === 0) {
                                window.location.reload(); // muat ulang jika kosong untuk render empty state
                            }
                        }, 300);
                    }
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(() => {
                showToast('Gagal menghapus tombol.', 'error');
            });
        }

        // Toggle Active status via AJAX
        function toggleActive(id, checked) {
            const formData = new FormData();
            formData.append('action', 'toggle_active');
            formData.append('id', id);
            formData.append('is_active', checked ? 1 : 0);
            formData.append('csrf_token', csrfToken);

            fetch('actions.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(() => {
                showToast('Gagal memperbarui status tombol.', 'error');
            });
        }

        // Save Profile via AJAX
        function saveProfile(e) {
            e.preventDefault();
            const form = document.getElementById('profile-form');
            const formData = new FormData(form);
            formData.append('action', 'save_profile');
            formData.append('csrf_token', csrfToken);

            fetch('actions.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(() => {
                showToast('Gagal menyimpan profil.', 'error');
            });
        }

        // Save Account Credentials via AJAX
        function saveAccount(e) {
            e.preventDefault();
            const form = document.getElementById('account-form');
            const formData = new FormData(form);
            formData.append('action', 'save_account');
            formData.append('csrf_token', csrfToken);

            fetch('actions.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    // Reset input password setelah sukses menyimpan
                    document.getElementById('admin_new_password').value = '';
                    document.getElementById('admin_current_password').value = '';
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(() => {
                showToast('Gagal menyimpan perubahan akun.', 'error');
            });
        }
    </script>
</body>
</html>
