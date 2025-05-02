<?php
include "../../api/config.php";
// Check session and get user data
session_start();
include "../components/alert.php"; // Include alert function

if (isset($_SESSION['showDisruptiveAlert'])) { 
  echo $_SESSION['showDisruptiveAlert'];
  unset($_SESSION['showDisruptiveAlert']); 
} 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Profil - Bioskop Kita</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .profile-page {
      background-color: #f8f9fa;
      min-height: 100vh;
    }
    .profile-container {
      max-width: 800px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    .profile-pic {
      width: 150px;
      height: 150px;
      object-fit: cover;
      border: 5px solid white;
      box-shadow: 0 3px 10px rgba(0,0,0,0.2);
    }
    .file-upload-label {
      cursor: pointer;
      transition: all 0.3s;
    }
    .file-upload-label:hover {
      opacity: 0.8;
    }
    .remove-photo-btn {
      position: absolute;
      right: 10px;
      top: 10px;
    }
  </style>
</head>
<body class="profile-page">
  <div class="container py-5">
    <div class="profile-container bg-white p-4 mx-auto">
      <h2 class="text-center mb-4">
        <i class="fas fa-user-edit me-2"></i>Edit Profil
      </h2>

      <form action="../../api/profile/update.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
        <input type="hidden" name="id" value="<?= $user['id'] ?>">
        <!-- Photo Upload Section -->
        <div class="text-center mb-4 position-relative">
          <input type="file" name="foto" id="profilePic" accept="image/*" class="d-none">
          
          <label for="profilePic" class="file-upload-label d-inline-block position-relative">
            <img id="profilePreview" 
                 src="<?= !empty($user['foto']) ? '../../assets/img/'.$user['foto'] : 'https://via.placeholder.com/150' ?>" 
                 class="profile-pic rounded-circle">
            <div class="mt-2 text-primary">
              <i class="fas fa-camera me-1"></i> Ganti Foto
            </div>
          </label>
          
          <?php if (!empty($user['foto'])): ?>
            <button type="button" class="btn btn-danger btn-sm remove-photo-btn" id="removePhoto">
              <i class="fas fa-times"></i>
            </button>
          <?php endif; ?>
        </div>

        <!-- Personal Info -->
        <div class="row g-3">
          <div class="col-md-6">
            <label for="nama" class="form-label">Nama Lengkap</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-user"></i></span>
              <input type="text" class="form-control" id="nama" name="nama" 
                     value="<?= htmlspecialchars($user['nama']) ?>" required>
            </div>
          </div>

          <div class="col-md-6">
            <label for="email" class="form-label">Email</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-envelope"></i></span>
              <input type="email" class="form-control" id="email" name="email" 
                     value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
          </div>

        <!-- Password Section -->
        <div class="card mt-4">
          <div class="card-header bg-light">
            <h5 class="mb-0">
              <i class="fas fa-lock me-2"></i>Ganti Password
            </h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label for="current_password" class="form-label">Password Saat Ini</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-key"></i></span>
                <input type="password" class="form-control" id="current_password" name="current_password">
                <button class="btn btn-outline-secondary toggle-password" type="button">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>

            <div class="mb-3">
              <label for="new_password" class="form-label">Password Baru</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" class="form-control" id="new_password" name="new_password">
                <button class="btn btn-outline-secondary toggle-password" type="button">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
              <div class="form-text">Minimal 8 karakter</div>
            </div>

            <div class="mb-3">
              <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                <button class="btn btn-outline-secondary toggle-password" type="button">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-between mt-4">
          <a href="../../dashboard" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali
          </a>
          <button type="submit" name="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i> Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle with Popper -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <script>
document.addEventListener('DOMContentLoaded', function() {
  
  // --- Toggle Password Visibility ---
  document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
      const input = this.previousElementSibling;
      const icon = this.querySelector('i');
      
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
      }
    });
  });

  // --- Profile Picture Preview ---
  const profilePicInput = document.getElementById('profilePic');
  const profilePreview = document.getElementById('profilePreview');
  const removeButton = document.getElementById('removePhoto');
  const defaultPhoto = '../../assets/img/default.jpeg'; // Default foto

  profilePicInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        profilePreview.src = e.target.result;
        if (removeButton) removeButton.style.display = 'block';
      }
      reader.readAsDataURL(file);
    }
  });

  // --- Remove Photo ---
  if (removeButton) {
    removeButton.addEventListener('click', function() {
      profilePicInput.value = '';
      profilePreview.src = defaultPhoto;
      this.style.display = 'none';

      // Biar server tahu kalau foto dihapus
      const hiddenInput = document.createElement('input');
      hiddenInput.type = 'hidden';
      hiddenInput.name = 'remove_photo';
      hiddenInput.value = '1';
      document.querySelector('form').appendChild(hiddenInput);
    });
  }

  // --- Validate New Password and Confirm Password ---
  const newPasswordInput = document.getElementById('new_password');
  const confirmPasswordInput = document.getElementById('confirm_password');
  const form = document.querySelector('form');

  function validatePasswords() {
    const newPassword = newPasswordInput.value;
    const confirmPassword = confirmPasswordInput.value;

    // Mencari alert sebelumnya dan menghapusnya
    const existingAlert = document.getElementById('passwordMismatchAlert');
    if (existingAlert) {
      existingAlert.remove();
    }

    // Validasi confirm password cocok
    if (confirmPassword && newPassword !== confirmPassword) {
      const alertDiv = document.createElement('div');
      alertDiv.innerHTML = `
        <div id="passwordMismatchAlert" class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
          <strong>Gagal!</strong> Password baru dan konfirmasi password tidak cocok.
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      `;
      const parent = confirmPasswordInput.closest('.mb-3');
      parent.appendChild(alertDiv.firstChild);
    }
  }

  // Cek password ketika user mengetik
  newPasswordInput.addEventListener('input', validatePasswords);
  confirmPasswordInput.addEventListener('input', function() {
    validatePasswords(); // Memanggil fungsi validasi saat konfirmasi password diubah
  });

  // Cek password lagi sebelum form submit
  form.addEventListener('submit', function(e) {
    const newPassword = newPasswordInput.value;
    const confirmPassword = confirmPasswordInput.value;
    
    if (newPassword !== confirmPassword) {
      e.preventDefault(); // Stop submit
      validatePasswords();
    }
  });
});
</script>
</body>
</html>