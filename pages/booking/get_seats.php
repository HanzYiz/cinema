<?php
// Include database configuration
include '../../api/config.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'seats' => [],
    'occupied_seats' => []
];

// Check if schedule_id is provided
if (!isset($_GET['schedule_id']) || empty($_GET['schedule_id'])) {
    $response['message'] = 'Parameter schedule_id tidak ditemukan';
    echo json_encode($response);
    exit;
}

$schedule_id = $_GET['schedule_id']; // Keep as string since IDs are varchar in DB

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // 1. Get schedule information to identify studio
    $scheduleQuery = $pdo->prepare("
        SELECT s.id, s.movie_id, s.studio_id, s.tanggal, s.jam_mulai as jam_mulai, 
               st.nama as studios_nama, st.kapasitas as kapasitas
        FROM schedules s
        JOIN studios st ON s.studio_id = st.id
        WHERE s.id = ?
    ");
    
    $scheduleQuery->execute([$schedule_id]);
    $schedule = $scheduleQuery->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        // Debug info
        error_log("Schedule not found: " . $schedule_id);
        throw new Exception('Jadwal tidak ditemukan');
    }
    
    // 2. Get all seats for the studio
    $seatsQuery = $pdo->prepare("
        SELECT id, studio_id, nomor_kursi, baris, status
        FROM seats
        WHERE studio_id = ?
        ORDER BY baris, nomor_kursi
    ");
    
    $seatsQuery->execute([$schedule['studio_id']]);
    $seats = $seatsQuery->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Get booked seats for this schedule
    $occupiedQuery = $pdo->prepare("
        SELECT bs.seat_id, s.nomor_kursi, s.baris
        FROM booking_seats bs
        JOIN bookings b ON bs.booking_id = b.id
        JOIN seats s ON bs.seat_id = s.id
        WHERE b.schedule_id = ? AND b.status != 'cancelled'
    ");
    
    $occupiedQuery->execute([$schedule_id]);
    $occupiedSeats = $occupiedQuery->fetchAll(PDO::FETCH_ASSOC);
    
    // Extract just the seat IDs for simpler checking
    $occupiedSeatIds = array_map(function($seat) {
        return $seat['seat_id'];
    }, $occupiedSeats);
    
    // Commit transaction
    $pdo->commit();
    
    // Prepare successful response
    $response['success'] = true;
    $response['seats'] = $seats;
    $response['occupied_seats'] = $occupiedSeatIds;
    $response['schedule'] = $schedule;
    
} catch (Exception $e) {
    // Roll back transaction on error
    $pdo->rollBack();
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>