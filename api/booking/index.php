<?php
include '../config.php';
require_once __DIR__ . '/../../components/alert.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'users') {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Anda tidak memiliki akses ke halaman ini!');
   header("Location: ../../api/login_ext.php");
   exit();
}


?>