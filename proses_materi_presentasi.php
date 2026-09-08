<?php
// proses_materi_presentasi.php
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

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === '') {
    jsonFail('Aksi tidak ditentukan.');
}

if (in_array($action, ['upload', 'save', 'delete'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['anggota_id']) && empty($_SESSION['akun_id'])) {
        http_response_code(401);
        jsonFail('Akses ditolak! Anda harus login terlebih dahulu sebagai Anggota KKN Kelompok 12.');
    }
}

// Map foto resmi anggota dari Tim & Struktur Organisasi KKN Kelompok 12
$pemateriFotoMap = [
    "Rizki Tri Saputra"          => "img/rizki tri saputra.jpg",
    "Anggi Rahmawati"             => "img/Anggi Rahmawati.jpg",
    "Virahmanda Abelia Ismaya"    => "img/Virahmanda Abelia Ismaya.jpg",
    "Muhammad Fiqri Mahendra"     => "img/Muhammad Fiqri Mahendra.jpg",
    "Sebastianus Aditia"          => "img/Sebastianus Aditia.jpg",
    "Khairunisa Salsabila"        => "img/Khairunisa Salsabila.jpg",
    "Tiara Fitriani"              => "img/Tiara Fitriani.jpg",
    "Siti Aliyah"                 => "img/Siti Aliyah.jpg",
    "Fathurrahman"                => "img/Fathurrahman.jpg",
    "Halimah Tusa'Diah"           => "img/Halimah Tusa'Diah.jpg",
];

try {
    // 1. LIST MATERI PRESENTASI
    if ($action === 'list') {
        $pk_id = isset($_GET['program_kerja_id']) ? (int)$_GET['program_kerja_id'] : 0;
        
        if ($pk_id > 0) {
            $stmt = $pdo->prepare("
                SELECT mp.*, pk.judul AS judul_prokja
                FROM materi_presentasi mp
                LEFT JOIN program_kerja pk ON mp.program_kerja_id = pk.id
                WHERE mp.program_kerja_id = :pkid
                ORDER BY mp.id DESC
            ");
            $stmt->execute([':pkid' => $pk_id]);
        } else {
            $stmt = $pdo->query("
                SELECT mp.*, pk.judul AS judul_prokja
                FROM materi_presentasi mp
                LEFT JOIN program_kerja pk ON mp.program_kerja_id = pk.id
                ORDER BY mp.id DESC
            ");
        }
        
        $materiList = $stmt->fetchAll();
        jsonSuccess(['data' => $materiList, 'pemateri_options' => array_keys($pemateriFotoMap)]);
    }

    // 2. GET SINGLE MATERI (UNTUK EDIT)
    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM materi_presentasi WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $materi = $stmt->fetch();
        if ($materi) {
            jsonSuccess(['data' => $materi]);
        } else {
            jsonFail('Data materi presentasi tidak ditemukan.');
        }
    }

    // 3. UPLOAD / EDIT MATERI PRESENTASI
    if ($action === 'upload' || $action === 'save') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonFail('Metode tidak diizinkan.');
        }

        $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : 0;
        $judul_materi = trim($_POST['judul_materi'] ?? '');
        $nama_kelompok = trim($_POST['nama_kelompok'] ?? 'KKN Kelompok 12');
        $pemateri_nama = trim($_POST['pemateri_nama'] ?? '');
        $program_kerja_id = isset($_POST['program_kerja_id']) && $_POST['program_kerja_id'] !== '' ? (int)$_POST['program_kerja_id'] : null;

        $tujuan_program  = trim($_POST['tujuan_program'] ?? '');
        $sasaran_program = trim($_POST['sasaran_program'] ?? '');
        $dampak_program  = trim($_POST['dampak_program'] ?? '');

        if ($judul_materi === '') {
            jsonFail('Judul materi presentasi wajib diisi.');
        }
        if ($pemateri_nama === '') {
            jsonFail('Nama pemateri wajib dipilih/diisi.');
        }

        // Tentukan foto pemateri berdasarkan peta foto tim
        $pemateri_foto = $pemateriFotoMap[$pemateri_nama] ?? 'img/foto org.jpg';

        $hasFile = isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] === UPLOAD_ERR_OK;

        if ($id <= 0 && !$hasFile) {
            jsonFail('Silakan pilih file presentasi yang valid.');
        }

        $relPath = null;
        $ext = null;
        $fileSize = null;

        if ($hasFile) {
            $file = $_FILES['file_materi'];
            $maxSize = 25 * 1024 * 1024; // 25MB
            if ($file['size'] > $maxSize) {
                jsonFail('Ukuran file terlalu besar. Maksimal 25MB.');
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'zip', 'rar'];
            if (!in_array($ext, $allowedExts)) {
                jsonFail('Format file tidak didukung. Format yang diizinkan: ' . implode(', ', $allowedExts));
            }

            $uploadDir = __DIR__ . '/uploads/materi/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $filename = 'materi-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            $relPath = 'uploads/materi/' . $filename;
            $fileSize = $file['size'];

            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                jsonFail('Gagal menyimpan file baru di server.');
            }
        }

        if ($id > 0) {
            // EDIT / UPDATE
            $stmtExist = $pdo->prepare("SELECT path_file FROM materi_presentasi WHERE id = :id");
            $stmtExist->execute([':id' => $id]);
            $existingData = $stmtExist->fetch();

            if (!$existingData) {
                jsonFail('Data materi yang akan diedit tidak ditemukan.');
            }

            if ($hasFile) {
                // Hapus file lama jika ada file baru diunggah
                $oldFile = __DIR__ . '/' . $existingData['path_file'];
                if (file_exists($oldFile) && is_file($oldFile)) {
                    @unlink($oldFile);
                }

                $stmtUpdate = $pdo->prepare("
                    UPDATE materi_presentasi 
                    SET program_kerja_id = :pkid, judul_materi = :judul, 
                        tujuan_program = :tujuan, sasaran_program = :sasaran, dampak_program = :dampak,
                        nama_kelompok = :kelompok, pemateri_nama = :pemateri, pemateri_foto = :foto, 
                        path_file = :path_file, tipe_file = :tipe, ukuran_file = :ukuran
                    WHERE id = :id
                ");
                $stmtUpdate->execute([
                    ':pkid'      => $program_kerja_id,
                    ':judul'     => $judul_materi,
                    ':tujuan'    => $tujuan_program,
                    ':sasaran'   => $sasaran_program,
                    ':dampak'    => $dampak_program,
                    ':kelompok'  => $nama_kelompok,
                    ':pemateri'  => $pemateri_nama,
                    ':foto'      => $pemateri_foto,
                    ':path_file' => $relPath,
                    ':tipe'      => $ext,
                    ':ukuran'    => $fileSize,
                    ':id'        => $id
                ]);
            } else {
                // Update tanpa mengganti file
                $stmtUpdate = $pdo->prepare("
                    UPDATE materi_presentasi 
                    SET program_kerja_id = :pkid, judul_materi = :judul, 
                        tujuan_program = :tujuan, sasaran_program = :sasaran, dampak_program = :dampak,
                        nama_kelompok = :kelompok, pemateri_nama = :pemateri, pemateri_foto = :foto
                    WHERE id = :id
                ");
                $stmtUpdate->execute([
                    ':pkid'     => $program_kerja_id,
                    ':judul'    => $judul_materi,
                    ':tujuan'   => $tujuan_program,
                    ':sasaran'  => $sasaran_program,
                    ':dampak'   => $dampak_program,
                    ':kelompok' => $nama_kelompok,
                    ':pemateri' => $pemateri_nama,
                    ':foto'     => $pemateri_foto,
                    ':id'       => $id
                ]);
            }

            jsonSuccess(['id' => $id, 'message' => 'Materi presentasi berhasil diperbarui.']);
        } else {
            // TAMBAH BARU / INSERT
            $stmtInsert = $pdo->prepare("
                INSERT INTO materi_presentasi (program_kerja_id, judul_materi, tujuan_program, sasaran_program, dampak_program, nama_kelompok, pemateri_nama, pemateri_foto, path_file, tipe_file, ukuran_file)
                VALUES (:pkid, :judul, :tujuan, :sasaran, :dampak, :kelompok, :pemateri, :foto, :path_file, :tipe, :ukuran)
            ");
            $stmtInsert->execute([
                ':pkid'        => $program_kerja_id,
                ':judul'       => $judul_materi,
                ':tujuan'      => $tujuan_program,
                ':sasaran'     => $sasaran_program,
                ':dampak'      => $dampak_program,
                ':kelompok'    => $nama_kelompok,
                ':pemateri'    => $pemateri_nama,
                ':foto'        => $pemateri_foto,
                ':path_file'   => $relPath,
                ':tipe'        => $ext,
                ':ukuran'      => $fileSize
            ]);

            jsonSuccess([
                'id' => $pdo->lastInsertId(),
                'message' => 'Materi presentasi berhasil diunggah.'
            ]);
        }
    }

    // 4. HAPUS MATERI PRESENTASI
    if ($action === 'delete') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonFail('Metode tidak diizinkan.');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonFail('ID materi tidak valid.');
        }

        // Ambil info file sebelum dihapus
        $stmt = $pdo->prepare("SELECT path_file FROM materi_presentasi WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if ($row) {
            $filePath = __DIR__ . '/' . $row['path_file'];
            if (file_exists($filePath) && is_file($filePath)) {
                @unlink($filePath);
            }

            $deleteStmt = $pdo->prepare("DELETE FROM materi_presentasi WHERE id = :id");
            $deleteStmt->execute([':id' => $id]);
            jsonSuccess(['message' => 'Materi presentasi berhasil dihapus.']);
        } else {
            jsonFail('Data materi presentasi tidak ditemukan.');
        }
    }

    jsonFail('Aksi tidak valid.');

} catch (Throwable $e) {
    jsonFail('Terjadi kesalahan: ' . $e->getMessage());
}
