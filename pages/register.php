<?php
include "../api/config.php";
include "../components/alert.php"; // Include alert function
if (isset($_SESSION['showDisruptiveAlert'])) { 
  echo $_SESSION['showDisruptiveAlert'];
  unset($_SESSION['showDisruptiveAlert']); 
} 
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar - Bioskop Kita</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .register-page {
      background-color: #f8f9fa;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .register-container {
      max-width: 500px;
      width: 100%;
      padding: 2rem;
      background: white;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    .register-header {
      text-align: center;
      margin-bottom: 2rem;
    }
    .register-header i {
      font-size: 3rem;
      color: #0d6efd;
      margin-bottom: 1rem;
    }
    .register-header h2 {
      color: #212529;
      font-weight: bold;
    }
    .btn-register {
      background-color: #0d6efd;
      color: white;
      padding: 0.5rem;
      font-weight: bold;
      margin-top: 1.5rem;
    }
    .btn-register:hover {
      background-color: #0b5ed7;
    }
    .register-link {
      color: #0d6efd;
      font-weight: bold;
      margin-left: 0.3rem;
    }
    .register-link:hover {
      text-decoration: underline;
    }
    .input-group-text {
      background-color: #e9ecef;
    }
    .password-toggle {
      cursor: pointer;
    }
    .photo-preview-container {
      margin-top: 15px;
      text-align: center;
      display: none;
    }
    .photo-preview {
      max-width: 200px;
      max-height: 200px;
      border-radius: 5px;
      border: 2px dashed #ddd;
      object-fit: cover;
    }
    .remove-photo {
      margin-top: 10px;
      display: none;
    }
    .file-upload-label {
      display: block;
      padding: 10px;
      border: 2px dashed #ddd;
      border-radius: 5px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
    }
    .file-upload-label:hover {
      border-color: #0d6efd;
      background-color: #f0f7ff;
    }
  </style>
</head>
<body class="register-page">
  <div class="container">
    <div class="register-container">
      <!-- Header -->
      <div class="register-header">
        <i class="fas fa-film"></i>
        <h2>DAFTAR AKUN BARU</h2>
      </div>
      
      <form action="../api/register_ext.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <!-- Nama Lengkap -->
                <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-user"></i></span>
            <input type="text" class="form-control" id="username" name="username" placeholder="username" required>
          </div>
        </div>
        <!-- Nama Lengkap -->
        <div class="mb-3">
          <label for="fullname" class="form-label">Nama Lengkap</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-user"></i></span>
            <input type="text" class="form-control" id="fullname" name="name" placeholder="John Doe" required>
          </div>
        </div>

        <!-- Email -->
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email" placeholder="user@example.com" required>
          </div>
        </div>

        <!-- Password -->
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
            <span class="input-group-text password-toggle" onclick="togglePassword('password')">
              <i class="fas fa-eye"></i>
            </span>
          </div>
        </div>

        <!-- Konfirmasi Password -->
        <div class="mb-3">
          <label for="confirm_password" class="form-label">Konfirmasi Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
            <span class="input-group-text password-toggle" onclick="togglePassword('confirm_password')">
              <i class="fas fa-eye"></i>
            </span>
          </div>
        </div>

        <!-- Photo Upload with Preview -->
        <div class="mb-3">
          <label class="form-label">Foto Profil (Opsional)</label>
          <label for="foto" class="file-upload-label">
            <i class="fas fa-camera me-2"></i>
            <span id="fileLabel">Pilih Foto</span>
          </label>
          <input type="file" name="foto" id="foto" class="d-none" accept="image/*">
          
          <div class="photo-preview-container" id="photoPreviewContainer">
            <img id="photoPreview" class="photo-preview" src="#" alt="Preview Foto"/>
            <button type="button" class="btn btn-sm btn-danger remove-photo" id="removePhoto">
              <i class="fas fa-times"></i> Hapus Foto
            </button>
          </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" name="submit" class="btn btn-primary btn-register w-100">
          <i class="fas fa-user-plus"></i> DAFTAR
        </button>
        
        <!-- Link Login -->
        <div class="text-center mt-3">
          <span>Sudah punya akun?</span>
          <a href="login.php" class="register-link">Login disini</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Fungsi toggle password
    function togglePassword(fieldId) {
      const field = document.getElementById(fieldId);
      const icon = field.nextElementSibling.querySelector('i');
      
      if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
        field.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
      }
    }

    // Validasi password match
    document.querySelector('form').addEventListener('submit', function(e) {
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      
      if (password !== confirmPassword) {
        e.preventDefault();
        alert('Password dan Konfirmasi Password tidak cocok!');
      }
    });

    // Photo Preview Functionality
    const fileInput = document.getElementById('foto');
    const fileLabel = document.getElementById('fileLabel');
    const previewContainer = document.getElementById('photoPreviewContainer');
    const preview = document.getElementById('photoPreview');
    const removeBtn = document.getElementById('removePhoto');
    
    fileInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      
      if (file) {
        // Update file label
        fileLabel.textContent = file.name;
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
          preview.src = e.target.result;
          previewContainer.style.display = 'block';
          removeBtn.style.display = 'block';
        }
        reader.readAsDataURL(file);
      } else {
        resetFileInput();
      }
    });
    
    // Remove photo functionality
    removeBtn.addEventListener('click', function(e) {
      e.preventDefault();
      resetFileInput();
    });
    
    function resetFileInput() {
      fileInput.value = '';
      fileLabel.textContent = 'Pilih Foto';
      preview.src = '#';
      previewContainer.style.display = 'none';
      removeBtn.style.display = 'none';
    }
  </script>
</body>
</html>