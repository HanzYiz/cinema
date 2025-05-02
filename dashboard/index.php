<?php
include "../api/config.php"; // Include your config file
// Assume $user_data and $bookings are fetched from your backend
include '../components/alert.php'; // Include alert component
ini_set('display_errors', 1);
error_reporting(E_ALL);


if (isset($_SESSION['showDisruptiveAlert'])) { 
  echo $_SESSION['showDisruptiveAlert'];
  unset($_SESSION['showDisruptiveAlert']); 
} 

if (!isset($_SESSION['user_id'])) {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!','Anda harus login terlebih dahulu!');
    header("Location: ../pages/login.php"); // Redirect to login if not logged in
    exit();
}


      //ambil data user dari session
        $user_id = $_SESSION['user_id'];
          $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
          $stmt->execute([$user_id]);
           $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
          if (!$user_data) {
                // Handle case when user not found
                die("User not found");
            }

            // 2. Fetch User's Bookings with Movie Details
            $stmt = $pdo->prepare("SELECT 
                b.id, b.kode_booking, b.total_harga, b.status, b.waktu_pesan,
                s.tanggal, s.jam_mulai,
                m.judul as movie_title, m.poster_url, m.durasi, m.genre,
                stu.nama as studio_name
                FROM bookings b
                JOIN schedules s ON b.schedule_id = s.id
                JOIN movies m ON s.movie_id = m.id
                JOIN studios stu ON s.studio_id = stu.id
                WHERE b.user_id = ?
                ORDER BY b.waktu_pesan DESC
                LIMIT 5");
            $stmt->execute([$user_id]);
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. Count Watched Movies (paid bookings)
            $stmt = $pdo->prepare("SELECT COUNT(*) as total_watched 
                FROM bookings 
                WHERE user_id = ? AND status = 'paid'");
            $stmt->execute([$user_id]);
            $total_watched = $stmt->fetchColumn();

            // 4. Count Active Bookings (pending/pending_verification)
            $stmt = $pdo->prepare("SELECT COUNT(*) as active_bookings 
                FROM bookings 
                WHERE user_id = ? AND status IN ('pending', 'pending_verification')");
            $stmt->execute([$user_id]);
            $active_bookings = $stmt->fetchColumn();

            // 5. Get Seats for Each Booking
            foreach ($bookings as &$booking) {
                $stmt = $pdo->prepare("SELECT 
                    s.baris, s.nomor_kursi 
                    FROM booking_seats bs
                    JOIN seats s ON bs.seat_id = s.id
                    WHERE bs.booking_id = ?");
                $stmt->execute([$booking['id']]);
                $seats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Format seats as "A1, A2" etc
                $booking['seats'] = array_map(function($seat) {
                    return $seat['baris'] . $seat['nomor_kursi'];
                }, $seats);
                $booking['seat_list'] = implode(', ', $booking['seats']);
            }
            unset($booking); // Break the reference
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard User - Bioskop Kita</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
  <div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3">
        <i class="fas fa-ticket-alt text-primary"></i> Dashboard User
      </h1>
      <a href="../api/logout.php" class="btn btn-outline-danger">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>

    <!-- Profile Section -->
    <div class="card profile-card mb-4">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-2 text-center">
            <img src="../assets/img/<?= htmlspecialchars($user_data['foto']) ?>" class="profile-pic rounded-circle mb-3" alt="Profile">
          </div>
          <div class="col-md-6">
            <h3 class="mb-1"><?= htmlspecialchars($user_data['username']) ?></h3>
            <p class="text-muted mb-1">
              <i class="fas fa-envelope"></i> <?= htmlspecialchars($user_data['email']) ?>
            </p>
            <p class="text-muted">
              <i class="fas fa-calendar-alt"></i> Member sejak <?= date('d M Y', strtotime($user_data['created_at'])) ?>
            </p>
          </div>
          <div class="col-md-4 text-end">
            <a href="profile/edit.php" class="btn btn-primary">
              <i class="fas fa-user-edit"></i> Edit Profil
            </a>
          </div>
        </div>
      </div>
    </div>


    <!-- Quick Stats -->
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card stat-card h-100">
          <div class="card-body">
            <h5 class="card-title text-muted">Total Film Ditonton</h5>
            <h2 class="text-primary"><?= $total_watched ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card stat-card h-100">
          <div class="card-body">
            <h5 class="card-title text-muted">Pemesanan Aktif</h5>
            <h2 class="text-primary"><?= $active_bookings ?></h2>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="d-flex gap-2 mb-4">
      <a href="../pages/booking" class="btn btn-primary flex-grow-1">
        <i class="fas fa-ticket-alt"></i> Pesan Tiket Baru
      </a>
      <a href="promo.php" class="btn btn-outline-primary">
        <i class="fas fa-tag"></i> Lihat Promo
      </a>
      <a href="profile/edit.php" class="btn btn-outline-secondary">
        <i class="fas fa-lock"></i> Ganti Password
      </a>
    </div>

    <!-- Booking History -->
    <div class="card mb-4">
      <div class="card-header bg-white">
        <h3 class="h5 mb-0">
          <i class="fas fa-history text-primary"></i> Riwayat Pemesanan Terakhir
        </h3>
      </div>
      <div class="card-body">
        <?php if (empty($bookings)): ?>
          <div class="alert alert-info">
            Anda belum memiliki riwayat pemesanan.
          </div>
        <?php else: ?>
          <?php foreach ($bookings as $booking): ?>
            <div class="card booking-card mb-3">
              <div class="card-body">
                <div class="row align-items-center">
                  <div class="col-md-2">
                    <img src="<?= htmlspecialchars($booking['poster_url']) ?>" class="movie-poster w-100" alt="Movie Poster">
                  </div>
                  <div class="col-md-6">
                    <h5 class="mb-1"><?= htmlspecialchars($booking['movie_title']) ?></h5>
                    <p class="text-muted mb-1">
                      <i class="fas fa-calendar-day"></i> <?= date('d M Y', strtotime($booking['tanggal'])) ?>, <?= date('H:i', strtotime($booking['jam_mulai'])) ?>
                    </p>
                    <p class="mb-1">
                      <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($booking['studio_name']) ?> • 
                      <i class="fas fa-chair"></i> <?= htmlspecialchars($booking['seat_list']) ?>
                    </p>
                    <p class="mb-0">
                      <i class="fas fa-receipt"></i> Kode Booking: <strong><?= htmlspecialchars($booking['kode_booking']) ?></strong>
                    </p>
                  </div>
                  <div class="col-md-2 text-center">
                    <span class="badge bg-<?= 
                        $booking['status'] === 'paid' ? 'success' : 
                        ($booking['status'] === 'pending_verification' ? 'warning' : 'secondary')
                    ?> rounded-pill p-2">
                        <?= ucfirst($booking['status']) ?>
                    </span>
                    <p class="mt-2 mb-0 <?= $booking['status'] === 'paid' ? 'text-success fw-bold' : 'text-muted' ?>">
                        Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?>
                    </p>
                  </div>
                  <div class="col-md-2 text-end">
                    <a href="../api/booking/booking_confirmation.php?booking_id=<?= $booking['id'] ?>" class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-eye"></i> Detail
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <!-- View All Button -->
        <div class="text-center mt-3">
          <a href="../api/booking/booking_history.php" class="btn btn-outline-primary">
            <i class="fas fa-list"></i> Lihat Semua Riwayat
          </a>
        </div>
      </div>
    </div>

    <!-- Notifications -->
    <div class="card">
      <div class="card-header bg-white">
        <h3 class="h5 mb-0">
          <i class="fas fa-bell text-primary"></i> Notifikasi
        </h3>
      </div>
      <div class="card-body">
        <div class="alert alert-info">
          <i class="fas fa-film"></i> <strong>Avengers: Endgame</strong> akan tayang besok pukul 14:00 WIB.
        </div>
        <div class="alert alert-success">
          <i class="fas fa-tag"></i> Promo spesial! Diskon 20% untuk semua film hari Senin.
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>