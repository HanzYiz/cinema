<?php
require_once '../../api/config.php';
require_once '../../components/alert.php';

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('warning', 'Gagal!', 'Akses ditolak!');
    header("Location: ../../api/login_ext.php");
    exit();
}

// Proses hapus film
if (isset($_GET['id'])) {
    try {
        // Periksa apakah film memiliki jadwal terkait
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM schedules WHERE movie_id = ?");
        $stmt->execute([$_GET['id']]);
        $scheduleCount = $stmt->fetchColumn();
        
        if ($scheduleCount > 0) {
            $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Film memiliki jadwal terkait dan tidak dapat dihapus.');
            header("Location: list.php");
            exit();
        }
        
        // Hapus film jika tidak ada jadwal terkait
        $stmt = $pdo->prepare("DELETE FROM movies WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('success', 'Berhasil!', 'Film berhasil dihapus.');
    } catch (PDOException $e) {
        error_log("Error deleting movie: " . $e->getMessage());
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Gagal menghapus film.');
    }
}

header("Location: list.php");
exit();
?>