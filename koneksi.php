<?php
// koneksi.php
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$host_hosting = 'sql206.infinityfree.com';
$db_hosting   = 'if0_42466670_kkn12';
$user_hosting = 'if0_42466670';
$pass_hosting = 'ADIxZ1ta08qDy';

$is_local_host = !isset($_SERVER['HTTP_HOST']) || (
    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false
);

$pdo = null;

// 1. Jika berjalan di localhost, coba MySQL Lokal dulu
if ($is_local_host) {
    $local_dbs = ['if0_42466670_kkn12', 'kkn_kelompok12', 'database_kkn'];
    foreach ($local_dbs as $ldb) {
        try {
            $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=$ldb;charset=utf8mb4", 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 2
            ]);
            if ($pdo) break;
        } catch (Throwable $e) {}
    }
}

// 2. Jika belum terhubung, coba MySQL Hosting InfinityFree
if (!$pdo) {
    try {
        $pdo = new PDO("mysql:host=$host_hosting;port=3306;dbname=$db_hosting;charset=utf8mb4", $user_hosting, $pass_hosting, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
    } catch (Throwable $e) {
        $last_db_error = $e->getMessage();
    }
}

// 3. Fallback Cerdas ke SQLite Lokal agar Aplikasi Tetap Berjalan jika Server MySQL Tidak Dapat Diakses
if (!$pdo) {
    $sqlite_file = __DIR__ . '/database_kkn_local.sqlite';
    if (file_exists($sqlite_file) && extension_loaded('pdo_sqlite')) {
        try {
            $pdo = new PDO("sqlite:" . $sqlite_file, null, null, $options);
        } catch (Throwable $e) {}
    }
}

// 4. Jika tetap gagal terhubung (misal kredensial InfinityFree berubah)
if (!$pdo) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>Koneksi Database InfinityFree — KKN Kelompok 12</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '<style>body{background:#f8fafc;font-family:sans-serif;padding:40px 15px;}.card{border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,0.08);}</style></head><body>';
    echo '<div class="container" style="max-width: 650px;">';
    echo '<div class="card p-4 p-md-5 border-0 bg-white">';
    echo '<div class="text-center mb-4"><span class="badge bg-danger px-3 py-2 fs-6 rounded-pill">Gagal Terhubung ke Database MySQL</span></div>';
    echo '<h4 class="fw-bold text-dark text-center mb-3">Konfigurasi Database InfinityFree</h4>';
    echo '<p class="text-muted text-center mb-4">Aplikasi KKN Kelompok 12 gagal terhubung ke database hosting InfinityFree.</p>';
    echo '<div class="alert alert-warning" role="alert"><strong class="d-block mb-1">Pesan Error:</strong> <code>' . htmlspecialchars($last_db_error ?? 'Database tidak dapat diakses.') . '</code></div>';
    echo '<div class="mt-4"><h6 class="fw-bold text-dark mb-2">Panduan Perbaikan di InfinityFree:</h6>';
    echo '<ol class="text-muted small ps-3" style="line-height: 1.7;">';
    echo '<li>Buka <strong>Control Panel (cPanel) InfinityFree</strong> atau <strong>Client Area</strong> Anda.</li>';
    echo '<li>Pilih menu <strong>MySQL Databases</strong>, lalu catat <em>MySQL Hostname</em>, <em>Database Name</em>, dan <em>MySQL Username</em>.</li>';
    echo '<li>Buka file <code>koneksi.php</code> pada File Manager InfinityFree (folder <code>htdocs/</code>), lalu sesuaikan variabel di baris 9 - 12:';
    echo '<pre class="bg-dark text-warning p-3 rounded-3 mt-2"><code>$host_hosting = \'HOSTNAME_ANDA\';\n$db_hosting   = \'NAMA_DATABASE_ANDA\';\n$user_hosting = \'USERNAME_ANDA\';\n$pass_hosting = \'PASSWORD_CPANEL_ANDA\';</code></pre></li>';
    echo '<li>Impor file <code>if0_42466670_kkn12.sql</code> ke <strong>phpMyAdmin</strong> InfinityFree Anda.</li>';
    echo '</ol></div>';
    echo '</div></div></body></html>';
    exit;
}

if ($pdo) {
    try {
        $pdo->exec("ALTER TABLE program_kerja ADD COLUMN link VARCHAR(255) NULL");
    } catch (Throwable $e) {}
    try {
        $pdo->exec("UPDATE program_kerja SET link = 'https://kkn12.ct.ws/?i=1' WHERE link IS NULL OR link = ''");
    } catch (Throwable $e) {}
}

if (!function_exists('parseGoogleDrivePhotoUrl')) {
    function parseGoogleDrivePhotoUrl(?string $url): string {
        $url = trim($url ?? '');
        if (empty($url)) return '';
        if (preg_match('/(?:drive\.google\.com\/(?:file\/d\/|open\?id=|uc\?id=)|lh3\.googleusercontent\.com\/d\/)([a-zA-Z0-9_-]+)/i', $url, $matches)) {
            return 'https://lh3.googleusercontent.com/d/' . $matches[1];
        }
        return $url;
    }
}

if (!function_exists('parsePhotoUrl')) {
    function parsePhotoUrl(?string $path): string {
        $path = trim($path ?? '');
        if (empty($path)) return 'img/default-placeholder.jpg';
        return parseGoogleDrivePhotoUrl($path);
    }
}