<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}
include '../koneksi.php';

// --- Logika Filter Tanggal ---
// Set tanggal default ke hari ini jika tidak ada filter yang dipilih
$tanggal_filter = date('Y-m-d');
if (isset($_GET['tanggal']) && !empty($_GET['tanggal'])) {
    $tanggal_filter = $_GET['tanggal'];
}

// Ambil data logo
$result_logo = mysqli_query($koneksi, "SELECT nilai_setting FROM konfigurasi WHERE nama_setting = 'logo_sekolah'");
$logo_path = '../' . mysqli_fetch_assoc($result_logo)['nilai_setting'];

// Query Laporan berdasarkan tanggal yang difilter
$queryLaporan = "SELECT s.nis, s.nama_lengkap, s.kelas, a.waktu_absen, a.foto_absen
                 FROM absensi a
                 JOIN siswa s ON a.id_siswa = s.id_siswa
                 WHERE DATE(a.waktu_absen) = ?
                 ORDER BY a.waktu_absen DESC";
$stmt = mysqli_prepare($koneksi, $queryLaporan);
mysqli_stmt_bind_param($stmt, "s", $tanggal_filter);
mysqli_stmt_execute($stmt);
$resultLaporan = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Absensi - Dashboard Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard_style.css"> </head>
<body>

<div class="container">
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo Sekolah" class="logo">
            <h2>Sistem Absensi</h2>
        </div>
        <nav class="sidebar-menu">
            <a href="dashboard.php">📊 Ringkasan</a>
    <a href="statistik.php">📈 Statistik</a> 
    <a href="laporan.php">📖 Riwayat Absensi</a>
    <a href="manajemen_siswa.php">👤 Manajemen Siswa</a>
        </nav>
    </div>

    <main class="main-content">
        <header>
            <h1>Riwayat Absensi</h1>
            <div class="admin-info">
                <span>Selamat datang, <strong><?= htmlspecialchars($_SESSION['username']); ?></strong></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </header>

        <section class="content-box">
            <h2>Filter Riwayat</h2>
            <form method="GET" action="laporan.php">
                <div class="filter-form">
                    <label for="tanggal">Pilih Tanggal:</label>
                    <input type="date" id="tanggal" name="tanggal" value="<?= htmlspecialchars($tanggal_filter); ?>">
                    <button type="submit" class="btn-submit">Tampilkan</button>
                </div>
            </form>
        </section>

        <section class="content-box">
            <h2>Laporan Tanggal: <?= htmlspecialchars(date('d F Y', strtotime($tanggal_filter))) ?></h2>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Waktu Absen</th>
                            <th>Foto Wajah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if (mysqli_num_rows($resultLaporan) > 0) {
                            while ($data = mysqli_fetch_assoc($resultLaporan)) {
                                echo "<tr>";
                                echo "<td>" . $no++ . "</td>";
                                echo "<td>" . htmlspecialchars($data['nis']) . "</td>";
                                echo "<td>" . htmlspecialchars($data['nama_lengkap']) . "</td>";
                                echo "<td>" . htmlspecialchars($data['kelas']) . "</td>";
                                echo "<td>" . htmlspecialchars(date('H:i:s', strtotime($data['waktu_absen']))) . "</td>";
                                // Path foto sekarang sudah lengkap dari database
                                echo "<td><a href='../" . htmlspecialchars($data['foto_absen']) . "' target='_blank'><img src='../" . htmlspecialchars($data['foto_absen']) . "' alt='Foto Absen' class='foto-absen'></a></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center;'>Tidak ada data absensi pada tanggal yang dipilih.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

</body>
</html>