<?php
include 'config.php'; // sudah ada session_start di config.php
include "../components/alert.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (isset($_POST['submit'])) {
    // Cek apakah ada file yang diunggah
    $namafile = $_FILES['foto']['name'];
    $ukuranfile = $_FILES['foto']['size'];
    $error = $_FILES['foto']['error'];
    $tmpname = $_FILES['foto']['tmp_name'];

    // Jika tidak ada file, gunakan foto default
    $namabaru = 'default.jpeg';

    if ($error === 0) {
        // Cek apakah file yang diunggah adalah gambar
        $ekstensivalid = ['jpg', 'jpeg', 'png'];
        $ekstensigambar = strtolower(pathinfo($namafile, PATHINFO_EXTENSION));
        
        if (!in_array($ekstensigambar, $ekstensivalid)) {
           $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!','Format gambar harus JPG, JPEG, atau PNG!');
            header("Location: ../pages/register.php");
            exit();
        }

        // Cek ukuran gambar
        if ($ukuranfile > 4000000) {
            $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!','Ukuran gambar terlalu besar!');
            header("Location: ../pages/register.php");
            exit();
        }

        // Generate nama unik untuk gambar
        $namabaru = uniqid() . '.' . $ekstensigambar;
        // Pindahkan file ke direktori yang diinginkan
        move_uploaded_file($tmpname, '../assets/img/' . $namabaru);
    }

    $username = sanitize_input($_POST['username']);
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $password = sanitize_input($_POST['password']);
    $csrf_token = $_POST['csrf_token'];
    $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Validate CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!','Token keamanan tidak valid!');
        header("Location: ../pages/register.php");
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!','Format email tidak valid!');
        header("Location: ../pages/register.php");
        exit();
    }

    // Validate password confirmation
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!','Konfirmasi password tidak cocok!');
        header("Location: ../pages/register.php");
        exit();
    }

    $role = 'users'; // Default role


    // Check if username already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!','Username atau password salah!');
        header("Location: ../pages/register.php");
        exit();
    }
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!','Email Atau password salah!');
        header("Location: ../pages/register.php");
        exit();
    }

    $id = bin2hex(random_bytes(10)); // Random ID

    // Insert into database
    $stmt = $pdo->prepare("INSERT INTO users (id, username, nama, foto, email, password_hash, password_plain, role) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$id, $username, $name, $namabaru, $email, $password_hash, $password, $role])) {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('success', 'Berhasil!','Registrasi berhasil! Silakan login.');
        header("Location: ../pages/login.php");
        exit();
    } else {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!','Registrasi gagal! Silakan coba lagi.');
        header("Location: ../pages/register.php");
        exit();
    }
} else {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!','Akses tidak valid!');
    header("Location: ../pages/register.php");
    exit();
}
?>
