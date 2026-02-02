<?php
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Check if user exists in users table
    $stmt = $pdo->prepare("SELECT u.*, k.id as karyawan_id FROM users u LEFT JOIN karyawan k ON u.id=k.user_id WHERE u.username=? AND u.status='active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Check if user is linked to karyawan
        if ($user['karyawan_id']) {
            $_SESSION['karyawan_id'] = $user['karyawan_id'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama_lengkap'];
            header("Location: karyawan_dashboard.php");
            exit;
        } else {
            $error = "Akun tidak terhubung dengan data karyawan. Hubungi administrator.";
        }
    } else {
        $error = "Username atau password salah";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Login Karyawan - Rangkiang Peduli Negeri</title>
<style>
* { margin:0; padding:0; box-sizing:border-box }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height:100vh; display:flex; align-items:center; justify-content:center }
.login-container { background:#fff; padding:40px; border-radius:10px; box-shadow:0 10px 40px rgba(0,0,0,0.2); width:100%; max-width:400px }
.login-container h1 { text-align:center; margin-bottom:30px; color:#2c3e50 }
.form-group { margin-bottom:20px }
.form-group label { display:block; margin-bottom:8px; color:#555; font-weight:600 }
.form-group input { width:100%; padding:12px; border:1px solid #ddd; border-radius:5px; font-size:14px }
.btn { width:100%; padding:12px; background:#3498db; color:#fff; border:none; border-radius:5px; font-size:16px; cursor:pointer; font-weight:600 }
.btn:hover { background:#2980b9 }
.error { background:#f8d7da; color:#721c24; padding:12px; border-radius:5px; margin-bottom:20px; border:1px solid #f5c6cb }
</style>
</head>
<body>
<div class="login-container">
    <h1>🏛️ Login Karyawan</h1>
    <?php if($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required autofocus>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn">Login</button>
    </form>
</div>
</body>
</html>

