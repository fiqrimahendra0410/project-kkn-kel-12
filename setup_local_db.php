<?php
// setup_local_db.php - Import and fix database for local XAMPP usage

echo "=== MEMPROSES DATABASE KKN ===\n";

$sql_file = __DIR__ . '/if0_42466670_kkn12.sql';
if (!file_exists($sql_file)) {
    die("Error: File $sql_file tidak ditemukan!\n");
}

$sql_content = file_get_contents($sql_file);

// Perbaiki kompatibilitas collation (utf8mb4_0900_ai_ci -> utf8mb4_general_ci untuk MariaDB XAMPP)
$sql_content_cleaned = str_replace('utf8mb4_0900_ai_ci', 'utf8mb4_general_ci', $sql_content);

// Simpan file SQL yang sudah disesuaikan untuk XAMPP
file_put_contents(__DIR__ . '/database_kkn_xampp.sql', $sql_content_cleaned);
echo "[OK] SQL dump disesuaikan dengan collation XAMPP (database_kkn_xampp.sql).\n";

$pdo_last = null;

// Connect to MySQL local (XAMPP)
try {
    $pdo_server = new PDO("mysql:host=127.0.0.1;port=3306;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "[OK] Terhubung ke MySQL XAMPP (127.0.0.1).\n";

    $databases = ['if0_42466670_kkn12', 'kkn_kelompok12', 'database_kkn'];
    foreach ($databases as $dbname) {
        $pdo_server->exec("DROP DATABASE IF EXISTS `$dbname`");
        $pdo_server->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        echo "[OK] Database `$dbname` dibuat ulang di MySQL.\n";

        // Connect to database specifically
        $pdo_db = new PDO("mysql:host=127.0.0.1;port=3306;dbname=$dbname;charset=utf8mb4", 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Disable foreign key checks while importing
        $pdo_db->exec("SET FOREIGN_KEY_CHECKS=0");
        
        // Execute SQL script
        $pdo_db->exec($sql_content_cleaned);

        $pdo_db->exec("SET FOREIGN_KEY_CHECKS=1");
        echo "[OK] Import data ke database MySQL `$dbname` BERHASIL!\n";
        $pdo_last = $pdo_db;
    }

} catch (Throwable $e) {
    echo "[WARN] Gagal terhubung ke MySQL: " . $e->getMessage() . "\n";
}

// Juga update SQLite local fallback agar 100% data dari zip ada di SQLite juga
echo "\n=== MEMPROSES BACKUP SQLITE ===\n";
try {
    $sqlite_file = __DIR__ . '/database_kkn_local.sqlite';
    if (file_exists($sqlite_file)) {
        unlink($sqlite_file);
    }
    
    // Switch to MySQL to pull schema & data into SQLite if MySQL was populated
    if ($pdo_last !== null) {
        $pdo_sqlite = new PDO("sqlite:" . $sqlite_file);
        $pdo_sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $tables_res = $pdo_last->query("SHOW TABLES");
        $tables = $tables_res->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            // Get columns info
            $col_res = $pdo_last->query("SHOW COLUMNS FROM `$table`");
            $cols = $col_res->fetchAll(PDO::FETCH_ASSOC);

            $col_defs = [];
            $col_names = [];
            foreach ($cols as $col) {
                $col_names[] = "`" . $col['Field'] . "`";
                $type = 'TEXT';
                if (strpos($col['Type'], 'int') !== false) $type = 'INTEGER';
                $col_defs[] = "`" . $col['Field'] . "` " . $type;
            }

            $create_stmt = "CREATE TABLE IF NOT EXISTS `$table` (" . implode(', ', $col_defs) . ")";
            $pdo_sqlite->exec($create_stmt);

            $rows = $pdo_last->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $placeholders = implode(', ', array_fill(0, count($col_names), '?'));
                $insert_sql = "INSERT INTO `$table` (" . implode(', ', $col_names) . ") VALUES ($placeholders)";
                $stmt = $pdo_sqlite->prepare($insert_sql);
                foreach ($rows as $row) {
                    $stmt->execute(array_values($row));
                }
            }
            echo "[OK] SQLite sync tabel: $table (" . count($rows) . " baris)\n";
        }
    }
} catch (Throwable $e) {
    echo "[WARN] Gagal sync SQLite: " . $e->getMessage() . "\n";
}

echo "\n=== PROSES SELESAI ===\n";
