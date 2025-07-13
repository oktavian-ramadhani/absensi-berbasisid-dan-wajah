<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}
include '../koneksi.php';

$pesan = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nis = $_POST['nis'];
    $nama = $_POST['nama_lengkap'];
    $kelas = $_POST['kelas'];

    if (empty($nis) || empty($nama) || empty($kelas)) {
        $pesan = "<div class='alert alert-danger'>Semua kolom wajib diisi.</div>";
    } else {
        $query = "INSERT INTO siswa (nis, nama_lengkap, kelas) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "sss", $nis, $nama, $kelas);
        if (mysqli_stmt_execute($stmt)) {
            header("Location: manajemen_siswa.php");
            exit();
        } else {
            $pesan = "<div class='alert alert-danger'>Gagal menambahkan siswa. NIS mungkin sudah ada.</div>";
        }
    }
}
$result_logo = mysqli_query($koneksi, "SELECT nilai_setting FROM konfigurasi WHERE nama_setting = 'logo_sekolah'");
$logo_path = '../uploads/' . mysqli_fetch_assoc($result_logo)['nilai_setting'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Siswa</title>
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
        <header>
            <h1>Tambah Siswa Baru</h1>
        </header>

        <section class="content-box">
            <?= $pesan ?>
            <form action="tambah_siswa.php" method="post">
                <div class="form-group">
                    <label for="nis">Nomor Induk Siswa (NIS)</label>
                    <input type="text" id="nis" name="nis" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="nama_lengkap">Nama Lengkap</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="kelas">Kelas</label>
                    <input type="text" id="kelas" name="kelas" class="form-control" required>
                </div>
                <button type="submit" class="btn-submit">Simpan</button>
                <a href="manajemen_siswa.php" style="margin-left:10px;">Batal</a>
            </form>
        </section>
    </main>
</div>
<style>.form-control { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 5px; }</style>
</body>
</html>