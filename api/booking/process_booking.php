<?php
require_once __DIR__ . '../../config.php';
require_once __DIR__ . '/../../components/alert.php';


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'users') {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Anda tidak memiliki akses ke halaman ini!');
   header("Location: ../../api/login_ext.php");
   exit();
}
header('Content-Type: application/json');

try {
    // 1. Get and validate input data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    $requiredFields = ['schedule_id', 'seats', 'customer_name', 'customer_email', 'payment_method', 'total_price'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $scheduleId = sanitize_input($data['schedule_id']);
    $seats = $data['seats'];
    $customerName = sanitize_input($data['customer_name']);
    $customerEmail = sanitize_input($data['customer_email']);
    $paymentMethod = sanitize_input($data['payment_method']);
    $totalPrice = (float)$data['total_price'];

    if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    if (!is_array($seats) || count($seats) === 0) {
        throw new Exception('No seats selected');
    }

    // 2. Verify schedule exists and get studio_id
    $stmt = $pdo->prepare("SELECT s.*, m.judul as movie_title, s.studio_id 
                          FROM schedules s 
                          JOIN movies m ON s.movie_id = m.id 
                          WHERE s.id = ?");
    $stmt->execute([$scheduleId]);
    $schedule = $stmt->fetch();

    if (!$schedule) {
        throw new Exception('Schedule not found');
    }

    // 3. Start transaction
    $pdo->beginTransaction();

    // 4. Generate unique booking code
    $bookingCode = 'BK-' . strtoupper(uniqid());

    // 5. Create booking record
    $bookingId = bin2hex(random_bytes(16));
    $insertBooking = $pdo->prepare("INSERT INTO bookings 
                                  (id, user_id, schedule_id, kode_booking, total_harga, status, metode_pembayaran) 
                                  VALUES (?, ?, ?, ?, ?, 'pending', ?)");
    
    $UserId = $_SESSION['user_id'] ?? null;
    if (!$UserId) {
        throw new Exception('User not logged in');
    }
    $insertBooking->execute([
        $bookingId,
        $UserId,
        $scheduleId,
        $bookingCode,
        $totalPrice,
        $paymentMethod
    ]);

    // 6. Process each seat (convert frontend format to database format)
    foreach ($seats as $frontendSeat) {
        // Parse seat format (e.g., "G4" to baris="G", nomor_kursi="4")
        if (!preg_match('/^([A-J])(\d+)$/', $frontendSeat, $matches)) {
            throw new Exception("Invalid seat format: $frontendSeat");
        }
        
        $baris = $matches[1];
        $nomor_kursi = $matches[2];
        
        // Get the actual seat_id from database
        $stmt = $pdo->prepare("SELECT id FROM seats 
                              WHERE studio_id = ? 
                              AND baris = ? 
                              AND nomor_kursi = ?");
        $stmt->execute([$schedule['studio_id'], $baris, $nomor_kursi]);
        $seat = $stmt->fetch();
        
        if (!$seat) {
            throw new Exception("Seat $frontendSeat not found in studio");
        }
        
        $seatId = $seat['id'];

        // Check seat availability
        $checkSeat = $pdo->prepare("SELECT status FROM seats WHERE id = ? FOR UPDATE");
        $checkSeat->execute([$seatId]);
        $seatStatus = $checkSeat->fetch();

        if ($seatStatus['status'] !== 'available') {
            throw new Exception("Seat $frontendSeat is no longer available");
        }

        // Reserve the seat
        $updateSeat = $pdo->prepare("UPDATE seats SET status = 'booked' WHERE id = ?");
        $updateSeat->execute([$seatId]);

        // Add to booking_seats
        $insertBookingSeat = $pdo->prepare("INSERT INTO booking_seats 
                                          (booking_id, seat_id, harga) 
                                          VALUES (?, ?, ?)");
        $insertBookingSeat->execute([
            $bookingId,
            $seatId,
            $schedule['harga']
        ]);
    }

    // 7. Create payment record
    $paymentId = bin2hex(random_bytes(16));
    $insertPayment = $pdo->prepare("INSERT INTO payments 
                                   (id, booking_id, jumlah, metode, status) 
                                   VALUES (?, ?, ?, ?, 'pending')");
    $insertPayment->execute([
        $paymentId,
        $bookingId,
        $totalPrice,
        $paymentMethod
    ]);

    // 8. Commit transaction
    $pdo->commit();

    // 9. Return success response
    echo json_encode([
        'success' => true,
        'booking_id' => $bookingId,
        'booking_code' => $bookingCode,
        'message' => 'Booking successful'
    ]);

} catch (Exception $e) {
    // Rollback transaction if there was an error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Booking error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}