<?php
// proses_lokasi_prokja.php
ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();

require 'koneksi.php';

header('Content-Type: application/json');

function jsonFail(string $message): void {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function jsonSuccess(array $data = []): void {
    if (ob_get_length()) ob_clean();
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

// Auto create & seed tabel lokasi_prokja jika belum ada atau kosong
try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS lokasi_prokja (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                program_kerja_id INTEGER NULL,
                nama_lokasi TEXT NOT NULL,
                kategori_lokasi TEXT DEFAULT 'Program Kerja',
                latitude REAL NOT NULL,
                longitude REAL NOT NULL,
                keterangan TEXT NULL,
                pengunggah_nama TEXT NULL,
                dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `lokasi_prokja` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `program_kerja_id` INT NULL,
                `nama_lokasi` VARCHAR(255) NOT NULL,
                `kategori_lokasi` VARCHAR(100) NULL DEFAULT 'Program Kerja',
                `latitude` DECIMAL(10,8) NOT NULL,
                `longitude` DECIMAL(11,8) NOT NULL,
                `keterangan` TEXT NULL,
                `pengunggah_nama` VARCHAR(150) NULL,
                `dibuat_pada` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    $count = (int)$pdo->query("SELECT COUNT(*) FROM lokasi_prokja")->fetchColumn();
    if ($count === 0) {
        $stmtSeed = $pdo->prepare("
            INSERT INTO lokasi_prokja (nama_lokasi, kategori_lokasi, latitude, longitude, keterangan, pengunggah_nama)
            VALUES (:nama, :kat, :lat, :lng, :ket, :pengunggah)
        ");
        $defaultPoints = [
            [
                'nama' => 'Kantor Kelurahan Pal Lima (Pusat Layanan)',
                'kat' => 'Posko KKN / Sekretariat',
                'lat' => -0.036937,
                'lng' => 109.288572,
                'ket' => 'Pusat Koordinasi dan Layanan Administrasi KKN Kelompok 12',
                'pengunggah' => 'Kelompok 12'
            ],
            [
                'nama' => 'Posko Utama KKN Kelompok 12',
                'kat' => 'Posko KKN / Sekretariat',
                'lat' => -0.0285055,
                'lng' => 109.2861275,
                'ket' => 'Posko Utama Tempat Tinggal & Sekretariat Mahasiswa KKN (Komplek Didis Permai 8 Blok C-D)',
                'pengunggah' => 'Kelompok 12'
            ],
            [
                'nama' => 'Posyandu Balita & Lansia Pal Lima',
                'kat' => 'Posyandu & Kesehatan',
                'lat' => -0.036200,
                'lng' => 109.289100,
                'ket' => 'Lokasi Pelaksanaan Program Kesehatan Gizi Balita & Penimbangan',
                'pengunggah' => 'Tim Kesehatan KKN'
            ],
            [
                'nama' => 'SD Negeri 15 Pontianak Barat',
                'kat' => 'Sekolah & Pendidikan',
                'lat' => -0.038100,
                'lng' => 109.287400,
                'ket' => 'Program Edukasi Digital, Literasi & Kebersihan Lingkungan Sekolah',
                'pengunggah' => 'Tim Pendidikan KKN'
            ],
            [
                'nama' => 'Kawasan Kerja Bakti & Bank Sampah',
                'kat' => 'Kerja Bakti & Lingkungan',
                'lat' => -0.035500,
                'lng' => 109.289800,
                'ket' => 'Aksi Gotong Royong Kebersihan Drainase & Penghijauan Lingkungan',
                'pengunggah' => 'Tim Lingkungan KKN'
            ]
        ];
        foreach ($defaultPoints as $dp) {
            $stmtSeed->execute([
                ':nama' => $dp['nama'],
                ':kat' => $dp['kat'],
                ':lat' => $dp['lat'],
                ':lng' => $dp['lng'],
                ':ket' => $dp['ket'],
                ':pengunggah' => $dp['pengunggah']
            ]);
        }
    }
} catch (Throwable $e) {}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === '') {
    jsonFail('Aksi tidak ditentukan.');
}

if (in_array($action, ['save', 'delete'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['anggota_id']) && empty($_SESSION['akun_id'])) {
        http_response_code(401);
        jsonFail('Akses ditolak! Anda harus login terlebih dahulu sebagai Anggota KKN Kelompok 12.');
    }
}

try {
    // 1. LIST TITIK LOKASI PROKER
    if ($action === 'list') {
        try {
            $stmt = $pdo->query("
                SELECT lp.*, pk.judul AS judul_prokja
                FROM lokasi_prokja lp
                LEFT JOIN program_kerja pk ON lp.program_kerja_id = pk.id
                ORDER BY lp.id DESC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $t) {
            $stmt = $pdo->query("
                SELECT lp.*, '' AS judul_prokja
                FROM lokasi_prokja lp
                ORDER BY lp.id DESC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        jsonSuccess(['data' => $data]);
    }

    // 2. SIMPAN / EDIT TITIK LOKASI
    if ($action === 'save') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonFail('Metode tidak diizinkan.');
        }

        $id               = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : 0;
        $nama_lokasi      = trim($_POST['nama_lokasi'] ?? '');
        $kategori_lokasi  = trim($_POST['kategori_lokasi'] ?? 'Program Kerja');
        $latitude         = trim($_POST['latitude'] ?? '');
        $longitude        = trim($_POST['longitude'] ?? '');
        $keterangan       = trim($_POST['keterangan'] ?? '');
        $program_kerja_id = isset($_POST['program_kerja_id']) && $_POST['program_kerja_id'] !== '' ? (int)$_POST['program_kerja_id'] : null;
        $pengunggah_nama  = $_SESSION['nama'] ?? 'Anggota KKN 12';

        // Auto split jika user memasukkan "lat, lng" sekaligus dalam satu field
        if (strpos($latitude, ',') !== false && ($longitude === '' || $longitude === null)) {
            $parts = explode(',', $latitude);
            $latitude = trim($parts[0]);
            $longitude = trim($parts[1]);
        } elseif (strpos($longitude, ',') !== false && ($latitude === '' || $latitude === null)) {
            $parts = explode(',', $longitude);
            $latitude = trim($parts[0]);
            $longitude = trim($parts[1]);
        }

        if ($nama_lokasi === '') {
            jsonFail('Nama lokasi titik proker wajib diisi.');
        }

        if ($latitude === '' || $longitude === '' || !is_numeric($latitude) || !is_numeric($longitude)) {
            jsonFail('Koordinat Latitude dan Longitude wajib diisi dengan angka rasional (contoh: Lat -0.036937, Lng 109.288572).');
        }

        $lat = (float)$latitude;
        $lng = (float)$longitude;

        if ($id > 0) {
            // UPDATE
            $stmt = $pdo->prepare("
                UPDATE lokasi_prokja SET
                    program_kerja_id = :pkid,
                    nama_lokasi = :nama,
                    kategori_lokasi = :kat,
                    latitude = :lat,
                    longitude = :lng,
                    keterangan = :ket
                WHERE id = :id
            ");
            $stmt->execute([
                ':pkid' => $program_kerja_id,
                ':nama' => $nama_lokasi,
                ':kat'  => $kategori_lokasi,
                ':lat'  => $lat,
                ':lng'  => $lng,
                ':ket'  => $keterangan,
                ':id'   => $id
            ]);
            jsonSuccess(['id' => $id, 'message' => 'Titik lokasi proker berhasil diperbarui.']);
        } else {
            // INSERT
            $stmt = $pdo->prepare("
                INSERT INTO lokasi_prokja (program_kerja_id, nama_lokasi, kategori_lokasi, latitude, longitude, keterangan, pengunggah_nama)
                VALUES (:pkid, :nama, :kat, :lat, :lng, :ket, :pengunggah)
            ");
            $stmt->execute([
                ':pkid'       => $program_kerja_id,
                ':nama'       => $nama_lokasi,
                ':kat'        => $kategori_lokasi,
                ':lat'        => $lat,
                ':lng'        => $lng,
                ':ket'        => $keterangan,
                ':pengunggah' => $pengunggah_nama
            ]);
            jsonSuccess(['id' => $pdo->lastInsertId(), 'message' => 'Titik lokasi proker berhasil ditambahkan ke peta!']);
        }
    }

    // 3. HAPUS TITIK LOKASI
    if ($action === 'delete') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonFail('Metode tidak diizinkan.');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonFail('ID lokasi tidak valid.');
        }

        $stmt = $pdo->prepare("DELETE FROM lokasi_prokja WHERE id = :id");
        $stmt->execute([':id' => $id]);
        jsonSuccess(['message' => 'Titik lokasi proker berhasil dihapus dari peta.']);
    }

    jsonFail('Aksi tidak valid.');

} catch (Throwable $e) {
    jsonFail('Terjadi kesalahan sistem: ' . $e->getMessage());
}
