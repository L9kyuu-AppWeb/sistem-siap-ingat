<?php
// Check permission - only pj role can access this
if (!hasRole(['pj', 'admin'])) {
    require_once __DIR__ . '/../errors/403.php';
    exit;
}

// First, we need to get the murid ID that corresponds to this user ID
// Users and murid are linked by the username pattern 'murid' + murid_id
$currentUser = getCurrentUser();
$userMuridId = null;

// Get the murid ID by checking if the username follows the 'murid{id}' pattern
if (preg_match('/^murid(\d+)$/', $currentUser['username'], $matches)) {
    $userMuridId = (int)$matches[1];
} else {
    // If not following the murid pattern, it might be an admin or other role
    // In this case, if they have admin role, they can see all classes
    if (hasRole('admin')) {
        $sql = "SELECT k.*, m.nama_lengkap as pj_nama, m.email as pj_email, m.no_hp as pj_hp
                FROM kelas k
                JOIN murid m ON k.pj_id = m.id
                ORDER BY k.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    } else {
        // For non-admin users, if they're not a murid we can't find a match
        $sql = "SELECT k.*, m.nama_lengkap as pj_nama, m.email as pj_email, m.no_hp as pj_hp
                FROM kelas k
                JOIN murid m ON k.pj_id = m.id
                WHERE k.pj_id = 0"; // No results for invalid users
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
}

if ($userMuridId) {
    $sql = "SELECT k.*, m.nama_lengkap as pj_nama, m.email as pj_email, m.no_hp as pj_hp
            FROM kelas k
            JOIN murid m ON k.pj_id = m.id
            WHERE k.pj_id = ?
            ORDER BY k.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userMuridId]);
}

$kelasList = $stmt->fetchAll();

// Handle token regeneration directly on this page
if (isset($_POST['regen_token']) && isset($_POST['kelas_id'])) {
    $kelasId = (int)$_POST['kelas_id'];

    // Verify that this class belongs to the current user
    // Need to use the murid ID, not the user ID
    $checkStmt = $pdo->prepare("SELECT * FROM kelas WHERE id = ? AND pj_id = ?");
    $checkStmt->execute([$kelasId, $userMuridId]);
    $kelas = $checkStmt->fetch();

    if ($kelas) {
        // Generate new unique token
        $newToken = generateUniqueToken($pdo, 'kelas', 6, 10);
        $newToken = strtoupper($newToken);

        // Update the token
        $updateStmt = $pdo->prepare("UPDATE kelas SET token = ?, updated_at = NOW() WHERE id = ?");
        if ($updateStmt->execute([$newToken, $kelasId])) {
            logActivity($_SESSION['user_id'], 'regenerate_kelas_token', "Regenerated token for class: " . $kelas['nama_kelas']);
            setAlert('success', 'Token kelas berhasil diganti!');

            // Update the token in our array for immediate display
            foreach ($kelasList as &$kl) {
                if ($kl['id'] == $kelasId) {
                    $kl['token'] = $newToken;
                    break;
                }
            }
        } else {
            setAlert('error', 'Gagal mengganti token kelas!');
        }
    } else {
        setAlert('error', 'Kelas tidak ditemukan atau Anda bukan penanggung jawab kelas ini!');
    }

    // Refresh the page to show the updated token
    redirect('index.php?page=pj_kelas');
}
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Kelas Saya</h1>
    <p class="text-gray-500 mt-1">Kelas yang Anda kelola sebagai Penanggung Jawab</p>
</div>

<!-- Classes List View -->
<?php if (count($kelasList) > 0): ?>
    <?php foreach ($kelasList as $kelas): ?>
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-6 border border-gray-100">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($kelas['nama_kelas']); ?></h2>
                        <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                            Kelas
                        </span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600"><span class="font-medium">Tahun Ajaran:</span> <?php echo htmlspecialchars($kelas['tahun_ajaran']); ?></p>
                            <p class="text-gray-600"><span class="font-medium">Penanggung Jawab:</span> <?php echo htmlspecialchars($kelas['pj_nama']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600"><span class="font-medium">Dibuat:</span> <?php echo date('d M Y', strtotime($kelas['created_at'])); ?></p>
                            <p class="text-gray-600"><span class="font-medium">Diperbarui:</span> <?php echo date('d M Y', strtotime($kelas['updated_at'])); ?></p>
                        </div>
                    </div>

                    <!-- Token Information -->
                    <div class="mt-4 p-4 bg-gray-50 rounded-xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Token Kelas</p>
                                <p class="font-mono text-lg font-bold text-gray-800 mt-1"><?php echo htmlspecialchars($kelas['token']); ?></p>
                                <p class="text-xs text-gray-500 mt-1">Gunakan token ini agar murid dapat bergabung ke kelas Anda</p>
                            </div>

                            <!-- Regenerate Token Form -->
                            <form method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin mengganti token kelas <?php echo addslashes(htmlspecialchars($kelas['nama_kelas'])); ?>? Token lama akan menjadi tidak berlaku.');">
                                <input type="hidden" name="kelas_id" value="<?php echo $kelas['id']; ?>">
                                <input type="hidden" name="regen_token" value="1">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-xl transition-colors flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span>Ganti Token</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="bg-white rounded-2xl shadow-sm p-12 text-center">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747.777 3.332 1.253 4.5 2.027v13C19.832 18.477 18.247 18 16.5 18c-1.746.777-3.332 1.253-4.5 2.027" />
        </svg>
        <h3 class="text-lg font-medium text-gray-800 mb-2">Tidak ada kelas yang Anda kelola</h3>
        <p class="text-gray-500">Anda belum menjadi penanggung jawab kelas manapun</p>
    </div>
<?php endif; ?>