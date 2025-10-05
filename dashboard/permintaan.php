<?php
// Koneksi ke database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_cctv";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Cek apakah data sudah dikirim
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_instansi = isset($_POST['nama_instansi']) ? $conn->real_escape_string($_POST['nama_instansi']) : '';
    $nomor_surat = isset($_POST['nomor_surat']) ? $conn->real_escape_string($_POST['nomor_surat']) : '';
    $tanggal_surat = isset($_POST['tanggal_surat']) ? $conn->real_escape_string($_POST['tanggal_surat']) : '';
    $tujuan = isset($_POST['tujuan']) ? $conn->real_escape_string($_POST['tujuan']) : '';
    $lokasi_cctv = isset($_POST['lokasi_cctv']) ? $conn->real_escape_string($_POST['lokasi_cctv']) : '';
    
    // Menggabungkan tanggal dan waktu menjadi satu string untuk rentang_waktu
    $tanggal_mulai = isset($_POST['tanggal_mulai']) ? $conn->real_escape_string($_POST['tanggal_mulai']) : '';
    $waktu_mulai = isset($_POST['waktu_mulai']) ? $conn->real_escape_string($_POST['waktu_mulai']) : '';
    $tanggal_akhir = isset($_POST['tanggal_akhir']) ? $conn->real_escape_string($_POST['tanggal_akhir']) : '';
    $waktu_akhir = isset($_POST['waktu_akhir']) ? $conn->real_escape_string($_POST['waktu_akhir']) : '';
    
    $rentang_waktu = "Dari: " . $tanggal_mulai . " " . $waktu_mulai . " - Sampai: " . $tanggal_akhir . " " . $waktu_akhir;

    $file_path = "";
    if (isset($_FILES['file_surat']) && $_FILES['file_surat']['error'] == 0) {
        $file_info = $_FILES['file_surat'];
        $file_mime_type = mime_content_type($file_info['tmp_name']);
        $file_size = $file_info['size'];

        // Validasi tipe file dan ukuran
        if ($file_mime_type != 'application/pdf') {
            echo "<script>alert('Hanya file PDF yang diizinkan.');</script>";
        } elseif ($file_size > 2000000) { // 2MB
            echo "<script>alert('Ukuran file tidak boleh lebih dari 2MB.');</script>";
        } else {
            $upload_dir = "../uploads/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = uniqid() . '_' . basename($file_info['name']);
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($file_info['tmp_name'], $target_file)) {
                $file_path = 'uploads/' . $file_name;
            } else {
                echo "<script>alert('Terjadi kesalahan saat mengunggah file.');</script>";
            }
        }
    } else {
        echo "<script>alert('Harap unggah file surat.');</script>";
    }

    if (!empty($file_path)) {
        // SQL untuk menyimpan data ke database
        $sql = "INSERT INTO permintaan_cctv (nama_instansi, nomor_surat, tanggal_surat, tujuan, lokasi_cctv, rentang_waktu, file_surat, status_permintaan, waktu_kirim) VALUES (?, ?, ?, ?, ?, ?, ?, 'Menunggu Verifikasi', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $nama_instansi, $nomor_surat, $tanggal_surat, $tujuan, $lokasi_cctv, $rentang_waktu, $file_path);

        if ($stmt->execute()) {
            header('Location: permintaan.php?status=sukses');
            exit();
        } else {
            echo "<script>alert('Terjadi kesalahan: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Permintaan Rekaman CCTV</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-image: url('../gbr/background.jpg');
            background-size: cover;
            background-position: center;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 50;
        }
        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 0.75rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 400px;
        }
        .suggestion {
            font-size: 0.875rem;
            color: #6B7280;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="bg-white bg-opacity-90 p-8 rounded-xl shadow-lg w-full max-w-2xl">
        <div class="flex flex-col items-center mb-6 text-center">
            <img src="../gbr/logo diskominfo.png" alt="Logo Diskominfosandi" class="h-16 mb-4">
            <h1 class="text-2xl md:text-3xl font-bold text-[#004C99]">Formulir Permintaan Rekaman CCTV</h1>
            <p class="mt-2 text-gray-600">Dinas Komunikasi, Informatika dan Persandian Kab. Barito Utara</p>
        </div>
        
        <form action="permintaan.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label for="nama_instansi" class="block text-sm font-medium text-gray-700">Nama Instansi / Lembaga:</label>
                <input type="text" id="nama_instansi" name="nama_instansi" value="<?php echo htmlspecialchars($_POST['nama_instansi'] ?? ''); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#00AEEF]">
                <p class="suggestion">Contoh: Kepolisian Resort Barito Utara</p>
            </div>
            <div>
                <label for="nomor_surat" class="block text-sm font-medium text-gray-700">Nomor Surat:</label>
                <input type="text" id="nomor_surat" name="nomor_surat" value="<?php echo htmlspecialchars($_POST['nomor_surat'] ?? ''); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#00AEEF]">
                <p class="suggestion">Contoh: 123/A/POLRES/XII/2023</p>
            </div>
            <div>
                <label for="tanggal_surat" class="block text-sm font-medium text-gray-700">Tanggal Surat:</label>
                <input type="date" id="tanggal_surat" name="tanggal_surat" value="<?php echo htmlspecialchars($_POST['tanggal_surat'] ?? ''); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#00AEEF]">
            </div>
            <div>
                <label for="tujuan" class="block text-sm font-medium text-gray-700">Tujuan Permintaan Rekaman:</label>
                <textarea id="tujuan" name="tujuan" rows="3" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#00AEEF]"><?php echo htmlspecialchars($_POST['tujuan'] ?? ''); ?></textarea>
            </div>
            <div>
                <label for="lokasi_cctv" class="block text-sm font-medium text-gray-700">Lokasi CCTV:</label>
                <select id="lokasi_cctv" name="lokasi_cctv" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#00AEEF]">
                    <option value="" disabled selected>Pilih Lokasi</option>
                    <option value="Simpang 4 Jalan A Yani & Diponegoro" <?php echo (isset($_POST['lokasi_cctv']) && $_POST['lokasi_cctv'] == 'Simpang 4 Jalan A Yani & Diponegoro') ? 'selected' : ''; ?>>Simpang 4 Jalan A Yani & Diponegoro</option>
                    <option value="Simpang 3 Jalan Pramuka" <?php echo (isset($_POST['lokasi_cctv']) && $_POST['lokasi_cctv'] == 'Simpang 3 Jalan Pramuka') ? 'selected' : ''; ?>>Simpang 3 Jalan Pramuka</option>
                    <option value="Bundaran Air Mancur" <?php echo (isset($_POST['lokasi_cctv']) && $_POST['lokasi_cctv'] == 'Bundaran Air Mancur') ? 'selected' : ''; ?>>Bundaran Air Mancur</option>
                    <option value="Simpang 4 Jalan Mangkusari & Imam Bonjol" <?php echo (isset($_POST['lokasi_cctv']) && $_POST['lokasi_cctv'] == 'Simpang 4 Jalan Mangkusari & Imam Bonjol') ? 'selected' : ''; ?>>Simpang 4 Jalan Mangkusari & Imam Bonjol</option>
                    <option value="Simpang 4 Jalan A Yani & Sengaji Hulu" <?php echo (isset($_POST['lokasi_cctv']) && $_POST['lokasi_cctv'] == 'Simpang 4 Jalan A Yani & Sengaji Hulu') ? 'selected' : ''; ?>>Simpang 4 Jalan A Yani & Sengaji Hulu</option>
                    <option value="Tugu Katamso" <?php echo (isset($_POST['lokasi_cctv']) && $_POST['lokasi_cctv'] == 'Tugu Katamso') ? 'selected' : ''; ?>>Tugu Katamso</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Rentang Waktu Rekaman:</label>
                <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4 mt-1">
                    <div class="flex-1">
                        <label for="tanggal_mulai" class="block text-xs font-medium text-gray-500 mb-1">Tanggal Mulai Peristiwa:</label>
                        <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="<?php echo htmlspecialchars($_POST['tanggal_mulai'] ?? ''); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#00AEEF]">
                    </div>
                    <div class="flex-1">
                        <label for="waktu_mulai" class="block text-xs font-medium text-gray-500 mb-1">Waktu Mulai Peristiwa:</label>
                        <input type="time" id="waktu_mulai" name="waktu_mulai" value="<?php echo htmlspecialchars($_POST['waktu_mulai'] ?? ''); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#00AEEF]">
                    </div>
                </div>
                <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4 mt-4">
                    <div class="flex-1">
                        <label for="tanggal_akhir" class="block text-xs font-medium text-gray-500 mb-1">Tanggal Akhir Peristiwa:</label>
                        <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="<?php echo htmlspecialchars($_POST['tanggal_akhir'] ?? ''); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#00AEEF]">
                    </div>
                    <div class="flex-1">
                        <label for="waktu_akhir" class="block text-xs font-medium text-gray-500 mb-1">Waktu Akhir Peristiwa:</label>
                        <input type="time" id="waktu_akhir" name="waktu_akhir" value="<?php echo htmlspecialchars($_POST['waktu_akhir'] ?? ''); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#00AEEF]">
                    </div>
                </div>
            </div>
            <div>
                <div class="flex items-center justify-between">
                    <label for="file_surat" class="block text-sm font-medium text-gray-700">Unggah File Surat:</label>
                    <a href="../contoh-surat-permintaan.docx" download class="text-xs text-[#00AEEF] hover:underline flex items-center">
                        <i class="fas fa-download mr-1"></i>Lihat Contoh Surat
                    </a>
                </div>
                <input type="file" id="file_surat" name="file_surat" required accept=".pdf" class="w-full mt-1 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[#00AEEF] file:text-white hover:file:bg-[#0096d5]">
                <p class="suggestion">Unggah file dalam format PDF (maks. 2MB).</p>
            </div>
            
            <button type="submit" class="w-full bg-[#004C99] text-white font-bold py-3 rounded-lg hover:bg-[#003B77] transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-[#00AEEF] focus:ring-offset-2">Kirim Permintaan</button>
        </form>

        <!-- Modal -->
        <div id="success-modal" class="modal">
            <div class="modal-content">
                <svg class="mx-auto h-16 w-16 text-green-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Permintaan Berhasil Dikirim</h2>
                <p class="text-gray-600 mb-6">Kami akan segera memprosesnya.</p>
                <a href="tracking.php" class="inline-block bg-[#00AEEF] text-white font-bold py-2 px-6 rounded-lg hover:bg-[#009bd4] transition-colors duration-300">Lacak Permintaan</a>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', (event) => {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('status') === 'sukses') {
                    document.getElementById('success-modal').style.display = 'flex';
                    // Hapus parameter 'status' dari URL agar tidak muncul lagi saat di-refresh
                    history.replaceState({}, document.title, window.location.pathname);
                }
            });
        </script>
    </div>
</body>
</html>
