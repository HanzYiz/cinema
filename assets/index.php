<?php
require "../api/config.php";
require "../components/alert.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Anda tidak memiliki akses ke halaman ini!');
   header("Location: ../api/login_ext.php");
   exit();
}


?>