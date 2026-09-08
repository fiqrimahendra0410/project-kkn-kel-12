<?php
require 'proteksi.php';
require 'koneksi.php';

tolakAksesViewer();

header('Content-Type: application/json');

function jsonFail(string $message): void {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function parseGoogleDrivePhotoUrl(string $url): string {
    $url = trim($url);
    if (empty($url)) return '';
    if (preg_match('/(?:drive\.google\.com\/(?:file\/d\/|open\?id=|uc\?id=)|lh3\.googleusercontent\.com\/d\/)([a-zA-Z0-9_-]+)/i', $url, $matches)) {
        return 'https://lh3.googleusercontent.com/d/' . $matches[1];
    }
    return $url;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonFail('Metode tidak diizinkan.');
}

$kegiatanId = (int) ($_POST['kegiatan_id'] ?? 0);
if ($kegiatanId <= 0) {
    jsonFail('Kegiatan tidak valid.');
}

// Pastikan kegiatan-nya benar-benar ada
$cekKegiatan = $pdo->prepare("SELECT id FROM kegiatan WHERE id = :id");
$cekKegiatan->execute([':id' => $kegiatanId]);
if (!$cekKegiatan->fetch()) {
    jsonFail('Kegiatan tidak ditemukan.');
}

$gdriveUrl = trim($_POST['gdrive_url'] ?? $_POST['foto_url'] ?? '');
$insertFoto = $pdo->prepare("INSERT INTO kegiatan_foto (kegiatan_id, path_foto, diunggah_pada) VALUES (:kid, :path, NOW())");

$berhasil = 0;
$gagal = [];
$paths = [];

// 1. Jika pengguna memasukkan Link Google Drive / URL Foto
if (!empty($gdriveUrl)) {
    // Bisa berupa beberapa URL dipisahkan koma/baris baru
    $urls = preg_split('/[\r\n,]+/', $gdriveUrl);
    foreach ($urls as $u) {
        $cleanUrl = trim($u);
        if (empty($cleanUrl)) continue;

        $parsedUrl = parseGoogleDrivePhotoUrl($cleanUrl);
        if (!preg_match('/^https?:\/\//i', $parsedUrl)) {
            $gagal[] = "$cleanUrl (URL tidak valid)";
            continue;
        }

        try {
            $insertFoto->execute([':kid' => $kegiatanId, ':path' => $parsedUrl]);
            $berhasil++;
            $paths[] = $parsedUrl;
        } catch (PDOException $e) {
            $gagal[] = "Gagal menyimpan link Google Drive ke database.";
        }
    }
}

// 2. Jika pengguna mengunggah file foto dari komputer
if (!empty($_FILES['foto']) && !empty($_FILES['foto']['name'][0])) {
    $uploadDir = __DIR__ . '/uploads/kegiatan/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $maxSizeBytes = 8 * 1024 * 1024; // 8MB per foto

    $fileCount = count($_FILES['foto']['name']);
    for ($i = 0; $i < $fileCount; $i++) {
        $namaAsli = $_FILES['foto']['name'][$i];
        $tmpPath  = $_FILES['foto']['tmp_name'][$i];
        $errCode  = $_FILES['foto']['error'][$i];
        $size     = $_FILES['foto']['size'][$i];

        if ($errCode !== UPLOAD_ERR_OK) {
            $gagal[] = "$namaAsli (gagal upload)";
            continue;
        }
        if ($size > $maxSizeBytes) {
            $gagal[] = "$namaAsli (ukuran terlalu besar)";
            continue;
        }

        $ext = strtolower(pathinfo($namaAsli, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            $gagal[] = "$namaAsli (format tidak didukung)";
            continue;
        }

        if (@getimagesize($tmpPath) === false) {
            $gagal[] = "$namaAsli (bukan file gambar yang valid)";
            continue;
        }

        $namaBaru = 'kegiatan-' . $kegiatanId . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $tujuan = $uploadDir . $namaBaru;

        if (!move_uploaded_file($tmpPath, $tujuan)) {
            $gagal[] = "$namaAsli (gagal disimpan ke server)";
            continue;
        }

        $pathRelatif = 'uploads/kegiatan/' . $namaBaru;

        try {
            $insertFoto->execute([':kid' => $kegiatanId, ':path' => $pathRelatif]);
            $berhasil++;
            $paths[] = $pathRelatif;
        } catch (PDOException $e) {
            $gagal[] = "$namaAsli (gagal disimpan ke database)";
        }
    }
}

if ($berhasil === 0 && empty($paths)) {
    jsonFail(empty($gagal) ? 'Pilih foto atau masukkan Link Google Drive.' : 'Gagal menyimpan foto: ' . implode(', ', $gagal));
}

echo json_encode([
    'success' => true,
    'uploaded' => $berhasil,
    'failed' => $gagal,
    'paths' => $paths,
]);