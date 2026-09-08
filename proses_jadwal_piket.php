<?php
require 'proteksi.php';
require 'koneksi.php';

header('Content-Type: application/json');

function jsonFail(string $message): void {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

if ($action !== 'list' && $action !== 'list_bidang') {
    tolakAksesViewer();
}

if ($action === 'list') {
    try {
        $stmt = $pdo->query("SELECT * FROM jadwal_piket ORDER BY FIELD(hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), id ASC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (PDOException $e) {
        jsonFail('Gagal mengambil data jadwal piket: ' . $e->getMessage());
    }
    exit;
}

if ($action === 'save') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonFail('Metode tidak diizinkan.');
    }

    $id         = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $hari       = trim($_POST['hari'] ?? '');
    $shift      = trim($_POST['shift'] ?? '08:00 - 17:00');
    $tugas      = trim($_POST['tugas'] ?? 'Piket Posko');
    $petugas    = is_array($_POST['petugas'] ?? null) ? implode(', ', $_POST['petugas']) : trim($_POST['petugas'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($hari === '') {
        jsonFail('Hari piket wajib dipilih.');
    }
    if ($petugas === '') {
        jsonFail('Nama petugas piket wajib diisi / dipilih.');
    }

    try {
        if ($id) {
            $stmt = $pdo->prepare("
                UPDATE jadwal_piket SET
                    hari = :hari,
                    shift = :shift,
                    tugas = :tugas,
                    petugas = :petugas,
                    keterangan = :keterangan
                WHERE id = :id
            ");
            $stmt->execute([
                ':id'         => $id,
                ':hari'       => $hari,
                ':shift'      => $shift,
                ':tugas'      => $tugas,
                ':petugas'    => $petugas,
                ':keterangan' => $keterangan,
            ]);
            echo json_encode(['success' => true, 'message' => 'Jadwal piket berhasil diperbarui.', 'id' => $id]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO jadwal_piket (hari, shift, tugas, petugas, keterangan)
                VALUES (:hari, :shift, :tugas, :petugas, :keterangan)
            ");
            $stmt->execute([
                ':hari'       => $hari,
                ':shift'      => $shift,
                ':tugas'      => $tugas,
                ':petugas'    => $petugas,
                ':keterangan' => $keterangan,
            ]);
            echo json_encode(['success' => true, 'message' => 'Jadwal piket berhasil ditambahkan.', 'id' => $pdo->lastInsertId()]);
        }
    } catch (PDOException $e) {
        jsonFail('Gagal menyimpan jadwal piket: ' . $e->getMessage());
    }
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        jsonFail('ID tidak valid.');
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM jadwal_piket WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['success' => true, 'message' => 'Jadwal piket berhasil dihapus.']);
    } catch (PDOException $e) {
        jsonFail('Gagal menghapus data: ' . $e->getMessage());
    }
    exit;
}

jsonFail('Aksi tidak dikenali.');
