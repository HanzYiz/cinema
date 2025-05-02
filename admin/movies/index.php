<?php
require_once '../../api/config.php';
require_once '../../components/alert.php';

if (isset($_SESSION['showDisruptiveAlert'])) { 
    echo $_SESSION['showDisruptiveAlert'];
    unset($_SESSION['showDisruptiveAlert']); 
  } 

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role'])) {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('warning', 'Gagal!', 'Silakan login terlebih dahulu!');
    header("Location: ../../api/login_ext.php");
    exit();
} else if ($_SESSION['role'] !== 'admin') {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('warning', 'Gagal!', 'Akses ditolak!');
    header("Location: ../dashboard");
    exit();
}

// Ambil data film dari database
try {
    $stmt = $pdo->prepare("SELECT * FROM movies ORDER BY created_at DESC");
    $stmt->execute();
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching movies: " . $e->getMessage());
    $movies = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Film - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../payment/admin-navbar.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-film"></i> Daftar Film</h2>
            <a href="add.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah Film
            </a>
        </div>

        <!-- Filter Status -->
        <div class="mb-3">
            <div class="btn-group" role="group">
                <a href="index.php?status=now_playing" class="btn btn-outline-primary">Now Playing</a>
                <a href="index.php?status=coming_soon" class="btn btn-outline-secondary">Coming Soon</a>
                <a href="index.php?status=archived" class="btn btn-outline-dark">Archived</a>
                <a href="index.php?" class="btn btn-outline-success">Semua</a>
            </div>
        </div>

        <!-- Tabel Film -->
        <div class="card shadow">
            <div class="card-body">
                <?php if (empty($movies)): ?>
                    <div class="alert alert-info">Tidak ada film yang tersedia</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Poster</th>
                                    <th>Judul</th>
                                    <th>Durasi</th>
                                    <th>Genre</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movies as $movie): ?>
                                    <tr>
                                        <td>
                                            <?php if ($movie['poster_url']): ?>
                                                <img src="<?= htmlspecialchars($movie['poster_url']) ?>" alt="Poster" style="width: 60px; height: 90px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light d-flex align-items-center justify-content-center" style="width: 60px; height: 90px;">
                                                    <i class="bi bi-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($movie['judul']) ?></td>
                                        <td><?= htmlspecialchars($movie['durasi']) ?> menit</td>
                                        <td><?= htmlspecialchars($movie['genre']) ?></td>
                                        <td><?= htmlspecialchars($movie['rating']) ?></td>
                                        <td>
                                            <?php 
                                            $statusClass = [
                                                'now_playing' => 'success',
                                                'coming_soon' => 'warning',
                                                'archived' => 'danger'
                                            ];
                                            ?>
                                            <span class="badge bg-<?= $statusClass[$movie['status']] ?>">
                                                <?= ucfirst(str_replace('_', ' ', $movie['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="edit.php?id=<?= $movie['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="delete.php?id=<?= $movie['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus film ini?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>