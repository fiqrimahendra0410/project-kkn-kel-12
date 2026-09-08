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
    try {
        $stmt = $pdo->query("SELECT * FROM jadwal_acara ORDER BY tanggal ASC, waktu ASC, id ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
    } catch (PDOException $e) {
        jsonFail('Gagal mengambil jadwal: ' . $e->getMessage());
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'save') {
        $id         = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
        $tanggal    = trim($_POST['tanggal'] ?? '');
        $waktu      = trim($_POST['waktu'] ?? '');
        $agenda     = trim($_POST['agenda'] ?? '');
        $pic        = trim($_POST['pic'] ?? '') ?: null;
        $keterangan = trim($_POST['keterangan'] ?? '') ?: null;

        if ($tanggal === '' || $waktu === '' || $agenda === '') {
            jsonFail('Parameter tidak lengkap.');
        }

        // Hitung otomatis hari dari tanggal
        $namaHariIndo = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
        ];
        $hari = $namaHariIndo[date('l', strtotime($tanggal))];

        try {
            if ($id) {
                $stmt = $pdo->prepare("
                    UPDATE jadwal_acara SET
                        tanggal = :tanggal,
                        hari = :hari,
                        waktu = :waktu,
                        agenda = :agenda,
                        pic = :pic,
                        keterangan = :keterangan
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':id'         => $id,
                    ':tanggal'    => $tanggal,
                    ':hari'       => $hari,
                    ':waktu'      => $waktu,
                    ':agenda'     => $agenda,
                    ':pic'        => $pic,
                    ':keterangan' => $keterangan,
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO jadwal_acara (tanggal, hari, waktu, agenda, pic, keterangan)
                    VALUES (:tanggal, :hari, :waktu, :agenda, :pic, :keterangan)
                ");
                $stmt->execute([
                    ':tanggal'    => $tanggal,
                    ':hari'       => $hari,
                    ':waktu'      => $waktu,
                    ':agenda'     => $agenda,
                    ':pic'         => $pic,
                    ':keterangan'  => $keterangan,
                ]);
            }
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            jsonFail('Gagal menyimpan jadwal: ' . $e->getMessage());
        }
        exit;
    }

    if ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        if (!$id) {
            jsonFail('ID tidak valid.');
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM jadwal_acara WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            jsonFail('Gagal menghapus jadwal: ' . $e->getMessage());
        }
        exit;
    }
}

jsonFail('Aksi tidak dikenali.');
