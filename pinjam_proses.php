<?php
include 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// --- LOGIKA VALIDASI DENDA BELUM LUNAS ---
$id_user = $_SESSION['id_user'];
$cek_denda = mysqli_query($conn, "SELECT SUM(denda) as total_denda FROM peminjaman 
                                  WHERE id_user = '$id_user' AND denda > 0");
$data_denda = mysqli_fetch_assoc($cek_denda);

if ($data_denda['total_denda'] > 0) {
    echo "<script>
            alert('Gagal! Kamu memiliki denda sebesar Rp " . number_format($data_denda['total_denda'], 0, ',', '.') . " yang belum dibayar. Silakan hubungi petugas untuk melunasi denda.');
            window.location.href = 'dashboard.php';
          </script>";
    exit;
}
// -----------------------------------------

// --- LOGIKA VALIDASI MAKSIMAL 5 BARANG ---
$cek_kuota = mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman 
                                  WHERE id_user = '$id_user' 
                                  AND status_pjm IN ('menunggu', 'disetujui')");
$data_kuota = mysqli_fetch_assoc($cek_kuota);

// Hitung sisa kuota yang boleh dipinjam (Maksimal 5)
$sisa_kuota = 5 - $data_kuota['total'];

if ($sisa_kuota <= 0) {
    echo "<script>
            alert('Gagal! Kamu sudah meminjam 5 barang. Selesaikan atau kembalikan pinjaman sebelumnya untuk meminjam lagi.');
            window.location.href = 'dashboard.php';
          </script>";
    exit;
}
// -----------------------------------------

// Ambil ID Alat dari URL
$id_alat = $_GET['id'];
$query = mysqli_query($conn, "SELECT * FROM alat WHERE id_alat = '$id_alat'");
$data = mysqli_fetch_assoc($query);

// Jika alat tidak ditemukan
if (!$data) { echo "Alat tidak ditemukan!"; exit; }

// Tentukan batas maksimal input (mana yang lebih kecil: stok alat atau sisa kuota siswa)
$max_input = min($data['stok'], $sisa_kuota);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Konfirmasi Peminjaman</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow border-0">
                    <div class="card-header bg-primary text-white text-center">
                        <h5>Form Peminjaman Alat</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-info py-2" style="font-size: 0.85rem;">
                            Sisa kuota pinjam Anda: <strong><?= $sisa_kuota ?> barang</strong>
                        </div>

                        <form action="aksi.php" method="POST">
                            <input type="hidden" name="id_alat" value="<?= $data['id_alat']; ?>">
                            <input type="hidden" name="id_user" value="<?= $_SESSION['id_user']; ?>">

                            <div class="mb-3">
                                <label class="form-label fw-bold">Nama Alat</label>
                                <input type="text" class="form-control bg-white" value="<?= $data['nama_alat']; ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Jumlah yang Dipinjam</label>
                                <input type="number" name="jumlah_pinjam" class="form-control" 
                                       min="1" max="<?= $max_input ?>" value="1" required>
                                <div class="form-text text-muted">Stok tersedia: <?= $data['stok'] ?> | Maks pinjam: <?= $max_input ?></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Tanggal Pinjam</label>
                                <input type="datetime-local" name="tgl_minta" class="form-control" required value="<?= date('Y-m-d\TH:i'); ?>">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Keperluan</label>
                                <textarea name="keperluan" class="form-control" placeholder="Contoh: Praktek Olahraga Kelas 10" rows="3" required></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="ajukan_pinjam_bulk" class="btn btn-primary">Kirim Permintaan Pinjam</button>
                                <a href="dashboard.php" class="btn btn-light">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>