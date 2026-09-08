<?php
require_once 'proteksi.php';

header('Content-Type: application/json');

function jsonFail(string $message): void {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// Coba koneksi database
$pdo = null;
try {
    require 'koneksi.php';
} catch (Throwable $e) {
    // Jika koneksi DB gagal, beritahu klien via JSON tanpa fatal error
    if ($_GET['action'] ?? $_POST['action'] ?? '' === 'list') {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    jsonFail('Gagal terkoneksi ke database.');
}

if (!$pdo) {
    if (($_GET['action'] ?? $_POST['action'] ?? '') === 'list') {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    jsonFail('Koneksi database tidak tersedia.');
}

// Auto create table if not exists
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `surat_kkn` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `nomor_surat` VARCHAR(100) NOT NULL,
          `tanggal_surat` VARCHAR(100) NOT NULL,
          `lampiran` VARCHAR(100) DEFAULT '-',
          `hal` VARCHAR(255) NOT NULL,
          `kepada` TEXT NOT NULL,
          `pembuka` TEXT DEFAULT NULL,
          `isi_poin` TEXT NOT NULL,
          `penutup` TEXT DEFAULT NULL,
          `jabatan_kiri` VARCHAR(150) DEFAULT 'Kepala Kelurahan Pal Lima',
          `nama_kiri` VARCHAR(150) DEFAULT '.....................................',
          `nidn_kiri` VARCHAR(100) DEFAULT 'NIP. .....................................',
          `jabatan_kanan` VARCHAR(150) DEFAULT 'Ketua KKN Kelompok 12',
          `nama_kanan` VARCHAR(150) DEFAULT 'Rizki Tri Saputra',
          `nim_kanan` VARCHAR(100) DEFAULT 'NIM. .....................................',
          `dibuat_pada` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (Throwable $e) {
    // Abaikan jika tabel sudah ada atau hambatan ijin
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

if ($action === 'list') {
    try {
        $stmt = $pdo->query("SELECT * FROM surat_kkn ORDER BY id DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Throwable $e) {
        echo json_encode(['success' => true, 'data' => []]);
    }
    exit;
}

if ($action === 'save') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonFail('Metode tidak diizinkan.');
    }

    $id            = (int)($_POST['id'] ?? 0);
    $nomor_surat   = trim($_POST['nomor_surat'] ?? '');
    $tanggal_surat = trim($_POST['tanggal_surat'] ?? '');
    $lampiran      = trim($_POST['lampiran'] ?? '-');
    $hal           = trim($_POST['hal'] ?? '');
    $kepada        = trim($_POST['kepada'] ?? '');
    $pembuka       = trim($_POST['pembuka'] ?? '');
    $isi_poin      = trim($_POST['isi_poin'] ?? '');
    $penutup       = trim($_POST['penutup'] ?? '');
    $jabatan_kiri  = trim($_POST['jabatan_kiri'] ?? 'Kepala Kelurahan Pal Lima');
    $nama_kiri     = trim($_POST['nama_kiri'] ?? '.....................................');
    $nidn_kiri     = trim($_POST['nidn_kiri'] ?? 'NIP. .....................................');
    $jabatan_kanan = trim($_POST['jabatan_kanan'] ?? 'Ketua KKN Kelompok 12');
    $nama_kanan    = trim($_POST['nama_kanan'] ?? 'Rizki Tri Saputra');
    $nim_kanan     = trim($_POST['nim_kanan'] ?? 'NIM. .....................................');

    if ($nomor_surat === '' || $hal === '') {
        jsonFail('Nomor Surat dan Perihal / Hal tidak boleh kosong.');
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE surat_kkn SET 
                    nomor_surat = :nomor_surat,
                    tanggal_surat = :tanggal_surat,
                    lampiran = :lampiran,
                    hal = :hal,
                    kepada = :kepada,
                    pembuka = :pembuka,
                    isi_poin = :isi_poin,
                    penutup = :penutup,
                    jabatan_kiri = :jabatan_kiri,
                    nama_kiri = :nama_kiri,
                    nidn_kiri = :nidn_kiri,
                    jabatan_kanan = :jabatan_kanan,
                    nama_kanan = :nama_kanan,
                    nim_kanan = :nim_kanan
                WHERE id = :id
            ");
            $stmt->execute([
                ':nomor_surat'   => $nomor_surat,
                ':tanggal_surat' => $tanggal_surat,
                ':lampiran'      => $lampiran,
                ':hal'           => $hal,
                ':kepada'        => $kepada,
                ':pembuka'       => $pembuka,
                ':isi_poin'      => $isi_poin,
                ':penutup'       => $penutup,
                ':jabatan_kiri'  => $jabatan_kiri,
                ':nama_kiri'     => $nama_kiri,
                ':nidn_kiri'     => $nidn_kiri,
                ':jabatan_kanan' => $jabatan_kanan,
                ':nama_kanan'    => $nama_kanan,
                ':nim_kanan'     => $nim_kanan,
                ':id'            => $id
            ]);
            $insertedId = $id;
        } else {
            // Jika ID = 0 (Surat Baru), buat record baru selalu di database
            $stmt = $pdo->prepare("
                INSERT INTO surat_kkn (
                    nomor_surat, tanggal_surat, lampiran, hal, kepada, pembuka, isi_poin, penutup,
                    jabatan_kiri, nama_kiri, nidn_kiri, jabatan_kanan, nama_kanan, nim_kanan
                ) VALUES (
                    :nomor_surat, :tanggal_surat, :lampiran, :hal, :kepada, :pembuka, :isi_poin, :penutup,
                    :jabatan_kiri, :nama_kiri, :nidn_kiri, :jabatan_kanan, :nama_kanan, :nim_kanan
                )
            ");
            $stmt->execute([
                ':nomor_surat'   => $nomor_surat,
                ':tanggal_surat' => $tanggal_surat,
                ':lampiran'      => $lampiran,
                ':hal'           => $hal,
                ':kepada'        => $kepada,
                ':pembuka'       => $pembuka,
                ':isi_poin'      => $isi_poin,
                ':penutup'       => $penutup,
                ':jabatan_kiri'  => $jabatan_kiri,
                ':nama_kiri'     => $nama_kiri,
                ':nidn_kiri'     => $nidn_kiri,
                ':jabatan_kanan' => $jabatan_kanan,
                ':nama_kanan'    => $nama_kanan,
                ':nim_kanan'     => $nim_kanan
            ]);
            $insertedId = (int)$pdo->lastInsertId();
        }

        echo json_encode(['success' => true, 'id' => $insertedId, 'message' => 'Surat berhasil disimpan.']);
    } catch (Throwable $e) {
        jsonFail('Gagal menyimpan surat: ' . $e->getMessage());
    }
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        jsonFail('ID surat tidak valid.');
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM surat_kkn WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['success' => true, 'message' => 'Surat berhasil dihapus dari arsip.']);
    } catch (Throwable $e) {
        jsonFail('Gagal menghapus surat: ' . $e->getMessage());
    }
    exit;
}

jsonFail('Aksi tidak dikenali.');
