<?php
require_once '../../api/config.php';
require_once '../../components/alert.php';

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('warning', 'Gagal!', 'Akses ditolak!');
    header("Location: ../../api/login_ext.php");
    exit();
}

// Ambil data jadwal yang akan diedit
$schedule = null;
if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT s.*, m.durasi 
                             FROM schedules s
                             JOIN movies m ON s.movie_id = m.id
                             WHERE s.id = ?");
        $stmt->execute([$_GET['id']]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching schedule: " . $e->getMessage());
    }
}

if (!$schedule) {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Jadwal tidak ditemukan.');
    header("Location: list.php");
    exit();
}

// Ambil daftar film dan studio
try {
    $moviesStmt = $pdo->prepare("SELECT id, judul FROM movies WHERE status = 'now_playing' ORDER BY judul");
    $moviesStmt->execute();
    $movies = $moviesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching movies: " . $e->getMessage());
    $movies = [];
}

try {
    $studiosStmt = $pdo->prepare("SELECT id, nama FROM studios ORDER BY nama");
    $studiosStmt->execute();
    $studios = $studiosStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching studios: " . $e->getMessage());
    $studios = [];
}

// Proses form edit jadwal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movie_id = $_POST['movie_id'] ?? '';
    $studio_id = $_POST['studio_id'] ?? '';
    $tanggal = $_POST['tanggal'] ?? '';
    $jam_mulai = $_POST['jam_mulai'] ?? '';
    $harga = $_POST['harga'] ?? 0;

    // Validasi input
    if (empty($movie_id) || empty($studio_id) || empty($tanggal) || empty($jam_mulai) || empty($harga)) {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('warning', 'Gagal!', 'Semua field harus diisi.');
    } else {
        try {
            // Hitung jam selesai baru
            $durasiStmt = $pdo->prepare("SELECT durasi FROM movies WHERE id = ?");
            $durasiStmt->execute([$movie_id]);
            $durasi = $durasiStmt->fetchColumn();

            if (!$durasi) {
                throw new Exception("Durasi film tidak ditemukan");
            }

            $jam_mulai_obj = new DateTime($tanggal . ' ' . $jam_mulai);
            $jam_selesai_obj = clone $jam_mulai_obj;
            $jam_selesai_obj->add(new DateInterval('PT' . $durasi . 'M'));
            $jam_selesai = $jam_selesai_obj->format('H:i:s');

            // Update database
            $stmt = $pdo->prepare("UPDATE schedules SET 
                                  movie_id = ?, studio_id = ?, tanggal = ?, 
                                  jam_mulai = ?, jam_selesai = ?, harga = ?
                                  WHERE id = ?");
            
            $stmt->execute([$movie_id, $studio_id, $tanggal, $jam_mulai, $jam_selesai, $harga, $schedule['id']]);
            
            $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('success', 'Berhasil!', 'Jadwal berhasil diperbarui.');
            header("Location: index.php");
            exit();
        } catch (PDOException $e) {
            error_log("Error updating schedule: " . $e->getMessage());
            $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Gagal memperbarui jadwal: ' . $e->getMessage());
        } catch (Exception $e) {
            error_log("Error calculating end time: " . $e->getMessage());
            $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Gagal menghitung waktu selesai: ' . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Jadwal - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <?php include '../payment/admin-navbar.php'; ?>

    <div class="container py-4">
        <h2 class="mb-4"><i class="bi bi-pencil"></i> Edit Jadwal</h2>
        
        <div class="card shadow">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="movie_id" class="form-label">Film</label>
                                <select class="form-select" id="movie_id" name="movie_id" required>
                                    <option value="">Pilih Film</option>
                                    <?php foreach ($movies as $movie): ?>
                                        <option value="<?= htmlspecialchars($movie['id']) ?>" <?= ($schedule['movie_id'] === $movie['id'] || (isset($_POST['movie_id']) && $_POST['movie_id'] === $movie['id'])) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($movie['judul']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="studio_id" class="form-label">Studio</label>
                                <select class="form-select" id="studio_id" name="studio_id" required>
                                    <option value="">Pilih Studio</option>
                                    <?php foreach ($studios as $studio): ?>
                                        <option value="<?= htmlspecialchars($studio['id']) ?>" <?= ($schedule['studio_id'] === $studio['id'] || (isset($_POST['studio_id']) && $_POST['studio_id'] === $studio['id'])) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($studio['nama']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tanggal" class="form-label">Tanggal Tayang</label>
                                <input type="date" class="form-control" id="tanggal" name="tanggal" 
                                       value="<?= htmlspecialchars($_POST['tanggal'] ?? $schedule['tanggal']) ?>" required
                                       min="<?= date('Y-m-d') ?>">
                            </div>

                            <div class="mb-3">
                                <label for="jam_mulai" class="form-label">Jam Mulai</label>
                                <input type="time" class="form-control" id="jam_mulai" name="jam_mulai" 
                                       value="<?= htmlspecialchars($_POST['jam_mulai'] ?? substr($schedule['jam_mulai'], 0, 5)) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="harga" class="form-label">Harga Tiket (Rp)</label>
                                <input type="number" class="form-control" id="harga" name="harga" 
                                       value="<?= htmlspecialchars($_POST['harga'] ?? $schedule['harga']) ?>" min="10000" step="5000" required>
                            </div>
                        </div>
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
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Inisialisasi datepicker
        flatpickr("#tanggal", {
            minDate: "today",
            dateFormat: "Y-m-d"
        });
    </script>
</body>
</html>