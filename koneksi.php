<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db_name = 'db_absensi';

$koneksi = mysqli_connect($host, $user, $pass, $db_name);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>