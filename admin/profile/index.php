<?php
require_once '../../api/config.php';
require_once '../../components/alert.php';

if (isset($_SESSION['showDisruptiveAlert'])) { 
    echo $_SESSION['showDisruptiveAlert'];
    unset($_SESSION['showDisruptiveAlert']); 
  } 
// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('warning', 'Gagal!', 'Akses ditolak!');
    header("Location: ../../api/login_ext.php");
    exit();
}

// Ambil data admin yang sedang login
$admin_id = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching admin data: " . $e->getMessage());
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Error!', 'Gagal memuat data admin.');
    header("Location: index.php");
    exit();
}

// Proses update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nama = $_POST['nama'] ?? '';
    $email = $_POST['email'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    try {
        // Validasi email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Format email tidak valid");
        }

        // Jika ingin ganti password
        if (!empty($current_password)) {
            if (!password_verify($current_password, $admin['password_hash'])) {
                throw new Exception("Password saat ini salah");
            }

            if ($new_password !== $confirm_password) {
                throw new Exception("Password baru tidak cocok");
            }

            if (strlen($new_password) < 6) {
                throw new Exception("Password minimal 6 karakter");
            }

            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        } else {
            $password_hash = $admin['password_hash'];
        }

        // Update database
        $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ?, password_hash = ? WHERE id = ?");
        $stmt->execute([$nama, $email, $password_hash, $admin_id]);

        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('success', 'Berhasil!', 'Profile berhasil diperbarui.');
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', $e->getMessage());
    }
}

// Proses upload foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../../assets/img/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $target_file = $target_dir . uniqid() . '.' . $file_ext;

        // Validasi file
        $allowed_types = ['jpg', 'jpeg', 'png'];
        if (!in_array(strtolower($file_ext), $allowed_types)) {
            $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Hanya file JPG, JPEG, dan PNG yang diizinkan.');
        } elseif ($_FILES['foto']['size'] > 2000000) {
            $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Ukuran file maksimal 2MB.');
        } elseif (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
            // Update database
            $stmt = $pdo->prepare("UPDATE users SET foto = ? WHERE id = ?");
            $stmt->execute([basename($target_file), $admin_id]);

            // Hapus foto lama jika bukan default
            if ($admin['foto'] !== 'default.jpeg' && file_exists($target_dir . $admin['foto'])) {
                unlink($target_dir . $admin['foto']);
            }

            $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('success', 'Berhasil!', 'Foto profile berhasil diupload.');
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Gagal mengupload foto.');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Admin - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .profile-card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <?php include 'payment/admin-navbar.php'; ?>

    <div class="container py-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card profile-card mb-4">
                    <div class="card-body text-center">
                        <div class="position-relative d-inline-block">
                            <img src="../../assets/img/<?= htmlspecialchars($admin['foto'] ?? 'default.jpeg') ?>" 
                                 class="profile-img mb-3" 
                                 onerror="this.src='../../assets/img/default.jpeg'"
                                 alt="Profile Photo">
                            <button class="btn btn-primary btn-sm position-absolute bottom-0 end-0 rounded-circle" 
                                    data-bs-toggle="modal" data-bs-target="#uploadPhotoModal">
                                <i class="bi bi-camera"></i>
                            </button>
                        </div>
                        <h4><?= htmlspecialchars($admin['nama']) ?></h4>
                        <p class="text-muted mb-1">Administrator</p>
                        <p class="text-muted"><i class="bi bi-envelope"></i> <?= htmlspecialchars($admin['email']) ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card profile-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-person-gear"></i> Edit Profile</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="index.php">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="nama" name="nama" 
                                       value="<?= htmlspecialchars($admin['nama']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($admin['email']) ?>" required>
                            </div>
                            
                            <hr class="my-4">
                            
                            <h6 class="mb-3">Ganti Password</h6>
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Password Saat Ini</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Password Baru</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Upload Foto -->
    <div class="modal fade" id="uploadPhotoModal" tabindex="-1" aria-labelledby="uploadPhotoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data" action="profile.php">
                    <input type="hidden" name="upload_photo" value="1">
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadPhotoModalLabel">Upload Foto Profile</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="foto" class="form-label">Pilih Foto</label>
                            <input class="form-control" type="file" id="foto" name="foto" accept="image/jpeg, image/png" required>
                            <div class="form-text">Format: JPG/PNG, Maksimal 2MB</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>