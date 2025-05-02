<?php
// Pastikan session sudah dimulai
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="">
            <?= APP_NAME ?> Admin
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/admin"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-credit-card"></i> Pembayaran
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/admin/payment/list.php?status=pending">Perlu Verifikasi</a></li>
                        <li><a class="dropdown-item" href="/admin/payment/list.php">Semua Pembayaran</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/movies"><i class="bi bi-film"></i> Film</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/schedules"><i class="bi bi-calendar-event"></i> Jadwal</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/users"><i class="bi bi-people"></i> Users</a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= $_SESSION['user_name'] ?? 'Admin' ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="/admin/profile"><i class="bi bi-person"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/../../api/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>