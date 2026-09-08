<?php
require 'proteksi.php';
require 'koneksi.php';

header('Content-Type: application/json');

function jsonFail(string $message): void {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

try {
    $pdo->exec("ALTER TABLE program_kerja ADD COLUMN link VARCHAR(255) NULL");
} catch (Throwable $e) {}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === '') {
    jsonFail('Aksi tidak ditentukan.');
}

try {
    if ($action === 'list') {
        // Ambil daftar bidang untuk reference
        $bidangRows = $pdo->query("SELECT id, nama FROM bidang_prokja ORDER BY id")->fetchAll();
        
        // Ambil program kerja
        $programs = $pdo->query("
            SELECT pk.*, bp.nama AS bidang_nama, bp.kode AS bidang_kode
            FROM program_kerja pk
            JOIN bidang_prokja bp ON pk.bidang_id = bp.id
            ORDER BY pk.urutan ASC, pk.id DESC
        ")->fetchAll();

        // Ambil foto per program kerja
        $fotoStmt = $pdo->prepare("SELECT * FROM program_kerja_foto WHERE program_kerja_id = :pkid ORDER BY is_cover DESC, id ASC");
        
        $result = [];
        foreach ($programs as $p) {
            $fotoStmt->execute([':pkid' => $p['id']]);
            $fotos = $fotoStmt->fetchAll();
            
            $p['fotos'] = $fotos;
            $result[] = $p;
        }

        echo json_encode(['success' => true, 'data' => $result, 'bidang' => $bidangRows]);
        exit;
    }

    if ($action === 'save') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonFail('Metode tidak diizinkan.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $raw_bidang_id = $_POST['bidang_id'] ?? '';
        $bidang_custom = trim($_POST['bidang_custom'] ?? '');
        $judul = trim($_POST['judul'] ?? '');
        $periode = trim($_POST['periode'] ?? '') ?: 'Juli – Agustus 2026';
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $link = trim($_POST['link'] ?? '') ?: 'https://kkn12.ct.ws/?i=1';
        $status = trim($_POST['status'] ?? 'terbit');

        $bidang_id = (int)$raw_bidang_id;

        // Jika user memilih custom atau mengisikan nama bidang baru sendiri
        if (($raw_bidang_id === 'custom' || $bidang_id <= 0) && !empty($bidang_custom)) {
            $kode = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $bidang_custom));
            if (empty($kode)) $kode = 'bidang_' . time();

            // Cek apakah bidang sudah ada di database (case-insensitive)
            $stmtCek = $pdo->prepare("SELECT id FROM bidang_prokja WHERE LOWER(nama) = LOWER(:nama) LIMIT 1");
            $stmtCek->execute([':nama' => $bidang_custom]);
            $existBidang = $stmtCek->fetch();

            if ($existBidang) {
                $bidang_id = (int)$existBidang['id'];
            } else {
                $stmtNew = $pdo->prepare("INSERT INTO bidang_prokja (kode, nama) VALUES (:kode, :nama)");
                $stmtNew->execute([':kode' => $kode, ':nama' => $bidang_custom]);
                $bidang_id = (int)$pdo->lastInsertId();
            }
        }

        if ($bidang_id <= 0) {
            jsonFail('Bidang program kerja wajib dipilih atau diisi.');
        }
        if ($judul === '') {
            jsonFail('Judul program kerja wajib diisi.');
        }

        if ($id > 0) {
            // Edit
            $stmt = $pdo->prepare("
                UPDATE program_kerja 
                SET bidang_id = :bidang_id, judul = :judul, periode = :periode, deskripsi = :deskripsi, link = :link, status = :status
                WHERE id = :id
            ");
            $stmt->execute([
                ':bidang_id' => $bidang_id,
                ':judul' => $judul,
                ':periode' => $periode,
                ':deskripsi' => $deskripsi,
                ':link' => $link,
                ':status' => $status,
                ':id' => $id
            ]);
        } else {
            // Tambah Baru
            $stmt = $pdo->prepare("
                INSERT INTO program_kerja (bidang_id, judul, periode, deskripsi, link, status)
                VALUES (:bidang_id, :judul, :periode, :deskripsi, :link, :status)
            ");
            $stmt->execute([
                ':bidang_id' => $bidang_id,
                ':judul' => $judul,
                ':periode' => $periode,
                ':deskripsi' => $deskripsi,
                ':link' => $link,
                ':status' => $status
            ]);
            $id = (int)$pdo->lastInsertId();
        }

        echo json_encode(['success' => true, 'id' => $id, 'message' => 'Program kerja berhasil disimpan.']);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonFail('ID tidak valid.');
        }

        // Ambil semua foto terkait untuk dihapus file fisiknya
        $stmtFoto = $pdo->prepare("SELECT path_foto FROM program_kerja_foto WHERE program_kerja_id = :id");
        $stmtFoto->execute([':id' => $id]);
        $fotos = $stmtFoto->fetchAll(PDO::FETCH_COLUMN);

        foreach ($fotos as $f) {
            $fullPath = __DIR__ . '/' . $f;
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM program_kerja WHERE id = :id");
        $stmt->execute([':id' => $id]);

        echo json_encode(['success' => true, 'message' => 'Program kerja berhasil dihapus beserta seluruh fotonya.']);
        exit;
    }

    if ($action === 'list_bidang') {
        $rows = $pdo->query("SELECT * FROM bidang_prokja ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    }

    if ($action === 'save_bidang') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonFail('Metode tidak diizinkan.');
        }

        $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
        $nama = trim($_POST['nama'] ?? '');

        if ($nama === '') {
            jsonFail('Nama bidang wajib diisi.');
        }

        // Generate kode otomatis dari nama bidang
        $kode = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $nama));
        $kode = trim($kode, '-');
        if ($kode === '') {
            $kode = 'bidang-' . time();
        }

        try {
            if ($id) {
                // Update bidang
                $stmt = $pdo->prepare("UPDATE bidang_prokja SET nama = :nama, kode = :kode WHERE id = :id");
                $stmt->execute([
                    ':nama' => $nama,
                    ':kode' => $kode,
                    ':id'   => $id
                ]);
            } else {
                // Insert new bidang
                // Check if already exists
                $cek = $pdo->prepare("SELECT COUNT(*) FROM bidang_prokja WHERE nama = :nama");
                $cek->execute([':nama' => $nama]);
                if ((int)$cek->fetchColumn() > 0) {
                    jsonFail('Nama bidang sudah ada.');
                }

                $stmt = $pdo->prepare("INSERT INTO bidang_prokja (kode, nama) VALUES (:kode, :nama)");
                $stmt->execute([
                    ':kode' => $kode,
                    ':nama' => $nama
                ]);
            }
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            jsonFail('Gagal menyimpan bidang: ' . $e->getMessage());
        }
        exit;
    }

    if ($action === 'delete_bidang') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonFail('Metode tidak diizinkan.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        if (!$id) {
            jsonFail('ID tidak valid.');
        }

        try {
            // Check if there are programs or activities associated with this bidang
            $cekPk = $pdo->prepare("SELECT COUNT(*) FROM program_kerja WHERE bidang_id = :id");
            $cekPk->execute([':id' => $id]);
            if ((int)$cekPk->fetchColumn() > 0) {
                jsonFail('Tidak dapat menghapus bidang ini karena masih digunakan oleh beberapa Program Kerja.');
            }

            $cekKeg = $pdo->prepare("SELECT COUNT(*) FROM kegiatan WHERE bidang_id = :id");
            $cekKeg->execute([':id' => $id]);
            if ((int)$cekKeg->fetchColumn() > 0) {
                jsonFail('Tidak dapat menghapus bidang ini karena masih digunakan oleh beberapa Kegiatan Harian.');
            }

            $stmt = $pdo->prepare("DELETE FROM bidang_prokja WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            jsonFail('Gagal menghapus bidang: ' . $e->getMessage());
        }
        exit;
    }

    if ($action === 'upload_foto') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonFail('Metode tidak diizinkan.');
        }

        tolakAksesViewer();

        $program_kerja_id = (int)($_POST['program_kerja_id'] ?? 0);
        $judul = trim($_POST['judul'] ?? '') ?: null;
        $deskripsi = trim($_POST['deskripsi'] ?? '') ?: null;
        $is_cover = isset($_POST['is_cover']) && (int)$_POST['is_cover'] === 1 ? 1 : 0;

        if ($program_kerja_id <= 0) {
            jsonFail('Program kerja tidak valid.');
        }

        // Pastikan program kerja ada
        $cek = $pdo->prepare("SELECT id FROM program_kerja WHERE id = :id");
        $cek->execute([':id' => $program_kerja_id]);
        if (!$cek->fetch()) {
            jsonFail('Program kerja tidak ditemukan.');
        }

        $gdriveUrl = trim($_POST['gdrive_url'] ?? $_POST['foto_url'] ?? '');
        $pathRelatif = '';

        if (!empty($gdriveUrl)) {
            $parsedUrl = parseGoogleDrivePhotoUrl($gdriveUrl);
            if (!preg_match('/^https?:\/\//i', $parsedUrl)) {
                jsonFail('URL atau Link Google Drive tidak valid.');
            }
            $pathRelatif = $parsedUrl;
        } else {
            if (empty($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                jsonFail('Pilih file foto atau tempelkan Link Google Drive.');
            }

            $uploadDir = __DIR__ . '/uploads/program_kerja/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $namaAsli = $_FILES['foto']['name'];
            $tmpPath  = $_FILES['foto']['tmp_name'];
            $size     = $_FILES['foto']['size'];
            
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $maxSizeBytes = 8 * 1024 * 1024; // 8MB

            if ($size > $maxSizeBytes) {
                jsonFail('Ukuran foto melebihi batas 8MB.');
            }

            $ext = strtolower(pathinfo($namaAsli, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                jsonFail('Format file tidak didukung (gunakan JPG, PNG, GIF, atau WEBP).');
            }

            if (@getimagesize($tmpPath) === false) {
                jsonFail('File yang diunggah bukan gambar valid.');
            }

            $namaBaru = 'program-' . $program_kerja_id . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            $tujuan = $uploadDir . $namaBaru;

            if (!move_uploaded_file($tmpPath, $tujuan)) {
                jsonFail('Gagal menyimpan file ke server.');
            }

            $pathRelatif = 'uploads/program_kerja/' . $namaBaru;
        }

        // Jika foto ini diset sebagai cover, reset is_cover foto lainnya terlebih dahulu
        if ($is_cover === 1) {
            $resetCover = $pdo->prepare("UPDATE program_kerja_foto SET is_cover = 0 WHERE program_kerja_id = :pkid");
            $resetCover->execute([':pkid' => $program_kerja_id]);
        } else {
            // Jika belum ada foto lain sama sekali, otomatis jadikan foto pertama ini sebagai cover
            $cekTotal = $pdo->prepare("SELECT COUNT(*) FROM program_kerja_foto WHERE program_kerja_id = :pkid");
            $cekTotal->execute([':pkid' => $program_kerja_id]);
            if ((int)$cekTotal->fetchColumn() === 0) {
                $is_cover = 1;
            }
        }

        $insert = $pdo->prepare("
            INSERT INTO program_kerja_foto (program_kerja_id, path_foto, judul, deskripsi, is_cover)
            VALUES (:pkid, :path, :judul, :deskripsi, :is_cover)
        ");
        $insert->execute([
            ':pkid' => $program_kerja_id,
            ':path' => $pathRelatif,
            ':judul' => $judul,
            ':deskripsi' => $deskripsi,
            ':is_cover' => $is_cover
        ]);

        echo json_encode(['success' => true, 'message' => 'Foto berhasil ditambahkan.']);
        exit;
    }

    if ($action === 'delete_foto') {
        $foto_id = (int)($_POST['foto_id'] ?? 0);
        if ($foto_id <= 0) {
            jsonFail('ID foto tidak valid.');
        }

        // Ambil data foto untuk menghapus file fisik & cek status cover
        $stmtFoto = $pdo->prepare("SELECT * FROM program_kerja_foto WHERE id = :id");
        $stmtFoto->execute([':id' => $foto_id]);
        $foto = $stmtFoto->fetch();

        if (!$foto) {
            jsonFail('Foto tidak ditemukan di database.');
        }

        $fullPath = __DIR__ . '/' . $foto['path_foto'];
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }

        $pdo->prepare("DELETE FROM program_kerja_foto WHERE id = :id")->execute([':id' => $foto_id]);

        // Jika foto yang dihapus adalah cover, dan masih ada foto lain, angkat salah satu menjadi cover baru
        if ((int)$foto['is_cover'] === 1) {
            $getLain = $pdo->prepare("SELECT id FROM program_kerja_foto WHERE program_kerja_id = :pkid LIMIT 1");
            $getLain->execute([':pkid' => $foto['program_kerja_id']]);
            $lainId = $getLain->fetchColumn();
            if ($lainId) {
                $updateCover = $pdo->prepare("UPDATE program_kerja_foto SET is_cover = 1 WHERE id = :id");
                $updateCover->execute([':id' => $lainId]);
            }
        }

        echo json_encode(['success' => true, 'message' => 'Foto berhasil dihapus.']);
        exit;
    }

    if ($action === 'set_cover') {
        $foto_id = (int)($_POST['foto_id'] ?? 0);
        $program_kerja_id = (int)($_POST['program_kerja_id'] ?? 0);

        if ($foto_id <= 0 || $program_kerja_id <= 0) {
            jsonFail('Parameter tidak valid.');
        }

        // Reset cover sebelumnya
        $reset = $pdo->prepare("UPDATE program_kerja_foto SET is_cover = 0 WHERE program_kerja_id = :pkid");
        $reset->execute([':pkid' => $program_kerja_id]);

        // Set cover baru
        $set = $pdo->prepare("UPDATE program_kerja_foto SET is_cover = 1 WHERE id = :id AND program_kerja_id = :pkid");
        $set->execute([':id' => $foto_id, ':pkid' => $program_kerja_id]);

        echo json_encode(['success' => true, 'message' => 'Foto sampul berhasil diperbarui.']);
        exit;
    }

    jsonFail('Aksi tidak dikenali.');

} catch (PDOException $e) {
    jsonFail('Terjadi kesalahan database: ' . $e->getMessage());
}
