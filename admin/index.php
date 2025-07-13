<?php
// Pastikan tidak ada output sebelum baris ini
// Mulai session
session_start();

// Jika sudah login, langsung redirect ke dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}

// Sertakan file koneksi SETELAH pengecekan session di atas
require_once '../koneksi.php'; // Menggunakan require_once lebih aman

$error = '';
// Proses form hanya jika metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validasi sederhana
    if (empty($username) || empty($password)) {
        $error = "Username dan password tidak boleh kosong!";
    } else {
        $query = "SELECT id_admin, username FROM admin WHERE username = ? AND password = ?";
        $stmt = mysqli_prepare($koneksi, $query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $username, $password);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) == 1) {
                $admin = mysqli_fetch_assoc($result);
                // Set session
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['username'] = $admin['username'];
                $_SESSION['id_admin'] = $admin['id_admin'];

                // Redirect ke dashboard
                header("Location: dashboard.php");
                exit(); // Wajib ada exit setelah header location
            } else {
                $error = "Username atau password salah!";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "Terjadi kesalahan pada sistem.";
        }
    }
}
// Bagian HTML dimulai di sini
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="glass-container">
        <h1>Login Admin</h1>
        <form action="index.php" method="post" novalidate>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="admin" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" value="admin" required>
            </div>
            <?php if(!empty($error)): ?>
                <div class="alert alert-gagal" style="margin-bottom: 15px;"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <button type="submit" class="btn">Login</button>
        </form>
    </div>
</body>
</html>