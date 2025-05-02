<?php
// Database connection parameters
$host = 'localhost:3306';
$dbname = 'cinema';
$username = 'root'; // Change this to your MySQL username
$password = ''; // Change this to your MySQL password

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Sample schedule data based on your screenshot
    $schedules = [
        [   
            'id' => 'shcedule1',
            'movie_id' => 'movie1',
            'studio_id' => 'studio1',
            'tanggal' => '2025-04-22',
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '12:00:00',
            'harga' => 45000.00
        ],
        [
            'id' => 'shcedule2',
            'movie_id' => 'movie2',
            'studio_id' => 'studio2',
            'tanggal' => '2025-04-22',
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '12:30:00',
            'harga' => 45000.00
        ]
    ];
    
    // Insert schedules
    $stmt = $pdo->prepare("
        INSERT INTO schedules 
        (id ,movie_id, studio_id, tanggal, jam_mulai, jam_selesai, harga) 
        VALUES 
        (:id, :movie_id, :studio_id, :tanggal, :jam_mulai, :jam_selesai, :harga)
    ");
    
    foreach ($schedules as $schedule) {
        $stmt->execute([
            ':id' => $schedule['id'],
            ':movie_id' => $schedule['movie_id'],
            ':studio_id' => $schedule['studio_id'],
            ':tanggal' => $schedule['tanggal'],
            ':jam_mulai' => $schedule['jam_mulai'],
            ':jam_selesai' => $schedule['jam_selesai'],
            ':harga' => $schedule['harga']
        ]);
        
        echo "Inserted schedule for movie {$schedule['movie_id']} at {$schedule['tanggal']} {$schedule['jam_mulai']}<br>";
    }
    
    echo "All schedules inserted successfully!";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>