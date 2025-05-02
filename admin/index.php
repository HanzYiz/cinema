<?php
require_once '../api/config.php';
require_once '../components/alert.php';

if (isset($_SESSION['showDisruptiveAlert'])) { 
    echo $_SESSION['showDisruptiveAlert'];
    unset($_SESSION['showDisruptiveAlert']); 
  } 

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'user') {
            $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('warning', 'Gagal!','Akses ditolak!');
            header("Location: ../dashboard");
            exit();
        } else {
            $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('warning', 'Gagal!','Mau ngapain si bos ?');
            header("Location: ../api/login_ext.php");
            exit();
        }
    exit(); 
}

function getDashboardStats($pdo) {
    $stats = [];
    
    try {
        // Periksa struktur tabel payments (tambahkan logging untuk debug)
        $checkStructureQuery = "DESCRIBE payments";
        $checkStmt = $pdo->prepare($checkStructureQuery);
        $checkStmt->execute();
        $columns = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
        error_log("Payments table columns: " . implode(", ", $columns));
        
        // Debug untuk melihat status yang ada di database
        $statusCheckQuery = "SELECT DISTINCT status FROM payments";
        $statusCheckStmt = $pdo->prepare($statusCheckQuery);
        $statusCheckStmt->execute();
        $availableStatuses = $statusCheckStmt->fetchAll(PDO::FETCH_COLUMN);
        error_log("Available payment statuses: " . implode(", ", $availableStatuses));
        
        // Coba query baik dari payments maupun bookings untuk memeriksa
        // (untuk mengatasi jika status mungkin disimpan di salah satu tabel)
        
        // 1. Cek dari tabel payments
        $pendingPaymentsQuery = "SELECT COUNT(*) FROM payments WHERE status = 'pending'";
        $pendingPaymentsStmt = $pdo->prepare($pendingPaymentsQuery);
        $pendingPaymentsStmt->execute();
        $pendingPayments = $pendingPaymentsStmt->fetchColumn();
        
        // 2. Cek dari tabel bookings juga (jika status juga disimpan di sini)
        $pendingBookingsQuery = "SELECT COUNT(*) FROM bookings WHERE status = 'pending'";
        $pendingBookingsStmt = $pdo->prepare($pendingBookingsQuery);
        $pendingBookingsStmt->execute();
        $pendingBookings = $pendingBookingsStmt->fetchColumn();
        
        // Gunakan nilai yang lebih besar (artinya ada data)
        $stats['pending'] = max($pendingPayments, $pendingBookings);
        
        // Lakukan hal yang sama untuk status selesai/paid
        $completedPaymentsQuery = "SELECT COUNT(*) FROM payments WHERE status = 'paid'";
        $completedPaymentsStmt = $pdo->prepare($completedPaymentsQuery);
        $completedPaymentsStmt->execute();
        $completedPayments = $completedPaymentsStmt->fetchColumn();
        
        $completedBookingsQuery = "SELECT COUNT(*) FROM bookings WHERE status = 'paid' OR status = 'completed'";
        $completedBookingsStmt = $pdo->prepare($completedBookingsQuery);
        $completedBookingsStmt->execute();
        $completedBookings = $completedBookingsStmt->fetchColumn();
        
        $stats['completed_payments'] = max($completedPayments, $completedBookings);
        
        // Booking hari ini (tetap sama karena sudah berfungsi)
        $todayBookingsQuery = "SELECT COUNT(*) FROM bookings WHERE DATE(waktu_pesan) = CURDATE()";
        $todayBookingsStmt = $pdo->prepare($todayBookingsQuery);
        $todayBookingsStmt->execute();
        $stats['today_bookings'] = $todayBookingsStmt->fetchColumn();

        $todayIncomeQuery = "SELECT SUM(total_harga) FROM bookings WHERE DATE(waktu_pesan) = CURDATE()";
        $todayIncomeStmt = $pdo->prepare($todayIncomeQuery);
        $todayIncomeStmt->execute();
        $stats['today_income'] = $todayIncomeStmt->fetchColumn() ?: 0; // Handle NULL value
    } catch (PDOException $e) {
        error_log("Error fetching dashboard stats: " . $e->getMessage());
        // Set default values if queries fail
        $stats['pending'] = 0;
        $stats['completed_payments'] = 0;
        $stats['today_bookings'] = 0;
        $stats['today_income'] = 0;
    }
    
    return $stats;
}

$stats = getDashboardStats($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .stat-card {
            border-radius: 10px;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <?php include 'payment/admin-navbar.php'; ?>

    <div class="container py-4">
        <h2 class="mb-4"><i class="bi bi-speedometer2"></i> Admin Dashboard</h2>
        
        <!-- Statistik -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <h5><i class="bi bi-clock-history"></i> Pending</h5>
                        <h3><?= $stats['pending'] ?></h3>
                        <a href="payment/list.php?status=pending" class="text-white">Lihat Detail</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <h5><i class="bi bi-check-circle"></i> Selesai</h5>
                        <h3><?= $stats['completed_payments'] ?></h3>
                        <a href="payment/list.php?status=paid" class="text-white">Lihat Detail</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <h5><i class="bi bi-ticket"></i> Booking Hari Ini</h5>
                        <h3><?= $stats['today_bookings'] ?></h3>
                        <a href="payment/list.php?status=today" class="text-white">Lihat Detail</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-warning text-dark">
                    <div class="card-body">
                        <h5><i class="bi bi-cash-stack"></i> Pendapatan Hari Ini</h5>
                        <h3>Rp <?= number_format($stats['today_income'], 0, ',', '.') ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pembayaran Terbaru yang Perlu Diverifikasi -->
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-credit-card"></i> Pembayaran Perlu Verifikasi</h5>
            </div>
            <div class="card-body">
                <?php
                try {
                    // Modifikasi query untuk mencari pembayaran yang perlu diverifikasi
                    // Mencoba berbagai kemungkinan status untuk mengambil data
                    $stmt = $pdo->prepare("SELECT b.id, b.kode_booking, b.total_harga, b.waktu_pesan, 
                                          m.judul as movie_title, u.nama as customer_name,
                                          p.bukti_pembayaran
                                       FROM bookings b
                                       JOIN payments p ON b.id = p.booking_id
                                       JOIN schedules s ON b.schedule_id = s.id
                                       JOIN movies m ON s.movie_id = m.id
                                       JOIN users u ON b.user_id = u.id
                                       WHERE b.status = 'pending' OR p.status = 'pending'
                                       ORDER BY b.waktu_pesan DESC
                                       LIMIT 5");
                    $stmt->execute();
                    $pendingPayments = $stmt->fetchAll();
                } catch (PDOException $e) {
                    error_log("Error fetching pending payments: " . $e->getMessage());
                    $pendingPayments = [];
                }
                ?>

                <?php if (empty($pendingPayments)): ?>
                    <div class="alert alert-info">Tidak ada pembayaran yang perlu diverifikasi</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Kode Booking</th>
                                    <th>Film</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Waktu</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingPayments as $payment): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($payment['kode_booking']) ?></td>
                                        <td><?= htmlspecialchars($payment['movie_title']) ?></td>
                                        <td><?= htmlspecialchars($payment['customer_name']) ?></td>
                                        <td>Rp <?= number_format($payment['total_harga'], 0, ',', '.') ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($payment['waktu_pesan'])) ?></td>
                                        <td>
                                            <a href="payment/verify.php?id=<?= $payment['id'] ?>" 
                                               class="btn btn-sm btn-primary">
                                               <i class="bi bi-eye"></i> Verifikasi
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end">
                        <a href="payment/list.php?status=pending_verification" class="btn btn-outline-primary">
                            Lihat Semua <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>