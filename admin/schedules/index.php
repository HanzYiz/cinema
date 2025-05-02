<?php
require_once '../../api/config.php';
require_once '../../components/alert.php';


// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('warning', 'Gagal!', 'Akses ditolak!');
    header("Location: ../../api/login_ext.php");
    exit();
}

if (isset($_SESSION['showDisruptiveAlert'])) { 
    echo $_SESSION['showDisruptiveAlert'];
    unset($_SESSION['showDisruptiveAlert']); 
  } 

// Ambil parameter filter
$filter_movie = $_GET['movie'] ?? '';
$filter_date = $_GET['date'] ?? '';

// Query untuk mengambil jadwal
try {
    $query = "SELECT s.*, m.judul as movie_title, st.nama as studio_name 
              FROM schedules s
              JOIN movies m ON s.movie_id = m.id
              JOIN studios st ON s.studio_id = st.id
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($filter_movie)) {
        $query .= " AND s.movie_id = ?";
        $params[] = $filter_movie;
    }
    
    if (!empty($filter_date)) {
        $query .= " AND s.tanggal = ?";
        $params[] = $filter_date;
    }
    
    $query .= " ORDER BY s.tanggal DESC, s.jam_mulai DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching schedules: " . $e->getMessage());
    $schedules = [];
}

// Ambil daftar film untuk filter
try {
    $moviesStmt = $pdo->prepare("SELECT id, judul FROM movies ORDER BY judul");
    $moviesStmt->execute();
    $movies = $moviesStmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Daftar Jadwal - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <?php include '../payment/admin-navbar.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-calendar-event"></i> Daftar Jadwal Tayang</h2>
            <a href="add.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah Jadwal
            </a>
        </div>

        <!-- Filter -->
        <div class="card mb-4 shadow">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-5">
                        <label for="movie" class="form-label">Filter Film</label>
                        <select class="form-select" id="movie" name="movie">
                            <option value="">Semua Film</option>
                            <?php foreach ($movies as $movie): ?>
                                <option value="<?= htmlspecialchars($movie['id']) ?>" <?= $filter_movie === $movie['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($movie['judul']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="date" class="form-label">Filter Tanggal</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($filter_date) ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Jadwal -->
        <div class="card shadow">
            <div class="card-body">
                <?php if (empty($schedules)): ?>
                    <div class="alert alert-info">Tidak ada jadwal yang tersedia</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Film</th>
                                    <th>Studio</th>
                                    <th>Harga</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $schedule): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($schedule['tanggal'])) ?></td>
                                        <td>
                                            <?= date('H:i', strtotime($schedule['jam_mulai'])) ?> - 
                                            <?= date('H:i', strtotime($schedule['jam_selesai'])) ?>
                                        </td>
                                        <td><?= htmlspecialchars($schedule['movie_title']) ?></td>
                                        <td><?= htmlspecialchars($schedule['studio_name']) ?></td>
                                        <td>Rp <?= number_format($schedule['harga'], 0, ',', '.') ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="edit.php?id=<?= $schedule['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="delete.php?id=<?= $schedule['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus jadwal ini?')">
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
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Inisialisasi datepicker untuk filter
        flatpickr("#date", {
            dateFormat: "Y-m-d"
        });
    </script>
</body>
</html>