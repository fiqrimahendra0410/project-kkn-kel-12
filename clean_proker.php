<?php
require 'koneksi.php';

try {
    // Hapus dari bidang_prokja
    $stmt1 = $pdo->prepare("DELETE FROM bidang_prokja WHERE LOWER(nama) LIKE '%survei%' OR LOWER(nama) LIKE '%lurah%' OR LOWER(nama) LIKE '%berkunjung%'");
    $stmt1->execute();
    $c1 = $stmt1->rowCount();

    // Hapus dari program_kerja
    $stmt2 = $pdo->prepare("DELETE FROM program_kerja WHERE LOWER(judul) LIKE '%survei%' OR LOWER(judul) LIKE '%lurah%' OR LOWER(judul) LIKE '%berkunjung%'");
    $stmt2->execute();
    $c2 = $stmt2->rowCount();

    echo "Berhasil menghapus: $c1 bidang_prokja & $c2 program_kerja";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
