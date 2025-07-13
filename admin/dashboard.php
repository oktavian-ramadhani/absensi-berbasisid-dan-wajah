<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}
include '../koneksi.php';

// --- PROSES UPDATE LOGO --- (Fungsi ini tetap ada di sini)
$pesan_update = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['logo_baru'])) {
    if (isset($_FILES['logo_baru']) && $_FILES['logo_baru']['error'] === 0) {
        $logo = $_FILES['logo_baru'];
        $check = getimagesize($logo['tmp_name']);
        if ($check) {
            $nama_file = 'logo_' . time() . '.' . pathinfo($logo['name'], PATHINFO_EXTENSION);
            $lokasi_upload = '../uploads/' . $nama_file;
            if (move_uploaded_file($logo['tmp_name'], $lokasi_upload)) {
                mysqli_query($koneksi, "UPDATE konfigurasi SET nilai_setting = 'uploads/$nama_file' WHERE nama_setting = 'logo_sekolah'");
                $pesan_update = "<div class='alert alert-success'>Logo berhasil diperbarui! Halaman akan dimuat ulang. <script>setTimeout(function(){ window.location.href = 'dashboard.php'; }, 2000);</script></div>";
            }
        }
    }
}

// --- PENGAMBILAN DATA STATISTIK ---
$result_jml_siswa = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM siswa");
$jml_siswa = mysqli_fetch_assoc($result_jml_siswa)['total'];
$today = date('Y-m-d');
$result_absen_hari_ini = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM absensi WHERE DATE(waktu_absen) = '$today'");
$absen_hari_ini = mysqli_fetch_assoc($result_absen_hari_ini)['total'];
$result_total_absen = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM absensi");
$total_absen = mysqli_fetch_assoc($result_total_absen)['total'];
$result_logo = mysqli_query($koneksi, "SELECT nilai_setting FROM konfigurasi WHERE nama_setting = 'logo_sekolah'");
$logo_path = '../' . mysqli_fetch_assoc($result_logo)['nilai_setting'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard_style.css">
</head>
<body>

<div id="welcomeModal" class="modal">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h3>Halo! Saya Oktavian Ramadhani</h3>
        <p>Terima kasih telah menggunakan web project ini, semoga bisa bermanfaat. Untuk informasi lebih lanjut, Anda dapat menghubungi saya di <strong>085183036226</strong>.</p>
    </div>
</div>

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
            <h1>Ringkasan Hari Ini</h1>
            <div class="admin-info">
                <span>Selamat datang, <strong><?= htmlspecialchars($_SESSION['username']); ?></strong></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </header>

        <section class="stat-cards">
            <div class="card">
                <h3>Jumlah Siswa</h3>
                <p class="stat-value"><?= $jml_siswa ?></p>
            </div>
            <div class="card">
                <h3>Absen Hari Ini</h3>
                <p class="stat-value"><?= $absen_hari_ini ?></p>
            </div>
            <div class="card">
                <h3>Total Rekap Absen</h3>
                <p class="stat-value"><?= $total_absen ?></p>
            </div>
        </section>

        <section class="content-box">
            <h2>Laporan Cepat Hari Ini (<?= date('d M Y') ?>)</h2>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Waktu Absen</th>
                            <th>Foto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $queryLaporan = "SELECT s.nis, s.nama_lengkap, a.waktu_absen, a.foto_absen FROM absensi a JOIN siswa s ON a.id_siswa = s.id_siswa WHERE DATE(a.waktu_absen) = '$today' ORDER BY a.waktu_absen DESC LIMIT 5";
                        $resultLaporan = mysqli_query($koneksi, $queryLaporan);
                        if (mysqli_num_rows($resultLaporan) > 0) {
                            while ($data = mysqli_fetch_assoc($resultLaporan)) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($data['nis']) . "</td>";
                                echo "<td>" . htmlspecialchars($data['nama_lengkap']) . "</td>";
                                echo "<td>" . htmlspecialchars(date('H:i:s', strtotime($data['waktu_absen']))) . "</td>";
                                echo "<td><a href='../" . htmlspecialchars($data['foto_absen']) . "' target='_blank'><img src='../" . htmlspecialchars($data['foto_absen']) . "' alt='Foto' class='foto-absen'></a></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' style='text-align:center;'>Belum ada data absensi hari ini.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="content-box">
            <h2>Konfigurasi Aplikasi</h2>
            <?= $pesan_update ?>
            <form action="dashboard.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="logo_baru">Ganti Logo Sekolah:</label>
                    <input type="file" name="logo_baru" id="logo_baru" accept="image/png, image/jpeg" required>
                </div>
                <button type="submit" class="btn-submit">Upload Logo</button>
            </form>
        </section>

 
    </section>

    <footer class="main-footer">
        <marquee>
            Halo, saya Oktavian Ramadhani. Terima kasih telah menggunakan web project ini, semoga bisa bermanfaat. Informasi lebih lanjut hubungi 085183036226.
        </marquee>
    </footer>

</main>
</div>

</body>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Ambil elemen-elemen modal
    var modal = document.getElementById("welcomeModal");
    var closeBtn = document.querySelector(".close-btn");

    // Fungsi untuk menampilkan modal
    function showModal() {
        modal.style.display = "block";
    }

    // Fungsi untuk menyembunyikan modal
    function hideModal() {
        modal.style.display = "none";
    }

    // Tampilkan modal hanya jika belum pernah ditampilkan di sesi ini
    if (!sessionStorage.getItem('welcomePopupShown')) {
        showModal();
        sessionStorage.setItem('welcomePopupShown', 'true');
    }

    // Sembunyikan modal saat tombol close diklik
    closeBtn.onclick = function() {
        hideModal();
    }

    // Sembunyikan modal saat area di luar modal diklik
    window.onclick = function(event) {
        if (event.target == modal) {
            hideModal();
        }
    }
});
</script>
</html>