<?php
// ekspor_db.php — Skrip untuk mencadangkan dan mengunduh database secara langsung melalui browser

require 'koneksi.php';

try {
    // Set header agar browser mengunduh sebagai file SQL
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="backup_database_kkn_' . date('Ymd_His') . '.sql"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Ambil daftar seluruh tabel
    $tables = [];
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $output = "-- ========================================================\n";
    $output .= "-- Backup Database: " . htmlspecialchars($db ?? 'kkn_kelompok12') . "\n";
    $output .= "-- Tanggal Ekspor: " . date('d-m-Y H:i:s') . " WIB\n";
    $output .= "-- Kelompok 12 UMP - Kelurahan Pal Lima\n";
    $output .= "-- ========================================================\n\n";
    $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        // 1. Dapatkan struktur pembuatan tabel (CREATE TABLE)
        $stmtStruct = $pdo->query("SHOW CREATE TABLE `$table`");
        $rowStruct = $stmtStruct->fetch(PDO::FETCH_ASSOC);
        
        $output .= "-- --------------------------------------------------------\n";
        $output .= "-- Struktur tabel untuk `$table`\n";
        $output .= "-- --------------------------------------------------------\n";
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $output .= $rowStruct['Create Table'] . ";\n\n";

        // 2. Dapatkan seluruh isi data tabel
        $stmtData = $pdo->query("SELECT * FROM `$table`");
        $rowsData = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        if (count($rowsData) > 0) {
            $output .= "-- Data untuk tabel `$table`\n";
            foreach ($rowsData as $row) {
                $keys = array_keys($row);
                $escaped_keys = array_map(function($key) {
                    return "`$key`";
                }, $keys);

                $values = array_values($row);
                $escaped_values = array_map(function($value) use ($pdo) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    return $pdo->quote($value);
                }, $values);

                $output .= "INSERT INTO `$table` (" . implode(', ', $escaped_keys) . ") VALUES (" . implode(', ', $escaped_values) . ");\n";
            }
            $output .= "\n";
        }
    }

    $output .= "SET FOREIGN_KEY_CHECKS=1;\n";

    echo $output;
    exit;

} catch (Throwable $e) {
    // Jika gagal, set header kembali ke text/html untuk memunculkan pesan kesalahan
    header_remove('Content-Type');
    header_remove('Content-Disposition');
    header('Content-Type: text/html; charset=utf-8');
    
    http_response_code(500);
    echo "<div style='font-family: sans-serif; padding: 20px; border: 1px solid #f5c2c2; background-color: #fcf2f2; color: #d9534f; border-radius: 8px;'>";
    echo "<h4 style='margin-top: 0;'>Gagal Mengekspor Database</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
