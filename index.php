<?php 
include 'config.php'; 

// Perbaikan: Gunakan pengecekan agar tidak double session_start jika di config.php sudah ada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login, lempar ke dashboard
if (isset($_SESSION['role'])) {
    header("location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pinjam Olahraga</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            /* Background Gelap sesuai tema Dashboard sebelumnya */
            background-color: #0f1011;
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background: #18191c; /* Warna Card sesuai Dashboard */
            padding: 40px;
            border-radius: 32px;
            border: 1px solid #2d2e32;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            width: 380px;
            text-align: center;
        }

        h2 { 
            /* Judul warna SALMON untuk identitas */
            color: #FA8072; 
            margin-bottom: 5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: -0.5px;
        }

        p.subtitle {
            color: #93959c;
            font-size: 14px;
            margin-bottom: 30px;
        }

        label {
            display: block;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #eeeeee;
            margin-bottom: 8px;
            margin-left: 5px;
        }

        input {
            width: 100%;
            padding: 14px 16px;
            margin-bottom: 20px;
            background: #25272b;
            border: 1px solid #3d3f44;
            border-radius: 14px;
            color: white;
            outline: none;
            transition: 0.3s;
            font-size: 15px;
        }

        input:focus {
            /* Focus border warna Salmon */
            border-color: #FA8072;
        }

        button {
            width: 100%;
            padding: 14px;
            /* Button warna BARN RED sesuai permintaan asli */
            background: #7C0A02;
            color: white;
            border: none;
            border-radius: 100px;
            cursor: pointer;
            font-weight: 700;
            font-size: 15px;
            transition: 0.3s;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(124, 10, 2, 0.2);
        }

        button:hover {
            background: #5a0701;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(124, 10, 2, 0.4);
        }

        .error-box {
            background: rgba(214, 48, 49, 0.1);
            color: #ff4d4d;
            padding: 12px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 20px;
            border-left: 5px solid #7C0A02;
            text-align: left;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>✨SELAMAT DATANG✨</h2>
    <p class="subtitle">Sistem Peminjaman Alat Olahraga</p>

    <?php
    if (isset($_POST['login'])) {
        $user = $_POST['username'];
        $pass = $_POST['password'];

        // Menggunakan mysqli_real_escape_string untuk keamanan tambahan
        $user = mysqli_real_escape_string($conn, $user);
        $pass = mysqli_real_escape_string($conn, $pass);

        $query = mysqli_query($conn, "SELECT * FROM users WHERE username='$user' AND password='$pass'");
        $data = mysqli_fetch_assoc($query);

        if ($data) {
            $_SESSION['id_user']  = $data['id_user'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['role']     = $data['role'];
            header("location: dashboard.php");
            exit();
        } else {
            echo "<div class='error-box'>Username atau Password salah!</div>";
        }
    }
    ?>

    <form method="POST">
        <label>Username</label>
        <input type="text" name="username" placeholder="Masukkan username" required>
        
        <label>Password</label>
        <input type="password" name="password" placeholder="Masukkan password" required>
        
        <button type="submit" name="login">Masuk Sekarang</button>
    </form>

    <div style="margin-top: 30px; font-size: 11px; color: #444;">
        PINJAM OLAHRAGA &bull; 2026
    </div>
</div>

</body>
</html>