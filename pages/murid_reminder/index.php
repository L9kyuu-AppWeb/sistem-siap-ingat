<?php
// Check permission - murid and pj roles can access this
if (!hasRole(['murid', 'pj', 'admin'])) {
    require_once __DIR__ . '/../errors/403.php';
    exit;
}

// Get current user info
$currentUser = getCurrentUser();
$userMuridId = null;

// Get the murid ID by checking if the username follows the 'murid{id}' pattern
if (preg_match('/^murid(\d+)$/', $currentUser['username'], $matches)) {
    $userMuridId = (int)$matches[1];
} elseif (hasRole('admin')) {
    // For admin, we'll allow viewing all reminders
    $userMuridId = null;
}

// Get filter parameters
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$kelasFilter = isset($_GET['kelas']) ? (int)$_GET['kelas'] : 0;
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Build the query - get global reminders AND reminders from classes the user is part of
$sql = "SELECT r.*, rc.nama_kategori, k.nama_kelas, u.first_name as created_by_name
        FROM reminders r
        LEFT JOIN reminder_categories rc ON r.category_id = rc.id
        LEFT JOIN kelas k ON r.kelas_id = k.id
        LEFT JOIN users u ON r.created_by = u.id
        WHERE (r.kelas_id IS NULL OR r.kelas_id IN (
            SELECT mk.kelas_id
            FROM murid_kelas mk
            WHERE mk.murid_id = :user_murid_id
        ) OR r.created_by = :created_by)";

if ($search) {
    $sql .= " AND (r.deskripsi LIKE :search1 OR rc.nama_kategori LIKE :search2)";
}

if ($kelasFilter) {
    $sql .= " AND r.kelas_id = :kelas";
}

if ($categoryFilter) {
    $sql .= " AND r.category_id = :category";
}

$sql .= " ORDER BY r.tanggal DESC, r.waktu DESC";

$stmt = $pdo->prepare($sql);

// Bind parameters
$stmt->bindValue(':user_murid_id', $userMuridId, PDO::PARAM_INT);
$stmt->bindValue(':created_by', $_SESSION['user_id'], PDO::PARAM_INT);

if ($search) {
    $searchParam = "%$search%";
    $stmt->bindValue(':search1', $searchParam);
    $stmt->bindValue(':search2', $searchParam);
}
if ($kelasFilter) {
    $stmt->bindValue(':kelas', $kelasFilter, PDO::PARAM_INT);
}
if ($categoryFilter) {
    $stmt->bindValue(':category', $categoryFilter, PDO::PARAM_INT);
}

$stmt->execute();
$reminders = $stmt->fetchAll();

// Get distinct classes and categories for filter (only from accessible reminders)
$kelasOptions = $pdo->prepare("
    SELECT DISTINCT k.id, k.nama_kelas
    FROM reminders r
    LEFT JOIN kelas k ON r.kelas_id = k.id
    WHERE (r.kelas_id IS NULL OR r.kelas_id IN (
        SELECT mk.kelas_id 
        FROM murid_kelas mk 
        WHERE mk.murid_id = ?
    ) OR r.created_by = ?)
    AND r.kelas_id IS NOT NULL
    ORDER BY k.nama_kelas
");
$kelasOptions->execute([$userMuridId, $_SESSION['user_id']]);
$kelasList = $kelasOptions->fetchAll();

$categories = $pdo->prepare("
    SELECT DISTINCT rc.id, rc.nama_kategori
    FROM reminders r
    JOIN reminder_categories rc ON r.category_id = rc.id
    WHERE (r.kelas_id IS NULL OR r.kelas_id IN (
        SELECT mk.kelas_id 
        FROM murid_kelas mk 
        WHERE mk.murid_id = ?
    ) OR r.created_by = ?)
    ORDER BY rc.nama_kategori
");
$categories->execute([$userMuridId, $_SESSION['user_id']]);
$categoryList = $categories->fetchAll();
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Reminder Kelas</h1>
    <p class="text-gray-500 mt-1">Reminder yang tersedia di kelas Anda dan reminder global</p>
</div>

<!-- Search & Filter -->
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-3">
        <input type="hidden" name="page" value="murid_reminder">
        <div class="flex-1">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                   placeholder="Cari deskripsi atau kategori..."
                   class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
        </div>
        <select name="kelas" class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            <option value="">Semua Kelas</option>
            <?php foreach ($kelasList as $kelas): ?>
                <option value="<?php echo $kelas['id']; ?>" <?php echo $kelasFilter == $kelas['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($kelas['nama_kelas']); ?>
                </option>
            <?php endforeach; ?>
        </select>
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
        <?php if ($search || $kelasFilter || $categoryFilter): ?>
        <a href="index.php?page=murid_reminder" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium px-6 py-2 rounded-xl transition-colors inline-flex items-center">
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
                            <p class="font-medium text-gray-800"><?php echo $reminder['nama_kelas'] ? htmlspecialchars($reminder['nama_kelas']) : 'Global'; ?></p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-4">
                        <span class="text-sm text-gray-500">
                            Dibuat: <?php echo date('d M Y H:i', strtotime($reminder['created_at'])); ?>
                            <?php if ($reminder['created_by'] == $_SESSION['user_id']): ?>
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    Milik Anda
                                </span>
                            <?php endif; ?>
                        </span>
                        
                        <div class="flex space-x-3">
                            <?php if ($reminder['created_by'] == $_SESSION['user_id']): ?>
                                <span class="text-gray-400 italic">Reminder Anda</span>
                            <?php else: ?>
                                <span class="text-gray-400 italic">Dibuat oleh <?php echo htmlspecialchars($reminder['created_by_name'] ?? 'Pengguna'); ?></span>
                            <?php endif; ?>
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
            <p class="text-gray-400 mt-2">Tidak ada reminder yang tersedia di kelas Anda atau reminder global</p>
        </div>
    <?php endif; ?>
</div>
</div>