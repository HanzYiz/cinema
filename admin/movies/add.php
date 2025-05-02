<?php
require_once '../../api/config.php';
require_once '../../components/alert.php';

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('warning', 'Gagal!', 'Akses ditolak!');
    header("Location: ../../api/login_ext.php");
    exit();
}

// Proses form tambah film
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = $_POST['judul'] ?? '';
    $poster_url = $_POST['poster_url'] ?? '';
    $durasi = $_POST['durasi'] ?? 0;
    $genre = $_POST['genre'] ?? '';
    $sutradara = $_POST['sutradara'] ?? '';
    $pemain = $_POST['pemain'] ?? '';
    $sinopsis = $_POST['sinopsis'] ?? '';
    $rating = $_POST['rating'] ?? 0;
    $status = $_POST['status'] ?? 'coming_soon';

    try {
        $stmt = $pdo->prepare("INSERT INTO movies (id, judul, poster_url, durasi, genre, sutradara, pemain, sinopsis, rating, status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Generate random ID
        $id = uniqid('movie', true);
        
        $stmt->execute([$id, $judul, $poster_url, $durasi, $genre, $sutradara, $pemain, $sinopsis, $rating, $status]);
        
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('success', 'Berhasil!', 'Film berhasil ditambahkan.');
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        error_log("Error adding movie: " . $e->getMessage());
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Gagal menambahkan film.');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Film - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../payment/admin-navbar.php'; ?>

    <div class="container py-4">
        <h2 class="mb-4"><i class="bi bi-plus-circle"></i> Tambah Film Baru</h2>
        
        <div class="card shadow">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="judul" class="form-label">Judul Film</label>
                                <input type="text" class="form-control" id="judul" name="judul" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="poster_url" class="form-label">URL Poster</label>
                                <input type="url" class="form-control" id="poster_url" name="poster_url" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="durasi" class="form-label">Durasi (menit)</label>
                                <input type="number" class="form-control" id="durasi" name="durasi" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="genre" class="form-label">Genre</label>
                                <input type="text" class="form-control" id="genre" name="genre" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sutradara" class="form-label">Sutradara</label>
                                <input type="text" class="form-control" id="sutradara" name="sutradara">
                            </div>
                            
                            <div class="mb-3">
                                <label for="pemain" class="form-label">Pemain (pisahkan dengan koma)</label>
                                <input type="text" class="form-control" id="pemain" name="pemain">
                            </div>
                            
                            <div class="mb-3">
                                <label for="rating" class="form-label">Rating</label>
                                <input type="number" step="0.1" min="0" max="10" class="form-control" id="rating" name="rating">
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="coming_soon">Coming Soon</option>
                                    <option value="now_playing">Now Playing</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sinopsis" class="form-label">Sinopsis</label>
                        <textarea class="form-control" id="sinopsis" name="sinopsis" rows="5"></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan Film
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>