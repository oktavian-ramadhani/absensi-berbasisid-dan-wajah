<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}
include '../koneksi.php';

$id = $_GET['id'];
$pesan = '';

// Ambil data siswa yang akan diedit
$querySiswa = "SELECT * FROM siswa WHERE id_siswa = ?";
$stmt = mysqli_prepare($koneksi, $querySiswa);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$siswa = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nis = $_POST['nis'];
    $nama = $_POST['nama_lengkap'];
    $kelas = $_POST['kelas'];

    $queryUpdate = "UPDATE siswa SET nis = ?, nama_lengkap = ?, kelas = ? WHERE id_siswa = ?";
    $stmtUpdate = mysqli_prepare($koneksi, $queryUpdate);
    mysqli_stmt_bind_param($stmtUpdate, "sssi", $nis, $nama, $kelas, $id);
    if (mysqli_stmt_execute($stmtUpdate)) {
        header("Location: manajemen_siswa.php");
        exit();
    } else {
        $pesan = "<div class='alert alert-danger'>Gagal mengupdate data siswa.</div>";
    }
}
$result_logo = mysqli_query($koneksi, "SELECT nilai_setting FROM konfigurasi WHERE nama_setting = 'logo_sekolah'");
$logo_path = '../uploads/' . mysqli_fetch_assoc($result_logo)['nilai_setting'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Siswa</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard_style.css">
</head>
<body>

<div class="container">
    <div class="sidebar">
       <div class="sidebar-header">
            <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo Sekolah" class="logo">
            <h2>Sistem Absensi</h2>
        </div>
        <nav class="sidebar-menu">
            <a href="dashboard.php">📊 Ringkasan</a>
            <a href="laporan.php">📖 Riwayat Absensi</a>
            <a href="manajemen_siswa.php" class="active">👤 Manajemen Siswa</a>
        </nav>
    </div>

    <main class="main-content">
        <header><h1>Edit Data Siswa</h1></header>
        <section class="content-box">
            <?= $pesan ?>
            <form method="post">
                <div class="form-group">
                    <label>NIS</label>
                    <input type="text" name="nis" value="<?= htmlspecialchars($siswa['nis']) ?>" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($siswa['nama_lengkap']) ?>" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Kelas</label>
                    <input type="text" name="kelas" value="<?= htmlspecialchars($siswa['kelas']) ?>" class="form-control" required>
                </div>
                <button type="submit" class="btn-submit">Update</button>
                 <a href="manajemen_siswa.php" style="margin-left:10px;">Batal</a>
            </form>
        </section>
    </main>
</div>
<style>.form-control { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 5px; }</style>
</body>
</html>