<?php
include 'config.php';
include '../components/alert.php';

// 1. Gunakan session untuk alert
$_SESSION['logout_alert'] = true; // Hanya flag

// 2. Bersihkan data user tapi biarkan session hidup
unset($_SESSION['user_id']); 
unset($_SESSION['role']);


// 3. Redirect
header("Location: ../pages/login.php");
exit();
?>