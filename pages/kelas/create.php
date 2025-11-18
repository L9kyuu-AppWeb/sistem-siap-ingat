<?php
// Check permission
if (!hasRole(['admin', 'manager'])) {
    require_once __DIR__ . '/../errors/403.php';
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kelas = cleanInput($_POST['nama_kelas']);
    $tahun_ajaran = cleanInput($_POST['tahun_ajaran']);
    $pj_id = !empty($_POST['pj_id']) ? (int)$_POST['pj_id'] : null;

    // Generate a random token
    $token = bin2hex(random_bytes(10)); // 20 character token

    // Validation
    if (empty($nama_kelas) || empty($tahun_ajaran) || empty($pj_id)) {
        $error = 'Semua field yang bertanda * harus diisi!';
    } else {
        // Check if kelas with same name already exists
        $stmt = $pdo->prepare("SELECT id FROM kelas WHERE nama_kelas = ?");
        $stmt->execute([$nama_kelas]);

        if ($stmt->fetch()) {
            $error = 'Nama kelas sudah digunakan!';
        } else {
            // Check if the selected murid is already a penanggung jawab for another class
            $checkStmt = $pdo->prepare("SELECT id FROM kelas WHERE pj_id = ?");
            $checkStmt->execute([$pj_id]);
            $existingClass = $checkStmt->fetch();

            if ($existingClass) {
                $error = 'Murid yang dipilih sudah menjadi penanggung jawab kelas lain!';
            } else {
                // Check if the selected murid is already a member of any class
                $checkStmt = $pdo->prepare("SELECT k.id, k.nama_kelas FROM murid_kelas mk JOIN kelas k ON mk.kelas_id = k.id WHERE mk.murid_id = ?");
                $checkStmt->execute([$pj_id]);
                $existingMembership = $checkStmt->fetch();

                if ($existingMembership) {
                    $error = 'Murid yang dipilih sudah berada di kelas: ' . $existingMembership['nama_kelas'];
                } else {
                    // Insert kelas into database
                    $stmt = $pdo->prepare("
                        INSERT INTO kelas (nama_kelas, tahun_ajaran, pj_id, token)
                        VALUES (?, ?, ?, ?)
                    ");

                    if ($stmt->execute([$nama_kelas, $tahun_ajaran, $pj_id, $token])) {
                        $kelasId = $pdo->lastInsertId();

                        // Update the murid's corresponding user role to 'pj' (role_id = 2)
                        $muridStmt = $pdo->prepare("SELECT * FROM murid WHERE id = ?");
                        $muridStmt->execute([$pj_id]);
                        $murid = $muridStmt->fetch();

                        if ($murid) {
                            $username = 'murid' . $pj_id; // Based on the naming convention
                            $updateRoleStmt = $pdo->prepare("UPDATE users SET role_id = 2 WHERE username = ?");
                            $updateRoleStmt->execute([$username]);
                        }

                        logActivity($_SESSION['user_id'], 'create_kelas', "Created new kelas: $nama_kelas");
                        setAlert('success', 'Kelas berhasil ditambahkan!');
                        redirect('index.php?page=kelas');
                    } else {
                        $error = 'Gagal menambahkan kelas!';
                    }
                }
            }
        }
    }
}

// Get all murid for penanggung jawab dropdown
$users = $pdo->query("SELECT id, nama_lengkap FROM murid ORDER BY nama_lengkap")->fetchAll();
?>

<div class="mb-6">
    <div class="flex items-center space-x-4 mb-4">
        <a href="index.php?page=kelas" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Tambah Kelas Baru</h1>
            <p class="text-gray-500 mt-1">Tambahkan kelas ke sistem</p>
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
                       value="<?php echo isset($_POST['nama_kelas']) ? htmlspecialchars($_POST['nama_kelas']) : ''; ?>"
                       class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tahun Ajaran *</label>
                <input type="text" name="tahun_ajaran" required
                       value="<?php echo isset($_POST['tahun_ajaran']) ? htmlspecialchars($_POST['tahun_ajaran']) : date('Y'); ?>"
                       class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Penanggung Jawab *</label>
                <select name="pj_id" required
                        class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                    <option value="">Pilih Penanggung Jawab</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>"
                                <?php echo (isset($_POST['pj_id']) && $_POST['pj_id'] == $user['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['nama_lengkap']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="flex space-x-3 pt-4 border-t">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-xl transition-colors">
                Simpan Kelas
            </button>
            <a href="index.php?page=kelas" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-6 rounded-xl transition-colors inline-block">
                Batal
            </a>
        </div>
    </form>
</div>
</div>