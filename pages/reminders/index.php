<?php
// Check permission
if (!hasRole(['admin', 'manager'])) {
    require_once __DIR__ . '/../errors/403.php';
    exit;
}

$action = isset($_GET['action']) ? cleanInput($_GET['action']) : 'list';

switch ($action) {
    case 'create':
        require_once 'create.php';
        break;
    case 'edit':
        require_once 'edit.php';
        break;
    case 'delete':
        require_once 'delete.php';
        break;
    default:
        // List reminders
        $search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
        $kelasFilter = isset($_GET['kelas']) ? cleanInput($_GET['kelas']) : '';
        $categoryFilter = isset($_GET['category']) ? cleanInput($_GET['category']) : '';
        $typeFilter = isset($_GET['type']) ? cleanInput($_GET['type']) : '';

        $sql = "SELECT r.*, k.nama_kelas, rc.nama_kategori, u.username as created_by_name FROM reminders r
                LEFT JOIN kelas k ON r.kelas_id = k.id
                LEFT JOIN reminder_categories rc ON r.category_id = rc.id
                LEFT JOIN users u ON r.created_by = u.id
                WHERE 1=1";

        if ($search) {
            $sql .= " AND (r.deskripsi LIKE :search1 OR r.tanggal LIKE :search2 OR k.nama_kelas LIKE :search3 OR rc.nama_kategori LIKE :search4)";
        }

        if ($kelasFilter) {
            $sql .= " AND r.kelas_id = :kelas_id";
        }

        if ($categoryFilter) {
            $sql .= " AND r.category_id = :category_id";
        }


        $sql .= " ORDER BY r.tanggal DESC, r.waktu ASC";

        $stmt = $pdo->prepare($sql);

        if ($search) {
            $stmt->bindValue(':search1', "%$search%");
            $stmt->bindValue(':search2', "%$search%");
            $stmt->bindValue(':search3', "%$search%");
            $stmt->bindValue(':search4', "%$search%");
        }
        if ($kelasFilter) {
            $stmt->bindValue(':kelas_id', $kelasFilter);
        }
        if ($categoryFilter) {
            $stmt->bindValue(':category_id', $categoryFilter);
        }

        $stmt->execute();
        $remindersList = $stmt->fetchAll();

        // Get distinct kelas and categories for filter
        $kelasList = $pdo->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas")->fetchAll();
        $categoryList = $pdo->query("SELECT id, nama_kategori FROM reminder_categories ORDER BY nama_kategori")->fetchAll();
?>

<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Manajemen Reminder</h1>
        <p class="text-gray-500 mt-1">Kelola reminder-reminder</p>
    </div>
    <?php if (hasRole(['admin', 'manager'])): ?>
    <a href="index.php?page=reminders&action=create" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-xl transition-colors inline-flex items-center space-x-2">
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
        <input type="hidden" name="page" value="reminders">
        <div class="flex-1">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                   placeholder="Cari deskripsi, tanggal, atau kelas..."
                   class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
        </div>
        <select name="kelas" class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            <option value="" <?php echo $kelasFilter === '' ? 'selected' : ''; ?>>Semua Kelas</option>
            <?php foreach ($kelasList as $kelas): ?>
                <option value="<?php echo $kelas['id']; ?>" <?php echo (string)$kelasFilter === (string)$kelas['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($kelas['nama_kelas']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="category" class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            <option value="" <?php echo $categoryFilter === '' ? 'selected' : ''; ?>>Semua Kategori</option>
            <?php foreach ($categoryList as $category): ?>
                <option value="<?php echo $category['id']; ?>" <?php echo (string)$categoryFilter === (string)$category['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['nama_kategori']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2 rounded-xl transition-colors">
            Filter
        </button>
        <?php if ($search || $kelasFilter || $categoryFilter): ?>
        <a href="index.php?page=reminders" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium px-6 py-2 rounded-xl transition-colors inline-flex items-center">
            Reset
        </a>
        <?php endif; ?>
    </form>
</div>

<!-- Reminders Card View -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (count($remindersList) > 0): ?>
        <?php foreach ($remindersList as $reminder): ?>
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100 hover:shadow-md transition-shadow">
                <!-- Reminder Content -->
                <div class="p-5">
                    <div class="mb-3">
                        <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($reminder['deskripsi']); ?></h3>
                        <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($reminder['nama_kategori']); ?></p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 mb-4">
                        <div>
                            <p class="text-xs text-gray-500">Tanggal</p>
                            <p class="font-medium"><?php echo date('d M Y', strtotime($reminder['tanggal'])); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Waktu</p>
                            <p class="font-medium"><?php echo $reminder['waktu'] ? htmlspecialchars($reminder['waktu']) : 'N/A'; ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Kelas</p>
                            <p class="font-medium"><?php echo htmlspecialchars($reminder['nama_kelas'] ?? 'Reminder Umum'); ?></p>
                        </div>
                    </div>

                    <div class="flex justify-between items-center">
                        <p class="text-xs text-gray-500">
                            Dibuat oleh: <?php echo htmlspecialchars($reminder['created_by_name']); ?>
                        </p>

                        <div class="flex space-x-2">
                            <a href="index.php?page=reminders&action=edit&id=<?php echo $reminder['id']; ?>"
                               class="text-blue-600 hover:text-blue-800 transition-colors" title="Edit">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            <a href="index.php?page=reminders&action=delete&id=<?php echo $reminder['id']; ?>"
                               class="text-red-600 hover:text-red-800 transition-colors" title="Hapus">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-span-full text-center py-12">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-lg font-medium text-gray-500">Tidak ada data reminder</p>
        </div>
    <?php endif; ?>
</div>

<?php
        break;
}
?>