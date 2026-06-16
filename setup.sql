-- Buat database jika belum ada
CREATE DATABASE IF NOT EXISTS `microsite`;
USE `microsite`;

-- 1. Tabel Users untuk Autentikasi Admin
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert User Admin Default (Username: admin, Password: admin123)
-- Password di-hash menggunakan password_hash() php dengan algoritma BCRYPT
INSERT INTO `users` (`username`, `password`) 
VALUES ('admin', '$2y$10$LeOlkbXwuXRy2yr3g0lh7.XE2HpV/Zd9zIKs2hKfm7SJHwgtTmOdq')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- 2. Tabel Buttons untuk Tautan Microsite
CREATE TABLE IF NOT EXISTS `buttons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `label` VARCHAR(100) NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `order_index` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Data Contoh Tombol
INSERT INTO `buttons` (`label`, `url`, `order_index`, `is_active`) VALUES
('Instagram', 'https://instagram.com', 1, 1),
('TikTok', 'https://tiktok.com', 2, 1),
('WhatsApp Chat', 'https://wa.me/6281234567890', 3, 1),
('Website Utama', 'https://google.com', 4, 1);

-- 3. Tabel Settings untuk Konfigurasi Profil & Tampilan Microsite
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` VARCHAR(50) PRIMARY KEY,
  `setting_value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Konfigurasi Awal
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('profile_name', 'Zainal Arifin'),
('profile_bio', 'Creator & Developer. Follow link sosial media saya di bawah ini.'),
('profile_avatar', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=facearea&facepad=2&w=256&h=256&q=80'),
('theme_gradient', 'from-slate-900 via-indigo-950 to-slate-900')
ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`;
