<?php
// Set header sebagai JSON
header('Content-Type: application/json');

include 'koneksi.php';

// Pastikan ada parameter NIS yang dikirim
if (!isset($_GET['nis']) || empty($_GET['nis'])) {
    echo json_encode(['status' => 'gagal', 'message' => 'NIS tidak boleh kosong.']);
    exit;
}

$nis = $_GET['nis'];

$query = "SELECT id_siswa, nama_lengkap, kelas FROM siswa WHERE nis = ?";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "s", $nis);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($data = mysqli_fetch_assoc($result)) {
    // Siswa ditemukan
    echo json_encode([
        'status' => 'sukses',
        'data' => [
            'id_siswa' => $data['id_siswa'],
            'nama_lengkap' => $data['nama_lengkap'],
            'kelas' => $data['kelas']
        ]
    ]);
} else {
    // Siswa tidak ditemukan
    echo json_encode(['status' => 'gagal', 'message' => 'Siswa dengan NIS tersebut tidak ditemukan.']);
}

mysqli_stmt_close($stmt);
mysqli_close($koneksi);
?>