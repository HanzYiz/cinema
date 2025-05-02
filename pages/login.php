<?php
include "../api/config.php"; // sudah ada session_start di config.php
// Tampilkan alert jika ada
include "../components/alert.php"; // Include alert function

if (isset($_SESSION['user_id'])){
    header("Location: ../dashboard/index.php"); // Redirect to dashboard if already logged in
    exit();
}


if (isset($_SESSION['showDisruptiveAlert'])) { 
  echo $_SESSION['showDisruptiveAlert'];
  unset($_SESSION['showDisruptiveAlert']); 
} 


if (isset($_SESSION['logout_alert'])) {
    echo showDisruptiveAlert('success', 'Berhasil!', 'Anda telah berhasil keluar!');
    unset($_SESSION['logout_alert']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Bioskop Kita</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
  <!-- Custom CSS -->
  <style>
    .login-page {
      background-color: #f8f9fa;
      min-height: 100vh;
      display: flex;
      align-items: center;
    }
    .login-container {
      max-width: 400px;
      margin: 0 auto;
      padding: 30px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    .login-header {
      text-align: center;
      margin-bottom: 30px;
    }
    .login-header i {
      font-size: 50px;
      color: #007bff;
      margin-bottom: 15px;
    }
    .login-header h2 {
      color: #343a40;
      font-weight: bold;
    }
    .btn-login {
      background-color: #007bff;
      color: white;
      padding: 10px;
      font-weight: bold;
      margin-top: 20px;
    }
    .btn-login:hover {
      background-color: #0069d9;
    }
    .login-link {
      color: #007bff;
      font-weight: bold;
      margin-left: 5px;
    }
    .login-link:hover {
      text-decoration: underline;
    }
    .input-group-text {
      background-color: #e9ecef;
    }
    .password-toggle {
      cursor: pointer;
      background-color: #e9ecef;
    }
  </style>
</head>
<body class="login-page">
  <div class="container">
    <div class="login-container">
      <!-- Header -->
      <div class="login-header">
        <i class="fas fa-film"></i>
        <h2>LOGIN CINEMA XXI</h2>
      </div>
      
      <!-- Form Login -->
      <form action="../api/login_ext.php" method="POST">
            <div class="mb-3">
        <label for="username_or_email" class="form-label">Username atau Email</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-user"></i></span>
          <input type="text" class="form-control" id="username_or_email" name="username_or_email" 
                placeholder="username atau user@example.com" required>
          <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">  
        </div>
      </div>
  
        <!-- Password dengan Toggle -->
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
            <span class="input-group-text password-toggle" onclick="togglePassword()">
              <i class="fas fa-eye"></i>
            </span>
          </div>
        </div>
        
        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary btn-login w-100">
          <i class="fas fa-sign-in-alt"></i> LOGIN
        </button>
        
        <!-- Link Register -->
        <div class="text-center mt-3">
          <span>Belum punya akun?</span>
          <a href="register.php" class="login-link">Daftar disini</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Fungsi toggle password
    function togglePassword() {
      const passwordField = document.getElementById('password');
      const toggleIcon = document.querySelector('.password-toggle i');
      
      if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
        passwordField.type = 'password';
        toggleIcon.classList.replace('fa-eye-slash', 'fa-eye');
      }
    }
  </script>
</body>
</html>