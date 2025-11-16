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
        // List murid
        $search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
        $kelasFilter = isset($_GET['kelas']) ? cleanInput($_GET['kelas']) : '';
        $tahunAjaranFilter = isset($_GET['tahun_ajaran']) ? cleanInput($_GET['tahun_ajaran']) : '';

        $sql = "SELECT m.*, GROUP_CONCAT(k.nama_kelas SEPARATOR ', ') as kelas_nama FROM murid m LEFT JOIN murid_kelas mk ON m.id = mk.murid_id LEFT JOIN kelas k ON mk.kelas_id = k.id WHERE 1=1 GROUP BY m.id";

        if ($search) {
            $sql .= " AND (m.nama_lengkap LIKE :search1 OR m.email LIKE :search2 OR m.no_hp LIKE :search3)";
        }

        if ($kelasFilter) {
            $sql .= " AND mk.kelas_id = :kelas_id";
        }

        if ($tahunAjaranFilter) {
            $sql .= " AND k.tahun_ajaran = :tahun_ajaran";
        }

        $sql .= " ORDER BY m.nama_lengkap ASC";

        $stmt = $pdo->prepare($sql);

        if ($search) {
            $stmt->bindValue(':search1', "%$search%");
            $stmt->bindValue(':search2', "%$search%");
            $stmt->bindValue(':search3', "%$search%");
        }
        if ($kelasFilter) {
            $stmt->bindValue(':kelas_id', $kelasFilter);
        }
        if ($tahunAjaranFilter) {
            $stmt->bindValue(':tahun_ajaran', $tahunAjaranFilter);
        }

        $stmt->execute();
        $muridList = $stmt->fetchAll();

        // Get distinct kelas for filter
        $kelasQuery = "SELECT DISTINCT k.id, k.nama_kelas FROM kelas k
                      INNER JOIN murid_kelas mk ON k.id = mk.kelas_id
                      ORDER BY k.nama_kelas";
        $kelasStmt = $pdo->prepare($kelasQuery);
        $kelasStmt->execute();
        $kelasList = $kelasStmt->fetchAll();

        $tahunAjaran = $pdo->query("SELECT DISTINCT k.tahun_ajaran FROM kelas k
                                   INNER JOIN murid_kelas mk ON k.id = mk.kelas_id
                                   WHERE k.tahun_ajaran IS NOT NULL
                                   ORDER BY k.tahun_ajaran DESC")->fetchAll();
?>

<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Manajemen Murid</h1>
        <p class="text-gray-500 mt-1">Kelola data murid</p>
    </div>
    <?php if (hasRole(['admin', 'manager'])): ?>
    <a href="index.php?page=murid&action=create" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-xl transition-colors inline-flex items-center space-x-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <span>Tambah Murid</span>
    </a>
    <?php endif; ?>
</div>

<!-- Search & Filter -->
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-3">
        <input type="hidden" name="page" value="murid">
        <div class="flex-1">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                   placeholder="Cari nama, email, atau nomor HP..."
                   class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
        </div>
        <select name="kelas" class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            <option value="">Semua Kelas</option>
            <?php foreach ($kelasList as $kelas): ?>
                <option value="<?php echo $kelas['id']; ?>" <?php echo $kelasFilter === $kelas['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($kelas['nama_kelas']); ?>
                </option>
            <?php endforeach; ?>
        </select>
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
        <?php if ($search || $kelasFilter || $tahunAjaranFilter): ?>
        <a href="index.php?page=murid" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium px-6 py-2 rounded-xl transition-colors inline-flex items-center">
            Reset
        </a>
        <?php endif; ?>
    </form>
</div>

<!-- Murid Card View -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (count($muridList) > 0): ?>
        <?php foreach ($muridList as $murid): ?>
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100 hover:shadow-md transition-shadow">
                <!-- Murid Content -->
                <div class="p-5">
                    <div class="mb-3">
                        <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($murid['nama_lengkap']); ?></h3>
                        <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($murid['email']); ?></p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 mb-4">
                        <div>
                            <p class="text-xs text-gray-500">No. HP</p>
                            <p class="font-medium"><?php echo htmlspecialchars($murid['no_hp'] ?? 'N/A'); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Kelas</p>
                            <p class="font-medium"><?php echo htmlspecialchars($murid['kelas_nama'] ?? 'Belum terdaftar di kelas'); ?></p>
                        </div>
                    </div>

                    <div class="flex justify-between items-center">
                        <p class="text-xs text-gray-500">
                            Dibuat: <?php echo date('d M Y', strtotime($murid['created_at'])); ?>
                        </p>

                        <div class="flex space-x-2">
                            <a href="index.php?page=murid&action=edit&id=<?php echo $murid['id']; ?>"
                               class="text-blue-600 hover:text-blue-800 transition-colors" title="Edit">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            <a href="index.php?page=murid&action=delete&id=<?php echo $murid['id']; ?>"
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <p class="text-lg font-medium text-gray-500">Tidak ada data murid</p>
        </div>
    <?php endif; ?>
</div>

<?php
        break;
}
?>