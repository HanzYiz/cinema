<?php
include '../config.php';
include '../../components/alert.php'; // Include alert function


// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$id = $_POST['id'] ?? null;

if (!$id) {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'ID user tidak ditemukan!');
    echo "<script>history.back();</script>";
    exit();
}

// Ambil data user berdasarkan ID
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'User tidak ditemukan!');
    echo "<script>history.back();</script>";
    exit();
}

if (isset($_POST['submit'])) {

    // Ambil foto lama
    $gambarLama = $user['foto'];

    // Proses upload gambar jika ada
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $namafile = $_FILES['foto']['name'];
        $tmpname = $_FILES['foto']['tmp_name'];
        $ukuranfile = $_FILES['foto']['size'];

        // Cek ekstensi file
        $ekstensivalid = ['jpg', 'jpeg', 'png'];
        $ekstensigambar = strtolower(pathinfo($namafile, PATHINFO_EXTENSION));

        if (!in_array($ekstensigambar, $ekstensivalid)) {
           $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Format gambar harus JPG, JPEG, atau PNG!');
            echo "<script>history.back();</script>";
            exit();
        }

        // Cek ukuran file
        if ($ukuranfile > 5000000) {
            $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Ukuran gambar terlalu besar!');
            echo "<script>history.back();</script>";
            exit();
        }

        // Hapus gambar lama kecuali default.jpeg
        if ($gambarLama !== 'default.jpeg' && !empty($gambarLama) && file_exists("../../assets/img/$gambarLama")) {
            unlink("../../assets/img/$gambarLama");
        }

        // Simpan gambar baru
        $namabaru = uniqid() . '.' . $ekstensigambar;
        move_uploaded_file($tmpname, "../../assets/img/" . $namabaru);
    } else {
        $namabaru = $gambarLama;
    }

    // Ambil inputan form
    $name = sanitize_input($_POST['nama']);
    $email = sanitize_input($_POST['email']);
    $csrf_token = $_POST['csrf_token'];

    // Validate CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Token keamanan tidak valid!');
        echo "<script>history.back();</script>";
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Format email tidak valid!');
        echo "<script>history.back();</script>";
        exit();
    }

    // Cek apakah user mau ganti password
    $old_password = $_POST['current_password'] ?? null;
    $new_password = sanitize_input($_POST['new_password'] ?? null);
    $confirm_password = sanitize_input($_POST['confirm_password'] ?? null);

    if (!empty($old_password) || !empty($new_password) || !empty($confirm_password)) {    
        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Semua field password harus diisi!');
            header("Location: ../../dashboard/profile/edit.php");
            exit();
        }
        if (!password_verify($old_password, $user['password_hash'])) {
            $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Password lama tidak cocok!');
           header("Location: ../../dashboard/profile/edit.php"); 
            exit();
        }
    
        if ($new_password !== $confirm_password) {
            $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Konfirmasi password tidak cocok!');
            header("Location: ../../dashboard/profile/edit.php");
            exit();
        }

        // Hash password baru
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);

        // Update semua field termasuk password baru
        $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ?, password_plain =?, password_hash = ?, foto = ? WHERE id = ?");
        $stmt->execute([$name, $email, $new_password, $password_hash, $namabaru, $id]);
    } else {
        // Tidak ganti password, hanya update nama, email, foto
        $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ?, foto = ? WHERE id = ?");
        $stmt->execute([$name, $email, $namabaru, $id]);
    }

    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('success', 'Berhasil!', 'Profil berhasil diperbarui!');
    header("Location: ../../dashboard");
    exit();

} else {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Akses tidak valid!');
    exit();
}
?>
