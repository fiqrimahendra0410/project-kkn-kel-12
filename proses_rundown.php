<?php
require 'proteksi.php';
require 'koneksi.php';

header('Content-Type: application/json');

function jsonFail(string $message): void {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$action = trim($_GET['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $kegiatanId = isset($_GET['kegiatan_id']) ? (int)$_GET['kegiatan_id'] : 0;
    if (!$kegiatanId) {
        jsonFail('ID kegiatan tidak valid.');
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM kegiatan_rundown WHERE kegiatan_id = :kid ORDER BY waktu ASC, id ASC");
        $stmt->execute([':kid' => $kegiatanId]);
        $rows = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $rows]);
    } catch (PDOException $e) {
        jsonFail('Gagal mengambil rundown: ' . $e->getMessage());
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'save') {
        $id         = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
        $kegiatanId = isset($_POST['kegiatan_id']) ? (int)$_POST['kegiatan_id'] : null;
        $waktu      = trim($_POST['waktu'] ?? '');
        $agenda     = trim($_POST['agenda'] ?? '');
        $pic        = trim($_POST['pic'] ?? '') ?: null;
        $keterangan = trim($_POST['keterangan'] ?? '') ?: null;

        if (!$kegiatanId || $waktu === '' || $agenda === '') {
            jsonFail('Parameter tidak lengkap.');
        }

        try {
            if ($id) {
                $stmt = $pdo->prepare("
                    UPDATE kegiatan_rundown SET
                        waktu = :waktu,
                        agenda = :agenda,
                        pic = :pic,
                        keterangan = :keterangan
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':id'         => $id,
                    ':waktu'      => $waktu,
                    ':agenda'     => $agenda,
                    ':pic'        => $pic,
                    ':keterangan' => $keterangan,
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO kegiatan_rundown (kegiatan_id, waktu, agenda, pic, keterangan)
                    VALUES (:kegiatan_id, :waktu, :agenda, :pic, :keterangan)
                ");
                $stmt->execute([
                    ':kegiatan_id' => $kegiatanId,
                    ':waktu'       => $waktu,
                    ':agenda'      => $agenda,
                    ':pic'         => $pic,
                    ':keterangan'  => $keterangan,
                ]);
            }
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            jsonFail('Gagal menyimpan rundown: ' . $e->getMessage());
        }
        exit;
    }

    if ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        if (!$id) {
            jsonFail('ID tidak valid.');
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM kegiatan_rundown WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            jsonFail('Gagal menghapus rundown: ' . $e->getMessage());
        }
        exit;
    }
}

jsonFail('Aksi tidak dikenali.');
