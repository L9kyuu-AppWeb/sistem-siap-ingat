<?php
if (isLoggedIn()) {
    redirect('index.php?page=dashboard');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = cleanInput($_POST['first_name']);
    $email = cleanInput($_POST['email']);
    $no_hp = cleanInput($_POST['no_hp']);
    $password = cleanInput($_POST['password']);
    $confirm_password = cleanInput($_POST['confirm_password']);
    $captcha_input = cleanInput($_POST['captcha']);
    $captcha_session = $_SESSION['captcha'] ?? '';

    // Validate CAPTCHA
    if (strtolower(trim($captcha_input)) !== strtolower(trim($captcha_session))) {
        $error = 'CAPTCHA tidak valid!';
    } elseif (empty($first_name) || empty($email) || empty($password) || empty($confirm_password) || empty($captcha_input)) {
        $error = 'Semua field harus diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak cocok!';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email sudah digunakan!';
        } else {
            // Check if murid with same email already exists
            $stmt = $pdo->prepare("SELECT id FROM murid WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email sudah digunakan!';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert into murid table, which will trigger user creation via the after_insert_murid trigger
                // Note: The trigger creates the user with default password, but we need the user to have the password they specified

                // Since the trigger creates a user with a default password, we need to insert the murid first,
                // then update the user's password separately
                try {
                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare("INSERT INTO murid (nama_lengkap, email, no_hp) VALUES (?, ?, ?)");
                    if ($stmt->execute([$first_name, $email, $no_hp])) {
                        $murid_id = $pdo->lastInsertId();
                        $username = 'murid' . $murid_id;

                        // Update the user's password to what they specified (the trigger created with default password)
                        $stmt = $pdo->prepare("UPDATE users SET password = ?, first_name = ?, last_name = NULL WHERE username = ?");
                        if ($stmt->execute([$hashed_password, $first_name, $username])) {
                            // Get the user ID for logging
                            $userStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                            $userStmt->execute([$username]);
                            $user = $userStmt->fetch();

                            if ($user) {
                                $pdo->commit();
                                logActivity($user['id'], 'register', 'New user registered as murid');
                                // Redirect to login page with success indicator
                                redirect('index.php?page=login&registered=1');
                            } else {
                                $pdo->rollback();
                                $error = 'Gagal membuat akun! Silakan coba lagi.';
                            }
                        } else {
                            $pdo->rollback();
                            $error = 'Gagal membuat akun! Silakan coba lagi.';
                        }
                    } else {
                        $pdo->rollback();
                        $error = 'Gagal mendaftar! Silakan coba lagi.';
                    }
                } catch (Exception $e) {
                    $pdo->rollback();
                    $error = 'Gagal mendaftar! Silakan coba lagi.';

                    // Clean up any orphaned murid record if exists
                    $cleanupStmt = $pdo->prepare("DELETE FROM murid WHERE email = ?");
                    $cleanupStmt->execute([$email]);
                }
            }
        }
    }
}

// Generate CAPTCHA values
$num1 = rand(1, 20);
$num2 = rand(1, 5);
$operation = rand(0, 1); // 0 for addition, 1 for subtraction
$operation_symbol = $operation === 0 ? '+' : '+';
$answer = $operation === 0 ? $num1 + $num2 : $num1 + $num2;

// Store correct answer in session
$_SESSION['captcha'] = $answer;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-500 to-blue-700 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-800"><?php echo SITE_NAME; ?></h1>
                <p class="text-gray-500 mt-2">Daftar akun baru</p>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-4">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" required value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                           placeholder="Masukkan nama lengkap">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                           placeholder="Masukkan email">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">No. HP</label>
                    <input type="tel" name="no_hp" value="<?php echo isset($_POST['no_hp']) ? htmlspecialchars($_POST['no_hp']) : ''; ?>"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                           placeholder="Masukkan nomor HP">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password" required
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                           placeholder="Masukkan password">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password <span class="text-red-500">*</span></label>
                    <input type="password" name="confirm_password" required
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                           placeholder="Ulangi password">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">CAPTCHA</label>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <div class="flex-1 flex items-center justify-center bg-gray-100 rounded-xl h-12 border border-gray-200 font-bold text-lg text-gray-800 min-h-[48px]">
                            <?php echo "$num1 $operation_symbol $num2 = ?"; ?>
                        </div>
                        <input type="text" name="captcha" required
                               class="px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition flex-1 min-w-[150px]"
                               placeholder="Jawaban">
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Masukkan hasil dari perhitungan di atas</p>
                </div>

                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors">
                    Daftar
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-gray-500">
                <p>Sudah punya akun? <a href="index.php?page=login" class="text-blue-600 hover:text-blue-800 font-medium">Masuk di sini</a></p>
            </div>
        </div>
    </div>

    <script>
        // Function to refresh CAPTCHA
        function refreshCaptcha() {
            location.reload();
        }
    </script>
</body>
</html>