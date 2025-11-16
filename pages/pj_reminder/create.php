<?php
// Check permission
if (!hasRole(['pj', 'admin'])) {
    require_once __DIR__ . '/../errors/403.php';
    exit;
}

$error = '';
$success = false;

// Get current user info to map to murid
$currentUser = getCurrentUser();
$userMuridId = null;

if (preg_match('/^murid(\d+)$/', $currentUser['username'], $matches)) {
    $userMuridId = (int)$matches[1];
} elseif (hasRole('admin')) {
    $userMuridId = null; // Admin mode
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deskripsi = cleanInput($_POST['deskripsi']);
    $tanggal = cleanInput($_POST['tanggal']);
    $waktu = !empty($_POST['waktu']) ? cleanInput($_POST['waktu']) : null;
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    // For PJ, always use their class (no option to select other classes or global)
    $kelasId = null;

    // Get the user's class automatically
    $userKelasStmt = $pdo->prepare("SELECT k.id FROM kelas k WHERE k.pj_id = ?");
    if ($userMuridId) {
        $userKelasStmt->execute([$userMuridId]);
    } else {
        // For admin, get class via user->murid mapping
        $userKelasStmt = $pdo->prepare("
            SELECT k.id
            FROM kelas k
            JOIN murid m ON k.pj_id = m.id
            JOIN users u ON CONCAT('murid', m.id) = u.username
            WHERE u.id = ?
        ");
        $userKelasStmt->execute([$_SESSION['user_id']]);
    }
    $userKelas = $userKelasStmt->fetch();

    if ($userKelas) {
        $kelasId = $userKelas['id'];
    } else {
        $error = 'Anda tidak memiliki kelas sebagai penanggung jawab!';
    }

    // Validation
    if (empty($deskripsi) || empty($tanggal) || !$categoryId || !$kelasId) {
        $error = 'Field yang bertanda * harus diisi!';
    } else {
        // Validate date format
        $dateCheck = DateTime::createFromFormat('Y-m-d', $tanggal);
        if (!$dateCheck || $dateCheck->format('Y-m-d') !== $tanggal) {
            $error = 'Format tanggal tidak valid!';
        } elseif ($waktu) {
            $timeCheck = DateTime::createFromFormat('H:i', $waktu);
            if (!$timeCheck || $timeCheck->format('H:i') !== $waktu) {
                $error = 'Format waktu tidak valid!';
            }
        }
    }

    if (empty($error)) {
        try {
            // Insert reminder into database (always assigned to user's class)
            $stmt = $pdo->prepare("
                INSERT INTO reminders (kelas_id, category_id, deskripsi, tanggal, waktu, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            if ($stmt->execute([$kelasId, $categoryId, $deskripsi, $tanggal, $waktu, $_SESSION['user_id']])) {
                logActivity($_SESSION['user_id'], 'create_reminder', "Created reminder: " . substr($deskripsi, 0, 50) . "...");
                setAlert('success', 'Reminder berhasil ditambahkan ke kelas Anda!');
                redirect('index.php?page=pj_reminder');
            } else {
                $error = 'Gagal menambahkan reminder!';
            }
        } catch (Exception $e) {
            $error = 'Gagal menambahkan reminder: ' . $e->getMessage();
        }
    }
}

// Get the user's class (since PJ only has one class)
$userKelasStmt = $pdo->prepare("SELECT k.id, k.nama_kelas FROM kelas k WHERE k.pj_id = ?");
if ($userMuridId) {
    $userKelasStmt->execute([$userMuridId]);
} else {
    // For admin, get class via user->murid mapping
    $userKelasStmt = $pdo->prepare("
        SELECT k.id, k.nama_kelas
        FROM kelas k
        JOIN murid m ON k.pj_id = m.id
        JOIN users u ON CONCAT('murid', m.id) = u.username
        WHERE u.id = ?
    ");
    $userKelasStmt->execute([$_SESSION['user_id']]);
}
$kelasList = $userKelasStmt->fetchAll();

// Get all categories
$categories = $pdo->query("SELECT * FROM reminder_categories ORDER BY nama_kategori")->fetchAll();
?>

<div class="mb-6">
    <div class="flex items-center space-x-4 mb-4">
        <a href="index.php?page=pj_reminder" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Tambah Reminder Baru</h1>
            <p class="text-gray-500 mt-1">Buat reminder baru</p>
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi *</label>
                <textarea name="deskripsi" required rows="4"
                          class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"><?php echo isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : ''; ?></textarea>
            </div>

            <div class="space-y-6">
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Kelas</label>
                <div class="px-4 py-2 border border-gray-200 rounded-xl bg-gray-50">
                    <?php if (count($kelasList) > 0): ?>
                        <?php echo htmlspecialchars($kelasList[0]['nama_kelas']); ?> (Kelas Anda)
                        <input type="hidden" name="kelas_id" value="<?php echo htmlspecialchars($kelasList[0]['id']); ?>">
                    <?php else: ?>
                        <span class="text-red-600">Anda tidak memiliki kelas sebagai penanggung jawab</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="flex space-x-3 pt-4 border-t">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-xl transition-colors">
                Simpan Reminder
            </button>
            <a href="index.php?page=pj_reminder" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-6 rounded-xl transition-colors inline-block">
                Batal
            </a>
        </div>
    </form>
</div>
</div>