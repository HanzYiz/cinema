<?php
// navbar.php - Navigation bar component
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="../index.php">
            <?= APP_NAME ?> <!-- Konstanta dari config.php -->
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../movies/now_playing.php">Sedang Tayang</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../movies/coming_soon.php">Segera Tayang</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../pages/promotions.php">Promo</a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> 
                            <?= htmlspecialchars($_SESSION['user_name'] ?? 'Akun') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../../dashboard">Dashboard</a></li>
                            <li><a class="dropdown-item" href="../user/bookings.php">Riwayat Booking</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>