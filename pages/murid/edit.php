<?php
// Check permission
if (!hasRole(['admin', 'manager'])) {
    require_once __DIR__ . '/../errors/403.php';
    exit;
}

$muridId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$muridId) {
    redirect('index.php?page=murid');
}

// Get murid data
$stmt = $pdo->prepare("SELECT * FROM murid WHERE id = ?");
$stmt->execute([$muridId]);
$murid = $stmt->fetch();

if (!$murid) {
    setAlert('error', 'Murid tidak ditemukan!');
    redirect('index.php?page=murid');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = cleanInput($_POST['nama_lengkap']);
    $email = cleanInput($_POST['email']);
    $no_hp = cleanInput($_POST['no_hp']);

    // Validation
    if (empty($nama_lengkap) || empty($email)) {
        $error = 'Semua field yang bertanda * harus diisi!';
    } else {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid!';
        } else {
            // Check if murid with same email already exists for other murid
            $stmt = $pdo->prepare("SELECT id FROM murid WHERE email = ? AND id != ?");
            $stmt->execute([$email, $muridId]);

            if ($stmt->fetch()) {
                $error = 'Email murid sudah digunakan!';
            } else {
                // Update murid in database
                $stmt = $pdo->prepare("
                    UPDATE murid
                    SET nama_lengkap = ?, email = ?, no_hp = ?
                        WHERE id = ?
                ");

                if ($stmt->execute([$nama_lengkap, $email, $no_hp, $muridId])) {
                    // Also update the corresponding user account if it exists
                    $userUpdateStmt = $pdo->prepare("UPDATE users SET first_name = ?, email = ? WHERE username = ?");
                    $userUpdateStmt->execute([$nama_lengkap, $email, "murid$muridId"]);

                    logActivity($_SESSION['user_id'], 'update_murid', "Updated murid: $nama_lengkap");
                    setAlert('success', 'Murid dan akun user berhasil diperbarui!');
                    redirect('index.php?page=murid');
                } else {
                    $error = 'Gagal memperbarui murid!';
                }
            }
        }
    }
}
?>

<div class="mb-6">
    <div class="flex items-center space-x-4 mb-4">
        <a href="index.php?page=murid" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Edit Murid</h1>
            <p class="text-gray-500 mt-1">Perbarui informasi murid</p>
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap *</label>
                <input type="text" name="nama_lengkap" required
                       value="<?php echo isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : htmlspecialchars($murid['nama_lengkap']); ?>"
                       class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                <input type="email" name="email" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : htmlspecialchars($murid['email']); ?>"
                       class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">No. HP</label>
                <input type="text" name="no_hp"
                       value="<?php echo isset($_POST['no_hp']) ? htmlspecialchars($_POST['no_hp']) : htmlspecialchars($murid['no_hp'] ?? ''); ?>"
                       class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>

        </div>

        <div class="flex space-x-3 pt-4 border-t">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-xl transition-colors">
                Update Murid
            </button>
            <a href="index.php?page=murid" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-6 rounded-xl transition-colors inline-block">
                Batal
            </a>
        </div>
    </form>
</div>
</div>