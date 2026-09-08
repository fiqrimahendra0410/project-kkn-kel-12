<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = !empty($_SESSION['anggota_id']) || !empty($_SESSION['akun_id']);
$namaUserLogin = $_SESSION['nama'] ?? '';

require 'koneksi.php'; // harus menghasilkan variabel $pdo (PDO), sama seperti di buku-lapangan-final.php

if (!function_exists('resolveTikTokShortUrl')) {
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
}

if (!function_exists('parseVideoLink')) {
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
}

// Auto create tabel materi_presentasi jika belum ada
try {
    $is_sqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
    if ($is_sqlite) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `materi_presentasi` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `program_kerja_id` INTEGER NULL,
                `judul_materi` TEXT NOT NULL,
                `tujuan_program` TEXT NULL,
                `sasaran_program` TEXT NULL,
                `dampak_program` TEXT NULL,
                `nama_kelompok` TEXT NOT NULL,
                `pemateri_nama` TEXT NOT NULL,
                `pemateri_foto` TEXT NOT NULL,
                `path_file` TEXT NOT NULL,
                `tipe_file` TEXT NULL,
                `ukuran_file` INTEGER NULL,
                `dibuat_pada` DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `materi_presentasi` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `program_kerja_id` INT NULL,
                `judul_materi` VARCHAR(255) NOT NULL,
                `tujuan_program` TEXT NULL,
                `sasaran_program` TEXT NULL,
                `dampak_program` TEXT NULL,
                `nama_kelompok` VARCHAR(150) NOT NULL,
                `pemateri_nama` VARCHAR(150) NOT NULL,
                `pemateri_foto` VARCHAR(255) NOT NULL,
                `path_file` VARCHAR(255) NOT NULL,
                `tipe_file` VARCHAR(50) NULL,
                `ukuran_file` INT NULL,
                `dibuat_pada` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
} catch (Throwable $e) {}

try { $pdo->exec("ALTER TABLE `materi_presentasi` ADD COLUMN `tujuan_program` TEXT NULL AFTER `judul_materi`"); } catch (Throwable $e) {}
try { $pdo->exec("ALTER TABLE `materi_presentasi` ADD COLUMN `sasaran_program` TEXT NULL AFTER `tujuan_program`"); } catch (Throwable $e) {}
try { $pdo->exec("ALTER TABLE `materi_presentasi` ADD COLUMN `dampak_program` TEXT NULL AFTER `sasaran_program`"); } catch (Throwable $e) {}

// Ambil data materi presentasi
$materiPresentasiList = $pdo->query("
    SELECT mp.*, pk.judul AS judul_prokja
    FROM materi_presentasi mp
    LEFT JOIN program_kerja pk ON mp.program_kerja_id = pk.id
    ORDER BY mp.id DESC
")->fetchAll();

// Auto create tabel video_kegiatan jika belum ada
try {
    $is_sqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
    if ($is_sqlite) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `video_kegiatan` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `program_kerja_id` INTEGER NULL,
                `judul_video` TEXT NOT NULL,
                `deskripsi` TEXT NULL,
                `pengunggah_nama` TEXT NOT NULL,
                `pengunggah_foto` TEXT NOT NULL,
                `path_video` TEXT NOT NULL,
                `tipe_video` TEXT NULL,
                `ukuran_file` INTEGER NULL,
                `dibuat_pada` DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `video_kegiatan` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `program_kerja_id` INT NULL,
                `judul_video` VARCHAR(255) NOT NULL,
                `deskripsi` TEXT NULL,
                `pengunggah_nama` VARCHAR(150) NOT NULL,
                `pengunggah_foto` VARCHAR(255) NOT NULL,
                `path_video` VARCHAR(255) NOT NULL,
                `tipe_video` VARCHAR(50) NULL,
                `ukuran_file` INT NULL,
                `dibuat_pada` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
} catch (Throwable $e) {}

// Ambil data video kegiatan
$videoKegiatanList = $pdo->query("
    SELECT vk.*, pk.judul AS judul_prokja
    FROM video_kegiatan vk
    LEFT JOIN program_kerja pk ON vk.program_kerja_id = pk.id
    ORDER BY vk.id DESC
")->fetchAll();

// Ambil program kerja dari database
$programKerjaRows = $pdo->query("
    SELECT pk.*, bp.nama AS nama_bidang, bp.kode AS kode_bidang, bp.warna_badge,
           (SELECT pkf.path_foto FROM program_kerja_foto pkf WHERE pkf.program_kerja_id = pk.id AND pkf.is_cover = 1 LIMIT 1) AS cover_foto
    FROM program_kerja pk
    JOIN bidang_prokja bp ON pk.bidang_id = bp.id
    WHERE pk.status = 'terbit'
    ORDER BY pk.urutan ASC, pk.id ASC
")->fetchAll();

$namaBulanIndo = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];


// Batas berapa kegiatan terbaru yang tampil di beranda (publik)
const JUMLAH_KEGIATAN_BERANDA = 8;

// Ambil kegiatan terbaru untuk Log Kegiatan
$kegiatanStmt = $pdo->prepare("
    SELECT k.*, b.kode AS divisi, b.nama AS divisiLabel
    FROM kegiatan k
    LEFT JOIN bidang_prokja b ON b.id = k.bidang_id
    ORDER BY k.tanggal DESC, k.id DESC
    LIMIT :lim
");
$kegiatanStmt->bindValue(':lim', JUMLAH_KEGIATAN_BERANDA, PDO::PARAM_INT);
$kegiatanStmt->execute();
$kegiatanTerbaru = $kegiatanStmt->fetchAll();

// Ambil foto per kegiatan (dipakai untuk mengisi Galeri Dokumentasi di beranda)
$fotoStmt = $pdo->prepare("SELECT path_foto FROM kegiatan_foto WHERE kegiatan_id = :kid ORDER BY diunggah_pada DESC");

$logEntries = [];
foreach ($kegiatanTerbaru as $k) {
    $tgl = strtotime($k['tanggal']);

    $jamMulai = !empty($k['jam_mulai']) ? str_replace(':', '.', substr($k['jam_mulai'], 0, 5)) : '';
    $jamSelesai = !empty($k['jam_selesai']) ? str_replace(':', '.', substr($k['jam_selesai'], 0, 5)) : '';
    $waktuLabel = ($jamMulai || $jamSelesai) ? " • Pukul " . ($jamMulai ?: '-') . " – " . ($jamSelesai ?: '-') . " WIB" : '';

    $logEntries[] = [
        'tanggal_label' => date('d', $tgl) . ' ' . $namaBulanIndo[(int)date('n', $tgl)] . ' ' . date('Y', $tgl) . $waktuLabel,
        'judul'         => $k['judul'],
        'deskripsi'     => $k['deskripsi'] ?: '-',
        'divisi'        => $k['divisi'] ?: 'umum',
        'divisiLabel'   => $k['divisiLabel'] ?: 'Kegiatan',
    ];
}

// Ambil SEMUA foto dokumentasi untuk Galeri Dokumentasi (tanpa batasan 8 kegiatan)
$galeriFotos = [];
$seenPaths   = [];

// 1. Ambil seluruh foto dari seluruh kegiatan di tabel kegiatan_foto
$semuaFotoKegiatan = $pdo->query("
    SELECT kf.id AS foto_id, kf.path_foto, kf.keterangan AS foto_keterangan,
           k.id AS kegiatan_id, k.judul AS kegiatan_judul, k.tanggal, k.deskripsi AS kegiatan_deskripsi,
           b.kode AS divisi, b.nama AS divisiLabel
    FROM kegiatan_foto kf
    JOIN kegiatan k ON kf.kegiatan_id = k.id
    LEFT JOIN bidang_prokja b ON b.id = k.bidang_id
    ORDER BY k.tanggal DESC, kf.id DESC
")->fetchAll();

foreach ($semuaFotoKegiatan as $fk) {
    $src = $fk['path_foto'];
    if (empty($src) || isset($seenPaths[$src])) continue;
    $seenPaths[$src] = true;

    $tgl = !empty($fk['tanggal']) ? strtotime($fk['tanggal']) : time();
    $tglLabel = date('d', $tgl) . ' ' . ($namaBulanIndo[(int)date('n', $tgl)] ?? '') . ' ' . date('Y', $tgl);

    $galeriFotos[] = [
        'src'           => $src,
        'kategori'      => $fk['divisi'] ?: 'umum',
        'kategoriLabel' => $fk['divisiLabel'] ?: 'Kegiatan',
        'judul'         => !empty($fk['foto_keterangan']) ? $fk['foto_keterangan'] : $fk['kegiatan_judul'],
        'tanggal'       => $tglLabel,
        'deskripsi'     => $fk['kegiatan_deskripsi'] ?: 'Dokumentasi kegiatan nyata KKN Kelompok 12 di Kelurahan Pal Lima.',
    ];
}

// 2. Ambil seluruh foto dari program_kerja_foto
$semuaFotoProkja = $pdo->query("
    SELECT pkf.id AS foto_id, pkf.path_foto, pkf.judul AS foto_judul, pkf.deskripsi AS foto_deskripsi,
           pk.judul AS prokja_judul, pk.deskripsi AS prokja_deskripsi, pk.periode,
           bp.kode AS divisi, bp.nama AS divisiLabel
    FROM program_kerja_foto pkf
    JOIN program_kerja pk ON pkf.program_kerja_id = pk.id
    LEFT JOIN bidang_prokja bp ON bp.id = pk.bidang_id
    WHERE pk.status = 'terbit'
    ORDER BY pkf.is_cover DESC, pkf.id DESC
")->fetchAll();

foreach ($semuaFotoProkja as $fp) {
    $src = $fp['path_foto'];
    if (empty($src) || isset($seenPaths[$src])) continue;
    $seenPaths[$src] = true;

    $galeriFotos[] = [
        'src'           => $src,
        'kategori'      => $fp['divisi'] ?: 'umum',
        'kategoriLabel' => $fp['divisiLabel'] ?: 'Program Kerja',
        'judul'         => !empty($fp['foto_judul']) ? $fp['foto_judul'] : $fp['prokja_judul'],
        'tanggal'       => $fp['periode'] ?: 'Juli – Agustus 2026',
        'deskripsi'     => !empty($fp['foto_deskripsi']) ? $fp['foto_deskripsi'] : ($fp['prokja_deskripsi'] ?: 'Dokumentasi program kerja KKN Kelompok 12.'),
    ];
}

// Daftar bidang/divisi untuk tombol filter galeri (sumbernya sama dengan Buku Lapangan)
$bidangList = $pdo->query("SELECT id, kode, nama FROM bidang_prokja ORDER BY id")->fetchAll();

// Ukuran ubin galeri dibuat berselang-seling supaya tetap terlihat seperti bento grid
$tileSizes = ['tile-lg', 'tile-md', 'tile-sm', 'tile-sm'];

// Hitung Progress & Statistik KKN untuk Hero Section
$tglMulaiKKN   = strtotime('2026-07-20');
$tglSelesaiKKN = strtotime('2026-08-30');
$totalHariKKN  = max(1, (int)round(($tglSelesaiKKN - $tglMulaiKKN) / 86400) + 1);

$sekarang = time();
if ($sekarang < $tglMulaiKKN) {
    $hariBerjalanKKN   = 0;
    $progressPersenKKN = 0;
    $statusKKNText     = 'Persiapan KKN';
} elseif ($sekarang > $tglSelesaiKKN) {
    $hariBerjalanKKN   = $totalHariKKN;
    $progressPersenKKN = 100;
    $statusKKNText     = 'KKN Selesai (100%)';
} else {
    $hariBerjalanKKN   = max(1, (int)floor(($sekarang - $tglMulaiKKN) / 86400) + 1);
    $progressPersenKKN = min(100, (int)round(($hariBerjalanKKN / $totalHariKKN) * 100));
    $statusKKNText     = "Hari Ke-{$hariBerjalanKKN} dari {$totalHariKKN}";
}

$totalProkjaTerbit     = count($programKerjaRows);
$totalKegiatanRecorded = (int)$pdo->query("SELECT COUNT(*) FROM kegiatan")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="img/logo baru.png">
    <link rel="shortcut icon" type="image/png" href="img/logo baru.png">
    <title>KKN Kelompok 12 — Kelurahan Pal Lima</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://api.mapbox.com/mapbox-gl-js/v3.15.0/mapbox-gl.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css"> <!-- Pastikan file ini ada -->
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            padding-top: 74px; /* offset supaya konten tidak ketutup navbar fixed */
        }
        
        /* ===== HERO PREMIUM STYLING ===== */
        .hero-bg {
            background: linear-gradient(135deg, rgba(8, 20, 52, 0.75) 0%, rgba(15, 23, 42, 0.85) 100%), url('img/foto%20kkn%20bareng.JPG') center/cover no-repeat;
            min-height: calc(100vh - 84px);
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        /* Floating overlay circles for design depth */
        .hero-bg::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.15) 0%, transparent 70%);
            top: 10%;
            right: 5%;
            pointer-events: none;
            z-index: 1;
        }

        .hero-bg::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.1) 0%, transparent 70%);
            bottom: -10%;
            left: -10%;
            pointer-events: none;
            z-index: 1;
        }

        .hero-bg .container {
            position: relative;
            z-index: 2;
        }

        /* Badge Glassmorphism */
        .badge-hero-glass {
            background: rgba(255, 255, 255, 0.08) !important;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff !important;
            font-weight: 600;
            padding: 8px 16px;
            font-size: 0.85rem !important;
            letter-spacing: 0.03em;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        /* Gradient text */
        .hero-gradient-text {
            background: linear-gradient(135deg, #38bdf8 0%, #818cf8 50%, #a855f7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            text-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.01em;
        }

        @media (max-width: 991px) {
            .hero-title {
                font-size: 2.8rem;
            }
        }

        .hero-desc {
            font-size: 1.15rem;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 400;
            line-height: 1.6;
            max-width: 620px;
        }

        /* Premium Buttons */
        .btn-hero-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%) !important;
            border: none !important;
            color: #fff !important;
            font-weight: 600;
            padding: 14px 32px;
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.4);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-hero-primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 30px rgba(79, 70, 229, 0.6);
            background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%) !important;
        }

        .btn-hero-outline {
            background: rgba(255, 255, 255, 0.05) !important;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1.5px solid rgba(255, 255, 255, 0.25) !important;
            color: #fff !important;
            font-weight: 600;
            padding: 14px 32px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-hero-outline:hover {
            background: #fff !important;
            color: #0f172a !important;
            border-color: #fff !important;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255,255,255,0.15);
        }

        /* Glass card */
        .hero-glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            border-radius: 24px;
            position: relative;
            z-index: 10;
            animation: float 6s ease-in-out infinite;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
        }

        .card-glow {
            position: absolute;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.2) 0%, transparent 70%);
            top: -50px;
            right: -50px;
            pointer-events: none;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-12px); }
            100% { transform: translateY(0px); }
        }

        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            display: inline-block;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .6; transform: scale(1.15); }
        }

        .bg-white-10 {
            background-color: rgba(255, 255, 255, 0.06) !important;
            transition: background-color 0.3s;
        }
        .bg-white-10:hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
        }
        .border-white-10 {
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
        }
        .icon-circle {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ===== MAPBOX POPUP CUSTOM STYLING ===== */
        .custom-map-popup .mapboxgl-popup-content {
            border-radius: 18px !important;
            padding: 14px 16px !important;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.25) !important;
            border: 1px solid rgba(0, 0, 0, 0.08) !important;
            background: #ffffff !important;
        }
        .custom-map-popup .mapboxgl-popup-close-button {
            font-size: 16px !important;
            padding: 4px 8px !important;
            color: #64748b !important;
            border-radius: 50% !important;
            top: 6px !important;
            right: 6px !important;
            outline: none !important;
        }
        .custom-map-popup .mapboxgl-popup-close-button:hover {
            background: rgba(15, 23, 42, 0.06) !important;
            color: #0f172a !important;
        }
        .custom-map-popup .mapboxgl-popup-anchor-bottom .mapboxgl-popup-tip {
            border-top-color: #ffffff !important;
        }
        .program-card {
            transition: all 0.3s;
        }
        .program-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
        }
        .program-card__img {
            height: 200px;
            object-fit: cover;
        }
        /* ===== NAVBAR PREMIUM STYLE ===== */
        .navbar-premium {
            background: rgba(255, 255, 255, 0.8) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            padding: 10px 0;
        }
        
        .logo {
            height: 50px !important;
            width: auto;
            object-fit: contain;
            transition: transform 0.3s;
        }
        
        .logo:hover {
            transform: scale(1.05);
        }

        .navbar-brand h5 {
            font-size: 1.15rem;
            color: #0f172a;
            letter-spacing: -0.01em;
            font-weight: 700;
        }

        .navbar-brand small {
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* Modern Nav Links with underlines */
        .navbar-premium .nav-link {
            font-weight: 600;
            color: #1e293b !important;
            font-size: 0.92rem;
            padding: 8px 16px !important;
            transition: all 0.2s ease;
            position: relative;
        }

        .navbar-premium .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: #4f46e5;
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .navbar-premium .nav-link:hover::after,
        .navbar-premium .nav-link.active::after {
            width: 70%;
        }

        .navbar-premium .nav-link:hover,
        .navbar-premium .nav-link.active {
            color: #4f46e5 !important;
        }

        /* Nav Button Buku Lapangan */
        .btn-nav-buku {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%) !important;
            border: none !important;
            color: #fff !important;
            font-weight: 600;
            padding: 8px 24px !important;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
            transition: all 0.3s ease !important;
        }
        
        .btn-nav-buku:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(79, 70, 229, 0.35);
            color: #fff !important;
        }
        
        /* Custom Dropdown Item Hover Style */
        .dropdown-menu .dropdown-item {
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }
        .dropdown-menu .dropdown-item:hover,
        .dropdown-menu .dropdown-item:focus,
        .dropdown-menu .dropdown-item:active {
            background-color: rgba(79, 70, 229, 0.08) !important;
            color: #4f46e5 !important;
            border-color: rgba(79, 70, 229, 0.2) !important;
        }
        .dropdown-menu .dropdown-item.text-danger:hover,
        .dropdown-menu .dropdown-item.text-danger:focus,
        .dropdown-menu .dropdown-item.text-danger:active {
            background-color: rgba(239, 68, 68, 0.08) !important;
            color: #dc2626 !important;
            border-color: rgba(239, 68, 68, 0.2) !important;
        }
        
        .placeholder-section {
            border: 2px dashed #dee2e6;
            border-radius: 1rem;
            padding: 3rem;
        }

        /* ===== VISI MISI PREMIUM STYLE ===== */
        .visimisi-section {
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            position: relative;
        }
        
        .visimisi-card {
            border: 1px solid rgba(0, 0, 0, 0.05) !important;
            border-radius: 24px !important;
            background: #ffffff;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.03) !important;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1) !important;
            overflow: hidden;
            position: relative;
            z-index: 2;
        }
        
        .visimisi-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px rgba(79, 70, 229, 0.08) !important;
            border-color: rgba(79, 70, 229, 0.15) !important;
        }

        .visimisi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(180deg, #4f46e5 0%, #a855f7 100%);
            opacity: 0.85;
        }

        .visimisi-icon-box {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            background: rgba(79, 70, 229, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4f46e5;
            margin-bottom: 24px;
            font-size: 1.6rem;
            transition: all 0.3s;
        }

        .visimisi-card:hover .visimisi-icon-box {
            background: #4f46e5;
            color: #ffffff;
            transform: scale(1.1) rotate(5deg);
        }

        .misi-list li {
            position: relative;
            padding-left: 36px;
            margin-bottom: 18px;
            font-size: 1.02rem;
            line-height: 1.5;
            color: #334155;
        }

        .misi-list li:last-child {
            margin-bottom: 0;
        }

        .misi-icon {
            position: absolute;
            left: 0;
            top: 2px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .visimisi-card:hover .misi-icon {
            background: #10b981;
            color: #ffffff;
            transform: scale(1.1);
        }

        /* ===== PETA WILAYAH & PROFIL DESA PREMIUM STYLING ===== */
        .map-card {
            border: 1px solid rgba(0, 0, 0, 0.05) !important;
            border-radius: 24px !important;
            background: #ffffff;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05) !important;
            overflow: hidden;
            transition: transform 0.3s;
        }

        .map-header {
            background: #0f172a;
            color: #ffffff;
            padding: 16px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .dot-blink {
            width: 8px;
            height: 8px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            animation: blink 1.5s infinite;
        }

        @keyframes blink {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.35); opacity: 0.35; }
        }

        .profile-stat-card {
            border: 1px solid rgba(0, 0, 0, 0.04) !important;
            border-radius: 20px !important;
            background: #ffffff;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.015) !important;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }
        
        .profile-stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 35px rgba(79, 70, 229, 0.08) !important;
            border-color: rgba(79, 70, 229, 0.12) !important;
        }
        
        .stat-icon-wrapper {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            transition: all 0.3s;
        }
        
        .profile-stat-card:hover .stat-icon-wrapper {
            transform: scale(1.15) rotate(5deg);
        }

        /* ===== PROGRAM KERJA CAROUSEL PREMIUM STYLING ===== */
        .program-section {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            position: relative;
        }

        .program-card {
            border: 1px solid rgba(0, 0, 0, 0.04) !important;
            border-radius: 24px !important;
            background: #ffffff;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06) !important;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1) !important;
            overflow: hidden;
        }

        .program-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px rgba(79, 70, 229, 0.1) !important;
        }

        .program-card__img {
            transition: transform 0.5s ease;
        }

        .program-card:hover .program-card__img {
            transform: scale(1.05);
        }

        .program-carousel-control {
            width: 48px;
            height: 48px;
            background: #ffffff !important;
            border-radius: 50% !important;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12) !important;
            color: #0f172a !important;
            border: 1px solid rgba(0, 0, 0, 0.05) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            transition: all 0.3s ease !important;
            opacity: 0.9 !important;
        }

        .program-carousel-control:hover {
            background: #4f46e5 !important;
            color: #ffffff !important;
            transform: translateY(-50%) scale(1.08) !important;
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.3) !important;
            opacity: 1 !important;
            border-color: transparent !important;
        }

        .program-carousel-control-icon {
            font-size: 1.25rem;
            font-weight: bold;
        }

        .program-carousel-indicators button {
            width: 10px !important;
            height: 10px !important;
            border-radius: 50% !important;
            background-color: #cbd5e1 !important;
            border: none !important;
            margin: 0 5px !important;
            transition: all 0.3s ease !important;
        }

        .program-carousel-indicators button.active {
            background-color: #4f46e5 !important;
            width: 24px !important;
            border-radius: 5px !important;
        }

        /* ===== Log Kegiatan (Timeline) ===== */
        .timeline {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px 0;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 25px;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, #4f46e5 0%, #a855f7 100%);
            border-radius: 4px;
            opacity: 0.8;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 65px;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }
        
        .timeline-item:last-child {
            margin-bottom: 0;
        }
        
        .timeline-dot {
            position: absolute;
            left: 17px;
            top: 8px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #ffffff;
            border: 4px solid #4f46e5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
            z-index: 2;
            transition: all 0.3s ease;
        }
        
        .timeline-item:hover .timeline-dot {
            background: #4f46e5;
            box-shadow: 0 0 0 6px rgba(79, 70, 229, 0.25);
            transform: scale(1.1);
        }
        
        .timeline-card {
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.04);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.02);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }
        
        .timeline-card::after {
            content: '';
            position: absolute;
            left: -10px;
            top: 13px;
            width: 0;
            height: 0;
            border-top: 10px solid transparent;
            border-bottom: 10px solid transparent;
            border-right: 10px solid #ffffff;
            filter: drop-shadow(-1px 0px 0px rgba(0,0,0,0.04));
        }
        
        .timeline-item:hover .timeline-card {
            transform: translateX(6px);
            box-shadow: 0 15px 35px rgba(79, 70, 229, 0.06);
            border-color: rgba(79, 70, 229, 0.1);
        }
        
        .timeline-date {
            font-weight: 700;
            color: #4f46e5;
            font-size: 0.82rem;
            display: inline-flex;
            align-items: center;
            letter-spacing: 0.02em;
        }
        /* ===== FOOTER PREMIUM STYLING ===== */
        .footer-premium {
            background-color: #0b0f19 !important;
            color: #94a3b8 !important;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.9rem;
        }

        .footer-premium h5 {
            color: #ffffff;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: -0.01em;
            position: relative;
            padding-bottom: 12px;
        }

        .footer-premium h5::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 35px;
            height: 3px;
            background: #4f46e5;
            border-radius: 2px;
        }

        .footer-social-btn {
            width: 38px;
            height: 38px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #94a3b8;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 1rem;
            text-decoration: none;
        }
        
        .footer-social-btn:hover {
            background: #4f46e5;
            color: #ffffff;
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35);
            border-color: transparent;
        }

        .footer-link {
            color: #94a3b8 !important;
            transition: all 0.25s ease;
            display: inline-block;
            text-decoration: none;
        }

        .footer-link:hover {
            color: #ffffff !important;
            transform: translateX(4px);
        }

        .footer-premium hr {
            border-color: rgba(255, 255, 255, 0.06);
            opacity: 1;
        }

        /* ===== MODERN LOGIN MODAL STYLING ===== */
        .modal-login-custom .modal-content {
            border-radius: 32px !important;
            border: 1px solid rgba(255, 255, 255, 0.45) !important;
            background: rgba(255, 255, 255, 0.83);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            box-shadow: 0 30px 80px rgba(79, 70, 229, 0.18), 0 10px 30px rgba(0, 0, 0, 0.04) !important;
            overflow: hidden;
            position: relative;
        }

        /* Ambient light glows behind modal content */
        .modal-login-custom .modal-content::before {
            content: '';
            position: absolute;
            width: 160px;
            height: 160px;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.12) 0%, transparent 70%);
            top: -30px;
            left: -30px;
            pointer-events: none;
            z-index: 0;
        }

        .modal-login-custom .modal-content::after {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(79, 70, 229, 0.12) 0%, transparent 70%);
            bottom: -40px;
            right: -40px;
            pointer-events: none;
            z-index: 0;
        }

        .modal-login-custom .modal-header {
            border: none;
            padding: 28px 28px 4px 28px;
            position: relative;
            z-index: 1;
        }

        .modal-login-custom .btn-close {
            position: absolute;
            top: 24px;
            right: 24px;
            background-color: rgba(15, 23, 42, 0.04);
            padding: 9px;
            border-radius: 50%;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            font-size: 0.7rem;
            border: 1px solid rgba(15, 23, 42, 0.08);
            z-index: 2;
        }

        .modal-login-custom .btn-close:hover {
            background-color: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            transform: rotate(90deg) scale(1.1);
            border-color: rgba(239, 68, 68, 0.25);
        }

        .login-brand-wrapper {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 24px;
            position: relative;
            z-index: 1;
        }

        .login-avatar-container {
            width: 76px;
            height: 76px;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.12) 0%, rgba(168, 85, 247, 0.12) 100%);
            border-radius: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px auto;
            color: #4f46e5;
            font-size: 2.1rem;
            position: relative;
            box-shadow: inset 0 0 15px rgba(79, 70, 229, 0.08);
            border: 1.5px solid rgba(79, 70, 229, 0.2);
            transition: all 0.4s ease;
        }

        .login-avatar-container::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 26px;
            border: 2px solid #4f46e5;
            top: 0;
            left: 0;
            opacity: 0.2;
            transform: scale(1.1);
            animation: pulse-ring 2.2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse-ring {
            0% { transform: scale(1); opacity: 0.25; }
            50% { transform: scale(1.15); opacity: 0.05; }
            100% { transform: scale(1); opacity: 0.25; }
        }

        .modal-login-custom .modal-body {
            padding: 0 32px 32px 32px;
            position: relative;
            z-index: 1;
        }

        .modal-login-custom .form-label {
            font-size: 0.88rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
            letter-spacing: 0.01em;
        }

        .modal-login-custom .input-group-custom {
            position: relative;
            background: rgba(248, 250, 252, 0.75);
            border: 1.5px solid #e2e8f0;
            border-radius: 18px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            align-items: center;
            overflow: hidden;
            width: 100%;
        }

        .modal-login-custom .input-group-custom:focus-within {
            border-color: #4f46e5;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12);
        }

        .modal-login-custom .input-group-icon {
            padding-left: 18px;
            color: #94a3b8;
            font-size: 1.15rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-login-custom .input-group-custom .form-control {
            border: none !important;
            background: transparent !important;
            padding: 15px 16px 15px 12px;
            font-size: 0.95rem;
            color: #0f172a;
            font-weight: 500;
            width: 100%;
        }

        .modal-login-custom .input-group-custom .form-control:focus {
            box-shadow: none !important;
            outline: none !important;
        }

        .modal-login-custom .btn-toggle-pw {
            border: none;
            background: transparent;
            color: #94a3b8;
            padding-right: 18px;
            transition: color 0.2s;
        }

        .modal-login-custom .btn-toggle-pw:hover {
            color: #4f46e5;
        }

        .modal-login-custom .btn-login-gradient {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%) !important;
            border: none !important;
            color: #ffffff !important;
            font-weight: 700;
            font-size: 1rem;
            padding: 15px;
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.35);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            margin-top: 12px;
        }

        .modal-login-custom .btn-login-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(79, 70, 229, 0.5);
            background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%) !important;
        }

        .modal-login-custom .btn-login-gradient:active {
            transform: translateY(0);
        }

        .modal-login-custom .forgot-password-link {
            text-align: center;
            margin-top: 24px;
            font-size: 0.82rem;
            color: #64748b;
        }

        .modal-login-custom .forgot-password-link a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }

        .modal-login-custom .forgot-password-link a:hover {
            text-decoration: underline;
            color: #4338ca;
        }

        .modal-login-custom .alert-custom {
            border-radius: 12px;
            border: none;
            background-color: #fef2f2;
            color: #ef4444;
            font-weight: 500;
            font-size: 0.85rem;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.05);
        }

        .quick-login-card {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(226, 232, 240, 0.8) !important;
            border-radius: 16px !important;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            padding: 10px 12px !important;
        }

        .quick-login-card:hover {
            border-color: #4f46e5 !important;
            background-color: #f4f5ff !important;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.12) !important;
        }
        
        .quick-login-card img {
            border: 2px solid rgba(79, 70, 229, 0.2);
            transition: all 0.3s ease;
        }

        .quick-login-card:hover img {
            border-color: #4f46e5;
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 0 12px rgba(79, 70, 229, 0.3);
        }

        #quickLoginCollapse::-webkit-scrollbar {
            width: 5px;
        }

        #quickLoginCollapse::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.02);
            border-radius: 10px;
        }

        #quickLoginCollapse::-webkit-scrollbar-thumb {
            background: rgba(79, 70, 229, 0.15);
            border-radius: 10px;
            transition: background 0.3s;
        }

        #quickLoginCollapse::-webkit-scrollbar-thumb:hover {
            background: rgba(79, 70, 229, 0.3);
        }

        @media (max-width: 576px) {
            .modal-login-custom {
                margin: 1.25rem;
            }
            .modal-login-custom .modal-body {
                padding: 0 24px 24px 24px;
            }
        }

        @media (max-width: 991px) {
            .navbar-collapse {
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border-radius: 18px;
                padding: 20px;
                margin-top: 15px;
                box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
                border: 1px solid rgba(0, 0, 0, 0.05);
            }
            .navbar-premium .nav-link {
                padding: 10px 15px !important;
                border-radius: 10px;
            }
            .navbar-premium .nav-link:hover {
                background: rgba(79, 70, 229, 0.08);
            }
            .navbar-premium .nav-link::after {
                display: none !important;
            }
            #navUserInfo, #navLoginBtn {
                margin-top: 15px;
                padding-top: 15px;
                border-top: 1px solid rgba(0, 0, 0, 0.06);
                justify-content: flex-start !important;
                width: 100%;
            }
            .hero-title {
                font-size: 2.2rem !important;
            }
        }

    </style>
</head>
<body>

<!-- Header -->
<nav class="navbar navbar-expand-lg navbar-light fixed-top navbar-premium">
    <div class="container">

        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="#beranda">
            <img src="img/logo%20ump.png" alt="Logo UMP" class="logo">
            <div class="ms-3">
                <h5 class="mb-0 fw-bold">KKN Kelompok 12</h5>
                <small class="text-muted">Universitas Muhammadiyah Pontianak</small>
            </div>
        </a>

        <!-- Toggle -->
        <!-- Toggler Button for Mobile Menu -->
        <button class="navbar-toggler border-0 p-2 ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation" style="outline: none; box-shadow: none; background: rgba(79, 70, 229, 0.08); border-radius: 12px; color: #4f46e5;">
            <i class="bi bi-list fs-4"></i>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="#beranda">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#tentang">Tentang</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#program">Program Kerja</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#kegiatan">Kegiatan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#galeri">Galeri</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#tim">Tim</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#lokasi">Lokasi Posko</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#kontak">Kontak</a>
                </li>
            </ul>

            <?php if ($isLoggedIn): ?>
            <?php 
            $fotoMapIndex = [
                'Rizki Tri Saputra'        => 'img/rizki tri saputra.jpg',
                'Anggi Rahmawati'          => 'img/Anggi Rahmawati.jpg',
                'Virahmanda Abelia Ismaya' => 'img/Virahmanda Abelia Ismaya.jpg',
                'Muhammad Fiqri Mahendra'  => 'img/Muhammad Fiqri Mahendra.jpg',
                'Sebastianus Aditia'      => 'img/Sebastianus Aditia.jpg',
                'Khairunisa Salsabila'      => 'img/Khairunisa Salsabila.jpg',
                'Tiara Fitriani'          => 'img/Tiara Fitriani.jpg',
                'Siti Aliyah'              => 'img/Siti Aliyah.jpg',
                'Fathurrahman'             => 'img/Fathurrahman.jpg',
                "Halimah Tusa'Diah"        => "img/Halimah Tusa'Diah.jpg",
                "Halimah Tusa’diah"        => "img/Halimah Tusa'Diah.jpg",
            ];
            $userPhotoIndex = $_SESSION['foto'] ?? ($fotoMapIndex[$namaUserLogin] ?? '');
            if (stripos($namaUserLogin, 'Kania') !== false) {
                $userPhotoIndex = '';
            }
            ?>
            <div id="navUserInfo" class="d-flex align-items-center gap-2 flex-nowrap ms-auto">
                <a href="buku-lapangan-kkn.php" class="btn btn-nav-buku rounded-pill px-3.5 py-1.5 text-nowrap" style="font-size:0.85rem; font-weight:600;">
                    <i class="bi bi-book-half me-1"></i>Buku Lapangan
                </a>
                <div class="dropdown">
                    <button class="btn btn-light bg-primary bg-opacity-10 border border-primary border-opacity-25 text-dark rounded-pill px-3 py-1 d-flex align-items-center gap-1.5 text-nowrap dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size:0.83rem; font-weight:600;">
                        <?php if (!empty($userPhotoIndex)): ?>
                            <img src="<?= htmlspecialchars($userPhotoIndex) ?>" class="rounded-circle object-fit-cover shadow-sm me-1" style="width: 28px; height: 28px; border: 1.5px solid #4f46e5;" alt="Foto Profile">
                        <?php else: ?>
                            <i class="bi bi-person-circle text-primary fs-6 me-0.5"></i>
                        <?php endif; ?>
                        <span class="nav-nama-user"><?= htmlspecialchars($namaUserLogin) ?></span>
                    </button>
                    <?php 
                    $roleLabel = 'Anggota KKN Kelompok 12';
                    if (isset($_SESSION['nama']) && stripos($_SESSION['nama'], 'Kania') !== false) {
                        $roleLabel = 'Dosen Pembimbing Lapangan (DPL)';
                    } elseif (isset($_SESSION['jabatan']) && !empty($_SESSION['jabatan'])) {
                        $roleLabel = $_SESSION['jabatan'] . ' Kelompok 12';
                    }
                    ?>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2 p-2" style="font-size:0.85rem; min-width: 220px;">
                        <li class="px-3 py-2 mb-1 bg-light rounded-3 d-flex align-items-center gap-2.5">
                            <?php if (!empty($userPhotoIndex)): ?>
                                <img src="<?= htmlspecialchars($userPhotoIndex) ?>" class="rounded-circle object-fit-cover shadow-sm" style="width: 36px; height: 36px; border: 1.5px solid #4f46e5;" alt="Foto Profile">
                            <?php else: ?>
                                <i class="bi bi-person-circle text-primary fs-3"></i>
                            <?php endif; ?>
                            <div>
                                <div class="fw-bold text-dark nav-nama-user" style="font-size: 0.88rem;"><?= htmlspecialchars($namaUserLogin) ?></div>
                                <small class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($roleLabel) ?></small>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider my-1 opacity-25"></li>
                        <li>
                            <a class="dropdown-item py-2 px-3 text-primary rounded-3 fw-semibold d-flex align-items-center gap-2" href="buku-lapangan-kkn.php">
                                <i class="bi bi-book-half fs-6"></i> Buku Lapangan
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 px-3 text-danger rounded-3 fw-semibold d-flex align-items-center gap-2" href="logout.php">
                                <i class="bi bi-box-arrow-right fs-6"></i> Logout / Keluar
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <?php else: ?>
            <div id="navLoginBtn" class="d-flex align-items-center gap-2 flex-nowrap ms-auto">
                <button type="button" onclick="checkAuthGuard(() => { location.reload(); })" class="btn btn-outline-primary rounded-pill px-3 py-1.5 text-nowrap" style="font-size:0.85rem; font-weight:600;">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Login
                </button>
                <button type="button" class="btn btn-nav-buku rounded-pill px-3.5 py-1.5 text-nowrap" style="font-size:0.85rem; font-weight:600;" onclick="checkAuthGuard(() => { window.location.href='buku-lapangan-kkn.php'; })">
                    <i class="bi bi-book-half me-1"></i>Buku Lapangan
                </button>
            </div>
            <div id="navUserInfo" class="align-items-center gap-2 flex-nowrap ms-auto" style="display:none!important;">
                <a href="buku-lapangan-kkn.php" class="btn btn-nav-buku rounded-pill px-3.5 py-1.5 text-nowrap" style="font-size:0.85rem; font-weight:600;">
                    <i class="bi bi-book-half me-1"></i>Buku Lapangan
                </a>
                <div class="dropdown">
                    <button class="btn btn-light bg-primary bg-opacity-10 border border-primary border-opacity-25 text-dark rounded-pill px-3 py-1.5 d-flex align-items-center gap-1.5 text-nowrap dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size:0.83rem; font-weight:600;">
                        <i class="bi bi-person-circle text-primary fs-6 me-0.5"></i>
                        <span class="nav-nama-user"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2 p-2" style="font-size:0.85rem; min-width: 210px;">
                        <li class="px-3 py-2 mb-1 bg-light rounded-3">
                            <div class="fw-bold text-dark nav-nama-user"></div>
                            <small class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($roleLabel) ?></small>
                        </li>
                        <li><hr class="dropdown-divider my-1 opacity-25"></li>
                        <li>
                            <a class="dropdown-item py-2 px-3 text-primary rounded-3 fw-semibold d-flex align-items-center gap-2" href="buku-lapangan-kkn.php">
                                <i class="bi bi-book-half fs-6"></i> Buku Lapangan
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 px-3 text-danger rounded-3 fw-semibold d-flex align-items-center gap-2" href="logout.php">
                                <i class="bi bi-box-arrow-right fs-6"></i> Logout / Keluar
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>
</nav>

<!-- Hero -->

<section id="beranda" class="hero-bg text-white d-flex align-items-center">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7 text-start">
                <span class="badge badge-hero-glass rounded-pill mb-3">
                    <i class="bi bi-calendar-event me-2 text-warning"></i> 20 Juli — 30 Agustus 2026
                </span>
                <h1 class="hero-title mb-3">
                    KKN Kelompok 12 <br>
                    <span class="hero-gradient-text">Kelurahan Pal Lima</span>
                </h1>
                <p class="hero-desc mb-4">
                    Pengabdian nyata oleh <a href="#" data-bs-toggle="modal" data-bs-target="#mahasiswaModal" class="text-warning text-decoration-none border-bottom border-warning border-2 fw-semibold" title="Lihat Daftar Mahasiswa">10 Mahasiswa</a> Universitas Muhammadiyah Pontianak untuk menginspirasi, memberdayakan, dan membangun kemajuan berkelanjutan bersama masyarakat Kelurahan Pal Lima.
                </p>

                <!-- Progress KKN Card Widget (Hero Left) -->
                <div class="hero-progress-card p-3 rounded-4 mb-4 bg-white-10 border-white-10 text-white shadow-sm" style="max-width: 580px; backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.12);">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="icon-circle bg-warning bg-opacity-25 text-warning rounded-circle p-2" style="width: 34px; height: 34px;">
                                <i class="bi bi-graph-up-arrow fs-6 text-warning"></i>
                            </div>
                            <div>
                                <span class="fw-bold text-white mb-0 d-block" style="font-size: 0.92rem; letter-spacing: 0.01em;">Progress Pengabdian KKN</span>
                                <small class="text-white-50" style="font-size: 11px;"><i class="bi bi-flag-fill me-1 text-warning"></i>Status: <?= htmlspecialchars($statusKKNText) ?></small>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary bg-opacity-25 border border-primary border-opacity-25 rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.85rem; color: #38bdf8 !important; backdrop-filter: blur(4px);">
                                <?= $progressPersenKKN ?>% Terlaksana
                            </span>
                        </div>
                    </div>
                    
                    <div class="progress rounded-pill bg-dark bg-opacity-50 overflow-hidden mb-2" style="height: 10px; border: 1px solid rgba(255,255,255,0.1);">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?= max(4, $progressPersenKKN) ?>%; background: linear-gradient(90deg, #f59e0b 0%, #38bdf8 50%, #6366f1 100%);" aria-valuenow="<?= $progressPersenKKN ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center text-white-50 pt-1 flex-wrap gap-2" style="font-size: 11.5px;">
                        <span><i class="bi bi-calendar-check me-1 text-warning"></i> Durasi: <?= $totalHariKKN ?> Hari</span>
                        <span><i class="bi bi-check2-square me-1 text-info"></i> <?= $totalProkjaTerbit ?> Program Kerja</span>
                        <span><i class="bi bi-journal-text me-1 text-success"></i> <?= $totalKegiatanRecorded ?> Kegiatan Terbuku</span>
                    </div>
                </div>

                <div class="d-flex gap-3 flex-wrap">
                    <button type="button" class="btn btn-hero-primary btn-lg rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="bi bi-journal-text me-2"></i> Buku Lapangan
                    </button>
                    <a href="#tentang" class="btn btn-hero-outline btn-lg rounded-pill px-4">
                        <i class="bi bi-arrow-down-circle me-2"></i> Tentang Kami
                    </a>
                </div>
            </div>
            
            <div class="col-lg-5 mt-4 mt-lg-0">
                <div class="hero-glass-card p-4 shadow-lg border border-white-50 text-white position-relative overflow-hidden">
                    <div class="card-glow"></div>
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2" style="font-size: 1.1rem; letter-spacing: 0.02em;">
                        <i class="bi bi-activity text-warning animate-pulse"></i> Sekilas KKN 12
                    </h5>
                    
                    <div class="row g-3">
                        <!-- Progress Bar Ringkasan KKN (Hero Card) -->
                        <div class="col-12">
                            <div class="p-3 bg-white-10 rounded-3 border-white-10">
                                <div class="d-flex justify-content-between align-items-center mb-1.5">
                                    <span class="fw-semibold text-white d-flex align-items-center gap-1.5" style="font-size: 12.5px;">
                                        <i class="bi bi-bar-chart-line-fill text-warning"></i> Progress KKN
                                    </span>
                                    <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-0.5 fw-bold" style="font-size: 11px;">
                                        <?= $progressPersenKKN ?>%
                                    </span>
                                </div>
                                <div class="progress rounded-pill bg-dark bg-opacity-50 overflow-hidden mb-1.5" style="height: 8px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?= max(5, $progressPersenKKN) ?>%; background: linear-gradient(90deg, #f59e0b 0%, #38bdf8 50%, #6366f1 100%);"></div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center text-white-50" style="font-size: 10.5px;">
                                    <span><i class="bi bi-clock-history me-1 text-warning"></i><?= htmlspecialchars($statusKKNText) ?></span>
                                    <span><i class="bi bi-kanban me-1 text-info"></i><?= $totalProkjaTerbit ?> Prokja | <?= $totalKegiatanRecorded ?> Kegiatan</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="p-3 bg-white-10 rounded-3 border-white-10 text-center" 
                                 data-bs-toggle="modal" data-bs-target="#mahasiswaModal" 
                                 style="cursor: pointer; transition: all 0.2s;"
                                 onmouseover="this.style.transform='scale(1.05)'; this.style.backgroundColor='rgba(255,255,255,0.15)';"
                                 onmouseout="this.style.transform='scale(1)'; this.style.backgroundColor='rgba(255,255,255,0.06)';"
                                 title="Klik untuk melihat daftar mahasiswa KKN">
                                <h3 class="fw-bold mb-1 text-warning" style="font-size: 2.2rem; font-family: 'Outfit', sans-serif;">10</h3>
                                <small class="text-white-50 text-uppercase" style="font-size: 10px; font-weight: 700; letter-spacing: 0.05em;">Mahasiswa <i class="bi bi-info-circle ms-1" style="font-size: 8px; vertical-align: middle;"></i></small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-white-10 rounded-3 border-white-10 text-center" 
                                 data-bs-toggle="modal" data-bs-target="#divisiModal" 
                                 style="cursor: pointer; transition: all 0.2s;"
                                 onmouseover="this.style.transform='scale(1.05)'; this.style.backgroundColor='rgba(255,255,255,0.15)';"
                                 onmouseout="this.style.transform='scale(1)'; this.style.backgroundColor='rgba(255,255,255,0.06)';"
                                 title="Klik untuk melihat pembagian divisi kerja">
                                <h3 class="fw-bold mb-1 text-info" style="font-size: 2.2rem; font-family: 'Outfit', sans-serif;">6</h3>
                                <small class="text-white-50 text-uppercase" style="font-size: 10px; font-weight: 700; letter-spacing: 0.05em;">Divisi Kerja <i class="bi bi-info-circle ms-1" style="font-size: 8px; vertical-align: middle;"></i></small>
                            </div>
                        </div>

                        <!-- Card DPL KKN 12 di Sekilas KKN -->
                        <div class="col-12">
                            <div class="p-3 bg-white-10 rounded-3 border-white-10 d-flex align-items-center gap-3" 
                                 style="transition: all 0.2s;"
                                 onmouseover="this.style.transform='scale(1.02)'; this.style.backgroundColor='rgba(255,255,255,0.15)';"
                                 onmouseout="this.style.transform='scale(1)'; this.style.backgroundColor='rgba(255,255,255,0.06)';"
                                 title="Dosen Pembimbing Lapangan KKN Kelompok 12">
                                <div class="icon-circle bg-warning bg-opacity-25 text-warning rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; flex-shrink: 0;">
                                    <i class="bi bi-person-workspace fs-5 text-warning"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 text-white" style="font-size: 12.5px; font-weight: 600;">Dosen Pembimbing Lapangan (DPL)</h6>
                                    <p class="mb-0 text-warning fw-bold" style="font-size: 11.5px;">Ibu Kania Khairunnisa, M.Psi., Psikolog</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <a href="https://maps.app.goo.gl/ZPPdFPtyNP9BAeJz7" target="_blank" class="text-decoration-none d-block">
                                <div class="p-3 bg-white-10 rounded-3 border-white-10 d-flex align-items-center gap-3" 
                                     style="cursor: pointer; transition: all 0.2s;"
                                     onmouseover="this.style.transform='scale(1.03)'; this.style.backgroundColor='rgba(255,255,255,0.15)';"
                                     onmouseout="this.style.transform='scale(1)'; this.style.backgroundColor='rgba(255,255,255,0.06)';"
                                     title="Klik untuk membuka lokasi di Google Maps">
                                    <div class="icon-circle bg-success bg-opacity-25 text-success rounded-circle p-2">
                                        <i class="bi bi-geo-alt fs-5 text-success"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-white" style="font-size: 13px; font-weight: 600;">Lokasi KKN <i class="bi bi-box-arrow-up-right ms-1 text-white-50" style="font-size: 9px; vertical-align: middle;"></i></h6>
                                        <p class="mb-0 text-white-50" style="font-size: 11px;">Komplek Didis Permai 8 Blok C-D, Pal Lima</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-12">
                            <div class="p-3 bg-white-10 rounded-3 border-white-10">
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <div class="icon-circle bg-primary bg-opacity-25 text-primary rounded-circle p-2" style="width: 38px; height: 38px;">
                                        <i class="bi bi-clock fs-5 text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-white" style="font-size: 13px; font-weight: 600;" id="timerStatus">Durasi Pengabdian</h6>
                                        <p class="mb-0 text-white-50" style="font-size: 11px;">20 Juli — 30 Agustus 2026 (40 Hari)</p>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 justify-content-between text-center mt-2 pt-1">
                                    <div class="p-2 rounded bg-dark bg-opacity-25 flex-fill" style="min-width: 55px;">
                                        <div class="fw-bold text-warning mb-0" id="timerDays" style="font-size: 16px; font-family: 'Outfit', sans-serif; line-height: 1.2;">00</div>
                                        <div class="text-white-50" style="font-size: 8px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Hari</div>
                                    </div>
                                    <div class="p-2 rounded bg-dark bg-opacity-25 flex-fill" style="min-width: 55px;">
                                        <div class="fw-bold text-white mb-0" id="timerHours" style="font-size: 16px; font-family: 'Outfit', sans-serif; line-height: 1.2;">00</div>
                                        <div class="text-white-50" style="font-size: 8px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Jam</div>
                                    </div>
                                    <div class="p-2 rounded bg-dark bg-opacity-25 flex-fill" style="min-width: 55px;">
                                        <div class="fw-bold text-white mb-0" id="timerMinutes" style="font-size: 16px; font-family: 'Outfit', sans-serif; line-height: 1.2;">00</div>
                                        <div class="text-white-50" style="font-size: 8px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Menit</div>
                                    </div>
                                    <div class="p-2 rounded bg-dark bg-opacity-25 flex-fill" style="min-width: 55px;">
                                        <div class="fw-bold text-info mb-0" id="timerSeconds" style="font-size: 16px; font-family: 'Outfit', sans-serif; line-height: 1.2;">00</div>
                                        <div class="text-white-50" style="font-size: 8px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Detik</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Visi Misi -->
<section id="tentang" class="visimisi-section py-5">
    <div class="container py-3">
        <div class="text-center mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill mb-2 fw-semibold" style="font-size: 0.85rem;">KOMITMEN KAMI</span>
            <h2 class="fw-bold display-5">Visi &amp; Misi</h2>
            <p class="text-muted lead fs-6">Bersama masyarakat membangun kelurahan yang lebih maju, sehat, dan sejahtera.</p>
        </div>
        <div class="row g-4">
            <!-- Visi Card -->
            <div class="col-lg-6">
                <div class="visimisi-card card h-100 border-0 p-4">
                    <div class="card-body">
                        <div class="visimisi-icon-box">
                            <i class="bi bi-eye-fill"></i>
                        </div>
                        <h3 class="fw-bold mb-3" style="font-size: 1.6rem; color: #0f172a;">Visi</h3>
                        <p class="text-secondary" style="font-size: 1.05rem; line-height: 1.7;">
                            Menjadi kelompok KKN yang memberikan kontribusi nyata, inovatif, dan berkelanjutan dalam pemberdayaan serta peningkatan kesejahteraan masyarakat di Kelurahan Pal Lima.
                        </p>
                    </div>
                </div>
            </div>
            <!-- Misi Card -->
            <div class="col-lg-6">
                <div class="visimisi-card card h-100 border-0 p-4">
                    <div class="card-body">
                        <div class="visimisi-icon-box">
                            <i class="bi bi-bullseye"></i>
                        </div>
                        <h3 class="fw-bold mb-4" style="font-size: 1.6rem; color: #0f172a;">Misi</h3>
                        <ul class="list-unstyled misi-list mb-0">
                            <li>
                                <div class="misi-icon"><i class="bi bi-check-lg"></i></div>
                                <strong>Pendidikan:</strong> Meningkatkan kualitas dan minat belajar masyarakat melalui program bimbingan edukatif.
                            </li>
                            <li>
                                <div class="misi-icon"><i class="bi bi-check-lg"></i></div>
                                <strong>Kesehatan:</strong> Mendukung program kesehatan warga melalui penyuluhan dan aksi sosial terpadu.
                            </li>
                            <li>
                                <div class="misi-icon"><i class="bi bi-check-lg"></i></div>
                                <strong>Ekonomi:</strong> Memberdayakan UMKM lokal melalui inovasi pemasaran dan digitalisasi usaha.
                            </li>
                            <li>
                                <div class="misi-icon"><i class="bi bi-check-lg"></i></div>
                                <strong>Lingkungan:</strong> Menginisiasi gerakan peduli kebersihan dan kelestarian lingkungan kelurahan.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Profil Kelurahan & Peta -->
<section id="profil-desa" class="py-5" style="background: linear-gradient(180deg, #f1f5f9 0%, #ffffff 100%);">
    <div class="container py-3">

        <!-- Judul -->
        <div class="text-center mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill mb-2 fw-semibold" style="font-size: 0.85rem;">GEO-LOKASI</span>
            <h2 class="fw-bold display-5">
                Peta Wilayah <span class="text-primary">Kelurahan Pal Lima</span>
            </h2>
            <p class="text-muted fs-6">
                Kecamatan Pontianak Barat, Kota Pontianak, Provinsi Kalimantan Barat
            </p>
        </div>

        <!-- Peta Interaktif Mapbox Card -->
        <div class="map-card shadow-lg border-0 rounded-4 overflow-hidden mb-5 position-relative" style="z-index: 2;">
            <div class="map-header d-flex align-items-center justify-content-between px-4 py-3 flex-wrap gap-2" style="background: #0f172a;">
                <span class="small fw-semibold text-white d-flex align-items-center gap-2" style="font-size: 13px; letter-spacing: 0.02em;">
                    <span class="dot-blink"></span> PETA SATELIT INTERAKTIF
                </span>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" onclick="bukaModalTambahLokasi()" class="badge bg-danger bg-opacity-20 text-white border border-danger border-opacity-30 rounded-pill px-3 py-1.5 fw-semibold d-flex align-items-center gap-1.5 border-0" style="font-size: 11.5px; cursor: pointer; transition: all 0.2s;" title="Klik untuk menambah titik lokasi pelaksanaan Program Kerja di peta">
                        <i class="bi bi-geo-alt-fill text-danger fs-6"></i> + Tambah Titik Proker
                    </button>
                    <button id="btnToggleTraffic" class="badge bg-success bg-opacity-20 text-white border border-success border-opacity-30 rounded-pill px-3 py-1.5 fw-semibold d-flex align-items-center gap-1.5 border-0" style="font-size: 11.5px; cursor: pointer; transition: all 0.2s;" onclick="if(window.toggleTrafficMap) window.toggleTrafficMap();" title="Klik untuk mengaktifkan/mematikan layer Lalu Lintas real-time">
                        <i class="bi bi-signpost-split-fill text-success fs-6"></i> <span id="lblTrafficState">Lalu Lintas: ON</span>
                    </button>
                    <div id="mapWeatherHeaderBadge" class="badge bg-white bg-opacity-10 text-white border border-white border-opacity-15 rounded-pill px-3 py-1.5 fw-normal d-flex align-items-center gap-1.5" style="font-size: 11.5px; cursor: pointer;" title="Klik untuk melihat lokasi & cuaca Kantor Kelurahan" onclick="if(window.bukaPopupMap) window.bukaPopupMap();">
                        <i class="bi bi-cloud-sun text-warning fs-6"></i> <span id="hdrWeatherText">Cuaca Pal Lima: Memuat...</span>
                    </div>
                </div>
            </div>
            <div id="map" style="width: 100%; height: 500px; background: #e2e8f0;"></div>

            <!-- Panel Daftar Titik Lokasi Peta & Hapus Titik -->
            <div class="px-4 py-3 border-top border-secondary border-opacity-25" style="background: #0f172a;">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                    <span class="fw-bold text-white small d-flex align-items-center gap-2" style="font-size: 0.85rem;">
                        <i class="bi bi-geo-fill text-danger fs-6"></i> Daftar Titik Lokasi Proker di Peta
                    </span>
                    <small class="text-white-50 extra-small" style="font-size: 0.76rem;">
                        <i class="bi bi-info-circle me-1 text-info"></i>Klik kartu untuk fokus peta • Klik <i class="bi bi-trash-fill text-danger mx-0.5"></i> untuk hapus titik
                    </small>
                </div>
                <div id="containerDaftarTitikProkja" class="d-flex gap-2.5 overflow-x-auto pb-2" style="scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.2) transparent;">
                    <div class="text-white-50 small p-2" style="font-size: 11px;">
                        <span class="spinner-border spinner-border-sm me-1" role="status"></span> Memuat daftar titik lokasi proker...
                    </div>
                </div>
            </div>
        </div>
        <script src="https://api.mapbox.com/mapbox-gl-js/v3.15.0/mapbox-gl.js"></script>
        <script src="map.js"></script>

        <!-- Profil -->
        <div class="row align-items-center mt-5">

            <div class="col-lg-12">
                <h3 class="fw-bold mb-3" style="font-size: 1.8rem;">
                    Profil Singkat <span class="text-primary">Kelurahan Pal Lima</span>
                </h3>

                <p class="text-secondary lead fs-6" style="line-height: 1.7;">
                    Kelurahan Pal Lima merupakan salah satu wilayah strategis yang berada di Kecamatan Pontianak Barat, Kota Pontianak. Kawasan ini terus berkembang pesat ditunjang oleh pemukiman warga yang tertata, fasilitas pendidikan yang lengkap, rumah ibadah, layanan kesehatan, serta geliat potensi ekonomi kreatif dan UMKM kemasyarakatan.
                </p>

                <div class="row g-4 mt-4">

                    <!-- Lokasi -->
                    <div class="col-6 col-md-3">
                        <div class="profile-stat-card border-0 p-4 text-center bg-white shadow-sm h-100">
                            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary mx-auto mb-3">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1" style="font-size: 1rem;">Lokasi</h6>
                            <p class="mb-0 text-muted small">Pontianak Barat</p>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="col-6 col-md-3">
                        <div class="profile-stat-card border-0 p-4 text-center bg-white shadow-sm h-100">
                            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success mx-auto mb-3">
                                <i class="bi bi-building"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1" style="font-size: 1rem;">Status</h6>
                            <p class="mb-0 text-muted small">Kelurahan</p>
                        </div>
                    </div>

                    <!-- Potensi -->
                    <div class="col-6 col-md-3">
                        <div class="profile-stat-card border-0 p-4 text-center bg-white shadow-sm h-100">
                            <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning mx-auto mb-3">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1" style="font-size: 1rem;">Potensi</h6>
                            <p class="mb-0 text-muted small">Perdagangan &amp; UMKM</p>
                        </div>
                    </div>

                    <!-- Fasilitas -->
                    <div class="col-6 col-md-3">
                        <div class="profile-stat-card border-0 p-4 text-center bg-white shadow-sm h-100">
                            <div class="stat-icon-wrapper bg-danger bg-opacity-10 text-danger mx-auto mb-3" style="color: #6f42c1 !important; background-color: rgba(111,66,193,0.1) !important;">
                                <i class="bi bi-house-heart" style="color: #6f42c1 !important;"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1" style="font-size: 1rem;">Fasilitas</h6>
                            <p class="mb-0 text-muted small" style="font-size: 12px; line-height: 1.3;">Sekolah, Masjid, Puskesmas</p>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>
</section>


<!-- Program Kerja (Carousel) -->
<section id="program" class="program-section py-5">
    <div class="container py-3">

        <div class="text-center mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill mb-2 fw-semibold" style="font-size: 0.85rem;">PROGRAM UTAMA</span>
            <h2 class="fw-bold display-5">Program Kerja KKN Kelompok 12</h2>
            <p class="text-muted fs-6">
                Program kerja yang dilaksanakan bersama masyarakat Kelurahan Pal Lima.
            </p>
        </div>

        <?php if (count($programKerjaRows) > 0): ?>
        <div id="programCarousel" class="carousel slide position-relative" data-bs-ride="carousel">

            <!-- Indicators -->
            <div class="carousel-indicators program-carousel-indicators position-relative mb-4">
                <?php foreach ($programKerjaRows as $index => $pk): ?>
                <button type="button" data-bs-target="#programCarousel" data-bs-slide-to="<?= $index ?>" class="<?= $index === 0 ? 'active' : '' ?>" aria-current="<?= $index === 0 ? 'true' : 'false' ?>" aria-label="Slide <?= $index + 1 ?>"></button>
                <?php endforeach; ?>
            </div>

            <div class="carousel-inner">
                <?php foreach ($programKerjaRows as $index => $pk): 
                    $cover = parsePhotoUrl($pk['cover_foto'] ?: ($pk['gambar'] ?: 'img/20171130105645.jpg'));
                    $badgeWarna = $pk['warna_badge'] ?: 'primary';
                    
                    // Map emoji badge
                    $badgeIcon = '📋';
                    if ($pk['kode_bidang'] === 'pendidikan') $badgeIcon = '📚';
                    elseif ($pk['kode_bidang'] === 'kesehatan') $badgeIcon = '❤️';
                    elseif ($pk['kode_bidang'] === 'ekonomi') $badgeIcon = '💼';
                    elseif ($pk['kode_bidang'] === 'lingkungan') $badgeIcon = '🌱';
                ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <div class="row justify-content-center">
                        <div class="col-lg-10 col-xl-9">
                            <div class="card border-0 shadow-lg program-card">
                                <div class="row g-0">
                                    <div class="col-md-5 position-relative overflow-hidden" style="min-height: 280px;">
                                        <img class="program-card__img w-100 h-100 position-absolute" style="object-fit: cover;" src="<?= htmlspecialchars($cover) ?>" alt="<?= htmlspecialchars($pk['judul']) ?>">
                                    </div>
                                    <div class="col-md-7 d-flex align-items-center">
                                        <div class="card-body p-4 p-md-5 text-start">
                                            <span class="badge bg-<?= htmlspecialchars($badgeWarna) ?> bg-opacity-10 text-<?= htmlspecialchars($badgeWarna) ?> mb-2 py-2 px-3 rounded-pill fw-semibold" style="font-size: 0.8rem; border: 1px solid rgba(var(--bs-<?= htmlspecialchars($badgeWarna) ?>-rgb), 0.15);"><?= $badgeIcon ?> <?= htmlspecialchars($pk['nama_bidang']) ?></span>
                                            <h4 class="fw-bold mb-2 text-dark" style="letter-spacing: -0.01em; font-size: 1.5rem;"><?= htmlspecialchars($pk['judul']) ?></h4>
                                            <p class="text-muted small mb-3"><i class="bi bi-calendar3 me-2"></i><?= htmlspecialchars($pk['periode']) ?></p>
                                            <p class="text-secondary mb-4" style="line-height: 1.6; font-size: 0.92rem;"><?= htmlspecialchars($pk['deskripsi']) ?></p>
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <a href="dokumentasi.php?id=<?= $pk['id'] ?>" class="btn btn-<?= htmlspecialchars($badgeWarna) ?> rounded-pill px-4 py-2" style="font-weight: 600; font-size: 0.88rem; box-shadow: 0 4px 12px rgba(var(--bs-<?= htmlspecialchars($badgeWarna) ?>-rgb), 0.2);">Lihat Dokumentasi</a>
                                                <?php 
                                                $prokjaLink = !empty($pk['link']) ? $pk['link'] : 'https://kkn12.ct.ws/?i=1';
                                                ?>
                                                <a href="<?= htmlspecialchars($prokjaLink) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-<?= htmlspecialchars($badgeWarna) ?> rounded-pill px-3 py-2" style="font-weight: 600; font-size: 0.88rem;">
                                                    <i class="bi bi-link-45deg me-1"></i> Link Program Kerja
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Controls -->
            <button class="carousel-control-prev program-carousel-control" type="button" data-bs-target="#programCarousel" data-bs-slide="prev" style="left: -20px;">
                <i class="bi bi-chevron-left program-carousel-control-icon"></i>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next program-carousel-control" type="button" data-bs-target="#programCarousel" data-bs-slide="next" style="right: -20px;">
                <i class="bi bi-chevron-right program-carousel-control-icon"></i>
                <span class="visually-hidden">Next</span>
            </button>

        </div>
        <?php else: ?>
            <p class="text-center text-muted">Belum ada Program Kerja yang diterbitkan.</p>
        <?php endif; ?>

        <!-- Section Materi Presentasi & Dokumen Program Kerja (Style Awal Light & Clean) -->
        <div class="mt-5 pt-4 border-top border-secondary border-opacity-10" id="section-materi-presentasi">
            
            <!-- Section Header -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <span class="badge px-3 py-2 rounded-pill mb-2 fw-semibold" style="font-size: 0.8rem; background: rgba(79, 70, 229, 0.1); color: #4f46e5;">
                        <i class="bi bi-easel-fill me-1"></i> MATERI &amp; DOKUMEN PROGRAM
                    </span>
                    <h3 class="fw-bold mb-1" style="letter-spacing: -0.01em;">Berkas Presentasi &amp; Dokumen Program Kerja</h3>
                    <p class="text-muted small mb-0">
                        Materi seminar, modul presentasi, serta berkas pendukung yang memuat 
                        <strong class="text-dark">Tujuan Program</strong>, <strong class="text-dark">Sasaran Program</strong>, dan <strong class="text-dark">Dampak yang Diharapkan</strong> dari KKN Kelompok 12.
                    </p>
                </div>
                <div>
                    <button type="button" onclick="bukaModalTambahMateri()" class="btn btn-primary rounded-pill px-4 py-2 text-nowrap shadow-sm" style="font-weight: 600; font-size: 0.9rem; background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); border: none;">
                        <i class="bi bi-cloud-arrow-up-fill me-2"></i>Upload Berkas &amp; Materi
                    </button>
                </div>
            </div>



            <?php if (count($materiPresentasiList) > 0): 
                $chunksMateri = array_chunk($materiPresentasiList, 3);
            ?>
                <div id="materiCarousel" class="carousel slide position-relative px-1 px-md-3" data-bs-ride="carousel">
                    <!-- Carousel Indicators -->
                    <?php if (count($chunksMateri) > 1): ?>
                    <div class="carousel-indicators position-relative mb-4">
                        <?php foreach ($chunksMateri as $cIndex => $chunk): ?>
                        <button type="button" data-bs-target="#materiCarousel" data-bs-slide-to="<?= $cIndex ?>" class="<?= $cIndex === 0 ? 'active' : '' ?>" aria-current="<?= $cIndex === 0 ? 'true' : 'false' ?>" aria-label="Slide <?= $cIndex + 1 ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="carousel-inner">
                        <?php foreach ($chunksMateri as $cIndex => $chunk): ?>
                        <div class="carousel-item <?= $cIndex === 0 ? 'active' : '' ?>">
                            <div class="row g-4">
                                <?php foreach ($chunk as $materi): 
                                    $fileExt = strtolower(pathinfo($materi['path_file'], PATHINFO_EXTENSION));
                                    $iconClass = 'bi-file-earmark-text-fill text-primary';
                                    if ($fileExt === 'pdf') $iconClass = 'bi-file-earmark-pdf-fill text-danger';
                                    elseif (in_array($fileExt, ['ppt', 'pptx'])) $iconClass = 'bi-file-earmark-slides-fill text-warning';
                                    elseif (in_array($fileExt, ['doc', 'docx'])) $iconClass = 'bi-file-earmark-word-fill text-info';
                                    elseif (in_array($fileExt, ['zip', 'rar'])) $iconClass = 'bi-file-earmark-zip-fill text-secondary';
                                    
                                    $fotoPemateri = $materi['pemateri_foto'] ?: 'img/foto org.jpg';
                                    
                                    // Map prodi / jurusan kuliah anggota
                                    $prodiMap = [
                                        'Rizki Tri Saputra'        => 'Ilmu Hukum',
                                        'Virahmanda Abelia Ismaya' => 'Ilmu Kesehatan Masyarakat',
                                        'Khairunisa Salsabila'     => 'Ilmu Kesehatan Masyarakat',
                                        'Muhammad Fiqri Mahendra'  => 'Teknik Informatika',
                                        'Siti Aliyyah'             => 'Manajemen',
                                        'Siti Aliyah'              => 'Manajemen',
                                        'Halimah Tusa’diah'        => 'Manajemen',
                                        "Halimah Tusa'Diah"        => 'Manajemen',
                                        'Anggi Rahmawati'          => 'Manajemen',
                                        'Tiara Fitriani'           => 'Manajemen',
                                        'Fathurrahman'             => 'Psikologi',
                                        'Sebastianus Aditia'       => 'Psikologi'
                                    ];
                                    $prodiNama = $prodiMap[$materi['pemateri_nama']] ?? '';
                                ?>
                                <div class="col-md-6 col-lg-4" id="materi-card-<?= $materi['id'] ?>">
                                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden card-materi-item" style="transition: all 0.3s ease; background: #ffffff; border: 1px solid rgba(0,0,0,0.06) !important;">
                                        <div class="card-body p-4 d-flex flex-column">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="position-relative me-3">
                                                    <img src="<?= htmlspecialchars($fotoPemateri) ?>" 
                                                         alt="<?= htmlspecialchars($materi['pemateri_nama']) ?>" 
                                                         class="rounded-circle object-fit-cover shadow-sm"
                                                         style="width: 50px; height: 50px; border: 2px solid #4f46e5;"
                                                         onerror="this.src='img/foto org.jpg'">
                                                    <span class="position-absolute bottom-0 end-0 bg-success border border-white rounded-circle p-1" title="Anggota Tim KKN Kelompok 12"></span>
                                                </div>
                                                <div class="overflow-hidden">
                                                    <h6 class="fw-bold mb-0 text-dark text-truncate" style="font-size: 0.95rem;"><?= htmlspecialchars($materi['pemateri_nama']) ?></h6>
                                                    <?php if (!empty($prodiNama)): ?>
                                                    <div class="text-primary fw-semibold text-truncate mb-1" style="font-size: 0.76rem;">
                                                        <i class="bi bi-mortarboard-fill me-1"></i><?= htmlspecialchars($prodiNama) ?>
                                                    </div>
                                                    <?php endif; ?>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary py-1 px-2 rounded-pill fw-normal" style="font-size: 0.7rem; border: 1px solid rgba(79,70,229,0.15);"><?= htmlspecialchars($materi['nama_kelompok']) ?></span>
                                                </div>
                                            </div>

                                            <h5 class="card-title fw-bold text-dark mb-2" style="font-size: 1.05rem; line-height: 1.4;">
                                                <?= htmlspecialchars($materi['judul_materi']) ?>
                                            </h5>

                                            <?php if (!empty($materi['judul_prokja'])): ?>
                                            <p class="text-muted small mb-2">
                                                <i class="bi bi-journal-bookmark me-1 text-primary"></i> <?= htmlspecialchars($materi['judul_prokja']) ?>
                                            </p>
                                            <?php endif; ?>

                                            <!-- Detail Informasi Berkas / Program (Mini Tabs & Bullet List Modern) -->
                                            <?php 
                                            $hasTujuan  = !empty(trim($materi['tujuan_program'] ?? ''));
                                            $hasSasaran = !empty(trim($materi['sasaran_program'] ?? ''));
                                            $hasDampak  = !empty(trim($materi['dampak_program'] ?? ''));
                                            if ($hasTujuan || $hasSasaran || $hasDampak): 
                                                $cardId = $materi['id'];
                                                
                                                if (!function_exists('renderBulletListFormat')) {
                                                    function renderBulletListFormat($text, $iconClass = 'bi-check-circle-fill text-primary') {
                                                        if (empty(trim($text))) return '';
                                                        $lines = preg_split('/\r\n|\r|\n/', trim($text));
                                                        $html = '<ul class="list-unstyled mb-0 d-flex flex-column gap-2 p-1">';
                                                        foreach ($lines as $line) {
                                                            $line = trim($line);
                                                            if (!empty($line)) {
                                                                $html .= '<li class="d-flex align-items-start gap-2 text-dark" style="font-size:0.8rem; line-height: 1.45;">';
                                                                $html .= '<i class="bi ' . $iconClass . ' flex-shrink-0 mt-0.5" style="font-size:0.85rem;"></i>';
                                                                $html .= '<span class="fw-medium text-secondary">' . htmlspecialchars($line) . '</span>';
                                                                $html .= '</li>';
                                                            }
                                                        }
                                                        $html .= '</ul>';
                                                        return $html;
                                                    }
                                                }
                                            ?>
                                            <div class="my-3 p-2.5 bg-light bg-opacity-75 rounded-3 border border-light-subtle">
                                                <!-- Mini Pills Switcher -->
                                                <ul class="nav nav-pills gap-1 mb-2 bg-white p-1 rounded-pill border shadow-sm" id="pills-tab-<?= $cardId ?>" role="tablist" style="font-size: 0.72rem;">
                                                    <?php if ($hasTujuan): ?>
                                                    <li class="nav-item flex-fill text-center" role="presentation">
                                                        <button class="nav-link active py-1 px-2 w-100 rounded-pill fw-bold text-truncate" id="tab-tujuan-<?= $cardId ?>-btn" data-bs-toggle="pill" data-bs-target="#tab-tujuan-<?= $cardId ?>" type="button" role="tab">🎯 Tujuan</button>
                                                    </li>
                                                    <?php endif; ?>
                                                    <?php if ($hasSasaran): ?>
                                                    <li class="nav-item flex-fill text-center" role="presentation">
                                                        <button class="nav-link <?= !$hasTujuan ? 'active' : '' ?> py-1 px-2 w-100 rounded-pill fw-bold text-truncate" id="tab-sasaran-<?= $cardId ?>-btn" data-bs-toggle="pill" data-bs-target="#tab-sasaran-<?= $cardId ?>" type="button" role="tab">👥 Sasaran</button>
                                                    </li>
                                                    <?php endif; ?>
                                                    <?php if ($hasDampak): ?>
                                                    <li class="nav-item flex-fill text-center" role="presentation">
                                                        <button class="nav-link <?= (!$hasTujuan && !$hasSasaran) ? 'active' : '' ?> py-1 px-2 w-100 rounded-pill fw-bold text-truncate" id="tab-dampak-<?= $cardId ?>-btn" data-bs-toggle="pill" data-bs-target="#tab-dampak-<?= $cardId ?>" type="button" role="tab">📈 Dampak</button>
                                                    </li>
                                                    <?php endif; ?>
                                                </ul>

                                                <!-- Tab Contents -->
                                                <div class="tab-content" id="pills-tabContent-<?= $cardId ?>">
                                                    <?php if ($hasTujuan): ?>
                                                    <div class="tab-pane fade show active" id="tab-tujuan-<?= $cardId ?>" role="tabpanel">
                                                        <?= renderBulletListFormat($materi['tujuan_program'], 'bi-check-circle-fill text-primary') ?>
                                                    </div>
                                                    <?php endif; ?>
                                                    <?php if ($hasSasaran): ?>
                                                    <div class="tab-pane fade <?= !$hasTujuan ? 'show active' : '' ?>" id="tab-sasaran-<?= $cardId ?>" role="tabpanel">
                                                        <?= renderBulletListFormat($materi['sasaran_program'], 'bi-people-fill text-success') ?>
                                                    </div>
                                                    <?php endif; ?>
                                                    <?php if ($hasDampak): ?>
                                                    <div class="tab-pane fade <?= (!$hasTujuan && !$hasSasaran) ? 'show active' : '' ?>" id="tab-dampak-<?= $cardId ?>" role="tabpanel">
                                                        <?= renderBulletListFormat($materi['dampak_program'], 'bi-graph-up-arrow text-warning') ?>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>

                                            <div class="mt-auto pt-3 border-top border-light d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi <?= $iconClass ?> fs-4 me-2"></i>
                                                    <span class="text-uppercase fw-semibold text-muted small"><?= strtoupper($fileExt) ?></span>
                                                </div>

                                                <div class="d-flex gap-2 align-items-center">
                                                    <a href="<?= htmlspecialchars($materi['path_file']) ?>" target="_blank" download class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.8rem;">
                                                        <i class="bi bi-download me-1"></i>Unduh
                                                    </a>
                                                    <button type="button" 
                                                            data-id="<?= $materi['id'] ?>"
                                                            data-judul="<?= htmlspecialchars($materi['judul_materi'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-kelompok="<?= htmlspecialchars($materi['nama_kelompok'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-pemateri="<?= htmlspecialchars($materi['pemateri_nama'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-prokja="<?= $materi['program_kerja_id'] ?>"
                                                            data-tujuan="<?= htmlspecialchars($materi['tujuan_program'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                            data-sasaran="<?= htmlspecialchars($materi['sasaran_program'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                            data-dampak="<?= htmlspecialchars($materi['dampak_program'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                            onclick="editMateriBtn(this)" 
                                                            class="btn btn-sm btn-outline-warning rounded-circle p-1 d-flex align-items-center justify-content-center" 
                                                            style="width: 30px; height: 30px;" 
                                                            title="Edit Berkas">
                                                        <i class="bi bi-pencil-fill" style="font-size: 0.75rem;"></i>
                                                    </button>
                                                    <button type="button" 
                                                            data-id="<?= $materi['id'] ?>"
                                                            data-judul="<?= htmlspecialchars($materi['judul_materi'], ENT_QUOTES, 'UTF-8') ?>"
                                                            onclick="konfirmasiHapusMateriBtn(this)" 
                                                            class="btn btn-sm btn-outline-danger rounded-circle p-1 d-flex align-items-center justify-content-center" 
                                                            style="width: 30px; height: 30px;" 
                                                            title="Hapus Berkas">
                                                        <i class="bi bi-trash-fill" style="font-size: 0.75rem;"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Controls -->
                    <?php if (count($chunksMateri) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#materiCarousel" data-bs-slide="prev" style="left: -20px; width: 42px; height: 42px; top: 45%; background: #4f46e5; border-radius: 50%; opacity: 0.9; border: none; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                        <i class="bi bi-chevron-left text-white fs-5"></i>
                        <span class="visually-hidden">Sebelumnya</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#materiCarousel" data-bs-slide="next" style="right: -20px; width: 42px; height: 42px; top: 45%; background: #4f46e5; border-radius: 50%; opacity: 0.9; border: none; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                        <i class="bi bi-chevron-right text-white fs-5"></i>
                        <span class="visually-hidden">Berikutnya</span>
                    </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5 rounded-4 bg-light">
                    <i class="bi bi-folder2-open display-4 text-muted"></i>
                    <p class="text-muted mt-2 mb-0">Belum ada berkas atau materi yang diunggah.</p>
                </div>
            <?php endif; ?>
        </div>
        <!-- / Section Materi Presentasi & Dokumen Program Kerja -->

        <div class="mt-5 pt-4 border-top border-secondary border-opacity-10">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <span class="badge px-3 py-2 rounded-pill mb-2 fw-semibold" style="font-size: 0.8rem; background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                        <i class="bi bi-camera-reels-fill me-1"></i> DOKUMENTASI VIDEO KEGIATAN
                    </span>
                    <h3 class="fw-bold mb-1" style="letter-spacing: -0.01em;">Galeri Video Kegiatan KKN</h3>
                    <p class="text-muted small mb-0">Dokumentasi video pelaksanaan program kerja dan aktivitas KKN Kelompok 12.</p>
                </div>
                <div>
                    <button type="button" onclick="bukaModalTambahVideo()" class="btn btn-danger rounded-pill px-4 py-2 text-nowrap shadow-sm" style="font-weight: 600; font-size: 0.9rem; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none;">
                        <i class="bi bi-camera-video-fill me-2"></i>Upload Video Kegiatan
                    </button>
                </div>
            </div>

            <?php if (count($videoKegiatanList) > 0): ?>
                <div class="row g-4" id="videoContainer">
                    <?php foreach ($videoKegiatanList as $vid): 
                        $fotoPengunggah = $vid['pengunggah_foto'] ?: 'img/foto org.jpg';
                    ?>
                    <div class="col-md-6 col-lg-4" id="video-card-<?= $vid['id'] ?>">
                        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden card-video-item" style="transition: all 0.3s ease; background: #ffffff; border: 1px solid rgba(0,0,0,0.06) !important;">
                            <div class="ratio ratio-16x9 bg-dark rounded-top overflow-hidden">
                                <?php 
                                $parsedVideo = parseVideoLink($vid['path_video']);
                                $embedUrl = $parsedVideo['url'];
                                $tipeVideo = $parsedVideo['type'];
                                $isEmbeddable = $parsedVideo['embeddable'];
                                $isVideoFile = !empty($parsedVideo['is_video_file']);
                                ?>
                                <?php if ($isVideoFile || $tipeVideo === 'local' || strpos($vid['path_video'], 'uploads/') === 0 || preg_match('/\.(mp4|webm|ogg|mov|mkv|avi|m4v|3gp)(\?.*)?$/i', $vid['path_video'])): ?>
                                    <video controls preload="metadata" class="w-100 h-100" style="object-fit: cover; background: #000;">
                                        <source src="<?= htmlspecialchars($vid['path_video']) ?>" type="video/<?= htmlspecialchars(pathinfo($vid['path_video'], PATHINFO_EXTENSION) ?: 'mp4') ?>">
                                        Browser Anda tidak mendukung pemutaran video.
                                    </video>
                                <?php elseif ($isEmbeddable && !empty($embedUrl)): ?>
                                    <iframe src="<?= htmlspecialchars($embedUrl) ?>" 
                                            title="<?= htmlspecialchars($vid['judul_video']) ?>" 
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                                            allowfullscreen 
                                            class="w-100 h-100 border-0"></iframe>
                                <?php else: ?>
                                    <video controls preload="metadata" class="w-100 h-100" style="object-fit: cover; background: #000;">
                                        <source src="<?= htmlspecialchars($vid['path_video']) ?>">
                                        Browser Anda tidak mendukung pemutaran video.
                                    </video>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-4 d-flex flex-column">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="<?= htmlspecialchars($fotoPengunggah) ?>" 
                                         alt="<?= htmlspecialchars($vid['pengunggah_nama']) ?>" 
                                         class="rounded-circle me-3 object-fit-cover shadow-sm"
                                         style="width: 42px; height: 42px; border: 2px solid #ef4444;"
                                         onerror="this.src='img/foto org.jpg'">
                                    <div class="overflow-hidden">
                                        <h6 class="fw-bold mb-0 text-dark text-truncate" style="font-size: 0.9rem;"><?= htmlspecialchars($vid['pengunggah_nama']) ?></h6>
                                        <span class="text-muted small" style="font-size: 0.75rem;"><i class="bi bi-clock me-1"></i><?= date('d M Y', strtotime($vid['dibuat_pada'])) ?></span>
                                    </div>
                                </div>

                                <h5 class="card-title fw-bold text-dark mb-2" style="font-size: 1.05rem; line-height: 1.4;">
                                    <?= htmlspecialchars($vid['judul_video']) ?>
                                </h5>

                                <?php if (!empty($vid['deskripsi'])): ?>
                                <p class="text-secondary small mb-3" style="font-size: 0.85rem; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= htmlspecialchars($vid['deskripsi']) ?>
                                </p>
                                <?php endif; ?>

                                <?php if (!empty($vid['judul_prokja'])): ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger mb-3 px-2 py-1 rounded-pill small" style="width: fit-content; font-size: 0.72rem;">
                                    <i class="bi bi-journal-bookmark me-1"></i><?= htmlspecialchars($vid['judul_prokja']) ?>
                                </span>
                                <?php endif; ?>

                                <div class="mt-auto pt-3 border-top border-light d-flex align-items-center justify-content-between">
                                    <?php if ($tipeVideo === 'instagram'): ?>
                                        <span class="badge text-uppercase fw-semibold" style="font-size: 0.72rem; background-color: rgba(225, 48, 108, 0.1) !important; color: #E1306C !important;">
                                            <i class="bi bi-instagram me-1"></i>INSTAGRAM
                                        </span>
                                    <?php elseif ($tipeVideo === 'tiktok'): ?>
                                        <span class="badge text-uppercase fw-semibold" style="font-size: 0.72rem; background-color: rgba(0, 0, 0, 0.08) !important; color: #111111 !important;">
                                            <i class="bi bi-tiktok me-1"></i>TIKTOK
                                        </span>
                                    <?php elseif ($tipeVideo === 'gdrive'): ?>
                                        <span class="badge text-uppercase fw-semibold" style="font-size: 0.72rem; background-color: rgba(59, 130, 246, 0.1) !important; color: #2563eb !important;">
                                            <i class="bi bi-google-drive me-1"></i>GOOGLE DRIVE
                                        </span>
                                    <?php elseif ($tipeVideo === 'facebook'): ?>
                                        <span class="badge text-uppercase fw-semibold" style="font-size: 0.72rem; background-color: rgba(24, 119, 242, 0.1) !important; color: #1877F2 !important;">
                                            <i class="bi bi-facebook me-1"></i>FACEBOOK
                                        </span>
                                    <?php elseif ($tipeVideo === 'local' || $isVideoFile): ?>
                                        <span class="badge text-uppercase fw-semibold" style="font-size: 0.72rem; background-color: rgba(16, 185, 129, 0.1) !important; color: #059669 !important;">
                                            <i class="bi bi-file-earmark-play-fill me-1"></i>VIDEO LOKAL / MP4
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger text-uppercase fw-semibold" style="font-size: 0.72rem;">
                                            <i class="bi bi-youtube me-1"></i>YOUTUBE
                                        </span>
                                    <?php endif; ?>

                                    <div class="d-flex gap-2 align-items-center">
                                        <button type="button" 
                                                data-id="<?= $vid['id'] ?>"
                                                data-judul="<?= htmlspecialchars($vid['judul_video'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-deskripsi="<?= htmlspecialchars($vid['deskripsi'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-pengunggah="<?= htmlspecialchars($vid['pengunggah_nama'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-prokja="<?= $vid['program_kerja_id'] ?>"
                                                data-link="<?= htmlspecialchars($vid['path_video'], ENT_QUOTES, 'UTF-8') ?>"
                                                onclick="editVideoBtn(this)" 
                                                class="btn btn-sm btn-outline-warning rounded-circle p-1 d-flex align-items-center justify-content-center" 
                                                style="width: 30px; height: 30px;" 
                                                title="Edit Video">
                                            <i class="bi bi-pencil-fill" style="font-size: 0.75rem;"></i>
                                        </button>
                                        <button type="button" 
                                                data-id="<?= $vid['id'] ?>"
                                                data-judul="<?= htmlspecialchars($vid['judul_video'], ENT_QUOTES, 'UTF-8') ?>"
                                                onclick="konfirmasiHapusVideoBtn(this)" 
                                                class="btn btn-sm btn-outline-danger rounded-circle p-1 d-flex align-items-center justify-content-center" 
                                                style="width: 30px; height: 30px;" 
                                                title="Hapus Video">
                                            <i class="bi bi-trash-fill" style="font-size: 0.75rem;"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5 rounded-4 bg-light">
                    <i class="bi bi-camera-video display-4 text-muted"></i>
                    <p class="text-muted mt-2 mb-0">Belum ada video kegiatan yang diunggah.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Log Kegiatan -->
<section id="kegiatan" class="py-5" style="background-color: #ffffff;">
    <div class="container py-3">
        <div class="text-center mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill mb-2 fw-semibold" style="font-size: 0.85rem;">LINIMASA KEGIATAN</span>
            <h2 class="fw-bold display-5">Log Kegiatan</h2>
            <p class="text-muted fs-6">Rangkaian log kegiatan harian KKN Kelompok 12 di Kelurahan Pal Lima</p>
        </div>

        <div class="timeline">
            <?php if (empty($logEntries)): ?>
                <p class="text-center text-muted">Belum ada kegiatan yang tercatat. Catatan akan otomatis muncul di sini setelah diisi lewat Buku Lapangan.</p>
            <?php else: ?>
                <?php foreach ($logEntries as $entry): 
                    $divisiClass = 'primary';
                    if ($entry['divisi'] === 'pendidikan') $divisiClass = 'success';
                    elseif ($entry['divisi'] === 'kesehatan') $divisiClass = 'danger';
                    elseif ($entry['divisi'] === 'ekonomi') $divisiClass = 'warning';
                    elseif ($entry['divisi'] === 'lingkungan') $divisiClass = 'info';
                ?>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-card">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                            <div class="timeline-date"><i class="bi bi-calendar-event me-2"></i><?= htmlspecialchars($entry['tanggal_label']) ?></div>
                            <span class="badge bg-<?= $divisiClass ?> bg-opacity-10 text-<?= $divisiClass ?> py-1 px-3 rounded-pill" style="font-size: 0.72rem; font-weight: 600; border: 1px solid rgba(var(--bs-<?= $divisiClass ?>-rgb), 0.15);"><?= htmlspecialchars($entry['divisiLabel']) ?></span>
                        </div>
                        <h5 class="fw-bold text-dark mb-2" style="font-size: 1.15rem; letter-spacing: -0.01em;"><?= htmlspecialchars($entry['judul']) ?></h5>
                        <p class="text-secondary small mb-0" style="line-height: 1.6; font-size: 0.88rem;"><?= nl2br(htmlspecialchars($entry['deskripsi'])) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mt-5">
            <button type="button" class="btn btn-outline-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#loginModal">
                <i class="bi bi-journal-text me-1"></i> Lihat Semua di Buku Lapangan
            </button>
        </div>
    </div>
</section>

<!-- Galeri Dokumentasi 3D Studio Modern 2026 -->
<section id="galeri" class="py-5 text-white position-relative overflow-hidden" style="background: linear-gradient(135deg, #090d16 0%, #0f172a 50%, #1e1b4b 100%);">

    <!-- Ambient Glow Effects -->
    <div style="position: absolute; top: -100px; left: -100px; width: 400px; height: 400px; background: radial-gradient(circle, rgba(99, 102, 241, 0.25) 0%, transparent 70%); pointer-events: none; z-index: 1;"></div>
    <div style="position: absolute; bottom: -100px; right: -100px; width: 450px; height: 450px; background: radial-gradient(circle, rgba(6, 182, 212, 0.2) 0%, transparent 70%); pointer-events: none; z-index: 1;"></div>

    <input class="gallery-radio" type="radio" name="Photos" id="check-semua" checked>
    <?php foreach ($bidangList as $b): ?>
        <input class="gallery-radio" type="radio" name="Photos" id="check-<?= htmlspecialchars($b['kode']) ?>">
    <?php endforeach; ?>

    <div class="container gallery-wrap position-relative" style="z-index: 2;">
        <div class="text-center mb-4">
            <span class="gallery-eyebrow"><i class="bi bi-stars me-1 text-warning"></i>DOKUMENTASI VISUAL 2026</span>
            <h2 class="fw-bold mt-2.5 display-5 text-white">
                Galeri <span style="background: linear-gradient(90deg, #38bdf8 0%, #818cf8 50%, #c084fc 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Dokumentasi 3D</span>
            </h2>
            <p class="text-white-50 fs-6">Dokumentasi visual kegiatan nyata KKN Kelompok 12 di Kelurahan Pal Lima</p>
        </div>

        <div class="gallery-filter">
            <label for="check-semua"><i class="bi bi-grid-3x3-gap-fill me-1.5"></i>Semua Foto<span class="gcount"><?= count($galeriFotos) ?></span></label>
            <?php foreach ($bidangList as $b):
                $jumlah = count(array_filter($galeriFotos, fn($f) => $f['kategori'] === $b['kode']));
                if ($jumlah === 0) continue;
            ?>
                <label for="check-<?= htmlspecialchars($b['kode']) ?>"><i class="bi bi-tag-fill me-1.5"></i><?= htmlspecialchars($b['nama']) ?><span class="gcount"><?= $jumlah ?></span></label>
            <?php endforeach; ?>
        </div>

        <div class="photo-gallery">
            <?php if (empty($galeriFotos)): ?>
                <p class="text-center text-white-50" style="grid-column: 1 / -1;">Belum ada foto dokumentasi. Foto akan otomatis muncul di sini setelah diunggah lewat Buku Lapangan.</p>
            <?php else: ?>
                <?php foreach ($galeriFotos as $i => $foto): ?>
                <div class="pic kat-<?= htmlspecialchars($foto['kategori']) ?> <?= $tileSizes[$i % count($tileSizes)] ?>" 
                     style="--d:<?= min($i * 0.05, 0.4) ?>s"
                     data-tanggal="<?= htmlspecialchars($foto['tanggal']) ?>"
                     data-kategori="<?= htmlspecialchars($foto['kategoriLabel']) ?>"
                     data-deskripsi="<?= htmlspecialchars($foto['deskripsi']) ?>">
                    <img src="<?= htmlspecialchars($foto['src']) ?>" alt="<?= htmlspecialchars($foto['judul']) ?>">
                    <div class="pic-shine"></div>
                    <div class="pic-caption text-start">
                        <div class="d-flex align-items-center justify-content-between gap-1 mb-1.5 flex-wrap">
                            <span class="badge rounded-pill px-2.5 py-1 text-uppercase" style="font-size: 8.5px; letter-spacing:0.04em; background: #4f46e5; color: #ffffff; font-weight: 700;">
                                <i class="bi bi-tag-fill me-1"></i><?= htmlspecialchars($foto['kategoriLabel']) ?>
                            </span>
                            <span class="badge rounded-pill px-2.5 py-1" style="font-size: 8.5px; background: #d97706; color: #ffffff; font-weight: 700;">
                                <i class="bi bi-calendar-event-fill me-1"></i><?= htmlspecialchars($foto['tanggal']) ?>
                            </span>
                        </div>
                        <div class="fw-bold text-white fs-6 text-truncate mb-1"><?= htmlspecialchars($foto['judul']) ?></div>
                        <?php if (!empty($foto['deskripsi'])): ?>
                            <div class="text-white-50 small text-truncate" style="font-size: 0.75rem; font-weight: 400; opacity: 0.9;"><?= htmlspecialchars($foto['deskripsi']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="pic-zoom"><i class="bi bi-arrows-fullscreen"></i></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <p class="text-center text-white-50 small mt-4 mb-0">
            <i class="bi bi-cursor-fill me-1 text-warning"></i>Klik foto untuk membuka viewer 3D fullscreen &amp; detail kegiatan
        </p>
    </div>

    <!-- Lightbox Fullscreen -->
    <div class="gallery-lightbox" id="galleryLightbox">
        <button type="button" class="gl-close" id="glClose" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
        <button type="button" class="gl-nav gl-prev" id="glPrev" aria-label="Sebelumnya"><i class="bi bi-chevron-left"></i></button>
        <figure class="gl-figure">
            <img id="glImage" src="" alt="">
            <figcaption id="glCaption" class="mt-2.5 p-3 rounded-4 bg-dark bg-opacity-90 backdrop-blur border border-secondary border-opacity-30 shadow-lg">
                <div class="d-flex align-items-center justify-content-center gap-2 mb-2 flex-wrap">
                    <span class="badge rounded-pill px-3 py-1.5 text-uppercase" style="font-size: 11px; background: #4f46e5; color: #ffffff; font-weight: 800; box-shadow: 0 4px 12px rgba(79,70,229,0.4);">
                        <i class="bi bi-tag-fill me-1"></i><span id="glKategori">Dokumentasi</span>
                    </span>
                    <span class="badge rounded-pill px-3 py-1.5" style="font-size: 11px; background: #f59e0b; color: #ffffff; font-weight: 800; box-shadow: 0 4px 12px rgba(245,158,11,0.4);">
                        <i class="bi bi-calendar-event-fill me-1"></i><span id="glTanggal">Tanggal</span>
                    </span>
                </div>
                <h5 id="glJudul" class="fw-bold text-white mb-1.5 fs-5">Judul Foto</h5>
                <p id="glDeskripsi" class="text-white-50 small mb-0 fs-6" style="line-height: 1.55; max-width: 650px; margin: 0 auto; color: #cbd5e1 !important;"></p>
            </figcaption>
        </figure>
        <button type="button" class="gl-nav gl-next" id="glNext" aria-label="Berikutnya"><i class="bi bi-chevron-right"></i></button>
    </div>
</section>

<style>
    /* ===== Galeri Dokumentasi 3D Studio Styling ===== */
    .gallery-radio {
        display: none;
    }

    .gallery-eyebrow {
        display: inline-flex;
        align-items: center;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #38bdf8;
        background: rgba(56, 189, 248, 0.12);
        border: 1px solid rgba(56, 189, 248, 0.25);
        padding: 6px 18px;
        border-radius: 50rem;
        box-shadow: 0 0 15px rgba(56, 189, 248, 0.2);
    }

    .gallery-filter {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 36px;
    }
    .gallery-filter label {
        display: inline-flex;
        align-items: center;
        padding: 10px 24px;
        border-radius: 50rem;
        font-size: 0.88rem;
        font-weight: 600;
        color: #94a3b8;
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.12);
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .gallery-filter label:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.12);
        border-color: rgba(255, 255, 255, 0.3);
        transform: translateY(-3px);
    }
    .gallery-filter .gcount {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        margin-left: 8px;
        border-radius: 50rem;
        background: rgba(255, 255, 255, 0.1);
        color: #e2e8f0;
        font-size: 0.72rem;
        font-weight: 700;
        transition: all 0.25s ease;
    }
    #check-semua:checked ~ .gallery-wrap .gallery-filter label[for="check-semua"]
    <?php foreach ($bidangList as $b): ?>
    , #check-<?= htmlspecialchars($b['kode']) ?>:checked ~ .gallery-wrap .gallery-filter label[for="check-<?= htmlspecialchars($b['kode']) ?>"]
    <?php endforeach; ?>
    {
        background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%);
        color: #ffffff;
        border-color: transparent;
        box-shadow: 0 0 25px rgba(79, 70, 229, 0.5), 0 0 10px rgba(6, 182, 212, 0.4);
    }
    #check-semua:checked ~ .gallery-wrap .gallery-filter label[for="check-semua"] .gcount
    <?php foreach ($bidangList as $b): ?>
    , #check-<?= htmlspecialchars($b['kode']) ?>:checked ~ .gallery-wrap .gallery-filter label[for="check-<?= htmlspecialchars($b['kode']) ?>"] .gcount
    <?php endforeach; ?>
    {
        background: rgba(255,255,255,0.25);
        color: #ffffff;
    }

    /* 3D Bento Gallery Grid Modern 2026 */
    .photo-gallery {
        width: 100%;
        margin: auto;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        grid-auto-rows: 140px;
        grid-auto-flow: dense;
        gap: 22px;
        perspective: 1200px;
    }
    .photo-gallery .pic.tile-lg { grid-column: span 2; grid-row: span 2; }
    .photo-gallery .pic.tile-md { grid-column: span 2; grid-row: span 1; }
    .photo-gallery .pic.tile-sm { grid-column: span 1; grid-row: span 1; }

    @media (max-width: 900px) {
        .photo-gallery { grid-template-columns: repeat(2, 1fr); grid-auto-rows: 150px; }
        .photo-gallery .pic.tile-lg { grid-column: span 2; grid-row: span 2; }
        .photo-gallery .pic.tile-md { grid-column: span 2; grid-row: span 1; }
        .photo-gallery .pic.tile-sm { grid-column: span 1; grid-row: span 1; }
    }
    @media (max-width: 560px) {
        .photo-gallery { grid-template-columns: repeat(2, 1fr); grid-auto-rows: 160px; gap: 14px; }
        .photo-gallery .pic.tile-lg,
        .photo-gallery .pic.tile-md { grid-column: span 2; grid-row: span 1; }
    }

    .photo-gallery .pic {
        position: relative;
        border-radius: 24px;
        overflow: hidden;
        cursor: pointer;
        display: block;
        background: #0f172a;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        border: 2px solid rgba(255, 255, 255, 0.12);
        transition: transform 0.6s cubic-bezier(0.23, 1, 0.32, 1), box-shadow 0.6s cubic-bezier(0.23, 1, 0.32, 1), border-color 0.4s ease;
        opacity: 0;
        animation: picIn 0.6s cubic-bezier(0.23, 1, 0.32, 1) forwards;
        animation-delay: var(--d, 0s);
        transform-style: preserve-3d;
    }
    @keyframes picIn {
        from { opacity: 0; transform: translateY(30px) scale(0.9); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .photo-gallery .pic:hover {
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6), 0 0 30px rgba(99, 102, 241, 0.4);
        transform: translateY(-10px) rotateX(6deg) rotateY(-6deg) scale(1.03);
        border-color: #818cf8;
        z-index: 10;
    }
    .photo-gallery .pic img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 0.8s cubic-bezier(0.23, 1, 0.32, 1), filter 0.5s ease;
    }
    .photo-gallery .pic:hover img {
        transform: scale(1.15) rotate(1.5deg);
        filter: brightness(1.1);
    }
    .photo-gallery .pic .pic-shine {
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, transparent 60%);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.5s ease;
        z-index: 2;
    }
    .photo-gallery .pic:hover .pic-shine {
        opacity: 1;
    }
    .photo-gallery .pic-caption {
        position: absolute;
        left: 14px;
        right: 14px;
        bottom: 14px;
        padding: 12px 16px;
        background: rgba(15, 23, 42, 0.85);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 16px;
        color: #ffffff;
        font-size: 0.82rem;
        font-weight: 700;
        transform: translateY(14px);
        opacity: 0;
        transition: transform 0.4s cubic-bezier(0.23, 1, 0.32, 1), opacity 0.4s ease;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        z-index: 3;
    }
    .photo-gallery .pic:hover .pic-caption {
        transform: translateY(0);
        opacity: 1;
    }
    .photo-gallery .pic-zoom {
        position: absolute;
        top: 14px;
        right: 14px;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4f46e5, #06b6d4);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        opacity: 0;
        transform: scale(0.5) rotate(-45deg);
        transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
        box-shadow: 0 4px 16px rgba(79, 70, 229, 0.5);
        z-index: 3;
    }
    .photo-gallery .pic:hover .pic-zoom {
        opacity: 1;
        transform: scale(1) rotate(0deg);
    }

    /* filter logic (dibuat otomatis sesuai daftar bidang di database) */
    <?php foreach ($bidangList as $b): ?>
    #check-<?= htmlspecialchars($b['kode']) ?>:checked ~ .gallery-wrap .photo-gallery .pic { display: none; }
    #check-<?= htmlspecialchars($b['kode']) ?>:checked ~ .gallery-wrap .photo-gallery .pic.kat-<?= htmlspecialchars($b['kode']) ?> { display: block; }
    <?php endforeach; ?>

    /* ===== Lightbox ===== */
    .gallery-lightbox {
        position: fixed;
        inset: 0;
        background: rgba(4, 7, 16, 0.95);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1080;
        padding: 20px 60px;
    }
    .gallery-lightbox.open {
        display: flex;
    }
    .gl-figure {
        margin: 0;
        max-width: 850px;
        width: 100%;
        max-height: 92vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .gl-figure img {
        max-width: 100%;
        max-height: 56vh;
        object-fit: contain;
        border-radius: 18px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
        border: 2px solid rgba(255, 255, 255, 0.15);
    }
    .gl-figure figcaption {
        width: 100%;
        max-width: 700px;
        color: #ffffff;
        margin-top: 14px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
    }
    .gl-close, .gl-nav {
        position: absolute;
        background: rgba(255,255,255,0.1);
        border: none;
        color: #fff;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s ease;
        font-size: 1.1rem;
    }
    .gl-close:hover, .gl-nav:hover {
        background: rgba(255,255,255,0.22);
    }
    .gl-close {
        top: 24px;
        right: 24px;
    }
    .gl-nav.gl-prev { left: 20px; top: 50%; transform: translateY(-50%); }
    .gl-nav.gl-next { right: 20px; top: 50%; transform: translateY(-50%); }
    @media (max-width: 640px) {
        .gallery-lightbox { padding: 20px; }
        .gl-nav { width: 38px; height: 38px; }
        .gl-nav.gl-prev { left: 8px; }
        .gl-nav.gl-next { right: 8px; }
    }
</style>

<script>
(function () {
    const pics = Array.from(document.querySelectorAll('#galeri .photo-gallery .pic'));
    const lightbox = document.getElementById('galleryLightbox');
    const glImage = document.getElementById('glImage');
    const glJudul = document.getElementById('glJudul');
    const glTanggal = document.getElementById('glTanggal');
    const glKategori = document.getElementById('glKategori');
    const glDeskripsi = document.getElementById('glDeskripsi');
    let current = 0;

    function openAt(index) {
        current = index;
        const pic = pics[current];
        const img = pic.querySelector('img');
        
        glImage.src = img.src;
        glImage.alt = img.alt;

        if (glJudul) glJudul.textContent = img.alt || 'Dokumentasi KKN';
        if (glTanggal) glTanggal.textContent = pic.dataset.tanggal || '-';
        if (glKategori) glKategori.textContent = pic.dataset.kategori || 'Dokumentasi';
        if (glDeskripsi) glDeskripsi.textContent = pic.dataset.deskripsi || '';

        lightbox.classList.add('open');
    }
    function close() {
        lightbox.classList.remove('open');
    }
    function step(delta) {
        let next = current;
        for (let i = 0; i < pics.length; i++) {
            next = (next + delta + pics.length) % pics.length;
            if (pics[next].offsetParent !== null) { openAt(next); return; }
        }
    }

    pics.forEach((pic, i) => pic.addEventListener('click', () => openAt(i)));
    document.getElementById('glClose').addEventListener('click', close);
    document.getElementById('glPrev').addEventListener('click', () => step(-1));
    document.getElementById('glNext').addEventListener('click', () => step(1));
    lightbox.addEventListener('click', (e) => { if (e.target === lightbox) close(); });
    document.addEventListener('keydown', (e) => {
        if (!lightbox.classList.contains('open')) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowLeft') step(-1);
        if (e.key === 'ArrowRight') step(1);
    });
})();
</script>



<!-- Tim & Struktur Organisasi -->
<section id="tim" class="py-5 bg-light position-relative overflow-hidden">

    <div class="marquee-bg" aria-hidden="true">
        <h2 class="marquee-text"><span>KKN KELOMPOK 12 &nbsp;&nbsp;&nbsp; KKN KELOMPOK 12 &nbsp;&nbsp;&nbsp;</span></h2>
    </div>

    <div class="text-center mb-4 position-relative" style="z-index: 2;">
        <h2 class="fw-bold position-relative display-6">Tim &amp; Struktur Organisasi</h2>
        <p class="text-muted position-relative fs-6">Kenali anggota KKN Kelompok 12 Kelurahan Pal Lima</p>
    </div>

    <div class="carousel-container mx-auto position-relative" style="z-index: 2;">
        <button class="nav-arrow left" id="orgPrev" aria-label="Sebelumnya">&lsaquo;</button>
        <div class="carousel-track" id="orgTrack"></div>
        <button class="nav-arrow right" id="orgNext" aria-label="Berikutnya">&rsaquo;</button>
    </div>

    <div class="member-info position-relative" style="z-index: 2;">
        <h3 class="member-name" id="orgMemberName">—</h3>
        <p class="member-role" id="orgMemberRole">—</p>
    </div>

    <div class="dots position-relative" id="orgDots" style="z-index: 2;"></div>

</section>

<style>
/* ===== TEKS BERJALAN DI BELAKANG JUDUL ===== */
.marquee-bg {
    position: absolute;
    top: 20px;
    left: 0;
    width: 100%;
    overflow: hidden;
    white-space: nowrap;
    pointer-events: none;
    z-index: 1;
}

.marquee-text {
    margin: 0;
    font-size: 6rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: -0.02em;
    color: rgba(79, 70, 229, 0.04);
}

.marquee-text span {
    display: inline-block;
    padding-left: 100%;
    animation: marquee 20s linear infinite;
}

@keyframes marquee {
    0%   { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

/* ===== CAROUSEL 3D COVERFLOW MODERN ===== */
.carousel-container {
    width: 100%;
    max-width: 1200px;
    height: 460px;
    perspective: 1200px;
    margin-top: 20px;
}

.carousel-track {
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    transform-style: preserve-3d;
}

.carousel-track .card {
    position: absolute;
    width: 280px;
    height: 390px;
    background: #ffffff;
    border-radius: 28px;
    overflow: hidden;
    box-shadow: 0 20px 45px rgba(0, 0, 0, 0.12);
    transition: all 0.7s cubic-bezier(0.23, 1, 0.32, 1);
    cursor: pointer;
    margin: 0;
    border: 2px solid rgba(255, 255, 255, 0.6);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
}

.carousel-track .card img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: all 0.7s cubic-bezier(0.23, 1, 0.32, 1);
}

/* Fallback saat foto gagal dimuat: tampilkan inisial nama, bukan icon rusak */
.carousel-track .card .img-fallback {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%);
    color: #fff;
    font-size: 3rem;
    font-weight: 800;
}

.carousel-track .card .card-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 24px 16px 16px;
    background: linear-gradient(to top, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 0.5) 65%, transparent 100%);
    color: #ffffff;
    z-index: 2;
    transition: all 0.4s ease;
    text-align: center;
}

.carousel-track .card .card-overlay-name {
    font-size: 1.1rem;
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 4px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.5);
}

.carousel-track .card .card-overlay-role {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #fde047;
}

/* 3D Perspective Positions with rotateY and translateZ */
.carousel-track .card.center {
    z-index: 10;
    transform: translateX(0) translateZ(100px) rotateY(0deg) scale(1.08);
    box-shadow: 0 30px 65px rgba(79, 70, 229, 0.4), 0 0 30px rgba(99, 102, 241, 0.4);
    border: 3px solid #4f46e5;
}

.carousel-track .card.center img {
    filter: none;
}

.carousel-track .card.left-1 {
    z-index: 6;
    transform: translateX(-220px) translateZ(-100px) rotateY(32deg) scale(0.88);
    opacity: 0.85;
}
.carousel-track .card.left-1 img { filter: brightness(0.85) saturate(0.9); }

.carousel-track .card.left-2 {
    z-index: 2;
    transform: translateX(-410px) translateZ(-260px) rotateY(52deg) scale(0.72);
    opacity: 0.5;
}
.carousel-track .card.left-2 img { filter: brightness(0.7) blur(1px); }

.carousel-track .card.right-1 {
    z-index: 6;
    transform: translateX(220px) translateZ(-100px) rotateY(-32deg) scale(0.88);
    opacity: 0.85;
}
.carousel-track .card.right-1 img { filter: brightness(0.85) saturate(0.9); }

.carousel-track .card.right-2 {
    z-index: 2;
    transform: translateX(410px) translateZ(-260px) rotateY(-52deg) scale(0.72);
    opacity: 0.5;
}
.carousel-track .card.right-2 img { filter: brightness(0.7) blur(1px); }

.carousel-track .card.hidden {
    opacity: 0;
    transform: translateZ(-400px) scale(0.5);
    pointer-events: none;
}

.member-info {
    text-align: center;
    margin-top: 40px;
    transition: all 0.5s ease-out;
}

.member-name {
    color: #0f172a;
    font-size: 2.25rem;
    font-weight: 800;
    margin-bottom: 8px;
    letter-spacing: -0.02em;
}

.member-role {
    display: inline-block;
    background: rgba(79, 70, 229, 0.08);
    color: #4f46e5;
    font-size: 0.88rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    padding: 6px 20px;
    border-radius: 50rem;
    border: 1px solid rgba(79, 70, 229, 0.15);
    margin-top: 5px;
}

.dots {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 50px;
}

.dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #cbd5e1;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 0;
    padding: 0;
    margin: 0 4px;
}

.dot:hover {
    background: #94a3b8;
}

.dot.active {
    width: 24px;
    background: #4f46e5;
    border-radius: 5px;
}

.nav-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: #ffffff !important;
    color: #0f172a !important;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 20;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    font-size: 1.5rem;
    border: 1px solid rgba(0, 0, 0, 0.05) !important;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12) !important;
    outline: none;
    line-height: 1;
}

.nav-arrow:hover {
    background: #4f46e5 !important;
    color: #ffffff !important;
    transform: translateY(-50%) scale(1.1);
    box-shadow: 0 8px 24px rgba(79, 70, 229, 0.3) !important;
    border-color: transparent !important;
}

.nav-arrow.left { left: 20px; }
.nav-arrow.right { right: 20px; }

@media (max-width: 768px) {
    .marquee-text { font-size: 3.5rem; }

    .carousel-track .card {
        width: 190px;
        height: 270px;
    }

    .carousel-track .card.left-2 { transform: translateX(-200px) scale(0.8) translateZ(-250px); }
    .carousel-track .card.left-1 { transform: translateX(-100px) scale(0.9) translateZ(-100px); }
    .carousel-track .card.right-1 { transform: translateX(100px) scale(0.9) translateZ(-100px); }
    .carousel-track .card.right-2 { transform: translateX(200px) scale(0.8) translateZ(-250px); }

    .member-name { font-size: 1.75rem; }
    .member-role { font-size: 0.8rem; padding: 4px 16px; }
}
</style>

<script>
(function () {
    const orgMembers = [
        { name: "Rizki Tri Saputra",            role: "Ketua KKN",            img: "img/rizki tri saputra.jpg" },
        { name: "Anggi Rahmawati",              role: "Sekretaris",            img: "img/Anggi Rahmawati.jpg" },
        { name: "Virahmanda Abelia Ismaya",     role: "Divisi Acara",          img: "img/Virahmanda Abelia Ismaya.jpg" },
        { name: "Muhammad Fiqri Mahendra",      role: "Divisi Acara",          img: "img/Muhammad Fiqri Mahendra.jpg" },
        { name: "Sebastianus Aditia",          role: "Divisi Humas",          img: "img/Sebastianus Aditia.jpg" },
        { name: "Khairunisa Salsabila",          role: "Divisi Humas",          img: "img/Khairunisa Salsabila.jpg" },
        { name: "Tiara Fitriani",              role: "Divisi PDD",            img: "img/Tiara Fitriani.jpg" },
        { name: "Siti Aliyah",                  role: "Divisi PDD",            img: "img/Siti Aliyah.jpg" },
        { name: "Fathurrahman",                 role: "Divisi Perlengkapan",  img: "img/Fathurrahman.jpg" },
        { name: "Halimah Tusa'Diah",            role: "Divisi Perlengkapan",  img: "img/Halimah Tusa'Diah.jpg" },
    ];

    const track = document.getElementById('orgTrack');
    const dotsWrap = document.getElementById('orgDots');
    const nameEl = document.getElementById('orgMemberName');
    const roleEl = document.getElementById('orgMemberRole');
    const btnPrev = document.getElementById('orgPrev');
    const btnNext = document.getElementById('orgNext');

    const n = orgMembers.length;
    let current = 0;

    function initials(name) {
        return name.trim().split(/\s+/).map(w => w[0]).slice(0, 2).join('').toUpperCase();
    }

    orgMembers.forEach((m, i) => {
        const card = document.createElement('div');
        card.className = 'card';
        card.dataset.index = i;
        card.innerHTML = `
            <img src="${m.img}" alt="${m.name}" onerror="this.outerHTML = '<div class=&quot;img-fallback&quot;>${initials(m.name)}</div>'">
            <div class="card-overlay">
                <div class="card-overlay-name">${m.name}</div>
                <div class="card-overlay-role">${m.role}</div>
            </div>
        `;
        card.addEventListener('click', () => goTo(i));
        track.appendChild(card);
    });

    orgMembers.forEach((m, i) => {
        const dot = document.createElement('button');
        dot.className = 'dot' + (i === 0 ? ' active' : '');
        dot.dataset.index = i;
        dot.setAttribute('aria-label', 'Ke anggota ' + (i + 1));
        dot.addEventListener('click', () => goTo(i));
        dotsWrap.appendChild(dot);
    });

    const cards = Array.from(track.children);
    const dots = Array.from(dotsWrap.children);

    function update() {
        cards.forEach((card, i) => {
            let rel = i - current;
            if (rel > n / 2) rel -= n;
            if (rel < -n / 2) rel += n;

            card.classList.remove('center', 'left-1', 'left-2', 'right-1', 'right-2', 'hidden');

            if (rel === 0) card.classList.add('center');
            else if (rel === -1) card.classList.add('left-1');
            else if (rel === -2) card.classList.add('left-2');
            else if (rel === 1) card.classList.add('right-1');
            else if (rel === 2) card.classList.add('right-2');
            else card.classList.add('hidden');
        });

        dots.forEach((dot, i) => dot.classList.toggle('active', i === current));

        nameEl.textContent = orgMembers[current].name;
        roleEl.textContent = orgMembers[current].role;
    }

    function goTo(index) {
        current = ((index % n) + n) % n;
        update();
    }

    btnPrev.addEventListener('click', () => goTo(current - 1));
    btnNext.addEventListener('click', () => goTo(current + 1));

    // Touch Swipe Support for Mobile & Tablet
    let touchStartX = 0;
    let touchEndX = 0;

    track.addEventListener('touchstart', (e) => {
      touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    track.addEventListener('touchend', (e) => {
      touchEndX = e.changedTouches[0].screenX;
      if (Math.abs(touchEndX - touchStartX) > 35) {
        if (touchEndX < touchStartX) goTo(current + 1);
        else goTo(current - 1);
      }
    }, { passive: true });

    document.addEventListener('keydown', (e) => {
        const section = document.getElementById('tim');
        const rect = section.getBoundingClientRect();
        const inView = rect.top < window.innerHeight && rect.bottom > 0;
        if (!inView) return;
        if (e.key === 'ArrowLeft') goTo(current - 1);
        if (e.key === 'ArrowRight') goTo(current + 1);
    });

    update();
})();
</script>

<!-- Leaflet CSS & JS untuk Peta 2 Pin Real-time -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Section Peta & Petunjuk Lokasi Posko KKN -->
<section id="lokasi" class="py-5 bg-white position-relative">
    <div class="container py-3">
        <div class="text-center mb-5">
            <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill mb-2 fw-semibold d-inline-flex align-items-center gap-2" style="font-size: 0.85rem;">
                <svg width="15" height="20" viewBox="0 0 384 512" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67a24 24 0 0 1-37.464 0z" fill="#EA4335"/>
                    <circle cx="192" cy="192" r="75" fill="#7A1C14"/>
                </svg>
                PETUNJUK LOKASI POSKO &amp; KANTOR LURAH
            </span>
            <h2 class="fw-bold display-6 text-dark mb-2">Lokasi Posko KKN &amp; Kantor Lurah</h2>
            <p class="text-muted fs-6 mb-0">Peta 2 titik lokasi utama: Kantor Lurah Pal Lima dan Posko Utama KKN Kelompok 12</p>
        </div>

        <div class="row g-4 align-items-stretch">
            <!-- Peta 2 Marker Interaktif Leaflet -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 position-relative" style="min-height: 440px; border: 1px solid rgba(0,0,0,0.08) !important;">
                    <div id="leafletPoskoMap" style="width: 100%; height: 100%; min-height: 440px; z-index: 1;"></div>
                </div>
            </div>

            <!-- Detail Kartu Petunjuk 2 Lokasi -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100 d-flex flex-column justify-content-between" style="background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%); border: 1px solid rgba(79, 70, 229, 0.12) !important;">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px;">
                                <i class="bi bi-geo-alt-fill fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0">Rute &amp; 2 Titik Lokasi Utama</h5>
                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-1 small fw-normal" style="font-size: 0.73rem;">Kelurahan Pal Lima</span>
                            </div>
                        </div>

                        <ul class="list-unstyled d-flex flex-column gap-3 mb-4">
                            <!-- Titik 1: Kantor Lurah Pal Lima -->
                            <li class="d-flex align-items-start gap-3 p-2.5 rounded-3 bg-light border border-light-subtle">
                                <div class="p-2 rounded-circle bg-primary text-white flex-shrink-0 mt-0.5 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-weight: 700; font-size: 0.82rem;">
                                    A
                                </div>
                                <div>
                                    <span class="fw-bold text-dark d-block" style="font-size: 0.88rem;">Kantor Lurah Pal Lima</span>
                                    <span class="text-muted small">Jl. Husein Hamzah No. 2, Pal Lima, Kec. Pontianak Barat</span>
                                </div>
                            </li>

                            <!-- Titik 2: Posko KKN Kelompok 12 -->
                            <li class="d-flex align-items-start gap-3 p-2.5 rounded-3 bg-danger bg-opacity-10 border border-danger border-opacity-25">
                                <div class="p-2 rounded-circle bg-danger text-white flex-shrink-0 mt-0.5 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-weight: 700; font-size: 0.82rem;">
                                    B
                                </div>
                                <div>
                                    <span class="fw-bold text-dark d-block" style="font-size: 0.88rem;">Posko Utama KKN Kelompok 12</span>
                                    <span class="text-muted small">Komplek Didis Permai 8 Blok C-D, Pal Lima, Kec. Pontianak Barat</span>
                                </div>
                            </li>

                            <li class="d-flex align-items-start gap-3">
                                <div class="p-2 rounded-circle bg-light text-warning flex-shrink-0 mt-0.5" style="width: 34px; height: 34px; display:flex; align-items:center; justify-content:center;">
                                    <i class="bi bi-telephone-fill fs-6"></i>
                                </div>
                                <div>
                                    <span class="fw-bold text-dark d-block" style="font-size: 0.88rem;">Kontak Posko</span>
                                    <a href="https://wa.me/6282357892820" target="_blank" class="text-primary fw-semibold small text-decoration-none">+62 823-5789-2820 (WhatsApp)</a>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div class="pt-3 border-top border-light-subtle d-flex flex-column gap-2">
                        <a href="https://maps.app.goo.gl/ZPPdFPtyNP9BAeJz7" target="_blank" class="btn btn-primary rounded-pill py-2.5 px-4 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); border: none; font-size: 0.9rem;">
                            <svg width="16" height="22" viewBox="0 0 384 512" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67a24 24 0 0 1-37.464 0z" fill="#ffffff"/>
                                <circle cx="192" cy="192" r="75" fill="#4f46e5"/>
                            </svg>
                            <span>Buka Petunjuk Posko KKN di Google Maps</span>
                        </a>
                        <a href="https://www.google.com/maps/dir/?api=1&origin=-0.037462,109.287023&destination=-0.0285055,109.2861275" target="_blank" class="btn btn-outline-secondary rounded-pill py-2 px-4 fw-semibold d-flex align-items-center justify-content-center gap-2" style="font-size: 0.82rem;">
                            <i class="bi bi-sign-turn-right-fill text-danger"></i> Navigasi Dari Kantor Lurah ke Posko
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Script Leaflet 2 Marker Google Maps Pin (Kantor Lurah & Posko KKN 12) -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const latLurah = -0.037462;
    const lngLurah = 109.287023;
    const latPosko = -0.0285055;
    const lngPosko = 109.2861275;

    const mapContainer = document.getElementById('leafletPoskoMap');
    if (!mapContainer || typeof L === 'undefined') return;

    // Inisialisasi Peta Leaflet
    const map = L.map('leafletPoskoMap', {
        scrollWheelZoom: false
    }).setView([(latLurah + latPosko)/2, (lngLurah + lngPosko)/2], 15);

    // Tile Layer CartoDB Voyager Modern
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 19
    }).addTo(map);

    // Marker 1: Kantor Lurah Pal Lima (Custom GMaps Pin Icon)
    const iconLurah = L.divIcon({
        html: `
            <div style="position: relative; display: flex; flex-direction: column; align-items: center; cursor: pointer; transform: translate(-50%, -100%);">
                <div style="background: #ffffff; color: #1e293b; font-weight: 800; font-size: 11px; padding: 4px 10px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.25); white-space: nowrap; margin-bottom: 5px; border: 1.5px solid #4f46e5;">
                    🏢 Kantor Lurah Pal Lima
                </div>
                <svg width="32" height="42" viewBox="0 0 384 512" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 4px 8px rgba(0,0,0,0.35));">
                    <path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67a24 24 0 0 1-37.464 0z" fill="#4F46E5"/>
                    <circle cx="192" cy="192" r="75" fill="#ffffff"/>
                </svg>
            </div>
        `,
        className: 'custom-gmaps-marker-a',
        iconSize: [0, 0],
        iconAnchor: [0, 0]
    });

    // Marker 2: Posko KKN Kelompok 12 (Custom Red GMaps Pin Icon)
    const iconPosko = L.divIcon({
        html: `
            <div style="position: relative; display: flex; flex-direction: column; align-items: center; cursor: pointer; transform: translate(-50%, -100%);">
                <div style="background: #ef4444; color: #ffffff; font-weight: 800; font-size: 11px; padding: 4px 10px; border-radius: 20px; box-shadow: 0 4px 15px rgba(239,68,68,0.4); white-space: nowrap; margin-bottom: 5px; border: 1.5px solid #ffffff;">
                    📍 Posko KKN Kelompok 12
                </div>
                <svg width="34" height="44" viewBox="0 0 384 512" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 4px 10px rgba(239,68,68,0.5));">
                    <path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67a24 24 0 0 1-37.464 0z" fill="#EA4335"/>
                    <circle cx="192" cy="192" r="75" fill="#7A1C14"/>
                </svg>
            </div>
        `,
        className: 'custom-gmaps-marker-b',
        iconSize: [0, 0],
        iconAnchor: [0, 0]
    });

    const markerLurah = L.marker([latLurah, lngLurah], { icon: iconLurah }).addTo(map);
    markerLurah.bindPopup('<div style="font-size:0.85rem;"><b>🏢 Kantor Lurah Pal Lima</b><br>Jl. Husein Hamzah No. 2, Pal Lima</div>');

    const markerPosko = L.marker([latPosko, lngPosko], { icon: iconPosko }).addTo(map);
    markerPosko.bindPopup('<div style="font-size:0.85rem;"><b>📍 Posko Utama KKN Kelompok 12</b><br>Komplek Didis Permai 8 Blok C-D, Pal Lima</div>');

    // Garis Rute Putus-putus Merah
    L.polyline([
        [latLurah, lngLurah],
        [latPosko, lngPosko]
    ], {
        color: '#ef4444',
        weight: 4,
        opacity: 0.85,
        dashArray: '8, 8'
    }).addTo(map);

    // Label Teks "RUTE POSKO KKN" di Tengah Garis Merah
    const midLat = (latLurah + latPosko) / 2;
    const midLng = (lngLurah + lngPosko) / 2;
    const iconRouteText = L.divIcon({
        html: `
            <div style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: #ffffff; font-weight: 800; font-size: 10px; padding: 4px 10px; border-radius: 20px; box-shadow: 0 4px 12px rgba(239,68,68,0.45); white-space: nowrap; border: 1.5px solid #ffffff; transform: translate(-50%, -50%); text-transform: uppercase; letter-spacing: 0.05em;">
                <i class="bi bi-sign-turn-right-fill me-1" style="font-size: 11px;"></i> RUTE POSKO KKN
            </div>
        `,
        className: 'custom-route-text-marker',
        iconSize: [0, 0],
        iconAnchor: [0, 0]
    });
    L.marker([midLat, midLng], { icon: iconRouteText, interactive: false }).addTo(map);

    // Auto Fit Bounds
    map.fitBounds([
        [latLurah, lngLurah],
        [latPosko, lngPosko]
    ], { padding: [60, 60] });
});
</script>

<!-- Footer -->
<footer id="kontak" class="footer-premium py-5" style="background-color: #0b0f19; color: #cbd5e1;">
    <div class="container py-3">
        <div class="row g-4">

            <!-- Tentang -->
            <div class="col-lg-4 col-md-6">
                <h5 class="mb-3 text-white fw-bold position-relative pb-2" style="font-size: 1.15rem;">
                    KKN Kelompok 12
                    <span style="position: absolute; bottom: 0; left: 0; width: 35px; height: 3px; background: linear-gradient(90deg, #4f46e5, #6366f1); border-radius: 2px;"></span>
                </h5>
                <p class="mb-4 text-secondary small" style="line-height: 1.75; color: #94a3b8 !important;">
                    Kelurahan Pal Lima merupakan wilayah strategis di Kecamatan Pontianak Barat, Kota Pontianak, yang terus bersinergi bersama civitas akademika dan masyarakat demi mewujudkan lingkungan yang maju, sehat, kreatif, dan sejahtera.
                </p>
                <div class="d-flex gap-2">
                    <a href="https://www.instagram.com/kknpallima_kel12/reels/" target="_blank" class="footer-social-btn" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="https://www.tiktok.com/@kknpallima_kel12?_r=1&amp;_t=ZS-980E7KegE9w" target="_blank" class="footer-social-btn" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
                    <a href="https://wa.me/6282357892820" target="_blank" class="footer-social-btn" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                </div>
            </div>

            <!-- Quick Links / Navigasi -->
            <div class="col-lg-2 col-md-6">
                <h5 class="mb-3 text-white fw-bold position-relative pb-2" style="font-size: 1.15rem;">
                    Navigasi
                    <span style="position: absolute; bottom: 0; left: 0; width: 35px; height: 3px; background: linear-gradient(90deg, #4f46e5, #6366f1); border-radius: 2px;"></span>
                </h5>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><a href="#beranda" class="footer-link">Beranda</a></li>
                    <li class="mb-2"><a href="#tentang" class="footer-link">Tentang</a></li>
                    <li class="mb-2"><a href="#program" class="footer-link">Program Kerja</a></li>
                    <li class="mb-2"><a href="#kegiatan" class="footer-link">Kegiatan</a></li>
                    <li class="mb-2"><a href="#galeri" class="footer-link">Galeri</a></li>
                    <li class="mb-2"><a href="#tim" class="footer-link">Tim KKN</a></li>
                    <li class="mb-2"><a href="#lokasi" class="footer-link">Lokasi Posko</a></li>
                </ul>
            </div>

            <!-- Program Kerja / Fokus Proker Dinamis -->
            <div class="col-lg-3 col-md-6">
                <h5 class="mb-3 text-white fw-bold position-relative pb-2" style="font-size: 1.15rem;">
                    Fokus Proker
                    <span style="position: absolute; bottom: 0; left: 0; width: 35px; height: 3px; background: linear-gradient(90deg, #4f46e5, #6366f1); border-radius: 2px;"></span>
                </h5>
                <ul class="list-unstyled mb-0">
                    <?php
                    // Ambil Judul Program Kerja Murni dari database (Exclude Survei Lokasi & Berkunjung ke Lurah)
                    try {
                        $fokusQuery = $pdo->query("
                            SELECT pk.id, pk.judul, bp.nama AS bidang_nama 
                            FROM program_kerja pk
                            LEFT JOIN bidang_prokja bp ON bp.id = pk.bidang_id
                            WHERE (pk.judul IS NOT NULL AND pk.judul != '')
                              AND LOWER(pk.judul) NOT LIKE '%survei%'
                              AND LOWER(pk.judul) NOT LIKE '%lurah%'
                              AND LOWER(pk.judul) NOT LIKE '%berkunjung%'
                            ORDER BY pk.id ASC LIMIT 8
                        ");
                        $fokusList = $fokusQuery ? $fokusQuery->fetchAll(PDO::FETCH_ASSOC) : [];

                        // Jika belum ada judul program kerja, ambil dari nama bidang KKN utama
                        if (empty($fokusList)) {
                            $fokusQuery = $pdo->query("
                                SELECT id, nama AS bidang_nama 
                                FROM bidang_prokja 
                                WHERE LOWER(nama) NOT LIKE '%survei%'
                                  AND LOWER(nama) NOT LIKE '%lurah%'
                                  AND LOWER(nama) NOT LIKE '%berkunjung%'
                                ORDER BY id ASC LIMIT 8
                            ");
                            $fokusList = $fokusQuery ? $fokusQuery->fetchAll(PDO::FETCH_ASSOC) : [];
                        }
                    } catch (Exception $e) {
                        $fokusList = [];
                    }
                    ?>
                    <?php if (!empty($fokusList)): ?>
                        <?php foreach ($fokusList as $fokus): 
                            $textItem = !empty($fokus['judul']) ? trim($fokus['judul']) : trim($fokus['bidang_nama'] ?? '');
                            // Filter tambahan via PHP
                            $cleanLower = strtolower($textItem);
                            if (strpos($cleanLower, 'survei') !== false || strpos($cleanLower, 'lurah') !== false || strpos($cleanLower, 'berkunjung') !== false) {
                                continue;
                            }
                        ?>
                            <li class="mb-2">
                                <a href="#program" class="footer-link d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-dash text-primary" style="font-size: 0.9rem;"></i>
                                    <span><?= htmlspecialchars($textItem) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="mb-2"><a href="#program" class="footer-link"><i class="bi bi-dash text-primary me-1"></i>Pendidikan</a></li>
                        <li class="mb-2"><a href="#program" class="footer-link"><i class="bi bi-dash text-primary me-1"></i>Kesehatan</a></li>
                        <li class="mb-2"><a href="#program" class="footer-link"><i class="bi bi-dash text-primary me-1"></i>Ekonomi</a></li>
                        <li class="mb-2"><a href="#program" class="footer-link"><i class="bi bi-dash text-primary me-1"></i>Lingkungan</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Kontak Kami -->
            <div class="col-lg-3 col-md-6">
                <h5 class="mb-3 text-white fw-bold position-relative pb-2" style="font-size: 1.15rem;">
                    Kontak Kami
                    <span style="position: absolute; bottom: 0; left: 0; width: 35px; height: 3px; background: linear-gradient(90deg, #4f46e5, #6366f1); border-radius: 2px;"></span>
                </h5>
                <ul class="list-unstyled mb-0" style="color: #94a3b8;">
                    <li class="mb-3 d-flex align-items-start gap-2.5">
                        <i class="bi bi-geo-alt-fill text-primary mt-1" style="font-size: 1rem;"></i>
                        <span class="small" style="line-height: 1.6;">Kelurahan Pal Lima, Kec. Pontianak Barat, Kota Pontianak, Kalimantan Barat 78113</span>
                    </li>
                    <li class="mb-3 d-flex align-items-center gap-2.5">
                        <i class="bi bi-telephone-fill text-primary" style="font-size: 0.95rem;"></i>
                        <a href="https://wa.me/6282357892820" target="_blank" class="footer-link small">+62 823-5789-2820</a>
                    </li>
                    <li class="mb-3 d-flex align-items-center gap-2.5">
                        <i class="bi bi-envelope-fill text-primary" style="font-size: 0.95rem;"></i>
                        <a href="mailto:kknpallimakelompok12@gmail.com" class="footer-link small">kknpallimakelompok12@gmail.com</a>
                    </li>
                </ul>
            </div>

        </div>

        <!-- Copyright -->
        <div class="row mt-5">
            <div class="col-12 text-center border-top border-secondary border-opacity-25 pt-4">
                <p class="small text-secondary mb-0" style="color: #64748b !important;">
                    &copy; <?= date('Y') ?> KKN Kelompok 12 Kelurahan Pal Lima. All Rights Reserved.
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- ===== Modal Daftar Divisi Kerja ===== -->
<div class="modal fade" id="divisiModal" tabindex="-1" aria-labelledby="divisiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem; overflow: hidden;">
            <div class="modal-header border-0 bg-info text-dark py-3">
                <h5 class="modal-title fw-bold" id="divisiModalLabel">
                    <i class="bi bi-briefcase-fill me-2"></i> Divisi Kerja KKN Kelompok 12
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="row g-3">
                    <!-- Ketua KKN -->
                    <div class="col-md-6">
                        <div class="p-3 border-0 rounded-4 bg-white shadow-sm h-100">
                            <h6 class="fw-bold text-primary mb-2 d-flex align-items-center gap-2">
                                <i class="bi bi-person-fill-check fs-5 text-primary"></i> Pimpinan (Ketua KKN)
                            </h6>
                            <p class="mb-0 text-muted small" style="line-height: 1.4;">Memimpin, mengoordinasikan, dan bertanggung jawab penuh atas seluruh program kerja KKN Kelompok 12.</p>
                            <hr class="my-2 opacity-25">
                            <div class="fw-semibold text-dark">Rizki Tri Saputra</div>
                        </div>
                    </div>
                    <!-- Sekretaris -->
                    <div class="col-md-6">
                        <div class="p-3 border-0 rounded-4 bg-white shadow-sm h-100">
                            <h6 class="fw-bold text-primary mb-2 d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-text fs-5 text-primary"></i> Administrasi (Sekretaris)
                            </h6>
                            <p class="mb-0 text-muted small" style="line-height: 1.4;">Mengurus administrasi kelompok, surat-menyurat, arsip berkas, dan penyusunan laporan KKN.</p>
                            <hr class="my-2 opacity-25">
                            <div class="fw-semibold text-dark">Anggi Rahmawati</div>
                        </div>
                    </div>
                    <!-- Divisi Acara -->
                    <div class="col-md-6">
                        <div class="p-3 border-0 rounded-4 bg-white shadow-sm h-100">
                            <h6 class="fw-bold text-primary mb-2 d-flex align-items-center gap-2">
                                <i class="bi bi-calendar3 fs-5 text-primary"></i> Divisi Acara
                            </h6>
                            <p class="mb-0 text-muted small" style="line-height: 1.4;">Menyusun konsep agenda, rundown detail setiap kegiatan, dan memoderatori program kerja kelompok.</p>
                            <hr class="my-2 opacity-25">
                            <ul class="list-unstyled mb-0 fw-semibold text-dark small" style="line-height: 1.5;">
                                <li>• Virahmanda Abelia Ismaya</li>
                                <li>• Muhammad Fiqri Mahendra</li>
                            </ul>
                        </div>
                    </div>
                    <!-- Divisi Humas -->
                    <div class="col-md-6">
                        <div class="p-3 border-0 rounded-4 bg-white shadow-sm h-100">
                            <h6 class="fw-bold text-primary mb-2 d-flex align-items-center gap-2">
                                <i class="bi bi-chat-left-text fs-5 text-primary"></i> Divisi Hubungan Masyarakat (Humas)
                            </h6>
                            <p class="mb-0 text-muted small" style="line-height: 1.4;">Menghubungkan kelompok dengan perangkat desa, warga setempat, dan mengurus izin kegiatan.</p>
                            <hr class="my-2 opacity-25">
                            <ul class="list-unstyled mb-0 fw-semibold text-dark small" style="line-height: 1.5;">
                                <li>• Sebastianus Aditia</li>
                                <li>• Khairunisa Salsabila</li>
                            </ul>
                        </div>
                    </div>
                    <!-- Divisi PDD -->
                    <div class="col-md-6">
                        <div class="p-3 border-0 rounded-4 bg-white shadow-sm h-100">
                            <h6 class="fw-bold text-primary mb-2 d-flex align-items-center gap-2">
                                <i class="bi bi-camera fs-5 text-primary"></i> Divisi PDD (Publikasi, Dekorasi & Dokumentasi)
                            </h6>
                            <p class="mb-0 text-muted small" style="line-height: 1.4;">Mengambil dokumentasi foto/video, mendesain spanduk banner, dan mengelola media publikasi sosial.</p>
                            <hr class="my-2 opacity-25">
                            <ul class="list-unstyled mb-0 fw-semibold text-dark small" style="line-height: 1.5;">
                                <li>• Tiara Fitriani</li>
                                <li>• Siti Aliyyah</li>
                            </ul>
                        </div>
                    </div>
                    <!-- Divisi Perlengkapan -->
                    <div class="col-md-6">
                        <div class="p-3 border-0 rounded-4 bg-white shadow-sm h-100">
                            <h6 class="fw-bold text-primary mb-2 d-flex align-items-center gap-2">
                                <i class="bi bi-tools fs-5 text-primary"></i> Divisi Perlengkapan & Logistik
                            </h6>
                            <p class="mb-0 text-muted small" style="line-height: 1.4;">Menginventarisasi, meminjam, dan mendistribusikan perlengkapan logistik pendukung kegiatan lapangan.</p>
                            <hr class="my-2 opacity-25">
                            <ul class="list-unstyled mb-0 fw-semibold text-dark small" style="line-height: 1.5;">
                                <li>• Fathurrahman</li>
                                <li>• Halimah Tusa’diah</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 py-3 bg-light">
                <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== Modal Daftar Mahasiswa ===== -->
<div class="modal fade" id="mahasiswaModal" tabindex="-1" aria-labelledby="mahasiswaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem; overflow: hidden;">
            <div class="modal-header border-0 bg-primary text-white py-3">
                <h5 class="modal-title fw-bold" id="mahasiswaModalLabel">
                    <i class="bi bi-people-fill me-2"></i> Daftar Mahasiswa KKN Kelompok 12
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-0">
                <!-- DPL Highlight Banner -->
                <div class="p-3 bg-warning bg-opacity-10 border-bottom border-warning border-opacity-25 d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-warning text-dark p-2 d-flex align-items-center justify-content-center" style="width:42px; height:42px; flex-shrink:0;">
                        <i class="bi bi-person-workspace fs-5"></i>
                    </div>
                    <div>
                        <small class="text-uppercase fw-bold text-warning-emphasis d-block" style="font-size: 10px; letter-spacing:0.05em;">Dosen Pembimbing Lapangan (DPL)</small>
                        <span class="fw-bold text-dark fs-6">Ibu Kania Khairunnisa, M.Psi., Psikolog</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.95rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center py-3" style="width: 8%;">No</th>
                                <th class="py-3">Nama Lengkap</th>
                                <th class="py-3" style="width: 25%;">NIM</th>
                                <th class="py-3" style="width: 35%;">Program Studi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center fw-bold py-3">1</td>
                                <td class="fw-semibold">Rizki Tri Saputra</td>
                                <td class="text-monospace text-muted">231710035</td>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary py-2 px-3 rounded-pill">Ilmu Hukum</span></td>
                            </tr>
                            <tr>
                                <td class="text-center fw-bold py-3">2</td>
                                <td class="fw-semibold">Virahmanda Abelia Ismaya</td>
                                <td class="text-monospace text-muted">231510034</td>
                                <td><span class="badge bg-success bg-opacity-10 text-success py-2 px-3 rounded-pill">Ilmu Kesehatan Masyarakat</span></td>
                            </tr>
                            <tr>
                                <td class="text-center fw-bold py-3">3</td>
                                <td class="fw-semibold">Khairunisa Salsabila</td>
                                <td class="text-monospace text-muted">231510090</td>
                                <td><span class="badge bg-success bg-opacity-10 text-success py-2 px-3 rounded-pill">Ilmu Kesehatan Masyarakat</span></td>
                            </tr>
                            <tr>
                                <td class="text-center fw-bold py-3">4</td>
                                <td class="fw-semibold text-dark">Muhammad Fiqri Mahendra</td>
                                <td class="text-monospace text-muted">231220040</td>
                                <td><span class="badge py-2 px-3 rounded-pill" style="background-color: rgba(13, 202, 240, 0.15) !important; color: #055160 !important;">Teknik Informatika</span></td>
                            </tr>
                            <tr>
                                <td class="text-center fw-bold py-3">5</td>
                                <td class="fw-semibold">Siti Aliyyah</td>
                                <td class="text-monospace text-muted">231310057</td>
                                <td><span class="badge bg-warning bg-opacity-10 text-warning text-dark py-2 px-3 rounded-pill">Manajemen</span></td>
                            </tr>
                            <tr>
                                <td class="text-center fw-bold py-3">6</td>
                                <td class="fw-semibold">Halimah Tusa’diah</td>
                                <td class="text-monospace text-muted">231210140</td>
                                <td><span class="badge bg-warning bg-opacity-10 text-warning text-dark py-2 px-3 rounded-pill">Manajemen</span></td>
                            </tr>
                            <tr>
                                <td class="text-center fw-bold py-3">7</td>
                                <td class="fw-semibold">Anggi Rahmawati</td>
                                <td class="text-monospace text-muted">231310246</td>
                                <td><span class="badge bg-warning bg-opacity-10 text-warning text-dark py-2 px-3 rounded-pill">Manajemen</span></td>
                            </tr>
                            <tr>
                                <td class="text-center fw-bold py-3">8</td>
                                <td class="fw-semibold">Tiara Fitriani</td>
                                <td class="text-monospace text-muted">231310273</td>
                                <td><span class="badge bg-warning bg-opacity-10 text-warning text-dark py-2 px-3 rounded-pill">Manajemen</span></td>
                            </tr>
                            <tr>
                                <td class="text-center fw-bold py-3">9</td>
                                <td class="fw-semibold">Fathurrahman</td>
                                <td class="text-monospace text-muted">231810019</td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-dark py-2 px-3 rounded-pill" style="color: #6f42c1 !important; background-color: rgba(111, 66, 193, 0.1) !important;">Psikologi</span></td>
                            </tr>
                            <tr>
                                <td class="text-center fw-bold py-3">10</td>
                                <td class="fw-semibold">Sebastianus Aditia</td>
                                <td class="text-monospace text-muted">231810120</td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-dark py-2 px-3 rounded-pill" style="color: #6f42c1 !important; background-color: rgba(111, 66, 193, 0.1) !important;">Psikologi</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 py-3">
                <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== Modal Login Buku Lapangan ===== -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-login-custom">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="login-brand-wrapper">
                    <div class="login-avatar-container">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-1">Login Anggota KKN</h4>
                    <p class="text-muted small px-3">Buku Lapangan hanya bisa diakses oleh anggota KKN Kelompok 12. Silakan masuk terlebih dahulu.</p>
                </div>

                <div id="loginAlert" class="alert alert-custom mb-3 d-none">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span id="loginAlertText"></span>
                </div>

                <form id="loginForm">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group-custom">
                            <span class="input-group-icon"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required autocomplete="username" placeholder="Masukkan username">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Kata Sandi</label>
                        <div class="input-group-custom">
                            <span class="input-group-icon"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password" placeholder="Masukkan kata sandi">
                            <button class="btn-toggle-pw" type="button" id="togglePassword"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login-gradient w-100" id="loginSubmitBtn">
                        <span id="loginBtnText">Masuk Sekarang</span>
                        <span id="loginBtnSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                    </button>
                </form>

                <div class="forgot-password-link">
                    Lupa kata sandi? Hubungi <span class="fw-semibold text-dark">Ketua / Sekretaris KKN</span>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Bootstrap JS (WAJIB ada supaya navbar toggle, carousel, dan modal login jalan) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function () {
    const form = document.getElementById('loginForm');
    const alertBox = document.getElementById('loginAlert');
    const btnText = document.getElementById('loginBtnText');
    const btnSpinner = document.getElementById('loginBtnSpinner');
    const submitBtn = document.getElementById('loginSubmitBtn');

    document.getElementById('togglePassword').addEventListener('click', function () {
        const pw = document.getElementById('password');
        const icon = this.querySelector('i');
        if (pw.type === 'password') {
            pw.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            pw.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        alertBox.classList.add('d-none');
        submitBtn.disabled = true;
        btnText.textContent = 'Memproses...';
        btnSpinner.classList.remove('d-none');

        const formData = new FormData(form);

        fetch('login.php', { method: 'POST', body: formData })
    .then(res => res.text().then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch (err) {
            throw new Error('Server tidak merespons dengan format yang benar. Pastikan halaman dibuka lewat http://localhost, bukan Live Server.');
        }
        return data;
    }))
    .then(data => {
        if (data.success) {
            btnText.textContent = 'Berhasil, mengalihkan...';
            window.location.href = data.redirect || 'buku-lapangan-kkn.html';
        } else {
            const alertText = document.getElementById('loginAlertText');
            if (alertText) {
                alertText.textContent = data.message || 'Username atau password salah.';
            } else {
                alertBox.textContent = data.message || 'Username atau password salah.';
            }
            alertBox.classList.remove('d-none');
            submitBtn.disabled = false;
            btnText.textContent = 'Masuk Sekarang';
            btnSpinner.classList.add('d-none');
        }
    })
   .catch((err) => {
        const alertText = document.getElementById('loginAlertText');
        if (alertText) {
            alertText.textContent = err.message || 'Terjadi kesalahan koneksi. Coba lagi.';
        } else {
            alertBox.textContent = err.message || 'Terjadi kesalahan koneksi. Coba lagi.';
        }
        alertBox.classList.remove('d-none');
        submitBtn.disabled = false;
        btnText.textContent = 'Masuk Sekarang';
        btnSpinner.classList.add('d-none');
    });
    });
    window.quickLogin = function (username) {
        document.getElementById('username').value = username;
        document.getElementById('password').value = 'kkn2026';
        submitBtn.click();
    };
})();
</script>

<!-- Mapbox -->
<script src="https://api.mapbox.com/mapbox-gl-js/v3.15.0/mapbox-gl.js"></script>

<!-- Script Peta -->
<script>
mapboxgl.accessToken = 'pk.eyJ1IjoibWFoZW5kcmFsYWJzIiwiYSI6ImNtcmx6eG1pcTA0c2cyenM2eWk2dnR4ZGkifQ.CmCFE_l3hWEeBPMQfRNsYQ';

const map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/mapbox/satellite-streets-v12',
    center: [109.287023, -0.037462], // Kantor Lurah Pal Lima
    zoom: 16,
    pitch: 65,
    bearing: -20,
    antialias: true
});

map.addControl(new mapboxgl.NavigationControl());

const popup = new mapboxgl.Popup({
    offset: 25
}).setHTML(`
    <h5 class="fw-bold mb-1" style="font-size: 14px;">Kantor Lurah Pal Lima</h5>
    <p class="text-muted mb-2" style="font-size: 12px; line-height: 1.3;">Kecamatan Pontianak Barat, Kota Pontianak</p>
    <a href="https://www.google.com/maps?q=-0.037462,109.287023" target="_blank" class="btn btn-primary btn-sm text-white py-1 px-2" style="font-size: 11px; border-radius: 6px;">
        📍 Buka di Google Maps
    </a>
`);

new mapboxgl.Marker({
    color: "red"
})
    .setLngLat([109.287023, -0.037462])
    .setPopup(popup)
    .addTo(map);

map.on('load', () => {
    if (map.setFog) {
        map.setFog({});
    }

    const layers = map.getStyle().layers;
    let labelLayerId;
    for (const layer of layers) {
        if (layer.type === "symbol" && layer.layout && layer.layout["text-field"]) {
            labelLayerId = layer.id;
            break;
        }
    }

    map.addLayer({
        id: '3d-buildings',
        source: 'composite',
        'source-layer': 'building',
        filter: ['==', 'extrude', 'true'],
        type: 'fill-extrusion',
        minzoom: 15,
        paint: {
            'fill-extrusion-color': '#cfcfcf',
            'fill-extrusion-height': [
                'interpolate',
                ['linear'],
                ['zoom'],
                15,
                0,
                16,
                ['get', 'height']
            ],
            'fill-extrusion-base': [
                'interpolate',
                ['linear'],
                ['zoom'],
                15,
                0,
                16,
                ['get', 'min_height']
            ],
            'fill-extrusion-opacity': 0.8
        }
    }, labelLayerId);
});
</script>
<script>
// ===== Scrollspy for Navbar Links =====
document.addEventListener('DOMContentLoaded', () => {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.navbar-premium .nav-link');

    window.addEventListener('scroll', () => {
        let currentSectionId = '';
        const scrollPosition = window.scrollY + 120; // offset for navbar height + margins

        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.offsetHeight;
            if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                currentSectionId = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${currentSectionId}`) {
                link.classList.add('active');
            }
        });
    });
});
</script>

<!-- Script Countdown Durasi Pengabdian KKN -->
<script>
(function() {
    // Tanggal Mulai dan Selesai KKN (Zona Waktu Pontianak / WIB: GMT+7)
    const startDate = new Date('2026-07-20T00:00:00+07:00').getTime();
    const endDate = new Date('2026-08-30T23:59:59+07:00').getTime();

    const timerStatus = document.getElementById('timerStatus');
    const dVal = document.getElementById('timerDays');
    const hVal = document.getElementById('timerHours');
    const mVal = document.getElementById('timerMinutes');
    const sVal = document.getElementById('timerSeconds');

    function updateTimer() {
        const now = new Date().getTime();

        let targetDate;
        if (now < startDate) {
            targetDate = startDate;
            if (timerStatus) timerStatus.textContent = 'Mulai Pengabdian (Countdown)';
        } else if (now >= startDate && now <= endDate) {
            targetDate = endDate;
            if (timerStatus) timerStatus.textContent = 'Sisa Waktu Pengabdian';
        } else {
            targetDate = null;
            if (timerStatus) timerStatus.textContent = 'Pengabdian Selesai!';
            if (dVal) dVal.textContent = '00';
            if (hVal) hVal.textContent = '00';
            if (mVal) mVal.textContent = '00';
            if (sVal) sVal.textContent = '00';
            return;
        }

        const distance = targetDate - now;

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        if (dVal) dVal.textContent = String(days).padStart(2, '0');
        if (hVal) hVal.textContent = String(hours).padStart(2, '0');
        if (mVal) mVal.textContent = String(minutes).padStart(2, '0');
        if (sVal) sVal.textContent = String(seconds).padStart(2, '0');
    }

    // Jalankan pertama kali dan buat interval berjalan setiap detik
    updateTimer();
    setInterval(updateTimer, 1000);
})();
</script>
<!-- MODAL UPLOAD / EDIT MATERI PRESENTASI -->
<div class="modal fade" id="modalUploadMateri" tabindex="-1" aria-labelledby="modalUploadMateriLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header text-white border-0 py-3" style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);">
                <h5 class="modal-header-title fw-bold mb-0 text-white" id="modalUploadMateriLabel" style="font-size: 1.1rem;">
                    <i class="bi bi-cloud-arrow-up-fill me-2" id="modalIconMateri"></i><span id="modalTitleMateri">Upload Materi Presentasi</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formUploadMateri" enctype="multipart/form-data">
                <input type="hidden" id="materi_id" name="id" value="">
                <div class="modal-body p-4">
                    <div id="uploadAlert" class="alert d-none py-2 px-3 mb-3 rounded-3" style="font-size: 0.88rem;"></div>

                    <!-- Pilih Pemateri (Diambil dari Tim & Struktur Organisasi) -->
                    <div class="mb-3">
                        <label for="pemateri_nama" class="form-label fw-semibold text-dark small">Pemateri / Anggota Tim <span class="text-danger">*</span></label>
                        <div class="d-flex align-items-center mb-2">
                            <img id="previewFotoPemateri" src="img/rizki tri saputra.jpg" class="rounded-circle me-3 object-fit-cover shadow-sm" style="width: 45px; height: 45px; border: 2px solid #4f46e5;" alt="Foto Pemateri">
                            <select class="form-select rounded-3 shadow-none border-secondary border-opacity-25" id="pemateri_nama" name="pemateri_nama" required onchange="updatePemateriPreview(this.value)">
                                <option value="Rizki Tri Saputra" data-foto="img/rizki tri saputra.jpg">Rizki Tri Saputra (Ketua KKN)</option>
                                <option value="Anggi Rahmawati" data-foto="img/Anggi Rahmawati.jpg">Anggi Rahmawati (Sekretaris)</option>
                                <option value="Virahmanda Abelia Ismaya" data-foto="img/Virahmanda Abelia Ismaya.jpg">Virahmanda Abelia Ismaya (Divisi Acara)</option>
                                <option value="Muhammad Fiqri Mahendra" data-foto="img/Muhammad Fiqri Mahendra.jpg">Muhammad Fiqri Mahendra (Divisi Acara)</option>
                                <option value="Sebastianus Aditia" data-foto="img/Sebastianus Aditia.jpg">Sebastianus Aditia (Divisi Humas)</option>
                                <option value="Khairunisa Salsabila" data-foto="img/Khairunisa Salsabila.jpg">Khairunisa Salsabila (Divisi Humas)</option>
                                <option value="Tiara Fitriani" data-foto="img/Tiara Fitriani.jpg">Tiara Fitriani (Divisi PDD)</option>
                                <option value="Siti Aliyah" data-foto="img/Siti Aliyah.jpg">Siti Aliyah (Divisi PDD)</option>
                                <option value="Fathurrahman" data-foto="img/Fathurrahman.jpg">Fathurrahman (Divisi Perlengkapan)</option>
                                <option value="Halimah Tusa'Diah" data-foto="img/Halimah Tusa'Diah.jpg">Halimah Tusa'Diah (Divisi Perlengkapan)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Nama Kelompok / Divisi -->
                    <div class="mb-3">
                        <label for="nama_kelompok" class="form-label fw-semibold text-dark small">Nama Kelompok / Divisi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3 shadow-none border-secondary border-opacity-25" id="nama_kelompok" name="nama_kelompok" placeholder="Contoh: Divisi Acara / KKN Kelompok 12" required>
                    </div>

                    <!-- Judul Materi Presentasi -->
                    <div class="mb-3">
                        <label for="judul_materi" class="form-label fw-semibold text-dark small">Judul Materi Presentasi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3 shadow-none border-secondary border-opacity-25" id="judul_materi" name="judul_materi" placeholder="Masukkan judul slide presentasi / modul" required>
                    </div>

                    <!-- Tujuan Program -->
                    <div class="mb-3">
                        <label for="tujuan_program" class="form-label fw-semibold text-dark small"><i class="bi bi-bullseye me-1 text-primary"></i>Tujuan Program</label>
                        <textarea class="form-control rounded-3 shadow-none border-secondary border-opacity-25" id="tujuan_program" name="tujuan_program" rows="2" placeholder="Contoh: Memberikan pemahaman literasi keuangan digital..."></textarea>
                    </div>

                    <!-- Sasaran Program -->
                    <div class="mb-3">
                        <label for="sasaran_program" class="form-label fw-semibold text-dark small"><i class="bi bi-people-fill me-1 text-success"></i>Sasaran Program</label>
                        <textarea class="form-control rounded-3 shadow-none border-secondary border-opacity-25" id="sasaran_program" name="sasaran_program" rows="2" placeholder="Contoh: Ibu-ibu PKK dan Pelaku UMKM Kelurahan Pal Lima..."></textarea>
                    </div>

                    <!-- Dampak yang Diharapkan -->
                    <div class="mb-3">
                        <label for="dampak_program" class="form-label fw-semibold text-dark small"><i class="bi bi-graph-up-arrow me-1 text-warning"></i>Dampak yang Diharapkan</label>
                        <textarea class="form-control rounded-3 shadow-none border-secondary border-opacity-25" id="dampak_program" name="dampak_program" rows="2" placeholder="Contoh: Peningkatan omzet usaha dan kemandirian kelola usaha..."></textarea>
                    </div>

                    <!-- Program Kerja Terkait (Opsional) -->
                    <div class="mb-3">
                        <label for="program_kerja_id" class="form-label fw-semibold text-dark small">Program Kerja Terkait</label>
                        <select class="form-select rounded-3 shadow-none border-secondary border-opacity-25" id="program_kerja_id" name="program_kerja_id">
                            <option value="">-- Umum / Tanpa Kaitan Khusus --</option>
                            <?php foreach ($programKerjaRows as $pk): ?>
                                <option value="<?= $pk['id'] ?>"><?= htmlspecialchars($pk['judul']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- File Presentasi -->
                    <div class="mb-3">
                        <label for="file_materi" class="form-label fw-semibold text-dark small">File Berkas Presentasi <span id="lblFileReq" class="text-danger">*</span></label>
                        <input type="file" class="form-control rounded-3 shadow-none border-secondary border-opacity-25" id="file_materi" name="file_materi" accept=".pdf,.ppt,.pptx,.doc,.docx,.zip">
                        <div id="fileHelpText" class="form-text text-muted" style="font-size: 0.78rem;">Format: PDF, PPT, PPTX, DOCX, ZIP (Maks. 25MB).</div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal" style="font-weight: 500;">Batal</button>
                    <button type="submit" id="btnSubmitMateri" class="btn btn-primary rounded-pill px-4" style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); border: none; font-weight: 600;">
                        <span id="btnTextMateri"><i class="bi bi-cloud-upload me-1"></i> Simpan Berkas</span>
                        <span id="btnSpinnerMateri" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



<!-- MODAL POPUP KONFIRMASI HAPUS MATERI -->
<div class="modal fade" id="modalHapusMateri" tabindex="-1" aria-labelledby="modalHapusMateriLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden text-center p-3">
            <div class="modal-body pt-4">
                <div class="mb-3">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 65px; height: 65px;">
                        <i class="bi bi-trash3-fill fs-2"></i>
                    </div>
                </div>
                <h5 class="fw-bold text-dark mb-2">Hapus Materi?</h5>
                <p class="text-muted small mb-2">Apakah Anda yakin ingin menghapus materi <span id="namaMateriHapusText" class="fw-bold text-dark"></span>?</p>
                <div class="text-muted" style="font-size: 0.76rem;">Berkas fisik di server juga akan dihapus permanen.</div>
                <input type="hidden" id="idMateriHapus" value="">
            </div>
            <div class="d-flex gap-2 justify-content-center pb-3 px-3">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal" style="font-size: 0.88rem;">Batal</button>
                <button type="button" id="btnKonfirmasiHapus" onclick="eksekusiHapusMateri()" class="btn btn-danger rounded-pill px-4 fw-semibold" style="font-size: 0.88rem;">
                    <span id="btnTextHapus"><i class="bi bi-trash-fill me-1"></i> Ya, Hapus</span>
                    <span id="btnSpinnerHapus" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL UPLOAD / EDIT VIDEO KEGIATAN -->
<div class="modal fade" id="modalUploadVideo" tabindex="-1" aria-labelledby="modalUploadVideoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header text-white border-0 py-3" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                <h5 class="modal-header-title fw-bold mb-0 text-white" id="modalUploadVideoLabel" style="font-size: 1.1rem;">
                    <i class="bi bi-camera-video-fill me-2" id="modalIconVideo"></i><span id="modalTitleVideo">Upload Video Kegiatan</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formUploadVideo" enctype="multipart/form-data">
                <input type="hidden" id="video_id" name="id" value="">
                <div class="modal-body p-4">
                    <div id="uploadVideoAlert" class="alert d-none py-2 px-3 mb-3 rounded-3" style="font-size: 0.88rem;"></div>

                    <!-- Judul Video Kegiatan -->
                    <div class="mb-3">
                        <label for="judul_video" class="form-label fw-semibold text-dark small">Judul Video Kegiatan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3 shadow-none border-secondary border-opacity-25" id="judul_video" name="judul_video" placeholder="Contoh: Video Liputan Kerja Bakti Warga Pal Lima" required>
                    </div>

                    <!-- Pengunggah / Pemateri -->
                    <div class="mb-3">
                        <label for="pengunggah_nama" class="form-label fw-semibold text-dark small">Pengunggah / Anggota Tim <span class="text-danger">*</span></label>
                        <div class="d-flex align-items-center mb-2">
                            <img id="previewFotoPengunggah" src="img/rizki tri saputra.jpg" class="rounded-circle me-3 object-fit-cover shadow-sm" style="width: 45px; height: 45px; border: 2px solid #ef4444;" alt="Foto Pengunggah">
                            <select class="form-select rounded-3 shadow-none border-secondary border-opacity-25" id="pengunggah_nama" name="pengunggah_nama" required onchange="updatePengunggahPreview(this.value)">
                                <option value="Rizki Tri Saputra" data-foto="img/rizki tri saputra.jpg">Rizki Tri Saputra (Ketua KKN)</option>
                                <option value="Anggi Rahmawati" data-foto="img/Anggi Rahmawati.jpg">Anggi Rahmawati (Sekretaris)</option>
                                <option value="Virahmanda Abelia Ismaya" data-foto="img/Virahmanda Abelia Ismaya.jpg">Virahmanda Abelia Ismaya (Divisi Acara)</option>
                                <option value="Muhammad Fiqri Mahendra" data-foto="img/Muhammad Fiqri Mahendra.jpg">Muhammad Fiqri Mahendra (Divisi Acara)</option>
                                <option value="Sebastianus Aditia" data-foto="img/Sebastianus Aditia.jpg">Sebastianus Aditia (Divisi Humas)</option>
                                <option value="Khairunisa Salsabila" data-foto="img/Khairunisa Salsabila.jpg">Khairunisa Salsabila (Divisi Humas)</option>
                                <option value="Tiara Fitriani" data-foto="img/Tiara Fitriani.jpg">Tiara Fitriani (Divisi PDD)</option>
                                <option value="Siti Aliyah" data-foto="img/Siti Aliyah.jpg">Siti Aliyah (Divisi PDD)</option>
                                <option value="Fathurrahman" data-foto="img/Fathurrahman.jpg">Fathurrahman (Divisi Perlengkapan)</option>
                                <option value="Halimah Tusa'Diah" data-foto="img/Halimah Tusa'Diah.jpg">Halimah Tusa'Diah (Divisi Perlengkapan)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Program Kerja Terkait -->
                    <div class="mb-3">
                        <label for="video_program_kerja_id" class="form-label fw-semibold text-dark small">Program Kerja Terkait</label>
                        <select class="form-select rounded-3 shadow-none border-secondary border-opacity-25" id="video_program_kerja_id" name="program_kerja_id">
                            <option value="">-- Umum / Tanpa Kaitan Khusus --</option>
                            <?php foreach ($programKerjaRows as $pk): ?>
                                <option value="<?= $pk['id'] ?>"><?= htmlspecialchars($pk['judul']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Deskripsi Video -->
                    <div class="mb-3">
                        <label for="video_deskripsi" class="form-label fw-semibold text-dark small">Deskripsi Singkat</label>
                        <textarea class="form-control rounded-3 shadow-none border-secondary border-opacity-25" id="video_deskripsi" name="deskripsi" rows="2" placeholder="Catatan singkat mengenai isi dokumentasi video ini..."></textarea>
                    </div>

                    <!-- Sumber Video: Berkas File ATAU Link -->
                    <div class="p-3 bg-light rounded-3 border border-secondary border-opacity-10 mb-3">
                        <div class="fw-bold text-dark small mb-2"><i class="bi bi-film me-1 text-danger"></i>Sumber Video Kegiatan</div>
                        
                        <!-- Upload File Video -->
                        <div class="mb-3">
                            <label for="file_video" class="form-label fw-semibold text-dark small mb-1">1. Upload Berkas Video (MP4 / WebM / MOV)</label>
                            <input type="file" class="form-control rounded-3 shadow-none border-secondary border-opacity-25" id="file_video" name="file_video" accept="video/mp4,video/webm,video/quicktime,video/x-matroska,video/*">
                            <div class="form-text text-muted" style="font-size: 0.76rem;">Pilih berkas video dari perangkat Anda untuk diputar langsung di web.</div>
                        </div>

                        <div class="text-center text-muted fw-bold small my-2" style="font-size: 0.78rem;">— ATAU —</div>

                        <!-- Link Video Online -->
                        <div>
                            <label for="link_video" class="form-label fw-semibold text-dark small mb-1">2. Tautan / Link Video (YouTube, TikTok, IG, Drive)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 border-secondary border-opacity-25"><i class="bi bi-link-45deg text-primary fs-5"></i></span>
                                <input type="url" class="form-control rounded-end-3 shadow-none border-secondary border-opacity-25" id="link_video" name="link_video" placeholder="https://www.youtube.com/watch?v=... atau https://youtu.be/...">
                            </div>
                            <div id="videoFileHelpText" class="form-text text-muted" style="font-size: 0.76rem;">Mendukung YouTube, YouTube Shorts, Instagram Reel, TikTok, Google Drive, atau URL MP4.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal" style="font-weight: 500;">Batal</button>
                    <button type="submit" id="btnSubmitVideo" class="btn btn-danger rounded-pill px-4" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none; font-weight: 600;">
                        <span id="btnTextVideo"><i class="bi bi-save me-1"></i> Simpan Video</span>
                        <span id="btnSpinnerVideo" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH TITIK LOKASI PROKER PETA -->
<div class="modal fade" id="modalLokasiProkja" tabindex="-1" aria-labelledby="modalLokasiProkjaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header text-white border-0 py-3" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                <h5 class="modal-header-title fw-bold mb-0 text-white" id="modalLokasiProkjaLabel" style="font-size: 1.1rem;">
                    <i class="bi bi-geo-alt-fill me-2" id="modalIconLokasi"></i><span id="modalTitleLokasi">Tambah Titik Lokasi Program Kerja</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formUploadLokasi">
                <input type="hidden" id="lokasi_id" name="id" value="">
                <div class="modal-body p-4">
                    <div id="uploadLokasiAlert" class="alert d-none py-2 px-3 mb-3 rounded-3" style="font-size: 0.88rem;"></div>

                    <!-- Nama Lokasi -->
                    <div class="mb-3">
                        <label for="nama_lokasi" class="form-label fw-semibold text-dark small">Nama Lokasi / Tempat <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3 shadow-none border-secondary border-opacity-25" id="nama_lokasi" name="nama_lokasi" placeholder="Contoh: Posyandu Mawar / SDN 05 Pal Lima / Balai Desa" required>
                    </div>

                    <!-- Program Kerja Terkait -->
                    <div class="mb-3">
                        <label for="lokasi_program_kerja_id" class="form-label fw-semibold text-dark small">Program Kerja Terkait</label>
                        <select class="form-select rounded-3 shadow-none border-secondary border-opacity-25" id="lokasi_program_kerja_id" name="program_kerja_id">
                            <option value="">-- Tanpa Kaitan Khusus / Umum --</option>
                            <?php foreach ($programKerjaRows as $pk): ?>
                                <option value="<?= $pk['id'] ?>"><?= htmlspecialchars($pk['judul']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Kategori Lokasi -->
                    <div class="mb-3">
                        <label for="kategori_lokasi" class="form-label fw-semibold text-dark small">Kategori Lokasi</label>
                        <select class="form-select rounded-3 shadow-none border-secondary border-opacity-25" id="kategori_lokasi" name="kategori_lokasi">
                            <option value="Posyandu &amp; Kesehatan">Posyandu &amp; Kesehatan</option>
                            <option value="Sekolah &amp; Pendidikan">Sekolah &amp; Pendidikan</option>
                            <option value="Kerja Bakti &amp; Lingkungan">Kerja Bakti &amp; Lingkungan</option>
                            <option value="UMKM &amp; Ekonomi Kreatif">UMKM &amp; Ekonomi Kreatif</option>
                            <option value="Posko KKN">Posko KKN / Sekretariat</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>

                    <!-- Contoh Preset Tombol Cepat Puskesmas Pal Lima -->
                    <div class="mb-3 p-2 bg-info bg-opacity-10 rounded-3 border border-info border-opacity-25 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="small text-info-emphasis fw-semibold" style="font-size: 0.76rem;">
                            <i class="bi bi-lightbulb-fill text-warning me-1"></i>Contoh Cepat: Puskesmas Pal Lima
                        </div>
                        <button type="button" class="btn btn-xs btn-info rounded-pill px-3 py-1 text-white fw-bold shadow-none" style="font-size: 0.72rem; background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); border: none;" onclick="isiContohPuskesmasPalLima()">
                            <i class="bi bi-geo-alt-fill me-1"></i>Isi Contoh Puskesmas Pal Lima
                        </button>
                    </div>

                    <!-- Koordinat Lat & Lng -->
                    <div class="p-3 bg-light rounded-3 border border-secondary border-opacity-10 mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-1 flex-wrap gap-1">
                            <div class="fw-bold text-dark small"><i class="bi bi-pin-map-fill text-danger me-1"></i>Koordinat Geografis</div>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0.5" style="font-size: 0.72rem; font-weight: 600;" onclick="if(window.pilihTitikDariPeta) window.pilihTitikDariPeta();">
                                    <i class="bi bi-crosshair me-1"></i>Pilih dari Peta
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-danger rounded-pill px-2 py-0.5" style="font-size: 0.72rem; font-weight: 600;" onclick="kosongkanKoordinatLokasi()" title="Kosongkan nilai Lat & Lng">
                                    <i class="bi bi-trash-fill me-1"></i>Hapus Koordinat
                                </button>
                            </div>
                        </div>
                        <div class="form-text text-muted mb-2" style="font-size: 0.76rem;" id="koordinatPickInfo">
                            Masukkan koordinat Lat &amp; Lng dari Google Maps, atau klik tombol "Pilih dari Peta" / "Isi Contoh Puskesmas Pal Lima".
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <label for="lokasi_lat" class="form-label text-muted extra-small mb-1" style="font-size: 0.76rem;">Latitude (Lat) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-3 shadow-none border-secondary border-opacity-25" id="lokasi_lat" name="latitude" placeholder="-0.035421" required>
                            </div>
                            <div class="col-6">
                                <label for="lokasi_lng" class="form-label text-muted extra-small mb-1" style="font-size: 0.76rem;">Longitude (Lng) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-3 shadow-none border-secondary border-opacity-25" id="lokasi_lng" name="longitude" placeholder="109.295123" required>
                            </div>
                        </div>
                    </div>

                    <!-- Keterangan / Catatan -->
                    <div class="mb-2">
                        <label for="lokasi_keterangan" class="form-label fw-semibold text-dark small">Keterangan / Aktivitas di Lokasi</label>
                        <textarea class="form-control rounded-3 shadow-none border-secondary border-opacity-25" id="lokasi_keterangan" name="keterangan" rows="2" placeholder="Contoh: Lokasi pelaksanaan pemeriksaan gizi anak dan balita..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal" style="font-weight: 500;">Batal</button>
                    <button type="submit" id="btnSubmitLokasi" class="btn btn-danger rounded-pill px-4" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none; font-weight: 600;">
                        <span id="btnTextLokasi"><i class="bi bi-save me-1"></i> Simpan Titik Lokasi</span>
                        <span id="btnSpinnerLokasi" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL POPUP KONFIRMASI HAPUS LOKASI PROKER -->
<div class="modal fade" id="modalHapusLokasi" tabindex="-1" aria-labelledby="modalHapusLokasiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden text-center p-3">
            <div class="modal-body pt-4">
                <div class="mb-3">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 65px; height: 65px;">
                        <i class="bi bi-geo-fill fs-2"></i>
                    </div>
                </div>
                <h5 class="fw-bold text-dark mb-2">Hapus Titik Lokasi?</h5>
                <p class="text-muted small mb-2">Apakah Anda yakin ingin menghapus titik proker <span id="namaLokasiHapusText" class="fw-bold text-dark"></span> dari peta?</p>
                <input type="hidden" id="idLokasiHapus" value="">
            </div>
            <div class="d-flex gap-2 justify-content-center pb-3 px-3">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal" style="font-size: 0.88rem;">Batal</button>
                <button type="button" id="btnKonfirmasiHapusLokasi" onclick="eksekusiHapusLokasi()" class="btn btn-danger rounded-pill px-4 fw-semibold" style="font-size: 0.88rem;">
                    <span id="btnTextHapusLokasi"><i class="bi bi-trash-fill me-1"></i> Ya, Hapus</span>
                    <span id="btnSpinnerHapusLokasi" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL POPUP KONFIRMASI HAPUS VIDEO -->
<div class="modal fade" id="modalHapusVideo" tabindex="-1" aria-labelledby="modalHapusVideoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden text-center p-3">
            <div class="modal-body pt-4">
                <div class="mb-3">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 65px; height: 65px;">
                        <i class="bi bi-camera-video-off-fill fs-2"></i>
                    </div>
                </div>
                <h5 class="fw-bold text-dark mb-2">Hapus Video?</h5>
                <p class="text-muted small mb-2">Apakah Anda yakin ingin menghapus video <span id="namaVideoHapusText" class="fw-bold text-dark"></span>?</p>
                <div class="text-muted" style="font-size: 0.76rem;">Berkas video di server juga akan dihapus permanen.</div>
                <input type="hidden" id="idVideoHapus" value="">
            </div>
            <div class="d-flex gap-2 justify-content-center pb-3 px-3">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal" style="font-size: 0.88rem;">Batal</button>
                <button type="button" id="btnKonfirmasiHapusVideo" onclick="eksekusiHapusVideo()" class="btn btn-danger rounded-pill px-4 fw-semibold" style="font-size: 0.88rem;">
                    <span id="btnTextHapusVideo"><i class="bi bi-trash-fill me-1"></i> Ya, Hapus</span>
                    <span id="btnSpinnerHapusVideo" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL POPUP LOGIN ANGGOTA KKN -->
<div class="modal fade" id="modalLoginPop" tabindex="-1" aria-labelledby="modalLoginPopLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-login-custom">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="login-brand-wrapper">
                    <div class="login-avatar-container">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-1">Login Anggota KKN</h4>
                    <p class="text-muted small px-3">Akses ini hanya untuk anggota KKN Kelompok 12. Silakan masuk terlebih dahulu.</p>
                </div>

                <div id="loginPopAlert" class="alert alert-custom mb-3 d-none"></div>

                <form id="formLoginPop">
                    <div class="mb-3">
                        <label for="pop_username" class="form-label">Nama Lengkap / Username</label>
                        <div class="input-group-custom">
                            <span class="input-group-icon"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="pop_username" name="username" required autocomplete="username" placeholder="Masukkan username">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="pop_password" class="form-label">Password</label>
                        <div class="input-group-custom">
                            <span class="input-group-icon"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control" id="pop_password" name="password" required autocomplete="current-password" placeholder="Masukkan kata sandi">
                            <button class="btn-toggle-pw" type="button" onclick="const pw = document.getElementById('pop_password'); pw.type = pw.type === 'password' ? 'text' : 'password'; this.querySelector('i').classList.toggle('bi-eye'); this.querySelector('i').classList.toggle('bi-eye-slash');"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login-gradient w-100" id="btnSubmitLoginPop">
                        <span id="btnTextLoginPop"><i class="bi bi-box-arrow-in-right me-1"></i> Masuk Sekarang</span>
                        <span id="btnSpinnerLoginPop" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </form>

                <div class="forgot-password-link">
                    Lupa kata sandi? Hubungi <span class="fw-semibold text-dark">Ketua / Sekretaris KKN</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.IS_LOGGED_IN = <?= json_encode($isLoggedIn) ?>;
window.NAMA_USER_LOGIN = <?= json_encode($namaUserLogin) ?>;
var IS_LOGGED_IN = window.IS_LOGGED_IN;
var NAMA_USER_LOGIN = window.NAMA_USER_LOGIN;
let pendingActionCallback = null;

function selectLoginAvatar(nama, imgSrc, element) {
    document.querySelectorAll('#loginAvatarGrid .login-avatar-card').forEach(el => el.classList.remove('active'));
    if (element) element.classList.add('active');

    const inputName = document.getElementById('pop_username');
    if (inputName) inputName.value = nama;

    const previewImg = document.getElementById('selectedLoginAvatarImg');
    if (previewImg) {
        if (imgSrc) {
            previewImg.src = imgSrc;
            previewImg.style.display = 'inline-block';
        } else {
            previewImg.src = 'img/foto org.jpg';
            previewImg.style.display = 'inline-block';
        }
    }
}

async function parseJsonResponse(res) {
    const text = await res.text();
    try {
        return JSON.parse(text);
    } catch (e) {
        const cleanMsg = text.replace(/<[^>]*>?/gm, ' ').replace(/\s+/g, ' ').trim();
        throw new Error(cleanMsg || 'Server mengembalikan respons non-JSON.');
    }
}

function checkAuthGuard(callback) {
    if (!IS_LOGGED_IN) {
        pendingActionCallback = callback;
        const modalEl = document.getElementById('modalLoginPop');
        if (!modalEl) {
            alert('Anda harus login terlebih dahulu untuk melakukan aksi ini.\nSilakan refresh halaman dan login.');
            return false;
        }
        const alertBox = document.getElementById('loginPopAlert');
        if (alertBox) alertBox.className = 'alert d-none py-2 px-3 mb-3 rounded-3';
        // Bersihkan form login tiap kali popup dibuka
        const formLogin = document.getElementById('formLoginPop');
        if (formLogin) formLogin.reset();
        // Gunakan getOrCreateInstance agar tidak crash jika instance sudah ada
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
        return false;
    }
    return true;
}

let materiIdHapus = 0;
let videoIdHapus = 0;

function updatePemateriPreview(nama) {
    const select = document.getElementById('pemateri_nama');
    const selectedOption = select ? select.options[select.selectedIndex] : null;
    const foto = selectedOption ? (selectedOption.getAttribute('data-foto') || 'img/foto org.jpg') : 'img/foto org.jpg';
    document.getElementById('previewFotoPemateri').src = foto;
}

function updatePengunggahPreview(nama) {
    const select = document.getElementById('pengunggah_nama');
    const selectedOption = select ? select.options[select.selectedIndex] : null;
    const foto = selectedOption ? (selectedOption.getAttribute('data-foto') || 'img/foto org.jpg') : 'img/foto org.jpg';
    document.getElementById('previewFotoPengunggah').src = foto;
}

function bukaModalTambahMateri() {
    if (!checkAuthGuard(() => bukaModalTambahMateri())) return;

    const form = document.getElementById('formUploadMateri');
    if (form) form.reset();

    document.getElementById('materi_id').value = '';
    document.getElementById('modalTitleMateri').textContent = 'Upload Materi / Berkas Program Baru';
    document.getElementById('modalIconMateri').className = 'bi bi-cloud-arrow-up-fill me-2';
    document.getElementById('nama_kelompok').value = 'Divisi Acara';
    document.getElementById('tujuan_program').value = '';
    document.getElementById('sasaran_program').value = '';
    document.getElementById('dampak_program').value = '';
    document.getElementById('file_materi').required = true;
    document.getElementById('lblFileReq').style.display = 'inline';
    document.getElementById('fileHelpText').textContent = 'Format yang didukung: PDF, PPT, PPTX, DOCX, ZIP (Maks. 25MB).';
    document.getElementById('uploadAlert').className = 'alert d-none py-2 px-3 mb-3 rounded-3';

    if (NAMA_USER_LOGIN) {
        const selPemateri = document.getElementById('pemateri_nama');
        if (selPemateri) selPemateri.value = NAMA_USER_LOGIN;
    }

    updatePemateriPreview(document.getElementById('pemateri_nama').value);
    
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalUploadMateri'));
    modal.show();
}

function editMateriBtn(btn) {
    if (!checkAuthGuard(() => editMateriBtn(btn))) return;

    const id = btn.getAttribute('data-id');
    const judul = btn.getAttribute('data-judul') || '';
    const kelompok = btn.getAttribute('data-kelompok') || '';
    const pemateri = btn.getAttribute('data-pemateri') || '';
    const prokja = btn.getAttribute('data-prokja') || '';
    const tujuan = btn.getAttribute('data-tujuan') || '';
    const sasaran = btn.getAttribute('data-sasaran') || '';
    const dampak = btn.getAttribute('data-dampak') || '';

    document.getElementById('materi_id').value = id;
    document.getElementById('modalTitleMateri').textContent = 'Edit Berkas & Materi Program';
    document.getElementById('modalIconMateri').className = 'bi bi-pencil-square me-2';

    document.getElementById('pemateri_nama').value = pemateri;
    document.getElementById('nama_kelompok').value = kelompok;
    document.getElementById('judul_materi').value = judul;
    document.getElementById('tujuan_program').value = tujuan;
    document.getElementById('sasaran_program').value = sasaran;
    document.getElementById('dampak_program').value = dampak;
    document.getElementById('program_kerja_id').value = prokja;

    document.getElementById('file_materi').required = false;
    document.getElementById('lblFileReq').style.display = 'none';
    document.getElementById('fileHelpText').textContent = 'Kosongkan jika tidak ingin mengganti file. Format: PDF, PPT, PPTX, DOCX, ZIP (Maks. 25MB).';
    document.getElementById('uploadAlert').className = 'alert d-none py-2 px-3 mb-3 rounded-3';

    updatePemateriPreview(pemateri);

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalUploadMateri'));
    modal.show();
}

function konfirmasiHapusMateriBtn(btn) {
    if (!checkAuthGuard(() => konfirmasiHapusMateriBtn(btn))) return;

    const id = btn.getAttribute('data-id');
    const judul = btn.getAttribute('data-judul');

    materiIdHapus = id;
    document.getElementById('idMateriHapus').value = id;
    document.getElementById('namaMateriHapusText').textContent = judul ? `"${judul}"` : 'ini';

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalHapusMateri'));
    modal.show();
}

function eksekusiHapusMateri() {
    const id = materiIdHapus || document.getElementById('idMateriHapus').value;
    if (!id) return;

    const btnHapus = document.getElementById('btnKonfirmasiHapus');
    const btnText = document.getElementById('btnTextHapus');
    const btnSpinner = document.getElementById('btnSpinnerHapus');

    btnHapus.disabled = true;
    btnText.classList.add('d-none');
    btnSpinner.classList.remove('d-none');

    const formData = new FormData();
    formData.append('id', id);

    fetch('proses_materi_presentasi.php?action=delete', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(parseJsonResponse)
    .then(data => {
        btnHapus.disabled = false;
        btnText.classList.remove('d-none');
        btnSpinner.classList.add('d-none');

        const modalEl = document.getElementById('modalHapusMateri');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();

        if (data.success) {
            const card = document.getElementById('materi-card-' + id);
            if (card) {
                card.style.transition = 'all 0.4s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.8)';
                setTimeout(() => card.remove(), 400);
            } else {
                window.location.reload();
            }
        } else {
            // Jika ditolak karena belum login, tampilkan popup login
            if (data.message && data.message.toLowerCase().includes('login')) {
                if (modalInstance) modalInstance.hide();
                checkAuthGuard(() => eksekusiHapusMateri());
            } else {
                alert(data.message || 'Gagal menghapus materi.');
            }
        }
    })
    .catch(err => {
        btnHapus.disabled = false;
        btnText.classList.remove('d-none');
        btnSpinner.classList.add('d-none');
        alert('Terjadi kesalahan: ' + err.message);
    });
}

function bukaModalTambahVideo() {
    if (!checkAuthGuard(() => bukaModalTambahVideo())) return;

    const form = document.getElementById('formUploadVideo');
    if (form) form.reset();

    document.getElementById('video_id').value = '';
    document.getElementById('modalTitleVideo').textContent = 'Upload Video Kegiatan';
    document.getElementById('modalIconVideo').className = 'bi bi-camera-video-fill me-2 text-primary';
    document.getElementById('link_video').value = '';
    const fileVid = document.getElementById('file_video');
    if (fileVid) fileVid.value = '';
    document.getElementById('uploadVideoAlert').className = 'alert d-none py-2 px-3 mb-3 rounded-3';

    if (NAMA_USER_LOGIN) {
        const selPengunggah = document.getElementById('pengunggah_nama');
        if (selPengunggah) selPengunggah.value = NAMA_USER_LOGIN;
    }

    updatePengunggahPreview(document.getElementById('pengunggah_nama').value);

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalUploadVideo'));
    modal.show();
}

function editVideoBtn(btn) {
    if (!checkAuthGuard(() => editVideoBtn(btn))) return;

    const id = btn.getAttribute('data-id');
    const judul = btn.getAttribute('data-judul') || '';
    const deskripsi = btn.getAttribute('data-deskripsi') || '';
    const pengunggah = btn.getAttribute('data-pengunggah') || '';
    const prokja = btn.getAttribute('data-prokja') || '';
    const link = btn.getAttribute('data-link') || '';

    document.getElementById('video_id').value = id;
    document.getElementById('modalTitleVideo').textContent = 'Edit Video Dokumentasi';
    document.getElementById('modalIconVideo').className = 'bi bi-pencil-square me-2 text-warning';

    document.getElementById('judul_video').value = judul;
    document.getElementById('video_deskripsi').value = deskripsi;
    document.getElementById('pengunggah_nama').value = pengunggah;
    document.getElementById('video_program_kerja_id').value = prokja;
    document.getElementById('link_video').value = link;
    const fileVid = document.getElementById('file_video');
    if (fileVid) fileVid.value = '';

    document.getElementById('uploadVideoAlert').className = 'alert d-none py-2 px-3 mb-3 rounded-3';

    updatePengunggahPreview(pengunggah);

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalUploadVideo'));
    modal.show();
}

function konfirmasiHapusVideoBtn(btn) {
    if (!checkAuthGuard(() => konfirmasiHapusVideoBtn(btn))) return;

    const id = btn.getAttribute('data-id');
    const judul = btn.getAttribute('data-judul');

    videoIdHapus = id;
    document.getElementById('idVideoHapus').value = id;
    document.getElementById('namaVideoHapusText').textContent = judul ? `"${judul}"` : 'ini';

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalHapusVideo'));
    modal.show();
}

function eksekusiHapusVideo() {
    const id = videoIdHapus || document.getElementById('idVideoHapus').value;
    if (!id) return;

    const btnHapus = document.getElementById('btnKonfirmasiHapusVideo');
    const btnText = document.getElementById('btnTextHapusVideo');
    const btnSpinner = document.getElementById('btnSpinnerHapusVideo');

    btnHapus.disabled = true;
    btnText.classList.add('d-none');
    btnSpinner.classList.remove('d-none');

    const formData = new FormData();
    formData.append('id', id);

    fetch('proses_video_kegiatan.php?action=delete', {
        method: 'POST',
        body: formData
    })
    .then(parseJsonResponse)
    .then(data => {
        btnHapus.disabled = false;
        btnText.classList.remove('d-none');
        btnSpinner.classList.add('d-none');

        const modalEl = document.getElementById('modalHapusVideo');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();

        if (data.success) {
            const card = document.getElementById('video-card-' + id);
            if (card) {
                card.style.transition = 'all 0.4s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.8)';
                setTimeout(() => card.remove(), 400);
            } else {
                window.location.reload();
            }
        } else {
            alert(data.message || 'Gagal menghapus video.');
        }
    })
    .catch(err => {
        btnHapus.disabled = false;
        btnText.classList.remove('d-none');
        btnSpinner.classList.add('d-none');
        alert('Terjadi kesalahan: ' + err.message);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Form Login In-Page
    const formLogin = document.getElementById('formLoginPop');
    if (formLogin) {
        formLogin.addEventListener('submit', function(e) {
            e.preventDefault();
            const alertBox = document.getElementById('loginPopAlert');
            const btnSubmit = document.getElementById('btnSubmitLoginPop');
            const btnText = document.getElementById('btnTextLoginPop');
            const btnSpinner = document.getElementById('btnSpinnerLoginPop');

            alertBox.className = 'alert d-none py-2 px-3 mb-3 rounded-3';
            btnSubmit.disabled = true;
            btnText.classList.add('d-none');
            btnSpinner.classList.remove('d-none');

            const formData = new FormData(formLogin);

            fetch('login.php', {
                method: 'POST',
                body: formData
            })
            .then(parseJsonResponse)
            .then(data => {
                btnSubmit.disabled = false;
                btnText.classList.remove('d-none');
                btnSpinner.classList.add('d-none');

                if (data.success) {
                    IS_LOGGED_IN = true;
                    if (data.nama) NAMA_USER_LOGIN = data.nama;

                    const modalEl = document.getElementById('modalLoginPop');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) modalInstance.hide();

                    alertBox.className = 'alert alert-success py-2 px-3 mb-3 rounded-3';
                    alertBox.textContent = 'Login berhasil!';

                    // Update tampilan navbar: sembunyikan tombol Login, tampilkan nama user
                    const navLoginBtn = document.getElementById('navLoginBtn');
                    const navUserInfo = document.getElementById('navUserInfo');
                    if (navLoginBtn) navLoginBtn.style.display = 'none';
                    if (navUserInfo) {
                        navUserInfo.style.display = 'flex';
                        if (NAMA_USER_LOGIN) {
                            navUserInfo.querySelectorAll('.nav-nama-user').forEach(el => el.textContent = NAMA_USER_LOGIN);
                        }
                    }

                    // Eksekusi aksi pending atau reload halaman index.php agar tetap di halaman ini
                    if (typeof pendingActionCallback === 'function') {
                        const actionToRun = pendingActionCallback;
                        pendingActionCallback = null;
                        setTimeout(() => actionToRun(), 350);
                    } else {
                        setTimeout(() => location.reload(), 350);
                    }
                } else {
                    alertBox.className = 'alert alert-danger py-2 px-3 mb-3 rounded-3';
                    alertBox.textContent = data.message || 'Username atau password salah.';
                }
            })
            .catch(err => {
                btnSubmit.disabled = false;
                btnText.classList.remove('d-none');
                btnSpinner.classList.add('d-none');

                alertBox.className = 'alert alert-danger py-2 px-3 mb-3 rounded-3';
                alertBox.textContent = 'Terjadi kesalahan sistem: ' + err.message;
            });
        });
    }

    // Form Materi
    const formMateri = document.getElementById('formUploadMateri');
    if (formMateri) {
        formMateri.addEventListener('submit', function(e) {
            e.preventDefault();
            const alertBox = document.getElementById('uploadAlert');
            const btnSubmit = document.getElementById('btnSubmitMateri');
            const btnText = document.getElementById('btnTextMateri');
            const btnSpinner = document.getElementById('btnSpinnerMateri');

            alertBox.className = 'alert d-none py-2 px-3 mb-3 rounded-3';
            btnSubmit.disabled = true;
            btnText.classList.add('d-none');
            btnSpinner.classList.remove('d-none');

            const formData = new FormData(formMateri);

            fetch('proses_materi_presentasi.php?action=save', {
                method: 'POST',
                body: formData
            })
            .then(parseJsonResponse)
            .then(data => {
                btnSubmit.disabled = false;
                btnText.classList.remove('d-none');
                btnSpinner.classList.add('d-none');

                if (data.success) {
                    alertBox.className = 'alert alert-success py-2 px-3 mb-3 rounded-3';
                    alertBox.textContent = data.message || 'Materi presentasi berhasil disimpan!';
                    setTimeout(() => {
                        window.location.reload();
                    }, 800);
                } else {
                    alertBox.className = 'alert alert-danger py-2 px-3 mb-3 rounded-3';
                    alertBox.textContent = data.message || 'Gagal menyimpan data.';
                }
            })
            .catch(err => {
                btnSubmit.disabled = false;
                btnText.classList.remove('d-none');
                btnSpinner.classList.add('d-none');

                alertBox.className = 'alert alert-danger py-2 px-3 mb-3 rounded-3';
                alertBox.textContent = 'Terjadi kesalahan sistem: ' + err.message;
            });
        });
    }

    // Form Video
    const formVideo = document.getElementById('formUploadVideo');
    if (formVideo) {
        formVideo.addEventListener('submit', function(e) {
            e.preventDefault();
            const alertBox = document.getElementById('uploadVideoAlert');
            const btnSubmit = document.getElementById('btnSubmitVideo');
            const btnText = document.getElementById('btnTextVideo');
            const btnSpinner = document.getElementById('btnSpinnerVideo');

            alertBox.className = 'alert d-none py-2 px-3 mb-3 rounded-3';
            btnSubmit.disabled = true;
            btnText.classList.add('d-none');
            btnSpinner.classList.remove('d-none');

            const formData = new FormData(formVideo);

            fetch('proses_video_kegiatan.php?action=save', {
                method: 'POST',
                body: formData
            })
            .then(parseJsonResponse)
            .then(data => {
                btnSubmit.disabled = false;
                btnText.classList.remove('d-none');
                btnSpinner.classList.add('d-none');

                if (data.success) {
                    alertBox.className = 'alert alert-success py-2 px-3 mb-3 rounded-3';
                    alertBox.textContent = data.message || 'Video kegiatan berhasil disimpan!';
                    setTimeout(() => {
                        window.location.reload();
                    }, 800);
                } else {
                    alertBox.className = 'alert alert-danger py-2 px-3 mb-3 rounded-3';
                    alertBox.textContent = data.message || 'Gagal menyimpan video.';
                }
            })
            .catch(err => {
                btnSubmit.disabled = false;
                btnText.classList.remove('d-none');
                btnSpinner.classList.add('d-none');

                alertBox.className = 'alert alert-danger py-2 px-3 mb-3 rounded-3';
                alertBox.textContent = 'Terjadi kesalahan sistem: ' + err.message;
            });
        });
    }

    // Form Lokasi Prokja
    const formLokasi = document.getElementById('formUploadLokasi');
    if (formLokasi) {
        // Auto split jika user memasukkan "lat, lng" dalam satu field
        const latInput = document.getElementById('lokasi_lat');
        const lngInput = document.getElementById('lokasi_lng');
        [latInput, lngInput].forEach(inp => {
            if (!inp) return;
            inp.addEventListener('paste', function(e) {
                setTimeout(() => {
                    const val = inp.value.trim();
                    if (val.includes(',')) {
                        const parts = val.split(',');
                        if (latInput) latInput.value = parts[0].trim();
                        if (lngInput) lngInput.value = parts[1].trim();
                    }
                }, 10);
            });
        });

        formLokasi.addEventListener('submit', function(e) {
            e.preventDefault();
            const alertBox = document.getElementById('uploadLokasiAlert');
            const btnSubmit = document.getElementById('btnSubmitLokasi');
            const btnText = document.getElementById('btnTextLokasi');
            const btnSpinner = document.getElementById('btnSpinnerLokasi');

            alertBox.className = 'alert d-none py-2 px-3 mb-3 rounded-3';
            btnSubmit.disabled = true;
            btnText.classList.add('d-none');
            btnSpinner.classList.remove('d-none');

            const formData = new FormData(formLokasi);
            const savedLat = parseFloat(formData.get('latitude'));
            const savedLng = parseFloat(formData.get('longitude'));

            fetch('proses_lokasi_prokja.php?action=save', {
                method: 'POST',
                body: formData
            })
            .then(parseJsonResponse)
            .then(data => {
                btnSubmit.disabled = false;
                btnText.classList.remove('d-none');
                btnSpinner.classList.add('d-none');

                if (data.success) {
                    alertBox.className = 'alert alert-success py-2 px-3 mb-3 rounded-3';
                    alertBox.textContent = data.message || 'Titik lokasi proker berhasil disimpan!';
                    setTimeout(() => {
                        const modalEl = document.getElementById('modalLokasiProkja');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) modalInstance.hide();
                        if (window.loadLokasiProkja) {
                            window.loadLokasiProkja({ lat: savedLat, lng: savedLng });
                        }
                    }, 500);
                } else {
                    alertBox.className = 'alert alert-danger py-2 px-3 mb-3 rounded-3';
                    alertBox.textContent = data.message || 'Gagal menyimpan titik lokasi.';
                }
            })
            .catch(err => {
                btnSubmit.disabled = false;
                btnText.classList.remove('d-none');
                btnSpinner.classList.add('d-none');

                alertBox.className = 'alert alert-danger py-2 px-3 mb-3 rounded-3';
                alertBox.textContent = 'Terjadi kesalahan sistem: ' + err.message;
            });
        });
    }
});

let lokasiIdHapus = 0;

function bukaModalTambahLokasi() {
    if (!checkAuthGuard(() => bukaModalTambahLokasi())) return;

    const form = document.getElementById('formUploadLokasi');
    if (form) form.reset();

    document.getElementById('lokasi_id').value = '';
    document.getElementById('modalTitleLokasi').textContent = 'Tambah Titik Lokasi Program Kerja';
    document.getElementById('modalIconLokasi').className = 'bi bi-geo-alt-fill me-2 text-primary';
    document.getElementById('uploadLokasiAlert').className = 'alert d-none py-2 px-3 mb-3 rounded-3';
    document.getElementById('koordinatPickInfo').innerHTML = 'Masukkan koordinat Lat &amp; Lng dari Google Maps, atau klik langsung lokasi pada peta satelit di belakang.';

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalLokasiProkja'));
    modal.show();
}

function isiContohPuskesmasPalLima() {
    const namaInput = document.querySelector('#formUploadLokasi input[name="nama_lokasi"]');
    const katInput = document.getElementById('kategori_lokasi');
    const latInput = document.getElementById('lokasi_lat');
    const lngInput = document.getElementById('lokasi_lng');
    const ketInput = document.getElementById('lokasi_keterangan');
    const infoText = document.getElementById('koordinatPickInfo');

    if (namaInput) namaInput.value = 'Puskesmas Pal Lima (Kec. Pontianak Barat)';
    if (katInput) katInput.value = 'Posyandu & Kesehatan';
    if (latInput) latInput.value = '-0.035421';
    if (lngInput) lngInput.value = '109.295123';
    if (ketInput) ketInput.value = 'Pusat Kesehatan Masyarakat Pal Lima - Pelayanan kesehatan, imunisasi balita & pemeriksaan posyandu.';

    if (infoText) {
        infoText.innerHTML = '<span class="text-success fw-bold">✓ Contoh koordinat Puskesmas Pal Lima (-0.035421, 109.295123) berhasil diisikan!</span>';
    }
}

function kosongkanKoordinatLokasi() {
    const latInput = document.getElementById('lokasi_lat');
    const lngInput = document.getElementById('lokasi_lng');
    const infoText = document.getElementById('koordinatPickInfo');

    if (latInput) latInput.value = '';
    if (lngInput) lngInput.value = '';
    if (infoText) {
        infoText.innerHTML = '<span class="text-secondary fw-semibold">✓ Koordinat telah dibersihkan. Masukkan koordinat baru atau pilih dari peta.</span>';
    }
}

function konfirmasiHapusLokasiBtn(id, nama) {
    if (!checkAuthGuard(() => konfirmasiHapusLokasiBtn(id, nama))) return;

    lokasiIdHapus = id;
    document.getElementById('idLokasiHapus').value = id;
    document.getElementById('namaLokasiHapusText').textContent = nama ? `"${nama}"` : 'ini';

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalHapusLokasi'));
    modal.show();
}

function eksekusiHapusLokasi() {
    const id = lokasiIdHapus || document.getElementById('idLokasiHapus').value;
    if (!id) return;

    const btnHapus = document.getElementById('btnKonfirmasiHapusLokasi');
    const btnText = document.getElementById('btnTextHapusLokasi');
    const btnSpinner = document.getElementById('btnSpinnerHapusLokasi');

    btnHapus.disabled = true;
    btnText.classList.add('d-none');
    btnSpinner.classList.remove('d-none');

    const formData = new FormData();
    formData.append('id', id);

    fetch('proses_lokasi_prokja.php?action=delete', {
        method: 'POST',
        body: formData
    })
    .then(parseJsonResponse)
    .then(data => {
        btnHapus.disabled = false;
        btnText.classList.remove('d-none');
        btnSpinner.classList.add('d-none');

        const modalEl = document.getElementById('modalHapusLokasi');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();

        if (data.success) {
            if (window.loadLokasiProkja) window.loadLokasiProkja();
        } else {
            alert(data.message || 'Gagal menghapus titik lokasi.');
        }
    })
    .catch(err => {
        btnHapus.disabled = false;
        btnText.classList.remove('d-none');
        btnSpinner.classList.add('d-none');
        alert('Terjadi kesalahan: ' + err.message);
    });
}
</script>

</body>
</html>

