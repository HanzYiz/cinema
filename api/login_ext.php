<?php
include 'config.php';
include '../components/alert.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitasi input
    $userinput = $_POST['username_or_email'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validasi dasar
    if (empty($userinput) || empty($password)) {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!','Email dan password harus diisi!');
        header("Location: ../pages/login.php");
        exit();
    }

    // Validasi CSRF
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!','Token keamanan tidak valid!');
        header("Location: ../pages/login.php");
        exit();
    }

    // Pisahkan username dan email
    if (filter_var($userinput, FILTER_VALIDATE_EMAIL)) {
        $email = $userinput;
    } else {
        $username = $userinput;
    }

    // Cek apakah username atau email ada di database
    if (isset($username)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
    }
    if ($stmt->rowCount() === 0) {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!','Email atau Username tidak terdaftar!');
        header("Location: ../pages/login.php");
        exit();
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verifikasi password
    if (!password_verify($password, $user['password_hash'])) {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!','Password salah!');
        header("Location: ../pages/login.php");
        exit();
    }

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['foto'] = $user['foto'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['nama'] = $user['nama'];
    // ...data session lainnya...
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('success', 'Berhasil!','Login berhasil!');
    if ($user['role'] !== 'admin'){
        header("Location: ../dashboard/index.php");
        exit();
    }
    header("Location: ../admin/index.php");
    exit();
} else {
    // Jika akses langsung ke file ini
    header("Location: ../pages/login.php");
    exit();
}