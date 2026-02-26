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

$id_user_skrg = $_SESSION['id_user'];
$role = strtolower($_SESSION['role']); 

// Ambil parameter halaman dari URL, defaultnya adalah 'home'
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// --- LOGIKA NOTIFIKASI DENDA OTOMATIS ---
$notif_denda = "";
if (isset($_GET['pesan']) && $_GET['pesan'] == 'konfirmasi_berhasil') {
    $dk = $_GET['dk'] ?? 0; // denda kondisi
    $dt = $_GET['dt'] ?? 0; // denda telat
    $tt = $_GET['tt'] ?? 0; // total denda
    $hr = $_GET['hr'] ?? 0; // jumlah hari telat

    $notif_denda = "✅ Konfirmasi Pengembalian Berhasil!\\n\\n";
    if ($tt > 0) {
        $notif_denda .= "💰 RINCIAN DENDA:\\n";
        if ($dk > 0) $notif_denda .= "- Kondisi Barang: Rp " . number_format($dk, 0, ',', '.') . "\\n";
        if ($dt > 0) $notif_denda .= "- Terlambat ($hr Hari): Rp " . number_format($dt, 0, ',', '.') . "\\n";
        $notif_denda .= "-----------------------------\\n";
        $notif_denda .= "TOTAL DENDA: Rp " . number_format($tt, 0, ',', '.');
    } else {
        $notif_denda .= "🌟 Barang kembali tepat waktu dan kondisi baik. Mantap!";
    }
}

// --- LOGIKA CEK STOK KRITIS UNTUK PETUGAS (PENTING) ---
$stok_kritis = [];
if ($role == 'petugas') {
    // Mengambil alat yang stoknya antara 1 sampai 2
    $cek_stok = mysqli_query($conn, "SELECT nama_alat, stok FROM alat WHERE stok < 3 AND stok > 0");
    while ($row_kritis = mysqli_fetch_assoc($cek_stok)) {
        $stok_kritis[] = $row_kritis['nama_alat'] . " (Sisa: " . $row_kritis['stok'] . ")";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Pinjam Olahraga 🏀</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #0f1011; 
            color: #eeeeee; 
            margin: 0; 
            display: flex; 
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background-color: #18191c;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            border-right: 1px solid #2d2e32;
            display: flex;
            flex-direction: column;
            padding: 24px 16px;
            z-index: 1000;
            box-sizing: border-box;
        }

        .sidebar-brand {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 32px;
            padding-left: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-menu { list-style: none; padding: 0; margin: 0; flex-grow: 1; }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px;
            color: #93959c;
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 4px;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: #25272b;
            color: #ffffff;
        }

        .logout-section {
            position: relative;
            margin-top: auto; 
            padding-top: 16px;
            padding-bottom: 10px; 
            border-top: 1px solid #2d2e32;
        }

        .logout-popup {
            display: none;
            position: absolute;
            bottom: 80px;
            left: 0;
            width: 100%;
            background: #1c1d21;
            border: 1px solid #3d3f44;
            border-radius: 12px;
            box-shadow: 0px 10px 25px rgba(0,0,0,0.5);
            z-index: 1001;
            overflow: hidden;
        }

        .logout-popup a {
            display: block;
            padding: 14px 16px;
            color: #ff4d4d;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .user-profile-container {
            display: flex;
            align-items: center;
            padding: 12px;
            background: #25272b;
            border-radius: 12px;
            cursor: pointer;
        }

        .user-avatar { width: 32px; height: 32px; background: #444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; margin-right: 10px; font-weight: bold; color: #fff; }
        .user-details { flex-grow: 1; overflow: hidden; }
        .user-name { font-size: 13px; font-weight: 600; color: #fff; }
        .user-role { font-size: 11px; color: #93959c; }
        .show-popup { display: block !important; }

        .main-content { margin-left: 280px; flex: 1; padding: 40px; box-sizing: border-box; width: calc(100% - 280px); }
        .content-card { background: #18191c; padding: 24px; border-radius: 24px; border: 1px solid #2d2e32; margin-bottom: 24px; }
        h3 { color: #fff; font-size: 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

        .stats-container { display: flex; gap: 20px; margin-bottom: 25px; }
        .stat-box { flex: 1; padding: 20px; border-radius: 20px; background: #18191c; border: 1px solid #2d2e32; text-align: center; }
        .search-input { width: 100%; padding: 14px 20px; border-radius: 100px; background: #25272b; border: 1px solid #3d3f44; color: white; margin-bottom: 32px; box-sizing: border-box; }
        .grid-barang { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        .card-item { background: #25272b; border: 1px solid #3d3f44; padding: 24px; border-radius: 24px; text-align: center; }
        
        .item-icon { font-size: 40px; margin-bottom: 15px; display: block; }

        .table-icon-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .table-emoji {
            font-size: 20px;
            background: #25272b;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid #3d3f44;
        }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; color: #93959c; font-size: 13px; padding: 12px; border-bottom: 1px solid #2d2e32; }
        td { padding: 16px 12px; border-bottom: 1px solid #2d2e32; font-size: 14px; color: #ccc; }

        .btn-action { display: inline-block; padding: 10px 18px; border-radius: 100px; text-decoration: none; font-size: 13px; font-weight: 600; border: none; cursor: pointer; }
        .btn-success { background: #ffffff; color: #000; }
        .btn-danger { background: #ff4d4d; color: #fff; }
        .btn-warning { background: #ffc107; color: #000; }

        .status-tag { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .ready, .selesai { background: rgba(0, 255, 136, 0.1); color: #00ff88; } 
        .waiting { background: rgba(255, 193, 7, 0.1); color: #ffc107; } 
        .ditolak { background: rgba(255, 77, 77, 0.1); color: #ff4d4d; }

        .alert-stok { background: #442726; color: #ff9b9b; padding: 12px; border-radius: 12px; margin-bottom: 24px; }
        
        /* Style Tambahan untuk Peringatan Stok Kritis Petugas */
        .alert-kritis { background: rgba(255, 193, 7, 0.1); border: 1px solid #ffc107; color: #ffc107; padding: 15px; border-radius: 15px; margin-bottom: 24px; }
    </style>
    
    <script>
        <?php if ($notif_denda != ""): ?>
            alert("<?php echo $notif_denda; ?>");
        <?php endif; ?>

        function filterAlat() {
            let input = document.getElementById('inputCari').value.toLowerCase();
            let rows = document.querySelectorAll("table.tabel-stok tr:not(:first-child)");
            rows.forEach(row => {
                let nama = row.cells[0].innerText.toLowerCase();
                row.style.display = nama.includes(input) ? "" : "none";
            });
            let cards = document.querySelectorAll(".card-item");
            cards.forEach(card => {
                let nama = card.querySelector("h4").innerText.toLowerCase();
                card.style.display = nama.includes(input) ? "" : "none";
            });
        }

        function toggleLogoutMenu() {
            var popup = document.getElementById("logoutPopup");
            popup.classList.toggle("show-popup");
        }

        function prosesTolak(id) {
            let alasan = prompt("❌ Alasan penolakan:", "Alat tidak tersedia/rusak");
            if (alasan) window.location.href = "aksi.php?tolak=" + id + "&alasan=" + encodeURIComponent(alasan);
        }

        function requestKembali(id) {
            let kondisi = prompt("🔍 Sebutkan kondisi barang saat ini (Contoh: Baik/Rusak/Hilang):", "Baik");
            if (kondisi) {
                window.location.href = "aksi.php?request_kembali=" + id + "&kondisi=" + encodeURIComponent(kondisi);
            }
        }

        function konfirmasiDiterima(id, tglPinjam, kondisiSiswa) {
            let dendaKondisi = 0;
            if (kondisiSiswa.toLowerCase().includes("hilang")) {
                dendaKondisi = 50000;
            }

            let pesan = "Siswa melaporkan kondisi: " + kondisiSiswa;
            if (dendaKondisi > 0) {
                pesan += "\n⚠️ PERINGATAN: Barang Hilang! Denda otomatis Rp 50.000 akan dikenakan.";
            }
            pesan += "\n\nApakah barang fisik sudah diterima dengan benar? ✅";

            if (confirm(pesan)) {
                window.location.href = "aksi.php?final_kembali=" + id + 
                                       "&kondisi=" + encodeURIComponent(kondisiSiswa) + 
                                       "&denda_kondisi=" + dendaKondisi;
            }
        }

        function resetPassword(username) {
            let newPass = prompt("🔑 Masukkan password baru untuk " + username + ":");
            if (newPass) {
                window.location.href = "aksi.php?reset_pass=" + username + "&pass=" + encodeURIComponent(newPass);
            }
        }
    </script>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">🏅 Pinjam Olahraga</div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php?page=home" class="<?= $page == 'home' ? 'active' : '' ?>">🏠 Dashboard</a></li>
            <?php if($role == 'admin'): ?>
            <li><a href="dashboard.php?page=users" class="<?= $page == 'users' ? 'active' : '' ?>">👥 Kelola Pengguna</a></li>
            <?php endif; ?>
            <li><a href="dashboard.php?page=peminjaman_aktif" class="<?= $page == 'peminjaman_aktif' ? 'active' : '' ?>">📜 Peminjaman Aktif</a></li>
            <li><a href="dashboard.php?page=riwayat" class="<?= $page == 'riwayat' ? 'active' : '' ?>">📖 Riwayat Aktivitas</a></li>
            <?php if($role == 'petugas'): ?>
            <li><a href="laporan.php">📊 Cetak Laporan</a></li>
            <?php endif; ?>
        </ul>
        <div class="logout-section">
            <div id="logoutPopup" class="logout-popup">
                <a href="logout.php" onclick="return confirm('Apakah anda yakin ingin logout? 👋')">🚪 Logout dari Akun</a>
            </div>
            <div class="user-profile-container" onclick="toggleLogoutMenu()">
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?></div>
                <div class="user-details">
                    <div class="user-name"><?= $_SESSION['username'] ?? 'User' ?></div>
                    <div class="user-role"><?= ucfirst($role) ?></div>
                </div>
                <div style="color:#93959c">⋮</div>
            </div>
        </div>
    </div>

    <div class="main-content">
        <?php if ($page == 'home'): ?>
            
            <?php 
            if ($role == 'petugas' || $role == 'admin'):
                $stok_nol = mysqli_query($conn, "SELECT nama_alat FROM alat WHERE stok = 0");
                if(mysqli_num_rows($stok_nol) > 0): ?>
                    <div class="alert-stok">
                        <marquee>🚨 <strong>STOK HABIS (0):</strong> Segera update stok alat yang kosong! 🚨</marquee>
                    </div>
                <?php endif; 
            endif; ?>

            <?php if ($role == 'petugas' && !empty($stok_kritis)): ?>
                <div class="alert-kritis">
                    <strong>⚠️ PERINGATAN STOK TIPIS:</strong><br>
                    <small>Alat berikut stoknya sudah kurang dari 3, segera lakukan penambahan:</small>
                    <ul style="margin: 8px 0 0 0; font-size: 13px; font-weight: 500;">
                        <?php foreach ($stok_kritis as $item): ?>
                            <li><?= $item ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($role == 'petugas') : ?>
                <div class="content-card">
                    <h3>🛠️ Persetujuan & Verifikasi</h3>
                    <table>
                        <tr><th>Peminjam</th><th>Alat</th><th>Keterangan Pinjam</th><th>Aksi</th></tr>
                        <?php
                        $res_aksi = mysqli_query($conn, "SELECT peminjaman.*, users.username, alat.nama_alat FROM peminjaman JOIN users ON peminjaman.id_user = users.id_user JOIN alat ON peminjaman.id_alat = alat.id_alat WHERE status_pjm IN ('menunggu', 'menunggu_kembali')");
                        if(mysqli_num_rows($res_aksi) > 0):
                        while($l = mysqli_fetch_assoc($res_aksi)) : ?>
                        <tr>
                            <td>👤 <?= $l['username'] ?></td>
                            <td><?= $l['nama_alat'] ?> <br><small><?= strtoupper($l['status_pjm']) ?></small></td>
                            <td><i style="font-size: 12px; color: #93959c;"><?= htmlspecialchars($l['keperluan'] ?? '-') ?></i></td>
                            <td>
                                <?php if($l['status_pjm'] == 'menunggu'): ?>
                                    <a href="aksi.php?setuju=<?= $l['id_peminjaman'] ?>" class="btn-action btn-success">✅ Setuju</a>
                                    <button onclick="prosesTolak(<?= $l['id_peminjaman'] ?>)" class="btn-action btn-danger">❌ Tolak</button>
                                <?php elseif($l['status_pjm'] == 'menunggu_kembali'): ?>
                                    <button onclick="konfirmasiDiterima(<?= $l['id_peminjaman'] ?>, '<?= $l['tgl_minta'] ?>', '<?= $l['kondisi_peminjam'] ?>')" class="btn-action btn-success">📥 Konfirmasi</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; else: echo "<tr><td colspan='4' align='center'>☕ Tidak ada antrean aksi</td></tr>"; endif; ?>
                    </table>
                </div>
                <div class="content-card">
                    <h3>➕ Tambah Jenis Alat</h3>
                    <form action="aksi.php" method="POST" style="display:flex; gap:10px;">
                        <input type="text" name="nama_alat" placeholder="Nama Alat" required style="flex:2; background:#25272b; border:1px solid #3d3f44; color:#fff; padding:10px; border-radius:10px;">
                        <input type="number" name="stok" placeholder="Stok" required style="flex:1; background:#25272b; border:1px solid #3d3f44; color:#fff; padding:10px; border-radius:10px;">
                        <button type="submit" name="tambah_alat" class="btn-action btn-success">💾 Simpan Alat</button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="content-card">
                <h3>📦 Stok Alat Olahraga</h3>
                <input type="text" id="inputCari" onkeyup="filterAlat()" placeholder="🔍 Cari nama alat olahraga..." class="search-input">
                
                <?php if ($role == 'admin' || $role == 'petugas') : ?>
                    <table class="tabel-stok">
                        <tr><th>Nama Alat</th><th>Stok</th><?php if($role == 'petugas'): ?> <th>Update</th> <th>Aksi</th> <?php endif; ?></tr>
                        <?php 
                        $sql_alat = mysqli_query($conn, "SELECT * FROM alat");
                        while($a = mysqli_fetch_assoc($sql_alat)) : 
                            $nama_kcl = strtolower((string)($a['nama_alat'] ?? ''));
                            $n = $nama_kcl; 
                            $emoji_tabel = "⚽"; 
                            if(strpos($nama_kcl, 'basket') !== false) $emoji_tabel = "🏀";
                            elseif(strpos($nama_kcl, 'voli') !== false) $emoji_tabel = "🏐";
                            elseif(strpos($nama_kcl, 'raket') !== false || strpos($nama_kcl, 'badminton') !== false) $emoji_tabel = "🏸";
                            elseif(strpos($nama_kcl, 'tenis') !== false) $emoji_tabel = "🎾";
                            elseif(strpos($nama_kcl, 'pingpong') !== false) $emoji_tabel = "🏓";
                            elseif(strpos($nama_kcl, 'matras') !== false) $emoji_tabel = "🧘";
                            elseif(strpos($nama_kcl, 'lompat') !== false) $emoji_tabel = "🏃";
                            elseif (strpos($n, 'kasti') !== false) $emoji_tabel = "🥎";
                            elseif (strpos($n, 'skipping') !== false) $emoji_tabel = "🪢";
                            elseif (strpos($n, 'american football') !== false) $emoji_tabel = "🏈";
                            elseif (strpos($n, 'baseball') !== false) $emoji_tabel = "⚾️";
                            elseif (strpos($n, 'hockey') !== false) $emoji_tabel = "🏒";
                            elseif (strpos($n, 'golf') !== false) $emoji_tabel = "🏑";
                            elseif (strpos($n, 'kriket') !== false) $emoji_tabel = "🏏";
                            elseif (strpos($n, 'boomerang') !== false) $emoji_tabel = "🪃";
                            elseif (strpos($n, 'lacrosse') !== false) $emoji_tabel = "🥍";
                        ?>
                        <tr>
                            <td>
                                <div class="table-icon-wrapper">
                                    <div class="table-emoji"><?= $emoji_tabel ?></div>
                                    <b><?= $a['nama_alat'] ?></b>
                                </div>
                            </td>
                            <td><span style="font-weight:bold; color:<?= $a['stok'] > 0 ? ($a['stok'] < 3 ? '#ffc107' : '#00ff88') : '#ff4d4d' ?>;"><?= $a['stok'] ?> Unit</span></td>
                            <?php if($role == 'petugas'): ?>
                            <td>
                                <form action="aksi.php" method="POST" style="display:flex; gap:5px;">
                                    <input type="hidden" name="id_alat" value="<?= $a['id_alat'] ?>">
                                    <input type="number" name="jumlah_tambah" style="width:50px; background:#25272b; color:#fff; border:1px solid #3d3f44;">
                                    <button type="submit" name="update_stok" class="btn-action btn-success" style="padding: 2px 10px;">+</button>
                                </form>
                            </td>
                            <td><a href="aksi.php?hapus=<?= $a['id_alat'] ?>" class="btn-action btn-danger" onclick="return confirm('Hapus alat ini? 🗑️')">🗑️</a></td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                <?php else : ?>
                    <div class="grid-barang">
                        <?php 
                        $sql_alat = mysqli_query($conn, "SELECT * FROM alat");
                        while($a = mysqli_fetch_assoc($sql_alat)) : 
                            $nama_kcl = strtolower((string)($a['nama_alat'] ?? ''));
                            $n = $nama_kcl;
                            $emoji = "⚽"; 
                            if(strpos($nama_kcl, 'basket') !== false) $emoji = "🏀";
                            elseif(strpos($nama_kcl, 'voli') !== false) $emoji = "🏐";
                            elseif(strpos($nama_kcl, 'raket') !== false || strpos($nama_kcl, 'badminton') !== false) $emoji = "🏸";
                            elseif(strpos($nama_kcl, 'tenis') !== false) $emoji = "🎾";
                            elseif(strpos($nama_kcl, 'pingpong') !== false) $emoji = "🏓";
                            elseif(strpos($nama_kcl, 'matras') !== false) $emoji = "🧘";
                            elseif(strpos($nama_kcl, 'lompat') !== false) $emoji = "🏃";
                            elseif (strpos($n, 'kasti') !== false) $emoji = "🥎";
                            elseif (strpos($n, 'skipping') !== false) $emoji = "🪢";
                            elseif (strpos($n, 'american football') !== false) $emoji = "🏈";
                            elseif (strpos($n, 'baseball') !== false) $emoji = "⚾️";
                            elseif (strpos($n, 'hockey') !== false) $emoji = "🏒";
                            elseif (strpos($n, 'golf') !== false) $emoji = "🏑";
                            elseif (strpos($n, 'kriket') !== false) $emoji = "🏏";
                            elseif (strpos($n, 'boomerang') !== false) $emoji = "🪃";
                            elseif (strpos($n, 'lacrosse') !== false) $emoji = "🥍";
                        ?>
                        <div class="card-item">
                            <span class="item-icon"><?= $emoji ?></span>
                            <h4><?= $a['nama_alat'] ?></h4>
                            <p style="color:#93959c; font-size:13px;">Stock: <?= $a['stok'] ?></p>
                            <?php if($a['stok'] > 0): ?>
                                <a href="pinjam_proses.php?id=<?= $a['id_alat'] ?>" class="btn-action btn-success" style="width:100%; box-sizing:border-box;">🤝 Pinjam</a>
                            <?php else: ?>
                                <button class="btn-action" style="background: #333; color: #666; width:100%; cursor:not-allowed;" disabled>🚫 Habis</button>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if($role == 'peminjam'): ?>
            <div class="content-card">
                <h3>🔄 Pengembalian Alat</h3>
                <?php 
                $res_pjm_user = mysqli_query($conn, "SELECT peminjaman.*, alat.nama_alat FROM peminjaman JOIN alat ON peminjaman.id_alat = alat.id_alat WHERE id_user = '$id_user_skrg' AND status_pjm = 'disetujui'");
                if(mysqli_num_rows($res_pjm_user) > 0) {
                    while($p = mysqli_fetch_assoc($res_pjm_user)) {
                        echo "<div style='display:flex; justify-content:space-between; align-items:center; background:#25272b; padding:15px; border-radius:12px; margin-bottom:10px;'>
                                <b>📦 {$p['nama_alat']}</b>
                                <button onclick='requestKembali({$p['id_peminjaman']})' class='btn-action btn-warning'>🚀 Kembali</button>
                              </div>";
                    }
                } else { echo "<small>✨ Tidak ada alat yang perlu dikembalikan.</small>"; }
                ?>
            </div>
            <?php endif; ?>

        <?php elseif ($page == 'users' && $role == 'admin'): ?>
            <div class="stats-container">
                <div class="stat-box" style="border-top: 3px solid #7C0A02;">
                    <small style="color:#93959c">👥 TOTAL PENGGUNA</small>
                    <h2 style="margin: 10px 0;"><?= mysqli_num_rows(mysqli_query($conn, "SELECT id_user FROM users")) ?></h2>
                </div>
                <div class="stat-box" style="border-top: 3px solid #007bff;">
                    <small style="color:#93959c">👮 PETUGAS</small>
                    <h2 style="margin: 10px 0;"><?= mysqli_num_rows(mysqli_query($conn, "SELECT id_user FROM users WHERE role='petugas'")) ?></h2>
                </div>
                <div class="stat-box" style="border-top: 3px solid #28a745;">
                    <small style="color:#93959c">🎓 SISWA</small>
                    <h2 style="margin: 10px 0;"><?= mysqli_num_rows(mysqli_query($conn, "SELECT id_user FROM users WHERE role='peminjam'")) ?></h2>
                </div>
            </div>
            <div class="content-card">
                <h3>👥 Daftarkan Pengguna Baru</h3>
                <form action="aksi.php" method="POST" style="display:flex; gap:10px; margin-bottom: 20px;">
                    <input type="text" name="username" placeholder="Username" required style="flex:2; background:#25272b; border:1px solid #3d3f44; color:#fff; padding:10px; border-radius:10px;">
                    <input type="password" name="password" placeholder="Password" required style="flex:2; background:#25272b; border:1px solid #3d3f44; color:#fff; padding:10px; border-radius:10px;">
                    <select name="role" style="flex:1; background:#25272b; border:1px solid #3d3f44; color:#fff; border-radius:10px;">
                        <option value="peminjam">Siswa (Peminjam) 🎓</option>
                        <option value="petugas">Petugas 👮</option>
                    </select>
                    <button type="submit" name="tambah_user" class="btn-action btn-success">➕ Daftarkan</button>
                </form>
            </div>
            <div class="content-card">
                <h3>📋 Daftar Semua Pengguna</h3>
                <table>
                    <thead><tr><th>Username</th><th>Role</th><th>Reset Password</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php
                        $res_users = mysqli_query($conn, "SELECT * FROM users ORDER BY role ASC");
                        while($u = mysqli_fetch_assoc($res_users)): ?>
                        <tr>
                            <td><b>👤 <?= $u['username'] ?></b></td>
                            <td><span class="status-tag <?= $u['role'] == 'admin' ? 'selesai' : ($u['role'] == 'petugas' ? 'waiting' : 'ditolak') ?>"><?= strtoupper($u['role']) ?></span></td>
                            <td>
                                <?php if($u['username'] !== ($_SESSION['username'] ?? '')): ?>
                                    <button onclick="resetPassword('<?= $u['username'] ?>')" class="btn-action btn-warning" style="padding: 5px 15px;">🔑 Reset</button>
                                <?php else: echo "<small>Akun Anda 😊</small>"; endif; ?>
                            </td>
                            <td>
                                <?php if($u['role'] !== 'admin'): ?>
                                    <a href="aksi.php?hapus_user=<?= $u['id_user'] ?>" class="btn-action btn-danger" onclick="return confirm('Hapus user ini? 🗑️')" style="padding: 5px 15px;">🗑️ Hapus</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($page == 'peminjaman_aktif'): ?>
            <div class="content-card">
                <h3>📜 Daftar Peminjaman Aktif</h3>
                <table>
                    <tr><th>Nama Peminjam</th><th>Alat</th><th>Tgl Pinjam</th><th>Status</th></tr>
                    <?php
                    $f = ($role == 'peminjam') ? "AND peminjaman.id_user = '$id_user_skrg'" : "";
                    $res_aktif = mysqli_query($conn, "SELECT peminjaman.*, users.username, alat.nama_alat FROM peminjaman JOIN users ON peminjaman.id_user = users.id_user JOIN alat ON peminjaman.id_alat = alat.id_alat WHERE status_pjm NOT IN ('selesai', 'ditolak') $f ORDER BY id_peminjaman DESC");
                    while($l = mysqli_fetch_assoc($res_aktif)): ?>
                    <tr>
                        <td>👤 <?= $l['username'] ?></td>
                        <td>📦 <?= $l['nama_alat'] ?></td>
                        <td>🗓️ <?= $l['tgl_minta'] ?></td>
                        <td><span class="status-tag waiting">⏳ <?= $l['status_pjm'] ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>

        <?php elseif ($page == 'riwayat'): ?>
            <div class="content-card">
                <h3>📖 Riwayat Aktivitas Lengkap</h3>
                <table>
                    <thead><tr><th>User</th><th>Alat</th><th>Tgl Pinjam</th><th>Tgl Kembali</th><th>Denda</th><th>Status</th><th>Keterangan</th><?php if($role == 'petugas'): ?><th>Aksi</th><?php endif; ?></tr></thead>
                    <tbody>
                        <?php
                        $f = ($role == 'peminjam') ? "AND peminjaman.id_user = '$id_user_skrg'" : "";
                        $res_log = mysqli_query($conn, "SELECT peminjaman.*, users.username, alat.nama_alat FROM peminjaman JOIN users ON peminjaman.id_user = users.id_user JOIN alat ON peminjaman.id_alat = alat.id_alat WHERE status_pjm IN ('selesai', 'ditolak') $f ORDER BY id_peminjaman DESC");
                        while($log = mysqli_fetch_assoc($res_log)): 
                            $status_class = ($log['status_pjm'] == 'selesai') ? 'ready' : 'ditolak';
                            $tgl_kembali = ($log['status_pjm'] == 'selesai') ? $log['tgl_kembali'] : '-';
                            $denda_nilai = $log['denda'] ?? 0;
                            $denda_teks = ($denda_nilai == 0 && $log['status_pjm'] == 'selesai') ? "✅ Lunas" : "Rp " . number_format($denda_nilai, 0, ',', '.');
                            $keterangan = ($log['status_pjm'] == 'ditolak') ? ($log['alasan_tolak'] ?? '-') : ($log['kondisi_peminjam'] ?? '-');
                        ?>
                        <tr>
                            <td><b>👤 <?= $log['username'] ?></b></td>
                            <td>📦 <?= $log['nama_alat'] ?></td>
                            <td><small>🗓️ <?= $log['tgl_minta'] ?></small></td>
                            <td><small>🔙 <?= $tgl_kembali ?></small></td>
                            <td style="color: <?= ($denda_nilai == 0) ? '#00ff88' : '#ff4d4d' ?>; font-weight: bold;"><?= $denda_teks ?></td>
                            <td><span class="status-tag <?= $status_class ?>"><?= $log['status_pjm'] ?></span></td>
                            <td><i style="font-size: 12px; color: #93959c;"><?= $keterangan ?></i></td>
                            <?php if($role == 'petugas'): ?>
                            <td>
                                <?php if($denda_nilai > 0): ?>
                                    <a href="aksi.php?lunasi_denda=<?= $log['id_peminjaman'] ?>" class="btn-action btn-success" style="padding: 5px 10px; font-size: 10px;" onclick="return confirm('Denda sudah dibayar lunas? 💸')">💸 LUNASI</a>
                                <?php else: echo "-"; endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>