<?php
require_once 'proteksi.php';

header('Content-Type: application/json');

function jsonFail(string $message): void {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$pdo = null;
try {
    require 'koneksi.php';
} catch (Throwable $e) {
    if (($_GET['action'] ?? $_POST['action'] ?? '') === 'list') {
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

// Auto create standalone table if not exists (Safe & Independent)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `kontak_humas` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nama` VARCHAR(150) NOT NULL,
            `jabatan` VARCHAR(150) NOT NULL,
            `no_hp` VARCHAR(50) NOT NULL,
            `wilayah` VARCHAR(150) NULL,
            `keterangan` TEXT NULL,
            `dibuat_pada` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (Throwable $e) {
    // Ignore permissions issue
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'list') {
    try {
        $stmt = $pdo->query("SELECT * FROM kontak_humas ORDER BY id ASC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Populate preset contacts if table is empty
        if (empty($data)) {
            $presets = [
                ['nama' => 'Bapak Kepala Kelurahan', 'jabatan' => 'Kepala Kelurahan Pal Lima', 'no_hp' => '081234567890', 'wilayah' => 'Pal Lima', 'keterangan' => 'Lurah Pal Lima, Pontianak Barat'],
                ['nama' => 'Bhabinkamtibmas Pal Lima', 'jabatan' => 'Bhabinkamtibmas (Polsek)', 'no_hp' => '081298765432', 'wilayah' => 'Pal Lima', 'keterangan' => 'Kontak Kamtibmas Kepolisian'],
                ['nama' => 'Babinsa Pal Lima', 'jabatan' => 'Babinsa (Koramil)', 'no_hp' => '081345678901', 'wilayah' => 'Pal Lima', 'keterangan' => 'Kontak Pertahanan/TNI Koramil'],
                ['nama' => 'Kepala Puskesmas Pal Lima', 'jabatan' => 'Kepala Puskesmas / Nakes', 'no_hp' => '081567890123', 'wilayah' => 'Pal Lima', 'keterangan' => 'Kontak Layanan Kesehatan Posko'],
                ['nama' => 'Ketua RW 01', 'jabatan' => 'Ketua RW / Tokoh Masyarakat', 'no_hp' => '081678901234', 'wilayah' => 'RW 01 Pal Lima', 'keterangan' => 'Ketua RW Sub-Wilayah Posko'],
                ['nama' => 'Ketua Karang Taruna', 'jabatan' => 'Ketua Pemuda / Karang Taruna', 'no_hp' => '081789012345', 'wilayah' => 'Pal Lima', 'keterangan' => 'Koordinator Pemuda Setempat'],
                ['nama' => 'Ibu Kania Khairunnisa, M.Psi.', 'jabatan' => 'Dosen Pembimbing Lapangan (DPL)', 'no_hp' => '081890123456', 'wilayah' => 'UMP', 'keterangan' => 'DPL Kelompok 12 UMP']
            ];

            foreach ($presets as $p) {
                $ins = $pdo->prepare("INSERT INTO kontak_humas (nama, jabatan, no_hp, wilayah, keterangan) VALUES (:nama, :jabatan, :no_hp, :wilayah, :keterangan)");
                $ins->execute($p);
            }
            $stmt = $pdo->query("SELECT * FROM kontak_humas ORDER BY id ASC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Throwable $e) {
        echo json_encode(['success' => true, 'data' => []]);
    }
    exit;
}

if ($action === 'save') {
    $id         = (int)($_POST['id'] ?? 0);
    $nama       = trim($_POST['nama'] ?? '');
    $jabatan    = trim($_POST['jabatan'] ?? '');
    $no_hp      = trim($_POST['no_hp'] ?? '');
    $wilayah    = trim($_POST['wilayah'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($nama === '' || $jabatan === '' || $no_hp === '') {
        jsonFail('Nama, Jabatan, dan Nomor HP / WhatsApp wajib diisi.');
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE kontak_humas SET
                    nama = :nama,
                    jabatan = :jabatan,
                    no_hp = :no_hp,
                    wilayah = :wilayah,
                    keterangan = :keterangan
                WHERE id = :id
            ");
            $stmt->execute([
                ':nama'       => $nama,
                ':jabatan'    => $jabatan,
                ':no_hp'      => $no_hp,
                ':wilayah'    => $wilayah,
                ':keterangan' => $keterangan,
                ':id'         => $id
            ]);
            $resId = $id;
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO kontak_humas (nama, jabatan, no_hp, wilayah, keterangan)
                VALUES (:nama, :jabatan, :no_hp, :wilayah, :keterangan)
            ");
            $stmt->execute([
                ':nama'       => $nama,
                ':jabatan'    => $jabatan,
                ':no_hp'      => $no_hp,
                ':wilayah'    => $wilayah,
                ':keterangan' => $keterangan
            ]);
            $resId = (int)$pdo->lastInsertId();
        }
        echo json_encode(['success' => true, 'id' => $resId, 'message' => 'Kontak humas berhasil disimpan.']);
    } catch (Throwable $e) {
        jsonFail('Gagal menyimpan kontak humas: ' . $e->getMessage());
    }
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    try {
        $stmt = $pdo->prepare("DELETE FROM kontak_humas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['success' => true, 'message' => 'Kontak humas berhasil dihapus.']);
    } catch (Throwable $e) {
        jsonFail('Gagal menghapus kontak.');
    }
    exit;
}

jsonFail('Aksi tidak valid.');
