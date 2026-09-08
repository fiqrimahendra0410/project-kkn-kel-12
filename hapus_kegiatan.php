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

$id = isset($_POST['id']) ? (int)$_POST['id'] : null;

if (!$id) {
    jsonFail('ID kegiatan tidak valid.');
}

try {
    $pdo->beginTransaction();

    // 1. Cek apakah ini ID kegiatan biasa di tabel `kegiatan`
    $stmtCekKeg = $pdo->prepare("SELECT id FROM kegiatan WHERE id = :id");
    $stmtCekKeg->execute([':id' => $id]);
    $kegiatanExist = $stmtCekKeg->fetchColumn();

    if ($kegiatanExist) {
        // A. Hapus foto-foto dari file system
        $fotoStmt = $pdo->prepare("SELECT path_foto FROM kegiatan_foto WHERE kegiatan_id = :id");
        $fotoStmt->execute([':id' => $id]);
        $fotos = $fotoStmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($fotos as $path) {
            if ($path) {
                $fullPath = __DIR__ . '/' . ltrim($path, '/\\');
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                } elseif (file_exists($path)) {
                    @unlink($path);
                }
            }
        }

        // B. Hapus foto dari database
        $delFoto = $pdo->prepare("DELETE FROM kegiatan_foto WHERE kegiatan_id = :id");
        $delFoto->execute([':id' => $id]);

        // C. Hapus absensi yang berasosiasi
        $delAbsen = $pdo->prepare("DELETE FROM absensi WHERE kegiatan_id = :id");
        $delAbsen->execute([':id' => $id]);

        // D. Hapus rundown yang berasosiasi
        $delRundown = $pdo->prepare("DELETE FROM kegiatan_rundown WHERE kegiatan_id = :id");
        $delRundown->execute([':id' => $id]);

        // E. Hapus kegiatan utama
        $delKegiatan = $pdo->prepare("DELETE FROM kegiatan WHERE id = :id");
        $delKegiatan->execute([':id' => $id]);
    } else {
        // 2. Jika tidak ada di tabel `kegiatan`, cek apakah ini item program kerja (disinkronisasi dengan ID >= 1000)
        $prokjaId = ($id >= 1000) ? ($id - 1000) : $id;

        $stmtCekPk = $pdo->prepare("SELECT id FROM program_kerja WHERE id = :id");
        $stmtCekPk->execute([':id' => $prokjaId]);
        $prokjaExist = $stmtCekPk->fetchColumn();

        if ($prokjaExist) {
            // A. Hapus foto-foto program kerja dari disk
            $fotoStmt = $pdo->prepare("SELECT path_foto FROM program_kerja_foto WHERE program_kerja_id = :id");
            $fotoStmt->execute([':id' => $prokjaId]);
            $fotos = $fotoStmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($fotos as $path) {
                if ($path) {
                    $fullPath = __DIR__ . '/' . ltrim($path, '/\\');
                    if (file_exists($fullPath)) {
                        @unlink($fullPath);
                    } elseif (file_exists($path)) {
                        @unlink($path);
                    }
                }
            }

            // B. Hapus foto dari database program_kerja_foto
            $delPkFoto = $pdo->prepare("DELETE FROM program_kerja_foto WHERE program_kerja_id = :id");
            $delPkFoto->execute([':id' => $prokjaId]);

            // C. Hapus program kerja utama
            $delPk = $pdo->prepare("DELETE FROM program_kerja WHERE id = :id");
            $delPk->execute([':id' => $prokjaId]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonFail('Gagal menghapus kegiatan: ' . $e->getMessage());
}


