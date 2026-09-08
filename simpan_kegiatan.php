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

$id         = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
$judul      = trim($_POST['judul'] ?? '');
$bidangNama = trim($_POST['bidang'] ?? '');
$tanggal    = trim($_POST['tanggal'] ?? '');
$jamMulai   = trim($_POST['jam_mulai'] ?? '') ?: null;
$jamSelesai = trim($_POST['jam_selesai'] ?? '') ?: null;
$lokasi     = trim($_POST['lokasi'] ?? '') ?: null;
$sasaran    = trim($_POST['sasaran'] ?? '') ?: null;
$deskripsi  = trim($_POST['deskripsi'] ?? '') ?: null;
$hasil      = trim($_POST['hasil'] ?? '') ?: null;

// Auto-correct 00:00 (12:00 AM) to 12:00 (12:00 PM / siang)
if ($jamMulai !== null && substr($jamMulai, 0, 5) === '00:00') {
    $jamMulai = '12:00';
}
if ($jamSelesai !== null && substr($jamSelesai, 0, 5) === '00:00') {
    $jamSelesai = '12:00';
}

if ($judul === '') {
    jsonFail('Judul kegiatan wajib diisi.');
}
if ($tanggal === '' || !strtotime($tanggal)) {
    jsonFail('Tanggal kegiatan wajib diisi dengan format yang benar.');
}

// ===== Nama hari dalam Bahasa Indonesia, dihitung otomatis dari tanggal =====
$namaHariIndo = [
    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
];
$hari = $namaHariIndo[date('l', strtotime($tanggal))];

try {
    // ===== Bidang: cari yang sudah ada, atau buat baru otomatis kalau belum ada =====
    $bidangId = null;

    if ($bidangNama !== '') {
        $cek = $pdo->prepare("SELECT id FROM bidang_prokja WHERE nama = :nama LIMIT 1");
        $cek->execute([':nama' => $bidangNama]);
        $bidangId = $cek->fetchColumn();

        if (!$bidangId) {
            $kode = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $bidangNama));
            $kode = trim($kode, '-');
            if ($kode === '') {
                $kode = 'bidang-' . time();
            }

            // Pastikan kode unik (kalau sudah ada kode yang sama, tambahkan suffix)
            $cekKode = $pdo->prepare("SELECT COUNT(*) FROM bidang_prokja WHERE kode = :kode");
            $kodeAsli = $kode;
            $suffix = 1;
            while (true) {
                $cekKode->execute([':kode' => $kode]);
                if ((int) $cekKode->fetchColumn() === 0) break;
                $kode = $kodeAsli . '-' . (++$suffix);
            }

            $insertBidang = $pdo->prepare("INSERT INTO bidang_prokja (kode, nama) VALUES (:kode, :nama)");
            $insertBidang->execute([':kode' => $kode, ':nama' => $bidangNama]);
            $bidangId = $pdo->lastInsertId();
        }
    }

    // ===== Simpan kegiatan =====
    if ($id) {
        $stmtCekKeg = $pdo->prepare("SELECT id FROM kegiatan WHERE id = :id");
        $stmtCekKeg->execute([':id' => $id]);
        $kegExist = $stmtCekKeg->fetchColumn();

        if ($kegExist) {
            $stmt = $pdo->prepare("
                UPDATE kegiatan SET
                    judul = :judul,
                    bidang_id = :bidang_id,
                    tanggal = :tanggal,
                    hari = :hari,
                    jam_mulai = :jam_mulai,
                    jam_selesai = :jam_selesai,
                    lokasi = :lokasi,
                    sasaran = :sasaran,
                    deskripsi = :deskripsi,
                    hasil = :hasil
                WHERE id = :id
            ");
            $stmt->execute([
                ':id'          => $id,
                ':judul'       => $judul,
                ':bidang_id'   => $bidangId,
                ':tanggal'     => $tanggal,
                ':hari'        => $hari,
                ':jam_mulai'   => $jamMulai,
                ':jam_selesai' => $jamSelesai,
                ':lokasi'      => $lokasi,
                ':sasaran'     => $sasaran,
                ':deskripsi'   => $deskripsi,
                ':hasil'       => $hasil,
            ]);
            echo json_encode(['success' => true, 'id' => $id]);
        } else {
            $prokjaId = ($id >= 1000) ? ($id - 1000) : $id;
            $stmtPk = $pdo->prepare("
                UPDATE program_kerja SET
                    judul = :judul,
                    bidang_id = :bidang_id,
                    deskripsi = :deskripsi
                WHERE id = :id
            ");
            $stmtPk->execute([
                ':id'        => $prokjaId,
                ':judul'     => $judul,
                ':bidang_id' => $bidangId,
                ':deskripsi' => $deskripsi,
            ]);
            echo json_encode(['success' => true, 'id' => $id]);
        }
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO kegiatan
                (judul, bidang_id, tanggal, hari, jam_mulai, jam_selesai, lokasi, sasaran, deskripsi, hasil)
            VALUES
                (:judul, :bidang_id, :tanggal, :hari, :jam_mulai, :jam_selesai, :lokasi, :sasaran, :deskripsi, :hasil)
        ");
        $stmt->execute([
            ':judul'       => $judul,
            ':bidang_id'   => $bidangId,
            ':tanggal'     => $tanggal,
            ':hari'        => $hari,
            ':jam_mulai'   => $jamMulai,
            ':jam_selesai' => $jamSelesai,
            ':lokasi'      => $lokasi,
            ':sasaran'     => $sasaran,
            ':deskripsi'   => $deskripsi,
            ':hasil'       => $hasil,
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    }

} catch (PDOException $e) {
    jsonFail('Gagal menyimpan ke database: ' . $e->getMessage());
}