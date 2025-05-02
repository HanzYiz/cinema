<?php
require_once '../../api/config.php';
require_once '../../components/alert.php';

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('warning', 'Gagal!', 'Akses ditolak!');
    header("Location: ../../api/login_ext.php");
    exit();
}

// Ambil parameter pencarian
$search = $_GET['search'] ?? '';

// Query untuk mengambil data user
try {
    $query = "SELECT * FROM users WHERE role = 'users'";
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (nama LIKE ? OR email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $users = [];
}

// Proses hapus user
if (isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    try {
        // Periksa apakah user memiliki booking
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $bookingCount = $stmt->fetchColumn();
        
        if ($bookingCount > 0) {
            $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'User memiliki booking aktif dan tidak dapat dihapus.');
        } else {
            // Hapus foto profile jika bukan default
            $stmt = $pdo->prepare("SELECT foto FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user && $user['foto'] !== 'default.jpeg') {
                $file_path = "../uploads/profiles/" . $user['foto'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            
            // Hapus user dari database
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('success', 'Berhasil!', 'User berhasil dihapus.');
        }
        
        header("Location: list.php");
        exit();
    } catch (PDOException $e) {
        error_log("Error deleting user: " . $e->getMessage());
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Gagal menghapus user.');
        header("Location: list.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .user-avatar {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
        }
        .search-box {
            max-width: 400px;
        }
    </style>
</head>
<body>
    <?php include '../payment/admin-navbar.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-people"></i> Manajemen User</h2>
            <form method="GET" class="d-flex search-box">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Cari user..." name="search" value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>

        <div class="card shadow">
            <div class="card-body">
                <?php if (empty($users)): ?>
                    <div class="alert alert-info">Tidak ada user yang terdaftar</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $index => $user): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="../../assets/img/<?= htmlspecialchars($user['foto'] ?? 'default.jpeg') ?>" 
                                                     class="user-avatar me-2"
                                                     onerror="this.src='../uploads/profiles/default.jpeg'">
                                                <?= htmlspecialchars($user['nama']) ?>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="detail.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> Detail
                                                </a>
                                                <a href="list.php?delete=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('Yakin ingin menghapus user ini?')">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>