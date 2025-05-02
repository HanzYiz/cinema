<?php
include "../config.php";
include '../../components/alert.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Authentication check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'users') {
     $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Anda tidak memiliki akses ke halaman ini!');
    header("Location: ../../api/login_ext.php");
    exit();
}


$user_id = $_SESSION['user_id'];

// Filter parameters
$filter_status = isset($_GET['status']) && !empty($_GET['status']) ? $_GET['status'] : null;
$filter_date_from = isset($_GET['date_from']) && !empty($_GET['date_from']) ? $_GET['date_from'] : null;
$filter_date_to = isset($_GET['date_to']) && !empty($_GET['date_to']) ? $_GET['date_to'] : null;

// Pagination setup
$perPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// Build query with filters
$query_conditions = ["b.user_id = ?"];
$query_params = [$user_id];

if ($filter_status) {
    $query_conditions[] = "b.status = ?";
    $query_params[] = $filter_status;
}

if ($filter_date_from) {
    $query_conditions[] = "s.tanggal >= ?";
    $query_params[] = $filter_date_from;
}

if ($filter_date_to) {
    $query_conditions[] = "s.tanggal <= ?";
    $query_params[] = $filter_date_to;
}

$where_clause = implode(" AND ", $query_conditions);

// Get total bookings for pagination with filters
$count_query = "SELECT COUNT(*) FROM bookings b 
                JOIN schedules s ON b.schedule_id = s.id 
                WHERE $where_clause";
$stmt = $pdo->prepare($count_query);
$stmt->execute($query_params);
$totalBookings = $stmt->fetchColumn();
$totalPages = ceil($totalBookings / $perPage);

// Get bookings with pagination and filters
$bookings_query = "SELECT 
    b.id, b.kode_booking, b.total_harga, b.status, b.waktu_pesan,
    s.tanggal, s.jam_mulai, s.jam_selesai,
    m.judul as movie_title, m.poster_url, m.durasi, m.genre,
    stu.nama as studio_name
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    JOIN movies m ON s.movie_id = m.id
    JOIN studios stu ON s.studio_id = stu.id
    WHERE $where_clause
    ORDER BY b.waktu_pesan DESC
    LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($bookings_query);
$query_params[] = $perPage;
$query_params[] = $offset;
$stmt->execute($query_params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get seats for each booking
foreach ($bookings as &$booking) {
    $stmt = $pdo->prepare("SELECT 
        s.baris, s.nomor_kursi 
        FROM booking_seats bs
        JOIN seats s ON bs.seat_id = s.id
        WHERE bs.booking_id = ?");
    $stmt->execute([$booking['id']]);
    $seats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $booking['seats'] = array_map(function($seat) {
        return $seat['baris'] . $seat['nomor_kursi'];
    }, $seats);
    $booking['seat_list'] = implode(', ', $booking['seats']);
}
unset($booking); // Break the reference

// Preserve filter parameters for pagination links
$pagination_params = [];
if ($filter_status) $pagination_params[] = "status=" . urlencode($filter_status);
if ($filter_date_from) $pagination_params[] = "date_from=" . urlencode($filter_date_from);
if ($filter_date_to) $pagination_params[] = "date_to=" . urlencode($filter_date_to);
$pagination_query_string = !empty($pagination_params) ? '&' . implode('&', $pagination_params) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pemesanan - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .booking-card {
            transition: transform 0.2s;
            margin-bottom: 1rem;
        }
        .booking-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .status-badge {
            font-size: 0.85rem;
            padding: 0.35em 0.65em;
        }
        .movie-poster-thumb {
            width: 80px;
            height: 120px;
            object-fit: cover;
            border-radius: 4px;
        }
        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="fas fa-history text-primary"></i> Riwayat Pemesanan
            </h2>
            <a href="../pages/booking" class="btn btn-primary">
                <i class="fas fa-plus"></i> Pesan Tiket Baru
            </a>
        </div>

        <!-- Filter Section -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="pending" <?= isset($_GET['status']) && $_GET['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="paid" <?= isset($_GET['status']) && $_GET['status'] == 'paid' ? 'selected' : '' ?>>Berhasil</option>
                            <option value="cancelled" <?= isset($_GET['status']) && $_GET['status'] == 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">Dari Tanggal</label>
                        <input type="date" name="date_from" id="date_from" class="form-control" value="<?= $_GET['date_from'] ?? '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to" class="form-label">Sampai Tanggal</label>
                        <input type="date" name="date_to" id="date_to" class="form-control" value="<?= $_GET['date_to'] ?? '' ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="booking_history.php" class="btn btn-outline-secondary">
                            <i class="fas fa-sync-alt"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Booking List -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($bookings)): ?>
                    <div class="alert alert-info text-center py-4">
                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                        <h4>Tidak ada riwayat pemesanan</h4>
                        <p class="mb-0">Anda belum memiliki riwayat pemesanan tiket</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Film</th>
                                    <th>Tanggal Tayang</th>
                                    <th>Studio</th>
                                    <th>Kursi</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?= htmlspecialchars($booking['poster_url']) ?>" 
                                                     class="movie-poster-thumb me-3" 
                                                     alt="<?= htmlspecialchars($booking['movie_title']) ?>">
                                                <div>
                                                    <strong><?= htmlspecialchars($booking['movie_title']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($booking['genre']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?= date('d M Y', strtotime($booking['tanggal'])) ?><br>
                                            <small class="text-muted"><?= date('H:i', strtotime($booking['jam_mulai'])) ?> - <?= date('H:i', strtotime($booking['jam_selesai'])) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($booking['studio_name']) ?></td>
                                        <td><?= htmlspecialchars($booking['seat_list']) ?></td>
                                        <td>
                                            <span class="badge status-badge bg-<?= 
                                                $booking['status'] === 'paid' ? 'success' : 
                                                ($booking['status'] === 'pending_verification' ? 'warning' : 
                                                ($booking['status'] === 'cancelled' ? 'danger' : 'secondary'))
                                            ?>">
                                                <?= ucfirst(str_replace('_', ' ', $booking['status'])) ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold">Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></td>
                                        <td>
                                            <a href="booking_confirmation.php?booking_id=<?= $booking['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary"
                                               title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($booking['status'] === 'paid'): ?>
                                                <a href="tiket.php?booking_id=<?= $booking['id'] ?>" 
                                                   class="btn btn-sm btn-outline-success"
                                                   title="Lihat Tiket">
                                                    <i class="fas fa-ticket-alt"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page-1 ?><?= $pagination_query_string ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?><?= $pagination_query_string ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page+1 ?><?= $pagination_query_string ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple filter enhancement
        document.addEventListener('DOMContentLoaded', function() {
            // Only set default dates if none provided in URL
            const hasFilters = <?= (isset($_GET['status']) || isset($_GET['date_from']) || isset($_GET['date_to'])) ? 'true' : 'false' ?>;
            
            if ($filter_status) {
    // Mapping status filter ke nilai database
                $status_mapping = [
                    'pending' => ['pending', 'pending_verification'],
                    'paid' => ['paid'],
                    'cancelled' => ['cancelled']
                ];
                
                if (isset($status_mapping[$filter_status])) {
                    $placeholders = implode(',', array_fill(0, count($status_mapping[$filter_status]), '?'));
                    $query_conditions[] = "b.status IN ($placeholders)";
                    $query_params = array_merge($query_params, $status_mapping[$filter_status]);
                } else {
                    // Fallback ke filter langsung
                    $query_conditions[] = "b.status = ?";
                    $query_params[] = $filter_status;
                }
            }

            if (!hasFilters) {
                // Set today as default end date if none provided
                if (!document.getElementById('date_to').value) {
                    document.getElementById('date_to').valueAsDate = new Date();
                }
                
                // Set one month ago as default start date if none provided
                if (!document.getElementById('date_from').value) {
                    const date = new Date();
                    date.setMonth(date.getMonth() - 1);
                    document.getElementById('date_from').valueAsDate = date;
                }
            }
        });
    </script>
</body>
</html>