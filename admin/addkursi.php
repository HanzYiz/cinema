<?php
// Koneksi ke database
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "cinema"; // Ganti dengan nama database kamu

$conn = new mysqli($host, $user, $pass, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Mulai id manual
$id_seat = 101; // mulai dari 1, atau bisa disesuaikan

// Array baris
$baris = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

// Insert kursi
foreach ($baris as $huruf) {
    for ($i = 1; $i <= 10; $i++) {
        $studio_id = 'studio2'; // Sesuaikan studio id
        $nomor_kursi = $i;
        $status = 'available'; // Status awal kursi

        $sql = "INSERT INTO seats (id, studio_id, nomor_kursi, baris, status) 
                VALUES ($id_seat, '$studio_id', $nomor_kursi, '$huruf', '$status')";

        if ($conn->query($sql) === TRUE) {
            echo "Berhasil tambah kursi ID $id_seat (baris $huruf nomor $nomor_kursi)<br>";
        } else {
            echo "Gagal tambah kursi ID $id_seat (baris $huruf nomor $nomor_kursi): " . $conn->error . "<br>";
        }

        $id_seat++; // ID bertambah setiap insert
    }
}

$conn->close();
?>
