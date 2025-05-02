<?php
include '../../api/config.php';

header('Content-Type: application/json');

// Ambil parameter
$movie_id = $_GET['movie_id'] ?? '';
$date = $_GET['date'] ?? '';

// Debugging
error_log("MOCK SCHEDULES REQUEST: movie_id=$movie_id, date=$date");

try {
    // Query ke database
    $query = $pdo->prepare("
        SELECT 
            s.id,
            s.movie_id,
            s.studio_id,
            st.nama AS studio_nama,
            st.kapasitas AS studio_kapasitas,
            DATE_FORMAT(s.jam_mulai, '%H:%i') AS time,
            (st.kapasitas - IFNULL((
                SELECT COUNT(*) 
                FROM booking_seats bs
                JOIN bookings b ON bs.booking_id = b.id
                WHERE b.schedule_id = s.id AND b.status != 'cancelled'
            ), 0)) AS available_seats
        FROM schedules s
        JOIN studios st ON s.studio_id = st.id
        WHERE s.movie_id = ? AND s.tanggal = ?
        ORDER BY s.jam_mulai ASC
    ");
    
    $query->execute([$movie_id, $date]);
    $schedules = $query->fetchAll(PDO::FETCH_ASSOC);

    // Format response konsisten dengan frontend
    echo json_encode([
        'success' => true,
        'schedules' => array_map(function($schedule) {
            return [
                'id' => $schedule['id'],
                'studio_id' => $schedule['studio_id'],
                'studio_nama' => $schedule['studio_nama'],
                'studio_kapasitas' => (int)$schedule['studio_kapasitas'],
                'time' => $schedule['time'],
                'available_seats' => (int)$schedule['available_seats']
            ];
        }, $schedules)
    ]);

} catch (PDOException $e) {
    // Fallback ke mock data statis jika database error
    error_log("DATABASE ERROR: " . $e->getMessage());
    
    $fallbackData = [
        [
            'id' => 'schedule1',
            'studio_id' => 'studio1',
            'studio_nama' => 'Studio 1',
            'studio_kapasitas' => 100,
            'time' => '10:00',
            'available_seats' => 85
        ],
        [
            'id' => 'schedule2',
            'studio_id' => 'studio2',
            'studio_nama' => 'Studio 2',
            'studio_kapasitas' => 80,
            'time' => '13:00',
            'available_seats' => 75
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'schedules' => $fallbackData
    ]);
}
?>