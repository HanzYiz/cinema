<?php
require_once '../../api/config.php';
require_once '../../components/alert.php';

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('warning', 'Gagal!', 'Akses ditolak!');
    header("Location: ../../api/login_ext.php");
    exit();
}

// Ambil data film yang akan diedit
$movie = null;
if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $movie = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching movie: " . $e->getMessage());
    }
}

if (!$movie) {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Film tidak ditemukan.');
    header("Location: list.php");
    exit();
}

// Proses form edit film
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
        $stmt = $pdo->prepare("UPDATE movies SET 
                             judul = ?, poster_url = ?, durasi = ?, genre = ?, 
                             sutradara = ?, pemain = ?, sinopsis = ?, rating = ?, status = ?
                             WHERE id = ?");
        
        $stmt->execute([$judul, $poster_url, $durasi, $genre, $sutradara, $pemain, $sinopsis, $rating, $status, $movie['id']]);
        
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('success', 'Berhasil!', 'Film berhasil diperbarui.');
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        error_log("Error updating movie: " . $e->getMessage());
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Gagal memperbarui film.');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Film - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../payment/admin-navbar.php'; ?>

    <div class="container py-4">
        <h2 class="mb-4"><i class="bi bi-pencil"></i> Edit Film</h2>
        
        <div class="card shadow">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="judul" class="form-label">Judul Film</label>
                                <input type="text" class="form-control" id="judul" name="judul" 
                                       value="<?= htmlspecialchars($movie['judul']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="poster_url" class="form-label">URL Poster</label>
                                <input type="url" class="form-control" id="poster_url" name="poster_url" 
                                       value="<?= htmlspecialchars($movie['poster_url']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="durasi" class="form-label">Durasi (menit)</label>
                                <input type="number" class="form-control" id="durasi" name="durasi" 
                                       value="<?= htmlspecialchars($movie['durasi']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="genre" class="form-label">Genre</label>
                                <input type="text" class="form-control" id="genre" name="genre" 
                                       value="<?= htmlspecialchars($movie['genre']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sutradara" class="form-label">Sutradara</label>
                                <input type="text" class="form-control" id="sutradara" name="sutradara" 
                                       value="<?= htmlspecialchars($movie['sutradara']) ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="pemain" class="form-label">Pemain (pisahkan dengan koma)</label>
                                <input type="text" class="form-control" id="pemain" name="pemain" 
                                       value="<?= htmlspecialchars($movie['pemain']) ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="rating" class="form-label">Rating</label>
                                <input type="number" step="0.1" min="0" max="10" class="form-control" id="rating" name="rating" 
                                       value="<?= htmlspecialchars($movie['rating']) ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="coming_soon" <?= $movie['status'] === 'coming_soon' ? 'selected' : '' ?>>Coming Soon</option>
                                    <option value="now_playing" <?= $movie['status'] === 'now_playing' ? 'selected' : '' ?>>Now Playing</option>
                                    <option value="archived" <?= $movie['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sinopsis" class="form-label">Sinopsis</label>
                        <textarea class="form-control" id="sinopsis" name="sinopsis" rows="5"><?= htmlspecialchars($movie['sinopsis']) ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="list.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>