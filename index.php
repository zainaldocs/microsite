<?php
// Include koneksi database (auth helper tidak wajib di sini karena halaman publik)
require_once __DIR__ . '/inc/db.php';

// Ambil konfigurasi profil & tema
$settings = [
    'profile_name' => 'Nama Profil',
    'profile_bio' => 'Deskripsi singkat...',
    'profile_avatar' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
    'theme_gradient' => 'from-slate-900 via-indigo-950 to-slate-900'
];

try {
    $stmt = $pdo->query('SELECT * FROM settings');
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Gunakan fallback default jika error
}

// Ambil semua tombol yang aktif, diurutkan berdasarkan order_index
$buttons = [];
try {
    $stmt = $pdo->prepare('SELECT * FROM buttons WHERE is_active = 1 ORDER BY order_index ASC');
    $stmt->execute();
    $buttons = $stmt->fetchAll();
} catch (PDOException $e) {
    // Gunakan array kosong jika error
}

/**
 * Fungsi untuk mencocokkan label tombol dengan ikon SVG yang sesuai (Instagram, TikTok, WhatsApp, dll)
 * @param string $label
 * @return string Kode SVG inline
 */
function get_social_icon($label) {
    $label = strtolower(trim($label));
    
    // Ikon Instagram
    if (str_contains($label, 'instagram') || str_contains($label, 'ig')) {
        return '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>';
    }
    
    // Ikon WhatsApp
    if (str_contains($label, 'whatsapp') || str_contains($label, 'wa')) {
        return '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>';
    }
    
    // Ikon TikTok
    if (str_contains($label, 'tiktok')) {
        return '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/></svg>';
    }
    
    // Ikon YouTube
    if (str_contains($label, 'youtube') || str_contains($label, 'yt')) {
        return '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17z"/><polygon points="10 15 15 12 10 9"/></svg>';
    }

    // Ikon GitHub
    if (str_contains($label, 'github') || str_contains($label, 'git')) {
        return '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"/><path d="M9 18c-4.51 2-5-2-7-2"/></svg>';
    }

    // Ikon Twitter / X
    if (str_contains($label, 'twitter') || str_contains($label, 'x.com')) {
        return '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/></svg>';
    }

    // Ikon Facebook
    if (str_contains($label, 'facebook') || str_contains($label, 'fb')) {
        return '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>';
    }

    // Default icon (link rantai)
    return '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['profile_name']) ?> - Linktree Microsite</title>
    <!-- Meta SEO -->
    <meta name="description" content="<?= htmlspecialchars($settings['profile_bio']) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($settings['profile_name']) ?> - Linktree Microsite">
    <meta property="og:description" content="<?= htmlspecialchars($settings['profile_bio']) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($settings['profile_avatar']) ?>">
    <meta name="twitter:card" content="summary_large_image">

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
        /* Animasi fade in untuk memuat halaman */
        .fade-in {
            animation: fadeIn 0.8s ease-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gradient-to-b <?= htmlspecialchars($settings['theme_gradient']) ?> min-h-screen text-slate-100 flex flex-col items-center justify-between py-16 px-4">

    <!-- Container Utama -->
    <div class="w-full max-w-md mx-auto flex-1 flex flex-col items-center justify-center fade-in">
        
        <!-- Section Profil -->
        <div class="text-center mb-8">
            <div class="relative inline-block mb-4 group">
                <!-- Glowing border behind avatar -->
                <div class="absolute -inset-0.5 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full blur opacity-40 group-hover:opacity-75 transition duration-1000 group-hover:duration-200"></div>
                <img class="relative w-28 h-28 rounded-full border-2 border-slate-800 object-cover shadow-xl" 
                     src="<?= htmlspecialchars($settings['profile_avatar']) ?>" 
                     alt="Foto Profil <?= htmlspecialchars($settings['profile_name']) ?>"
                     onerror="this.src='https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=facearea&facepad=2&w=256&h=256&q=80'">
            </div>
            
            <h1 class="text-2xl font-bold text-white tracking-tight leading-tight"><?= htmlspecialchars($settings['profile_name']) ?></h1>
            <p class="text-slate-300 text-sm mt-3 px-6 max-w-sm mx-auto font-light leading-relaxed"><?= htmlspecialchars($settings['profile_bio']) ?></p>
        </div>

        <!-- Section Tombol-Tombol -->
        <div class="w-full space-y-4 px-2 mb-12">
            <?php if (empty($buttons)): ?>
                <div class="text-center py-8 bg-slate-900/40 backdrop-blur-md border border-slate-800/80 rounded-2xl">
                    <p class="text-slate-400 text-sm">Tidak ada tautan aktif saat ini.</p>
                </div>
            <?php else: ?>
                <?php foreach ($buttons as $button): ?>
                    <a href="<?= htmlspecialchars($button['url']) ?>" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="w-full py-4 px-5 bg-slate-900/40 hover:bg-indigo-600/20 backdrop-blur-md border border-slate-800 hover:border-indigo-500/50 rounded-2xl flex items-center justify-between text-slate-100 hover:text-white transition-all duration-300 transform hover:scale-[1.02] hover:shadow-lg hover:shadow-indigo-500/5 group">
                        
                        <!-- Left: Icon & Label -->
                        <div class="flex items-center space-x-3 min-w-0">
                            <!-- Icon -->
                            <div class="text-indigo-400 group-hover:text-indigo-300 transition-colors">
                                <?= get_social_icon($button['label']) ?>
                            </div>
                            <!-- Label -->
                            <span class="text-sm font-semibold truncate tracking-wide"><?= htmlspecialchars($button['label']) ?></span>
                        </div>

                        <!-- Right: Arrow icon -->
                        <div class="text-slate-500 group-hover:text-indigo-300 transform translate-x-0 group-hover:translate-x-1 transition-all duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- Footer -->
    <footer class="w-full text-center text-xs text-slate-500 tracking-wide mt-8">
        <div class="inline-flex items-center space-x-1.5">
            <span>Dibuat dengan</span>
            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-3.5 h-3.5 text-indigo-500 animate-pulse" viewBox="0 0 24 24">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
            </svg>
            <a href="admin/login.php" class="hover:text-indigo-400 transition-colors font-semibold hover:underline">Microsite Admin</a>
        </div>
    </footer>

</body>
</html>
