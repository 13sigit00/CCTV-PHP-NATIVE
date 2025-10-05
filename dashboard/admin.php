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

session_start();

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $user = $_POST['user'];
    $pass = $_POST['pass'];
    
    // Periksa user dan password
    if ($user === 'admin' && $pass === 'admin123') {
        $_SESSION['loggedin'] = true;
    } else {
        $error_message = "Username atau password salah.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit();
}

// Proses pembaruan status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        $id = $conn->real_escape_string($_POST['id']);
        $status = $conn->real_escape_string($_POST['status']);
        $catatan = $conn->real_escape_string($_POST['catatan_admin']);
        $link_drive = isset($_POST['link_drive']) ? $conn->real_escape_string($_POST['link_drive']) : '';

        // Tentukan SQL query berdasarkan status
        if ($status === 'Selesai') {
            $sql_update = "UPDATE permintaan_cctv SET status_permintaan = ?, catatan_admin = ?, link_drive = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("sssi", $status, $catatan, $link_drive, $id);
        } else {
            $sql_update = "UPDATE permintaan_cctv SET status_permintaan = ?, catatan_admin = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("ssi", $status, $catatan, $id);
        }
        
        $stmt_update->execute();
        $stmt_update->close();
    }
}

$is_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;

// Mengambil data dari database dan mengurutkannya berdasarkan ID secara menaik
$sql = "SELECT id, nama_instansi, nomor_surat, tanggal_surat, tujuan, lokasi_cctv, rentang_waktu, file_surat, status_permintaan, waktu_kirim FROM permintaan_cctv ORDER BY id ASC";
$result = $conn->query($sql);
$data_permintaan = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data_permintaan[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-image: url('../gbr/admin.jpg');
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
        .new-label {
            position: absolute;
            top: 0;
            right: 0;
            background-color: #ef4444; /* Red 500 */
            color: white;
            padding: 0.1rem 0.5rem;
            font-size: 0.75rem;
            border-bottom-left-radius: 0.5rem;
            font-weight: bold;
        }
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 50;
        }
        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 500px;
        }
    </style>
</head>
<body class="p-4">
    <?php if (!$is_logged_in): ?>
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white bg-opacity-90 p-8 rounded-xl shadow-lg w-full max-w-sm">
                <div class="flex flex-col items-center mb-6">
                    <img src="../gbr/logo diskominfo.png" alt="Logo Diskominfosandi" class="h-16 mb-4">
                    <h1 class="text-2xl font-bold text-[#004C99]">Admin Login</h1>
                </div>
                <?php if (!empty($error_message)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                        <span class="block sm:inline"><?= htmlspecialchars($error_message); ?></span>
                    </div>
                <?php endif; ?>
                <form action="admin.php" method="POST" class="space-y-4">
                    <div>
                        <label for="user" class="block text-sm font-medium text-gray-700">Username:</label>
                        <input type="text" id="user" name="user" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#00AEEF]">
                    </div>
                    <div>
                        <label for="pass" class="block text-sm font-medium text-gray-700">Password:</label>
                        <input type="password" id="pass" name="pass" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#00AEEF]">
                    </div>
                    <button type="submit" name="login" class="w-full bg-[#004C99] text-white font-bold py-3 rounded-lg hover:bg-[#003B77] transition-colors duration-300">Login</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white bg-opacity-90 p-8 rounded-xl shadow-lg w-full">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-6">
                <div class="flex items-center mb-4 sm:mb-0 text-center sm:text-left">
                    <img src="../gbr/logo diskominfo.png" alt="Logo Diskominfosandi" class="h-12 mr-4">
                    <h1 class="text-xl md:text-2xl font-bold text-[#004C99]">Dashboard Admin</h1>
                    <button onclick="window.location.reload();" class="ml-4 px-4 py-2 bg-blue-500 text-white font-bold rounded-lg hover:bg-blue-600 transition-colors duration-300">
                      Refresh
                    </button>
                </div>
                <form action="admin.php" method="POST">
                    <button type="submit" name="logout" class="bg-red-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-red-600 transition-colors duration-300">Logout</button>
                </form>
            </div>

            <?php if (empty($data_permintaan)): ?>
                <div class="bg-gray-100 p-6 rounded-lg text-center text-gray-600 font-medium">
                    Tidak ada data permintaan yang masuk.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3 px-6">No.</th>
                                <th scope="col" class="py-3 px-6">Instansi</th>
                                <th scope="col" class="py-3 px-6">Nomor Surat</th>
                                <th scope="col" class="py-3 px-6">Lokasi CCTV</th>
                                <th scope="col" class="py-3 px-6">Rentang Waktu</th>
                                <th scope="col" class="py-3 px-6">File Surat</th>
                                <th scope="col" class="py-3 px-6">Status</th>
                                <th scope="col" class="py-3 px-6">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $counter = 1; ?>
                            <?php foreach ($data_permintaan as $row): ?>
                                <tr class="bg-white border-b hover:bg-gray-50 relative">
                                    <?php
                                    $waktu_kirim = new DateTime($row['waktu_kirim']);
                                    $sekarang = new DateTime();
                                    $selisih = $sekarang->getTimestamp() - $waktu_kirim->getTimestamp();
                                    $is_new = ($row['status_permintaan'] == 'Menunggu Verifikasi' && $selisih <= 60);
                                    ?>
                                    <td class="py-4 px-6 relative">
                                        <?= $counter++; ?>
                                        <?php if ($is_new): ?>
                                            <div class="new-label">BARU</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6"><?= htmlspecialchars($row['nama_instansi']); ?></td>
                                    <td class="py-4 px-6"><?= htmlspecialchars($row['nomor_surat']); ?></td>
                                    <td class="py-4 px-6"><?= htmlspecialchars($row['lokasi_cctv']); ?></td>
                                    <td class="py-4 px-6"><?= htmlspecialchars($row['rentang_waktu']); ?></td>
                                    <td class="py-4 px-6">
                                        <a href="../<?= htmlspecialchars($row['file_surat']); ?>" download class="text-blue-600 hover:underline font-medium">Unduh</a>
                                    </td>
                                    <td class="py-4 px-6">
                                        <?php
                                        $status = htmlspecialchars($row['status_permintaan']);
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
                                    <td class="py-4 px-6">
                                        <div class="flex items-center space-x-2">
                                            <button onclick="showModal(<?= $row['id']; ?>, 'Sedang Diproses')" class="bg-blue-500 text-white p-1 rounded-full hover:bg-blue-600 transition-colors duration-300" title="Proses">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zM6 9a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1z"/></svg>
                                            </button>
                                            <button onclick="showModal(<?= $row['id']; ?>, 'Selesai')" class="bg-green-500 text-white p-1 rounded-full hover:bg-green-600 transition-colors duration-300" title="Selesai">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                            </button>
                                            <button onclick="showModal(<?= $row['id']; ?>, 'Ditolak')" class="bg-red-500 text-white p-1 rounded-full hover:bg-red-600 transition-colors duration-300" title="Tolak">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div id="status-modal" class="modal hidden">
            <div class="modal-content">
                <h2 class="text-xl font-bold mb-4" id="modal-title"></h2>
                <form id="status-form" action="admin.php" method="POST" class="space-y-4">
                    <input type="hidden" name="update_status" value="1">
                    <input type="hidden" name="id" id="modal-id">
                    <input type="hidden" name="status" id="modal-status">
                    <div>
                        <label for="catatan_admin" class="block text-sm font-medium text-gray-700">Catatan Admin (Opsional):</label>
                        <textarea id="catatan_admin" name="catatan_admin" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#00AEEF]"></textarea>
                    </div>
                    <div id="link-drive-container" class="hidden">
                        <label for="link_drive" class="block text-sm font-medium text-gray-700">Link Google Drive:</label>
                        <input type="url" id="link_drive" name="link_drive" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#00AEEF]">
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeModal()" class="bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg hover:bg-gray-400 transition-colors duration-300">Batal</button>
                        <button type="submit" class="bg-blue-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-600 transition-colors duration-300" id="modal-submit-button">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function showModal(id, status) {
                const modal = document.getElementById('status-modal');
                const modalTitle = document.getElementById('modal-title');
                const modalId = document.getElementById('modal-id');
                const modalStatus = document.getElementById('modal-status');
                const catatanInput = document.getElementById('catatan_admin');
                const linkDriveContainer = document.getElementById('link-drive-container');
                const submitButton = document.getElementById('modal-submit-button');

                modalId.value = id;
                modalStatus.value = status;
                
                catatanInput.required = false;
                linkDriveContainer.classList.add('hidden');

                if (status === 'Sedang Diproses') {
                    modalTitle.textContent = 'Proses Permintaan';
                    submitButton.textContent = 'Proses';
                    submitButton.className = 'bg-blue-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-600 transition-colors duration-300';
                } else if (status === 'Selesai') {
                    modalTitle.textContent = 'Selesaikan Permintaan';
                    submitButton.textContent = 'Selesai';
                    submitButton.className = 'bg-green-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-green-600 transition-colors duration-300';
                    linkDriveContainer.classList.remove('hidden');
                } else if (status === 'Ditolak') {
                    modalTitle.textContent = 'Tolak Permintaan';
                    submitButton.textContent = 'Tolak';
                    submitButton.className = 'bg-red-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-red-600 transition-colors duration-300';
                    catatanInput.required = true;
                }

                modal.classList.remove('hidden');
            }

            function closeModal() {
                const modal = document.getElementById('status-modal');
                modal.classList.add('hidden');
            }
        </script>
    <?php endif; ?>
</body>
</html>
