<?php
require 'proteksi.php';
require 'koneksi.php';

header('Content-Type: application/json');

function jsonFail(string $message): void {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

if ($action === 'list') {
    try {
        $stmt = $pdo->query("SELECT id, nama, jabatan, foto, kata_kata FROM anggota WHERE kata_kata IS NOT NULL AND kata_kata != '' ORDER BY id ASC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (PDOException $e) {
        jsonFail('Gagal mengambil data kata-kata: ' . $e->getMessage());
    }
    exit;
}

if ($action === 'save') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonFail('Metode tidak diizinkan.');
    }

    // Hak edit kata-kata khusus hanya untuk akun anggota yang sedang login
    $anggota_id = (int)($_POST['anggota_id'] ?? ($_SESSION['anggota_id'] ?? 0));
    $kata_kata  = trim($_POST['kata_kata'] ?? '');

    if ($anggota_id <= 0) {
        $dplRow = $pdo->query("SELECT id FROM anggota WHERE nama LIKE '%Kania%' OR jabatan LIKE '%Dosen Pembimbing%' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $anggota_id = (int)($dplRow['id'] ?? 0);
    }

    if ($anggota_id <= 0) {
        jsonFail('Pilih anggota terlebih dahulu.');
    }
    if ($kata_kata === '') {
        jsonFail('Isi kata-kata / pesan tidak boleh kosong.');
    }

    try {
        $stmt = $pdo->prepare("UPDATE anggota SET kata_kata = :k WHERE id = :id");
        $stmt->execute([':k' => $kata_kata, ':id' => $anggota_id]);
        echo json_encode(['success' => true, 'message' => 'Kata-kata / Pesan DPL berhasil diperbarui!']);
    } catch (PDOException $e) {
        jsonFail('Gagal memperbarui pesan: ' . $e->getMessage());
    }
    exit;
}

if ($action === 'reset' || $action === 'reset_dpl') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonFail('Metode tidak diizinkan.');
    }

    try {
        $dplRow = $pdo->query("SELECT id FROM anggota WHERE nama LIKE '%Kania%' OR jabatan LIKE '%Dosen Pembimbing%' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $dplId = (int)($dplRow['id'] ?? 0);

        // Verifikasi bahwa user yang login adalah Ibu Kania
        $sessAnggotaId = (int)($_SESSION['anggota_id'] ?? 0);
        $sessNama      = $_SESSION['nama'] ?? '';
        $isKania = ($sessAnggotaId === $dplId || stripos($sessNama, 'Kania') !== false);

        if (!$isKania) {
            jsonFail('Fitur reset kata-kata ini khusus untuk login Ibu Kania.');
        }

        $stmt = $pdo->prepare("UPDATE anggota SET kata_kata = '' WHERE nama LIKE '%Kania%' OR jabatan LIKE '%Dosen Pembimbing%' OR id = :id");
        $stmt->execute([':id' => $dplId]);

        echo json_encode(['success' => true, 'message' => 'Kata-kata Ibu Kania berhasil direset! Pop-up kata-kata akan muncul kembali saat login.']);
    } catch (PDOException $e) {
        jsonFail('Gagal mereset kata-kata: ' . $e->getMessage());
    }
    exit;
}

jsonFail('Aksi tidak dikenali.');
