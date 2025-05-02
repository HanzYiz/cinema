<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../../api/config.php';
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

function jsonResponse($success, $message = '', $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Log incoming request
error_log("Incoming request: " . file_get_contents('php://input'));

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['studio_id']) || !isset($data['occupied_seats'])) {
    jsonResponse(false, 'Invalid request data');
}

// Debug logging
error_log("Processing studio: " . $data['studio_id']);
error_log("Seats to update: " . implode(', ', $data['occupied_seats']));

try {
    $pdo->beginTransaction();

    // 1. Verify studio exists first instead of checking seats
    $checkStudioStmt = $pdo->prepare("
        SELECT id 
        FROM studios 
        WHERE id = ? 
        LIMIT 1
    ");
    $checkStudioStmt->execute([$data['studio_id']]);
    
    if ($checkStudioStmt->rowCount() === 0) {
        throw new Exception("Studio tidak ditemukan");
    }

    // 2. Reset all seats first
    $resetStmt = $pdo->prepare("
        UPDATE seats 
        SET status = 'available' 
        WHERE studio_id = ?
    ");
    $resetStmt->execute([$data['studio_id']]);

    // 3. Update occupied seats one by one
    $updateStmt = $pdo->prepare("
        UPDATE seats 
        SET status = 'booked' 
        WHERE studio_id = ? 
        AND baris = ? 
        AND nomor_kursi = ?
    ");

    $updated = 0;
    foreach ($data['occupied_seats'] as $seat) {
        // Ekstrak baris (huruf) dan nomor kursi (angka) dari format seperti "A1"
        preg_match('/([A-Z])(\d+)/', $seat, $matches);
        
        if (count($matches) >= 3) {
            $baris = $matches[1]; // Huruf baris (A, B, C, dll)
            $nomorKursi = $matches[2]; // Nomor kursi (1, 2, 3, dll)
            
            if ($updateStmt->execute([$data['studio_id'], $baris, $nomorKursi])) {
                $updated += $updateStmt->rowCount();
            }
        }
    }

    $pdo->commit();
    
    jsonResponse(true, "Updated $updated seats", [
        'studio_id' => $data['studio_id'],
        'updated_count' => $updated
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("DB Error: " . $e->getMessage());
    jsonResponse(false, "Database error: " . $e->getMessage());
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error: " . $e->getMessage());
    jsonResponse(false, $e->getMessage());
}