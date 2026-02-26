<?php 
include 'config.php'; 

// Cek agar tidak muncul error session_start ganda
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role'])) { 
    header("location: index.php"); 
    exit();
}

$role_user = strtolower($_SESSION['role']); 
$id_user_login = $_SESSION['id_user'];

// --- 1. ADMIN: TAMBAH USER BARU ---
if (isset($_POST['tambah_user']) && $role_user == 'admin') {
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']); 
    $role = $_POST['role'];
    
    mysqli_query($conn, "INSERT INTO users (username, password, role) VALUES ('$user', '$password', '$role')");
    header("location: dashboard.php?page=users&pesan=user_ditambah");
    exit();
}

// --- 2. ADMIN: RESET PASSWORD USER ---
if (isset($_GET['reset_pass']) && $role_user == 'admin') {
    $user = mysqli_real_escape_string($conn, $_GET['reset_pass']);
    $pass_baru = mysqli_real_escape_string($conn, $_GET['pass']); 
    
    mysqli_query($conn, "UPDATE users SET password = '$pass_baru' WHERE username = '$user'");
    header("location: dashboard.php?page=users&pesan=password_diupdate");
    exit();
}

// --- 3. ADMIN: HAPUS USER ---
if (isset($_GET['hapus_user']) && $role_user == 'admin') {
    $id_u = (int)$_GET['hapus_user'];
    
    if($id_u != $id_user_login) {
        mysqli_query($conn, "DELETE FROM peminjaman WHERE id_user = '$id_u'");
        mysqli_query($conn, "DELETE FROM users WHERE id_user = '$id_u'");
        header("location: dashboard.php?page=users&pesan=user_dihapus");
    } else {
        header("location: dashboard.php?page=users&pesan=gagal_hapus_diri_sendiri");
    }
    exit();
}

// --- 4. PETUGAS: TAMBAH ALAT BARU ---
if (isset($_POST['tambah_alat']) && $role_user == 'petugas') {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_alat']);
    $stok = (int)$_POST['stok'];
    
    mysqli_query($conn, "INSERT INTO alat (nama_alat, stok) VALUES ('$nama', '$stok')");
    header("location: dashboard.php?pesan=alat_ditambah");
    exit();
}

// --- 5. PETUGAS: UPDATE (TAMBAH) STOK ALAT ---
if (isset($_POST['update_stok']) && $role_user == 'petugas') {
    $id_alt = (int)$_POST['id_alat'];
    $jumlah_tambah = (int)$_POST['jumlah_tambah'];

    mysqli_query($conn, "UPDATE alat SET stok = stok + $jumlah_tambah WHERE id_alat = $id_alt");
    header("location: dashboard.php?pesan=stok_diupdate");
    exit();
}

// --- 6. PETUGAS: HAPUS ALAT ---
if (isset($_GET['hapus']) && $role_user == 'petugas') {
    $id_alt = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM alat WHERE id_alat = $id_alt");
    header("location: dashboard.php?pesan=alat_dihapus");
    exit();
}

// --- 7. SISWA: PROSES PINJAM BULK ---
if (isset($_POST['ajukan_pinjam_bulk']) && ($role_user == 'peminjam' || $role_user == 'siswa')) {
    $id_alat = (int)$_POST['id_alat'];
    $id_user = (int)$_POST['id_user'];
    $jumlah_pinjam = (int)$_POST['jumlah_pinjam']; 
    $tgl_minta = mysqli_real_escape_string($conn, $_POST['tgl_minta']); 
    $keperluan = mysqli_real_escape_string($conn, $_POST['keperluan']);

    $cek_kuota = mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman 
                                      WHERE id_user = '$id_user' 
                                      AND status_pjm IN ('menunggu', 'disetujui', 'menunggu_kembali')");
    $data_kuota = mysqli_fetch_assoc($cek_kuota);
    $total_saat_ini = $data_kuota['total'];

    if (($total_saat_ini + $jumlah_pinjam) > 5) {
        echo "<script>alert('Gagal! Total pinjaman Anda melebihi batas maksimal 5 barang.'); window.location.href='dashboard.php';</script>";
        exit();
    }

    $cek_stok = mysqli_query($conn, "SELECT stok FROM alat WHERE id_alat = $id_alat");
    $data_stok = mysqli_fetch_assoc($cek_stok);

    if ($data_stok['stok'] <= 0) {
        echo "<script>alert('Gagal! Stok barang baru saja habis (0). Pilih alat lain.'); window.location.href='dashboard.php';</script>";
        exit();
    }

    if ($data_stok['stok'] >= $jumlah_pinjam) {
        mysqli_query($conn, "UPDATE alat SET stok = stok - $jumlah_pinjam WHERE id_alat = $id_alat");
        for ($i = 0; $i < $jumlah_pinjam; $i++) {
            mysqli_query($conn, "INSERT INTO peminjaman (id_user, id_alat, tgl_minta, status_pjm, keperluan) 
                                 VALUES ('$id_user', '$id_alat', '$tgl_minta', 'menunggu', '$keperluan')");
        }
        header("location: dashboard.php?pesan=menunggu_konfirmasi");
    } else {
        header("location: dashboard.php?pesan=stok_habis");
    }
    exit();
}

// --- 8. PETUGAS: SETUJUI PEMINJAMAN ---
if (isset($_GET['setuju']) && $role_user == 'petugas') {
    $id_pjm = (int)$_GET['setuju'];
    mysqli_query($conn, "UPDATE peminjaman SET status_pjm = 'disetujui' WHERE id_peminjaman = $id_pjm");
    header("location: dashboard.php?pesan=disetujui");
    exit();
}

// --- 9. PETUGAS: TOLAK PEMINJAMAN ---
if (isset($_GET['tolak']) && $role_user == 'petugas') {
    $id_pjm = (int)$_GET['tolak'];
    $alasan = isset($_GET['alasan']) ? mysqli_real_escape_string($conn, $_GET['alasan']) : 'Ditolak oleh petugas';
    
    $pjm = mysqli_query($conn, "SELECT id_alat FROM peminjaman WHERE id_peminjaman = $id_pjm");
    $data_pjm = mysqli_fetch_assoc($pjm);
    $id_alt = $data_pjm['id_alat'];

    mysqli_query($conn, "UPDATE peminjaman SET status_pjm = 'ditolak', alasan_tolak = '$alasan' WHERE id_peminjaman = $id_pjm");
    mysqli_query($conn, "UPDATE alat SET stok = stok + 1 WHERE id_alat = $id_alt");
    header("location: dashboard.php?pesan=ditolak");
    exit();
}

// --- 10. PETUGAS: SELESAIKAN (VERSI DIRECT) ---
if (isset($_GET['selesai_pinjam']) && $role_user == 'petugas') {
    $id_pjm = (int)$_GET['selesai_pinjam'];
    $kondisi = isset($_GET['kondisi']) ? mysqli_real_escape_string($conn, $_GET['kondisi']) : 'Baik';
    $denda = isset($_GET['denda']) ? (int)$_GET['denda'] : 0;

    $pjm = mysqli_query($conn, "SELECT id_alat FROM peminjaman WHERE id_peminjaman = $id_pjm");
    $data_pjm = mysqli_fetch_assoc($pjm);
    $id_alt = $data_pjm['id_alat'];

    mysqli_query($conn, "UPDATE peminjaman SET status_pjm = 'selesai', tgl_kembali = NOW(), kondisi_akhir = '$kondisi', denda = '$denda' WHERE id_peminjaman = $id_pjm");
    mysqli_query($conn, "UPDATE alat SET stok = stok + 1 WHERE id_alat = $id_alt");

    header("location: dashboard.php?pesan=barang_kembali");
    exit();
}

// --- 11. PETUGAS: LUNASI DENDA ---
if (isset($_GET['lunasi_denda']) && $role_user == 'petugas') {
    $id_pjm = (int)$_GET['lunasi_denda'];
    mysqli_query($conn, "UPDATE peminjaman SET denda = 0 WHERE id_peminjaman = $id_pjm");
    header("location: dashboard.php?pesan=denda_lunas");
    exit();
}

// --- 12. SISWA: REQUEST KEMBALIKAN BARANG ---
if (isset($_GET['request_kembali']) && ($role_user == 'peminjam' || $role_user == 'siswa')) {
    $id_pjm = (int)$_GET['request_kembali'];
    $kondisi_siswa = mysqli_real_escape_string($conn, $_GET['kondisi']);
    
    mysqli_query($conn, "UPDATE peminjaman SET status_pjm = 'menunggu_kembali', kondisi_peminjam = '$kondisi_siswa' WHERE id_peminjaman = $id_pjm");
    
    header("location: dashboard.php?pesan=menunggu_dicek");
    exit();
}

// --- 13. PETUGAS: KONFIRMASI PENERIMAAN BARANG (AKUMULASI DENDA) ---
if (isset($_GET['final_kembali']) && $role_user == 'petugas') {
    $id_pjm = (int)$_GET['final_kembali'];
    $kondisi_akhir = mysqli_real_escape_string($conn, $_GET['kondisi']);

    // Ambil data untuk hitung denda
    $pjm = mysqli_query($conn, "SELECT id_alat, tgl_minta FROM peminjaman WHERE id_peminjaman = $id_pjm");
    $data_pjm = mysqli_fetch_assoc($pjm);
    $id_alt = $data_pjm['id_alat'];
    $tgl_minta = strtotime($data_pjm['tgl_minta']);
    $tgl_sekarang = time();

    // 1. Logika Denda Kondisi (Cek Manual & Otomatis)
    $denda_kondisi = 0;
    if (isset($_GET['denda_kondisi']) && (int)$_GET['denda_kondisi'] > 0) {
        $denda_kondisi = (int)$_GET['denda_kondisi'];
    } else {
        // Deteksi kata kunci dalam input kondisi
        if (strpos(strtolower($kondisi_akhir), 'rusak') !== false) {
            $denda_kondisi = 20000;
        } elseif (strpos(strtolower($kondisi_akhir), 'hilang') !== false) {
            $denda_kondisi = 50000;
        }
    }

    // 2. Logika Denda Terlambat (5rb per hari setelah 24 jam)
    $denda_terlambat = 0;
    $selisih_detik = $tgl_sekarang - $tgl_minta;
    $jumlah_hari = 0;
    if ($selisih_detik > 86400) { 
        $jumlah_hari = floor($selisih_detik / 86400);
        $denda_terlambat = $jumlah_hari * 5000;
    }

    // 3. TOTAL AKUMULASI DENDA
    $total_denda = $denda_kondisi + $denda_terlambat;

    mysqli_query($conn, "UPDATE peminjaman SET 
                        status_pjm = 'selesai', 
                        tgl_kembali = NOW(), 
                        kondisi_akhir = '$kondisi_akhir', 
                        denda = '$total_denda' 
                        WHERE id_peminjaman = $id_pjm");
    
    mysqli_query($conn, "UPDATE alat SET stok = stok + 1 WHERE id_alat = $id_alt");

    // Kirim data rincian ke URL agar alert di dashboard bisa menampilkan breakdown-nya
    header("location: dashboard.php?page=riwayat&pesan=konfirmasi_berhasil&dk=$denda_kondisi&dt=$denda_terlambat&tt=$total_denda&hr=$jumlah_hari");
    exit();
}
?>