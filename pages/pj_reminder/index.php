<?php
// Check permission - only pj role can access this
if (!hasRole(['pj', 'admin'])) {
    require_once __DIR__ . '/../errors/403.php';
    exit;
}

$action = isset($_GET['action']) ? cleanInput($_GET['action']) : 'list';

switch ($action) {
    case 'create':
        require_once 'create.php';
        break;
    case 'edit':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) {
            redirect('index.php?page=pj_reminder');
        }
        require_once 'edit.php';
        break;
    case 'delete':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) {
            redirect('index.php?page=pj_reminder');
        }
        require_once 'delete.php';
        break;
    default:
        // First, get the user ID by mapping the session user to murid
        $currentUser = getCurrentUser();
        $userMuridId = null;

        // Get the murid ID by checking if the username follows the 'murid{id}' pattern
        if (preg_match('/^murid(\d+)$/', $currentUser['username'], $matches)) {
            $userMuridId = (int)$matches[1];
        } elseif (hasRole('admin')) {
            // For admin mode
            $userMuridId = null;
        }

        // List reminders created by the current user
        $search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
        $kelasFilter = isset($_GET['kelas']) ? (int)$_GET['kelas'] : 0;
        $categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

        // Get the user's class ID first
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

        $sql = "SELECT r.*, rc.nama_kategori, k.nama_kelas
                FROM reminders r
                LEFT JOIN reminder_categories rc ON r.category_id = rc.id
                LEFT JOIN kelas k ON r.kelas_id = k.id
                WHERE r.created_by = ? AND r.kelas_id = ?"; // Only reminders created by current user AND for their class

        if ($search) {
            $sql .= " AND (r.deskripsi LIKE :search1 OR rc.nama_kategori LIKE :search2)";
        }

        if ($categoryFilter) {
            $sql .= " AND r.category_id = :category";
        }

        $sql .= " ORDER BY r.tanggal DESC, r.waktu DESC";

        // First, get the user ID by mapping the session user to murid
        $currentUser = getCurrentUser();
        $userMuridId = null;

        // Get the murid ID by checking if the username follows the 'murid{id}' pattern
        if (preg_match('/^murid(\d+)$/', $currentUser['username'], $matches)) {
            $userMuridId = (int)$matches[1];
        } elseif (hasRole('admin')) {
            // For admin, we'll get reminders created by this user ID directly
            $userMuridId = null; // Indicates admin mode
        }
        
        $stmt = $pdo->prepare($sql);
        if ($userMuridId) {
            $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT); // Using user ID since created_by references users.id
            $stmt->bindValue(2, $userKelasId, PDO::PARAM_INT); // Only reminders for user's class
        } else {
            $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(2, $userKelasId, PDO::PARAM_INT); // Only reminders for user's class
        }

        if ($search) {
            $searchParam = "%$search%";
            $stmt->bindValue(':search1', $searchParam);
            $stmt->bindValue(':search2', $searchParam);
        }
        if ($categoryFilter) {
            $stmt->bindValue(':category', $categoryFilter, PDO::PARAM_INT);
        }

        $stmt->execute();
        $reminders = $stmt->fetchAll();

        // Get distinct classes and categories for filter (only from user's reminders for their class)
        // Since users can only see reminders for their class, we only show the one class they manage
        $kelasOptions = $pdo->prepare("
            SELECT k.id, k.nama_kelas
            FROM kelas k
            WHERE k.id = ?
            ORDER BY k.nama_kelas
        ");
        $kelasOptions->execute([$userKelasId]);
        $kelasList = $kelasOptions->fetchAll();

        $categories = $pdo->prepare("
            SELECT DISTINCT rc.id, rc.nama_kategori
            FROM reminders r
            JOIN reminder_categories rc ON r.category_id = rc.id
            WHERE r.created_by = ? AND r.kelas_id = ?
            ORDER BY rc.nama_kategori
        ");
        $categories->execute([$_SESSION['user_id'], $userKelasId]);
        $categoryList = $categories->fetchAll();
?>

<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Reminder Saya</h1>
        <p class="text-gray-500 mt-1">Daftar reminder yang Anda buat</p>
    </div>
    <?php if (hasRole(['pj', 'admin'])): ?>
    <a href="index.php?page=pj_reminder&action=create" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-xl transition-colors inline-flex items-center space-x-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <span>Tambah Reminder</span>
    </a>
    <?php endif; ?>
</div>

<!-- Search & Filter -->
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-3">
        <input type="hidden" name="page" value="pj_reminder">
        <div class="flex-1">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                   placeholder="Cari deskripsi atau kategori..."
                   class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
        </div>
        <div class="px-4 py-2 border border-gray-200 rounded-xl bg-gray-50">
            <?php if (count($kelasList) > 0): ?>
                <?php echo htmlspecialchars($kelasList[0]['nama_kelas']); ?> (Kelas Saya)
            <?php else: ?>
                <span class="text-red-600">Tidak ada kelas</span>
            <?php endif; ?>
        </div>
        <select name="category" class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            <option value="">Semua Kategori</option>
            <?php foreach ($categoryList as $category): ?>
                <option value="<?php echo $category['id']; ?>" <?php echo $categoryFilter == $category['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['nama_kategori']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2 rounded-xl transition-colors">
            Filter
        </button>
        <?php if ($search || $categoryFilter): ?>
        <a href="index.php?page=pj_reminder" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium px-6 py-2 rounded-xl transition-colors inline-flex items-center">
            Reset
        </a>
        <?php endif; ?>
    </form>
</div>

<!-- Reminders List View -->
<div class="grid grid-cols-1 gap-6">
    <?php if (count($reminders) > 0): ?>
        <?php foreach ($reminders as $reminder): ?>
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100 hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="mb-4">
                        <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($reminder['deskripsi']); ?></h3>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 border-b pb-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Tanggal</p>
                            <p class="font-medium text-gray-800"><?php echo date('d M Y', strtotime($reminder['tanggal'])); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Waktu</p>
                            <p class="font-medium text-gray-800"><?php echo $reminder['waktu'] ? date('H:i', strtotime($reminder['waktu'])) : 'Seharian'; ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Kategori</p>
                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($reminder['nama_kategori']); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Kelas</p>
                            <p class="font-medium text-gray-800"><?php echo $reminder['nama_kelas'] ? htmlspecialchars($reminder['nama_kelas']) : 'Tidak ada kelas'; ?></p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-4">
                        <span class="text-sm text-gray-500">
                            Dibuat: <?php echo date('d M Y H:i', strtotime($reminder['created_at'])); ?>
                        </span>

                        <div class="flex space-x-3">
                            <a href="index.php?page=pj_reminder&action=edit&id=<?php echo $reminder['id']; ?>"
                               class="text-blue-600 hover:text-blue-800 transition-colors flex items-center space-x-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                <span>Edit</span>
                            </a>
                            <a href="index.php?page=pj_reminder&action=delete&id=<?php echo $reminder['id']; ?>"
                               class="text-red-600 hover:text-red-800 transition-colors flex items-center space-x-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                <span>Hapus</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="text-center py-12">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-xl font-medium text-gray-500">Tidak ada reminder</p>
            <p class="text-gray-400 mt-2">Anda belum membuat reminder apapun</p>
            <?php if (hasRole(['pj', 'admin'])): ?>
            <a href="index.php?page=pj_reminder&action=create" class="mt-6 inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-8 rounded-xl transition-colors">
                Buat Reminder Pertama
            </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
        break;
}
?>