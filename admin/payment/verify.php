<?php
require_once __DIR__ . '/../../api/config.php';

require_once __DIR__ . '/../../components/alert.php';

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    if (isset($_SESSION['role'])) {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Anda tidak memiliki akses ke halaman ini!');
        header("Location: ../../dashboard");
    } else {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Harap Login Terlebih Dahulu!');
        header("Location: ../../api/login_ext.php");
    }
    exit();
}

// Tangani aksi verifikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId = sanitize_input($_POST['booking_id']);
    $action = sanitize_input($_POST['action']);
    
    try {
        $pdo->beginTransaction();
        
        // Update status booking
        $newStatus = ($action === 'approve') ? 'paid' : 'cancelled';
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $bookingId]);
        
        // Update status pembayaran
        $paymentStatus = ($action === 'approve') ? 'success' : 'failed';
        $stmt = $pdo->prepare("UPDATE payments SET status = ? WHERE booking_id = ?");
        $stmt->execute([$paymentStatus, $bookingId]);
        
        // Jika ditolak, lepaskan kursi yang dipesan
        if ($action === 'reject') {
            // 1. Dapatkan semua seat_id yang terkait dengan booking ini
            $stmt = $pdo->prepare("SELECT seat_id FROM booking_seats WHERE booking_id = ?");
            $stmt->execute([$bookingId]);
            $seatIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // 2. Update status kursi kembali ke 'available'
            if (!empty($seatIds)) {
                $placeholders = implode(',', array_fill(0, count($seatIds), '?'));
                $stmt = $pdo->prepare("UPDATE seats SET status = 'available' WHERE id IN ($placeholders)");
                $stmt->execute($seatIds);
            }
            
            // 3. Hapus record booking_seats
            $stmt = $pdo->prepare("DELETE FROM booking_seats WHERE booking_id = ?");
            $stmt->execute([$bookingId]);
        }
        
        $pdo->commit();
        
        // Redirect dengan pesan sukses
       if ($action === 'approve') {
            $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('success', 'Berhasil!', 'Pembayaran berhasil diverifikasi.');
        } else {
            $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('warning', 'Dibatalkan!', 'Pembayaran ditolak.');
        }
        header("Location: list.php?status=pending_verification&verified=1");
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Payment verification error: " . $e->getMessage());
        header("Location: verify.php?id=$bookingId&error=1");
        exit();
    }
}

// Ambil data pembayaran
$bookingId = sanitize_input($_GET['id'] ?? '');
if (empty($bookingId)) {
    header("Location: list.php?error=invalid_id");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT 
                          b.id, b.kode_booking, b.total_harga, b.status, b.waktu_pesan,
                          p.bukti_pembayaran, p.metode, p.waktu_pembayaran,
                          m.judul as movie_title, m.poster_url,
                          s.tanggal, s.jam_mulai, s.studio_id,
                          stu.nama as studio_name,
                          u.nama as customer_name, u.email as customer_email
                       FROM bookings b
                       JOIN payments p ON b.id = p.booking_id
                       JOIN schedules s ON b.schedule_id = s.id
                       JOIN movies m ON s.movie_id = m.id
                       JOIN studios stu ON s.studio_id = stu.id
                       JOIN users u ON b.user_id = u.id
                       WHERE b.id = ?");
    $stmt->execute([$bookingId]);
    $payment = $stmt->fetch();

    if (!$payment) {
        header("Location: list.php?error=not_found");
        exit();
    }

    // Ambil kursi yang dipesan
    $stmt = $pdo->prepare("SELECT 
                          s.baris, s.nomor_kursi
                       FROM booking_seats bs
                       JOIN seats s ON bs.seat_id = s.id
                       WHERE bs.booking_id = ?");
    $stmt->execute([$bookingId]);
    $seats = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Error fetching payment details: " . $e->getMessage());
    header("Location: list.php?error=db_error");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pembayaran - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .payment-proof-img {
            max-width: 100%;
            max-height: 400px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .verification-card {
            border-left: 5px solid #0d6efd;
        }
    </style>
</head>
<body>
    <?php include 'admin-navbar.php'; ?>

    <div class="container py-4">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="list.php">Pembayaran</a></li>
                <li class="breadcrumb-item active">Verifikasi</li>
            </ol>
        </nav>

        <div class="card verification-card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-credit-card"></i> Verifikasi Pembayaran</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Detail Booking</h5>
                        <table class="table table-sm">
                            <tr>
                                <th>Kode Booking</th>
                                <td><?= htmlspecialchars($payment['kode_booking']) ?></td>
                            </tr>
                            <tr>
                                <th>Customer</th>
                                <td><?= htmlspecialchars($payment['customer_name']) ?><br>
                                    <small><?= htmlspecialchars($payment['customer_email']) ?></small>
                                </td>
                            </tr>
                            <tr>
                                <th>Film</th>
                                <td><?= htmlspecialchars($payment['movie_title']) ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal</th>
                                <td><?= date('l, d F Y', strtotime($payment['tanggal'])) ?><br>
                                    <?= date('H:i', strtotime($payment['jam_mulai'])) ?> - <?= $payment['studio_name'] ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Kursi</th>
                                <td>
                                    <?php foreach ($seats as $seat): ?>
                                        <span class="badge bg-secondary">
                                            <?= htmlspecialchars($seat['baris']) ?><?= htmlspecialchars($seat['nomor_kursi']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Total Pembayaran</th>
                                <td>Rp <?= number_format($payment['total_harga'], 0, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <th>Metode Pembayaran</th>
                                <td><?= strtoupper($payment['metode']) ?></td>
                            </tr>
                            <tr>
                                <th>Waktu Pembayaran</th>
                                <td><?= date('d/m/Y H:i', strtotime($payment['waktu_pembayaran'])) ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Bukti Pembayaran</h5>
                        <?php if (!empty($payment['bukti_pembayaran'])): ?>
                            <?php
                            $extension = pathinfo($payment['bukti_pembayaran'], PATHINFO_EXTENSION);
                            $isImage = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']);
                            ?>
                            
                            <?php if ($isImage): ?>
                                <img src="../../api/uploads/payments/<?= htmlspecialchars($payment['bukti_pembayaran']) ?>" 
                                     class="payment-proof-img img-fluid mb-3">
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-file-earmark"></i> File bukti pembayaran dalam format <?= strtoupper($extension) ?>
                                </div>
                                <a href="../../uploads/payments/<?= htmlspecialchars($payment['bukti_pembayaran']) ?>" 
                                   target="_blank" 
                                   class="btn btn-outline-primary">
                                    <i class="bi bi-download"></i> Download Bukti
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> Tidak ada bukti pembayaran yang diupload
                            </div>
                        <?php endif; ?>

                        <hr>

                        <h5 class="mt-4">Verifikasi</h5>
                        <form method="post">
                            <input type="hidden" name="booking_id" value="<?= $payment['id'] ?>">
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="action" id="approve" value="approve" checked>
                                <label class="form-check-label text-success fw-bold" for="approve">
                                    <i class="bi bi-check-circle"></i> Setujui Pembayaran
                                </label>
                                <div class="form-text">Booking akan dikonfirmasi dan kursi akan dipesan</div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="action" id="reject" value="reject">
                                <label class="form-check-label text-danger fw-bold" for="reject">
                                    <i class="bi bi-x-circle"></i> Tolak Pembayaran
                                </label>
                                <div class="form-text">Booking akan dibatalkan dan kursi akan dilepas</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Catatan (Opsional)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="list.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Kembali
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg"></i> Submit Verifikasi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>