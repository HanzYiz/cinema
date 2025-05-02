<?php
include '../../api/config.php';
header('Content-Type: application/json');

if (!isset($_GET['studio_id']) || !isset($_GET['schedule_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID studio dan schedule wajib diisi']);
    exit;
}

$studioId = $_GET['studio_id'];
$scheduleId = $_GET['schedule_id'];

try {
    $query = $pdo->prepare("
        SELECT CONCAT(s.baris, s.nomor_kursi) AS nomor_kursi
        FROM booking_seats bs
        JOIN bookings b ON bs.booking_id = b.id
        JOIN seats s ON bs.seat_id = s.id
        WHERE b.schedule_id = ? AND s.studio_id = ? AND b.status = 'paid'
    ");

    $query->execute([$scheduleId, $studioId]);
    $bookedSeats = $query->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'booked_seats' => $bookedSeats]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
