<?php
include '../api/config.php';
include '../components/alert.php';
session_start();

if (isset($_SESSION['showDisruptiveAlert'])) { 
  echo $_SESSION['showDisruptiveAlert'];
  unset($_SESSION['showDisruptiveAlert']); 
} 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home - Bioskop Kita</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/home.css">
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
      <a class="navbar-brand" href="home.php">
        <i class="fas fa-film"></i> CINEMA XXI
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Kalau sudah login -->
            <li class="nav-item">
              <a class="nav-link" href="../dashboard/"><i class="fas fa-user"></i> Profile</a>
            </li>
            <li class="nav-item ms-2">
              <a href="../api/logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
          <?php else: ?>
            <!-- Kalau belum login -->
            <li class="nav-item">
              <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
            </li>
            <li class="nav-item ms-2">
              <a href="register.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Daftar</a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
   <?php if (isset($_SESSION['user_id'])): ?>
  <section class="hero-section">
    <div class="hero-content text-center text-white">
      <h1 class="text-shadow display-4">HanzYiz Bioskop</h1>
      <a href="booking" class="btn btn-warning btn-lg mt-3">
        <i class="fas fa-ticket-alt"></i> Pesan Sekarang
      </a>
    </div>
  </section>
  <?php else: ?>
    <section class="hero-section">
    <div class="hero-content text-center text-white">
      <h1 class="text-shadow display-4">HanzYiz Bioskop</h1>
      <a href="login.php" class="btn btn-warning btn-lg mt-3">
        <i class="fas fa-ticket-alt"></i> Pesan Sekarang
      </a>
    </div>
  </section>
  <?php endif; ?>
  <!-- Now Playing -->
  <div class="container py-5">
  <h2 class="text-center mb-4"><i class="fas fa-play-circle"></i> Sedang Tayang</h2>
  <div class="row g-4">
    <?php

    $ket = "now_playing";

    try {
      $stmt = $pdo->prepare("SELECT * FROM movies WHERE status = ?");
      $stmt->execute([$ket]);
      $result = $stmt->fetchAll(); // result = array
    } catch (PDOException $e) {
      echo '<div class="alert alert-danger">Terjadi kesalahan: ' . htmlspecialchars($e->getMessage()) . '</div>';
      $result = [];
    }

    if (!empty($result)):
      foreach ($result as $row):
    ?>
      <div class="col-md-4">
        <div class="card movie-card h-100">
          <img src="<?= htmlspecialchars($row['poster_url']) ?>" class="card-img-top" alt="Movie Poster">
          <div class="card-body d-flex flex-column">
            <h3 class="card-title"><?= htmlspecialchars($row['judul']) ?></h3>
            <p class="card-text">
              <span class="badge bg-info me-1"><?= htmlspecialchars($row['genre']) ?></span>
              <span class="badge bg-secondary">
                <?php 
                  $jam = floor($row['durasi'] / 60);
                  $menit = $row['durasi'] % 60;
                  echo "{$jam}h {$menit}m";
                ?>
              </span>
            </p>
            <a href="booking" class="btn btn-primary mt-auto w-100">
              <i class="fas fa-shopping-cart"></i> Pesan
            </a>
          </div>
        </div>
      </div>
    <?php
      endforeach;
    else:
    ?>
      <div class="col-12">
        <p class="text-center">Belum ada film yang sedang tayang.</p>
      </div>
    <?php endif; ?>
  </div>
</div>


  <!-- Footer -->
  <footer class="bg-dark text-center py-4 text-white mt-5">
    &copy; 2025 Bioskop Kita
  </footer>

  <!-- Bootstrap 5 JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
