<?php
require_once __DIR__ . '/../config.php';

require_once __DIR__ . '/../../components/alert.php';

if (isset($_SESSION['showDisruptiveAlert'])) { 
    echo $_SESSION['showDisruptiveAlert'];
    unset($_SESSION['showDisruptiveAlert']); 
  } 
  
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'users') {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Anda tidak memiliki akses ke halaman ini!');
   header("Location: ../../api/login_ext.php");
   exit();
}
// Check if booking_id is provided
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    header("Location: ../index.php?error=no_booking_id");
    exit();
}

$bookingId = sanitize_input($_GET['booking_id']);

try {
    // Fetch booking details
    $stmt = $pdo->prepare("SELECT 
                          b.*, 
                          s.tanggal, 
                          s.jam_mulai, 
                          m.judul as movie_title,
                          m.poster_url,
                          p.bukti_pembayaran
                       FROM bookings b
                       JOIN schedules s ON b.schedule_id = s.id
                       JOIN movies m ON s.movie_id = m.id
                       LEFT JOIN payments p ON b.id = p.booking_id
                       WHERE b.id = ?");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        throw new Exception("Booking tidak ditemukan");
    }

    // Check if booking is already paid
    if ($booking['status'] === 'paid') {
        header("Location: booking_confirmation.php?booking_id=$bookingId");
        exit();
    }
    
    // Check if payment proof is already uploaded
    if (!empty($booking['bukti_pembayaran'])) {
        header("Location: booking_confirmation.php?booking_id=$bookingId&awaiting_verification=1");
        exit();
    }

    // Process payment if form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate payment proof
        if (empty($_FILES['payment_proof']['name'])) {
            throw new Exception("Harap upload bukti pembayaran");
        }
        $namefile = $_FILES['payment_proof']['name'];
        $ekstensivalid = ['jpg', 'jpeg', 'png'];
        $ekstensigambar = strtolower(pathinfo($namefile, PATHINFO_EXTENSION));

        if (!in_array($ekstensigambar, $ekstensivalid)) {
           $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Format gambar harus JPG, JPEG, atau PNG!');
            echo "<script>history.back();</script>";
            exit();
        }

        // Validate file type and size
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['payment_proof']['type'], $allowedTypes)) {
            throw new Exception("Format file tidak didukung. Hanya JPEG, PNG, atau PDF");
        }
        
        if ($_FILES['payment_proof']['size'] > $maxSize) {
            throw new Exception("Ukuran file terlalu besar. Maksimal 2MB");
        }

        // Create upload directory if not exists
        $uploadDir = '../uploads/payments/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $extension = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
        $filename = 'payment_' . $bookingId . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($_FILES['payment_proof']['tmp_name'], $targetPath)) {
            throw new Exception("Gagal mengupload bukti pembayaran");
        }

        // Update booking and payment status
        $pdo->beginTransaction();
        
        try {
            // Status remains 'unpaid' until verified by admin
            // We only update the payment proof in the payments table
            
            // Check if payment record exists
            $stmt = $pdo->prepare("SELECT id FROM payments WHERE booking_id = ?");
            $stmt->execute([$bookingId]);
            $paymentExists = $stmt->fetch();
            
            if ($paymentExists) {
                // Update existing payment record
                $stmt = $pdo->prepare("UPDATE payments 
                                      SET status = 'pending', 
                                          waktu_pembayaran = NOW(),
                                          bukti_pembayaran = ?
                                      WHERE booking_id = ?");
                $stmt->execute([$filename, $bookingId]);
            } else {
                // Create new payment record
                $stmt = $pdo->prepare("INSERT INTO payments 
                                      (booking_id, status, waktu_pembayaran, bukti_pembayaran) 
                                      VALUES (?, 'pending', NOW(), ?)");
                $stmt->execute([$bookingId, $filename]);
            }
            
            $pdo->commit();
            
            // Redirect to confirmation page
            header("Location: booking_confirmation.php?booking_id=$bookingId&awaiting_verification=1");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            // Delete uploaded file if transaction failed
            if (file_exists($targetPath)) {
                unlink($targetPath);
            }
            throw $e;
        }
    }

} catch (Exception $e) {
    error_log("Payment error: " . $e->getMessage());
    $errorMessage = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .payment-container {
            max-width: 800px;
            margin: 2rem auto;
        }
        .bank-info {
            background-color: #f1f8ff;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .movie-poster {
            height: 150px;
            object-fit: cover;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container payment-container">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0">Pembayaran</h2>
            </div>
            <div class="card-body">
                <?php if (isset($errorMessage)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <img src="<?= htmlspecialchars($booking['poster_url']) ?>" alt="Movie Poster" class="img-fluid movie-poster">
                    </div>
                    <div class="col-md-9">
                        <h4><?= htmlspecialchars($booking['movie_title']) ?></h4>
                        <p class="text-muted">
                            <?= date('l, d F Y', strtotime($booking['tanggal'])) ?> | 
                            <?= date('H:i', strtotime($booking['jam_mulai'])) ?>
                        </p>
                        <p><strong>Kode Booking:</strong> <?= htmlspecialchars($booking['kode_booking']) ?></p>
                        <p><strong>Total Pembayaran:</strong> Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></p>
                    </div>
                </div>
                
                <div class="bank-info">
                    <h4>Transfer Bank</h4>
                    <p>Silakan transfer sesuai dengan metode pembayaran yang dipilih:</p>
                    
                    <?php if ($booking['metode_pembayaran'] === 'bca'): ?>
                        <div class="bank-details">
                            <h5>Bank BCA</h5>
                            <p><strong>Nomor Rekening:</strong> 123 456 7890</p>
                            <p><strong>Atas Nama:</strong> <?= APP_NAME ?></p>
                            <p><strong>Jumlah Transfer:</strong> Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></p>
                            <p class="text-muted">Harap transfer tepat sampai 3 digit terakhir untuk memudahkan verifikasi</p>
                        </div>
                    <?php elseif ($booking['metode_pembayaran'] === 'bni'): ?>
                        <div class="bank-details">
                            <h5>Bank BNI</h5>
                            <p><strong>Nomor Rekening:</strong> 987 654 3210</p>
                            <p><strong>Atas Nama:</strong> <?= APP_NAME ?></p>
                            <p><strong>Jumlah Transfer:</strong> Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></p>
                        </div>
                    <?php elseif ($booking['metode_pembayaran'] === 'mandiri'): ?>
                        <div class="bank-details">
                            <h5>Bank Mandiri</h5>
                            <p><strong>Nomor Rekening:</strong> 456 789 1230</p>
                            <p><strong>Atas Nama:</strong> <?= APP_NAME ?></p>
                            <p><strong>Jumlah Transfer:</strong> Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></p>
                        </div>
                    <?php elseif ($booking['metode_pembayaran'] === 'gopay'): ?>
                        <div class="bank-details">
                            <h5>Gopay</h5>
                            <p><strong>Nomor Gopay:</strong> 0812 3456 7890</p>
                            <p><strong>Atas Nama:</strong> <?= APP_NAME ?></p>
                            <p><strong>Jumlah Transfer:</strong> Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></p>
                        </div>
                    <?php elseif ($booking['metode_pembayaran'] === 'ovo'): ?>
                        <div class="bank-details">
                            <h5>OVO</h5>
                            <p><strong>Nomor OVO:</strong> 0812 3456 7890</p>
                            <p><strong>Atas Nama:</strong> <?= APP_NAME ?></p>
                            <p><strong>Jumlah Transfer:</strong> Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></p>
                        </div>
                    <?php elseif ($booking['metode_pembayaran'] === 'dana'): ?>
                        <div class="bank-details">
                            <h5>Dana</h5>
                            <p><strong>Nomor Dana:</strong> 0812 3456 7890</p>
                            <p><strong>Atas Nama:</strong> <?= APP_NAME ?></p>
                            <p><strong>Jumlah Transfer:</strong> Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Silakan selesaikan pembayaran melalui <?= strtoupper($booking['metode_pembayaran']) ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="alert alert-warning">
                    <strong>Catatan:</strong> Setelah Anda upload bukti pembayaran, tim admin kami akan memverifikasi pembayaran Anda dalam waktu 1x24 jam.
                </div>
                
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="payment_proof" class="form-label">Upload Bukti Pembayaran</label>
                        <input class="form-control" type="file" id="payment_proof" name="payment_proof" required accept="image/jpeg,image/png,application/pdf">
                        <div class="form-text">Format: JPEG, PNG, atau PDF (maks. 2MB)</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Upload Bukti Pembayaran</button>
                        <a href="booking_confirmation.php?booking_id=<?= $bookingId ?>" class="btn btn-outline-secondary">
                            Kembali
                        </a>
                    </div>
                </form>
            </div>
            <div class="card-footer text-muted">
                Pembayaran akan diverifikasi dalam 1x24 jam. Status booking akan tetap 'unpaid' hingga admin memverifikasi bukti pembayaran Anda.
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>