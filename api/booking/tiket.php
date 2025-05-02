<?php
require_once __DIR__ . '/../config.php';

require_once __DIR__ . '/../../components/alert.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'users') {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Anda tidak memiliki akses ke halaman ini!');
   header("Location: ../../api/login_ext.php");
   exit();
}

// Check if booking_id is provided
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    header("Location: ../../pages/home.php");
    exit();
}

$bookingJam = $pdo ->prepare("SELECT waktu_pembayaran FROM payments WHERE booking_id = ?");
$bookingJam->execute([$_GET['booking_id']]);
$bookingJam = $bookingJam->fetchColumn();
if ($bookingJam) {
    $bookingJam = date('Y-m-d H:i', strtotime($bookingJam));
} else {
    $bookingJam = null;
}

$bookingId = sanitize_input($_GET['booking_id']);

try {
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
                          m.genre,
                          stu.nama as studio_name,
                          stu.lokasi as studio_location,
                          p.metode as payment_method,
                          p.waktu_pembayaran,
                          u.nama as customer_name,
                          u.email as customer_email
                       FROM bookings b
                       JOIN schedules s ON b.schedule_id = s.id
                       JOIN movies m ON s.movie_id = m.id
                       JOIN studios stu ON s.studio_id = stu.id
                       JOIN payments p ON b.id = p.booking_id
                       JOIN users u ON b.user_id = u.id
                       WHERE b.id = ? AND b.status = 'paid'");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        throw new Exception("Tiket tidak ditemukan atau pembayaran belum diverifikasi");
    }

    // Fetch booked seats
    $stmt = $pdo->prepare("SELECT 
                          s.baris,
                          s.nomor_kursi
                       FROM booking_seats bs
                       JOIN seats s ON bs.seat_id = s.id
                       WHERE bs.booking_id = ?");
    $stmt->execute([$bookingId]);
    $seats = $stmt->fetchAll();

    // Format seat information
    $seatList = array_map(function($seat) {
        return $seat['baris'] . $seat['nomor_kursi'];
    }, $seats);
    $seatInfo = implode(', ', $seatList);

} catch (Exception $e) {
    error_log("Error fetching ticket: " . $e->getMessage());
    die("Terjadi kesalahan saat memuat tiket. Silakan coba lagi.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket - <?= htmlspecialchars($booking['movie_title']) ?> | <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- QR Code library -->
    <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .ticket-container {
            max-width: 600px;
            margin: 2rem auto;
        }
        .ticket-card {
            border: 2px solid #0d6efd;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .ticket-header {
            background-color: #0d6efd;
            color: white;
            padding: 0.75rem;
            text-align: center;
        }
        .ticket-body {
            padding: 1.5rem;
            background-color: white;
        }
        .ticket-qr {
            width: 120px;
            height: 120px;
            margin: 0 auto;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .ticket-movie-poster {
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }
        .ticket-detail {
            margin-bottom: 0.5rem;
        }
        .ticket-detail-label {
            font-weight: bold;
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        .ticket-footer {
            background-color: #f8f9fa;
            padding: 0.5rem;
            text-align: center;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        /* Tampilan layar normal */
        @media screen {
            h3 { font-size: 1.5rem; }
            h4 { font-size: 1.2rem; }
            .ticket-container { margin: 2rem auto; }
            .ticket-card { box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); }
            .ticket-detail-value { font-size: 1rem; }
        }
        
        /* Optimasi khusus untuk print */
        @media print {
            @page {
                size: A4;
                margin: 0;
            }
            
            html, body {
                width: 210mm;
                height: 297mm;
                margin: 0;
                padding: 0;
                font-size: 12px;
                background-color: white;
            }
            
            body * {
                visibility: hidden;
            }
            
            .ticket-container, .ticket-container * {
                visibility: visible;
            }
            
            .ticket-container {
                position: absolute;
                left: 50%;
                top: 0;
                transform: translateX(-50%);
                width: 190mm;
                max-width: 190mm;
                margin: 5mm auto;
                padding: 0;
                page-break-inside: avoid;
                page-break-after: always;
            }
            
            .ticket-card {
                border: 1px solid #ccc;
                box-shadow: none;
                page-break-inside: avoid;
                margin: 0;
                width: 100%;
            }
            
            .navbar, .no-print, footer, .btn {
                display: none !important;
            }
            
            /* Memperkecil semua elemen */
            .ticket-header {
                padding: 0.5rem;
            }
            
            .ticket-header h3 {
                font-size: 16px !important;
                margin: 0 !important;
            }
            
            .ticket-header p {
                font-size: 12px !important;
                margin: 0 !important;
            }
            
            .ticket-body {
                padding: 0.75rem;
            }
            
            h4 {
                font-size: 14px !important;
                margin: 0.25rem 0 !important;
            }
            
            .text-muted {
                font-size: 10px !important;
            }
            
            .ticket-detail-label {
                font-size: 11px !important;
                margin-bottom: 0 !important;
            }
            
            .ticket-detail-value, .ticket-detail div:not(.ticket-detail-label) {
                font-size: 12px !important;
                margin-bottom: 0 !important;
            }
            
            .ticket-movie-poster {
                height: 120px;
            }
            
            .ticket-footer {
                padding: 0.3rem;
                font-size: 10px;
            }
            
            .ticket-footer p {
                margin: 0 !important;
            }
            
            /* Memastikan QR code muat dan tidak terpisah */
            .ticket-qr {
                width: 100px;
                height: 100px;
                margin: 0.5rem auto;
            }
            
            hr {
                margin: 0.5rem 0 !important;
            }
            
            /* Mengurangi margin dalam row/col */
            .row {
                margin-right: -5px !important;
                margin-left: -5px !important;
                margin-bottom: 0.5rem !important;
            }
            
            .col-md-4, .col-md-6, .col-md-8 {
                padding-right: 5px !important;
                padding-left: 5px !important;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container ticket-container">
        <div class="ticket-card">
            <div class="ticket-header">
                <h3><i class="bi bi-ticket-perforated"></i> E-Ticket</h3>
                <p><?= APP_NAME ?></p>
            </div>
            
            <div class="ticket-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <img src="<?= htmlspecialchars($booking['poster_url']) ?>" alt="Movie Poster" class="img-fluid ticket-movie-poster">
                    </div>
                    <div class="col-md-8">
                        <h4><?= htmlspecialchars($booking['movie_title']) ?></h4>
                        <p class="text-muted"><?= htmlspecialchars($booking['genre']) ?> • <?= floor($booking['durasi']/60) ?>j <?= $booking['durasi']%60 ?>m</p>
                        
                        <!-- Tanggal & Waktu - Berdampingan dengan poster -->
                        <div class="ticket-detail">
                            <div class="ticket-detail-label">Tanggal & Waktu</div>
                            <div><?= date('l, d F Y', strtotime($booking['tanggal'])) ?></div>
                            <div><?= date('H:i', strtotime($booking['jam_mulai'])) ?> - <?= date('H:i', strtotime($booking['jam_selesai'])) ?></div>
                        </div>
                        
                        <!-- Studio - Berdampingan dengan poster -->
                        <div class="ticket-detail">
                            <div class="ticket-detail-label">Studio</div>
                            <div><?= htmlspecialchars($booking['studio_name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($booking['studio_location']) ?></small>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="ticket-detail">
                            <div class="ticket-detail-label">Kursi</div>
                            <div><?= htmlspecialchars($seatInfo) ?></div>
                        </div>
                        
                        <div class="ticket-detail">
                            <div class="ticket-detail-label">Jumlah Tiket</div>
                            <div><?= count($seats) ?> kursi</div>
                        </div>
                        
                        <div class="ticket-detail">
                            <div class="ticket-detail-label">Kode Booking</div>
                            <div class="fw-bold"><?= htmlspecialchars($booking['kode_booking']) ?></div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="ticket-detail">
                            <div class="ticket-detail-label">Total Pembayaran</div>
                            <div class="fw-bold">Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></div>
                        </div>
                        
                        <div class="ticket-detail">
                            <div class="ticket-detail-label">Metode Pembayaran</div>
                            <div><?= strtoupper(htmlspecialchars($booking['payment_method'])) ?></div>
                            <small class="text-muted">
                                <?php 
                               
                                    // Periksa apakah waktu_pembayaran NULL
                                    if ($booking['waktu_pembayaran'] !== null) {
                                        echo date('Y-m-d H:i', strtotime($booking['waktu_pembayaran']));
                                    } else {
                                        echo "Pembayaran terkonfirmasi";
                                    }
                                ?>
                            </small>
                        </div>
                        
                        <div class="ticket-detail">
                            <div class="ticket-detail-label">Nama Pemesan</div>
                            <div><?= htmlspecialchars($booking['customer_name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($booking['customer_email']) ?></small>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <div class="ticket-qr mb-2" id="qrCode">
                        <!-- QR Code akan dihasilkan di sini -->
                    </div>
                    <p class="text-muted small mb-0">Tunjukkan tiket ini di loket bioskop</p>
                </div>
            </div>
            
            <div class="ticket-footer">
                <p class="mb-0"><?= APP_NAME ?> • <?= date('Y') ?></p>
                <p class="small mb-0">No refund or exchange after purchase</p>
            </div>
        </div>
        
        <div class="text-center mt-3 no-print">
            <button onclick="window.print()" class="btn btn-primary me-2">
                <i class="bi bi-printer"></i> Cetak Tiket
            </button>
            <a href="../../dashboard" class="btn btn-outline-secondary">
                <i class="bi bi-house"></i> Kembali ke Beranda
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Generate QR code
        document.addEventListener('DOMContentLoaded', function() {
            // Pastikan elemen target sudah ada
            const qrContainer = document.getElementById('qrCode');
            if (!qrContainer) {
                console.error('Element with ID "qrCode" not found');
                return;
            }
            
            try {
                const qrData = "BOOKING:<?= htmlspecialchars($booking['kode_booking']) ?>\n" +
                    "MOVIE:<?= htmlspecialchars($booking['movie_title']) ?>\n" +
                    "SEATS:<?= htmlspecialchars($seatInfo) ?>\n" +
                    "DATE:<?= date('Y-m-d', strtotime($booking['tanggal'])) ?>\n" +
                    "TIME:<?= date('H:i', strtotime($booking['jam_mulai'])) ?>\n" +
                    "STUDIO:<?= htmlspecialchars($booking['studio_name']) ?>";
                
                // Buat objek QRCode baru
                new QRCode(qrContainer, {
                    text: qrData,
                    width: 100,
                    height: 100,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
                
                console.log('QR code berhasil dibuat!');
            } catch (error) {
                console.error('Gagal membuat QR code:', error);
                qrContainer.innerHTML = '<div class="text-danger">Gagal membuat QR code: ' + error.message + '</div>';
            }
        });
    </script>
</body>
</html>