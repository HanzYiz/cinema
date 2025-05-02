<?php
include '../../api/config.php';

// Get parameters
$movie_id = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';

// Validate input
if ($movie_id <= 0 || empty($date)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid parameters'
    ]);
    exit;
}

try {
    // Query schedules
    $query = $pdo->prepare("
        SELECT s.id, s.time, s.studio_id, 
               st.name AS studio_nama, st.capacity AS studio_kapasitas,
               (st.capacity - COALESCE(b.booked_seats, 0)) AS available_seats
        FROM schedules s
        JOIN studios st ON s.studio_id = st.id
        LEFT JOIN (
            SELECT schedule_id, COUNT(*) AS booked_seats 
            FROM bookings 
            WHERE tanggal = :date AND status != 'cancelled'
            GROUP BY schedule_id
        ) b ON s.id = b.schedule_id
        WHERE s.movie_id = :movie_id AND s.tanggal = :date
        ORDER BY s.time ASC
    ");
    
    $query->execute([
        ':movie_id' => $movie_id,
        ':date' => $date
    ]);
    
    $schedules = $query->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'schedules' => $schedules
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>