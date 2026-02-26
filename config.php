<?php
// Konfigurasi Database
$host = "localhost";
$user = "root";
$pass = ""; // Laragon defaultnya kosong, jangan diisi spasi
$db   = "peminjaman_olahraga";

// Membuat Koneksi
$conn = mysqli_connect($host, $user, $pass, $db);

// Cek Koneksi
if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

// Memulai Session untuk RBAC
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>