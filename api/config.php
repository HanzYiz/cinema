<?php
// 1. Koneksi Database yang Aman
$db_host = 'localhost'; 
$db_name = 'cinema';
$db_user = 'root';
$db_pass = ''; 

try {
  $pdo = new PDO(
    "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
    $db_user,
    $db_pass,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false, // Penting untuk keamanan
      PDO::ATTR_PERSISTENT => false // Disable persistent connections
    ]
  );
} catch (PDOException $e) {
  error_log("Koneksi database gagal: " . $e->getMessage());
  die("Maaf, sistem sedang maintenance. Silakan coba lagi nanti.");
}


// 2. Pengaturan Session Aman
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Hanya aktif jika pakai HTTPS
ini_set('session.use_strict_mode', 1);
session_start();

// 3. Konfigurasi Umum
define('APP_NAME', 'Bioskop Kita');
define('BASE_URL', 'https://cinema.test'); // Selalu pakai HTTPS
define('MAX_LOGIN_ATTEMPTS', 5); // Limit percobaan login
define('PASSWORD_HASH_COST', 12); // Untuk password_hash()

// 4. Fungsi Keamanan Dasar
function sanitize_input($data) {
  return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function generate_csrf_token() {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

// 6. Error Reporting (Development vs Production)
if ($_SERVER['SERVER_NAME'] === 'localhost') {
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
} else {
  ini_set('display_errors', 0);
  error_reporting(0);
}
?>