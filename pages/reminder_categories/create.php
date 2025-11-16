<?php
// Check permission
if (!hasRole(['admin', 'manager'])) {
    require_once __DIR__ . '/../errors/403.php';
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kategori = cleanInput($_POST['nama_kategori']);

    // Validation
    if (empty($nama_kategori)) {
        $error = 'Nama kategori harus diisi!';
    } else {
        // Check if category with same name already exists
        $stmt = $pdo->prepare("SELECT id FROM reminder_categories WHERE nama_kategori = ?");
        $stmt->execute([$nama_kategori]);

        if ($stmt->fetch()) {
            $error = 'Nama kategori sudah digunakan!';
        } else {
            // Insert category into database
            $stmt = $pdo->prepare("
                INSERT INTO reminder_categories (nama_kategori)
                VALUES (?)
            ");

            if ($stmt->execute([$nama_kategori])) {
                $categoryId = $pdo->lastInsertId();

                logActivity($_SESSION['user_id'], 'create_reminder_category', "Created new reminder category: $nama_kategori");
                setAlert('success', 'Kategori reminder berhasil ditambahkan!');
                redirect('index.php?page=reminder_categories');
            } else {
                $error = 'Gagal menambahkan kategori reminder!';
            }
        }
    }
}

?>

<div class="mb-6">
    <div class="flex items-center space-x-4 mb-4">
        <a href="index.php?page=reminder_categories" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Tambah Kategori Reminder</h1>
            <p class="text-gray-500 mt-1">Tambahkan kategori untuk reminder</p>
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
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Kategori *</label>
            <input type="text" name="nama_kategori" required
                   value="<?php echo isset($_POST['nama_kategori']) ? htmlspecialchars($_POST['nama_kategori']) : ''; ?>"
                   class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
        </div>

        <div class="flex space-x-3 pt-4 border-t">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-xl transition-colors">
                Simpan Kategori
            </button>
            <a href="index.php?page=reminder_categories" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-6 rounded-xl transition-colors inline-block">
                Batal
            </a>
        </div>
    </form>
</div>
</div>