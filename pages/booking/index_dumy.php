<?php
include '../../api/config.php';
include '../../components/alert.php';

// Verifikasi user sudah login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('warning', 'Login Diperlukan', 'Silakan login terlebih dahulu untuk memesan tiket.');
    header('Location: ../pages/login.php');
    exit;
}

// Ambil semua film dari database
$query = $pdo->prepare("SELECT id, judul, poster_url, durasi, sutradara, pemain, sinopsis, rating FROM movies WHERE status = 'now_playing' ORDER BY id DESC");
$query->execute();
$movies = $query->fetchAll(PDO::FETCH_ASSOC);

// Format data untuk JavaScript
$movies_data = [];
foreach ($movies as $movie) {
    $movies_data[$movie['id']] = [
        'judul' => $movie['judul'],
        'poster_url' => $movie['poster_url'],
        'durasi' => $movie['durasi'],
        'sutradara' => $movie['sutradara'],
        'pemain' => $movie['pemain'],
        'sinopsis' => $movie['sinopsis'],
        'rating' => $movie['rating']
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pemesanan Tiket Bioskop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .movie-poster {
            width: 100%;
            height: 360px;
            object-fit: cover;
            border-radius: 10px;
            transition: transform 0.3s;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .movie-poster:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        .movie-card {
            cursor: pointer;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s;
            height: 100%;
        }
        .movie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        .movie-title {
            font-weight: 700;
            font-size: 1.2rem;
            margin-top: 12px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            height: 50px;
        }
        .movie-info {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .badge-custom {
            font-size: 0.8rem;
            padding: 0.35em 0.65em;
        }
        .screening-time {
            cursor: pointer;
            transition: all 0.2s;
        }
        .screening-time:hover {
            background-color: #e9ecef;
        }
        .screening-time.selected {
            background-color: #0d6efd;
            color: white;
        }
        .seat {
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #ddd;
            font-size: 10px;
        }
        .seat-map-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .seat.available {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            color: #495057;
        }
        .seat.available:hover {
            transform: scale(1.05);
            box-shadow: 0 3px 6px rgba(0,0,0,0.15);
        }
        .seat.selected {
            background: #0d6efd !important;
            border: 2px solid #0a58ca !important;
            color: white !important;
        }
        .seat.occupied {
            background: #dc3545 !important;
            border: 2px solid #b02a37 !important;
            color: white !important;
            cursor: not-allowed;
        }
        .seat.unavailable {
            background-color: #adb5bd;
            opacity: 0.5;
            cursor: not-allowed;
        }
        .seat-grid {
            display: grid;
            grid-template-columns: 30px repeat(10, 40px);
            gap: 8px;
            margin: 20px 0;
        }
        .seat-row-label {
            grid-column: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .screen {
            margin-bottom: 30px;
            padding: 10px;
            background: #f0f0f0;
            text-align: center;
            font-weight: bold;
            border-radius: 5px;
        }
        .seat-grid-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 20px 0;
        }
        .me-4 {
             margin-right: 1.5rem !important;
        }

        .seat-legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
        }

        .legend-color {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            margin-right: 8px;
        }
        .row-label {
            width: 20px;
            font-weight: bold;
            margin-right: 10px;
        }
        .seat-container {
            display: flex;
            justify-content: center;
            gap: 40px; /* Jarak antara kiri-kanan */
            margin-top: 20px;
        }
        .seats-wrapper {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            width: 300px; /* Sesuaikan dengan lebar yang diinginkan */
        }
        .seat-section {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .seat-row {
            display: flex;
            align-items: center;
        }
        .seats-wrapper {
            display: flex;
            gap: 3px;
        }
        .step-indicator {
            font-size: 1.5rem;
            font-weight: bold;
            color: #adb5bd;
        }
        .step-indicator.active {
            color: #0d6efd;
        }
        .summary-box {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        .movie-detail-container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .movie-detail-poster {
            width: 100%;
            height: 420px;
            object-fit: cover;
        }
        .selected-movie-poster {
            width: 100%;
            height: auto;
            border-radius: 8px;
            object-fit: cover;
        }
        .btn-booking {
            background-color: #0d6efd;
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.3s;
        }
        .btn-booking:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            background-color: #0b5ed7;
        }
        .step-content {
            display: none;
        }
        .step-content.active {
            display: block;
        }
        .seat-legend {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            margin-right: 5px;
        }
        .summary-section {
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }
        #loading-spinner {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: none;
            background: rgba(255,255,255,0.8);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>
<body>
    <div id="loading-spinner" class="d-none">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">CinemaKu</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../home.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Pesan Tiket</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../pages/my_bookings.php">Tiket Saya</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-light me-3">Halo, <?= $_SESSION['nama'] ?? 'Pengguna' ?></span>
                    <a href="../../api/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">Pemesanan Tiket Bioskop</h4>
                            <div class="d-flex">
                                <div class="me-3 text-center">
                                    <div class="step-indicator active" id="step1-indicator">1</div>
                                    <small>Pilih Film</small>
                                </div>
                                <div class="me-3 text-center">
                                    <div class="step-indicator" id="step2-indicator">2</div>
                                    <small>Jadwal</small>
                                </div>
                                <div class="me-3 text-center">
                                    <div class="step-indicator" id="step3-indicator">3</div>
                                    <small>Kursi</small>
                                </div>
                                <div class="text-center">
                                    <div class="step-indicator" id="step4-indicator">4</div>
                                    <small>Bayar</small>
                                </div>
                            </div>
                        </div>

                       <!-- Step 1: Pilih Film -->
                      <div class="step-content active" id="step1-content">
                          <div class="row">
                              <?php foreach ($movies as $movie): ?>
                              <div class="col-md-3 mb-4">
                                  <div class="movie-card p-3" data-movie-id="<?= $movie['id'] ?>" onclick="selectMovie('<?= $movie['id'] ?>')">
                                      <img src="<?= $movie['poster_url'] ?>" class="movie-poster" alt="<?= $movie['judul'] ?>">
                                      <div class="movie-title"><?= $movie['judul'] ?></div>
                                      <div class="movie-info mb-2">
                                          <div class="d-flex justify-content-between">
                                              <span><i class="bi bi-clock"></i> <?= $movie['durasi'] ?> menit</span>
                                              <span><i class="bi bi-star-fill text-warning"></i> <?= $movie['rating'] ?></span>
                                          </div>
                                      </div>
                                      <div class="d-grid">
                                          <button class="btn btn-sm btn-outline-primary">Pilih Film</button>
                                      </div>
                                  </div>
                              </div>
                              <?php endforeach; ?>
                          </div>
                      </div>

                        <!-- Step 2: Pilih Jadwal & Studio -->
                        <div class="step-content" id="step2-content">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <img id="selected-movie-poster" src="" class="selected-movie-poster" alt="Movie Poster">
                                        <div class="card-body">
                                            <h5 id="selected-movie-title" class="card-title"></h5>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    <i class="bi bi-clock"></i> <span id="selected-movie-duration"></span> menit
                                                </small>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title mb-3">Pilih Tanggal</h5>
                                            <div class="d-flex mb-4 overflow-auto pb-2" id="date-selector">
                                                <!-- Dates will be generated by JavaScript -->
                                            </div>
                                            
                                            <h5 class="card-title mb-3">Pilih Studio & Jam Tayang</h5>
                                            <div id="schedule-container">
                                                <!-- Studio and time options will be loaded here -->
                                                <div class="alert alert-info">
                                                    Silakan pilih tanggal terlebih dahulu
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-3">
                                <button class="btn btn-outline-secondary" onclick="prevStep()">Kembali</button>
                                <button class="btn btn-primary" id="next-to-seats" disabled onclick="goToStep(3)">Lanjut Pilih Kursi</button>
                            </div>
                        </div>

                        <!-- Step 3: Pilih Kursi -->
                        <div class="step-content" id="step3-content">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title mb-3">Pilih Kursi</h5>
                                            <div class="seat-legend">
                                                <div class="legend-item">
                                                    <div class="legend-color bg-secondary"></div>
                                                    <span>Tersedia</span>
                                                </div>
                                                <div class="legend-item">
                                                    <div class="legend-color bg-primary"></div>
                                                    <span>Dipilih</span>
                                                </div>
                                                <div class="legend-item">
                                                    <div class="legend-color bg-danger"></div>
                                                    <span>Sudah Terisi</span>
                                                </div>
                                            </div>
                                            
                                            <div id="seat-map" class="mb-4">
                                                <div class="text-center py-4">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                    <p class="mt-2">Memuat denah kursi...</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Detail Pemesanan</h5>
                                            <p class="mb-1"><strong>Film:</strong> <span id="summary-movie"></span></p>
                                            <p class="mb-1"><strong>Tanggal:</strong> <span id="summary-date"></span></p>
                                            <p class="mb-1"><strong>Studio:</strong> <span id="summary-studio"></span></p>
                                            <p class="mb-3"><strong>Jam:</strong> <span id="summary-time"></span></p>
                                            
                                            <h6 class="mb-2">Kursi Dipilih:</h6>
                                            <div id="selected-seats" class="mb-3">-</div>
                                            
                                            <div class="summary-section">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Jumlah Tiket:</span>
                                                    <span id="ticket-count">0</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Harga per Tiket:</span>
                                                    <span>Rp 50.000</span>
                                                </div>
                                                <div class="d-flex justify-content-between fw-bold">
                                                    <span>Total:</span>
                                                    <span id="total-price">Rp 0</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-3">
                                <button class="btn btn-outline-secondary" onclick="prevStep()">Kembali</button>
                                <button class="btn btn-primary" id="next-to-payment" disabled onclick="goToStep(4)">Lanjut ke Pembayaran</button>
                            </div>
                        </div>

                        <!-- Step 4: Pembayaran -->
                        <div class="step-content" id="step4-content">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Informasi Pembayaran</h5>
                                            <form id="payment-form">
                                                <div class="mb-3">
                                                    <label for="name" class="form-label">Nama Lengkap</label>
                                                    <input type="text" class="form-control" id="name" value="<?= $_SESSION['nama'] ?? $_SESSION['nama'] ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="email" class="form-label">Email</label>
                                                    <input type="email" class="form-control" id="email" value="<?= $_SESSION['email'] ?? '' ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="phone" class="form-label">Nomor Telepon</label>
                                                    <input type="tel" class="form-control" id="phone" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Metode Pembayaran</label>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="payment_method" id="payment1" value="bca" checked>
                                                            <label class="form-check-label" for="payment1">
                                                                Transfer BCA
                                                            </label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="payment_method" id="payment2" value="mandiri">
                                                            <label class="form-check-label" for="payment2">
                                                                Transfer Mandiri
                                                            </label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="payment_method" id="payment3" value="gopay">
                                                            <label class="form-check-label" for="payment3">
                                                                GoPay
                                                            </label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="payment_method" id="payment4" value="ovo">
                                                            <label class="form-check-label" for="payment4">
                                                                OVO
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>                                    
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Ringkasan Pesanan</h5>
                                            <p class="mb-1"><strong>Film:</strong> <span id="final-movie"></span></p>
                                            <p class="mb-1"><strong>Tanggal:</strong> <span id="final-date"></span></p>
                                            <p class="mb-1"><strong>Studio:</strong> <span id="final-studio"></span></p>
                                            <p class="mb-1"><strong>Jam:</strong> <span id="final-time"></span></p>
                                            <p class="mb-3"><strong>Kursi:</strong> <span id="final-seats"></span></p>
                                            
                                            <div class="summary-section">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Jumlah Tiket:</span>
                                                    <span id="final-ticket-count">0</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Harga per Tiket:</span>
                                                    <span>Rp 50.000</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Subtotal:</span>
                                                    <span id="final-subtotal">Rp 0</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Biaya Layanan:</span>
                                                    <span id="final-service-fee">Rp 5.000</span>
                                                </div>
                                                <div class="d-flex justify-content-between fw-bold mt-2 pt-2 border-top">
                                                    <span>Total:</span>
                                                    <span id="final-total-price">Rp 0</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-3">
                                <button class="btn btn-outline-secondary" onclick="prevStep()">Kembali</button>
                                <button class="btn btn-booking btn-lg" onclick="submitBooking()">
                                    <i class="bi bi-credit-card me-2"></i>Bayar Sekarang
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
const movies = <?php echo json_encode($movies_data); ?>;
// State aplikasi
let currentStep = 1;
let selectedMovieId = null;
let selectedDate = null;
let selectedStudioId = null;
let selectedTime = null;
let selectedSeats = [];
let currentScheduleId = null;
let currentOccupiedSeats = [];
const servicesFee = 5000;
const ticketPrice = 50000;

// Fungsi step navigation
function goToStep(step) {
    // Validasi sebelum pindah step
    if (step > currentStep) {
        if (step === 2 && !selectedMovieId) {
            alert('Silakan pilih film terlebih dahulu');
            return;
        }
        if (step === 3 && (!selectedDate || !selectedStudioId || !selectedTime)) {
            alert('Silakan pilih tanggal, studio, dan jam tayang');
            return;
        }
        if (step === 4 && selectedSeats.length === 0) {
            alert('Silakan pilih kursi terlebih dahulu');
            return;
        }
    }
    
    // Sembunyikan step sebelumnya dan tampilkan step baru
    document.getElementById(`step${currentStep}-content`).classList.remove('active');
    document.getElementById(`step${currentStep}-indicator`).classList.remove('active');

    document.getElementById(`step${step}-content`).classList.add('active');
    document.getElementById(`step${step}-indicator`).classList.add('active');

    currentStep = step;

    // Jika ke step seat selection, muat data kursi
    if (step === 3) {
        loadSeatMapWithCapacity(selectedStudioId).then(() => {
            updateBookingSummary();
            updateSelectedSeatsDisplay();
        });
    }

    // Jika ke step pembayaran, update summary final
    if (step === 4) {
        updateFinalSummary();
    }

    // Scroll ke atas
    window.scrollTo(0, 0);
}

function prevStep() {
    if (currentStep > 1) {
        goToStep(currentStep - 1);
    }
}


// Step 1: Pilih Film
function selectMovie(movieId) {
    selectedMovieId = movieId;

    // Update UI
    document.querySelectorAll('.movie-card').forEach(card => {
        card.classList.remove('border', 'border-primary');
    });

    document.querySelector(`.movie-card[data-movie-id="${movieId}"]`).classList.add('border', 'border-primary');

    // Update movie info di step 2
    document.getElementById('selected-movie-poster').src = movies[movieId].poster_url;
    document.getElementById('selected-movie-title').textContent = movies[movieId].judul;
    document.getElementById('selected-movie-duration').textContent = movies[movieId].durasi;

    // Persiapkan kalender
    generateDateSelector();
    
    // Langsung ke step 2
    goToStep(2);
}

// Step 2: Generate Date Selector
function generateDateSelector() {
    const dateSelector = document.getElementById('date-selector');
    dateSelector.innerHTML = '';
    
    const today = new Date();
    
    // Generate 7 hari ke depan
    for (let i = 0; i < 7; i++) {
        const date = new Date();
        date.setDate(today.getDate() + i);
        
        const dateString = formatDate(date);
        const dayName = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][date.getDay()];
        const formattedDate = `${date.getDate()} ${['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'][date.getMonth()]}`;
        
        const dateCard = document.createElement('div');
        dateCard.className = 'card text-center me-2';
        dateCard.style.minWidth = '100px';
        dateCard.style.cursor = 'pointer';
        dateCard.dataset.date = dateString;
        dateCard.innerHTML = `
            <div class="card-body p-2">
                <div class="fw-bold">${dayName}</div>
                <div>${formattedDate}</div>
            </div>
        `;
        
        dateCard.addEventListener('click', () => selectDate(dateString, dateCard));
        dateSelector.appendChild(dateCard);
    }
    
    // Pilih tanggal hari ini secara default
    const todayCard = dateSelector.querySelector(`[data-date="${formatDate(today)}"]`);
    if (todayCard) {
        todayCard.click();
    }
}

function formatDate(date) {
    const d = new Date(date);
    const month = `${d.getMonth() + 1}`.padStart(2, '0');
    const day = `${d.getDate()}`.padStart(2, '0');
    const year = d.getFullYear();
    return `${year}-${month}-${day}`;
}

function formatDisplayDate(dateString) {
    const date = new Date(dateString);
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('id-ID', options);
}

// Step 2: Pilih Tanggal
function selectDate(dateString, dateElement) {
    selectedDate = dateString;
    
    // Update UI
    document.querySelectorAll('#date-selector .card').forEach(card => {
        card.classList.remove('bg-primary', 'text-white');
    });
    
    // Highlight the selected date
    dateElement.classList.add('bg-primary', 'text-white');
    
    // Reset studio and time selection
    selectedStudioId = null;
    selectedTime = null;
    document.getElementById('next-to-seats').disabled = true;
    
    // Load schedules for the selected date
    loadSchedule();
}

// Step 2: Load Schedule
async function loadSchedule() {
    const scheduleContainer = document.getElementById('schedule-container');
    scheduleContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    
    try {
        // Gunakan API untuk mendapatkan jadwal
        const response = await fetch(`mock_schedules.php?movie_id=${selectedMovieId}&date=${selectedDate}`);
        const data = await response.json();
        
        if (data.success) {
            renderSchedule(data.schedules);
        } else {
            throw new Error(data.message || 'Gagal memuat jadwal');
        }
    } catch (error) {
        console.error('Error loading schedules:', error);
        
        // Fallback ke mock data statis jika error
        const mockData = {
            success: true,
            schedules: [
                {
                    id: 'schedule1',
                    studio_id: 'studio1',
                    studio_nama: 'Studio 1',
                    studio_kapasitas: 100,
                    time: '10:00',
                    available_seats: 85
                },
                {
                    id: 'schedule2',
                    studio_id: 'studio2',
                    studio_nama: 'Studio 2',
                    studio_kapasitas: 80,
                    time: '13:00',
                    available_seats: 75
                }
            ]
        };
        
        renderSchedule(mockData.schedules);
    }
}


function renderSchedule(schedules) {
    const scheduleContainer = document.getElementById('schedule-container');
    scheduleContainer.innerHTML = '';
    
    if (schedules.length === 0) {
        scheduleContainer.innerHTML = '<div class="alert alert-warning">Tidak ada jadwal tersedia untuk tanggal ini</div>';
        return;
    }
    
    // Group schedules by studio
    const studios = {};
    schedules.forEach(schedule => {
        if (!studios[schedule.studio_id]) {
            studios[schedule.studio_id] = {
                nama: schedule.studio_nama,
                kapasitas: schedule.studio_kapasitas,
                times: []
            };
        }
        studios[schedule.studio_id].times.push({
            id: schedule.id,
            time: schedule.time,
            available_seats: schedule.available_seats
        });
    });
    
    // Render studio options
    for (const studioId in studios) {
        const studio = studios[studioId];
        const studioCard = document.createElement('div');
        studioCard.className = 'card mb-3';
        studioCard.innerHTML = `
            <div class="card-header">
                <h6 class="mb-0">${studio.nama} (Kapasitas: ${studio.kapasitas})</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2" id="times-${studioId}"></div>
            </div>
        `;
        scheduleContainer.appendChild(studioCard);
        
        // Render time options for each studio
        const timesContainer = document.getElementById(`times-${studioId}`);
        studio.times.forEach(time => {
            const timeBtn = document.createElement('button');
            timeBtn.className = 'btn btn-outline-primary screening-time';
            timeBtn.textContent = time.time;
            timeBtn.title = `${time.available_seats} kursi tersedia`;
            timeBtn.dataset.studioId = studioId;
            timeBtn.dataset.time = time.time;
            timeBtn.dataset.scheduleId = time.id;
            
            timeBtn.addEventListener('click', (e) => {
                selectSchedule(time.id, studioId, time.time, e.target);
            });
            
            timesContainer.appendChild(timeBtn);
        });
    }
}

function selectSchedule(scheduleId, studioId, time, element) {
    selectedStudioId = studioId;
    selectedTime = time;
    currentScheduleId = scheduleId;
    
    // Update UI
    document.querySelectorAll('.screening-time').forEach(btn => {
        btn.classList.remove('selected');
    });
    element.classList.add('selected');
    document.getElementById('next-to-seats').disabled = false;
    
    // Jangan reset selectedSeats di sini agar tetap terjaga
    // ketika kembali dari step 4 ke step 3
    
    // Update summary
    updateBookingSummary();
}

// Step 3: Load Seat Map
async function loadSeatMap() {
    loadSeatMapWithCapacity(100)
    const seatMap = document.getElementById('seat-map');
    seatMap.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Memuat denah kursi...</p></div>';
    
    console.log("Loading seats for schedule ID:", currentScheduleId);
    
    try {
        const response = await fetch(`get_seats.php?schedule_id=${currentScheduleId}`);
        const data = await response.json();
        
        console.log("Seat data response:", data);
        
        if (data.success) {
            renderSeatMap(data.seats, data.occupied_seats);
            return data;
        } else {
            throw new Error(data.message || 'Gagal memuat denah kursi');
        }
    } catch (error) {
        console.error('Error loading seat map:', error);
        
        // Fallback ke mock data statis jika error
        const mockSeats = [];
        const totalRows = 5;
        const seatsPerRow = 10;
        
        // Generate mock seats
        for (let row = 0; row < totalRows; row++) {
            const rowLetter = String.fromCharCode(65 + row);
            for (let col = 1; col <= seatsPerRow; col++) {
                mockSeats.push(`${rowLetter}${col}`);
            }
        }
        
        // Generate some random occupied seats
        const mockOccupiedSeats = [''];
        
        renderSeatMap(mockSeats, mockOccupiedSeats);
        return { seats: mockSeats, occupied_seats: mockOccupiedSeats };
    }
}


// Fungsi untuk menghasilkan layout kursi secara dinamis berdasarkan kapasitas
function generateSeatLayout(capacity) {
    const seats = [];
    const rows = capacity > 50 ? 
        ['A','B','C','D','E','F','G','H','I','J'] : 
        ['A','B','C','D','E'];
    
    rows.forEach(row => {
        for (let i = 1; i <= 10; i++) {
            if (seats.length < capacity) {
                seats.push(`${row}${i}`);
            }
        }
    });
    
    return seats;
}

// Fungsi untuk mensimulasikan kursi yang sudah terisi
function generateOccupiedSeats(seats, occupancyRate = 0.2) {
    const occupiedCount = Math.floor(seats.length * occupancyRate);
    const shuffled = [...seats].sort(() => 0.5 - Math.random());
    return shuffled.slice(0, occupiedCount);
}

// Fungsi untuk memuat denah kursi dengan kapasitas tertentu
async function loadSeatMapWithCapacity(studioId) {
    const seatMap = document.getElementById('seat-map');
    seatMap.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Memuat denah kursi...</p></div>';

    try {
        // Ambil data studio untuk mendapatkan kapasitas
        const studioResponse = await fetch(`get_studio.php?id=${studioId}`);
        const studioData = await studioResponse.json();
        
        if (!studioData.success) {
            throw new Error('Gagal memuat data studio');
        }

        const capacity = studioData.studio.kapasitas;
        
        // Ambil data kursi yang sudah dipesan
        const seatsResponse = await fetch(`get_booked_seats.php?studio_id=${studioId}`);
        const seatsData = await seatsResponse.json();
        
        if (!seatsData.success) {
            throw new Error('Gagal memuat data kursi');
        }

        const occupiedSeats = seatsData.booked_seats.map(seat => seat.nomor_kursi);
        currentOccupiedSeats = occupiedSeats;

        // Generate layout kursi berdasarkan kapasitas studio
        const seats = generateSeatLayout(capacity);
        
        // Render denah kursi
        renderSeatMap(seats, occupiedSeats);
        
        // Kembalikan ke state pemilihan kursi sebelumnya
        selectedSeats.forEach(seatNumber => {
            const seatElement = document.querySelector(`.seat[data-seat="${seatNumber}"]`);
            if (seatElement && !occupiedSeats.includes(seatNumber)) {
                seatElement.classList.remove('available');
                seatElement.classList.add('selected');
            }
        });

        return { seats, occupiedSeats };
    } catch (error) {
        console.error('Error loading seat map:', error);
        alert('Gagal memuat denah kursi: ' + error.message);
        
        // Fallback ke mock data jika error
        const mockSeats = generateSeatLayout(100);
        const mockOccupiedSeats = ['A1', 'A2', 'B5', 'C3', 'D7'];
        renderSeatMap(mockSeats, mockOccupiedSeats);
        return { seats: mockSeats, occupiedSeats: mockOccupiedSeats };
    }
}

function renderSeatMap(seats, occupiedSeats) {
    const seatMap = document.getElementById('seat-map');
    seatMap.innerHTML = '';
    
    // Create screen
    const screen = document.createElement('div');
    screen.className = 'screen';
    screen.textContent = 'LAYAR';
    seatMap.appendChild(screen);

    // Group seats by row
    const seatsByRow = {};
    seats.forEach(seat => {
        const row = seat.charAt(0);
        if (!seatsByRow[row]) seatsByRow[row] = [];
        seatsByRow[row].push(seat);
    });

    // Create container
    const container = document.createElement('div');
    container.className = 'seat-container';
    
    // Left section (A-E)
    const leftSection = document.createElement('div');
    leftSection.className = 'seat-section';
    ['A','B','C','D','E'].forEach(row => {
        if (seatsByRow[row]) {
            leftSection.appendChild(createSeatRow(row, seatsByRow[row]));
        }
    });
    container.appendChild(leftSection);

    // Right section (F-J) - only if exists
    if (Object.keys(seatsByRow).some(r => ['F','G','H','I','J'].includes(r))) {
        const rightSection = document.createElement('div');
        rightSection.className = 'seat-section';
        ['F','G','H','I','J'].forEach(row => {
            if (seatsByRow[row]) {
                rightSection.appendChild(createSeatRow(row, seatsByRow[row]));
            }
        });
        container.appendChild(rightSection);
    }

    seatMap.appendChild(container);
}

// Helper function to create a seat row
function createSeatRow(rowLetter, seatNumbers) {
    const rowDiv = document.createElement('div');
    rowDiv.className = 'seat-row mb-2';
    
    // Row label
    const label = document.createElement('div');
    label.className = 'row-label';
    label.textContent = rowLetter;
    rowDiv.appendChild(label);
    
    // Seats
    const seatsDiv = document.createElement('div');
    seatsDiv.className = 'seats-wrapper';
    
    seatNumbers.forEach(seatNumber => {
        const seat = createSeat(seatNumber);
        seatsDiv.appendChild(seat);
    });
    
    rowDiv.appendChild(seatsDiv);
    return rowDiv;
}


function calculateOptimalLayout(totalSeats) {
    // Default layout
    let seatsPerRow = 10;
    let spacingColumns = [5]; // Add space after column 5
    
    // Adjust based on total seats
    if (totalSeats <= 50) {
        seatsPerRow = 10;
        spacingColumns = [5];
    } else if (totalSeats <= 80) {
        seatsPerRow = 12;
        spacingColumns = [6];
    } else if (totalSeats <= 100) {
        seatsPerRow = 14;
        spacingColumns = [7];
    } else {
        seatsPerRow = 16;
        spacingColumns = [8];
    }
    
    return {
        seatsPerRow,
        spacingColumns
    };
}


// Fungsi pembuat kursi
function createSeat(seatNumber) {
    const seat = document.createElement('div');
    seat.className = 'seat';
    seat.dataset.seat = seatNumber;
    seat.textContent = seatNumber;
    
    // Check if seat is occupied
    if (currentOccupiedSeats.includes(seatNumber)) {
        seat.classList.add('occupied');
        seat.title = 'Kursi sudah terisi';
    } else {
        seat.classList.add('available');
        seat.title = 'Kursi tersedia';
        seat.addEventListener('click', () => toggleSeatSelection(seat));
    }
    
    return seat;
}

function toggleSeatSelection(seatElement) {
    const seatNumber = seatElement.dataset.seat;
    
    // Prevent selecting occupied seats
    if (seatElement.classList.contains('occupied')) {
        return;
    }
    
    // Check if already selected
    if (seatElement.classList.contains('selected')) {
        // Deselect seat
        seatElement.classList.remove('selected');
        seatElement.classList.add('available');
        selectedSeats = selectedSeats.filter(s => s !== seatNumber);
    } else {
        // Select seat - only if it's available and not already selected
        if (!selectedSeats.includes(seatNumber)) {
            seatElement.classList.remove('available');
            seatElement.classList.add('selected');
            selectedSeats.push(seatNumber);
        }
    }
    
    updateSelectedSeatsDisplay();
    document.getElementById('next-to-payment').disabled = selectedSeats.length === 0;
}

// Calculate seat layout
function calculateSplitLayout(capacity) {
    const seatsPerRow = 10;
    const totalRows = Math.ceil(capacity / seatsPerRow);
    const shouldSplit = capacity >= 80;
    const leftRows = shouldSplit ? Math.floor(totalRows / 2) : totalRows;
    
    return {
        seatsPerRow,
        totalRows,
        shouldSplit,
        leftRows
    };
}

// Update selected seats display
function updateSelectedSeatsDisplay() {
    const selectedSeatsElement = document.getElementById('selected-seats');
    const ticketCountElement = document.getElementById('ticket-count');
    const totalPriceElement = document.getElementById('total-price');
    
    // Sort seats for better display
    const sortedSeats = [...selectedSeats].sort((a, b) => {
        const rowA = a.charAt(0);
        const rowB = b.charAt(0);
        const numA = parseInt(a.substring(1));
        const numB = parseInt(b.substring(1));
        
        if (rowA === rowB) {
            return numA - numB;
        }
        return rowA.localeCompare(rowB);
    });
    
    if (sortedSeats.length > 0) {
        selectedSeatsElement.textContent = sortedSeats.join(', ');
        ticketCountElement.textContent = sortedSeats.length;
        totalPriceElement.textContent = `Rp ${(sortedSeats.length * ticketPrice).toLocaleString('id-ID')}`;
    } else {
        selectedSeatsElement.textContent = '-';
        ticketCountElement.textContent = '0';
        totalPriceElement.textContent = 'Rp 0';
    }
}

// Step 3: Update Booking Summary
function updateBookingSummary() {
    if (!selectedMovieId || !selectedDate || !selectedStudioId || !selectedTime) return;
    
    document.getElementById('summary-movie').textContent = movies[selectedMovieId].judul;
    document.getElementById('summary-date').textContent = formatDisplayDate(selectedDate);
    
    const studioHeader = document.querySelector(`.screening-time.selected`)?.parentElement.parentElement.querySelector('.card-header h6');
    if (studioHeader) {
        document.getElementById('summary-studio').textContent = studioHeader.textContent;
    }
    
    document.getElementById('summary-time').textContent = selectedTime;
}

// Step 4: Update Final Summary
function updateFinalSummary() {
    document.getElementById('final-movie').textContent = movies[selectedMovieId].judul;
    document.getElementById('final-date').textContent = formatDisplayDate(selectedDate);
    
    const studioHeader = document.querySelector(`.screening-time.selected`)?.parentElement.parentElement.querySelector('.card-header h6');
    if (studioHeader) {
        document.getElementById('final-studio').textContent = studioHeader.textContent;
    }
    
    document.getElementById('final-time').textContent = selectedTime;
    
    // Sort seats for better display
    const sortedSeats = [...selectedSeats].sort((a, b) => {
        const rowA = a.charAt(0);
        const rowB = b.charAt(0);
        const numA = parseInt(a.substring(1));
        const numB = parseInt(b.substring(1));
        
        if (rowA === rowB) {
            return numA - numB;
        }
        return rowA.localeCompare(rowB);
    });
    
    document.getElementById('final-seats').textContent = sortedSeats.join(', ');
    document.getElementById('final-ticket-count').textContent = sortedSeats.length;
    
    const subtotal = sortedSeats.length * ticketPrice;
    const total = subtotal + servicesFee;
    
    document.getElementById('final-subtotal').textContent = `Rp ${subtotal.toLocaleString('id-ID')}`;
    document.getElementById('final-service-fee').textContent = `Rp ${servicesFee.toLocaleString('id-ID')}`;
    document.getElementById('final-total-price').textContent = `Rp ${total.toLocaleString('id-ID')}`;
    
    console.log("Final summary updated:", {
        seats: sortedSeats,
        count: sortedSeats.length,
        subtotal,
        servicesFee,
        total
    });
}
// Step 4: Submit Booking
async function submitBooking() {
    const loadingSpinner = document.getElementById('loading-spinner');
    loadingSpinner.classList.remove('d-none');
    
    try {
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value;
        
        // Validate form
        if (!name || !email || !phone) {
            throw new Error('Semua bidang formulir harus diisi');
        }
        
        // Validate seats
        if (selectedSeats.length === 0) {
            throw new Error('Silakan pilih kursi terlebih dahulu');
        }
        
        const response = await fetch('process_booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                schedule_id: currentScheduleId,
                seats: selectedSeats,
                payment_method: paymentMethod,
                customer_name: name,
                customer_email: email,
                customer_phone: phone,
                total_price: (selectedSeats.length * ticketPrice) + servicesFee
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Redirect to booking confirmation page
            window.location.href = `booking_confirmation.php?booking_id=${data.booking_id}`;
        } else {
            throw new Error(data.message || 'Gagal memproses pemesanan');
        }
    } catch (error) {
        console.error('Error submitting booking:', error);
        alert(`Gagal memproses pemesanan: ${error.message}`);
    } finally {
        loadingSpinner.classList.add('d-none');
    }
}

// Document ready event
document.addEventListener('DOMContentLoaded', function() {
    // Event listener untuk movie cards
    document.querySelectorAll('.movie-card').forEach(card => {
        card.addEventListener('click', function() {
            selectMovie(this.dataset.movieId);
        });
    });
    
    // Event listener untuk tombol next/back
    document.getElementById('next-to-seats').addEventListener('click', () => goToStep(3));
    document.getElementById('next-to-payment').addEventListener('click', () => goToStep(4));
    
    // Initialize date picker if we're on step 2 and movie is selected
    if (currentStep === 2 && selectedMovieId) {
        generateDateSelector();
    }
});
</script>
</body>
</html>