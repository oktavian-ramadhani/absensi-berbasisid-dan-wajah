<?php
header('Content-Type: application/json');
include 'koneksi.php';

// --- Validasi Awal ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'gagal', 'message' => 'Metode request harus POST.']);
    exit;
}

if (!isset($_POST['id_siswa']) || !isset($_POST['image_data'])) {
    echo json_encode(['status' => 'gagal', 'message' => 'Data tidak lengkap.']);
    exit;
}

$id_siswa = $_POST['id_siswa'];
$imageData = $_POST['image_data'];

// --- 1. Ambil data siswa untuk membuat path folder ---
$querySiswa = "SELECT nis, nama_lengkap, kelas FROM siswa WHERE id_siswa = ?";
$stmtSiswa = mysqli_prepare($koneksi, $querySiswa);
mysqli_stmt_bind_param($stmtSiswa, "i", $id_siswa);
mysqli_stmt_execute($stmtSiswa);
$resultSiswa = mysqli_stmt_get_result($stmtSiswa);
$siswa = mysqli_fetch_assoc($resultSiswa);

if (!$siswa) {
    echo json_encode(['status' => 'gagal', 'message' => 'Data siswa tidak ditemukan.']);
    exit;
}

// --- 2. Buat Path Folder Dinamis ---
$nama_folder_aman = preg_replace('/[^A-Za-z0-9\-_\.]/', '', str_replace(' ', '_', $siswa['nama_lengkap']));
$kelas_folder_aman = preg_replace('/[^A-Za-z0-9\-_\.]/', '', str_replace(' ', '_', $siswa['kelas']));
$tanggal_folder = date('Y-m-d');

// Struktur path: uploads/1001-Budi_Santoso/XII_IPA_1/2025-07-12/
$path_penyimpanan = 'uploads/' . $siswa['nis'] . '-' . $nama_folder_aman . '/' . $kelas_folder_aman . '/' . $tanggal_folder . '/';

// Buat folder jika belum ada
if (!is_dir($path_penyimpanan)) {
    if (!mkdir($path_penyimpanan, 0777, true)) {
        echo json_encode(['status' => 'gagal', 'message' => 'Error: Gagal membuat direktori penyimpanan.']);
        exit;
    }
}

// --- 3. Proses dan Simpan Gambar ---
@list($type, $imageData) = explode(';', $imageData);
@list(, $imageData) = explode(',', $imageData);
$imageData = base64_decode($imageData);

if (empty($imageData)) {
     echo json_encode(['status' => 'gagal', 'message' => 'Data gambar tidak valid.']);
     exit;
}

// Nama file unik dengan jam, menit, detik
$nama_file_foto = date('H-i-s') . '.png';
$file_path_untuk_simpan = $path_penyimpanan . $nama_file_foto; // INI ADALAH PATH YANG BENAR

// Menyimpan file gambar ke server
if (file_put_contents($file_path_untuk_simpan, $imageData) === false) {
    echo json_encode(['status' => 'gagal', 'message' => 'Gagal menyimpan file gambar ke server. Periksa izin folder `uploads`.']);
    exit;
}

// --- 4. Simpan ke Database ---
// Simpan path yang sama yang digunakan untuk menyimpan file
$query = "INSERT INTO absensi (id_siswa, foto_absen) VALUES (?, ?)";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "is", $id_siswa, $file_path_untuk_simpan);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'sukses', 'message' => 'Absensi berhasil direkam, Selamat Belajar!']);
} else {
    unlink($file_path_untuk_simpan); // Hapus foto jika gagal simpan DB
    echo json_encode(['status' => 'gagal', 'message' => 'Gagal menyimpan data ke database.']);
}

mysqli_stmt_close($stmt);
mysqli_close($koneksi);
?>