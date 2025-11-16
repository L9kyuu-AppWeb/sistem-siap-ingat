<?php
// Check permission - only murid role can access this
if (!hasRole(['murid', 'pj', 'admin'])) {
    require_once __DIR__ . '/../errors/403.php';
    exit;
}

// Get current user info to map to murid
$currentUser = getCurrentUser();
$userMuridId = null;

// Get the murid ID by checking if the username follows the 'murid{id}' pattern
if (preg_match('/^murid(\d+)$/', $currentUser['username'], $matches)) {
    $userMuridId = (int)$matches[1];
} else {
    // If not a murid, redirect or show error
    setAlert('error', 'Anda tidak memiliki akses ke modul ini!');
    redirect('index.php?page=dashboard');
    exit;
}

$error = '';
$success = '';

// Handle joining a class using token
if (isset($_POST['join_class']) && isset($_POST['token']) && !empty($_POST['token'])) {
    $token = cleanInput($_POST['token']);
    
    // Find class with this token
    $classStmt = $pdo->prepare("SELECT * FROM kelas WHERE token = ?");
    $classStmt->execute([$token]);
    $kelas = $classStmt->fetch();
    
    if (!$kelas) {
        $error = 'Token kelas tidak valid!';
    } else {
        // Check if the user is already in this class
        $checkStmt = $pdo->prepare("SELECT * FROM murid_kelas WHERE murid_id = ? AND kelas_id = ?");
        $checkStmt->execute([$userMuridId, $kelas['id']]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            $error = 'Anda sudah bergabung ke kelas ini!';
        } else {
            // Check if the user is already in another class
            $alreadyInClassStmt = $pdo->prepare("SELECT * FROM murid_kelas WHERE murid_id = ?");
            $alreadyInClassStmt->execute([$userMuridId]);
            $existingClass = $alreadyInClassStmt->fetch();
            
            if ($existingClass) {
                $error = 'Anda sudah bergabung ke kelas lain. Silakan keluar dari kelas tersebut terlebih dahulu.';
            } else {
                try {
                    // Add user to the class
                    $insertStmt = $pdo->prepare("INSERT INTO murid_kelas (murid_id, kelas_id) VALUES (?, ?)");
                    if ($insertStmt->execute([$userMuridId, $kelas['id']])) {
                        logActivity($_SESSION['user_id'], 'join_kelas', "Joined class: " . $kelas['nama_kelas']);
                        $success = 'Berhasil bergabung ke kelas: ' . $kelas['nama_kelas'];
                    } else {
                        $error = 'Gagal bergabung ke kelas. Silakan coba lagi.';
                    }
                } catch (Exception $e) {
                    $error = 'Gagal bergabung ke kelas: ' . $e->getMessage();
                }
            }
        }
    }
}

// Handle leaving a class
if (isset($_POST['leave_class']) && isset($_POST['kelas_id'])) {
    $kelasId = (int)$_POST['kelas_id'];
    
    // Verify that the user is in this class
    $checkStmt = $pdo->prepare("SELECT mk.*, k.nama_kelas FROM murid_kelas mk JOIN kelas k ON mk.kelas_id = k.id WHERE mk.murid_id = ? AND mk.kelas_id = ?");
    $checkStmt->execute([$userMuridId, $kelasId]);
    $membership = $checkStmt->fetch();
    
    if ($membership) {
        try {
            // Remove user from the class
            $deleteStmt = $pdo->prepare("DELETE FROM murid_kelas WHERE murid_id = ? AND kelas_id = ?");
            if ($deleteStmt->execute([$userMuridId, $kelasId])) {
                logActivity($_SESSION['user_id'], 'leave_kelas', "Left class: " . $membership['nama_kelas']);
                $success = 'Berhasil keluar dari kelas: ' . $membership['nama_kelas'];
            } else {
                $error = 'Gagal keluar dari kelas. Silakan coba lagi.';
            }
        } catch (Exception $e) {
            $error = 'Gagal keluar dari kelas: ' . $e->getMessage();
        }
    } else {
        $error = 'Anda tidak tergabung dalam kelas ini!';
    }
}

// Get classes that the user is a member of
$classesStmt = $pdo->prepare("
    SELECT k.*, mk.joined_at, m.nama_lengkap as pj_nama
    FROM kelas k
    JOIN murid_kelas mk ON k.id = mk.kelas_id
    JOIN murid m ON k.pj_id = m.id
    WHERE mk.murid_id = ?
    ORDER BY mk.joined_at DESC
");
$classesStmt->execute([$userMuridId]);
$userClasses = $classesStmt->fetchAll();
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Kelas Saya</h1>
    <p class="text-gray-500 mt-1">Kelas yang Anda ikuti</p>
</div>

<?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-4">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl mb-4">
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if (count($userClasses) === 0): ?>
    <!-- Form to join a class using token -->
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Bergabung ke Kelas</h2>
        <p class="text-gray-600 mb-6">Masukkan token kelas untuk bergabung ke kelas yang disediakan oleh penanggung jawab kelas</p>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Token Kelas *</label>
                <input type="text" name="token" required
                       placeholder="Masukkan token kelas"
                       class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>
            
            <div class="flex space-x-3 pt-4">
                <button type="submit" name="join_class" value="1" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-xl transition-colors">
                    Bergabung ke Kelas
                </button>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- Show classes the user is in -->
    <div class="grid grid-cols-1 gap-6">
        <?php foreach ($userClasses as $kelas): ?>
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                <div class="p-6">
                    <div class="flex flex-wrap justify-between items-start gap-4 mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($kelas['nama_kelas']); ?></h3>
                            <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($kelas['tahun_ajaran']); ?></p>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                            Kelas Anda
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Penanggung Jawab</p>
                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($kelas['pj_nama']); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Bergabung Sejak</p>
                            <p class="font-medium text-gray-800"><?php echo date('d M Y', strtotime($kelas['joined_at'])); ?></p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-4 border-t">
                        <span class="text-sm text-gray-500">
                            Token Kelas: <span class="font-mono bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($kelas['token'] ?? ''); ?></span>
                        </span>

                        <form method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin keluar dari kelas <?php echo addslashes(htmlspecialchars($kelas['nama_kelas'])); ?>?');">
                            <input type="hidden" name="kelas_id" value="<?php echo $kelas['id']; ?>">
                            <button type="submit" name="leave_class" value="1" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-xl transition-colors">
                                Keluar dari Kelas
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>