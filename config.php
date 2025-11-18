<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistem_siap_ingat');

// Konfigurasi Aplikasi
define('SITE_NAME', 'Siap Ingat');
define('BASE_URL', 'http://assuring-quail-real.ngrok-free.app/sistem-siap-ingat/');
define('UPLOAD_PATH', __DIR__ . '/assets/uploads/');

// Konfigurasi Session
define('SESSION_TIMEOUT', 3600); // 1 hour

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}