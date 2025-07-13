<?php
header('Content-Type: application/json');
include '../koneksi.php';

// Tentukan batas waktu keterlambatan
define('BATAS_WAKTU_TERLAMBAT', '08:00:00');

$data = [];
$today = date('Y-m-d');
$current_month = date('Y-m');

// --- DATA DASAR ---
// Dapatkan jumlah total siswa
$query_total_siswa = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM siswa");
$data['total_siswa'] = (int)mysqli_fetch_assoc($query_total_siswa)['total'];

// Dapatkan total absensi hari ini
$query_absen_hari_ini = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM absensi WHERE DATE(waktu_absen) = '$today'");
$data['absen_hari_ini'] = (int)mysqli_fetch_assoc($query_absen_hari_ini)['total'];

// --- STATISTIK YANG SUDAH ADA ---
// Kehadiran harian selama 7 hari terakhir (untuk grafik garis)
$kehadiran_per_hari = ['label' => [], 'data' => []];
for ($i = 6; $i >= 0; $i--) {
    $tanggal = date('Y-m-d', strtotime("-$i days"));
    $query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM absensi WHERE DATE(waktu_absen) = '$tanggal'");
    $hasil = mysqli_fetch_assoc($query);
    $kehadiran_per_hari['label'][] = date('d M', strtotime($tanggal));
    $kehadiran_per_hari['data'][] = (int)$hasil['total'];
}
$data['kehadiran_harian'] = $kehadiran_per_hari;

// --- FRAME STATISTIK BARU (TERMASUK PERBAIKAN BUG) ---

// 1. Rasio Kehadiran (Hadir vs. Tidak Hadir Hari Ini) - PERBAIKAN BUG
$data['rasio_hadir_vs_tidak_hadir'] = [
    'hadir' => $data['absen_hari_ini'],
    'tidak_hadir' => $data['total_siswa'] - $data['absen_hari_ini']
];

// 2. Distribusi Waktu Rata-rata Absen Hari Ini
$query_distribusi = mysqli_query($koneksi, "SELECT HOUR(waktu_absen) as jam, COUNT(*) as jumlah FROM absensi WHERE DATE(waktu_absen) = '$today' GROUP BY jam ORDER BY jam ASC");
$distribusi_waktu = ['label' => [], 'data' => []];
while($row = mysqli_fetch_assoc($query_distribusi)){
    $distribusi_waktu['label'][] = $row['jam'] . ':00 - ' . ($row['jam'] + 1) . ':00';
    $distribusi_waktu['data'][] = (int)$row['jumlah'];
}
$data['distribusi_waktu_hari_ini'] = $distribusi_waktu;

// 3. Siswa yang Belum Absen Hari Ini
$query_belum_absen = mysqli_query($koneksi, "SELECT nama_lengkap, kelas FROM siswa WHERE id_siswa NOT IN (SELECT id_siswa FROM absensi WHERE DATE(waktu_absen) = '$today') ORDER BY kelas, nama_lengkap ASC");
$belum_absen_list = [];
while($row = mysqli_fetch_assoc($query_belum_absen)){
    $belum_absen_list[] = $row['nama_lengkap'] . ' (' . $row['kelas'] . ')';
}
$data['siswa_belum_absen_hari_ini'] = [
    'jumlah' => count($belum_absen_list),
    'daftar' => array_slice($belum_absen_list, 0, 10) // Tampilkan 10 pertama sebagai sampel
];

// 4. Statistik Keterlambatan - PERBAIKAN BUG
$query_terlambat = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM absensi WHERE DATE(waktu_absen) = '$today' AND TIME(waktu_absen) > '" . BATAS_WAKTU_TERLAMBAT . "'");
$jumlah_terlambat_hari_ini = (int)mysqli_fetch_assoc($query_terlambat)['total'];
// Mencegah error pembagian dengan nol
$persentase_terlambat = ($data['absen_hari_ini'] > 0) ? ($jumlah_terlambat_hari_ini / $data['absen_hari_ini']) * 100 : 0;
$data['statistik_keterlambatan'] = [
    'jumlah_hari_ini' => $jumlah_terlambat_hari_ini,
    'persentase_hari_ini' => round($persentase_terlambat, 1)
];


// 5. Rekap Absen Bulanan (untuk kalender heatmap)
$query_rekap_bulanan = mysqli_query($koneksi, "SELECT DATE(waktu_absen) as tanggal, COUNT(*) as jumlah FROM absensi WHERE DATE_FORMAT(waktu_absen, '%Y-%m') = '$current_month' GROUP BY tanggal");
$rekap_bulanan = [];
while($row = mysqli_fetch_assoc($query_rekap_bulanan)){
    $rekap_bulanan[$row['tanggal']] = $row['jumlah'];
}
$data['rekap_absen_bulanan'] = $rekap_bulanan;


// 6. Perbandingan Kehadiran Antar Kelas (Bulan Ini)
$query_perbandingan_kelas = mysqli_query($koneksi, "SELECT s.kelas, COUNT(a.id_absen) AS total_hadir 
                                                    FROM absensi a 
                                                    JOIN siswa s ON a.id_siswa = s.id_siswa
                                                    WHERE DATE_FORMAT(a.waktu_absen, '%Y-%m') = '$current_month'
                                                    GROUP BY s.kelas 
                                                    ORDER BY total_hadir DESC");
$perbandingan_kelas = ['label' => [], 'data' => []];
$kelas_list = [];
while($row = mysqli_fetch_assoc($query_perbandingan_kelas)){
    $perbandingan_kelas['label'][] = $row['kelas'];
    $perbandingan_kelas['data'][] = (int)$row['total_hadir'];
    $kelas_list[] = ['nama' => $row['kelas'], 'total' => (int)$row['total_hadir']];
}
$data['perbandingan_kelas_bulan_ini'] = $perbandingan_kelas;

// 7. Kelas dengan Kehadiran Terbaik dan Terendah
$data['kelas_kehadiran_terbaik'] = $kelas_list[0]['nama'] ?? 'N/A';
$data['kelas_kehadiran_terendah'] = !empty($kelas_list) ? end($kelas_list)['nama'] : 'N/A';

// 8. Rata-rata Kehadiran Harian per Kelas
$query_avg_kelas = mysqli_query($koneksi, "SELECT s.kelas, COUNT(a.id_absen) as total_hadir, COUNT(DISTINCT DATE(a.waktu_absen)) as jumlah_hari_aktif 
                                           FROM absensi a 
                                           JOIN siswa s ON a.id_siswa = s.id_siswa
                                           WHERE DATE_FORMAT(a.waktu_absen, '%Y-%m') = '$current_month'
                                           GROUP BY s.kelas ORDER BY s.kelas ASC");
$rata_rata_kelas = ['label' => [], 'data' => []];
while($row = mysqli_fetch_assoc($query_avg_kelas)){
    $rata_rata = ($row['jumlah_hari_aktif'] > 0) ? $row['total_hadir'] / $row['jumlah_hari_aktif'] : 0;
    $rata_rata_kelas['label'][] = $row['kelas'];
    $rata_rata_kelas['data'][] = round($rata_rata, 2);
}
$data['rata_rata_kehadiran_kelas'] = $rata_rata_kelas;

// --- FITUR BARU YANG DIMINTA ---

// 1. Rekap Bulanan (Kalender Heatmap) - Sudah ada dari permintaan sebelumnya
$query_rekap_bulanan = mysqli_query($koneksi, "SELECT DATE(waktu_absen) as tanggal, COUNT(*) as jumlah FROM absensi WHERE DATE_FORMAT(waktu_absen, '%Y-%m') = '$current_month' GROUP BY tanggal");
$rekap_bulanan = [];
while($row = mysqli_fetch_assoc($query_rekap_bulanan)){
    $rekap_bulanan[$row['tanggal']] = $row['jumlah'];
}
$data['rekap_absen_bulanan'] = $rekap_bulanan;

// 2. Tren Absensi Mingguan (4 minggu terakhir)
$tren_mingguan = ['label' => [], 'data' => []];
for ($i = 3; $i >= 0; $i--) {
    $start_week = date('Y-m-d', strtotime("-$i week last monday"));
    $end_week = date('Y-m-d', strtotime("-$i week next sunday"));
    $query_mingguan = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM absensi WHERE DATE(waktu_absen) BETWEEN '$start_week' AND '$end_week'");
    $hasil_mingguan = mysqli_fetch_assoc($query_mingguan);
    $tren_mingguan['label'][] = 'Minggu ' . date('W', strtotime($start_week));
    $tren_mingguan['data'][] = (int)$hasil_mingguan['total'];
}
$data['tren_absensi_mingguan'] = $tren_mingguan;

// 3. Peringkat Kehadiran Siswa Bulan Ini (Peringkat Penuh)
$query_ranking = mysqli_query($koneksi, "SELECT s.nama_lengkap, s.kelas, COUNT(a.id_absen) AS total_hadir 
                                          FROM siswa s
                                          LEFT JOIN absensi a ON s.id_siswa = a.id_siswa AND DATE_FORMAT(a.waktu_absen, '%Y-%m') = '$current_month'
                                          GROUP BY s.id_siswa 
                                          ORDER BY total_hadir DESC, s.nama_lengkap ASC");
$ranking_kehadiran = [];
while($row = mysqli_fetch_assoc($query_ranking)){
    $ranking_kehadiran[] = [
        'nama_lengkap' => $row['nama_lengkap'],
        'kelas' => $row['kelas'],
        'total_hadir' => (int)$row['total_hadir']
    ];
}
$data['ranking_kehadiran_bulan_ini'] = $ranking_kehadiran;

// 4. Top 5 Siswa Paling Rajin & Paling Jarang Hadir
$data['top_5_rajin'] = array_slice($ranking_kehadiran, 0, 5);
// Menyaring siswa yang hadir > 0 untuk 'paling jarang hadir' agar lebih relevan
$filtered_ranking = array_filter($ranking_kehadiran, function($siswa) { return $siswa['total_hadir'] > 0; });
$data['top_5_jarang_hadir'] = array_slice(array_reverse($filtered_ranking), 0, 5);


// 5. Rata-rata Jam Absen per Siswa & Siswa Sering Terlambat
$query_detail_waktu = mysqli_query($koneksi, "SELECT 
                                                s.nama_lengkap, s.kelas,
                                                AVG(TIME_TO_SEC(TIME(a.waktu_absen))) as avg_waktu_absen,
                                                SUM(CASE WHEN TIME(a.waktu_absen) > '" . BATAS_WAKTU_TERLAMBAT . "' THEN 1 ELSE 0 END) as total_terlambat
                                              FROM absensi a
                                              JOIN siswa s ON a.id_siswa = s.id_siswa
                                              WHERE DATE_FORMAT(a.waktu_absen, '%Y-%m') = '$current_month'
                                              GROUP BY s.id_siswa
                                              HAVING total_terlambat > 0
                                              ORDER BY total_terlambat DESC, avg_waktu_absen DESC
                                              LIMIT 10");
$detail_waktu = [];
while($row = mysqli_fetch_assoc($query_detail_waktu)){
    $detail_waktu[] = [
        'nama_lengkap' => $row['nama_lengkap'],
        'kelas' => $row['kelas'],
        'rata_rata_absen' => gmdate('H:i:s', (int)$row['avg_waktu_absen']),
        'total_terlambat' => (int)$row['total_terlambat']
    ];
}
$data['siswa_sering_terlambat'] = $detail_waktu;


// 6. Riwayat Ketidakhadiran Siswa
// Asumsi hari sekolah adalah Senin-Sabtu.
$hari_sekolah_berlalu = 0;
for ($i = 1; $i <= date('d'); $i++) {
    $day_of_week = date('N', strtotime("$current_month-$i"));
    if ($day_of_week < 7) { // 1=Senin, 6=Sabtu, 7=Minggu
        $hari_sekolah_berlalu++;
    }
}

$riwayat_tidak_hadir = [];
foreach ($ranking_kehadiran as $siswa) {
    $tidak_hadir = $hari_sekolah_berlalu - $siswa['total_hadir'];
    if ($tidak_hadir > 0) {
        $riwayat_tidak_hadir[] = [
            'nama_lengkap' => $siswa['nama_lengkap'],
            'kelas' => $siswa['kelas'],
            'jumlah_tidak_hadir' => $tidak_hadir
        ];
    }
}
// Urutkan berdasarkan jumlah tidak hadir terbanyak
usort($riwayat_tidak_hadir, function($a, $b) {
    return $b['jumlah_tidak_hadir'] - $a['jumlah_tidak_hadir'];
});
$data['riwayat_ketidakhadiran'] = array_slice($riwayat_tidak_hadir, 0, 10); // Ambil 10 teratas



echo json_encode($data);
exit();
?>