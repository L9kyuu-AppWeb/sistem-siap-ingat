<?php
// Check permission
if (!hasRole(['pj', 'admin'])) {
    require_once __DIR__ . '/../errors/403.php';
    exit;
}

$reminderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$reminderId) {
    redirect('index.php?page=pj_reminder');
}

// Get current user info to map to murid
$currentUser = getCurrentUser();
$userMuridId = null;

if (preg_match('/^murid(\d+)$/', $currentUser['username'], $matches)) {
    $userMuridId = (int)$matches[1];
} elseif (hasRole('admin')) {
    $userMuridId = null; // Admin mode
}

// Get user's class ID first
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
$userKelasId = $userKelas ? $userKelas['id'] : 0;

// Get reminder data (only if created by current user AND for their class)
$stmt = $pdo->prepare("
    SELECT r.*, rc.nama_kategori
    FROM reminders r
    JOIN reminder_categories rc ON r.category_id = rc.id
    WHERE r.id = ? AND r.created_by = ? AND r.kelas_id = ?
");
$stmt->execute([$reminderId, $_SESSION['user_id'], $userKelasId]);
$reminder = $stmt->fetch();

if (!$reminder) {
    setAlert('error', 'Reminder tidak ditemukan atau Anda tidak memiliki akses!');
    redirect('index.php?page=pj_reminder');
}

// Delete reminder
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Delete the reminder from database (only for user's class)
        $stmt = $pdo->prepare("DELETE FROM reminders WHERE id = ? AND created_by = ? AND kelas_id = ?");
        if ($stmt->execute([$reminderId, $_SESSION['user_id'], $userKelasId])) {
            logActivity($_SESSION['user_id'], 'delete_reminder', "Deleted reminder: " . substr($reminder['deskripsi'], 0, 50) . "...");
            setAlert('success', 'Reminder berhasil dihapus!');
        } else {
            setAlert('error', 'Gagal menghapus reminder!');
        }
    } catch (Exception $e) {
        setAlert('error', 'Gagal menghapus reminder: ' . $e->getMessage());
    }

    redirect('index.php?page=pj_reminder');
}
?>

<div class="mb-6">
    <div class="flex items-center space-x-4 mb-4">
        <a href="index.php?page=pj_reminder" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Hapus Reminder</h1>
            <p class="text-gray-500 mt-1">Konfirmasi penghapusan reminder</p>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm p-6">
    <div class="text-center">
        <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
        <h3 class="mt-2 text-lg font-medium text-gray-800">Konfirmasi Penghapusan</h3>
        <p class="mt-1 text-sm text-gray-500">Apakah Anda yakin ingin menghapus reminder di bawah ini?</p>

        <div class="mt-6 bg-gray-50 p-4 rounded-xl">
            <h4 class="text-center mt-1 font-medium text-gray-800"><?php echo htmlspecialchars($reminder['deskripsi']); ?></h4>
            <p class="text-center text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($reminder['nama_kategori']); ?></p>
            <p class="text-center text-sm text-gray-500 mt-1"><?php echo date('d M Y', strtotime($reminder['tanggal'])); ?></p>
            <p class="text-center text-sm text-gray-500 mt-1">
                <?php echo $reminder['waktu'] ? date('H:i', strtotime($reminder['waktu'])) : 'Seharian'; ?>
            </p>
        </div>

        <form method="POST" class="mt-6 flex justify-center space-x-3">
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-6 rounded-xl transition-colors">
                Hapus
            </button>
            <a href="index.php?page=pj_reminder" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-6 rounded-xl transition-colors inline-block">
                Batal
            </a>
        </form>
    </div>
</div>
</div>