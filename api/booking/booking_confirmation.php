<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../components/alert.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'users' && $_SESSION['role'] !== 'admin') {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Anda tidak memiliki akses ke halaman ini!');
   header("Location: ../../api/login_ext.php");
   exit();
}

// Check if booking_id is provided
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    header("Location: ../../pages/home.php");
    exit();
}

$bookingId = sanitize_input($_GET['booking_id']);

// Check if this is a payment verification request (admin only)
$isVerifying = isset($_POST['verify_payment']) && $_SESSION['user_role'] === 'admin';

try {
    // Handle payment verification by admin
    if ($isVerifying) {
        $newStatus = sanitize_input($_POST['new_status']);
        $pdo->beginTransaction();
        
        try {
            // Update booking status
            $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $bookingId]);
            
            // Update payment status
            $stmt = $pdo->prepare("UPDATE payments SET status = ? WHERE booking_id = ?");
            $paymentStatus = ($newStatus === 'paid') ? 'success' : 'failed';
            $stmt->execute([$paymentStatus, $bookingId]);
            
            $pdo->commit();
            
            // Reload the page to show updated status
            header("Location: booking_confirmation.php?booking_id=$bookingId");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Fetch booking details
    $stmt = $pdo->prepare("SELECT 
                          b.*, 
                          s.tanggal, 
                          s.jam_mulai, 
                          s.jam_selesai, 
                          s.harga as harga_tiket,
                          m.judul as movie_title,
                          m.poster_url,
                          m.durasi,
                          stu.nama as studio_name,
                          p.bukti_pembayaran,
                          p.status as payment_status
                       FROM bookings b
                       JOIN schedules s ON b.schedule_id = s.id
                       JOIN movies m ON s.movie_id = m.id
                       JOIN studios stu ON s.studio_id = stu.id
                       LEFT JOIN payments p ON b.id = p.booking_id
                       WHERE b.id = ?");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        throw new Exception("Booking tidak ditemukan");
    }

    // Fetch booked seats
    $stmt = $pdo->prepare("SELECT 
                          bs.seat_id,
                          s.baris,
                          s.nomor_kursi
                       FROM booking_seats bs
                       JOIN seats s ON bs.seat_id = s.id
                       WHERE bs.booking_id = ?");
    $stmt->execute([$bookingId]);
    $seats = $stmt->fetchAll();

    // Calculate total seats and total price
    $totalSeats = count($seats);
    $totalPrice = $booking['total_harga'];

} catch (Exception $e) {
    error_log("Error fetching booking: " . $e->getMessage());
    die("Terjadi kesalahan saat memuat data booking. Silakan coba lagi.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Booking - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .confirmation-card {
            max-width: 800px;
            margin: 2rem auto;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 1rem;
        }
        .movie-poster {
            height: 300px;
            object-fit: cover;
            border-radius: 0.5rem;
        }
        .seat-badge {
            font-size: 0.9rem;
            margin-right: 0.3rem;
            margin-bottom: 0.3rem;
        }
        .payment-proof {
            max-width: 100%;
            max-height: 300px;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
        }
        .verification-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="card confirmation-card">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0">Booking Confirmation</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <img src="<?= htmlspecialchars($booking['poster_url']) ?>" alt="Movie Poster" class="img-fluid movie-poster mb-3">
                    </div>
                    <div class="col-md-8">
                        <h3><?= htmlspecialchars($booking['movie_title']) ?></h3>
                        <p class="text-muted">
                            <?= date('l, d F Y', strtotime($booking['tanggal'])) ?> | 
                            <?= date('H:i', strtotime($booking['jam_mulai'])) ?> - <?= date('H:i', strtotime($booking['jam_selesai'])) ?>
                        </p>
                        <p><strong>Studio:</strong> <?= htmlspecialchars($booking['studio_name']) ?></p>
                        <p><strong>Durasi:</strong> <?= floor($booking['durasi']/60) ?> jam <?= $booking['durasi']%60 ?> menit</p>
                        
                        <hr>
                        
                        <h5>Detail Booking</h5>
                        <p><strong>Kode Booking:</strong> <?= htmlspecialchars($booking['kode_booking']) ?></p>
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?= 
                                $booking['status'] === 'paid' ? 'success' : 
                                ($booking['status'] === 'pending_verification' ? 'warning' : 'secondary')
                            ?>">
                                <?= ucfirst(str_replace('_', ' ', $booking['status'])) ?>
                            </span>
                        </p>
                        
                        <div class="mb-3">
                            <strong>Kursi Dipilih:</strong><br>
                            <?php foreach ($seats as $seat): ?>
                                <span class="badge seat-badge bg-secondary">
                                    <?= htmlspecialchars($seat['baris']) ?><?= htmlspecialchars($seat['nomor_kursi']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Total Harga:</strong> Rp <?= number_format($totalPrice, 0, ',', '.') ?>
                        </div>
                        
                        <?php if (!empty($booking['bukti_pembayaran'])): ?>
                            <div class="mb-3">
                                <strong>Bukti Pembayaran:</strong><br>
                                <?php 
                                $extension = pathinfo($booking['bukti_pembayaran'], PATHINFO_EXTENSION);
                                if (in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                    <img src="../uploads/payments/<?= htmlspecialchars($booking['bukti_pembayaran']) ?>" 
                                         alt="Bukti Pembayaran" 
                                         class="payment-proof img-fluid mb-2">
                                <?php else: ?>
                                    <a href="../uploads/payments/<?= htmlspecialchars($booking['bukti_pembayaran']) ?>" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-file-earmark"></i> Lihat Bukti PDF
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Admin Verification Section -->
                        <?php if ($_SESSION['user_role'] === 'admin' && $booking['status'] === 'pending_verification'): ?>
                            <div class="admin-verification mt-4 p-3 bg-light rounded">
                                <h5><i class="bi bi-shield-check"></i> Verifikasi Pembayaran</h5>
                                <form method="post">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="new_status" id="verifySuccess" value="paid" checked>
                                        <label class="form-check-label" for="verifySuccess">
                                            Verifikasi Berhasil
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="new_status" id="verifyFailed" value="cancelled">
                                        <label class="form-check-label" for="verifyFailed">
                                            Tolak Pembayaran
                                        </label>
                                    </div>
                                    <div class="verification-buttons">
                                        <button type="submit" name="verify_payment" class="btn btn-success btn-sm">
                                            <i class="bi bi-check-circle"></i> Submit Verifikasi
                                        </button>
                                        <a href="../admin/dashboard.php" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-arrow-left"></i> Kembali
                                        </a>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <!-- User Actions -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                            <?php if ($booking['status'] === 'pending'): ?>
                                <a href="payment.php?booking_id=<?= $bookingId ?>" class="btn btn-primary">
                                    <i class="bi bi-credit-card"></i> Lanjutkan Pembayaran
                                </a>
                            <?php elseif ($booking['status'] === 'paid'): ?>
                                
                                <a href="tiket.php?booking_id=<?= $bookingId ?>" class="btn btn-success">
                                    <i class="bi bi-ticket-perforated"></i> Lihat Tiket
                                </a>
                            <?php endif; ?>
                            <a href="../../dashboard" class="btn btn-outline-secondary">
                                <i class="bi bi-house"></i> Kembali ke Beranda
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-muted">
                <?= APP_NAME ?> | <?= date('Y') ?>
                <?php if ($booking['status'] === 'pending_verification'): ?>
                    | Pembayaran sedang diverifikasi
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>