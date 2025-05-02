<?php
include '../../api/config.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID studio tidak valid']);
    exit;
}

$studioId = $_GET['id'];

try {
    $query = $pdo->prepare("SELECT * FROM studios WHERE id = ?");
    $query->execute([$studioId]);
    $studio = $query->fetch(PDO::FETCH_ASSOC);
    
    if (!$studio) {
        echo json_encode(['success' => false, 'message' => 'Studio tidak ditemukan']);
        exit;
    }
    
    echo json_encode(['success' => true, 'studio' => $studio]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>