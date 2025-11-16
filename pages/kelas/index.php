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
        // List kelas
        $search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
        $tahunAjaranFilter = isset($_GET['tahun_ajaran']) ? cleanInput($_GET['tahun_ajaran']) : '';

        $sql = "SELECT k.*, m.nama_lengkap as pj_name FROM kelas k LEFT JOIN murid m ON k.pj_id = m.id WHERE 1=1";

        if ($search) {
            $sql .= " AND (k.nama_kelas LIKE :search1 OR k.tahun_ajaran LIKE :search2 OR m.nama_lengkap LIKE :search3)";
        }

        if ($tahunAjaranFilter) {
            $sql .= " AND tahun_ajaran = :tahun_ajaran";
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $pdo->prepare($sql);

        if ($search) {
            $stmt->bindValue(':search1', "%$search%");
            $stmt->bindValue(':search2', "%$search%");
            $stmt->bindValue(':search3', "%$search%");
        }
        if ($tahunAjaranFilter) {
            $stmt->bindValue(':tahun_ajaran', $tahunAjaranFilter);
        }

        $stmt->execute();
        $kelasList = $stmt->fetchAll();

        // Get distinct tahun ajaran for filter
        $tahunAjaran = $pdo->query("SELECT DISTINCT tahun_ajaran FROM kelas WHERE tahun_ajaran IS NOT NULL ORDER BY tahun_ajaran DESC")->fetchAll();
?>

<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Manajemen Kelas</h1>
        <p class="text-gray-500 mt-1">Kelola kelas pelajaran</p>
    </div>
    <?php if (hasRole(['admin', 'manager'])): ?>
    <a href="index.php?page=kelas&action=create" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-xl transition-colors inline-flex items-center space-x-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <span>Tambah Kelas</span>
    </a>
    <?php endif; ?>
</div>

<!-- Search & Filter -->
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-3">
        <input type="hidden" name="page" value="kelas">
        <div class="flex-1">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                   placeholder="Cari nama kelas atau tahun ajaran..."
                   class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
        </div>
        <select name="tahun_ajaran" class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            <option value="">Semua Tahun Ajaran</option>
            <?php foreach ($tahunAjaran as $tahun): ?>
                <option value="<?php echo $tahun['tahun_ajaran']; ?>" <?php echo $tahunAjaranFilter === $tahun['tahun_ajaran'] ? 'selected' : ''; ?>>
                    <?php echo $tahun['tahun_ajaran']; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2 rounded-xl transition-colors">
            Filter
        </button>
        <?php if ($search || $tahunAjaranFilter): ?>
        <a href="index.php?page=kelas" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium px-6 py-2 rounded-xl transition-colors inline-flex items-center">
            Reset
        </a>
        <?php endif; ?>
    </form>
</div>

<!-- Kelas Card View -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (count($kelasList) > 0): ?>
        <?php foreach ($kelasList as $kelas): ?>
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100 hover:shadow-md transition-shadow">
                <!-- Kelas Content -->
                <div class="p-5">
                    <div class="mb-3">
                        <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($kelas['nama_kelas']); ?></h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Tahun Ajaran: <?php echo htmlspecialchars($kelas['tahun_ajaran']); ?>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 mb-4">
                        <div>
                            <p class="text-xs text-gray-500">Penanggung Jawab</p>
                            <p class="font-medium"><?php echo htmlspecialchars($kelas['pj_name'] ?? 'Belum ditentukan'); ?></p>
                        </div>
                    </div>

                    <div class="flex justify-between items-center">
                        <p class="text-xs text-gray-500">
                            Dibuat: <?php echo date('d M Y', strtotime($kelas['created_at'])); ?>
                        </p>

                        <div class="flex space-x-2">
                            <a href="index.php?page=kelas&action=edit&id=<?php echo $kelas['id']; ?>"
                               class="text-blue-600 hover:text-blue-800 transition-colors" title="Edit">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            <a href="index.php?page=kelas&action=delete&id=<?php echo $kelas['id']; ?>"
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747.777 3.332 1.253 4.5 2.027v13C19.832 18.477 18.247 18 16.5 18c-1.746.777-3.332 1.253-4.5 2.027"/>
            </svg>
            <p class="text-lg font-medium text-gray-500">Tidak ada data kelas</p>
        </div>
    <?php endif; ?>
</div>

<?php
        break;
}
?>