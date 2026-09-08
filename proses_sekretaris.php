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
    if (in_array($_GET['action'] ?? $_POST['action'] ?? '', ['list_notulensi', 'list_surat_masuk'])) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    jsonFail('Gagal terkoneksi ke database.');
}

if (!$pdo) {
    if (in_array($_GET['action'] ?? $_POST['action'] ?? '', ['list_notulensi', 'list_surat_masuk'])) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    jsonFail('Koneksi database tidak tersedia.');
}

// Auto create standalone tables if not exist (Safe & Independent)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `notulensi_rapat` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `tanggal` DATE NOT NULL,
            `judul_rapat` VARCHAR(255) NOT NULL,
            `pembahasan` TEXT NOT NULL,
            `keputusan` TEXT NULL,
            `peserta` VARCHAR(255) NULL,
            `dibuat_pada` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `surat_masuk` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nomor_surat` VARCHAR(100) NOT NULL,
            `pengirim` VARCHAR(255) NOT NULL,
            `perihal` VARCHAR(255) NOT NULL,
            `tanggal_terima` DATE NOT NULL,
            `keterangan` TEXT NULL,
            `dibuat_pada` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (Throwable $e) {
    // Abaikan jika hambatan izin
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'list_notulensi') {
    try {
        $stmt = $pdo->query("SELECT * FROM notulensi_rapat ORDER BY tanggal DESC, id DESC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Throwable $e) {
        echo json_encode(['success' => true, 'data' => []]);
    }
    exit;
}

if ($action === 'save_notulensi') {
    $id          = (int)($_POST['id'] ?? 0);
    $tanggal     = trim($_POST['tanggal'] ?? date('Y-m-d'));
    $judul_rapat = trim($_POST['judul_rapat'] ?? '');
    $pembahasan  = trim($_POST['pembahasan'] ?? '');
    $keputusan   = trim($_POST['keputusan'] ?? '');
    $peserta     = trim($_POST['peserta'] ?? '');

    if ($judul_rapat === '' || $pembahasan === '') {
        jsonFail('Judul Rapat dan Pembahasan tidak boleh kosong.');
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE notulensi_rapat SET
                    tanggal = :tanggal,
                    judul_rapat = :judul_rapat,
                    pembahasan = :pembahasan,
                    keputusan = :keputusan,
                    peserta = :peserta
                WHERE id = :id
            ");
            $stmt->execute([
                ':tanggal'     => $tanggal,
                ':judul_rapat' => $judul_rapat,
                ':pembahasan'  => $pembahasan,
                ':keputusan'   => $keputusan,
                ':peserta'     => $peserta,
                ':id'          => $id
            ]);
            $resId = $id;
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO notulensi_rapat (tanggal, judul_rapat, pembahasan, keputusan, peserta)
                VALUES (:tanggal, :judul_rapat, :pembahasan, :keputusan, :peserta)
            ");
            $stmt->execute([
                ':tanggal'     => $tanggal,
                ':judul_rapat' => $judul_rapat,
                ':pembahasan'  => $pembahasan,
                ':keputusan'   => $keputusan,
                ':peserta'     => $peserta
            ]);
            $resId = (int)$pdo->lastInsertId();
        }
        echo json_encode(['success' => true, 'id' => $resId, 'message' => 'Notulensi rapat berhasil disimpan.']);
    } catch (Throwable $e) {
        jsonFail('Gagal menyimpan notulensi rapat: ' . $e->getMessage());
    }
    exit;
}

if ($action === 'delete_notulensi') {
    $id = (int)($_POST['id'] ?? 0);
    try {
        $stmt = $pdo->prepare("DELETE FROM notulensi_rapat WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['success' => true, 'message' => 'Notulensi berhasil dihapus.']);
    } catch (Throwable $e) {
        jsonFail('Gagal menghapus notulensi.');
    }
    exit;
}

if ($action === 'list_surat_masuk') {
    try {
        $stmt = $pdo->query("SELECT * FROM surat_masuk ORDER BY tanggal_terima DESC, id DESC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Throwable $e) {
        echo json_encode(['success' => true, 'data' => []]);
    }
    exit;
}

if ($action === 'save_surat_masuk') {
    $id             = (int)($_POST['id'] ?? 0);
    $nomor_surat    = trim($_POST['nomor_surat'] ?? '');
    $pengirim       = trim($_POST['pengirim'] ?? '');
    $perihal        = trim($_POST['perihal'] ?? '');
    $tanggal_terima = trim($_POST['tanggal_terima'] ?? date('Y-m-d'));
    $keterangan     = trim($_POST['keterangan'] ?? '');

    if ($nomor_surat === '' || $perihal === '') {
        jsonFail('Nomor Surat dan Perihal tidak boleh kosong.');
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE surat_masuk SET
                    nomor_surat = :nomor_surat,
                    pengirim = :pengirim,
                    perihal = :perihal,
                    tanggal_terima = :tanggal_terima,
                    keterangan = :keterangan
                WHERE id = :id
            ");
            $stmt->execute([
                ':nomor_surat'    => $nomor_surat,
                ':pengirim'       => $pengirim,
                ':perihal'        => $perihal,
                ':tanggal_terima' => $tanggal_terima,
                ':keterangan'     => $keterangan,
                ':id'             => $id
            ]);
            $resId = $id;
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO surat_masuk (nomor_surat, pengirim, perihal, tanggal_terima, keterangan)
                VALUES (:nomor_surat, :pengirim, :perihal, :tanggal_terima, :keterangan)
            ");
            $stmt->execute([
                ':nomor_surat'    => $nomor_surat,
                ':pengirim'       => $pengirim,
                ':perihal'        => $perihal,
                ':tanggal_terima' => $tanggal_terima,
                ':keterangan'     => $keterangan
            ]);
            $resId = (int)$pdo->lastInsertId();
        }
        echo json_encode(['success' => true, 'id' => $resId, 'message' => 'Surat masuk berhasil dicatat.']);
    } catch (Throwable $e) {
        jsonFail('Gagal mencatat surat masuk: ' . $e->getMessage());
    }
    exit;
}

if ($action === 'delete_surat_masuk') {
    $id = (int)($_POST['id'] ?? 0);
    try {
        $stmt = $pdo->prepare("DELETE FROM surat_masuk WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['success' => true, 'message' => 'Surat masuk berhasil dihapus.']);
    } catch (Throwable $e) {
        jsonFail('Gagal menghapus surat masuk.');
    }
    exit;
}

jsonFail('Aksi tidak valid.');
