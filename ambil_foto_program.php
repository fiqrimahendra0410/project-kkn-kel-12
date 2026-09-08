<?php
require 'koneksi.php';

header('Content-Type: application/json');

$program_id = isset($_GET['program_kerja_id']) ? (int)$_GET['program_kerja_id'] : 0;

if ($program_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID program tidak valid.']);
    exit;
}

try {
    // Ambil detail program
    $stmtProgram = $pdo->prepare("SELECT judul, deskripsi FROM program_kerja WHERE id = :id AND status = 'terbit'");
    $stmtProgram->execute([':id' => $program_id]);
    $program = $stmtProgram->fetch();

    if (!$program) {
        echo json_encode(['success' => false, 'message' => 'Program kerja tidak ditemukan atau belum diterbitkan.']);
        exit;
    }

    // Ambil foto program
    $stmtFotos = $pdo->prepare("SELECT id, path_foto, judul, deskripsi, is_cover FROM program_kerja_foto WHERE program_kerja_id = :id ORDER BY is_cover DESC, id ASC");
    $stmtFotos->execute([':id' => $program_id]);
    $fotos = $stmtFotos->fetchAll();

    echo json_encode([
        'success' => true,
        'program' => $program,
        'fotos' => $fotos
    ]);
    exit;

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
