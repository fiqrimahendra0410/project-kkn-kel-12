<?php
// proses_video_kegiatan.php
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
$pengunggahFotoMap = [
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

function resolveTikTokShortUrl(string $url): string {
    if (!function_exists('curl_init')) {
        return $url;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    if (!empty($info['url'])) {
        return $info['url'];
    }
    return $url;
}

function parseVideoLink(string $url): array {
    $url = trim($url);
    if (empty($url)) {
        return ['url' => '', 'type' => 'local', 'embeddable' => false, 'is_video_file' => false];
    }

    // 0. Detect local file upload or direct video file URL (.mp4, .webm, .mov, .mkv, .avi, .ogg, .3gp, .m4v)
    if (strpos($url, 'uploads/') === 0 || strpos($url, 'img/') === 0 || preg_match('/\.(mp4|webm|ogg|mov|mkv|avi|m4v|3gp)(\?.*)?$/i', $url)) {
        return [
            'url' => $url,
            'type' => 'local',
            'embeddable' => false,
            'is_video_file' => true
        ];
    }

    // 1. Detect YouTube (shorts, watch, embed, v, youtu.be)
    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $matches)) {
        return [
            'url' => 'https://www.youtube.com/embed/' . $matches[1],
            'type' => 'youtube',
            'embeddable' => true,
            'is_video_file' => false
        ];
    }
    if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
        return [
            'url' => 'https://www.youtube.com/embed/' . $url,
            'type' => 'youtube',
            'embeddable' => true,
            'is_video_file' => false
        ];
    }

    // 2. Detect Instagram (posts, reels, tv)
    if (preg_match('/(?:instagram\.com|instagr\.am)\/(?:p|reel|tv)\/([a-zA-Z0-9_-]+)/i', $url, $matches)) {
        return [
            'url' => 'https://www.instagram.com/p/' . $matches[1] . '/embed/',
            'type' => 'instagram',
            'embeddable' => true,
            'is_video_file' => false
        ];
    }

    // 3. Detect TikTok
    if (preg_match('/(?:vt|vm|t)\.tiktok\.com\/([a-zA-Z0-9_-]+)/i', $url)) {
        $resolvedUrl = resolveTikTokShortUrl($url);
        if (!empty($resolvedUrl)) {
            $url = $resolvedUrl;
        }
    }
    if (preg_match('/(?:tiktok\.com\/@?[^\/]*\/video\/|tiktok\.com\/embed\/v2\/|tiktok\.com\/embed\/)(\d+)/i', $url, $matches)) {
        return [
            'url' => 'https://www.tiktok.com/embed/v2/' . $matches[1],
            'type' => 'tiktok',
            'embeddable' => true,
            'is_video_file' => false
        ];
    }

    // 4. Detect Google Drive video
    if (preg_match('/(?:drive\.google\.com\/file\/d\/|drive\.google\.com\/open\?id=)([a-zA-Z0-9_-]+)/i', $url, $matches)) {
        return [
            'url' => 'https://drive.google.com/file/d/' . $matches[1] . '/preview',
            'type' => 'gdrive',
            'embeddable' => true,
            'is_video_file' => false
        ];
    }

    // 5. Detect Facebook video / reel
    if (preg_match('/(?:facebook\.com|fb\.watch)\/(?:.+?\/videos\/|watch\/\?v=|reel\/)(\d+)/i', $url, $matches)) {
        return [
            'url' => 'https://www.facebook.com/plugins/video.php?href=' . urlencode($url) . '&show_text=false',
            'type' => 'facebook',
            'embeddable' => true,
            'is_video_file' => false
        ];
    }

    // 6. Generic URL or Fallback
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return [
            'url' => $url,
            'type' => 'embed',
            'embeddable' => true,
            'is_video_file' => false
        ];
    }

    return [
        'url' => $url,
        'type' => 'local',
        'embeddable' => false,
        'is_video_file' => true
    ];
}

try {
    // 1. LIST VIDEO KEGIATAN
    if ($action === 'list') {
        $pk_id = isset($_GET['program_kerja_id']) ? (int)$_GET['program_kerja_id'] : 0;
        
        if ($pk_id > 0) {
            $stmt = $pdo->prepare("
                SELECT vk.*, pk.judul AS judul_prokja
                FROM video_kegiatan vk
                LEFT JOIN program_kerja pk ON vk.program_kerja_id = pk.id
                WHERE vk.program_kerja_id = :pkid
                ORDER BY vk.id DESC
            ");
            $stmt->execute([':pkid' => $pk_id]);
        } else {
            $stmt = $pdo->query("
                SELECT vk.*, pk.judul AS judul_prokja
                FROM video_kegiatan vk
                LEFT JOIN program_kerja pk ON vk.program_kerja_id = pk.id
                ORDER BY vk.id DESC
            ");
        }
        
        $videoList = $stmt->fetchAll();
        jsonSuccess(['data' => $videoList]);
    }

    // 2. GET SINGLE VIDEO
    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM video_kegiatan WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $video = $stmt->fetch();
        if ($video) {
            jsonSuccess(['data' => $video]);
        } else {
            jsonFail('Video kegiatan tidak ditemukan.');
        }
    }

    // 3. UPLOAD / EDIT VIDEO KEGIATAN
    if ($action === 'upload' || $action === 'save') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonFail('Metode tidak diizinkan.');
        }

        $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : 0;
        $judul_video = trim($_POST['judul_video'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $pengunggah_nama = trim($_POST['pengunggah_nama'] ?? 'Muhammad Fiqri Mahendra');
        $program_kerja_id = isset($_POST['program_kerja_id']) && $_POST['program_kerja_id'] !== '' ? (int)$_POST['program_kerja_id'] : null;

        if ($judul_video === '') {
            jsonFail('Judul video kegiatan wajib diisi.');
        }

        $finalPath = '';
        $finalType = '';

        // Prioritas 1: Unggah Berkas Video Langsung jika ada file
        if (isset($_FILES['file_video']) && $_FILES['file_video']['error'] !== UPLOAD_ERR_NO_FILE) {
            $fileError = $_FILES['file_video']['error'];
            if ($fileError === UPLOAD_ERR_INI_SIZE || $fileError === UPLOAD_ERR_FORM_SIZE) {
                jsonFail('Ukuran file video terlalu besar (melebihi batas maksimal server).');
            }
            if ($fileError !== UPLOAD_ERR_OK) {
                jsonFail('Terjadi kesalahan saat mengunggah file video (Kode Error: ' . $fileError . ').');
            }

            $fileTmp = $_FILES['file_video']['tmp_name'];
            $fileName = $_FILES['file_video']['name'];
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExt = ['mp4', 'webm', 'mov', 'mkv', 'avi', 'ogg', '3gp', 'm4v'];
            if (!in_array($ext, $allowedExt)) {
                jsonFail('Format file video tidak didukung. Harap gunakan MP4, WebM, MOV, MKV, atau AVI.');
            }

            $uploadDir = __DIR__ . '/uploads/video_kegiatan/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }

            $newFileName = 'video_' . time() . '_' . uniqid() . '.' . $ext;
            $targetFile = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmp, $targetFile)) {
                $finalPath = 'uploads/video_kegiatan/' . $newFileName;
                $finalType = 'local';
            } else {
                jsonFail('Gagal menyimpan file video ke server.');
            }
        } else {
            // Prioritas 2: Link Video (YouTube, Shorts, TikTok, IG, Drive, MP4 URL)
            $link_raw = trim($_POST['link_video'] ?? $_POST['path_video'] ?? '');
            if ($link_raw !== '') {
                $parsed = parseVideoLink($link_raw);
                $finalPath = $parsed['url'];
                $finalType = $parsed['type'];
            }
        }

        $pengunggah_foto = $pengunggahFotoMap[$pengunggah_nama] ?? 'img/foto org.jpg';

        if ($id > 0) {
            // UPDATE
            $stmtExist = $pdo->prepare("SELECT path_video, tipe_video FROM video_kegiatan WHERE id = :id");
            $stmtExist->execute([':id' => $id]);
            $existingData = $stmtExist->fetch();

            if (!$existingData) {
                jsonFail('Data video yang akan diedit tidak ditemukan.');
            }

            if ($finalPath === '') {
                // Tidak ada file/link baru, gunakan video lama
                $finalPath = $existingData['path_video'];
                $finalType = $existingData['tipe_video'];
            } else {
                // Jika ganti video dan video lama ada di uploads/video_kegiatan, hapus berkas fisik lama
                if (!empty($existingData['path_video']) && strpos($existingData['path_video'], 'uploads/video_kegiatan/') === 0) {
                    $oldPath = __DIR__ . '/' . $existingData['path_video'];
                    if (file_exists($oldPath) && is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
            }

            $stmtUpdate = $pdo->prepare("
                UPDATE video_kegiatan 
                SET program_kerja_id = :pkid, judul_video = :judul, deskripsi = :deskripsi,
                    pengunggah_nama = :pengunggah, pengunggah_foto = :foto, path_video = :path_video,
                    tipe_video = :tipe_video
                WHERE id = :id
            ");
            $stmtUpdate->execute([
                ':pkid'       => $program_kerja_id,
                ':judul'      => $judul_video,
                ':deskripsi'  => $deskripsi,
                ':pengunggah' => $pengunggah_nama,
                ':foto'       => $pengunggah_foto,
                ':path_video' => $finalPath,
                ':tipe_video' => $finalType,
                ':id'         => $id
            ]);

            jsonSuccess(['id' => $id, 'message' => 'Video kegiatan berhasil diperbarui.']);
        } else {
            // INSERT
            if ($finalPath === '') {
                jsonFail('Harap upload berkas video (MP4/WebM) atau masukkan link video kegiatan.');
            }

            $stmtInsert = $pdo->prepare("
                INSERT INTO video_kegiatan (program_kerja_id, judul_video, deskripsi, pengunggah_nama, pengunggah_foto, path_video, tipe_video)
                VALUES (:pkid, :judul, :deskripsi, :pengunggah, :foto, :path_video, :tipe_video)
            ");
            $stmtInsert->execute([
                ':pkid'       => $program_kerja_id,
                ':judul'      => $judul_video,
                ':deskripsi'  => $deskripsi,
                ':pengunggah' => $pengunggah_nama,
                ':foto'       => $pengunggah_foto,
                ':path_video' => $finalPath,
                ':tipe_video' => $finalType
            ]);

            jsonSuccess([
                'id' => $pdo->lastInsertId(),
                'message' => 'Video kegiatan berhasil disimpan.'
            ]);
        }
    }

    // 4. HAPUS VIDEO KEGIATAN
    if ($action === 'delete') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonFail('Metode tidak diizinkan.');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonFail('ID video tidak valid.');
        }

        $stmt = $pdo->prepare("SELECT path_video FROM video_kegiatan WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if ($row) {
            $filePath = __DIR__ . '/' . $row['path_video'];
            if (file_exists($filePath) && is_file($filePath)) {
                @unlink($filePath);
            }

            $deleteStmt = $pdo->prepare("DELETE FROM video_kegiatan WHERE id = :id");
            $deleteStmt->execute([':id' => $id]);
            jsonSuccess(['message' => 'Video kegiatan berhasil dihapus.']);
        } else {
            jsonFail('Data video tidak ditemukan.');
        }
    }

    jsonFail('Aksi tidak valid.');

} catch (Throwable $e) {
    jsonFail('Terjadi kesalahan: ' . $e->getMessage());
}
