<?php
// Check permission
if (!hasRole(['admin', 'manager'])) {
    require_once __DIR__ . '/../errors/403.php';
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kelas_id = !empty($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : null;
    $category_id = (int)$_POST['category_id'];
    $deskripsi = cleanInput($_POST['deskripsi']);
    $tanggal = cleanInput($_POST['tanggal']);
    $waktu = !empty($_POST['waktu']) ? cleanInput($_POST['waktu']) : null;

    // Validation
    if (empty($category_id) || empty($deskripsi) || empty($tanggal)) {
        $error = 'Semua field yang bertanda * harus diisi!';
    } else {
        // Insert reminder into database
        $stmt = $pdo->prepare("
            INSERT INTO reminders (kelas_id, category_id, deskripsi, tanggal, waktu, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        if ($stmt->execute([$kelas_id, $category_id, $deskripsi, $tanggal, $waktu, $_SESSION['user_id']])) {
            $reminderId = $pdo->lastInsertId();

            logActivity($_SESSION['user_id'], 'create_reminder', "Created new reminder: $deskripsi");
            setAlert('success', 'Reminder berhasil ditambahkan!');
            redirect('index.php?page=reminders');
        } else {
            $error = 'Gagal menambahkan reminder!';
        }
    }
}

// Get all available kelas and categories for dropdowns
$kelas = $pdo->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas")->fetchAll();
$categories = $pdo->query("SELECT id, nama_kategori FROM reminder_categories ORDER BY nama_kategori")->fetchAll();
?>

<div class="mb-6">
    <div class="flex items-center space-x-4 mb-4">
        <a href="index.php?page=reminders" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Tambah Reminder</h1>
            <p class="text-gray-500 mt-1">Tambahkan reminder baru</p>
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Kelas</label>
                <select name="kelas_id"
                        class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                    <option value="">Reminder Umum (Semua Kelas)</option>
                    <?php foreach ($kelas as $k): ?>
                        <option value="<?php echo $k['id']; ?>"
                                <?php echo (isset($_POST['kelas_id']) && $_POST['kelas_id'] == $k['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($k['nama_kelas']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Kategori *</label>
                <select name="category_id" required
                        class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                    <option value="">Pilih Kategori</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"
                                <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['nama_kategori']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal *</label>
                <input type="date" name="tanggal" required
                       value="<?php echo isset($_POST['tanggal']) ? htmlspecialchars($_POST['tanggal']) : ''; ?>"
                       class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Waktu</label>
                <input type="time" name="waktu"
                       value="<?php echo isset($_POST['waktu']) ? htmlspecialchars($_POST['waktu']) : ''; ?>"
                       class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi *</label>
            <textarea name="deskripsi" required rows="4"
                      class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"><?php echo isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : ''; ?></textarea>
        </div>

        <div class="flex space-x-3 pt-4 border-t">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-xl transition-colors">
                Simpan Reminder
            </button>
            <a href="index.php?page=reminders" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-6 rounded-xl transition-colors inline-block">
                Batal
            </a>
        </div>
    </form>
</div>
</div>