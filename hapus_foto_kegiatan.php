<?php
require 'proteksi.php';
require 'koneksi.php';

tolakAksesViewer();

header('Content-Type: application/json');

function jsonFail(string $message): void {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonFail('Metode tidak diizinkan.');
}

$kegiatanId = isset($_POST['kegiatan_id']) ? (int)$_POST['kegiatan_id'] : null;
$pathFoto   = trim($_POST['path_foto'] ?? '');

if (!$kegiatanId || $pathFoto === '') {
    jsonFail('Parameter tidak lengkap.');
}

try {
    // 1. Cek keberadaan foto di database
    $stmt = $pdo->prepare("SELECT id FROM kegiatan_foto WHERE kegiatan_id = :kegiatan_id AND path_foto = :path_foto LIMIT 1");
    $stmt->execute([
        ':kegiatan_id' => $kegiatanId,
        ':path_foto'   => $pathFoto
    ]);
    $fotoId = $stmt->fetchColumn();

    if (!$fotoId) {
        jsonFail('Foto tidak ditemukan di database.');
    }

    // 2. Hapus file fisik dari disk
    $fullPath = __DIR__ . '/' . ltrim($pathFoto, '/\\');
    if (file_exists($fullPath)) {
        @unlink($fullPath);
    } elseif (file_exists($pathFoto)) {
        @unlink($pathFoto);
    }

    // 3. Hapus record dari database
    $del = $pdo->prepare("DELETE FROM kegiatan_foto WHERE id = :id");
    $del->execute([':id' => $fotoId]);

    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    jsonFail('Gagal menghapus foto: ' . $e->getMessage());
}

