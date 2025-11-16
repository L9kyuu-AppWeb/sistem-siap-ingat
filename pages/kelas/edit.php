<?php
// Check permission
if (!hasRole(['admin', 'manager'])) {
    require_once __DIR__ . '/../errors/403.php';
    exit;
}

$kelasId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$kelasId) {
    redirect('index.php?page=kelas');
}

// Get kelas data
$stmt = $pdo->prepare("SELECT * FROM kelas WHERE id = ?");
$stmt->execute([$kelasId]);
$kelas = $stmt->fetch();

if (!$kelas) {
    setAlert('error', 'Kelas tidak ditemukan!');
    redirect('index.php?page=kelas');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kelas = cleanInput($_POST['nama_kelas']);
    $tahun_ajaran = cleanInput($_POST['tahun_ajaran']);
    $pj_id = !empty($_POST['pj_id']) ? (int)$_POST['pj_id'] : null;

    // Validation
    if (empty($nama_kelas) || empty($tahun_ajaran) || empty($pj_id)) {
        $error = 'Semua field yang bertanda * harus diisi!';
    } else {
        // Check if kelas with same name already exists for other kelas
        $stmt = $pdo->prepare("SELECT id FROM kelas WHERE nama_kelas = ? AND id != ?");
        $stmt->execute([$nama_kelas, $kelasId]);

        if ($stmt->fetch()) {
            $error = 'Nama kelas sudah digunakan!';
        } else {
            // Update kelas in database
            $stmt = $pdo->prepare("
                UPDATE kelas
                SET nama_kelas = ?, tahun_ajaran = ?, pj_id = ?
                    WHERE id = ?
            ");

            if ($stmt->execute([$nama_kelas, $tahun_ajaran, $pj_id, $kelasId])) {
                logActivity($_SESSION['user_id'], 'update_kelas', "Updated kelas: $nama_kelas");
                setAlert('success', 'Kelas berhasil diperbarui!');
                redirect('index.php?page=kelas');
            } else {
                $error = 'Gagal memperbarui kelas!';
            }
        }
    }
}

// Get all murid for penanggung jawab dropdown
$users = $pdo->query("SELECT id, nama_lengkap FROM murid ORDER BY nama_lengkap")->fetchAll();
$allUsers = $pdo->query("SELECT id, nama_lengkap FROM murid ORDER BY nama_lengkap")->fetchAll();
?>

<div class="mb-6">
    <div class="flex items-center space-x-4 mb-4">
        <a href="index.php?page=kelas" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Edit Kelas</h1>
            <p class="text-gray-500 mt-1">Perbarui informasi kelas</p>
        </div>
    </div>
</div>

<?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-4">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm p-6">
    <form method="POST" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Kelas *</label>
                <input type="text" name="nama_kelas" required
                       value="<?php echo isset($_POST['nama_kelas']) ? htmlspecialchars($_POST['nama_kelas']) : htmlspecialchars($kelas['nama_kelas']); ?>"
                       class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tahun Ajaran *</label>
                <input type="text" name="tahun_ajaran" required
                       value="<?php echo isset($_POST['tahun_ajaran']) ? htmlspecialchars($_POST['tahun_ajaran']) : htmlspecialchars($kelas['tahun_ajaran']); ?>"
                       class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Penanggung Jawab *</label>
                <select name="pj_id" required
                        class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                    <option value="">Pilih Penanggung Jawab</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>"
                                <?php echo (isset($_POST['pj_id']) ? $_POST['pj_id'] : $kelas['pj_id']) == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['nama_lengkap']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="flex space-x-3 pt-4 border-t">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-xl transition-colors">
                Update Kelas
            </button>
            <a href="index.php?page=kelas" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-6 rounded-xl transition-colors inline-block">
                Batal
            </a>
        </div>
    </form>
</div>
</div>