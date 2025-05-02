<?php
include '../../api/config.php';
include '../../components/alert.php';

// Verifikasi role admin

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    if (isset($_SESSION['role'])) {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Anda tidak memiliki akses ke halaman ini!');
        header("Location: ../../dashboard");
    } else {
        $_SESSION['showDisruptiveAlert'] = showDisruptiveAlert('danger', 'Gagal!', 'Harap Login Terlebih Dahulu!');
        header("Location: ../../api/login_ext.php");
    }
    exit();
}

// Ambil semua studio dari database
$query = $pdo->prepare("SELECT id, nama, kapasitas FROM studios");
$query->execute();
$studios = $query->fetchAll(PDO::FETCH_ASSOC);

// Format data studio untuk JavaScript
$studio_data = [];
foreach ($studios as $studio) {
    $studio_data[$studio['id']] = [
        'nama' => $studio['nama'],
        'kapasitas' => $studio['kapasitas'],
        'occupiedSeats' => [] // Akan diisi via AJAX
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - Manajemen Kursi Bioskop</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .seat {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        font-size: 16px;
        background-color: #e9ecef;
        color: #495057;
        cursor: pointer;
        transition: all 0.2s;
    }
    .seat.available {
        background-color: #e9ecef; /* Abu-abu */
        color: #495057;
    }
    .seat.occupied {
        background-color: #dc3545; /* Merah */
        color: white;
    }
    .seat.dragging-over {
      transform: scale(1.1);
      box-shadow: 0 0 5px rgba(0,0,0,0.3);
    }
    .screen {
      background: linear-gradient(to bottom, #f8f9fa, #adb5bd);
      height: 30px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #495057;
      font-weight: bold;
      letter-spacing: 2px;
    }
    .row-label {
        width: 40px;
        font-size: 18px;
        font-weight: bold;
        text-align: center;
        margin-right: 12px;
    }
    .seat-container {
        display: flex;
        justify-content: center;
        width: 100%;
        gap: 80px; /* Jarak yang lebih konsisten antara kolom kiri dan kanan */
        margin: 0 auto;
    }
    .seat-section {
        flex: 0 0 auto;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }
    .seat-row {
        display: flex;
        align-items: center;
        margin-bottom: 8px; /* Jarak antar baris yang konsisten */
        height: 44px; /* Tinggi baris yang konsisten */
    }
    .loading-spinner {
      display: none;
      width: 20px;
      height: 20px;
      border: 3px solid rgba(255,255,255,.3);
      border-radius: 50%;
      border-top-color: #fff;
      animation: spin 1s ease-in-out infinite;
      margin-left: 10px;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    .screen {
      background: linear-gradient(to bottom, #f8f9fa, #adb5bd);
      height: 30px;
      border-radius: 4px 4px 50% 50%;
      margin-bottom: 30px;
      text-align: center;
      line-height: 30px;
      color: #495057;
      font-weight: bold;
    }
    .divider::before {
        content: '';
        position: absolute;
        top: -20px;
        left: 50%;
        transform: translateX(-50%);
        height: calc(100% + 40px);
        width: 1px;
        background-color: #dee2e6;
    }
    .divider-label {
        background-color: #fff;
        padding: 5px 10px;
        border-radius: 4px;
        border: 1px solid #dee2e6;
        font-size: 14px;
        position: relative;
        z-index: 2;
        margin-top: 20px;
    }

    .divider {
        position: relative;
        padding: 0 10px;
        text-align: center;
    }
    .seats-wrapper {
        display: flex;
        gap: 4px; /* Jarak antar kursi dalam satu baris */
    }
  </style>
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
      <a class="navbar-brand" href="#">Admin Bioskop</a>
      <div class="ms-auto d-flex align-items-center">
        <div class="dropdown me-3">
          <button class="btn btn-outline-light dropdown-toggle" type="button" id="studioDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            Pilih Studio
          </button>
          <ul class="dropdown-menu" aria-labelledby="studioDropdown">
            <?php foreach ($studios as $studio): ?>
              <li><a class="dropdown-item" href="#" data-studio="<?= $studio['id'] ?>"><?= $studio['nama'] ?> (Kapasitas: <?= $studio['kapasitas'] ?>)</a></li>
            <?php endforeach; ?>
          </ul>
        </div>

        <span class="text-white me-3">Studio: <span id="current-studio">-</span></span>
        <button id="save-btn" class="btn btn-sm btn-success">
          Simpan Perubahan
          <span id="save-spinner" class="loading-spinner"></span>
        </button>
      </div>
    </div>
  </nav>

  <div class="container">
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Manajemen Kursi</h5>
      </div>
      <div class="card-body">
        <div class="d-flex justify-content-between mb-3">
          <div>
            <span class="badge bg-secondary me-2">Tersedia</span>
            <span class="badge bg-danger">Terisi</span>
          </div>
          <div>
            <button id="reset-btn" class="btn btn-sm btn-outline-secondary me-2">Reset</button>
            <button id="fill-all-btn" class="btn btn-sm btn-outline-danger">Tandai Semua Terisi</button>
          </div>
        </div>

        <div class="screen mb-3">LAYAR</div>
        
        <div id="seat-map" class="mb-4">
          <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Silakan pilih studio terlebih dahulu</p>
          </div>
        </div>

        <div class="alert alert-info">
          <strong>Petunjuk:</strong> Klik untuk mengubah status kursi. Drag untuk memilih banyak kursi sekaligus.
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Data studio dari PHP
      const studios = <?php echo json_encode($studio_data); ?>;
      
      // Elemen DOM
      const seatMap = document.getElementById('seat-map');
      const saveBtn = document.getElementById('save-btn');
      const resetBtn = document.getElementById('reset-btn');
      const fillAllBtn = document.getElementById('fill-all-btn');
      const studioNameEl = document.getElementById('current-studio');
      const studioDropdownItems = document.querySelectorAll('.dropdown-item');
      const saveSpinner = document.getElementById('save-spinner');

      // State aplikasi
      let currentStudio = null;
      let currentStudioId = null;
      let currentOccupiedSeats = [];
      let isDragging = false;
      let dragStartSeat = null;

      // Tambahkan event listener untuk menghentikan dragging ketika mouseup terjadi di luar kursi
      document.addEventListener('mouseup', function() {
        isDragging = false;
        document.querySelectorAll('.seat.dragging-over').forEach(seat => {
          seat.classList.remove('dragging-over');
        });
      });

      // Event Dropdown pilih Studio
      studioDropdownItems.forEach(item => {
        item.addEventListener('click', async function(e) {
          e.preventDefault();
          const studioId = this.getAttribute('data-studio');
          
          // Set state
          currentStudioId = studioId;
          currentStudio = studios[studioId];
          studioNameEl.textContent = currentStudio.nama;
          
          // Tampilkan loading
          seatMap.innerHTML = `
            <div class="text-center py-5">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p class="mt-2">Memuat data kursi studio ${currentStudio.nama}</p>
            </div>
          `;
          
          try {
            // Ambil data kursi terisi dari server
            const response = await fetch(`get_occupied_seats.php?studio_id=${studioId}`);
            const data = await response.json();
            
            if (data.success) {
              currentOccupiedSeats = data.occupied_seats || [];
              renderSeatMap();
            } else {
              throw new Error(data.message || 'Gagal memuat data kursi');
            }
          } catch (error) {
            console.error('Error:', error);
            seatMap.innerHTML = `
              <div class="alert alert-danger">
                Gagal memuat data kursi: ${error.message}
              </div>
            `;
          }
        });
      });

      // Fungsi untuk menghitung layout split
      function calculateSplitLayout(kapasitas) {
        const seatsPerRow = 10;
        const totalRows = Math.ceil(kapasitas / seatsPerRow);
        
        // Tentukan apakah perlu split layout
        const shouldSplit = kapasitas > 50;
        const splitAtRow = shouldSplit ? Math.ceil(totalRows / 2) : totalRows;
        
        return {
          seatsPerRow,
          totalRows,
          shouldSplit,
          splitAtRow,
          leftRows: shouldSplit ? splitAtRow : totalRows,
          rightRows: shouldSplit ? totalRows - splitAtRow : 0
        };
      }

      // Fungsi render kursi dengan layout split
      function renderSeatMap() {
        if (!currentStudio) return;
        
        const layout = calculateSplitLayout(currentStudio.kapasitas);
        seatMap.innerHTML = '';
        
        // Container utama
        const container = document.createElement('div');
        container.className = 'seat-container';
        
        // Render bagian kiri
        const leftSection = document.createElement('div');
        leftSection.className = 'seat-section';
        
        for (let row = 0; row < layout.leftRows; row++) {
            const rowLetter = String.fromCharCode(65 + row);
            const rowDiv = document.createElement('div');
            rowDiv.className = 'seat-row';
            
            const rowLabel = document.createElement('span');
            rowLabel.className = 'row-label';
            rowLabel.textContent = rowLetter;
            rowDiv.appendChild(rowLabel);
            
            const seatsWrapper = document.createElement('div');
            seatsWrapper.className = 'seats-wrapper';
            
            for (let col = 1; col <= layout.seatsPerRow; col++) {
                const seatNumber = `${rowLetter}${col}`;
                const seatIndex = (row * layout.seatsPerRow) + col;
                
                if (seatIndex <= currentStudio.kapasitas) {
                    const seat = createSeat(seatNumber);
                    seatsWrapper.appendChild(seat);
                }
            }
            
            rowDiv.appendChild(seatsWrapper);
            leftSection.appendChild(rowDiv);
        }
        
        container.appendChild(leftSection);
        
        // Render bagian kanan jika perlu split
        if (layout.shouldSplit) {
            const rightSection = document.createElement('div');
            rightSection.className = 'seat-section';
            
            for (let row = layout.leftRows; row < layout.totalRows; row++) {
                const rowLetter = String.fromCharCode(65 + row);
                const rowDiv = document.createElement('div');
                rowDiv.className = 'seat-row';
                
                const rowLabel = document.createElement('span');
                rowLabel.className = 'row-label';
                rowLabel.textContent = rowLetter;
                rowDiv.appendChild(rowLabel);
                
                const seatsWrapper = document.createElement('div');
                seatsWrapper.className = 'seats-wrapper';
                
                for (let col = 1; col <= layout.seatsPerRow; col++) {
                    const seatNumber = `${rowLetter}${col}`;
                    const seatIndex = (row * layout.seatsPerRow) + col;
                    
                    if (seatIndex <= currentStudio.kapasitas) {
                        const seat = createSeat(seatNumber);
                        seatsWrapper.appendChild(seat);
                    }
                }
                
                rowDiv.appendChild(seatsWrapper);
                rightSection.appendChild(rowDiv);
            }
            
            container.appendChild(rightSection);
        }
        
        seatMap.appendChild(container);
        
        if (layout.shouldSplit) {
            const exitLabel = document.createElement('div');
            exitLabel.className = 'text-center mt-4';
            exitLabel.innerHTML = '<span class="badge bg-secondary px-3 py-2">Pintu Keluar</span>';
            seatMap.appendChild(exitLabel);
        }
      }

      // Fungsi pembuat kursi
      function createSeat(seatNumber) {
        const seat = document.createElement('div');
        seat.className = 'seat';
        seat.dataset.seat = seatNumber;
        seat.textContent = seatNumber.match(/\d+/)[0]; // Hanya angka saja
        
        if (currentOccupiedSeats.includes(seatNumber)) {
          seat.classList.add('occupied');
        } else {
          seat.classList.add('available');
        }
        
        seat.addEventListener('mousedown', handleSeatMouseDown);
        seat.addEventListener('mouseenter', handleSeatMouseEnter);
        seat.addEventListener('mouseup', handleSeatMouseUp);
        
        return seat;
      }

      function handleSeatMouseDown(e) {
        isDragging = true;
        dragStartSeat = e.target.dataset.seat;
        toggleSeatStatus(e.target);
        e.preventDefault();
      }

      function handleSeatMouseEnter(e) {
        if (isDragging && e.target.classList.contains('seat')) {
          e.target.classList.add('dragging-over');
          toggleSeatStatus(e.target);
        }
      }

      function handleSeatMouseUp() {
        isDragging = false;
        document.querySelectorAll('.seat.dragging-over').forEach(seat => {
          seat.classList.remove('dragging-over');
        });
      }

      function toggleSeatStatus(seatElement) {
        const seatNumber = seatElement.dataset.seat;
        const index = currentOccupiedSeats.indexOf(seatNumber);
        
        if (index === -1) {
          currentOccupiedSeats.push(seatNumber);
          seatElement.classList.remove('available');
          seatElement.classList.add('occupied');
        } else {
          currentOccupiedSeats.splice(index, 1);
          seatElement.classList.remove('occupied');
          seatElement.classList.add('available');
        }
      }

      // Simpan perubahan ke server
      saveBtn.addEventListener('click', async function() {
        if (!currentStudioId) {
            alert('Silakan pilih studio terlebih dahulu');
            return;
        }
        
        saveSpinner.style.display = 'inline-block';
        saveBtn.disabled = true;
        
        try {
            const response = await fetch('save_seats.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                studio_id: currentStudioId,
                occupied_seats: currentOccupiedSeats
            })
            });
            
            const data = await response.json();
            
            if (data.success) {
              showToast('success', `Status kursi studio ${currentStudio.nama} berhasil disimpan!`);
            } else {
              throw new Error(data.message || 'Gagal menyimpan data');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('danger', `Gagal menyimpan: ${error.message}`);
        } finally {
            saveSpinner.style.display = 'none';
            saveBtn.disabled = false;
        }
      });

      // Reset semua kursi menjadi tersedia
      resetBtn.addEventListener('click', function() {
        if (!currentStudioId) {
          alert('Silakan pilih studio terlebih dahulu');
          return;
        }
        
        if (confirm('Apakah Anda yakin ingin mereset semua kursi menjadi tersedia?')) {
          currentOccupiedSeats = [];
          renderSeatMap();
        }
      });

      // Tandai semua kursi sebagai terisi
      fillAllBtn.addEventListener('click', function() {
        if (!currentStudioId) {
          alert('Silakan pilih studio terlebih dahulu');
          return;
        }
        
        if (confirm('Apakah Anda yakin ingin menandai semua kursi sebagai terisi?')) {
          currentOccupiedSeats = [];
          const layout = calculateSplitLayout(currentStudio.kapasitas);
          
          // Generate semua nomor kursi
          for (let row = 0; row < layout.totalRows; row++) {
            const rowLetter = String.fromCharCode(65 + row);
            for (let col = 1; col <= layout.seatsPerRow; col++) {
              const seatIndex = (row * layout.seatsPerRow) + col;
              if (seatIndex <= currentStudio.kapasitas) {
                currentOccupiedSeats.push(`${rowLetter}${col}`);
              }
            }
          }
          
          renderSeatMap();
        }
      });

      // Fungsi untuk menampilkan toast notifikasi
      function showToast(type, message) {
        const toast = document.createElement('div');
        toast.className = 'position-fixed bottom-0 end-0 p-3';
        toast.style.zIndex = '5000';
        toast.innerHTML = `
          <div class="toast show" role="alert">
            <div class="toast-header bg-${type} text-white">
              <strong class="me-auto">${type === 'success' ? 'Sukses' : 'Error'}</strong>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
              ${message}
            </div>
          </div>
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => {
          toast.remove();
        }, 3000);
      }

      document.addEventListener('selectstart', function(e) {
        if (isDragging) e.preventDefault();
      });
    });
  </script>
</body>
</html>