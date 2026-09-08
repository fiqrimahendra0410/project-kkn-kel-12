<?php
require 'koneksi.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

try {
    // Ambil detail program
    $stmtProgram = $pdo->prepare("
        SELECT pk.*, bp.nama AS nama_bidang, bp.kode AS kode_bidang, bp.warna_badge
        FROM program_kerja pk
        JOIN bidang_prokja bp ON pk.bidang_id = bp.id
        WHERE pk.id = :id AND pk.status = 'terbit'
    ");
    $stmtProgram->execute([':id' => $id]);
    $program = $stmtProgram->fetch();

    if (!$program) {
        // Jika program tidak ditemukan atau draft, kembalikan ke index
        header('Location: index.php');
        exit;
    }

    // Ambil foto-foto dokumentasi program dari program_kerja_foto
    $stmtFotos = $pdo->prepare("
        SELECT path_foto, judul, deskripsi, is_cover FROM program_kerja_foto 
        WHERE program_kerja_id = :id 
        ORDER BY is_cover DESC, id ASC
    ");
    $stmtFotos->execute([':id' => $id]);
    $fotos = $stmtFotos->fetchAll();

    $seenPaths = array_column($fotos, 'path_foto');

    // Ambil foto-foto kegiatan (kegiatan_foto) yang masuk dalam bidang program kerja ini
    if (!empty($program['bidang_id'])) {
        $stmtKegFoto = $pdo->prepare("
            SELECT kf.path_foto, kf.keterangan AS judul, k.deskripsi, 0 AS is_cover
            FROM kegiatan_foto kf
            JOIN kegiatan k ON kf.kegiatan_id = k.id
            WHERE k.bidang_id = :bid
            ORDER BY kf.id DESC
        ");
        $stmtKegFoto->execute([':bid' => $program['bidang_id']]);
        $kegFotos = $stmtKegFoto->fetchAll();
        foreach ($kegFotos as $kf) {
            if (!in_array($kf['path_foto'], $seenPaths, true)) {
                $fotos[] = $kf;
                $seenPaths[] = $kf['path_foto'];
            }
        }
    }

} catch (PDOException $e) {
    die('Terjadi kesalahan database: ' . $e->getMessage());
}

// Map emoji badge
$badgeIcon = '📋';
if ($program['kode_bidang'] === 'pendidikan') $badgeIcon = '📚';
elseif ($program['kode_bidang'] === 'kesehatan') $badgeIcon = '❤️';
elseif ($program['kode_bidang'] === 'ekonomi') $badgeIcon = '💼';
elseif ($program['kode_bidang'] === 'lingkungan') $badgeIcon = '🌱';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumentasi - <?= htmlspecialchars($program['judul']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0d6efd;
            --primary-dark: #0a58ca;
            --bg: #f8f9fa;
            --card-border: rgba(0, 0, 0, 0.05);
            --ink: #1c1d22;
            --ink-soft: #6b6d78;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            color: var(--ink);
            min-height: 100vh;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--ink-soft);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease-in-out;
            padding: 8px 16px;
            background: #fff;
            border-radius: 30px;
            border: 1px solid var(--card-border);
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
        }

        .back-btn:hover {
            color: var(--primary);
            border-color: var(--primary);
            transform: translateX(-4px);
        }

        .program-header-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid var(--card-border);
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            margin-top: 2rem;
            margin-bottom: 3rem;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }

        .program-header-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary);
        }

        .badge-prokja {
            font-size: 0.85rem;
            font-weight: 700;
            padding: 8px 16px;
            border-radius: 30px;
            margin-bottom: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .gallery-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--card-border);
            box-shadow: 0 6px 20px rgba(0,0,0,0.02);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .gallery-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 35px rgba(0,0,0,0.08);
        }

        .gallery-img-container {
            position: relative;
            overflow: hidden;
            aspect-ratio: 4/3;
            background-color: #f1f1f1;
            cursor: pointer;
        }

        .gallery-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .gallery-card:hover .gallery-img {
            transform: scale(1.08);
        }

        .gallery-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.2);
            opacity: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.3s ease;
        }

        .gallery-card:hover .gallery-overlay {
            opacity: 1;
        }

        .zoom-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #fff;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            box-shadow: 0 10px 20px rgba(0,0,0,0.25);
            transform: scale(0.8);
            transition: transform 0.3s ease;
        }

        .gallery-card:hover .zoom-icon {
            transform: scale(1);
        }

        .gallery-content {
            padding: 1.25rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .gallery-title {
            font-weight: 700;
            font-size: 1.05rem;
            margin-bottom: 0.5rem;
            color: var(--ink);
        }

        .gallery-desc {
            font-size: 0.88rem;
            color: var(--ink-soft);
            line-height: 1.5;
            margin-bottom: 0;
        }

        /* Lightbox CSS */
        .lightbox-overlay {
            position: fixed;
            inset: 0;
            background: rgba(8, 10, 20, 0.95);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            backdrop-filter: blur(8px);
        }

        .lightbox-overlay.show {
            display: flex;
        }

        .lightbox-container {
            max-width: 90vw;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .lightbox-img {
            max-width: 100%;
            max-height: 70vh;
            border-radius: 12px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            object-fit: contain;
        }

        .lightbox-caption-box {
            color: #fff;
            margin-top: 1.5rem;
            max-width: 600px;
        }

        .lightbox-title {
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 0.25rem;
        }

        .lightbox-desc {
            color: #ccc;
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .lightbox-close {
            position: absolute;
            top: 25px;
            right: 35px;
            background: none;
            border: none;
            color: #fff;
            font-size: 2.5rem;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .lightbox-close:hover {
            opacity: 1;
        }

        .empty-state {
            background: #fff;
            border-radius: 16px;
            padding: 4rem 2rem;
            border: 1px dashed #dee2e6;
            text-align: center;
            margin-top: 2rem;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <!-- Back Button -->
    <a href="index.php" class="back-btn">
        <i class="bi bi-arrow-left-short fs-4"></i> Kembali ke Beranda
    </a>

    <!-- Header / Program Info -->
    <div class="program-header-card">
        <span class="badge bg-<?= htmlspecialchars($program['warna_badge'] ?: 'primary') ?> badge-prokja text-light">
            <?= $badgeIcon ?> <?= htmlspecialchars($program['nama_bidang']) ?>
        </span>
        <h1 class="fw-bold mb-3"><?= htmlspecialchars($program['judul']) ?></h1>
        <p class="text-muted small mb-3"><i class="bi bi-calendar3 me-2"></i><?= htmlspecialchars($program['periode']) ?></p>
        <p class="lead text-secondary mb-4" style="font-size: 1.1rem; line-height: 1.7;">
            <?= nl2br(htmlspecialchars($program['deskripsi'])) ?>
        </p>
        <?php $prokjaLink = !empty($program['link']) ? $program['link'] : 'https://kkn12.ct.ws/?i=1'; ?>
        <div>
            <a href="<?= htmlspecialchars($prokjaLink) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary rounded-pill px-4 py-2" style="font-weight: 600;">
                <i class="bi bi-globe me-2"></i> Kunjungi Link / Website Program
            </a>
        </div>
    </div>

    <!-- Gallery Grid -->
    <h3 class="fw-bold mb-4"><i class="bi bi-images text-primary me-2"></i>Galeri Dokumentasi</h3>
    
    <?php if (count($fotos) > 0): ?>
        <div class="row g-4">
            <?php foreach ($fotos as $f): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="gallery-card">
                        <div class="gallery-img-container" onclick="openLightbox('<?= htmlspecialchars(parsePhotoUrl($f['path_foto'])) ?>', '<?= htmlspecialchars(addslashes($f['judul'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($f['deskripsi'] ?? '')) ?>')">
                            <img src="<?= htmlspecialchars(parsePhotoUrl($f['path_foto'])) ?>" class="gallery-img" alt="<?= htmlspecialchars($f['judul'] ?: '') ?>">
                            <div class="gallery-overlay">
                                <div class="zoom-icon"><i class="bi bi-zoom-in"></i></div>
                            </div>
                        </div>
                        <div class="gallery-content">
                            <h5 class="gallery-title text-truncate" title="<?= htmlspecialchars($f['judul'] ?: '') ?>">
                                <?= htmlspecialchars($f['judul'] ?: '(Tanpa Judul)') ?>
                            </h5>
                            <p class="gallery-desc">
                                <?= htmlspecialchars($f['deskripsi'] ?: '') ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
            <h5 class="fw-bold text-muted mt-3">Belum ada foto dokumentasi</h5>
            <p class="text-muted small">Foto dokumentasi untuk program kerja ini akan segera diunggah oleh panitia KKN.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Premium Lightbox Overlay -->
<div class="lightbox-overlay" id="lightboxOverlay">
    <button class="lightbox-close" id="lightboxCloseBtn">&times;</button>
    <div class="lightbox-container">
        <img id="lightboxImage" class="lightbox-img" src="" alt="">
        <div class="lightbox-caption-box">
            <h4 id="lightboxTitle" class="lightbox-title"></h4>
            <p id="lightboxDesc" class="lightbox-desc"></p>
        </div>
    </div>
</div>

<script>
    const lightbox = document.getElementById('lightboxOverlay');
    const lightboxImg = document.getElementById('lightboxImage');
    const lightboxTitle = document.getElementById('lightboxTitle');
    const lightboxDesc = document.getElementById('lightboxDesc');
    const closeBtn = document.getElementById('lightboxCloseBtn');

    function openLightbox(src, title, desc) {
        lightboxImg.src = src;
        lightboxTitle.textContent = title || '(Tanpa Judul)';
        lightboxDesc.textContent = desc || '';
        lightbox.classList.add('show');
    }

    closeBtn.addEventListener('click', () => {
        lightbox.classList.remove('show');
    });

    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) {
            lightbox.classList.remove('show');
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && lightbox.classList.contains('show')) {
            lightbox.classList.remove('show');
        }
    });
</script>
</body>
</html>

