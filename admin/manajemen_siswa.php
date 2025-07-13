<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}
include '../koneksi.php';

// Ambil semua data siswa
$querySiswa = "SELECT * FROM siswa ORDER BY nama_lengkap ASC";
$resultSiswa = mysqli_query($koneksi, $querySiswa);

// Ambil path logo
$result_logo = mysqli_query($koneksi, "SELECT nilai_setting FROM konfigurasi WHERE nama_setting = 'logo_sekolah'");
$logo_path = '../uploads/' . mysqli_fetch_assoc($result_logo)['nilai_setting'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Siswa - Dashboard Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard_style.css">
    <style>
        .action-btn {
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 5px;
            color: white;
            font-size: 0.9em;
            margin-right: 5px;
        }
        .edit-btn { background-color: #f0ad4e; }
        .delete-btn { background-color: #d9534f; }
        .add-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 500;
        }
    </style>
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
            <a href="statistik.php">📈 Statistik</a> 
            <a href="laporan.php">📖 Riwayat Absensi</a>
            <a href="manajemen_siswa.php">👤 Manajemen Siswa</a>
        </nav>
    </div>

    <main class="main-content">
        <header>
            <h1>Manajemen Siswa</h1>
            <div class="admin-info">
                <span>Selamat datang, <strong><?= htmlspecialchars($_SESSION['username']); ?></strong></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </header>

        <section class="content-box">
            <a href="tambah_siswa.php" class="add-btn">➕ Tambah Siswa Baru</a>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIS</th>
                            <th>Nama Lengkap</th>
                            <th>Kelas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if (mysqli_num_rows($resultSiswa) > 0) {
                            while ($data = mysqli_fetch_assoc($resultSiswa)) {
                                echo "<tr>";
                                echo "<td>" . $no++ . "</td>";
                                echo "<td>" . htmlspecialchars($data['nis']) . "</td>";
                                echo "<td>" . htmlspecialchars($data['nama_lengkap']) . "</td>";
                                echo "<td>" . htmlspecialchars($data['kelas']) . "</td>";
                                echo "<td>
                                        <a href='edit_siswa.php?id=" . $data['id_siswa'] . "' class='action-btn edit-btn'>Edit</a>
                                        <a href='hapus_siswa.php?id=" . $data['id_siswa'] . "' class='action-btn delete-btn' onclick='return confirm(\"Apakah Anda yakin ingin menghapus siswa ini?\");'>Hapus</a>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center;'>Belum ada data siswa.</td></tr>";
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