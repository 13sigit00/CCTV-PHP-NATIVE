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

$permintaan = null;
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nomor_surat'])) {
    $nomor_surat = $conn->real_escape_string($_POST['nomor_surat']);
    $sql = "SELECT * FROM permintaan_cctv WHERE nomor_surat = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $nomor_surat);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $permintaan = $result->fetch_assoc();
    } else {
        $error_message = "Nomor surat tidak ditemukan. Mohon periksa kembali.";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak Permintaan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-image: url('../gbr/background.jpg');
            background-size: cover;
            background-position: center;
        }
        .status-pill {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="bg-white bg-opacity-90 p-8 rounded-xl shadow-lg w-full max-w-2xl">
        <div class="flex flex-col items-center mb-6 text-center">
            <img src="../gbr/logo diskominfo.png" alt="Logo Diskominfosandi" class="h-16 mb-4">
            <h1 class="text-2xl md:text-3xl font-bold text-[#004C99]">Lacak Status Permintaan CCTV</h1>
            <p class="mt-2 text-gray-600">Dinas Komunikasi, Informatika dan Persandian Kab. Barito Utara</p>
        </div>

        <form action="tracking.php" method="POST" class="space-y-4">
            <div>
                <label for="nomor_surat" class="block text-sm font-medium text-gray-700">Masukkan Nomor Surat:</label>
                <input type="text" id="nomor_surat" name="nomor_surat" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#00AEEF]">
            </div>
            <button type="submit" class="w-full bg-[#004C99] text-white font-bold py-3 rounded-lg hover:bg-[#003B77] transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-[#00AEEF] focus:ring-offset-2">Lacak</button>
        </form>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mt-6" role="alert">
                <span class="block sm:inline"><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($permintaan): ?>
            <div class="mt-6 p-6 border border-gray-200 rounded-lg shadow-inner">
                <h2 class="text-xl font-bold text-[#004C99] mb-4">Detail Permintaan</h2>
                <table class="w-full text-sm text-left text-gray-700">
                    <tbody>
                        <tr class="border-b">
                            <th scope="row" class="py-2 font-medium whitespace-nowrap">Nomor Surat:</th>
                            <td class="py-2"><?= htmlspecialchars($permintaan['nomor_surat']); ?></td>
                        </tr>
                        <tr class="border-b">
                            <th scope="row" class="py-2 font-medium whitespace-nowrap">Tanggal Surat:</th>
                            <td class="py-2"><?= htmlspecialchars($permintaan['tanggal_surat']); ?></td>
                        </tr>
                        <tr class="border-b">
                            <th scope="row" class="py-2 font-medium whitespace-nowrap">Status:</th>
                            <td class="py-2">
                                <?php
                                $status = htmlspecialchars($permintaan['status_permintaan']);
                                $color = 'bg-gray-200 text-gray-800';
                                if ($status == 'Menunggu Verifikasi') {
                                    $color = 'bg-yellow-100 text-yellow-800';
                                } elseif ($status == 'Sedang Diproses') {
                                    $color = 'bg-blue-100 text-blue-800';
                                } elseif ($status == 'Selesai') {
                                    $color = 'bg-green-100 text-green-800';
                                } elseif ($status == 'Ditolak') {
                                    $color = 'bg-red-100 text-red-800';
                                }
                                ?>
                                <span class="status-pill <?= $color; ?>"><?= $status; ?></span>
                            </td>
                        </tr>
                        <?php if ($permintaan['status_permintaan'] == 'Selesai' && !empty($permintaan['link_drive'])): ?>
                        <tr>
                            <th scope="row" class="py-2 font-medium whitespace-nowrap">Link Rekaman:</th>
                            <td class="py-2"><a href="<?= htmlspecialchars($permintaan['link_drive']); ?>" target="_blank" class="text-blue-600 hover:underline">Akses Rekaman</a></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($permintaan['catatan_admin'])): ?>
                        <tr>
                            <th scope="row" class="py-2 font-medium whitespace-nowrap">Catatan Admin:</th>
                            <td class="py-2"><?= nl2br(htmlspecialchars($permintaan['catatan_admin'])); ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
