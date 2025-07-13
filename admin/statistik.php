<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}
include '../koneksi.php';

// Ambil path logo
$result_logo = mysqli_query($koneksi, "SELECT nilai_setting FROM konfigurasi WHERE nama_setting = 'logo_sekolah'");
$logo_path = '../' . mysqli_fetch_assoc($result_logo)['nilai_setting'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Revisi Statistik Absensi - Dashboard Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard_style.css">
    <style>
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .chart-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
        }
        .chart-card.full-width {
            grid-column: 1 / -1;
        }
        .section-title {
            grid-column: 1 / -1;
            margin-top: 20px;
            margin-bottom: -5px;
            color: var(--secondary-blue);
            font-size: 1.5em;
            font-weight: 600;
            border-bottom: 2px solid var(--light-blue);
            padding-bottom: 10px;
        }
        .chart-card h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: var(--secondary-blue);
            text-align: center;
            font-size: 1.1em;
        }
        .stat-display {
            text-align: center;
            margin: auto;
        }
        .stat-display .value {
            font-size: 2.8em;
            font-weight: 700;
            color: var(--primary-blue);
        }
        .stat-display .value-small { font-size: 2em; }
        .stat-display .value.danger { color: #d9534f; }
        .stat-display .label { font-size: 1em; color: var(--dark-grey); }
        .stat-display .sub-label { font-size: 0.8em; color: #888; }
        .list-container { font-size: 0.9em; padding: 0; list-style: none; overflow-y: auto; max-height: 120px; }
        .list-container li { margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #f0f0f0; }
        .list-container li:last-child { border-bottom: none; }
        #calendar-heatmap { width: 100%; border-collapse: collapse; }
        #calendar-heatmap th { padding: 8px; background-color: var(--light-blue); font-size: 0.9em; }
        #calendar-heatmap td { height: 70px; text-align: center; vertical-align: top; border: 1px solid #eee; position: relative; }
        #calendar-heatmap .day-number { font-size: 0.8em; padding: 5px; color: #999; }
        #calendar-heatmap .day-content { font-size: 1.1em; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: bold; }
        #calendar-heatmap .not-month { background-color: #f9f9f9; }
        .table-wrapper { max-height: 350px; overflow-y: auto; }
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
            <a href="statistik.php" class="active">📈 Statistik</a>
            <a href="laporan.php">📖 Riwayat Absensi</a>
            <a href="manajemen_siswa.php">👤 Manajemen Siswa</a>
        </nav>
    </div>

    <main class="main-content">
        <header>
            <h1>Statistik & Analisis Kehadiran</h1>
        </header>

        <section class="grid-container">
            <h2 class="section-title">Ringkasan Hari Ini (<?= date('d M Y') ?>)</h2>
            <div class="chart-card">
                <h3>Total Absensi</h3>
                <div class="stat-display">
                    <p class="value"><span id="totalHadirHariIni">0</span> / <span id="totalSiswa">0</span></p>
                    <p class="label">Siswa Hadir dari Total Siswa</p>
                </div>
            </div>
            <div class="chart-card">
                <h3>Rasio Kehadiran</h3>
                <canvas id="grafikRasioHadir" style="max-height: 150px;"></canvas>
            </div>
            <div class="chart-card">
                <h3>Statistik Keterlambatan</h3>
                <div class="stat-display">
                    <p class="value danger" id="jumlahTerlambat">0</p>
                    <p class="label">Siswa Terlambat</p>
                </div>
            </div>
            <div class="chart-card">
                <h3>Siswa Belum Absen</h3>
                <div class="stat-display">
                    <p class="value" id="jumlahBelumAbsen">0</p>
                    <p class="label">Siswa Belum Hadir</p>
                </div>
            </div>
        </section>

        <section class="grid-container">
            <h2 class="section-title">Rekap & Tren Absensi</h2>
            <div class="chart-card full-width">
                <h3 id="calendar-title">Rekap Absen Bulanan</h3>
                <table id="calendar-heatmap"></table>
            </div>
            <div class="chart-card">
                <h3>Distribusi Waktu Absen (Hari Ini)</h3>
                <canvas id="grafikDistribusiWaktu"></canvas>
            </div>
            <div class="chart-card">
                <h3>Tren Absensi Mingguan (4 Minggu Terakhir)</h3>
                <canvas id="grafikTrenMingguan"></canvas>
            </div>
        </section>

        <section class="grid-container">
            <h2 class="section-title">Analisis Per Kelas (Bulan Ini)</h2>
            <div class="chart-card full-width">
                <h3>Perbandingan Kehadiran Antar Kelas</h3>
                <canvas id="grafikPerbandinganKelas"></canvas>
            </div>
            <div class="chart-card">
                <h3>Kelas Kehadiran Terbaik</h3>
                <div class="stat-display">
                    <p class="value value-small" id="kelasTerbaik">N/A</p>
                    <p class="label">Total Kehadiran Tertinggi</p>
                </div>
            </div>
            <div class="chart-card">
                <h3>Kelas Kehadiran Terendah</h3>
                <div class="stat-display">
                    <p class="value value-small" id="kelasTerendah">N/A</p>
                    <p class="label">Total Kehadiran Terendah</p>
                </div>
            </div>
            <div class="chart-card">
                 <h3>Rata-rata Kehadiran Tiap Kelas</h3>
                 <div class="table-wrapper" style="max-height: 200px;">
                    <table>
                        <thead><tr><th>Kelas</th><th>Rata-rata Hadir/Hari</th></tr></thead>
                        <tbody id="tabelRataRataKelas"></tbody>
                    </table>
                 </div>
            </div>
        </section>
        
        <section class="grid-container">
             <h2 class="section-title">🥇 Peringkat Siswa (Bulan Ini)</h2>
             <div class="chart-card">
                <h3>Top 1000 Siswa Paling Rajin</h3>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>#</th><th>Nama</th><th>Kelas</th><th>Hadir</th></tr></thead>
                        <tbody id="tabelTopRajin"></tbody>
                    </table>
                </div>
             </div>
             <div class="chart-card">
                <h3>Top 5 Siswa Paling Sering Tidak Hadir</h3>
                 <div class="table-wrapper">
                    <table>
                        <thead><tr><th>#</th><th>Nama</th><th>Kelas</th><th>Absen</th></tr></thead>
                        <tbody id="tabelTidakHadir"></tbody>
                    </table>
                </div>
             </div>
             <div class="chart-card">
                <h3>Top 5 Siswa Sering Terlambat</h3>
                 <div class="table-wrapper">
                    <table>
                        <thead><tr><th>#</th><th>Nama</th><th>Kelas</th><th>Telat</th></tr></thead>
                        <tbody id="tabelSeringTerlambat"></tbody>
                    </table>
                </div>
             </div>
        </section>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const createChart = (ctx, type, data, options = {}) => new Chart(ctx, { type, data, options });

    const generateCalendar = (data, totalSiswa) => {
        const calendarEl = document.getElementById('calendar-heatmap');
        const monthName = new Date().toLocaleString('id-ID', { month: 'long', year: 'numeric' });
        document.getElementById('calendar-title').textContent = `Rekap Absen Bulanan (${monthName})`;
        
        let htmlContent = '<thead><tr><th>Min</th><th>Sen</th><th>Sel</th><th>Rab</th><th>Kam</th><th>Jum</th><th>Sab</th></tr></thead><tbody>';
        const now = new Date();
        const year = now.getFullYear();
        const month = now.getMonth();
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        
        let day = 1;
        for (let i = 0; i < 6 && day <= daysInMonth; i++) {
            htmlContent += '<tr>';
            for (let j = 0; j < 7; j++) {
                if (i === 0 && j < firstDay) {
                    htmlContent += '<td class="not-month"></td>';
                } else if (day > daysInMonth) {
                    htmlContent += '<td class="not-month"></td>';
                } else {
                    const currentDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    const count = data[currentDate] || 0;
                    const opacity = totalSiswa > 0 ? (count / totalSiswa) : 0;
                    const bgColor = `rgba(0, 85, 164, ${opacity})`;
                    htmlContent += `<td style="background-color: ${bgColor}; color: ${opacity > 0.6 ? 'white' : 'inherit'}">
                                      <div class="day-number">${day}</div>
                                      <div class="day-content">${count > 0 ? count : ''}</div>
                                   </td>`;
                    day++;
                }
            }
            htmlContent += '</tr>';
        }
        calendarEl.innerHTML = htmlContent + '</tbody>';
    };
    
    const populateTable = (tbodyId, data, rowGenerator, colSpan) => {
        const tbody = document.getElementById(tbodyId);
        tbody.innerHTML = '';
        if (data && data.length > 0) {
            data.forEach((item, index) => tbody.innerHTML += rowGenerator(item, index + 1));
        } else {
            tbody.innerHTML = `<tr><td colspan="${colSpan}" style="text-align:center;">Data tidak tersedia.</td></tr>`;
        }
    };

    fetch('api_statistik.php')
        .then(response => response.json())
        .then(data => {
            // Section 1: Ringkasan Hari Ini
            document.getElementById('totalHadirHariIni').textContent = data.absen_hari_ini;
            document.getElementById('totalSiswa').textContent = data.total_siswa;
            createChart('grafikRasioHadir', 'doughnut', {
                labels: ['Hadir', 'Tidak Hadir'],
                datasets: [{ data: [data.rasio_hadir_vs_tidak_hadir.hadir, data.rasio_hadir_vs_tidak_hadir.tidak_hadir], backgroundColor: ['#0055a4', '#e0e0e0'], borderWidth: 0 }]
            }, { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } });
            document.getElementById('jumlahTerlambat').textContent = data.statistik_keterlambatan.jumlah_hari_ini;
            document.getElementById('jumlahBelumAbsen').textContent = data.siswa_belum_absen_hari_ini.jumlah;

            // Section 2: Rekap & Tren
            generateCalendar(data.rekap_absen_bulanan, data.total_siswa);
            createChart('grafikDistribusiWaktu', 'bar', { labels: data.distribusi_waktu_hari_ini.label, datasets: [{ label: 'Jml Siswa', data: data.distribusi_waktu_hari_ini.data, backgroundColor: '#f0ad4e' }] });
            createChart('grafikTrenMingguan', 'line', { labels: data.tren_absensi_mingguan.label, datasets: [{ label: 'Total Hadir', data: data.tren_absensi_mingguan.data, borderColor: '#28a745', fill: true, tension: 0.2 }] });
            
            // Section 3: Analisis Kelas
            createChart('grafikPerbandinganKelas', 'bar', {
                labels: data.perbandingan_kelas_bulan_ini.label,
                datasets: [{ label: 'Total Kehadiran', data: data.perbandingan_kelas_bulan_ini.data, backgroundColor: 'rgba(0, 85, 164, 0.8)' }]
            }, { indexAxis: 'y' });
            document.getElementById('kelasTerbaik').textContent = data.kelas_kehadiran_terbaik;
            document.getElementById('kelasTerendah').textContent = data.kelas_kehadiran_terendah;
            populateTable('tabelRataRataKelas', data.rata_rata_kehadiran_kelas, item => `<tr><td>${item.label}</td><td>${item.data}</td></tr>`, 2);

            // Section 4: Peringkat Siswa
            populateTable('tabelTopRajin', data.top_5_rajin, (item, i) => `<tr><td>${i}</td><td>${item.nama_lengkap}</td><td>${item.kelas}</td><td>${item.total_hadir}</td></tr>`, 1000);
            populateTable('tabelTidakHadir', data.riwayat_ketidakhadiran, (item, i) => `<tr><td>${i}</td><td>${item.nama_lengkap}</td><td>${item.kelas}</td><td>${item.jumlah_tidak_hadir}</td></tr>`, 4);
            populateTable('tabelSeringTerlambat', data.siswa_sering_terlambat, (item, i) => `<tr><td>${i}</td><td>${item.nama_lengkap}</td><td>${item.kelas}</td><td>${item.total_terlambat}</td></tr>`, 4);
        })
        .catch(error => console.error('Error fetching statistics data:', error));
});
</script>
</body>
</html>