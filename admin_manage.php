<?php
include 'config.php';
if ($_SESSION['role'] != 'admin') die("Akses Ditolak");

// Tambah Alat
if (isset($_POST['tambah'])) {
    $nama = $_POST['nama_alat'];
    mysqli_query($conn, "INSERT INTO alat (nama_alat) VALUES ('$nama')");
    header("location: dashboard.php");
}

// Hapus Alat
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM alat WHERE id=$id");
    header("location: dashboard.php");
}
?>
<form method="POST">
    <input type="text" name="nama_alat" placeholder="Nama Alat" required>
    <button type="submit" name="tambah">Simpan Alat</button>
</form>