<?php
include 'koneksi.php';

if (isset($_POST['kembalikan'])) {
    $id_pjm = $_POST['id_peminjaman'];
    $kondisi = $_POST['kondisi_akhir'];
    $tgl_kembali = date('Y-m-d');

    // Cukup update tabel peminjaman
    // Database (Trigger) akan otomatis menambah stok JIKA kondisi == 'Baik'
    $query = "UPDATE peminjaman SET 
              status_pjm = 'selesai', 
              tgl_kembali = '$tgl_kembali', 
              kondisi_akhir = '$kondisi' 
              WHERE id_peminjaman = '$id_pjm'";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Data Berhasil Disimpan!'); window.location='data_peminjaman.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>