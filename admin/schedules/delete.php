<?php
require_once '../../api/config.php';
require_once '../../components/alert.php';

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('warning', 'Gagal!', 'Akses ditolak!');
    header("Location: ../../api/login_ext.php");
    exit();
}

// Proses hapus jadwal
if (isset($_GET['id'])) {
    try {
        // Periksa apakah jadwal memiliki booking terkait
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE schedule_id = ?");
        $stmt->execute([$_GET['id']]);
        $bookingCount = $stmt->fetchColumn();
        
        if ($bookingCount > 0) {
            $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Jadwal memiliki booking terkait dan tidak dapat dihapus.');
            header("Location: index.php");
            exit();
        }
        
        // Hapus jadwal jika tidak ada booking terkait
        $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('success', 'Berhasil!', 'Jadwal berhasil dihapus.');
    } catch (PDOException $e) {
        error_log("Error deleting schedule: " . $e->getMessage());
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Gagal menghapus jadwal.');
    }
}

header("Location: list.php");
exit();
?>