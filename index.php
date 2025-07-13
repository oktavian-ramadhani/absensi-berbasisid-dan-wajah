<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Kehadiran Siswa</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Tambahan style untuk menonaktifkan tombol dan input */
        .disabled { pointer-events: none; opacity: 0.6; }
        .hidden { display: none; }
        .search-btn {
            padding: 12px 20px;
            margin-left: 10px;
            border: none;
            background: #004080;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
        }
        .search-container {
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>

    <div class="glass-container">
        <?php
            include 'koneksi.php';
            // Ambil path logo dari DB
            $result = mysqli_query($koneksi, "SELECT nilai_setting FROM konfigurasi WHERE nama_setting = 'logo_sekolah'");
            $logo_data = mysqli_fetch_assoc($result);
            $logo_path = 'uploads/' . ($logo_data['nilai_setting'] ?? 'logo-default.png');
        ?>
        <div class="header">
            <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo Sekolah" class="logo">
            <h1>Absensi Digital</h1>
            <p>Masukkan NIS Anda lalu klik "Cari" untuk menampilkan data.</p>
        </div>

        <div class="form-group">
            <label for="nis">Nomor Induk Siswa (NIS)</label>
            <div class="search-container">
                <input type="text" id="nis" name="nis" placeholder="Ketik NIS Anda di sini..." required>
                <button id="btn-cari" class="search-btn">Cari</button>
            </div>
        </div>

        <div id="pesan-status" class="alert" style="display: none;"></div>

        <form id="form-absen" class="hidden">
            <input type="hidden" id="id_siswa" name="id_siswa">

            <div class="form-group">
                <label for="nama_lengkap">Nama Lengkap</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" readonly>
            </div>

            <div class="form-group">
                <label for="kelas">Kelas</label>
                <input type="text" id="kelas" name="kelas" placeholder="Kelas akan muncul di sini" readonly>
            </div>

            <div class="camera-container">
                <video id="camera-preview" autoplay playsinline></video>
                <canvas id="canvas" style="display:none;"></canvas>
            </div>

            <button type="submit" class="btn" id="tombol-hadir">📸 Hadir</button>
        </form>
    </div>

<script>
    // Elemen-elemen Halaman
    const nisInput = document.getElementById('nis');
    const btnCari = document.getElementById('btn-cari');
    const pesanStatus = document.getElementById('pesan-status');
    const formAbsen = document.getElementById('form-absen');
    
    // Elemen-elemen Form Absen
    const idSiswaInput = document.getElementById('id_siswa');
    const namaLengkapInput = document.getElementById('nama_lengkap');
    const kelasInput = document.getElementById('kelas');
    const video = document.getElementById('camera-preview');
    const canvas = document.getElementById('canvas');
    const tombolHadir = document.getElementById('tombol-hadir');

    // Fungsi untuk menampilkan pesan
    function tampilkanPesan(tipe, pesan) {
        pesanStatus.textContent = pesan;
        pesanStatus.className = 'alert alert-' + tipe;
        pesanStatus.style.display = 'block';
    }

    // 1. Logika Pencarian Siswa
    btnCari.addEventListener('click', async function() {
        const nis = nisInput.value.trim();
        if (!nis) {
            tampilkanPesan('gagal', 'NIS tidak boleh kosong.');
            return;
        }

        btnCari.disabled = true;
        btnCari.textContent = 'Mencari...';
        
        try {
            const response = await fetch(`cari_siswa.php?nis=${nis}`);
            const result = await response.json();

            if (result.status === 'sukses') {
                pesanStatus.style.display = 'none'; // Sembunyikan pesan jika sukses
                const data = result.data;
                // Isi form absensi dengan data yang ditemukan
                idSiswaInput.value = data.id_siswa;
                namaLengkapInput.value = data.nama_lengkap;
                kelasInput.value = data.kelas;
                // Tampilkan form absensi
                formAbsen.classList.remove('hidden');
                // Mulai kamera
                startCamera();
            } else {
                tampilkanPesan('gagal', result.message);
                formAbsen.classList.add('hidden'); // Sembunyikan form jika gagal
            }
        } catch (error) {
            console.error('Error:', error);
            tampilkanPesan('gagal', 'Terjadi kesalahan pada sistem pencarian.');
        } finally {
            btnCari.disabled = false;
            btnCari.textContent = 'Cari';
        }
    });

    // 2. Mengakses Kamera (dipanggil setelah siswa ditemukan)
    async function startCamera() {
        if (video.srcObject) return; // Jangan mulai ulang jika sudah berjalan
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { width: 480, height: 360 } });
            video.srcObject = stream;
        } catch (err) {
            console.error("Error mengakses kamera: ", err);
            tampilkanPesan('gagal', "Error: Kamera tidak dapat diakses. Mohon izinkan akses kamera.");
        }
    }

    // 3. Event Listener untuk Form Submission Absensi
    formAbsen.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        tombolHadir.disabled = true;
        tombolHadir.textContent = 'Memproses...';

        // Mengambil gambar dari video
        const context = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageDataURL = canvas.toDataURL('image/png');

        // Mengirim data ke server
        const formData = new FormData();
        formData.append('id_siswa', idSiswaInput.value);
        formData.append('image_data', imageDataURL);

        try {
            const response = await fetch('proses_absen.php', {
                method: 'POST',
                body: new URLSearchParams(formData)
            });
            const result = await response.json();
            tampilkanPesan(result.status, result.message);
            if (result.status === 'sukses') {
                formAbsen.classList.add('hidden'); // Sembunyikan form setelah absen
                nisInput.value = ''; // Kosongkan input NIS
            }
        } catch (error) {
            console.error('Error:', error);
            tampilkanPesan('gagal', 'Terjadi kesalahan saat mengirim data absensi.');
        } finally {
            tombolHadir.disabled = false;
            tombolHadir.textContent = '📸 Hadir';
        }
    });

</script>
</body>
</html>