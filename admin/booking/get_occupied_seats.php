<?php
include '../../api/config.php';
require_once __DIR__ . '/../../components/alert.php';

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

// Pastikan studio_id ada
if (!isset($_GET['studio_id'])) {
    echo json_encode(['success' => false, 'message' => 'Parameter studio_id diperlukan']);
    exit;
}

$studio_id = $_GET['studio_id'];

try {
    // Query kursi dengan status 'booked' (terisi)
    $query = $pdo->prepare("
        SELECT baris, nomor_kursi
        FROM seats 
        WHERE studio_id = :studio_id 
        AND status = 'booked'");
    $query->execute(['studio_id' => $studio_id]);
    $results = $query->fetchAll(PDO::FETCH_ASSOC);
    
    // Format ulang hasil query ke format A1, B2, dst
    $occupied_seats = [];
    foreach ($results as $row) {
        $occupied_seats[] = $row['baris'] . $row['nomor_kursi'];
    }

    // Kembalikan daftar kursi yang terisi (occupied)
    echo json_encode([
        'success' => true,
        'occupied_seats' => $occupied_seats // Kursi yang sudah terisi (booked)
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>