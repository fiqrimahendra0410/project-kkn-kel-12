<?php
require 'proteksi.php';
require 'koneksi.php';

header('Content-Type: application/json');

function jsonFail(string $message): void {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

if ($action !== 'list') {
    tolakAksesViewer();
}

if ($action === 'list') {
    try {
        $stmt = $pdo->query("SELECT * FROM informasi_posko ORDER BY id ASC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (PDOException $e) {
        jsonFail('Gagal mengambil data informasi posko: ' . $e->getMessage());
    }
    exit;
}

if ($action === 'save') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonFail('Metode tidak diizinkan.');
    }

    $id               = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $nama_barang      = trim($_POST['nama_barang'] ?? '');
    $kategori         = trim($_POST['kategori'] ?? 'Perlengkapan Posko');
    $penanggung_jawab = is_array($_POST['penanggung_jawab'] ?? null) ? implode(', ', $_POST['penanggung_jawab']) : trim($_POST['penanggung_jawab'] ?? '');
    $keterangan       = trim($_POST['keterangan'] ?? '');

    if ($nama_barang === '') {
        jsonFail('Nama barang / perlengkapan wajib diisi.');
    }
    if ($penanggung_jawab === '') {
        jsonFail('Penanggung jawab wajib diisi.');
    }

    try {
        if ($id) {
            $stmt = $pdo->prepare("
                UPDATE informasi_posko SET
                    nama_barang = :nama,
                    kategori = :kat,
                    penanggung_jawab = :pj,
                    keterangan = :ket
                WHERE id = :id
            ");
            $stmt->execute([
                ':id'   => $id,
                ':nama' => $nama_barang,
                ':kat'  => $kategori,
                ':pj'   => $penanggung_jawab,
                ':ket'  => $keterangan,
            ]);
            echo json_encode(['success' => true, 'message' => 'Informasi berhasil diperbarui.', 'id' => $id]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO informasi_posko (nama_barang, kategori, penanggung_jawab, keterangan)
                VALUES (:nama, :kat, :pj, :ket)
            ");
            $stmt->execute([
                ':nama' => $nama_barang,
                ':kat'  => $kategori,
                ':pj'   => $penanggung_jawab,
                ':ket'  => $keterangan,
            ]);
            echo json_encode(['success' => true, 'message' => 'Informasi baru berhasil ditambahkan.', 'id' => $pdo->lastInsertId()]);
        }
    } catch (PDOException $e) {
        jsonFail('Gagal menyimpan data: ' . $e->getMessage());
    }
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        jsonFail('ID tidak valid.');
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM informasi_posko WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['success' => true, 'message' => 'Data berhasil dihapus.']);
    } catch (PDOException $e) {
        jsonFail('Gagal menghapus data: ' . $e->getMessage());
    }
    exit;
}

jsonFail('Aksi tidak dikenali.');
