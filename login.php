<?php 
include 'config.php'; 
session_start(); // Pastikan session dimulai untuk menyimpan login

// Logika Asli Kamu (Tetap Dipertahankan)
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $query = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' AND password='$password'");
    $data = mysqli_fetch_assoc($query);

    if ($data) {
        $_SESSION['username'] = $data['username']; 
        $_SESSION['id_user'] = $data['id_user']; // Sesuaikan dengan dashboard yang memanggil id_user
        $_SESSION['role'] = $data['role'];
        header("location: dashboard.php");
    } else {
        $error = "Login Gagal! Username atau Password salah.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - SKL PINJAM</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* DASHBOARD DARK MODE - CHARACTER AI STYLE */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f1011; /* Hitam pekat khas Character.ai */
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #eeeeee;
        }

        .login-card {
            background: #18191c; /* Warna kartu sedikit lebih terang dari BG */
            padding: 40px;
            border-radius: 32px; /* Lebih membulat modern */
            border: 1px solid #2d2e32;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            width: 360px;
            text-align: center;
        }

        h2 { 
            color: #ffffff; 
            margin-bottom: 8px; 
            font-size: 24px;
            font-weight: 700;
        }

        .subtitle {
            color: #93959c;
            font-size: 14px;
            margin-bottom: 30px;
        }

        /* Styling Input */
        input {
            width: 100%;
            padding: 14px 16px;
            margin-bottom: 15px;
            background-color: #25272b;
            border: 1px solid #3d3f44;
            border-radius: 14px;
            box-sizing: border-box;
            color: white;
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s;
        }

        input:focus {
            border-color: #555;
        }

        /* Tombol ala Character.ai (Putih bersih) */
        button {
            width: 100%;
            padding: 14px;
            background-color: #ffffff;
            color: #000000;
            border: none;
            border-radius: 100px;
            cursor: pointer;
            font-weight: 700;
            font-size: 15px;
            margin-top: 10px;
            transition: transform 0.2s, opacity 0.2s;
        }

        button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Notifikasi Error */
        .error-msg {
            color: #ff4d4d;
            font-size: 13px;
            margin-bottom: 20px;
            background: rgba(255, 77, 77, 0.1);
            padding: 12px;
            border-radius: 12px;
            border: 1px solid rgba(255, 77, 77, 0.2);
        }

        label {
            display: block;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #93959c;
            padding-left: 4px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <h2>SKL-PINJAM</h2>
    <div class="subtitle">Welcome back! Please login to your account.</div>
    
    <?php if (isset($error)) : ?>
        <div class="error-msg"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Username</label>
        <input type="text" name="username" placeholder="Username" required>
        
        <label>Password</label>
        <input type="password" name="password" placeholder="Password" required>
        
        <button type="submit" name="login">Login</button>
    </form>
    
    <div style="margin-top: 25px; font-size: 11px; color: #444;">
        Inventaris Olahraga System v2.0
    </div>
</div>

</body>
</html>