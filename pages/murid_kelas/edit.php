<?php
// Check permission
if (!hasRole(['admin', 'manager'])) {
    require_once __DIR__ . '/../errors/403.php';
    exit;
}

$muridKelasId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$muridKelasId) {
    redirect('index.php?page=murid_kelas');
}

// Get murid_kelas data
$stmt = $pdo->prepare("SELECT * FROM murid_kelas WHERE id = ?");
$stmt->execute([$muridKelasId]);
$muridKelas = $stmt->fetch();

if (!$muridKelas) {
    setAlert('error', 'Relasi murid kelas tidak ditemukan!');
    redirect('index.php?page=murid_kelas');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $murid_id = (int)$_POST['murid_id'];
    $kelas_id = (int)$_POST['kelas_id'];

    // Validation
    if (empty($murid_id) || empty($kelas_id)) {
        $error = 'Semua field yang bertanda * harus diisi!';
    } else {
        // Check if this murid_kelas relationship already exists for other records
        $stmt = $pdo->prepare("SELECT id FROM murid_kelas WHERE murid_id = ? AND kelas_id = ? AND id != ?");
        $stmt->execute([$murid_id, $kelas_id, $muridKelasId]);

        if ($stmt->fetch()) {
            $error = 'Murid sudah terdaftar di kelas ini!';
        } else {
            // Update murid_kelas in database
            $stmt = $pdo->prepare("
                UPDATE murid_kelas
                SET murid_id = ?, kelas_id = ?
                    WHERE id = ?
            ");

            if ($stmt->execute([$murid_id, $kelas_id, $muridKelasId])) {
                logActivity($_SESSION['user_id'], 'update_murid_kelas', "Updated murid kelas: Murid ID $murid_id, Kelas ID $kelas_id");
                setAlert('success', 'Relasi murid kelas berhasil diperbarui!');
                redirect('index.php?page=murid_kelas');
            } else {
                $error = 'Gagal memperbarui relasi murid kelas!';
            }
        }
    }
}

// Get all available murid and kelas for dropdowns
$murid = $pdo->query("SELECT id, nama_lengkap FROM murid ORDER BY nama_lengkap")->fetchAll();
$kelas = $pdo->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas")->fetchAll();
?>

<div class="mb-6">
    <div class="flex items-center space-x-4 mb-4">
        <a href="index.php?page=murid_kelas" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Edit Relasi Murid Kelas</h1>
            <p class="text-gray-500 mt-1">Perbarui relasi murid dengan kelas</p>
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Murid *</label>
                <select name="murid_id" required
                        class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                    <option value="">Pilih Murid</option>
                    <?php foreach ($murid as $m): ?>
                        <option value="<?php echo $m['id']; ?>"
                                <?php echo (isset($_POST['murid_id']) ? $_POST['murid_id'] : $muridKelas['murid_id']) == $m['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($m['nama_lengkap']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Kelas *</label>
                <select name="kelas_id" required
                        class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                    <option value="">Pilih Kelas</option>
                    <?php foreach ($kelas as $k): ?>
                        <option value="<?php echo $k['id']; ?>"
                                <?php echo (isset($_POST['kelas_id']) ? $_POST['kelas_id'] : $muridKelas['kelas_id']) == $k['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($k['nama_kelas']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="flex space-x-3 pt-4 border-t">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-xl transition-colors">
                Update
            </button>
            <a href="index.php?page=murid_kelas" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-6 rounded-xl transition-colors inline-block">
                Batal
            </a>
        </div>
    </form>
</div>
</div>