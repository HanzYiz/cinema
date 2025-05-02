<?php
require_once __DIR__ . '/../../api/config.php';
require_once __DIR__ . '/../../components/alert.php';

if (isset($_SESSION['showDisruptiveAlert'])) { 
    echo $_SESSION['showDisruptiveAlert'];
    unset($_SESSION['showDisruptiveAlert']); 
  } 
// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    if (isset($_SESSION['role'])) {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Anda tidak memiliki akses ke halaman ini!');
        header("Location: ../../dashboard");
    } else {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Harap Login Terlebih Dahulu!');
        header("Location: ../../api/login_ext.php");
    }
    exit();
}

// Filter berdasarkan status
$statusFilter = sanitize_input($_GET['status'] ?? '');
$validStatuses = ['pending', 'paid', 'cancelled'];
$statusFilter = in_array($statusFilter, $validStatuses) ? $statusFilter : '';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

try {
    // Query dasar
    $query = "SELECT b.id, b.kode_booking, b.total_harga, b.status, b.waktu_pesan,
                     p.metode, p.status as payment_status, p.waktu_pembayaran,
                     m.judul as movie_title,
                     u.nama as customer_name
              FROM bookings b
              JOIN payments p ON b.id = p.booking_id
              JOIN schedules s ON b.schedule_id = s.id
              JOIN movies m ON s.movie_id = m.id
              JOIN users u ON b.user_id = u.id";
    
    // Tambahkan filter
    $where = [];
    $params = [];
    
    if (!empty($statusFilter)) {
        $where[] = "b.status = ?";
        $params[] = $statusFilter;
    }
    
    if (!empty($_GET['search'])) {
        $search = '%' . sanitize_input($_GET['search']) . '%';
        $where[] = "(b.kode_booking LIKE ? OR m.judul LIKE ? OR u.nama LIKE ?)";
        array_push($params, $search, $search, $search);
    }
    
    if (!empty($where)) {
        $query .= " WHERE " . implode(" AND ", $where);
    }
    
    // Query untuk data
    $dataQuery = $query . " ORDER BY b.waktu_pesan DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($dataQuery);
    
    // Bind parameter
    $paramCount = count($params);
    for ($i = 0; $i < $paramCount; $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    $stmt->bindValue($paramCount + 1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue($paramCount + 2, $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $payments = $stmt->fetchAll();
    
    // Query untuk total
    $countQuery = "SELECT COUNT(*) as total FROM ($query) as subquery";
    $stmt = $pdo->prepare($countQuery);
    
    for ($i = 0; $i < $paramCount; $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    
    $stmt->execute();
    $total = $stmt->fetchColumn();
    $totalPages = ceil($total / $perPage);
    
} catch (PDOException $e) {
    error_log("Error fetching payments: " . $e->getMessage());
    $payments = [];
    $totalPages = 1;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pembayaran - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'admin-navbar.php'; ?>

    <div class="container py-4">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Pembayaran</li>
            </ol>
        </nav>

        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-credit-card"></i> Daftar Pembayaran</h5>
                    <div>
                        <a href="list.php" class="btn btn-sm btn-light">Reset Filter</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter dan Pencarian -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <form method="get" class="row g-2">
                            <div class="col-md-6">
                                <select name="status" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>pending</option>
                                    <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>>Paid</option>
                                    <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form method="get">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Cari kode booking, film, atau customer..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabel Pembayaran -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Kode Booking</th>
                                <th>Film</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Metode</th>
                                <th>Status</th>
                                <th>Waktu</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">Tidak ada data pembayaran</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($payment['kode_booking']) ?></td>
                                        <td><?= htmlspecialchars($payment['movie_title']) ?></td>
                                        <td><?= htmlspecialchars($payment['customer_name']) ?></td>
                                        <td>Rp <?= number_format($payment['total_harga'], 0, ',', '.') ?></td>
                                        <td><?= strtoupper($payment['metode']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $payment['status'] === 'paid' ? 'success' : 
                                                ($payment['status'] === 'pending_verification' ? 'warning' : 'danger')
                                            ?>">
                                                <?= ucfirst(str_replace('_', ' ', $payment['status'])) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($payment['waktu_pesan'])) ?></td>
                                        <td>
                                            <a href="verify.php?id=<?= $payment['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= 
                                    http_build_query(array_merge(
                                        $_GET,
                                        ['page' => $page - 1]
                                    )) 
                                ?>">Previous</a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= 
                                        http_build_query(array_merge(
                                            $_GET,
                                            ['page' => $i]
                                        )) 
                                    ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= 
                                    http_build_query(array_merge(
                                        $_GET,
                                        ['page' => $page + 1]
                                    )) 
                                ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>