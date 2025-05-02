<?php
require_once '../../api/config.php';
require_once '../../components/alert.php';

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('warning', 'Gagal!', 'Akses ditolak!');
    header("Location: ../../api/login_ext.php");
    exit();
}

// Ambil data user
$user_id = $_GET['id'] ?? '';
if (empty($user_id)) {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'User tidak ditemukan.');
    header("Location: index.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'User tidak ditemukan.');
        header("Location: index.php");
        exit();
    }
    
    // Ambil history booking user
    $bookingStmt = $pdo->prepare("SELECT b.*, m.judul as movie_title, s.tanggal, s.jam_mulai
                                 FROM bookings b
                                 JOIN schedules s ON b.schedule_id = s.id
                                 JOIN movies m ON s.movie_id = m.id
                                 WHERE b.user_id = ?
                                 ORDER BY b.waktu_pesan DESC");
    $bookingStmt->execute([$user_id]);
    $bookings = $bookingStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching user data: " . $e->getMessage());
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Error!', 'Gagal memuat data user.');
    header("Location: list.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail User - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .badge-status {
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <?php include '../payment/admin-navbar.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-person"></i> Detail User</h2>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow mb-4">
                    <div class="card-body text-center">
                        <img src="../../assets/img/<?= htmlspecialchars($user['foto'] ?? 'default.jpeg') ?>" 
                             class="profile-img mb-3"
                             onerror="this.src='../../assets/img/default.jpeg'"
                             alt="Profile Photo">
                        <h4><?= htmlspecialchars($user['nama']) ?></h4>
                        <p class="text-muted mb-1">Member</p>
                        <p class="text-muted mb-1"><?= htmlspecialchars($user['username']) ?></p>
                        <p class="text-muted"><i class="bi bi-envelope"></i> <?= htmlspecialchars($user['email']) ?></p>
                        <p class="text-muted">
                            <i class="bi bi-calendar"></i> Bergabung pada <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-ticket"></i> History Booking</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($bookings)): ?>
                            <div class="alert alert-info">User belum melakukan booking</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Kode Booking</th>
                                            <th>Film</th>
                                            <th>Tanggal</th>
                                            <th>Jam</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookings as $booking): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($booking['kode_booking']) ?></td>
                                                <td><?= htmlspecialchars($booking['movie_title']) ?></td>
                                                <td><?= date('d/m/Y', strtotime($booking['tanggal'])) ?></td>
                                                <td><?= date('H:i', strtotime($booking['jam_mulai'])) ?></td>
                                                <td>Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></td>
                                                <td>
                                                    <?php
                                                    $statusClass = [
                                                        'pending' => 'warning',
                                                        'paid' => 'success',
                                                        'cancelled' => 'danger'
                                                    ];
                                                    ?>
                                                    <span class="badge bg-<?= $statusClass[$booking['status']] ?> badge-status">
                                                        <?= ucfirst($booking['status']) ?>
                                                    </span>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>