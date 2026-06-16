<?php
// Set header untuk response JSON
header('Content-Type: application/json');

// Jalankan database dan auth helpers
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/auth.php';

// Proteksi Autentikasi: Harus sudah login
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesi Anda telah berakhir. Silakan login kembali.']);
    exit;
}

// Ambil input JSON jika ada (untuk request drag-and-drop sortable yang mengirim data JSON)
$input_raw = file_get_contents('php://input');
$input_json = json_decode($input_raw, true);

// Ambil token CSRF dari Request Header atau POST/JSON data
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? $input_json['csrf_token'] ?? '';

// Proteksi CSRF
if (!verify_csrf_token($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Validasi keamanan CSRF gagal. Silakan muat ulang halaman.']);
    exit;
}

// Ambil parameter action dari POST atau JSON
$action = $_POST['action'] ?? $input_json['action'] ?? '';

switch ($action) {
    case 'get_button':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID tombol tidak valid.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare('SELECT * FROM buttons WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $button = $stmt->fetch();

            if ($button) {
                echo json_encode(['success' => true, 'data' => $button]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Tombol tidak ditemukan.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil data: ' . $e->getMessage()]);
        }
        break;

    case 'save_button':
        $id = intval($_POST['id'] ?? 0);
        $label = trim($_POST['label'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($label) || empty($url)) {
            echo json_encode(['success' => false, 'message' => 'Label dan URL tombol wajib diisi.']);
            exit;
        }

        // Validasi format URL dasar
        if (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('/^(https?:\/\/|mailto:|tel:|wa\.me\/)/i', $url)) {
            echo json_encode(['success' => false, 'message' => 'Format URL tidak valid. Pastikan diawali http://, https://, wa.me, dll.']);
            exit;
        }

        try {
            if ($id > 0) {
                // Update tombol yang ada
                $stmt = $pdo->prepare('UPDATE buttons SET label = :label, url = :url, is_active = :is_active WHERE id = :id');
                $stmt->execute([
                    'label' => $label,
                    'url' => $url,
                    'is_active' => $is_active,
                    'id' => $id
                ]);
                echo json_encode(['success' => true, 'message' => 'Tombol berhasil diperbarui.']);
            } else {
                // Tambah tombol baru (cari index urutan terbesar terlebih dahulu)
                $order_stmt = $pdo->query('SELECT MAX(order_index) as max_order FROM buttons');
                $max_order = $order_stmt->fetch()['max_order'] ?? 0;
                $new_order = $max_order + 1;

                $stmt = $pdo->prepare('INSERT INTO buttons (label, url, order_index, is_active) VALUES (:label, :url, :order_index, :is_active)');
                $stmt->execute([
                    'label' => $label,
                    'url' => $url,
                    'order_index' => $new_order,
                    'is_active' => $is_active
                ]);
                echo json_encode(['success' => true, 'message' => 'Tombol baru berhasil ditambahkan.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan tombol: ' . $e->getMessage()]);
        }
        break;

    case 'delete_button':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID tombol tidak valid.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare('DELETE FROM buttons WHERE id = :id');
            $stmt->execute(['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Tombol berhasil dihapus.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus tombol: ' . $e->getMessage()]);
        }
        break;

    case 'toggle_active':
        $id = intval($_POST['id'] ?? 0);
        $is_active = intval($_POST['is_active'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID tombol tidak valid.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare('UPDATE buttons SET is_active = :is_active WHERE id = :id');
            $stmt->execute([
                'is_active' => $is_active,
                'id' => $id
            ]);
            echo json_encode(['success' => true, 'message' => 'Status tombol berhasil diperbarui.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status: ' . $e->getMessage()]);
        }
        break;

    case 'update_order':
        // Dapatkan data IDs berurutan dari payload JSON
        $ids = $input_json['ids'] ?? [];

        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['success' => false, 'message' => 'Data urutan tidak valid.']);
            exit;
        }

        try {
            $pdo->beginTransaction();
            // Update order_index masing-masing ID tombol
            $stmt = $pdo->prepare('UPDATE buttons SET order_index = :order_index WHERE id = :id');
            foreach ($ids as $index => $id) {
                $stmt->execute([
                    'order_index' => $index + 1,
                    'id' => intval($id)
                ]);
            }
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Urutan tombol berhasil disimpan.']);
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan urutan: ' . $e->getMessage()]);
        }
        break;

    case 'save_profile':
        $profile_name = trim($_POST['profile_name'] ?? '');
        $profile_bio = trim($_POST['profile_bio'] ?? '');
        $profile_avatar = trim($_POST['profile_avatar'] ?? '');
        $theme_gradient = trim($_POST['theme_gradient'] ?? 'from-slate-900 via-indigo-950 to-slate-900');

        if (empty($profile_name)) {
            echo json_encode(['success' => false, 'message' => 'Nama profil tidak boleh kosong.']);
            exit;
        }

        try {
            $pdo->beginTransaction();
            
            // Simpan atau update ke tabel settings menggunakan parameter posisional (?) untuk menghindari error parameter PDO
            $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?');
            
            $stmt->execute(['profile_name', $profile_name, $profile_name]);
            $stmt->execute(['profile_bio', $profile_bio, $profile_bio]);
            $stmt->execute(['profile_avatar', $profile_avatar, $profile_avatar]);
            $stmt->execute(['theme_gradient', $theme_gradient, $theme_gradient]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Pengaturan profil berhasil disimpan.']);
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan profil: ' . $e->getMessage()]);
        }
        break;

    case 'save_account':
        $username = trim($_POST['username'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $current_password = $_POST['current_password'] ?? '';
        $admin_id = $_SESSION['admin_id'] ?? 0;

        if ($admin_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sesi tidak valid. Silakan login kembali.']);
            exit;
        }

        if (empty($username)) {
            echo json_encode(['success' => false, 'message' => 'Username tidak boleh kosong.']);
            exit;
        }

        if (empty($current_password)) {
            echo json_encode(['success' => false, 'message' => 'Password saat ini wajib diisi untuk verifikasi keamanan.']);
            exit;
        }

        try {
            // Ambil data admin dari database
            $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$admin_id]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($current_password, $user['password'])) {
                echo json_encode(['success' => false, 'message' => 'Password saat ini tidak cocok/salah.']);
                exit;
            }

            // Validasi duplikasi username
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1');
            $stmt->execute([$username, $admin_id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Username sudah digunakan oleh akun lain.']);
                exit;
            }

            if (!empty($new_password)) {
                if (strlen($new_password) < 6) {
                    echo json_encode(['success' => false, 'message' => 'Password baru minimal harus 6 karakter.']);
                    exit;
                }
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('UPDATE users SET username = ?, password = ? WHERE id = ?');
                $stmt->execute([$username, $hashed_password, $admin_id]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET username = ? WHERE id = ?');
                $stmt->execute([$username, $admin_id]);
            }

            // Perbarui username di session
            $_SESSION['admin_username'] = $username;

            echo json_encode(['success' => true, 'message' => 'Akun admin berhasil diperbarui.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui akun admin: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal atau tidak didukung.']);
        break;
}
