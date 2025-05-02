<?php
include "config.php";
include "../components/alert.php";
session_start();

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'user' && $_SESSION['role'] !== 'admin')) {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!','Harap Login Terlebih Dahulu!');
    header("Location: login_ext.php");
    exit();
}
?>
