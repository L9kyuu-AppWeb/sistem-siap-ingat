<?php
// Check permission - only pj role can access this
if (!hasRole(['pj', 'admin'])) {
    require_once __DIR__ . '/../errors/403.php';
    exit;
}

// First, we need to get the murid ID that corresponds to this user ID
$currentUser = getCurrentUser();
$userMuridId = null;

// Get the murid ID by checking if the username follows the 'murid{id}' pattern
if (preg_match('/^murid(\d+)$/', $currentUser['username'], $matches)) {
    $userMuridId = (int)$matches[1];
} else {
    // If not following the murid pattern, it might be an admin or other role
    if (!hasRole('admin')) {
        // For non-admin users, if they're not a murid we can't find a match
        require_once __DIR__ . '/../errors/403.php';
        exit;
    }
}

// Get classes where user is the pj
$sql = "SELECT k.*, m.nama_lengkap as pj_nama 
        FROM kelas k
        JOIN murid m ON k.pj_id = m.id";
        
if (!$userMuridId && !hasRole('admin')) {
    $sql .= " WHERE k.pj_id = 0";  // No results for invalid users
} elseif ($userMuridId) {
    $sql .= " WHERE k.pj_id = ?";  // Only classes where user is pj
}

$sql .= " ORDER BY k.created_at DESC";

$stmt = $pdo->prepare($sql);

if ($userMuridId) {
    $stmt->execute([$userMuridId]);
} elseif (hasRole('admin')) {
    $stmt->execute();  // Admin sees all classes
}

$kelasList = $stmt->fetchAll();

// Handle bulk delete - delete all students except current user from a class
if (isset($_POST['bulk_delete']) && isset($_POST['kelas_id'])) {
    $kelasId = (int)$_POST['kelas_id'];
    
    // Verify that this class belongs to the current user (if not admin)
    $checkStmt = $pdo->prepare("SELECT * FROM kelas WHERE id = ? AND pj_id = ?");
    $checkStmt->execute([$kelasId, $userMuridId]);
    $kelas = $checkStmt->fetch();
    
    if ($kelas || hasRole('admin')) {
        // Delete all students in this class except the current user (pj)
        $deleteStmt = $pdo->prepare("DELETE FROM murid_kelas WHERE kelas_id = ? AND murid_id != ?");
        if ($deleteStmt->execute([$kelasId, $userMuridId])) {
            logActivity($_SESSION['user_id'], 'bulk_delete_murid_kelas', "Deleted all students from class: " . $kelas['nama_kelas'] . " except PJ");
            setAlert('success', 'Semua murid di kelas ini telah dihapus, kecuali penanggung jawab!');
        } else {
            setAlert('error', 'Gagal menghapus murid dari kelas!');
        }
    } else {
        setAlert('error', 'Kelas tidak ditemukan atau Anda bukan penanggung jawab kelas ini!');
    }
    
    redirect('index.php?page=pj_murid_kelas');
}

// Handle individual delete
if (isset($_POST['delete_student']) && isset($_POST['murid_kelas_id'])) {
    $muridKelasId = (int)$_POST['murid_kelas_id'];
    
    // Get the kelas_id and murid_id associated with this entry
    $getStmt = $pdo->prepare("SELECT mk.kelas_id, mk.murid_id, k.pj_id 
                              FROM murid_kelas mk
                              JOIN kelas k ON mk.kelas_id = k.id
                              WHERE mk.id = ?");
    $getStmt->execute([$muridKelasId]);
    $entry = $getStmt->fetch();
    
    if ($entry) {
        // Verify that the class belongs to the current user (if not admin)
        if ($entry['pj_id'] == $userMuridId || hasRole('admin')) {
            // Make sure it's not the PJ themselves
            if ($entry['murid_id'] != $userMuridId) {
                $deleteStmt = $pdo->prepare("DELETE FROM murid_kelas WHERE id = ?");
                if ($deleteStmt->execute([$muridKelasId])) {
                    logActivity($_SESSION['user_id'], 'delete_murid_kelas', "Deleted student from class");
                    setAlert('success', 'Murid berhasil dihapus dari kelas!');
                } else {
                    setAlert('error', 'Gagal menghapus murid dari kelas!');
                }
            } else {
                setAlert('error', 'Anda tidak dapat menghapus diri sendiri dari kelas!');
            }
        } else {
            setAlert('error', 'Anda bukan penanggung jawab kelas ini!');
        }
    } else {
        setAlert('error', 'Data murid kelas tidak ditemukan!');
    }
    
    redirect('index.php?page=pj_murid_kelas');
}
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Murid dalam Kelas Saya</h1>
    <p class="text-gray-500 mt-1">Daftar murid yang tergabung dalam kelas yang Anda kelola</p>
</div>

<?php if (count($kelasList) > 0): ?>
    <?php foreach ($kelasList as $kelas): ?>
        <?php
        // Get students in this class
        $studentsSql = "SELECT mk.id as murid_kelas_id, m.nama_lengkap, m.email, m.no_hp, mk.joined_at
                        FROM murid_kelas mk
                        JOIN murid m ON mk.murid_id = m.id
                        WHERE mk.kelas_id = ?
                        ORDER BY mk.joined_at DESC";
        $studentsStmt = $pdo->prepare($studentsSql);
        $studentsStmt->execute([$kelas['id']]);
        $students = $studentsStmt->fetchAll();
        ?>
        
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-6 border border-gray-100">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($kelas['nama_kelas']); ?></h2>
                        <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                            Kelas
                        </span>
                    </div>
                    <p class="text-sm text-gray-600">Tahun Ajaran: <?php echo htmlspecialchars($kelas['tahun_ajaran']); ?></p>
                    <p class="text-sm text-gray-600">Penanggung Jawab: <?php echo htmlspecialchars($kelas['pj_nama']); ?></p>
                </div>
                
                <div class="flex space-x-2">
                    <!-- Bulk delete button -->
                    <form method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus semua murid dari kelas <?php echo addslashes(htmlspecialchars($kelas['nama_kelas'])); ?>? Hanya penanggung jawab yang akan tetap berada di kelas.');">
                        <input type="hidden" name="kelas_id" value="<?php echo $kelas['id']; ?>">
                        <input type="hidden" name="bulk_delete" value="1">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-xl transition-colors flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Hapus Semua Murid</span>
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Students table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No HP</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bergabung</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($students) > 0): ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img class="h-10 w-10 rounded-full" src="<?php echo getAvatarUrl(null); ?>" alt="<?php echo htmlspecialchars($student['nama_lengkap']); ?>">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($student['nama_lengkap']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($student['no_hp']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d M Y', strtotime($student['joined_at'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <!-- Individual delete button -->
                                        <form method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus murid <?php echo addslashes(htmlspecialchars($student['nama_lengkap'])); ?> dari kelas ini?');">
                                            <input type="hidden" name="murid_kelas_id" value="<?php echo $student['murid_kelas_id']; ?>">
                                            <input type="hidden" name="delete_student" value="1">
                                            <button type="submit" class="text-red-600 hover:text-red-900 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Tidak ada murid dalam kelas ini
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (count($students) === 0): ?>
                <div class="text-center py-6 text-gray-500">
                    Tidak ada murid dalam kelas ini
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="bg-white rounded-2xl shadow-sm p-12 text-center">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <h3 class="text-lg font-medium text-gray-800 mb-2">Tidak ada kelas yang Anda kelola</h3>
        <p class="text-gray-500">Anda belum menjadi penanggung jawab kelas manapun</p>
    </div>
<?php endif; ?>