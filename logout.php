<?php
// 1. Hubungkan dengan session yang sedang berjalan
// Ini wajib agar server tahu session mana yang akan dihapus
session_start();

// 2. Hapus semua data session (role, username, id_user, dll)
// Menghosongkan semua variabel $_SESSION yang terdaftar
session_unset();

// 3. Hancurkan session dari sistem secara permanen
// Menghapus data session di folder temporary server
session_destroy();

// 4. Pastikan tidak ada script di bawahnya yang tereksekusi
// Lalu arahkan kembali ke halaman login (index.php)
header("location: index.php");
exit();
?>