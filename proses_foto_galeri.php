<?php
require 'proteksi.php';
require 'koneksi.php';

header('Content-Type: application/json');

function jsonFail(string $message): void {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

tolakAksesViewer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonFail('Metode tidak diizinkan.');
}

if ($action === 'update_caption') {
    $type    = trim($_POST['type'] ?? 'kegiatan'); // 'kegiatan' or 'prokja'
    $id      = (int)($_POST['id'] ?? 0);
    $caption = trim($_POST['caption'] ?? '');

    if ($id <= 0) {
        jsonFail('ID foto tidak valid.');
    }

    try {
        if ($type === 'prokja') {
            $stmt = $pdo->prepare("UPDATE program_kerja_foto SET judul = :cap, deskripsi = :cap WHERE id = :id");
            $stmt->execute([':cap' => $caption, ':id' => $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE kegiatan_foto SET keterangan = :cap WHERE id = :id");
            $stmt->execute([':cap' => $caption, ':id' => $id]);
        }
        echo json_encode(['success' => true, 'message' => 'Deskripsi foto berhasil diperbarui.']);
    } catch (PDOException $e) {
        jsonFail('Gagal memperbarui deskripsi foto: ' . $e->getMessage());
    }
    exit;
}

if ($action === 'delete') {
    $type = trim($_POST['type'] ?? 'kegiatan');
    $id   = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        jsonFail('ID foto tidak valid.');
    }

    try {
        if ($type === 'prokja') {
            $stmtSelect = $pdo->prepare("SELECT path_foto FROM program_kerja_foto WHERE id = :id");
            $stmtSelect->execute([':id' => $id]);
            $path = $stmtSelect->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM program_kerja_foto WHERE id = :id");
            $stmt->execute([':id' => $id]);
        } else {
            $stmtSelect = $pdo->prepare("SELECT path_foto FROM kegiatan_foto WHERE id = :id");
            $stmtSelect->execute([':id' => $id]);
            $path = $stmtSelect->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM kegiatan_foto WHERE id = :id");
            $stmt->execute([':id' => $id]);
        }

        // Hapus file fisik jika ada di disk
        if ($path) {
            $fullPath = __DIR__ . '/' . ltrim($path, '/');
            if (file_exists($fullPath) && is_file($fullPath)) {
                @unlink($fullPath);
            }
        }

        echo json_encode(['success' => true, 'message' => 'Foto berhasil dihapus.']);
    } catch (PDOException $e) {
        jsonFail('Gagal menghapus foto: ' . $e->getMessage());
    }
    exit;
}

jsonFail('Aksi tidak valid.');
