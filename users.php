<?php 
include 'config.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if ($_SESSION['role'] !== 'admin') { header("location: dashboard.php"); exit(); } // Proteksi khusus Admin

$role = strtolower($_SESSION['role']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Pengguna - Pinjam Olahraga</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Gunakan style yang sama dengan dashboard.php Anda agar seragam */
        /* ... (Copy style dari dashboard.php Anda di sini) ... */
    </style>
    <script>
        function confirmReset(username) {
            let newPass = prompt("Masukkan password baru untuk user: " + username);
            if (newPass) {
                window.location.href = "aksi.php?reset_pass=" + username + "&pass=" + encodeURIComponent(newPass);
            }
        }
    </script>
</head>
<body>
    <?php include 'sidebar_template.php'; // Atau copy manual kode sidebar di sini ?>

    <div class="main-content">
        <div class="stats-container">
            <div class="stat-box" style="border-top: 3px solid #7C0A02;">
                <small style="color:#93959c">TOTAL PENGGUNA</small>
                <h2 style="margin: 10px 0;"><?= mysqli_num_rows(mysqli_query($conn, "SELECT id_user FROM users")) ?></h2>
            </div>
            <div class="stat-box" style="border-top: 3px solid #007bff;">
                <small style="color:#93959c">PETUGAS</small>
                <h2 style="margin: 10px 0;"><?= mysqli_num_rows(mysqli_query($conn, "SELECT id_user FROM users WHERE role='petugas'")) ?></h2>
            </div>
            <div class="stat-box" style="border-top: 3px solid #28a745;">
                <small style="color:#93959c">SISWA</small>
                <h2 style="margin: 10px 0;"><?= mysqli_num_rows(mysqli_query($conn, "SELECT id_user FROM users WHERE role='peminjam'")) ?></h2>
            </div>
        </div>

        <div class="content-card">
            <h3>👥 Daftarkan Pengguna Baru</h3>
            <form action="aksi.php" method="POST" style="display:flex; gap:10px; margin-bottom: 20px;">
                <input type="text" name="username" placeholder="Username" required style="flex:2; background:#25272b; border:1px solid #3d3f44; color:#fff; padding:10px; border-radius:10px;">
                <input type="password" name="password" placeholder="Password" required style="flex:2; background:#25272b; border:1px solid #3d3f44; color:#fff; padding:10px; border-radius:10px;">
                <select name="role" style="flex:1; background:#25272b; border:1px solid #3d3f44; color:#fff; border-radius:10px;">
                    <option value="peminjam">Siswa (Peminjam)</option>
                    <option value="petugas">Petugas</option>
                </select>
                <button type="submit" name="tambah_user" class="btn-action btn-success">Daftarkan</button>
            </form>
        </div>

        <div class="content-card">
            <h3>📋 Daftar Semua Pengguna</h3>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Reset Password</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res_users = mysqli_query($conn, "SELECT * FROM users ORDER BY role ASC");
                    while($u = mysqli_fetch_assoc($res_users)): ?>
                    <tr>
                        <td><b><?= $u['username'] ?></b></td>
                        <td>
                            <span class="status-tag <?= $u['role'] == 'admin' ? 'selesai' : ($u['role'] == 'petugas' ? 'waiting' : 'ditolak') ?>">
                                <?= strtoupper($u['role']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if($u['username'] !== $_SESSION['username']): ?>
                                <button onclick="confirmReset('<?= $u['username'] ?>')" class="btn-action btn-warning" style="padding: 5px 15px;">Reset</button>
                            <?php else: ?>
                                <small>Akun Anda</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($u['role'] !== 'admin'): ?>
                                <a href="aksi.php?hapus_user=<?= $u['id_user'] ?>" class="btn-action btn-danger" onclick="return confirm('Hapus user ini?')" style="padding: 5px 15px;">Hapus</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>