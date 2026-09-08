<?php
require 'proteksi.php';
require 'koneksi.php';

$isViewer = (($_SESSION['role'] ?? '') === 'viewer');

// Base64 encode logo images to prevent CORS or load failure in html2canvas PDF rendering
$logoUmpPath = __DIR__ . '/img/logo ump.png';
$logoKknPath = __DIR__ . '/img/logo baru.png';
$logoUmpSrc = file_exists($logoUmpPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoUmpPath)) : 'img/logo%20ump.png';
$logoKknSrc = file_exists($logoKknPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoKknPath)) : 'img/logo%20baru.png';

// ===== Ambil data kegiatan + absensi + foto dari database =====
$kegiatanRows = [];
$entriesForJs = [];
$totalKegiatan = 0;
$totalFoto = 0;
$totalJam = "0,0";
$totalJamRaw = 0;
$targetJam = 150;
$persenJam = 0;
$prokjaPhotosRows = [];
$bidangList = [];
$anggotaRows = [];
$dplRow = null;
$isDplUser = false;
$currentAnggotaId = $_SESSION['anggota_id'] ?? 0;
$currentNamaUser  = $_SESSION['nama'] ?? 'Anggota';
$currentQuote     = '';
$isQuoteEmpty     = false;
$jadwalPiketRows = [];
$infoPoskoRows    = [];
$jadwalAcaraRows  = [];
$jadwalAcaraForJs = [];

if ($pdo) {
    try {
        $kegiatanRows = $pdo->query("
            SELECT k.*, b.kode AS divisi, b.nama AS divisiLabel
            FROM kegiatan k
            LEFT JOIN bidang_prokja b ON b.id = k.bidang_id
            ORDER BY k.tanggal DESC, k.id DESC
        ")->fetchAll();

        $namaBulanIndo = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

        $fotoStmt = $pdo->prepare("SELECT id, path_foto, keterangan FROM kegiatan_foto WHERE kegiatan_id = :kid ORDER BY diunggah_pada");
        $rundownStmt = $pdo->prepare("SELECT id, waktu, agenda, pic, keterangan FROM kegiatan_rundown WHERE kegiatan_id = :kid ORDER BY waktu ASC, id ASC");

        foreach ($kegiatanRows as $k) {
            $tgl = strtotime($k['tanggal']);

            $fotoStmt->execute([':kid' => $k['id']]);
            $fotoRowsFull = $fotoStmt->fetchAll(PDO::FETCH_ASSOC);
            $fotoRows = array_column($fotoRowsFull, 'path_foto');

            $rundownStmt->execute([':kid' => $k['id']]);
            $rundownRows = $rundownStmt->fetchAll(PDO::FETCH_ASSOC);

            $jm = trim($k['jam_mulai'] ?? '');
            $js = trim($k['jam_selesai'] ?? '');

            $jmClean = preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $jm) ? substr($jm, 0, 5) : $jm;
            $jsClean = preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $js) ? substr($js, 0, 5) : $js;

            $jmDisplay = preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $jm) ? str_replace(':', '.', substr($jm, 0, 5)) : $jm;
            $jsDisplay = preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $js) ? str_replace(':', '.', substr($js, 0, 5)) : $js;

            if ($jmDisplay !== '' && $jsDisplay !== '') {
                $waktu = "{$jmDisplay} – {$jsDisplay}";
            } elseif ($jmDisplay !== '') {
                $waktu = $jmDisplay;
            } elseif ($jsDisplay !== '') {
                $waktu = $jsDisplay;
            } else {
                $waktu = '-';
            }

            if ($waktu !== '-' && !preg_match('/(wib|wita|wit)/i', $waktu)) {
                $waktu .= ' WIB';
            }

            $entriesForJs[] = [
                'id'          => (int)$k['id'],
                'tgl'         => date('d', $tgl),
                'bulan'       => $namaBulanIndo[(int)date('n', $tgl)],
                'hari'        => $k['hari'],
                'title'       => $k['judul'],
                'divisi'      => $k['divisi'] ?: '',
                'divisiLabel' => $k['divisiLabel'] ?: '-',
                'waktu'       => $waktu,
                'tanggal'     => $k['tanggal'],
                'jam_mulai'   => $jmClean,
                'jam_selesai' => $jsClean,
                'lokasi'      => $k['lokasi'] ?: '-',
                'sasaran'     => $k['sasaran'] ?: '-',
                'desc'        => $k['deskripsi'] ?: '-',
                'hasil'       => $k['hasil'] ?: '-',
                'foto'        => $fotoRows,
                'fotoObjects' => $fotoRowsFull,
                'rundown'     => $rundownRows,
            ];
        }

        $totalKegiatan = (int) $pdo->query("SELECT COUNT(*) FROM kegiatan")->fetchColumn();
        $totalFoto     = (int) $pdo->query("SELECT (SELECT COUNT(*) FROM kegiatan_foto) + (SELECT COUNT(*) FROM program_kerja_foto)")->fetchColumn();
        $totalMenit = 0;
        foreach ($kegiatanRows as $k) {
            $jm = trim($k['jam_mulai'] ?? '');
            $js = trim($k['jam_selesai'] ?? '');

            $startMins = null;
            if (preg_match('/(\d{1,2})[:.](\d{2})/', $jm, $m)) {
                $startMins = (int)$m[1] * 60 + (int)$m[2];
            }

            $endMins = null;
            if (preg_match('/(\d{1,2})[:.](\d{2})/', $js, $m)) {
                $endMins = (int)$m[1] * 60 + (int)$m[2];
            }

            if ($startMins !== null && $endMins !== null) {
                $diff = $endMins - $startMins;
                if ($diff < 0) {
                    $diff += 1440;
                }
                $totalMenit += $diff;
            }
        }
        $totalJamRaw = max(150, $totalMenit / 60);
        $totalJam    = ($totalJamRaw == (int)$totalJamRaw) ? number_format($totalJamRaw, 0, ',', '.') : number_format($totalJamRaw, 1, ',', '.');
        $targetJam   = 150;
        $persenJam   = min(100, round(($totalJamRaw / $targetJam) * 100, 1));

        $prokjaPhotosRows = $pdo->query("
            SELECT pkf.id, pkf.path_foto, pkf.judul, pkf.deskripsi, pk.judul AS program_judul
            FROM program_kerja_foto pkf
            LEFT JOIN program_kerja pk ON pk.id = pkf.program_kerja_id
            ORDER BY pkf.id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $bidangList = $pdo->query("SELECT id, kode, nama FROM bidang_prokja ORDER BY id")->fetchAll();
        $anggotaRows = $pdo->query("SELECT id, nama, jabatan, foto, kata_kata FROM anggota ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $dplRow = $pdo->query("SELECT id, nama, jabatan, foto, kata_kata FROM anggota WHERE nama LIKE '%Kania%' OR jabatan LIKE '%Dosen Pembimbing%' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $isDplUser = (($_SESSION['anggota_id'] ?? 0) == ($dplRow['id'] ?? 11) || (stripos($_SESSION['nama'] ?? '', 'Kania') !== false));

        foreach ($anggotaRows as $a) {
            if ($a['id'] == $currentAnggotaId) {
                $currentQuote = $a['kata_kata'] ?? '';
                break;
            }
        }
        if ($isDplUser) {
            $currentQuote = $dplRow['kata_kata'] ?? '';
        }
        $isQuoteEmpty = (trim($currentQuote) === '');

        $jadwalPiketRows = $pdo->query("SELECT * FROM jadwal_piket ORDER BY FIELD(hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $infoPoskoRows = $pdo->query("SELECT * FROM informasi_posko ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $jadwalAcaraRows = $pdo->query("SELECT * FROM jadwal_acara ORDER BY tanggal ASC, waktu ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($jadwalAcaraRows as $row) {
            $tgl = strtotime($row['tanggal']);
            $jadwalAcaraForJs[] = [
                'id'         => (int)$row['id'],
                'tanggal'    => $row['tanggal'],
                'tgl'        => date('d', $tgl),
                'bulan'      => $namaBulanIndo[(int)date('n', $tgl)],
                'hari'       => $row['hari'],
                'waktu'      => $row['waktu'],
                'agenda'     => $row['agenda'],
                'pic'        => $row['pic'] ?: '',
                'keterangan' => $row['keterangan'] ?: '-'
            ];
        }

        // Synchronize program kerja dari index.php ke dalam entries buku lapangan
        try {
            $prokjaListForBuku = $pdo->query("
                SELECT pk.*, bp.nama AS nama_bidang, bp.kode AS kode_bidang,
                       (SELECT pkf.path_foto FROM program_kerja_foto pkf WHERE pkf.program_kerja_id = pk.id LIMIT 1) AS cover_foto
                FROM program_kerja pk
                LEFT JOIN bidang_prokja bp ON pk.bidang_id = bp.id
            ")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($prokjaListForBuku as $pk) {
                $alreadyExists = false;
                foreach ($entriesForJs as $existing) {
                    if (strcasecmp($existing['title'], $pk['judul']) === 0) {
                        $alreadyExists = true;
                        break;
                    }
                }
                if (!$alreadyExists && !empty($pk['judul'])) {
                    $tgl = !empty($pk['dibuat_pada']) ? strtotime($pk['dibuat_pada']) : time();
                    $entriesForJs[] = [
                        'id'          => 1000 + (int)$pk['id'],
                        'tgl'         => date('d', $tgl),
                        'bulan'       => $namaBulanIndo[(int)date('n', $tgl)] ?? 'Jul',
                        'hari'        => 'Senin',
                        'title'       => $pk['judul'],
                        'divisi'      => $pk['kode_bidang'] ?: '',
                        'divisiLabel' => $pk['nama_bidang'] ?: 'Program Kerja',
                        'waktu'       => '08.00 – Selesai WIB',
                        'tanggal'     => date('Y-m-d', $tgl),
                        'jam_mulai'   => '08:00',
                        'jam_selesai' => '17:00',
                        'lokasi'      => 'Kelurahan Pal Lima, Pontianak',
                        'sasaran'     => 'Warga Kelurahan Pal Lima',
                        'desc'        => $pk['deskripsi'] ?: 'Program kerja KKN Kelompok 12 UMP.',
                        'hasil'       => 'Terlaksana dengan baik.',
                        'foto'        => $pk['cover_foto'] ? [$pk['cover_foto']] : [],
                        'fotoObjects' => [],
                        'rundown'     => []
                    ];
                }
            }
        } catch (Throwable $e) {}
    } catch (Throwable $e) {
        // Fallback aman jika terjadi kesalahan kueri
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="img/logo baru.png">
    <link rel="shortcut icon" type="image/png" href="img/logo baru.png">
    <title>Buku Lapangan - KKN Kelompok 12</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- html2pdf.js Library for client-side PDF generation on mobile & desktop -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
:root {
    --primary: #4f46e5;
    --primary-dark: #4338ca;
    --ink: #0f172a;
    --ink-soft: #475569;
    --border: rgba(0, 0, 0, 0.05);
    --bg: #f8fafc;
    --hadir: #10b981;
    --sakit: #ef4444;
}

body {
    font-family: 'Outfit', sans-serif;
    background: var(--bg);
    color: var(--ink);
}

/* Sembunyikan elemen cetak di layar biasa */
.print-only-header, .print-only-report, .print-only-jadwal {
    display: none !important;
}

.hero-bg {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    position: relative;
    overflow: hidden;
}

.hero-bg::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle at 85% 20%, rgba(79, 70, 229, 0.15) 0%, transparent 45%),
                       radial-gradient(circle at 10% 90%, rgba(79, 70, 229, 0.08) 0%, transparent 40%);
    pointer-events: none;
}

.hero-bg .container { position: relative; z-index: 1; }

.hero-bg .badge.bg-primary {
    background: rgba(255, 255, 255, 0.08) !important;
    border: 1px solid rgba(255, 255, 255, 0.15);
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 50rem;
}

.hero-bg .text-primary {
    color: #fff !important;
    background: linear-gradient(120deg, #818cf8 0%, #c084fc 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.hero-bg .lead { color: #94a3b8; font-size: 1.15rem; }

.back-home-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #cbd5e1;
    font-size: .85rem;
    font-weight: 600;
    text-decoration: none;
    margin-bottom: 18px;
    padding: 8px 18px;
    border-radius: 30px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.04);
    transition: all 0.3s ease;
}

.back-home-link:hover {
    color: #fff;
    gap: 12px;
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-1px);
}

.hero-btn-solid {
    background: var(--primary) !important;
    color: #fff !important;
    border: none !important;
    font-weight: 700;
    border-radius: 12px;
    padding: 12px 26px;
    transition: all 0.2s ease-in-out;
    box-shadow: 0 4px 14px rgba(79,70,229,0.3);
}

.hero-btn-solid:hover { background: var(--primary-dark) !important; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(79,70,229,0.4); }

.hero-btn-outline {
    background: transparent !important;
    color: #fff !important;
    border: 1.5px solid rgba(255,255,255,0.4) !important;
    font-weight: 700;
    border-radius: 12px;
    padding: 12px 26px;
    transition: all 0.2s ease-in-out;
}

.hero-btn-outline:hover { background: rgba(255,255,255,0.08) !important; border-color: #fff !important; transform: translateY(-2px); }

.stat-section { padding: 40px 0 20px; background: transparent; }

.stat-box { 
    background: #fff;
    border-radius: 24px;
    padding: 24px; 
    border: 1px solid rgba(0,0,0,0.03);
    box-shadow: 0 10px 30px rgba(0,0,0,0.015);
    display: flex;
    align-items: center;
    gap: 18px;
    text-align: left;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.stat-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(79, 70, 229, 0.08);
    border-color: rgba(79, 70, 229, 0.12);
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: rgba(79, 70, 229, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    margin: 0;
    font-size: 1.4rem;
    transition: all 0.3s ease;
}

.stat-box:hover .stat-icon {
    transform: scale(1.1) rotate(5deg);
    background: var(--primary);
    color: #ffffff;
}

.search-section { padding: 10px 0 20px; }

.search-filter-row { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }

.search-container { position: relative; flex: 1 1 320px; min-width: 240px; }

.search-input {
    height: 48px;
    border-radius: 14px;
    padding-left: 44px;
    padding-right: 42px;
    border: 1px solid rgba(0,0,0,0.08);
    box-shadow: 0 4px 12px rgba(0,0,0,0.01);
    font-size: 0.9rem;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    width: 100%;
}

.search-input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(79,70,229,0.12);
    outline: none;
}

.search-icon {
    position: absolute;
    top: 50%;
    left: 16px;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.95rem;
    pointer-events: none;
}

.search-input:focus ~ .search-icon { color: var(--primary); }

.search-clear {
    position: absolute;
    top: 50%;
    right: 16px;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 1.1rem;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.search-clear:hover { color: #64748b; }

.filter-chips { display: flex; gap: 8px; flex-wrap: wrap; flex: 2 1 auto; }

.chip {
    padding: 9px 18px;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    background: #fff;
    color: var(--ink-soft);
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 4px 10px rgba(0,0,0,0.01);
}

.chip:hover {
    color: var(--primary);
    background: rgba(79,70,229,0.05);
    border-color: rgba(79,70,229,0.15);
    transform: translateY(-2px);
}

.chip.active {
    background: var(--primary);
    border-color: transparent;
    color: #fff;
    box-shadow: 0 8px 20px rgba(79,70,229,0.28);
}

@media (max-width: 640px) {
    .search-filter-row { flex-direction: column; align-items: stretch; }
    .filter-chips { justify-content: center; }
}

.timeline-section { padding: 20px 0 60px; }

.section-heading { margin-bottom: 24px; }

.section-heading .text-muted { font-size: .9rem; }

.timeline { position:relative; padding-left:28px; }

.timeline::before {
    content:"";
    position:absolute;
    left:5px;
    top:8px;
    bottom:8px;
    width:3px;
    background: linear-gradient(180deg, #4f46e5 0%, #a855f7 100%);
    border-radius: 3px;
    opacity: 0.8;
}

.entry { position:relative; margin-bottom:20px; }

.entry-dot {
    position:absolute;
    left:-28px;
    top:24px;
    width:14px;
    height:14px;
    border-radius:50%;
    background:#fff;
    border:4px solid var(--primary);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
    z-index:2;
}

.timeline .card {
    background:#fff;
    border:1px solid rgba(0,0,0,0.04) !important;
    border-radius:20px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.015);
    overflow:hidden;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.timeline .card:hover {
    box-shadow: 0 20px 40px rgba(79, 70, 229, 0.08);
    border-color: rgba(79, 70, 229, 0.12) !important;
}

.card-head { padding:20px 24px; display:flex; align-items:center; gap:16px; cursor:pointer; }

.date-tag {
    background: rgba(79,70,229,0.06);
    color: var(--primary);
    font-size:.72rem;
    font-weight:700;
    padding:8px 14px;
    border-radius:12px;
    text-align:center;
    line-height:1.2;
    min-width:56px;
    border: 1px solid rgba(79, 70, 229, 0.12);
}

.date-tag .dnum { display:block; font-size:1.15rem; font-weight: 800; }

.card-title-wrap { flex:1; min-width:0; }

.card-title { font-weight:800; font-size:1.15rem; margin:0 0 4px; color:var(--ink); letter-spacing: -0.01em; }

.card-meta { font-size:.8rem; color:var(--ink-soft); display:flex; gap:12px; flex-wrap:wrap; }

.div-badge {
    font-size:.68rem;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.04em;
    padding:4px 12px;
    border-radius:30px;
    background: rgba(79,70,229,0.08);
    color: var(--primary);
    border: 1px solid rgba(79, 70, 229, 0.15);
}

.chevron { color:var(--ink-soft); transition:transform .25s; flex-shrink:0; }

.entry.open .chevron { transform:rotate(180deg); color: var(--primary); }

.card-body { max-height:0; overflow:hidden; transition:max-height .35s ease; border-top:1px solid transparent; }

.entry.open .card-body { max-height:4000px; border-top:1px solid rgba(0,0,0,0.04); }

.card-body-inner { padding:24px; }

.desc { font-size:.95rem; line-height:1.7; color: var(--ink-soft); margin:0 0 20px; }

.kv-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:18px; }

.kv { background: var(--bg); border-radius:14px; padding:12px 18px; border: 1px solid rgba(0,0,0,0.03); }

.kv-label { font-size:.68rem; text-transform:uppercase; letter-spacing:.05em; color:var(--ink-soft); margin-bottom:3px; font-weight: 700; }

.kv-val { font-size:.87rem; font-weight:600; color: var(--ink); }

.section-label {
    font-size:.72rem;
    text-transform:uppercase;
    letter-spacing:.08em;
    color:var(--ink-soft);
    font-weight:700;
    margin:0 0 12px;
    display:flex;
    align-items:center;
    gap:8px;
}

.section-label::after { content:""; flex:1; height:1px; background:var(--border); }

@media print {
    /* Hide ALL screen components */
    .hero-bg, .stat-section, .search-section, .upload-section, .timeline-section, .galeri-section, .section-heading, .chevron, .btn-quick, #timeline, .lightbox, .upload-toast {
        display: none !important;
    }
    
    /* Background adjustments */
    body {
        background: #fff !important;
        color: #000 !important;
        font-size: 11pt !important;
        font-family: 'Times New Roman', Times, serif !important;
    }
    
    @page {
        size: A4 portrait;
        margin: 15mm 15mm 20mm 15mm;
        @bottom-center {
            content: "Halaman " counter(page);
            font-family: 'Times New Roman', Times, serif;
            font-size: 10pt;
        }
        @bottom-right {
            content: "Buku Lapangan KKN 12 UM Pontianak";
            font-family: 'Times New Roman', Times, serif;
            font-size: 8.5pt;
            font-style: italic;
        }
    }

    /* Document structure styling */
    body.print-mode-logbook .print-only-header {
        display: block !important;
        font-family: 'Times New Roman', Times, serif !important;
    }
    
    body.print-mode-logbook .print-only-report {
        display: block !important;
        width: 100% !important;
        margin: 15px 0 0 0 !important;
        padding: 0 !important;
        page-break-before: avoid !important;
        break-before: avoid !important;
        font-family: 'Times New Roman', Times, serif !important;
    }
    
    .formal-report-table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin-top: 15px !important;
        page-break-before: avoid !important;
        break-before: avoid !important;
        font-family: 'Times New Roman', Times, serif !important;
    }
    
    .formal-report-table th, .formal-report-table td {
        border: 1px solid #000 !important;
        padding: 6px 8px !important;
        vertical-align: top !important;
        font-size: 8.5pt !important;
        color: #000 !important;
        line-height: 1.35 !important;
    }
    
    .formal-report-table th {
        background: #f1f5f9 !important;
        font-weight: 700 !important;
        text-align: center !important;
        font-size: 9pt !important;
    }
    
    .formal-report-table tr {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
    
    @page {
        margin: 15mm 12mm 15mm 12mm;
    }
}

.upload-section { padding: 10px 0 40px; }

.upload-card {
    background:#fff;
    border:1px solid rgba(0,0,0,0.04) !important;
    border-radius:24px;
    padding:32px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.02) !important;
}

.upload-card-header {
    display:flex;
    align-items:center;
    gap:16px;
    margin-bottom:26px;
}

.upload-icon-box {
    width:48px;
    height:48px;
    flex-shrink:0;
    border-radius:14px;
    background: rgba(79,70,229,0.08);
    display:flex;
    align-items:center;
    justify-content:center;
    transition: all 0.3s;
}

.upload-card:hover .upload-icon-box {
    transform: scale(1.08) rotate(5deg);
}

.upload-icon-box i { font-size:1.3rem; color: var(--primary); }
.upload-card-title { font-size:1.08rem; font-weight:700; margin:0; letter-spacing: -0.01em; }
.upload-card-subtitle { font-size:.85rem; color:var(--ink-soft); margin:2px 0 0; }

.upload-steps { position:relative; padding-left:34px; margin-bottom:22px; }
.upload-steps::before {
    content:"";
    position:absolute;
    left:11px;
    top:8px;
    bottom:8px;
    width:2px;
    background:repeating-linear-gradient(to bottom, var(--border) 0 6px, transparent 6px 11px);
}

.upload-step { position:relative; margin-bottom:22px; }
.upload-step:last-child { margin-bottom:0; }
.step-num {
    position:absolute;
    left:-34px;
    top:0;
    width:22px;
    height:22px;
    border-radius:50%;
    background: var(--primary);
    color:#fff;
    font-size:.7rem;
    font-weight:700;
    display:flex;
    align-items:center;
    justify-content:center;
    z-index:1;
    box-shadow: 0 3px 10px rgba(79, 70, 229, 0.2);
}

.upload-field { display:flex; flex-direction:column; gap:7px; }
.upload-field label { font-size:.82rem; font-weight:700; color:var(--ink); }
.upload-field select {
    padding:12px 16px;
    border:1px solid rgba(0,0,0,0.08);
    border-radius:12px;
    font-size:.9rem;
    background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='%236b6d78' d='M4.5 6l3.5 4 3.5-4z'/%3E%3C/svg%3E") no-repeat right 14px center;
    appearance:none;
    -webkit-appearance:none;
    cursor:pointer;
    outline: none;
    transition: all 0.3s ease;
}
.upload-field select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(79,70,229,0.12);
}

.upload-box {
    border:2px dashed rgba(79, 70, 229, 0.15);
    border-radius:16px;
    padding:32px 20px;
    text-align:center;
    cursor:pointer;
    transition: all 0.3s ease;
    background: rgba(79, 70, 229, 0.01);
}
.upload-box:hover {
    border-color:var(--primary);
    background:rgba(79,70,229,0.05);
}
.upload-box input { display:none; }
.upload-box-icon {
    width:44px;
    height:44px;
    margin:0 auto 12px;
    border-radius:50%;
    background: rgba(79, 70, 229, 0.08);
    color: var(--primary);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:1.25rem;
    transition: all 0.3s;
}
.upload-box:hover .upload-box-icon {
    transform: translateY(-2px);
    background: var(--primary);
    color: #ffffff;
}

.upload-hint { color:var(--ink); font-size:.92rem; margin-bottom:2px; }
.upload-hint b { color:var(--primary); }
.upload-hint-sub { color:var(--ink-soft); font-size:.78rem; }

.preview-count { font-size:.78rem; color:var(--ink-soft); margin:12px 0 8px; font-weight:600; }
.preview-strip { display:flex; gap:10px; flex-wrap:wrap; }
.preview-thumb {
    position:relative;
    width:84px;
    height:84px;
    border-radius:12px;
    overflow:hidden;
    border:1px solid var(--border);
    box-shadow:0 5px 15px rgba(0,0,0,0.08);
}
.preview-thumb img { width:100%; height:100%; object-fit:cover; }
.preview-thumb .rm {
    position:absolute;
    top:4px;
    right:4px;
    background:rgba(0,0,0,0.65);
    color:#fff;
    width:22px;
    height:22px;
    border-radius:50%;
    border:none;
    font-size:.75rem;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    transition: all 0.2s;
}
.preview-thumb .rm:hover { background:var(--sakit); }

.btn-upload-submit {
    width:100%;
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%) !important;
    color:#fff !important;
    border:none !important;
    padding:14px;
    border-radius:14px;
    font-weight:700;
    font-size:.95rem;
    cursor:pointer;
    transition: all 0.3s ease;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    box-shadow: 0 4px 14px rgba(79,70,229,0.25);
}
.btn-upload-submit:hover {
    background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%) !important;
    transform:translateY(-2px);
    box-shadow: 0 6px 20px rgba(79,70,229,0.35);
}
.btn-upload-submit:disabled { opacity:.5; cursor:not-allowed; }

.upload-toast {
    position:fixed;
    bottom:26px;
    left:50%;
    transform:translateX(-50%) translateY(20px);
    background: var(--ink);
    color:#fff;
    padding:12px 24px;
    border-radius:50rem;
    font-size:.88rem;
    opacity:0;
    pointer-events:none;
    transition:.3s cubic-bezier(0.16, 1, 0.3, 1);
    z-index:999;
    display:flex;
    align-items:center;
    gap:8px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.08);
}
.upload-toast.show { opacity:1; transform:translateX(-50%) translateY(0); }

.t-photos { display:flex; gap:8px; margin-top:14px; flex-wrap:wrap; }
.t-photos img { width:60px; height:60px; object-fit:cover; border-radius:10px; cursor:pointer; transition: transform 0.2s; }
.t-photos img:hover { transform: scale(1.05); }
@media (max-width: 576px) {
    .t-photos {
        flex-wrap: nowrap !important;
        overflow-x: auto !important;
        padding-bottom: 8px !important;
        -webkit-overflow-scrolling: touch;
    }
    .t-photos .t-photo-item {
        flex: 0 0 auto !important;
        margin-right: 0 !important;
    }
}

.galeri-section { padding:10px 0 60px; }
.galeri-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
.g-item {
    position:relative;
    border-radius:16px;
    overflow:hidden;
    aspect-ratio:1/1;
    background:#eee;
    cursor:pointer;
    box-shadow: 0 8px 20px rgba(0,0,0,0.02);
}
.g-item img { width:100%; height:100%; object-fit:cover; display:block; transition:.4s; }
.g-item:hover img { transform:scale(1.06); }
.g-overlay {
    position:absolute;
    inset:0;
    display:flex;
    align-items:flex-end;
    padding:16px;
    background:linear-gradient(to top, rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0));
    opacity:0;
    transition:.3s;
}
.g-item:hover .g-overlay { opacity:1; }
.g-cap { color:#fff; font-size:.8rem; font-weight:600; letter-spacing: -0.01em; }
.galeri-empty { color:var(--ink-soft); font-size:.9rem; padding:16px 0; }

.g-actions-bar {
    position: absolute;
    top: 10px;
    right: 10px;
    display: flex;
    gap: 6px;
    z-index: 10;
    opacity: 0.85;
    transition: opacity 0.2s ease;
}
.g-item:hover .g-actions-bar {
    opacity: 1;
}
.g-btn-action {
    background: rgba(255, 255, 255, 0.95);
    border: none;
    border-radius: 50%;
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1e293b;
    font-size: 14px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    transition: all 0.2s ease;
}
.g-btn-action:hover {
    transform: scale(1.12);
    background: #ffffff;
}
.g-btn-action.edit:hover { color: #0d6efd; }
.g-btn-action.delete:hover { color: #dc3545; }

.lightbox {
    position:fixed;
    inset:0;
    background:rgba(8,10,20,0.96);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:1000;
    padding:40px;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}
.lightbox.open { display:flex; }
.lightbox img { max-width:88vw; max-height:78vh; border-radius:16px; box-shadow:0 30px 70px rgba(0,0,0,0.6); }
.lightbox-caption { position:absolute; bottom:30px; left:50%; transform:translateX(-50%); color:#fff; font-size:.95rem; opacity:.9; text-align:center; }
.lightbox-close {
    position:absolute;
    top:22px;
    right:28px;
    background:none;
    border:none;
    color:#fff;
    font-size:2.2rem;
    cursor:pointer;
    line-height:1;
    opacity:.8;
    transition: all 0.2s;
}
.lightbox-close:hover { opacity:1; transform: scale(1.1); }

@media (max-width: 700px){
    .galeri-grid{ grid-template-columns:repeat(2,1fr); }
    .upload-card{ padding:20px; }
    .upload-card-header{ align-items:flex-start; }
}

@media (max-width: 576px){
    .stat-icon{ width:54px; height:54px; }
    .stat-icon i{ font-size:1.3rem; }
    .stat-box h2{ font-size:1.7rem; }
}

@media print {
    body.print-mode-jadwal .print-only-header,
    body.print-mode-jadwal .hero-bg,
    body.print-mode-jadwal .stat-section,
    body.print-mode-jadwal .filter-section,
    body.print-mode-jadwal .timeline-section,
    body.print-mode-jadwal .upload-section,
    body.print-mode-jadwal .galeri-section,
    body.print-mode-jadwal footer,
    body.print-mode-jadwal .print-only-report {
        display: none !important;
    }
    body.print-mode-jadwal .print-only-jadwal {
        display: block !important;
    }
    body.print-mode-logbook .print-only-jadwal {
        display: none !important;
    }
}

.hero-actions-container {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 20px;
    padding: 24px;
    margin-top: 24px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}
.hero-actions-title {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.825rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.btn-emerald {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    color: #fff !important;
    font-weight: 600;
    border: none;
    border-radius: 12px;
    padding: 12px 18px;
    box-shadow: 0 4px 14px rgba(16,185,129,0.3);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    font-size: 0.925rem;
    display: inline-flex;
    align-items: center;
    justify-content: flex-start;
}
.btn-emerald:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16,185,129,0.45);
    color: #fff !important;
}
.btn-purple {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important;
    color: #fff !important;
    font-weight: 600;
    border: none;
    border-radius: 12px;
    padding: 12px 18px;
    box-shadow: 0 4px 14px rgba(139,92,246,0.3);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    font-size: 0.925rem;
    display: inline-flex;
    align-items: center;
    justify-content: flex-start;
}
.btn-purple:hover {
    background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%) !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(139,92,246,0.45);
    color: #fff !important;
}
.hero-btn-solid-blue {
    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%) !important;
    color: #fff !important;
    border: none !important;
    font-weight: 600;
    border-radius: 12px;
    padding: 12px 18px;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 4px 14px rgba(79,70,229,0.3);
    font-size: 0.925rem;
    display: inline-flex;
    align-items: center;
    justify-content: flex-start;
}
.hero-btn-solid-blue:hover {
    background: linear-gradient(135deg, #4338ca 0%, #3730a3 100%) !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(79,70,229,0.45);
    color: #fff !important;
}

.nav-pills.bg-white {
    background: #ffffff !important;
    border: 1px solid rgba(0, 0, 0, 0.05) !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02) !important;
}
.nav-pills .nav-link {
    color: var(--ink-soft);
    border-radius: 12px !important;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.nav-pills .nav-link:hover {
    color: var(--primary);
    background: rgba(79, 70, 229, 0.04);
}
.nav-pills .nav-link.active {
    background: var(--primary) !important;
    color: #ffffff !important;
    box-shadow: 0 8px 20px rgba(79, 70, 229, 0.2);
}

/* =========================================================
   Clean Rundown List Style (Waktu ⟶ Agenda)
   ========================================================= */
.rundown-card-wrapper {
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 20px;
    padding: 32px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
    max-width: 850px;
    margin: 0 auto;
    font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.rundown-list-container {
    margin-top: 10px;
}

.rundown-item {
    display: flex;
    align-items: center;
    padding: 14px 10px;
    border-bottom: 1px solid #f1f5f9;
    transition: background-color 0.2s ease;
    border-radius: 10px;
    position: relative;
}
.rundown-item:last-child {
    border-bottom: none;
}
.rundown-item:hover {
    background-color: #f8fafc;
}

.rundown-time {
    width: 155px;
    flex-shrink: 0;
    font-weight: 700;
    color: var(--primary);
    font-size: 1.05rem;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
}

.rundown-arrow {
    width: 55px;
    flex-shrink: 0;
    text-align: center;
    font-size: 1.35rem;
    color: #94a3b8;
    line-height: 1;
}

.rundown-agenda {
    flex-grow: 1;
    font-weight: 600;
    color: #1e293b;
    font-size: 1.05rem;
    line-height: 1.4;
}

.rundown-item-sub {
    font-size: 0.85rem;
    color: #64748b;
    font-weight: 400;
    margin-top: 2px;
}

.rundown-actions {
    display: flex !important;
    gap: 6px;
    opacity: 1 !important;
    margin-left: auto;
    flex-shrink: 0;
}

.rundown-btn-action {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: #475569;
    border-radius: 8px;
    padding: 4px 10px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
}
.rundown-btn-action.edit:hover { background: #e0e7ff; color: #4338ca; border-color: #c7d2fe; }
.rundown-btn-action.delete:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }

@media (max-width: 576px) {
    .rundown-card-wrapper {
        padding: 20px 14px;
        border-radius: 14px;
    }
    .rundown-time {
        width: 120px;
        font-size: 0.925rem;
    }
    .rundown-arrow {
        width: 35px;
        font-size: 1.1rem;
    }
    .rundown-agenda {
        font-size: 0.925rem;
    }
}

@media print {
    .print-only-jadwal .rundown-card-wrapper {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .rundown-actions {
        display: none !important;
    }
}

/* Custom Responsive Polish for Buku Lapangan */
@media (max-width: 768px) {
    .quote-marquee-container {
        flex-wrap: wrap;
        border-radius: 1.25rem !important;
        padding: 10px 14px !important;
        gap: 8px !important;
    }
    .quote-marquee-label {
        font-size: 0.72rem !important;
        padding: 4px 10px !important;
    }
    .hero-actions-container {
        padding: 18px !important;
    }
    .display-3 {
        font-size: clamp(2rem, 8vw, 3rem) !important;
    }
    .dpl-message-banner {
        padding: 16px !important;
    }
}

@media (max-width: 480px) {
    .quote-item-badge {
        font-size: 0.82rem !important;
        margin-right: 18px !important;
    }
    .modal-dialog {
        margin: 0.5rem !important;
    }
    .stat-box {
        padding: 14px !important;
    }
    .chip {
        padding: 6px 14px !important;
        font-size: 0.78rem !important;
    }
}

/* Marquee Ticker Kata-Kata Hari Ini */
.quote-marquee-container {
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.22);
    border-radius: 50rem;
    padding: 6px 14px 6px 8px;
    display: flex;
    align-items: center;
    gap: 12px;
    overflow: hidden;
    margin-top: 22px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
}
.quote-marquee-label {
    background: linear-gradient(135deg, #4f46e5, #06b6d4);
    color: #fff;
    font-weight: 700;
    font-size: 0.78rem;
    padding: 6px 14px;
    border-radius: 50rem;
    white-space: nowrap;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    display: flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
}
.quote-marquee-content-wrapper {
    flex: 1;
    overflow: hidden;
    white-space: nowrap;
    position: relative;
}
.quote-marquee-track {
    display: inline-block;
    white-space: nowrap;
    animation: marquee-scroll 40s linear infinite;
}
.quote-marquee-container:hover .quote-marquee-track {
    animation-play-state: paused;
}
@keyframes marquee-scroll {
    0% { transform: translateX(0%); }
    100% { transform: translateX(-50%); }
}
.quote-item-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #fff;
    font-size: 0.88rem;
    margin-right: 28px;
}
.quote-item-text {
    font-style: italic;
    color: rgba(255, 255, 255, 0.95);
}
.quote-item-author {
    font-weight: 700;
    color: #fde047;
}

/* Custom Responsive Enhancements */
.dpl-message-banner {
    padding: 24px;
}
@media (max-width: 576px) {
    .kv-row {
        grid-template-columns: 1fr !important;
        gap: 10px !important;
        margin-bottom: 12px !important;
    }
    .stat-box {
        flex-direction: column !important;
        text-align: center !important;
        gap: 8px !important;
        padding: 16px 12px !important;
        justify-content: center !important;
    }
    .stat-icon {
        margin: 0 auto !important;
    }
}
@media (max-width: 480px) {
    .card-head {
        padding: 12px 14px !important;
        gap: 10px !important;
    }
    .card-body-inner {
        padding: 14px 16px !important;
    }
    .date-tag {
        padding: 6px 10px !important;
        min-width: 46px !important;
    }
    .date-tag .dnum {
        font-size: 1rem !important;
    }
    .card-title {
        font-size: 1rem !important;
    }
    .card-meta {
        font-size: 0.75rem !important;
        gap: 6px !important;
    }
    .div-badge {
        padding: 2px 8px !important;
        font-size: 0.62rem !important;
    }
    .timeline {
        padding-left: 20px !important;
    }
    .timeline::before {
        left: 3px !important;
    }
    .entry-dot {
        left: -20px !important;
        width: 10px !important;
        height: 10px !important;
        top: 20px !important;
        border-width: 3px !important;
    }
}

/* html2pdf container overrides for screen media rendering */
/* html2pdf container overrides for screen media rendering */
.html2pdf-container {
    background: #ffffff !important;
    color: #000000 !important;
    font-family: 'Times New Roman', Times, serif !important;
    box-sizing: border-box !important;
}
.html2pdf-container .print-only-header,
.html2pdf-container .print-only-report,
.html2pdf-container .print-only-jadwal {
    display: block !important;
    font-family: 'Times New Roman', Times, serif !important;
}
.html2pdf-container .print-only-signatures {
    display: block !important;
    font-family: 'Times New Roman', Times, serif !important;
    page-break-inside: avoid !important;
    break-inside: avoid !important;
}
.html2pdf-container .d-print-none {
    display: none !important;
}
.html2pdf-container .formal-report-table {
    width: 100% !important;
    border-collapse: collapse !important;
    margin-top: 8px !important;
    margin-bottom: 15px !important;
    font-family: 'Times New Roman', Times, serif !important;
    table-layout: fixed !important;
}
.html2pdf-container .formal-report-table thead {
    display: table-header-group !important;
}
.html2pdf-container .formal-report-table tr {
    page-break-inside: avoid !important;
    break-inside: avoid !important;
}
.html2pdf-container .formal-report-table th,
.html2pdf-container .formal-report-table td {
    border: 1px solid #000000 !important;
    padding: 7px 9px !important;
    vertical-align: top !important;
    font-size: 9pt !important;
    color: #000000 !important;
    line-height: 1.45 !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
}
.html2pdf-container .formal-report-table th {
    background: #f8fafc !important;
    font-weight: 700 !important;
    text-align: center !important;
    font-size: 9.5pt !important;
    vertical-align: middle !important;
}
.html2pdf-container .rundown-card-wrapper {
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
}

<?php if ($isViewer): ?>
body.is-viewer-mode .upload-section,
body.is-viewer-mode .g-actions-bar,
body.is-viewer-mode .rundown-actions,
body.is-viewer-mode .entry-actions,
body.is-viewer-mode button[onclick*="bukaKelola"],
body.is-viewer-mode button[onclick*="bukaEdit"],
body.is-viewer-mode button[onclick*="edit"],
body.is-viewer-mode button[onclick*="hapus"],
body.is-viewer-mode button[onclick*="delete"],
body.is-viewer-mode .btn-primary.btn-sm.rounded-pill[onclick*="bukaKelola"] {
    display: none !important;
}
<?php endif; ?>
</style>
</head>
<body class="<?= $isViewer ? 'is-viewer-mode' : '' ?>">
<!-- Print Only Jadwal Acara -->
<div class="print-only-jadwal mb-3" id="printJadwalContainer">
    <!-- Diisi otomatis oleh JavaScript -->
</div>

<!-- Print Only Header -->
<div class="print-only-header mb-2" style="border-bottom: 3px double #000; padding-bottom: 8px; font-family: 'Times New Roman', Times, serif; color: #000;">
    <table style="width: 100%; border: none !important; border-collapse: collapse; margin: 0; padding: 0;">
        <tr>
            <td style="width: 80px; text-align: left; vertical-align: middle; border: none !important; padding: 0 !important;">
                <img src="<?= $logoUmpSrc ?>" alt="Logo UMP" style="height: 70px; width: auto; display: block; object-fit: contain;">
            </td>
            <td style="text-align: center; vertical-align: middle; border: none !important; padding: 0 10px !important;">
                <h2 style="font-size: 13.5pt; font-weight: bold; letter-spacing: 0.5px; text-transform: uppercase; margin: 0 0 3px; font-family: 'Times New Roman', Times, serif; line-height: 1.2;">UNIVERSITAS MUHAMMADIYAH PONTIANAK</h2>
                <h3 style="font-size: 11.5pt; font-weight: bold; text-transform: uppercase; margin: 0 0 3px; font-family: 'Times New Roman', Times, serif; line-height: 1.2;">KULIAH KERJA NYATA (KKN) KELOMPOK 12</h3>
                <h4 style="font-size: 10.5pt; font-weight: bold; text-transform: uppercase; color: #000; margin: 0 0 4px; font-family: 'Times New Roman', Times, serif; line-height: 1.2;">LAPORAN KEGIATAN HARIAN (BUKU LAPANGAN)</h4>
                <p style="font-size: 9.5pt; margin: 0 0 2px; font-family: 'Times New Roman', Times, serif; line-height: 1.2;">Kelurahan Pal Lima &middot; Kecamatan Pontianak Barat &middot; Kota Pontianak</p>
                <p style="font-size: 9pt; font-style: italic; color: #334155; margin: 0; font-family: 'Times New Roman', Times, serif; line-height: 1.2;">Periode Pelaksanaan: 20 Juli — 30 Agustus 2026</p>
            </td>
            <td style="width: 80px; text-align: right; vertical-align: middle; border: none !important; padding: 0 !important;">
                <img src="<?= $logoKknSrc ?>" alt="Logo KKN 12" style="height: 65px; width: auto; display: block; margin-left: auto; object-fit: contain;">
            </td>
        </tr>
    </table>
</div>

<!-- Print Only Report Table -->
<div class="print-only-report mt-1">
    <table class="formal-report-table">
        <thead>
            <tr>
                <th style="width: 6%; text-align: center;">No</th>
                <th style="width: 17%;">Hari / Tanggal / Waktu</th>
                <th style="width: 18%;">Kegiatan / Bidang</th>
                <th style="width: 39%;">Deskripsi Kegiatan &amp; Hasil / Output</th>
                <th style="width: 20%;">Lokasi &amp; Sasaran</th>
            </tr>
        </thead>
        <tbody id="printReportTableBody">
            <!-- diisi otomatis oleh JavaScript -->
        </tbody>
    </table>

    <div class="print-only-signatures mt-4 pt-3" style="font-family: 'Times New Roman', Times, serif; color: #000; page-break-inside: avoid;">
        <div class="d-flex justify-content-between text-center" style="margin-top: 30px;">
            <div style="width: 40%;">
                <p class="mb-1">Mengetahui,</p>
                <p class="fw-bold mb-5">Dosen Pembimbing Lapangan (DPL)</p>
                <p class="fw-bold mb-0 text-decoration-underline">(...................................................)</p>
                <p class="small text-muted" style="font-size: 8.5pt;">NIDN. .....................................</p>
            </div>
            <div style="width: 40%;">
                <p class="mb-1">Pontianak, 30 Agustus 2026</p>
                <p class="fw-bold mb-5">Ketua KKN Kelompok 12</p>
                <p class="fw-bold mb-0 text-decoration-underline">Rizki Tri Saputra</p>
                <p class="small text-muted" style="font-size: 8.5pt;">NIM. .....................................</p>
            </div>
        </div>
    </div>
</div>
<!-- Sticky Navbar Top Header -->
<nav class="navbar navbar-expand-lg sticky-top shadow-sm py-2.5 d-print-none" style="background: rgba(15, 23, 42, 0.94); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border-bottom: 1px solid rgba(255, 255, 255, 0.12); z-index: 1050;">
    <div class="container d-flex align-items-center justify-content-between">
        <!-- Brand / Kembali ke Beranda -->
        <a href="index.php" class="d-inline-flex align-items-center gap-2.5 text-white text-decoration-none fw-bold" style="font-size: 0.92rem;">
            <div class="rounded-circle bg-primary bg-opacity-25 p-2 d-flex align-items-center justify-content-center border border-primary border-opacity-40" style="width: 36px; height: 36px;">
                <i class="bi bi-arrow-left text-info fs-6"></i>
            </div>
            <span>Kembali ke Beranda</span>
        </a>

        <!-- User Profile & Tombol Keluar -->
        <?php 
        $fotoMapBuku = [
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
        $currentNamaSession = $_SESSION['nama'] ?? '';
        $userPhotoBuku = $_SESSION['foto'] ?? ($fotoMapBuku[$currentNamaSession] ?? '');
        if (stripos($currentNamaSession, 'Kania') !== false) {
            $userPhotoBuku = '';
        }
        ?>
        <div class="d-flex align-items-center gap-3">
            <div class="d-none d-sm-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-10 border border-white border-opacity-15 text-white" style="font-size: 0.85rem;">
                <?php if (!empty($userPhotoBuku)): ?>
                    <img src="<?= htmlspecialchars($userPhotoBuku) ?>" class="rounded-circle object-fit-cover shadow-sm border border-info border-opacity-50" style="width: 28px; height: 28px;" alt="Foto Profile">
                <?php else: ?>
                    <i class="bi bi-person-circle text-info fs-6"></i>
                <?php endif; ?>
                <span class="fw-semibold"><?= htmlspecialchars($_SESSION['nama']) ?></span>
                <?php if (!empty($_SESSION['jabatan'])): ?>
                    <span class="badge bg-primary bg-opacity-40 text-white border border-primary border-opacity-40 rounded-pill px-2 py-0.5" style="font-size: 10px;"><?= htmlspecialchars($_SESSION['jabatan']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Tombol Keluar dengan Pop-up Konfirmasi -->
            <button type="button" class="btn btn-danger btn-sm rounded-pill px-3.5 py-1.5 fw-bold shadow-sm d-inline-flex align-items-center gap-1.5" 
                    data-bs-toggle="modal" data-bs-target="#confirmLogoutModal" 
                    style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none; font-size: 0.85rem; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.4);">
                <i class="bi bi-box-arrow-right fs-6"></i>
                <span>Keluar</span>
            </button>
        </div>
    </div>
</nav>

<!-- Modal Konfirmasi Logout -->
<div class="modal fade" id="confirmLogoutModal" tabindex="-1" aria-labelledby="confirmLogoutModalLabel" aria-hidden="true" style="z-index: 1090;">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg text-center" style="border-radius: 1.25rem; overflow: hidden; background: #ffffff;">
            <div class="modal-body p-4">
                <div class="rounded-circle bg-danger bg-opacity-10 text-danger mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="bi bi-box-arrow-right fs-2"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Konfirmasi Keluar</h5>
                <p class="text-secondary small mb-4" style="font-size: 0.88rem;">Apakah Anda yakin ingin keluar dari akun <b><?= htmlspecialchars($_SESSION['nama']) ?></b>?</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-light rounded-pill px-3.5 py-2 fw-semibold text-secondary flex-grow-1" data-bs-dismiss="modal" style="font-size: 0.85rem;">Batal</button>
                    <a href="logout.php" class="btn btn-danger rounded-pill px-3.5 py-2 fw-bold flex-grow-1 shadow-sm" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none; font-size: 0.85rem;">Ya, Keluar</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hero -->
<?php if (!$pdo): ?>
<div class="alert alert-warning border-0 shadow m-3 rounded-4 p-4 text-dark" style="background: #fef3c7; border-left: 6px solid #f59e0b !important; position: relative; z-index: 1050;">
    <div class="d-flex align-items-center gap-3">
        <i class="bi bi-exclamation-triangle-fill fs-1 text-warning"></i>
        <div>
            <h5 class="fw-bold text-dark mb-1">Database MySQL di XAMPP Belum Dinyalakan (STOPPED)</h5>
            <p class="mb-0 small text-secondary">
                Seluruh data kegiatan tersimpan aman di database MySQL lokal Anda. Data tidak tampil saat ini karena service <strong>MySQL</strong> di aplikasi XAMPP belum di-start.<br>
                <strong>Cara Mengembalikan Data:</strong> Buka aplikasi <strong>XAMPP Control Panel</strong> di laptop/komputer Anda, kemudian klik tombol <strong>Start</strong> pada baris <strong>MySQL</strong>. Setelah itu refresh halaman ini!
            </p>
        </div>
    </div>
</div>
<?php endif; ?>

<section id="beranda" class="hero-bg text-white">
    <div class="container pt-4 pb-5">
        <div class="row align-items-center">
            <div class="col-lg-11 col-xl-10">
                <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                    <span class="badge bg-primary text-light fs-6">20 Juli — 30 Agustus 2026</span>
                    <?= $isViewer ? '<span class="badge bg-warning text-dark fw-bold px-3 py-1.5 rounded-pill" style="font-size: 0.85rem;"><i class="bi bi-eye-fill me-1"></i>Pelihat (Viewer)</span>' : '' ?>
                </div>
                <h1 class="display-3 fw-bold mb-3">Catatan Kegiatan<br><span class="text-primary">Lapangan</span></h1>

                <!-- Featured Banner: Pesan DPL (Ibu Kania Khairunnisa, M.Psi., Psikolog) -->
                <div class="dpl-message-banner mb-4 rounded-4 shadow-sm text-white" style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.35), rgba(6, 182, 212, 0.35)); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.25);">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning text-dark fw-bold px-3 py-1.5 rounded-pill shadow-sm" style="font-size: 0.78rem;">
                                <i class="bi bi-person-workspace me-1"></i> PESAN DOSEN PEMBIMBING LAPANGAN (DPL)
                            </span>
                            <span class="fw-bold text-white small" style="letter-spacing: 0.02em;"><?= htmlspecialchars($dplRow['nama'] ?? 'Ibu Kania Khairunnisa, M.Psi., Psikolog') ?></span>
                        </div>
                        <?php if ($isDplUser): ?>
                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                            <button class="btn btn-sm btn-light rounded-pill px-3 py-1 fw-semibold text-primary shadow-sm" onclick="bukaPopUpKataKataDpl()" style="font-size: 0.78rem;">
                                <i class="bi bi-pencil-square me-1"></i> Edit Pesan Saya
                            </button>
                            <button class="btn btn-sm btn-outline-light rounded-pill px-3 py-1 fw-semibold shadow-sm" onclick="resetKataKataKania()" style="font-size: 0.78rem;" title="Reset Kata-Kata Ibu Kania agar Pop-Up muncul kembali saat login">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Kata-Kata
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-start gap-2.5 pt-1">
                        <i class="bi bi-quote fs-3 text-warning opacity-75 leading-none"></i>
                        <div class="fst-italic text-white" style="font-size: 1.05rem; text-shadow: 0 2px 4px rgba(0,0,0,0.3);" id="dplMessageTextDisplay">
                            <?php if (!empty($dplRow['kata_kata'])): ?>
                                "<?= htmlspecialchars($dplRow['kata_kata']) ?>"
                            <?php else: ?>
                                <span class="opacity-75">Belum ada kata-kata dari Ibu DPL. Kata-kata/pesan motivasi akan tampil di sini setelah Ibu Kania mengisinya pada pop-up.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="hero-actions-container">
                    <div class="row g-4 align-items-stretch">
                        <div class="col-lg-5 col-md-12">
                            <span class="hero-actions-title"><i class="bi bi-file-earmark-pdf fs-5"></i> Ekspor PDF Laporan</span>
                            <div class="d-flex flex-column gap-2">
                                <button class="btn btn-emerald text-start w-100" onclick="siapkanPDF()">
                                    <i class="bi bi-download me-2 fs-6"></i> <span>Unduh Buku Lapangan (PDF)</span>
                                </button>
                                <button class="btn btn-purple text-start w-100" onclick="siapkanJadwalPDF()">
                                    <i class="bi bi-calendar-event me-2 fs-6"></i> <span>Unduh Jadwal Acara (PDF)</span>
                                </button>
                            </div>
                        </div>
                        <div class="col-lg-7 col-md-12 border-lg-start border-top border-lg-top-0 border-white border-opacity-10 pt-4 pt-lg-0 ps-lg-4">
                            <span class="hero-actions-title"><i class="bi bi-sliders fs-5"></i> Kelola Data &amp; Input</span>
                            <div class="row g-2">
                                <div class="col-12 col-sm-6">
                                    <button type="button" class="btn hero-btn-solid-blue text-start w-100 h-100" data-bs-toggle="modal" data-bs-target="#kegiatanModal" onclick="tambahKegiatanBaru()">
                                        <i class="bi bi-plus-lg me-2 fs-6"></i> <span>Catat Kegiatan Harian</span>
                                    </button>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <button type="button" class="btn hero-btn-solid-blue text-start w-100 h-100" data-bs-toggle="modal" data-bs-target="#programKerjaModal" onclick="loadProgramKerja()">
                                        <i class="bi bi-briefcase me-2 fs-6"></i> <span>Kelola Program &amp; Bidang</span>
                                    </button>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <button type="button" class="btn hero-btn-solid-blue text-start w-100 h-100" onclick="bukaKelolaJadwalAcaraModal()">
                                        <i class="bi bi-calendar3 me-2 fs-6"></i> <span>Kelola Jadwal Acara</span>
                                    </button>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <button type="button" class="btn hero-btn-solid-blue text-start w-100 h-100" onclick="bukaModalBuatSurat()">
                                        <i class="bi bi-envelope-paper me-2 fs-6 text-warning"></i> <span>Buat Surat Dinas KKN 12</span>
                                    </button>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <button type="button" class="btn hero-btn-solid-blue text-start w-100 h-100" onclick="bukaModalNotulensi()">
                                        <i class="bi bi-journal-text me-2 fs-6 text-info"></i> <span>Notulensi &amp; Surat Masuk</span>
                                    </button>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <button type="button" class="btn hero-btn-solid-blue text-start w-100 h-100" onclick="bukaModalKontakHumas()">
                                        <i class="bi bi-telephone-outbound me-2 fs-6 text-success"></i> <span>Kontak Humas &amp; Stakeholder</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Marquee Running Text Kata-Kata Hari Ini -->
                <div class="quote-marquee-container">
                    <div class="quote-marquee-label">
                        <i class="bi bi-chat-quote-fill fs-6"></i> Kata-Kata Hari Ini
                    </div>
                    <div class="quote-marquee-content-wrapper">
                        <div class="quote-marquee-track" id="quoteMarqueeTrack">
                            <!-- Diisi otomatis oleh JavaScript -->
                        </div>
                    </div>
                    <?php if (!$isViewer): ?>
                    <button class="btn btn-sm btn-light rounded-pill px-3 py-1 fw-semibold small flex-shrink-0" onclick="bukaKelolaKataKataModal()" style="font-size: 0.78rem;">
                        <i class="bi bi-pencil-fill me-1 text-primary"></i> Edit Kata-Kata
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistik -->
<section class="stat-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4 col-6 mb-4">
                <div class="stat-box">
                    <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
                    <h2 class="fw-bold"><?= $totalKegiatan ?></h2>
                    <p class="text-muted">Kegiatan</p>
                </div>
            </div>
            <div class="col-md-4 col-6 mb-4">
                <div class="stat-box position-relative overflow-hidden">
                    <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
                    <h2 class="fw-bold mb-0"><?= $totalJam ?> <span class="fs-6 text-muted fw-normal">/ 150 Jam</span></h2>
                    <p class="text-muted mb-2">Jam Lapangan</p>
                    <div class="progress rounded-pill mt-2" style="height: 6px; background-color: rgba(79, 70, 229, 0.12);">
                        <div class="progress-bar rounded-pill" role="progressbar" style="width: <?= $persenJam ?>%; background: linear-gradient(90deg, #4f46e5 0%, #6366f1 100%);" aria-valuenow="<?= $persenJam ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1 px-1" style="font-size: 0.75rem;">
                        <span class="text-muted">Target 150 Jam</span>
                        <span class="fw-bold text-primary"><?= $persenJam ?>%</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-12 mb-4">
                <div class="stat-box">
                    <div class="stat-icon"><i class="bi bi-images"></i></div>
                    <h2 class="fw-bold"><?= $totalFoto ?></h2>
                    <p class="text-muted">Foto Dokumentasi</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Search & Filter -->
<section class="search-section">
    <div class="container">
        <div class="search-filter-row">
            <div class="search-container">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="form-control search-input" id="searchInput"
                    placeholder="Cari kegiatan, foto, atau anggota...">
                <button type="button" class="search-clear" id="searchClear" aria-label="Hapus pencarian" style="display:none;">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
            </div>
            <div class="filter-chips" id="filterChips">
                <button class="chip active" data-filter="semua">Semua</button>
                <?php foreach ($bidangList as $b): ?>
                <button class="chip" data-filter="<?= htmlspecialchars($b['kode']) ?>"><?= htmlspecialchars($b['nama']) ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- Catat Kegiatan (Modal) -->
<div class="modal fade" id="kegiatanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius:1rem;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-journal-plus text-primary me-2"></i>Catat Kegiatan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body pt-2">
                <div id="kegiatanAlert" class="alert alert-danger small py-2 d-none"></div>
                <form id="kegiatanForm">
                    <input type="hidden" name="id" id="kegiatanId" value="">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Judul Kegiatan</label>
                            <input type="text" class="form-control" name="judul" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Bidang</label>
                            <input type="text" class="form-control" name="bidang" list="bidangOptions"
                                placeholder="Ketik bidang..." autocomplete="off">
                            <datalist id="bidangOptions">
                                <?php foreach ($bidangList as $b): ?>
                                <option value="<?= htmlspecialchars($b['nama']) ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <div class="form-text">Ketik bebas — kalau bidang belum ada, otomatis dibuat baru.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal</label>
                            <input type="date" class="form-control" name="tanggal" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Jam Mulai</label>
                            <input type="text" class="form-control" name="jam_mulai" placeholder="Contoh: 08.00 WIB atau 08:00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Jam Selesai</label>
                            <input type="text" class="form-control" name="jam_selesai" placeholder="Contoh: Selesai atau 10.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Lokasi</label>
                            <input type="text" class="form-control" name="lokasi">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Sasaran</label>
                            <input type="text" class="form-control" name="sasaran">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Deskripsi Kegiatan</label>
                            <textarea class="form-control" name="deskripsi" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Hasil / Output</label>
                            <textarea class="form-control" name="hasil" rows="2"></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-4" id="kegiatanSubmitBtn">
                        <span id="kegiatanBtnText">Simpan Kegiatan</span>
                        <span id="kegiatanBtnSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Foto Dokumentasi Di-upload Langsung di Setiap Kartu Log Kegiatan -->

<!-- Log & Jadwal KKN -->
<section id="kegiatan" class="timeline-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div class="section-heading mb-0">
                <h3 class="fw-bold mb-1">Catatan &amp; Jadwal KKN</h3>
                <p class="text-muted mb-0 small">Kelola dan pantau seluruh agenda serta kegiatan lapangan secara terorganisir</p>
            </div>
            
            <!-- Tab Toggle -->
            <div class="nav nav-pills bg-white p-1 rounded-4 shadow-sm border border-light d-flex flex-wrap gap-1" id="logTab" role="tablist">
                <button class="nav-link active rounded-3 px-3 py-2 small fw-bold" id="tab-log-kegiatan" data-bs-toggle="pill" data-bs-target="#content-log-kegiatan" type="button" role="tab" aria-selected="true">
                    <i class="bi bi-journal-text me-1.5"></i>Log Kegiatan
                </button>
                <button class="nav-link rounded-3 px-3 py-2 small fw-bold" id="tab-jadwal-acara" data-bs-toggle="pill" data-bs-target="#content-jadwal-acara" type="button" role="tab" aria-selected="false">
                    <i class="bi bi-calendar-event me-1.5"></i>Jadwal Acara
                </button>
                <button class="nav-link rounded-3 px-3 py-2 small fw-bold" id="tab-jadwal-piket" data-bs-toggle="pill" data-bs-target="#content-jadwal-piket" type="button" role="tab" aria-selected="false">
                    <i class="bi bi-people-fill me-1.5"></i>Jadwal Piket Posko
                </button>
                <button class="nav-link rounded-3 px-3 py-2 small fw-bold" id="tab-info-lainnya" data-bs-toggle="pill" data-bs-target="#content-info-lainnya" type="button" role="tab" aria-selected="false">
                    <i class="bi bi-box-seam-fill me-1.5"></i>Informasi Lainnya
                </button>
            </div>
        </div>
        
        <div class="tab-content" id="logTabContent">
            <!-- Tab 1: Log Kegiatan -->
            <div class="tab-pane fade show active" id="content-log-kegiatan" role="tabpanel" aria-labelledby="tab-log-kegiatan">
                <div class="timeline" id="timeline">
                    <!-- diisi otomatis oleh JavaScript -->
                </div>
            </div>
            
            <!-- Tab 2: Jadwal Acara -->
            <div class="tab-pane fade" id="content-jadwal-acara" role="tabpanel" aria-labelledby="tab-jadwal-acara">
                <div class="timeline" id="timeline-jadwal-acara">
                    <!-- diisi otomatis oleh JavaScript -->
                </div>
            </div>

            <!-- Tab 3: Jadwal Piket Posko -->
            <div class="tab-pane fade" id="content-jadwal-piket" role="tabpanel" aria-labelledby="tab-jadwal-piket">
                <div class="timeline" id="timeline-jadwal-piket">
                    <!-- diisi otomatis oleh JavaScript -->
                </div>
            </div>

            <!-- Tab 4: Informasi Lainnya -->
            <div class="tab-pane fade" id="content-info-lainnya" role="tabpanel" aria-labelledby="tab-info-lainnya">
                <div class="bg-white rounded-4 shadow-sm border p-4">
                    <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom flex-wrap gap-2">
                        <div>
                            <span class="badge bg-primary text-white fw-bold px-3 py-1.5 rounded-pill text-uppercase" style="font-size: 0.75rem;">INFORMASI LAINNYA</span>
                            <h3 class="fw-bold text-dark mb-0 mt-1">Daftar Perlengkapan &amp; Penanggung Jawab Posko</h3>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1.5 fw-semibold" onclick="bukaKelolaInfoPoskoModal()">
                                <i class="bi bi-plus-lg me-1"></i> Tambah Informasi
                            </button>
                            <span class="badge bg-light text-muted border px-3 py-2 rounded-pill" id="infoPoskoCountBadge"><i class="bi bi-box-seam me-1 text-primary"></i><?= count($infoPoskoRows) ?> Item Perlengkapan</span>
                        </div>
                    </div>

                    <div class="row g-3" id="infoPoskoCardsContainer">
                        <!-- Diisi otomatis oleh JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>



<!-- Galeri Dokumentasi -->
<section id="galeri" class="galeri-section">
    <div class="container">
        <div class="section-heading">
            <h3 class="fw-bold">Galeri Dokumentasi</h3>
            <p class="text-muted">Kumpulan foto dari seluruh kegiatan lapangan</p>
        </div>
        <div class="galeri-grid" id="galeriGrid"></div>
    </div>
</section>

<!-- Modal Konfirmasi Hapus Kegiatan -->
<div class="modal fade" id="hapusKegiatanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem;">
            <div class="modal-header border-0 pb-0 justify-content-end">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body text-center pt-0 px-4 pb-4">
                <div class="text-danger mb-3" style="font-size: 3rem;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <h5 class="fw-bold mb-2">Hapus Kegiatan?</h5>
                <p class="text-muted small mb-4">Apakah Anda yakin ingin menghapus kegiatan ini beserta seluruh foto dan data absensi yang terkait? Tindakan ini tidak dapat dibatalkan.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-secondary w-50" style="border-radius: 10px;" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger w-50" style="border-radius: 10px;" id="confirmHapusBtn">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Kelola Jadwal Acara Terpisah -->
<div class="modal fade" id="kelolaJadwalAcaraModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg" style="border-radius:1rem;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-calendar3 text-primary me-2"></i>Kelola Jadwal &amp; Acara KKN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body pt-2">
                <!-- Form Entry Jadwal Acara -->
                <div class="card p-3 border-0 bg-light mb-4">
                    <p class="fw-semibold small mb-2" id="jadwalAcaraFormLabel"><i class="bi bi-plus-circle me-1 text-primary"></i>Tambah Jadwal Acara Baru</p>
                    <form id="jadwalAcaraForm">
                        <input type="hidden" name="id" id="jadwalAcaraId" value="">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Tanggal</label>
                                <input type="date" class="form-control form-control-sm" name="tanggal" id="jadwalAcaraTanggal" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Waktu (e.g. 08:00 - 10:00)</label>
                                <input type="text" class="form-control form-control-sm" name="waktu" id="jadwalAcaraWaktu" required placeholder="Contoh: 08:00 - 10:00">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Agenda / Acara</label>
                                <input type="text" class="form-control form-control-sm" name="agenda" id="jadwalAcaraAgenda" required placeholder="Contoh: Sosialisasi PHBS">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">PIC / PJ</label>
                                <input type="text" class="form-control form-control-sm" name="pic" id="jadwalAcaraPic" placeholder="Nama PIC">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Keterangan</label>
                                <div class="d-flex gap-2">
                                    <input type="text" class="form-control form-control-sm" name="keterangan" id="jadwalAcaraKeterangan" placeholder="Catatan">
                                    <button type="submit" class="btn btn-sm btn-primary px-3" id="jadwalAcaraSubmitBtn" style="border-radius: 8px;">Simpan</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Table Jadwal Acara Items -->
                <p class="fw-semibold small mb-2"><i class="bi bi-list-task me-1 text-primary"></i>Daftar Jadwal Acara KKN</p>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle" style="font-size: 0.9rem;">
                        <thead>
                            <tr>
                                <th style="width: 15%;">Hari, Tanggal</th>
                                <th style="width: 15%;">Waktu</th>
                                <th style="width: 30%;">Agenda / Acara</th>
                                <th style="width: 15%;">PIC</th>
                                <th style="width: 15%;">Keterangan</th>
                                <th style="width: 10%;" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="jadwalAcaraTableBody">
                            <!-- Diisi AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Kelola Jadwal Piket Posko -->
<div class="modal fade" id="kelolaJadwalPiketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius:1rem;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-people-fill text-primary me-2"></i>Kelola Jadwal Piket Posko KKN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body pt-2">
                <!-- Form Entry Jadwal Piket -->
                <div class="card p-3 border-0 bg-light mb-4">
                    <p class="fw-semibold small mb-2" id="jadwalPiketFormLabel"><i class="bi bi-plus-circle me-1 text-primary"></i>Input / Edit Jadwal Piket</p>
                    <form id="jadwalPiketForm">
                        <input type="hidden" name="id" id="jadwalPiketId" value="">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Hari Piket</label>
                                <select class="form-select form-select-sm" name="hari" id="jadwalPiketHari" required>
                                    <option value="Senin">Senin</option>
                                    <option value="Selasa">Selasa</option>
                                    <option value="Rabu">Rabu</option>
                                    <option value="Kamis">Kamis</option>
                                    <option value="Jumat">Jumat</option>
                                    <option value="Sabtu">Sabtu</option>
                                    <option value="Minggu">Minggu</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Waktu / Shift</label>
                                <input type="text" class="form-control form-control-sm" name="shift" id="jadwalPiketShift" placeholder="Contoh: 08:00 - 17:00" value="08:00 - 17:00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Jenis Tugas Piket</label>
                                <input type="text" class="form-control form-control-sm" name="tugas" id="jadwalPiketTugas" placeholder="Contoh: Piket Posko & Kebersihan" value="Piket Posko & Kebersihan">
                            </div>
                        </div>

                        <!-- Checkbox Pilihan Anggota Kelompok -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold d-block"><i class="bi bi-person-check-fill me-1 text-primary"></i>Pilih Petugas Piket (Anggota Kelompok 12):</label>
                            <div class="d-flex flex-wrap gap-2 p-2.5 bg-white rounded-3 border">
                                <?php foreach ($anggotaRows as $m): ?>
                                    <div class="form-check me-2">
                                        <input class="form-check-input piket-petugas-cb" type="checkbox" name="petugas[]" value="<?= htmlspecialchars($m['nama']) ?>" id="cb_piket_<?= $m['id'] ?>">
                                        <label class="form-check-label small fw-medium" for="cb_piket_<?= $m['id'] ?>">
                                            <?= htmlspecialchars($m['nama']) ?> <span class="text-muted" style="font-size: 0.75rem;">(<?= htmlspecialchars($m['jabatan']) ?>)</span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Catatan / Keterangan Tambahan (Opsional)</label>
                            <input type="text" class="form-control form-control-sm" name="keterangan" id="jadwalPiketKeterangan" placeholder="Contoh: Menyiapkan konsumsi & piket posko utama">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm px-4" id="jadwalPiketSubmitBtn" style="border-radius: 8px;">
                                <i class="bi bi-save me-1"></i> Simpan Jadwal Piket
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="resetJadwalPiketForm()" style="border-radius: 8px;">
                                Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Table Jadwal Piket Items -->
                <p class="fw-semibold small mb-2"><i class="bi bi-list-task me-1 text-primary"></i>Daftar Jadwal Piket KKN</p>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle" style="font-size: 0.9rem;">
                        <thead>
                            <tr>
                                <th style="width: 12%;">Hari</th>
                                <th style="width: 15%;">Shift</th>
                                <th style="width: 25%;">Tugas</th>
                                <th style="width: 38%;">Petugas Piket</th>
                                <th style="width: 10%;" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="jadwalPiketTableBody">
                            <!-- Diisi AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pop-Up Modal Otomatis Kata-Kata Hari Ini saat Login (Wajib Isi: DPL & Seluruh Member) -->
<div class="modal fade" id="autoPopUpKataKataModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem;">
            <div class="modal-header border-0 pb-0 text-center d-block position-relative pt-4">
                <div class="rounded-circle bg-primary-subtle text-primary mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:64px; height:64px;">
                    <i class="bi bi-chat-quote-fill fs-2 text-primary"></i>
                </div>
                <h5 class="modal-title fw-bold text-dark mb-1">
                    <?= $isDplUser ? 'Selamat Datang, Ibu DPL!' : 'Halo, ' . htmlspecialchars($currentNamaUser) . '!' ?>
                </h5>
                <p class="text-muted small mb-0 px-3">
                    <?= $isDplUser ? 'Ibu Kania Khairunnisa, M.Psi., Psikolog kasih kata katanya untuk kelompok 12 dong ✨' : htmlspecialchars($currentNamaUser) . ', kasih kata katanya hari ini dong ✨' ?>
                </p>
            </div>
            <div class="modal-body p-4 pt-3">
                <form id="autoFormKataKata">
                    <input type="hidden" name="anggota_id" value="<?= $currentAnggotaId ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Kata-Kata Hari Ini / Motto Motivasional</label>
                        <textarea class="form-control rounded-3 p-3" name="kata_kata" id="autoInputKataKata" rows="4" placeholder="Tuliskan kata-kata hari ini..." required><?= htmlspecialchars($currentQuote) ?></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm" id="autoBtnSimpanKataKata" style="font-size: 1rem;">
                            <i class="bi bi-check-circle-fill me-1"></i> OK (Simpan &amp; Tampilkan)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Kata-Kata Hari Ini -->
<div class="modal fade" id="kelolaKataKataModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:1rem;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-chat-quote-fill text-primary me-2"></i>Kata-Kata Hari Ini (Quote KKN)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body pt-2">
                <form id="kataKataForm">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Pemilik Kata-Kata (Akun Anda)</label>
                        <input type="text" class="form-control form-control-sm bg-light fw-bold text-dark" value="<?= htmlspecialchars($_SESSION['nama'] ?? '') ?>" readonly disabled>
                        <input type="hidden" name="anggota_id" value="<?= $_SESSION['anggota_id'] ?? 0 ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kata-Kata Hari Ini / Motto Motivasional</label>
                        <textarea class="form-control form-control-sm" name="kata_kata" id="kataKataInput" rows="3" placeholder="Tuliskan kata-kata hari ini atau kutipan motivasi..." required></textarea>
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-2 pt-2">
                        <?php if ($isDplUser): ?>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 me-auto" onclick="resetKataKataKania()" title="Reset kata-kata agar pop-up muncul kembali saat login">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Kata-Kata
                        </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4" id="simpanKataKataBtn">
                            <i class="bi bi-send me-1"></i> Simpan &amp; Tampilkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Kelola Informasi Posko -->
<div class="modal fade" id="kelolaInfoPoskoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius:1rem;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-box-seam-fill text-primary me-2"></i>Kelola Informasi &amp; Perlengkapan Posko</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body pt-2">
                <!-- Form Entry Informasi Posko -->
                <div class="card p-3 border-0 bg-light mb-4">
                    <p class="fw-semibold small mb-2" id="infoPoskoFormLabel"><i class="bi bi-plus-circle me-1 text-primary"></i>Tambah / Edit Informasi Posko</p>
                    <form id="infoPoskoForm">
                        <input type="hidden" name="id" id="infoPoskoId" value="">
                        <div class="row g-3 mb-3">
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Nama Barang / Tugas</label>
                                <input type="text" class="form-control form-control-sm" name="nama_barang" id="infoPoskoNama" placeholder="Contoh: Kompor / Nyedot Air Galon" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Kategori</label>
                                <input type="text" class="form-control form-control-sm" name="kategori" id="infoPoskoKategori" placeholder="Contoh: Perlengkapan Dapur" value="Perlengkapan Posko">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Penanggung Jawab</label>
                                <input type="text" class="form-control form-control-sm" name="penanggung_jawab" id="infoPoskoPj" placeholder="Contoh: Diah / Abel / Masing-masing" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Catatan / Keterangan (Opsional)</label>
                            <input type="text" class="form-control form-control-sm" name="keterangan" id="infoPoskoKeterangan" placeholder="Contoh: Digunakan untuk masak posko harian">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm px-4" id="infoPoskoSubmitBtn" style="border-radius: 8px;">
                                <i class="bi bi-save me-1"></i> Simpan Data
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="resetInfoPoskoForm()" style="border-radius: 8px;">
                                Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Table Items -->
                <p class="fw-semibold small mb-2"><i class="bi bi-list-task me-1 text-primary"></i>Daftar Informasi &amp; Perlengkapan Posko</p>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle" style="font-size: 0.9rem;">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Nama Barang / Tugas</th>
                                <th style="width: 25%;">Kategori</th>
                                <th style="width: 25%;">Penanggung Jawab</th>
                                <th style="width: 15%;">Catatan</th>
                                <th style="width: 10%;" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="infoPoskoTableBody">
                            <!-- Diisi AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Deskripsi Foto Galeri -->
<div class="modal fade" id="editFotoGaleriModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Deskripsi Foto Galeri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="text-center mb-3">
                    <img id="editFotoGaleriPreview" src="" alt="Preview" style="max-height: 180px; width: auto; border-radius: 12px; object-fit: contain;" class="shadow-sm border">
                </div>
                <form id="editFotoGaleriForm">
                    <input type="hidden" name="id" id="editFotoGaleriId">
                    <input type="hidden" name="type" id="editFotoGaleriType">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Deskripsi / Judul Foto</label>
                        <input type="text" class="form-control" name="caption" id="editFotoGaleriCaption" placeholder="Ketik deskripsi kegiatan di foto ini..." required>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4" id="editFotoGaleriSubmitBtn"><i class="bi bi-check-lg me-1"></i>Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Input Google Drive Foto -->
<div class="modal fade" id="gdriveFotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-google-drive text-success me-2"></i>Tambah Foto dari Google Drive</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="text-muted small mb-3" id="gdriveModalSub">Masukkan tautan/link foto publik dari Google Drive.</p>
                <form id="gdriveFotoForm">
                    <input type="hidden" name="kegiatan_id" id="gdriveKegiatanId">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Link Google Drive / URL Foto</label>
                        <textarea class="form-control form-control-sm" name="gdrive_url" id="gdriveUrlInput" rows="3" placeholder="Contoh: https://drive.google.com/file/d/1A2B3C4D5E.../view?usp=sharing&#10;(Bisa isi beberapa link dipisahkan baris baru)" required></textarea>
                        <div class="form-text" style="font-size: 0.76rem;"><i class="bi bi-info-circle me-1"></i>Pastikan akses berbagi file Google Drive disetel ke <strong>"Siapa saja yang memiliki link"</strong>.</div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4" id="gdriveSubmitBtn">
                            <i class="bi bi-check-lg me-1"></i> Simpan Foto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Kelola Rundown Kegiatan -->
<div class="modal fade" id="kegiatanRundownModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="rundownModalTitle"><i class="bi bi-calendar3 text-primary me-2"></i>Kelola Rundown Acara</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body pt-2">
                <!-- Form Entry Rundown -->
                <div class="card p-3 border-0 bg-light mb-4">
                    <p class="fw-semibold small mb-2" id="rundownFormLabel"><i class="bi bi-plus-circle me-1 text-primary"></i>Tambah Agenda Baru</p>
                    <form id="rundownForm">
                        <input type="hidden" name="id" id="rundownId" value="">
                        <input type="hidden" name="kegiatan_id" id="rundownKegiatanId" value="">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Waktu (e.g. 08:00-09:00)</label>
                                <input type="text" class="form-control form-control-sm" name="waktu" id="rundownWaktu" required placeholder="Contoh: 08:00 - 08:30">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Agenda / Acara</label>
                                <input type="text" class="form-control form-control-sm" name="agenda" id="rundownAgenda" required placeholder="Contoh: Registrasi &amp; Pembukaan">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">PIC (Seksi Acara)</label>
                                <input type="text" class="form-control form-control-sm" name="pic" id="rundownPic" placeholder="Nama PIC">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Keterangan / Catatan</label>
                                <div class="d-flex gap-2">
                                    <input type="text" class="form-control form-control-sm" name="keterangan" id="rundownKeterangan" placeholder="Catatan tambahan">
                                    <button type="submit" class="btn btn-sm btn-primary px-3" id="rundownSubmitBtn" style="border-radius: 8px;">Simpan</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Table Rundown Items -->
                <p class="fw-semibold small mb-2"><i class="bi bi-list-stars me-1 text-primary"></i>Jadwal / Rundown Acara</p>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle" style="font-size: 0.9rem;">
                        <thead>
                            <tr>
                                <th style="width: 20%;">Waktu</th>
                                <th style="width: 35%;">Agenda / Acara</th>
                                <th style="width: 15%;">PIC</th>
                                <th style="width: 20%;">Keterangan</th>
                                <th style="width: 10%;" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="rundownTableBody">
                            <!-- Diisi AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Kelola Program Kerja -->
<div class="modal fade" id="programKerjaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg" style="border-radius:1rem;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-briefcase text-primary me-2"></i>Kelola Program Kerja KKN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body pt-2">
                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs mb-3" id="prokjaTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#tab-list" type="button" role="tab" aria-controls="tab-list" aria-selected="true"><i class="bi bi-list-task me-1"></i>Daftar Program</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="form-tab" data-bs-toggle="tab" data-bs-target="#tab-form" type="button" role="tab" aria-controls="tab-form" aria-selected="false" onclick="onProkjaFormTabClick()"><i class="bi bi-plus-circle me-1"></i>Tambah / Edit Program</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="bidang-tab" data-bs-toggle="tab" data-bs-target="#tab-bidang" type="button" role="tab" aria-controls="tab-bidang" aria-selected="false" onclick="bukaKelolaBidang()"><i class="bi bi-tags me-1"></i>Kelola Bidang KKN</button>
                    </li>
                    <li class="nav-item d-none" role="presentation" id="tab-photo-nav">
                        <button class="nav-link" id="photo-tab" data-bs-toggle="tab" data-bs-target="#tab-photo" type="button" role="tab" aria-controls="tab-photo" aria-selected="false"><i class="bi bi-images me-1"></i>Kelola Foto &amp; Deskripsi</button>
                    </li>
                </ul>
                
                <div class="tab-content" id="prokjaTabsContent">
                    <!-- Tab 1: Daftar Program -->
                    <div class="tab-pane fade show active" id="tab-list" role="tabpanel" aria-labelledby="list-tab">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Bidang</th>
                                        <th>Nama / Judul Program</th>
                                        <th>Periode</th>
                                        <th>Status</th>
                                        <th>Foto Cover</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="prokjaTableBody">
                                    <!-- Diisi AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Tab 2: Form Tambah/Edit Program -->
                    <div class="tab-pane fade" id="tab-form" role="tabpanel" aria-labelledby="form-tab">
                        <div id="prokjaAlert" class="alert alert-danger small py-2 d-none"></div>
                        <form id="prokjaForm">
                            <input type="hidden" name="id" id="prokjaId" value="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label small fw-semibold mb-0">Bidang KKN</label>
                                        <span class="text-primary small fw-semibold cursor-pointer" onclick="toggleCustomBidangInput()" style="font-size: 0.78rem; cursor: pointer;">
                                            <i class="bi bi-plus-circle-fill me-1"></i>+ Ketik Bidang Baru
                                        </span>
                                    </div>
                                    <select class="form-select" name="bidang_id" id="prokjaBidangId" required onchange="onBidangSelectChange(this.value)">
                                        <option value="">Pilih bidang...</option>
                                        <?php foreach ($bidangList as $b): ?>
                                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nama']) ?></option>
                                        <?php endforeach; ?>
                                        <option value="custom" style="font-weight: 700; color: #4f46e5;">+ Input Bidang Sendiri (Ketik Baru)...</option>
                                    </select>
                                    <div id="customBidangWrapper" class="mt-2 d-none">
                                        <input type="text" class="form-control form-control-sm border-primary" name="bidang_custom" id="prokjaBidangCustom" placeholder="Ketik nama Bidang KKN baru di sini...">
                                        <div class="form-text text-muted" style="font-size: 0.75rem;"><i class="bi bi-info-circle me-1"></i>Bidang baru akan otomatis tersimpan ke daftar Bidang KKN.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Judul Program Kerja</label>
                                    <input type="text" class="form-control" name="judul" id="prokjaJudul" required placeholder="Contoh: Program Pendidikan">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Periode Pelaksanaan</label>
                                    <input type="text" class="form-control" name="periode" id="prokjaPeriode" value="Juli – Agustus 2026" placeholder="Contoh: Juli – Agustus 2026">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Status Penerbitan</label>
                                    <select class="form-select" name="status" id="prokjaStatus">
                                        <option value="terbit">Terbitkan (Tampil di Beranda)</option>
                                        <option value="draft">Draft (Sembunyikan)</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label small fw-semibold">Link Program / Website</label>
                                    <input type="url" class="form-control" name="link" id="prokjaLink" value="https://kkn12.ct.ws/?i=1" placeholder="https://kkn12.ct.ws/?i=1">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Deskripsi Singkat Program</label>
                                    <textarea class="form-control" name="deskripsi" id="prokjaDeskripsi" rows="4" required placeholder="Jelaskan mengenai program kerja ini..."></textarea>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <button type="button" class="btn btn-secondary" onclick="document.getElementById('list-tab').click()">Batal</button>
                                <button type="submit" class="btn btn-primary" id="prokjaSubmitBtn">Simpan Program</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Tab 3: Kelola Foto & Deskripsi -->
                    <div class="tab-pane fade" id="tab-photo" role="tabpanel" aria-labelledby="photo-tab">
                        <h6 class="fw-bold mb-3" id="photoProgramTitle">Program Kerja: -</h6>
                        
                        <!-- Upload Form -->
                        <div class="card p-3 border-0 bg-light mb-4">
                            <p class="fw-semibold small mb-2"><i class="bi bi-cloud-arrow-up me-1 text-primary"></i>Tambah Foto Dokumentasi</p>
                            <form id="prokjaPhotoForm" enctype="multipart/form-data">
                                <input type="hidden" name="program_kerja_id" id="photoProgramId">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold">Pilih File Foto</label>
                                        <input type="file" class="form-control form-control-sm" name="foto" id="prokjaFotoFileInput" accept="image/*">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold">Atau Link Google Drive</label>
                                        <input type="url" class="form-control form-control-sm" name="gdrive_url" id="prokjaFotoGdriveInput" placeholder="https://drive.google.com/file/d/...">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-semibold">Judul Foto</label>
                                        <input type="text" class="form-control form-control-sm" name="judul" placeholder="Contoh: Mengajar Anak">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-semibold">Deskripsi Foto</label>
                                        <input type="text" class="form-control form-control-sm" name="deskripsi" placeholder="Detail kegiatan...">
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="is_cover" value="1" id="photoIsCover">
                                            <label class="form-check-label small" for="photoIsCover">
                                                Cover
                                            </label>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-primary w-100" id="photoSubmitBtn"><i class="bi bi-plus-lg me-1"></i> Simpan</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Grid Foto Dokumentasi -->
                        <p class="fw-semibold small mb-2"><i class="bi bi-images me-1 text-primary"></i>Foto yang Sudah Diunggah</p>
                        <div class="row g-3" id="prokjaPhotoGrid">
                            <!-- Diisi AJAX -->
                        </div>
                    </div>
                    
                    <!-- Tab 4: Kelola Bidang KKN -->
                    <div class="tab-pane fade" id="tab-bidang" role="tabpanel" aria-labelledby="bidang-tab">
                        <div id="bidangAlert" class="alert alert-danger small py-2 d-none"></div>
                        
                        <!-- Form Tambah/Edit Bidang -->
                        <div class="card p-3 border-0 bg-light mb-4">
                            <p class="fw-semibold small mb-2" id="bidangFormLabel"><i class="bi bi-plus-circle me-1 text-primary"></i>Tambah Bidang Baru</p>
                            <form id="bidangForm">
                                <input type="hidden" name="id" id="bidangId" value="">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-8">
                                        <label class="form-label small fw-semibold">Nama Bidang KKN / Program Kerja</label>
                                        <input type="text" class="form-control form-control-sm" name="nama" id="bidangNama" required placeholder="Contoh: Bidang Kesehatan Lingkungan">
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-sm btn-primary w-100" id="bidangSubmitBtn" style="border-radius: 8px;">Simpan</button>
                                            <button type="button" class="btn btn-sm btn-secondary" onclick="resetBidangForm()" style="border-radius: 8px;">Batal</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Table Daftar Bidang KKN -->
                        <p class="fw-semibold small mb-2"><i class="bi bi-list-task me-1 text-primary"></i>Daftar Bidang KKN</p>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle" style="font-size: 0.9rem;">
                                <thead>
                                    <tr>
                                        <th style="width: 15%;">ID</th>
                                        <th style="width: 25%;">Kode / Tag</th>
                                        <th style="width: 45%;">Nama Bidang KKN</th>
                                        <th style="width: 15%;" class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="bidangTableBody">
                                    <!-- Diisi AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    </div>
</div>

<!-- Modal Buat Surat Resmi / Dinas KKN Kelompok 12 -->
<div class="modal fade" id="suratKknModal" tabindex="-1" aria-labelledby="suratKknModalLabel" aria-hidden="true" style="z-index: 1085;">
    <div class="modal-dialog modal-fullscreen-lg-down modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem; overflow: hidden; background: #f8fafc;">
            <div class="modal-header bg-dark text-white border-0 py-3 px-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-warning bg-opacity-25 text-warning p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-envelope-paper-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white fs-5" id="suratKknModalLabel">Buat Surat Resmi / Dinas KKN Kelompok 12</h5>
                        <small class="text-white-50" style="font-size: 0.78rem;">Format Surat Edaran / Dinas / Undangan Resmi dengan Logo UMP &amp; KKN 12</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body p-3 p-md-4">
                <!-- Preset Template Options & Saved Letters -->
                <div class="card border-0 shadow-sm p-3 mb-4 rounded-4" style="background: #ffffff;">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <span class="fw-bold text-dark small text-uppercase" style="letter-spacing: 0.05em;"><i class="bi bi-magic me-1 text-primary"></i>Pilih Template Surat Cepat:</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1.5 fw-semibold" onclick="pilihTemplateSurat('edaran')">
                            <i class="bi bi-file-earmark-text me-1"></i> 1. Surat Edaran / Pemberitahuan
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-purple rounded-pill px-3 py-1.5 fw-semibold" onclick="pilihTemplateSurat('undangan')">
                            <i class="bi bi-calendar-event me-1"></i> 2. Surat Undangan Kegiatan
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1.5 fw-semibold" onclick="pilihTemplateSurat('permohonan')">
                            <i class="bi bi-clipboard-check me-1"></i> 3. Surat Permohonan / Izin Tempat
                        </button>
                        <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3 py-1.5 fw-bold shadow-sm ms-auto" onclick="toggleDaftarSuratTersimpan()">
                            <i class="bi bi-folder2-open me-1.5"></i> Lihat Surat yang Sudah Dibuat (<span id="jumlahSuratTersimpanBadge">0</span>)
                        </button>
                    </div>

                    <!-- Panel In-line Arsip Surat Tersimpan -->
                    <div id="panelSuratTersimpan" class="mt-3 p-3 border rounded-3 bg-light d-none">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold text-dark small"><i class="bi bi-journal-bookmark-fill me-1 text-info"></i> Arsip Surat yang Sudah Dibuat</span>
                            <button type="button" class="btn-close small" onclick="toggleDaftarSuratTersimpan()" aria-label="Tutup"></button>
                        </div>
                        <div class="table-responsive bg-white rounded-2 border">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 5%;">No</th>
                                        <th style="width: 25%;">Nomor Surat</th>
                                        <th style="width: 35%;">Hal / Perihal</th>
                                        <th style="width: 20%;">Tanggal Surat</th>
                                        <th style="width: 15%;" class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="daftarSuratTableBody">
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Form Controls (Kiri) -->
                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm p-3.5 rounded-4 h-100" style="background: #ffffff;">
                            <h6 class="fw-bold text-primary mb-3 pb-2 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <span><i class="bi bi-pencil-square me-1"></i> Form Isian Surat</span>
                                <div class="d-flex align-items-center gap-1.5">
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-1" onclick="buatSuratBaru()" title="Reset untuk buat surat baru" style="font-size: 0.78rem;">
                                        <i class="bi bi-plus-circle me-1"></i>Surat Baru
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success text-white rounded-pill px-3 py-1 fw-bold shadow-sm" onclick="simpanSuratSaatIni()" style="font-size: 0.78rem;">
                                        <i class="bi bi-bookmark-check-fill me-1"></i> Simpan Surat
                                    </button>
                                </div>
                            </h6>
                            
                            <form id="suratForm" oninput="updateSuratPreview()">
                                <!-- Tanggal & Nomor -->
                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-dark mb-1">Tempat &amp; Tanggal Surat</label>
                                        <input type="text" class="form-control form-control-sm" id="suratTanggalInput" value="Pontianak, 26 Juli 2026" placeholder="Pontianak, 26 Juli 2026">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-dark mb-1">Nomor Surat</label>
                                        <input type="text" class="form-control form-control-sm" id="suratNomorInput" value="012/KKN-12/UMP/VII/2026" placeholder="Nomor surat...">
                                    </div>
                                </div>

                                <!-- Lampiran & Hal -->
                                <div class="row g-2 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-dark mb-1">Lampiran</label>
                                        <input type="text" class="form-control form-control-sm" id="suratLampiranInput" value="-" placeholder="-">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label small fw-bold text-dark mb-1">Hal / Perihal</label>
                                        <input type="text" class="form-control form-control-sm" id="suratHalInput" value="Pelaksanaan Kegiatan KKN Kelompok 12" placeholder="Perihal surat...">
                                    </div>
                                </div>

                                <!-- Tujuan / Kepada Yth -->
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-dark mb-1">Kepada Yth. (Tujuan Surat)</label>
                                    <textarea class="form-control form-control-sm" id="suratKepadaInput" rows="4" placeholder="Ketik penerima surat...">Yth. 1. Bapak Kepala Kelurahan Pal Lima
2. Ketua RW/RT Kelurahan Pal Lima
3. Tokoh Masyarakat Sub-Kelurahan
di -
Pontianak</textarea>
                                </div>

                                <!-- Paragraf Pembuka -->
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-dark mb-1">Paragraf Pembuka</label>
                                    <textarea class="form-control form-control-sm" id="suratPembukaInput" rows="3" placeholder="Paragraf pembuka...">Memperhatikan pelaksanaan kegiatan Kuliah Kerja Nyata (KKN) Kelompok 12 Universitas Muhammadiyah Pontianak di wilayah Kelurahan Pal Lima, Pontianak Barat, perlu kami sampaikan hal-hal sebagai berikut:</textarea>
                                </div>

                                <!-- Isi Poin-Poin Surat -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label small fw-bold text-dark mb-0">Isi / Poin-Poin Utama Surat</label>
                                        <span class="text-muted" style="font-size: 0.72rem;">Setiap baris = 1 poin nomor</span>
                                    </div>
                                    <textarea class="form-control form-control-sm" id="suratIsiInput" rows="6" placeholder="Ketik poin 1, 2, 3...">1. Masa pelaksanaan kegiatan Kuliah Kerja Nyata (KKN) Kelompok 12 berlangsung mulai tanggal 20 Juli sampai dengan 30 Agustus 2026;
2. Seluruh mahasiswa KKN Kelompok 12 wajib melaksanakan program kerja yang telah disetujui oleh Dosen Pembimbing Lapangan dan pihak Kelurahan;
3. Dalam pelaksanaan program kerja, seluruh anggota hendaknya senantiasa berkoordinasi dengan pengurus RT, RW, dan tokoh masyarakat setempat;
4. Seluruh warga masyarakat diimbau untuk turut serta berpartisipasi aktif dan mendukung kelancaran program kegiatan KKN Kelompok 12.</textarea>
                                </div>

                                <!-- Paragraf Penutup -->
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-dark mb-1">Paragraf Penutup</label>
                                    <textarea class="form-control form-control-sm" id="suratPenutupInput" rows="2" placeholder="Paragraf penutup...">Demikian surat ini kami sampaikan, untuk menjadi pedoman dan dilaksanakan dengan penuh tanggung jawab.</textarea>
                                </div>

                                <!-- Opsi Lampiran Tabel Daftar Peserta KKN -->
                                <div class="card p-3 border bg-light mb-3 rounded-3 shadow-sm">
                                    <div class="form-check form-switch mb-1">
                                        <input class="form-check-input" type="checkbox" id="tampilkanTabelAnggotaCheck" onchange="toggleTampilkanTabelAnggota()">
                                        <label class="form-check-label fw-bold text-dark small" for="tampilkanTabelAnggotaCheck">
                                            <i class="bi bi-table me-1 text-primary"></i> Lampirkan Tabel Daftar Peserta KKN
                                        </label>
                                    </div>

                                    <div id="wrapperInputTabelAnggota" class="mt-2 d-none">
                                        <div class="mb-2">
                                            <label class="form-label text-muted mb-0" style="font-size: 0.74rem;">Judul Tabel / Lampiran</label>
                                            <input type="text" class="form-control form-control-sm" id="tabelJudulInput" value="DAFTAR PESERTA KKN 2026 KELOMPOK 12" oninput="renderPreviewTabelAnggota()">
                                        </div>
                                        <div class="row g-2 mb-2">
                                            <div class="col-12">
                                                <label class="form-label text-muted mb-0" style="font-size: 0.74rem;">Dosen Pembimbing Lapangan (DPL)</label>
                                                <input type="text" class="form-control form-control-sm" id="tabelDplInput" value="Kania Khairunnisa, M.Psi., Psikolog" oninput="renderPreviewTabelAnggota()">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label text-muted mb-0" style="font-size: 0.74rem;">Lokasi KKN / Desa / Kelurahan</label>
                                                <input type="text" class="form-control form-control-sm" id="tabelLokasiInput" value="Desa Arang Limbung, Kec. Sungai Raya, Kubu Raya" oninput="renderPreviewTabelAnggota()">
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="fw-bold text-dark" style="font-size: 0.76rem;">Daftar Anggota Kelompok</span>
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" onclick="resetContohAnggotaTabel()" style="font-size: 0.7rem;" title="Isi ulang data contoh sesuai gambar">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Reset Contoh
                                                </button>
                                                <button type="button" class="btn btn-xs btn-primary py-0 px-2 fw-bold" onclick="tambahBarisAnggotaTabel()" style="font-size: 0.7rem;">
                                                    <i class="bi bi-plus-lg"></i> + Tambah
                                                </button>
                                            </div>
                                        </div>

                                        <div class="table-responsive border rounded-2 bg-white mb-1" style="max-height: 240px; overflow-y: auto;">
                                            <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 0.75rem;">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width: 20px;">No</th>
                                                        <th>Nama</th>
                                                        <th style="width: 90px;">NIM</th>
                                                        <th style="width: 110px;">Prodi</th>
                                                        <th style="width: 80px;">Jabatan</th>
                                                        <th style="width: 20px;"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbodyInputAnggotaSurat">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Penandatangan Kanan (Ketua) -->
                                <div class="card p-2.5 border bg-light mb-3 rounded-3">
                                    <span class="fw-bold small text-dark mb-2 d-block"><i class="bi bi-pen me-1 text-primary"></i>Penandatangan Utama (Ketua)</span>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input type="text" class="form-control form-control-sm mb-1.5" id="suratJabatanKananInput" value="Ketua KKN Kelompok 12" placeholder="Jabatan">
                                        </div>
                                        <div class="col-6">
                                            <input type="text" class="form-control form-control-sm mb-1.5" id="suratNamaKananInput" value="Rizki Tri Saputra" placeholder="Nama Lengkap">
                                        </div>
                                        <div class="col-12">
                                            <input type="text" class="form-control form-control-sm" id="suratNimKananInput" value="NIM. ....................................." placeholder="NIM / ID">
                                        </div>
                                    </div>
                                </div>

                                <!-- Penandatangan Kiri (DPL / Mengetahui) -->
                                <div class="card p-2.5 border bg-light mb-3 rounded-3">
                                    <span class="fw-bold small text-dark mb-2 d-block"><i class="bi bi-pen me-1 text-primary"></i>Penandatangan Pendamping (Mengetahui)</span>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input type="text" class="form-control form-control-sm mb-1.5" id="suratJabatanKiriInput" value="Kepala Kelurahan Pal Lima" placeholder="Jabatan">
                                        </div>
                                        <div class="col-6">
                                            <input type="text" class="form-control form-control-sm mb-1.5" id="suratNamaKiriInput" value="....................................." placeholder="Nama Lengkap">
                                        </div>
                                        <div class="col-12">
                                            <input type="text" class="form-control form-control-sm" id="suratNidnKiriInput" value="NIP. ....................................." placeholder="NIP / Identitas">
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Document Live Preview (Kanan) -->
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm p-3 rounded-4 h-100" style="background: #e2e8f0;">
                            <style>
                                .surat-preview-paper [contenteditable="true"] {
                                    transition: background-color 0.15s ease, outline 0.15s ease, box-shadow 0.15s ease;
                                    border-radius: 3px;
                                    padding: 1px 2px;
                                }
                                .surat-preview-paper [contenteditable="true"]:hover {
                                    outline: 1.5px dashed #2563eb !important;
                                    background-color: rgba(37, 99, 235, 0.05) !important;
                                    cursor: text;
                                }
                                .surat-preview-paper [contenteditable="true"]:focus {
                                    outline: 2px solid #1d4ed8 !important;
                                    background-color: rgba(29, 78, 216, 0.08) !important;
                                    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
                                }
                            </style>

                            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold text-dark small"><i class="bi bi-eye me-1 text-primary"></i> Pratinjau Dokumen Surat (A4)</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-0.5" id="btnToggleEditKertas" onclick="toggleEditKertasMode()" style="font-size: 0.76rem;" title="Klik untuk mengaktifkan / menonaktifkan mode edit tulisan langsung di lembar kertas">
                                        <i class="bi bi-pencil-fill me-1"></i> Edit di Kertas: <span id="statusEditKertasBadge" class="badge bg-success ms-1">AKTIF</span>
                                    </button>
                                </div>
                                <div class="d-flex align-items-center gap-1.5">
                                    <button type="button" class="btn btn-sm btn-primary fw-bold rounded-pill px-2.5 py-1 shadow-sm" onclick="unduhSuratWord()" style="font-size: 0.78rem;" title="Unduh dokumen terformat Word yang 100% dapat diedit">
                                        <i class="bi bi-file-earmark-word-fill me-1"></i> Unduh Word (.docx)
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger fw-bold rounded-pill px-2.5 py-1 shadow-sm" onclick="unduhSuratPDF()" style="font-size: 0.78rem;" title="Unduh berkas PDF siap cetak">
                                        <i class="bi bi-file-earmark-pdf-fill me-1"></i> Unduh PDF
                                    </button>
                                </div>
                            </div>

                            <!-- Live Paper Preview (Exact A4 Ratio) -->
                            <div class="surat-preview-paper bg-white border shadow-sm rounded-2 mx-auto" id="suratPreviewContainer" data-editable="true" style="color: #000; font-family: 'Times New Roman', Times, serif; width: 100%; max-width: 210mm; min-height: 297mm; box-sizing: border-box; padding: 20mm 20mm 20mm 20mm; font-size: 11pt; line-height: 1.5; background: #ffffff;">
                                <!-- Kop Surat Header dengan Logo UMP & KKN 12 -->
                                <div style="border-bottom: 2.5px solid #000; padding-bottom: 1.5px; margin-bottom: 20px;">
                                    <div style="border-bottom: 1px solid #000; padding-bottom: 8px;">
                                        <table style="width: 100%; border-collapse: collapse; border: none !important; margin: 0; padding: 0;">
                                            <tr>
                                                <td style="width: 85px; vertical-align: middle; text-align: left; border: none !important; padding: 0 !important;">
                                                    <img src="<?= $logoUmpSrc ?>" alt="Logo UMP" style="height: 78px; width: auto; display: block; object-fit: contain;">
                                                </td>
                                                <td style="text-align: center; vertical-align: middle; border: none !important; padding: 0 10px !important;">
                                                    <h2 id="prevKopHeader1" contenteditable="true" style="font-size: 11pt; font-weight: bold; text-transform: uppercase; margin: 0 0 2px; font-family: 'Times New Roman', Times, serif; line-height: 1.2; color: #000; letter-spacing: 0.5px;">PIMPINAN PUSAT MUHAMMADIYAH</h2>
                                                    <h2 id="prevKopHeader2" contenteditable="true" style="font-size: 14pt; font-weight: bold; text-transform: uppercase; margin: 0 0 2px; font-family: 'Times New Roman', Times, serif; line-height: 1.2; color: #002B66; letter-spacing: 0.5px;">UNIVERSITAS MUHAMMADIYAH PONTIANAK</h2>
                                                    <h3 id="prevKopHeader3" contenteditable="true" style="font-size: 12pt; font-weight: bold; text-transform: uppercase; margin: 0 0 3px; font-family: 'Times New Roman', Times, serif; line-height: 1.2; color: #000;">PANITIA KULIAH KERJA NYATA (KKN) KELOMPOK 12</h3>
                                                    <p id="prevKopSub" contenteditable="true" style="font-size: 9.5pt; font-style: italic; margin: 0 0 1px; font-family: 'Times New Roman', Times, serif; line-height: 1.2; color: #000;">Kelurahan Pal Lima &middot; Kecamatan Pontianak Barat &middot; Kota Pontianak</p>
                                                    <p id="prevKopAlamat" contenteditable="true" style="font-size: 8.5pt; margin: 0; font-family: 'Times New Roman', Times, serif; line-height: 1.2; color: #444;">Alamat Posko: Kel. Pal Lima, Kec. Pontianak Barat, Kota Pontianak, Kode Pos 78115</p>
                                                </td>
                                                <td style="width: 85px; vertical-align: middle; text-align: right; border: none !important; padding: 0 !important;">
                                                    <img src="<?= $logoKknSrc ?>" alt="Logo KKN 12" style="height: 74px; width: auto; display: block; margin-left: auto; object-fit: contain;">
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                <!-- Tanggal Surat (Right Aligned) -->
                                <div style="text-align: right; font-size: 11pt; margin-bottom: 18px;" id="prevSuratTanggal" contenteditable="true">
                                    Pontianak, 26 Juli 2026
                                </div>

                                <!-- Table Details & Kepada Yth -->
                                <table style="width: 100%; border-collapse: collapse; border: none !important; margin-bottom: 22px; font-size: 11pt;">
                                    <tr>
                                        <td style="width: 48%; vertical-align: top; border: none !important; padding: 0;">
                                            <table style="width: 100%; border-collapse: collapse; border: none !important;">
                                                <tr>
                                                    <td style="width: 80px; border: none !important; padding: 2px 0; vertical-align: top;">Nomor</td>
                                                    <td style="width: 15px; border: none !important; padding: 2px 0; vertical-align: top;">:</td>
                                                    <td style="border: none !important; padding: 2px 0; font-weight: bold; vertical-align: top;" id="prevSuratNomor" contenteditable="true">012/KKN-12/UMP/VII/2026</td>
                                                </tr>
                                                <tr>
                                                    <td style="border: none !important; padding: 2px 0; vertical-align: top;">Lampiran</td>
                                                    <td style="border: none !important; padding: 2px 0; vertical-align: top;">:</td>
                                                    <td style="border: none !important; padding: 2px 0; vertical-align: top;" id="prevSuratLampiran" contenteditable="true">-</td>
                                                </tr>
                                                <tr>
                                                    <td style="border: none !important; padding: 2px 0; vertical-align: top;">Hal</td>
                                                    <td style="border: none !important; padding: 2px 0; vertical-align: top;">:</td>
                                                    <td style="border: none !important; padding: 2px 0; font-weight: bold; vertical-align: top;" id="prevSuratHal" contenteditable="true">Pelaksanaan Kegiatan KKN Kelompok 12</td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td style="width: 52%; vertical-align: top; border: none !important; padding: 0 0 0 20px;">
                                            <div style="font-weight: bold; margin-bottom: 4px;">Kepada</div>
                                            <div id="prevSuratKepada" contenteditable="true" style="line-height: 1.45;">
                                                Yth. 1. Bapak Kepala Kelurahan Pal Lima<br>
                                                2. Ketua RW/RT Kelurahan Pal Lima<br>
                                                3. Tokoh Masyarakat Sub-Kelurahan<br>
                                                di -<br>
                                                <span style="text-decoration: underline; margin-left: 20px;">Pontianak</span>
                                            </div>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Paragraf Pembuka -->
                                <p style="text-align: justify; text-indent: 32px; font-size: 11pt; line-height: 1.6; margin-bottom: 15px;" id="prevSuratPembuka" contenteditable="true">
                                    Memperhatikan pelaksanaan kegiatan Kuliah Kerja Nyata (KKN) Kelompok 12 Universitas Muhammadiyah Pontianak di wilayah Kelurahan Pal Lima, Pontianak Barat, perlu kami sampaikan hal-hal sebagai berikut:
                                </p>

                                <!-- Isi Poin-Poin Surat / Tabel Undangan -->
                                <div style="font-size: 11pt; line-height: 1.6; text-align: justify; margin-bottom: 20px;" id="prevSuratIsiPoin" contenteditable="true">
                                    <!-- Diisi otomatis oleh updateSuratPreview() -->
                                </div>

                                <!-- Paragraf Penutup -->
                                <p style="text-align: justify; text-indent: 32px; font-size: 11pt; line-height: 1.6; margin-bottom: 30px;" id="prevSuratPenutup" contenteditable="true">
                                    Demikian surat ini kami sampaikan, untuk menjadi pedoman dan dilaksanakan dengan penuh tanggung jawab.
                                </p>

                                <!-- Tabel Lampiran Daftar Peserta KKN -->
                                <div id="prevSuratContainerTabelAnggota" style="margin-top: 30px; margin-bottom: 35px; display: none; page-break-inside: avoid; break-inside: avoid;">
                                    <h4 id="prevTabelJudul" contenteditable="true" style="font-size: 11pt; font-weight: bold; text-align: center; text-transform: uppercase; margin: 0 0 14px; font-family: 'Times New Roman', Times, serif; color: #000; letter-spacing: 0.5px;">
                                        DAFTAR PESERTA KKN 2026 KELOMPOK 12
                                    </h4>

                                    <table style="width: 100%; border-collapse: collapse; border: none !important; margin-bottom: 12px; font-size: 11pt; line-height: 1.5;">
                                        <tr>
                                            <td style="width: 220px; vertical-align: top; padding: 2px 0; border: none !important;">Dosen Pembimbing Lapangan</td>
                                            <td style="width: 15px; vertical-align: top; padding: 2px 0; border: none !important;">:</td>
                                            <td style="vertical-align: top; padding: 2px 0; font-weight: bold; border: none !important;" id="prevTabelDpl" contenteditable="true">
                                                Kania Khairunnisa, M.Psi., Psikolog
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="vertical-align: top; padding: 2px 0; border: none !important;">Lokasi</td>
                                            <td style="vertical-align: top; padding: 2px 0; border: none !important;">:</td>
                                            <td style="vertical-align: top; padding: 2px 0; border: none !important;" id="prevTabelLokasi" contenteditable="true">
                                                Desa Arang Limbung, Kec. Sungai Raya, Kubu Raya
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Grid Tabel Anggota -->
                                    <table style="width: 100%; border-collapse: collapse; border: 1.5px solid #000; font-size: 10.5pt;" id="prevTabelAnggotaGrid">
                                        <thead>
                                            <tr style="background-color: #ffffff; text-align: left;">
                                                <th style="border: 1px solid #000; padding: 6px 6px; text-align: center; width: 38px; font-weight: bold;">No.</th>
                                                <th style="border: 1px solid #000; padding: 6px 10px; font-weight: bold;">Nama</th>
                                                <th style="border: 1px solid #000; padding: 6px 10px; width: 115px; font-weight: bold;">NIM</th>
                                                <th style="border: 1px solid #000; padding: 6px 10px; font-weight: bold;">Program Studi</th>
                                                <th style="border: 1px solid #000; padding: 6px 10px; width: 100px; font-weight: bold; text-align: center;">Jabatan</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyPrevAnggotaGrid">
                                            <!-- Generated via JS -->
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Signature Block -->
                                <div style="font-size: 11pt; line-height: 1.4; page-break-inside: avoid; break-inside: avoid; margin-top: 30px;">
                                    <table style="width: 100%; border-collapse: collapse; border: none !important; text-align: center;">
                                        <tr>
                                            <td style="width: 48%; vertical-align: top; border: none !important;">
                                                <p style="margin: 0 0 5px;">Mengetahui,</p>
                                                <p style="margin: 0 0 60px; font-weight: bold;" id="prevSuratJabatanKiri" contenteditable="true">Kepala Kelurahan Pal Lima</p>
                                                <p style="margin: 0; font-weight: bold; text-decoration: underline;" id="prevSuratNamaKiri" contenteditable="true">( ..................................... )</p>
                                                <p style="margin: 2px 0 0; font-size: 9.5pt; color: #222;" id="prevSuratNidnKiri" contenteditable="true">NIP. .....................................</p>
                                            </td>
                                            <td style="width: 4%; border: none !important;"></td>
                                            <td style="width: 48%; vertical-align: top; border: none !important;">
                                                <p style="margin: 0 0 5px;">&nbsp;</p>
                                                <p style="margin: 0 0 60px; font-weight: bold;" id="prevSuratJabatanKanan" contenteditable="true">Ketua KKN Kelompok 12</p>
                                                <p style="margin: 0; font-weight: bold; text-decoration: underline;" id="prevSuratNamaKanan" contenteditable="true">( Rizki Tri Saputra )</p>
                                                <p style="margin: 2px 0 0; font-size: 9.5pt; color: #222;" id="prevSuratNimKanan" contenteditable="true">NIM. .....................................</p>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light border-0 py-3 px-4">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-outline-dark fw-bold rounded-pill px-4 shadow-sm" onclick="cetakSuratLangsung()">
                    <i class="bi bi-printer-fill me-1.5"></i> Cetak / Print
                </button>
                <button type="button" class="btn btn-primary fw-bold rounded-pill px-4 shadow-sm" onclick="unduhSuratWord()">
                    <i class="bi bi-file-earmark-word-fill me-1.5"></i> Unduh Word (.docx)
                </button>
                <button type="button" class="btn btn-danger fw-bold rounded-pill px-4 shadow-sm" onclick="unduhSuratPDF()">
                    <i class="bi bi-file-earmark-pdf-fill me-1.5"></i> Unduh Surat (PDF)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Notulensi Rapat & Administrasi Kesekretariatan -->
<div class="modal fade" id="notulensiSekretarisModal" tabindex="-1" aria-labelledby="notulensiSekretarisModalLabel" aria-hidden="true" style="z-index: 1085;">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem; overflow: hidden; background: #ffffff;">
            <div class="modal-header bg-dark text-white border-0 py-3 px-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-info bg-opacity-25 text-info p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-journal-check fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white fs-5" id="notulensiSekretarisModalLabel">Administrasi Kesekretariatan KKN Kelompok 12</h5>
                        <small class="text-white-50" style="font-size: 0.78rem;">Catatan Notulensi Rapat Internal Kelompok &amp; Registrasi Agenda Surat Masuk</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body p-3 p-md-4">
                <!-- Nav Tabs Kesekretariatan -->
                <ul class="nav nav-pills mb-3 border-bottom pb-2 gap-2" id="sekretarisTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold rounded-pill px-4" id="tab-notulensi-tab" data-bs-toggle="tab" data-bs-target="#tab-notulensi" type="button" role="tab">
                            <i class="bi bi-journal-text me-1.5"></i> 1. Notulensi Rapat Kelompok
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold rounded-pill px-4" id="tab-surat-masuk-tab" data-bs-toggle="tab" data-bs-target="#tab-surat-masuk" type="button" role="tab">
                            <i class="bi bi-inbox-fill me-1.5"></i> 2. Agenda Surat Masuk
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="sekretarisTabsContent">
                    <!-- TAB 1: NOTULENSI RAPAT -->
                    <div class="tab-pane fade show active" id="tab-notulensi" role="tabpanel">
                        <div class="row g-4">
                            <!-- Form Input Notulensi (Kiri) -->
                            <div class="col-lg-5">
                                <div class="card border-0 shadow-sm p-3.5 rounded-4 bg-light">
                                    <h6 class="fw-bold text-primary mb-3 pb-2 border-bottom d-flex align-items-center justify-content-between">
                                        <span><i class="bi bi-pencil-square me-1"></i> Form Notulensi Rapat</span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-0.5" onclick="resetFormNotulensi()" style="font-size:0.75rem;">Reset</button>
                                    </h6>
                                    <form id="notulensiForm" onsubmit="simpanNotulensi(event)">
                                        <input type="hidden" id="notulensiId" value="0">
                                        <div class="mb-2.5">
                                            <label class="form-label small fw-bold text-dark mb-1">Tanggal Rapat</label>
                                            <input type="date" class="form-control form-control-sm" id="notulensiTanggal" required>
                                        </div>
                                        <div class="mb-2.5">
                                            <label class="form-label small fw-bold text-dark mb-1">Judul / Agenda Rapat</label>
                                            <input type="text" class="form-control form-control-sm" id="notulensiJudul" placeholder="Misal: Rapat Pembahasan Program Kerja Mingguan" required>
                                        </div>
                                        <div class="mb-2.5">
                                            <label class="form-label small fw-bold text-dark mb-1">Peserta / Anggota Hadir</label>
                                            <input type="text" class="form-control form-control-sm" id="notulensiPeserta" placeholder="Seluruh Anggota Kelompok 12 &amp; DPL">
                                        </div>
                                        <div class="mb-2.5">
                                            <label class="form-label small fw-bold text-dark mb-1">Poin Pembahasan Utama</label>
                                            <textarea class="form-control form-control-sm" id="notulensiPembahasan" rows="4" placeholder="Ketik pokok bahasan rapat..." required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-dark mb-1">Hasil / Keputusan Rapat</label>
                                            <textarea class="form-control form-control-sm" id="notulensiKeputusan" rows="3" placeholder="Ketik keputusan/kesepakatan rapat..."></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold rounded-pill shadow-sm" id="notulensiSubmitBtn">
                                            <i class="bi bi-save me-1"></i> Simpan Notulensi Rapat
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Daftar Notulensi (Kanan) -->
                            <div class="col-lg-7">
                                <div class="card border-0 shadow-sm p-3 rounded-4 bg-white h-100">
                                    <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom d-flex align-items-center justify-content-between">
                                        <span><i class="bi bi-journal-text me-1 text-info"></i> Daftar Notulensi Rapat</span>
                                        <span class="badge bg-primary text-white rounded-pill" id="badgeTotalNotulensi">0 Notulensi</span>
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0" style="font-size:0.85rem;">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th style="width:15%;">Tanggal</th>
                                                    <th style="width:35%;">Judul Rapat</th>
                                                    <th style="width:35%;">Hasil / Keputusan</th>
                                                    <th style="width:15%;" class="text-end">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody id="notulensiTableBody">
                                                <!-- Populated by JS -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: SURAT MASUK -->
                    <div class="tab-pane fade" id="tab-surat-masuk" role="tabpanel">
                        <div class="row g-4">
                            <!-- Form Input Surat Masuk (Kiri) -->
                            <div class="col-lg-5">
                                <div class="card border-0 shadow-sm p-3.5 rounded-4 bg-light">
                                    <h6 class="fw-bold text-primary mb-3 pb-2 border-bottom d-flex align-items-center justify-content-between">
                                        <span><i class="bi bi-inbox-fill me-1"></i> Form Catat Surat Masuk</span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-0.5" onclick="resetFormSuratMasuk()" style="font-size:0.75rem;">Reset</button>
                                    </h6>
                                    <form id="suratMasukForm" onsubmit="simpanSuratMasuk(event)">
                                        <input type="hidden" id="suratMasukId" value="0">
                                        <div class="mb-2.5">
                                            <label class="form-label small fw-bold text-dark mb-1">Tanggal Diterima</label>
                                            <input type="date" class="form-control form-control-sm" id="suratMasukTanggal" required>
                                        </div>
                                        <div class="mb-2.5">
                                            <label class="form-label small fw-bold text-dark mb-1">Nomor Surat Masuk</label>
                                            <input type="text" class="form-control form-control-sm" id="suratMasukNomor" placeholder="Misal: 045/KEL-PAL-LIMA/VII/2026" required>
                                        </div>
                                        <div class="mb-2.5">
                                            <label class="form-label small fw-bold text-dark mb-1">Instansi / Pengirim</label>
                                            <input type="text" class="form-control form-control-sm" id="suratMasukPengirim" placeholder="Misal: Kantot Kelurahan Pal Lima" required>
                                        </div>
                                        <div class="mb-2.5">
                                            <label class="form-label small fw-bold text-dark mb-1">Perihal / Isi Surat</label>
                                            <input type="text" class="form-control form-control-sm" id="suratMasukPerihal" placeholder="Misal: Balasi Izin Penggunaan Balai Warga" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-dark mb-1">Keterangan / Tindak Lanjut</label>
                                            <textarea class="form-control form-control-sm" id="suratMasukKeterangan" rows="3" placeholder="Ketik catatan tindak lanjut..."></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold rounded-pill shadow-sm" id="suratMasukSubmitBtn">
                                            <i class="bi bi-save me-1"></i> Simpan Agenda Surat Masuk
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Daftar Surat Masuk (Kanan) -->
                            <div class="col-lg-7">
                                <div class="card border-0 shadow-sm p-3 rounded-4 bg-white h-100">
                                    <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom d-flex align-items-center justify-content-between">
                                        <span><i class="bi bi-journal-bookmark-fill me-1 text-info"></i> Agenda Surat Masuk</span>
                                        <span class="badge bg-primary text-white rounded-pill" id="badgeTotalSuratMasuk">0 Surat</span>
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0" style="font-size:0.85rem;">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th style="width:15%;">Tgl Terima</th>
                                                    <th style="width:25%;">Nomor Surat</th>
                                                    <th style="width:25%;">Pengirim</th>
                                                    <th style="width:20%;">Perihal</th>
                                                    <th style="width:15%;" class="text-end">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody id="suratMasukTableBody">
                                                <!-- Populated by JS -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light border-0 py-2.5 px-4">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Direktori Kontak Humas & Stakeholder -->
<div class="modal fade" id="kontakHumasModal" tabindex="-1" aria-labelledby="kontakHumasModalLabel" aria-hidden="true" style="z-index: 1086;">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem; overflow: hidden; background: #ffffff;">
            <div class="modal-header bg-dark text-white border-0 py-3 px-4" style="background: linear-gradient(135deg, #065f46 0%, #047857 100%) !important;">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-success bg-opacity-25 text-white p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-telephone-inbound fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white fs-5" id="kontakHumasModalLabel">Direktori Kontak Humas &amp; Stakeholder Kelurahan Pal Lima</h5>
                        <small class="text-white-50" style="font-size: 0.78rem;">Kontak Penting Perangkat Kelurahan, Babinsa, Bhabinkamtibmas, RW/RT &amp; DPL</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body p-3 p-md-4">
                <div class="row g-4">
                    <!-- Form Input Kontak (Kiri) -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm p-3.5 rounded-4 bg-light">
                            <h6 class="fw-bold text-success mb-3 pb-2 border-bottom d-flex align-items-center justify-content-between">
                                <span><i class="bi bi-person-plus-fill me-1"></i> Form Kontak Stakeholder</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-0.5" onclick="resetFormKontakHumas()" style="font-size:0.75rem;">Reset</button>
                            </h6>
                            <form id="kontakHumasForm" onsubmit="simpanKontakHumas(event)">
                                <input type="hidden" id="kontakHumasId" value="0">
                                <div class="mb-2.5">
                                    <label class="form-label small fw-bold text-dark mb-1">Nama Tokoh / Pejabat</label>
                                    <input type="text" class="form-control form-control-sm" id="kontakHumasNama" placeholder="Misal: Bapak Lurah Pal Lima" required>
                                </div>
                                <div class="mb-2.5">
                                    <label class="form-label small fw-bold text-dark mb-1">Jabatan / Instansi</label>
                                    <input type="text" class="form-control form-control-sm" id="kontakHumasJabatan" placeholder="Misal: Kepala Kelurahan / Bhabinkamtibmas" required>
                                </div>
                                <div class="mb-2.5">
                                    <label class="form-label small fw-bold text-dark mb-1">Nomor HP / WhatsApp</label>
                                    <input type="text" class="form-control form-control-sm" id="kontakHumasNoHp" placeholder="Misal: 081234567890" required>
                                </div>
                                <div class="mb-2.5">
                                    <label class="form-label small fw-bold text-dark mb-1">Wilayah / Lokasi Tugas</label>
                                    <input type="text" class="form-control form-control-sm" id="kontakHumasWilayah" placeholder="Misal: Kelurahan Pal Lima / RW 01">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-dark mb-1">Keterangan Catatan</label>
                                    <textarea class="form-control form-control-sm" id="kontakHumasKeterangan" rows="2" placeholder="Catatan tambahan..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-success btn-sm w-100 fw-bold rounded-pill shadow-sm" id="kontakHumasSubmitBtn">
                                    <i class="bi bi-save me-1"></i> Simpan Kontak Humas
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Tabel Daftar Kontak (Kanan) -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm p-3 rounded-4 bg-white h-100">
                            <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom d-flex align-items-center justify-content-between">
                                <span><i class="bi bi-telephone-fill me-1 text-success"></i> Buku Telepon Kontak Penting</span>
                                <span class="badge bg-success text-white rounded-pill" id="badgeTotalKontakHumas">0 Kontak</span>
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="font-size:0.85rem;">
                                    <thead class="bg-light">
                                        <tr>
                                            <th style="width:25%;">Nama Tokoh</th>
                                            <th style="width:25%;">Jabatan &amp; Wilayah</th>
                                            <th style="width:25%;">Nomor WhatsApp</th>
                                            <th style="width:25%;" class="text-end">Aksi Hubungi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="kontakHumasTableBody">
                                        <!-- Populated by JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light border-0 py-2.5 px-4">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Lightbox -->
<div class="lightbox" id="lightbox">
    <button class="lightbox-close" id="lightboxClose" aria-label="Tutup">&times;</button>
    <img id="lightboxImg" src="" alt="">
    <div class="lightbox-caption" id="lightboxCaption"></div>
</div>

<div class="upload-toast" id="uploadToast"><i class="bi bi-check-circle-fill"></i> Foto berhasil diunggah</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script>
let entries = <?php echo json_encode($entriesForJs, JSON_UNESCAPED_UNICODE); ?>;
const prokjaPhotos = <?php echo json_encode($prokjaPhotosRows, JSON_UNESCAPED_UNICODE); ?>;
const jadwalAcaraEntries = <?php echo json_encode($jadwalAcaraForJs, JSON_UNESCAPED_UNICODE); ?>;
const jadwalPiketEntries = <?php echo json_encode($jadwalPiketRows, JSON_UNESCAPED_UNICODE); ?>;
const infoPoskoEntries = <?php echo json_encode($infoPoskoRows, JSON_UNESCAPED_UNICODE); ?>;
const anggotaList = <?php echo json_encode($anggotaRows, JSON_UNESCAPED_UNICODE); ?>;
const logoUmpSrc = <?php echo json_encode($logoUmpSrc); ?>;
const logoKknSrc = <?php echo json_encode($logoKknSrc); ?>;
const isViewer = <?php echo json_encode($isViewer); ?>;
const isDplUser = <?php echo json_encode($isDplUser); ?>;
let isQuoteEmpty = <?php echo json_encode($isQuoteEmpty); ?>;
const dplAnggotaId = <?php echo json_encode((int)($dplRow['id'] ?? 0)); ?>;

function formatPhotoUrl(url) {
  if (!url) return 'img/default-placeholder.jpg';
  url = String(url).trim();
  const driveMatch = url.match(/(?:drive\.google\.com\/(?:file\/d\/|open\?id=|uc\?id=)|lh3\.googleusercontent\.com\/d\/)([a-zA-Z0-9_-]+)/i);
  if (driveMatch && driveMatch[1]) {
    return 'https://lh3.googleusercontent.com/d/' + driveMatch[1];
  }
  return url;
}

function bukaModalGDriveFoto(kegiatanId, title) {
  document.getElementById('gdriveKegiatanId').value = kegiatanId;
  document.getElementById('gdriveUrlInput').value = '';
  const subEl = document.getElementById('gdriveModalSub');
  if (subEl) subEl.textContent = 'Masukkan tautan/link foto publik dari Google Drive untuk kegiatan "' + title + '".';
  const modal = new bootstrap.Modal(document.getElementById('gdriveFotoModal'));
  modal.show();
}

function getFilteredEntries(){
  const activeChip = document.querySelector('.chip.active');
  const filter = activeChip ? activeChip.dataset.filter : "semua";
  const query = (document.getElementById('searchInput')?.value || "").trim().toLowerCase();

  return entries.filter(e => {
    const matchFilter = filter === "semua" || e.divisi === filter;
    if(!matchFilter) return false;
    if(!query) return true;

    const words = query.split(/\s+/).filter(Boolean);
    const rundownText = (e.rundown || []).map(r => `${r.agenda} ${r.pic} ${r.keterangan}`).join(" ");
    const haystack = [
      e.title, e.desc, e.divisiLabel, e.lokasi, e.sasaran, e.hasil, e.hari, e.tgl, e.bulan, e.tanggal, rundownText
    ].join(" ").toLowerCase();

    return words.every(w => haystack.includes(w));
  });
}

function update(keepOpenId){
  renderTimeline(getFilteredEntries(), keepOpenId);
}

function renderTimeline(list, keepOpenId){
  const tl = document.getElementById('timeline');
  tl.innerHTML = "";

  // Populate print table
  const pBody = document.getElementById('printReportTableBody');
  if (pBody) {
    pBody.innerHTML = "";
    list.forEach((e, index) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td style="text-align:center; font-weight:bold; font-size:9pt; color:#000;">${index + 1}</td>
        <td style="font-size:9pt; color:#000;">
          <strong>${e.hari}</strong><br>
          ${e.tgl} ${e.bulan} 2026<br>
          Pukul ${e.waktu}
        </td>
        <td style="font-size:9pt; color:#000;">
          <strong>${e.title}</strong><br>
          <span style="font-size:8pt;">(${e.divisiLabel})</span>
        </td>
        <td style="font-size:9pt; color:#000; line-height:1.5;">
          <p style="margin:0 0 6px; text-align:justify;">${e.desc}</p>
          <p style="margin:6px 0 0; font-size:8.5pt;"><strong>Hasil / Output:</strong></p>
          <p style="margin:2px 0 0; font-style:italic; text-align:justify;">${e.hasil}</p>
        </td>
        <td style="font-size:9pt; color:#000;">
          <p style="margin:0 0 2px;"><strong>Lokasi:</strong></p>
          <p style="margin:0 0 6px;">${e.lokasi}</p>
          <p style="margin:0 0 2px;"><strong>Sasaran:</strong></p>
          <p style="margin:0;">${e.sasaran}</p>
        </td>
      `;
      pBody.appendChild(tr);
    });
  }

  if(list.length === 0){
    tl.innerHTML = `<p style="color:var(--ink-soft);font-size:.9rem;padding:20px 0;">${entries.length === 0 ? 'Belum ada kegiatan tercatat. Klik "Catat Kegiatan" untuk menambahkan.' : 'Tidak ada kegiatan yang cocok dengan pencarian/filter ini.'}</p>`;
    return;
  }

  list.forEach((e) => {
    const el = document.createElement('div');
    const hasPhotos = (e.foto && e.foto.length > 0);
    const isOpen = (e.id === keepOpenId) || (keepOpenId === undefined && hasPhotos);
    el.className = "entry" + (isOpen ? " open" : "");
    el.innerHTML = `
      <div class="entry-dot"></div>
      <div class="card">
        <div class="card-head" onclick="this.closest('.entry').classList.toggle('open')">
          <div class="date-tag">${e.bulan}<span class="dnum">${e.tgl}</span></div>
          <div class="card-title-wrap">
            <p class="card-title">${e.title}</p>
            <div class="card-meta align-items-center gap-2">
              <span class="div-badge">${e.divisiLabel}</span>
              <span>${e.hari}, ${e.waktu}</span>
              <span id="badge-photo-wrap-${e.id}">
                ${hasPhotos ? `<span class="badge bg-primary text-white rounded-pill px-2.5 py-1 small badge-camera" style="font-size: 0.75rem;"><i class="bi bi-camera-fill me-1"></i>${e.foto.length} Foto</span>` : ''}
              </span>
            </div>
          </div>
          <svg class="chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg>
        </div>
        <div class="card-body">
          <div class="card-body-inner">
            <p class="desc">${e.desc}</p>
            <div class="kv-row">
              <div class="kv"><div class="kv-label">Lokasi</div><div class="kv-val">${e.lokasi}</div></div>
              <div class="kv"><div class="kv-label">Sasaran</div><div class="kv-val">${e.sasaran}</div></div>
            </div>
            <div class="kv-row" style="grid-template-columns:1fr;margin-bottom:22px;">
              <div class="kv"><div class="kv-label">Hasil / Output</div><div class="kv-val">${e.hasil}</div></div>
            </div>

            ${e.rundown && e.rundown.length ? `
              <p class="section-label">Rundown Acara</p>
              <div class="table-responsive mt-2 mb-3">
                <table class="table table-sm table-bordered" style="font-size: 0.85rem; border-color: rgba(0,0,0,0.06); font-family: inherit;">
                  <thead class="bg-light">
                    <tr>
                      <th style="width: 20%; font-weight: 700;">Waktu</th>
                      <th style="width: 35%; font-weight: 700;">Agenda / Acara</th>
                      <th style="width: 20%; font-weight: 700;">PIC</th>
                      <th style="width: 25%; font-weight: 700;">Catatan</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${e.rundown.map(r => `
                      <tr>
                        <td><strong>${r.waktu}</strong></td>
                        <td>${r.agenda}</td>
                        <td>${r.pic || '-'}</td>
                        <td class="text-muted">${r.keterangan || '-'}</td>
                      </tr>
                    `).join('')}
                  </tbody>
                </table>
              </div>
            ` : ''}

            <!-- Section Foto Dokumentasi + Tombol Upload Langsung & Google Drive -->
            <div class="t-photos-section mt-3 mb-2 p-3 bg-light bg-opacity-50 rounded-3 border border-light">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <span class="section-label mb-0" style="font-size: 0.88rem; font-weight: 700; color: var(--ink);">
                  <i class="bi bi-images me-1.5 text-primary"></i>Foto Dokumentasi (<span id="photo-count-num-${e.id}">${e.foto ? e.foto.length : 0}</span>)
                </span>
                <div class="d-flex gap-1">
                  <label class="btn btn-sm btn-primary rounded-pill px-3 py-1 fw-semibold small cursor-pointer mb-0 shadow-sm d-print-none" style="font-size: 0.78rem;">
                    <i class="bi bi-cloud-arrow-up-fill me-1"></i>+ Upload
                    <input type="file" accept="image/*" multiple style="display:none;" onchange="uploadFotoKegiatanDirect(this.files, ${e.id}, this)">
                  </label>
                  <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 fw-semibold small mb-0 shadow-sm d-print-none" onclick="bukaModalGDriveFoto(${e.id}, '${(e.title || '').replace(/'/g, "\\'")}')" style="font-size: 0.78rem;">
                    <i class="bi bi-google-drive me-1 text-success"></i>+ Drive
                  </button>
                </div>
              </div>

              <div class="t-photos d-flex flex-wrap gap-2" id="t-photos-${e.id}">
                ${(e.foto || []).map(src => `
                  <div class="t-photo-item position-relative" style="width: 68px; height: 68px;">
                    <img src="${formatPhotoUrl(src)}" onclick="openLightboxSrc('${formatPhotoUrl(src)}','${(e.title || '').replace(/'/g,"\\'")}')" style="cursor: pointer; width: 100%; height: 100%; object-fit: cover; border-radius: 10px; display: block; border: 1px solid rgba(0,0,0,0.08);" onerror="this.onerror=null; this.src='img/foto kkn bareng.JPG';">
                    <button class="btn btn-sm btn-danger p-0 d-print-none" onclick="hapusFotoKegiatan(event, '${src}', ${e.id}, this)" title="Hapus foto ini" style="position: absolute; top: -5px; right: -5px; border-radius: 50%; width: 20px; height: 20px; font-size: 11px; display: flex; align-items: center; justify-content: center; opacity: 0.95; box-shadow: 0 2px 4px rgba(0,0,0,0.3); border: none;">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>
                `).join('')}
              </div>
              <div id="upload-status-${e.id}" class="small text-primary mt-2 fw-semibold d-none"></div>
            </div>

            <div class="d-flex justify-content-end mt-3 d-print-none gap-2">
              <button class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;" onclick="bukaRundownModal(${e.id})">
                <i class="bi bi-calendar3"></i> Rundown
              </button>
              <button class="btn btn-sm btn-outline-danger" style="border-radius: 8px;" onclick="hapusKegiatan(${e.id})">
                <i class="bi bi-trash"></i> Hapus
              </button>
              <button class="btn btn-sm btn-outline-primary" style="border-radius: 8px;" onclick="editKegiatan(${e.id})">
                <i class="bi bi-pencil-square"></i> Edit Kegiatan
              </button>
            </div>
          </div>
        </div>
      </div>
    `;
    tl.appendChild(el);
  });

  if(keepOpenId === undefined && tl.firstElementChild){
    tl.firstElementChild.classList.add('open');
  }
}

function simpanSubTitleRundown(val) {
  if (val) localStorage.setItem('rundownSubTitle', val.trim());
}
function simpanDateTextRundown(val) {
  if (val) localStorage.setItem('rundownDateText', val.trim());
}

function generateRundownPosterHtml(sortedJadwal, isPrint = false) {
  let dateText = "20 Juli — 30 Agustus 2026";
  let titleText = "Jadwal Acara & Rundown Kegiatan KKN";

  if (localStorage.getItem('rundownSubTitle')) {
    titleText = localStorage.getItem('rundownSubTitle');
  }
  if (localStorage.getItem('rundownDateText')) {
    dateText = localStorage.getItem('rundownDateText');
  }

  let itemsHtml = "";
  let printTableHtml = "";

  if (sortedJadwal.length === 0) {
    itemsHtml = `
      <div class="text-center py-5 text-muted">
        <i class="bi bi-calendar2-x display-4 text-muted opacity-50 mb-3 d-block"></i>
        <p class="mb-2 fw-semibold">Belum ada jadwal acara yang tercatat.</p>
        ${!isPrint ? `<button class="btn btn-sm btn-primary rounded-pill px-4 py-2 mt-2" onclick="bukaKelolaJadwalAcaraModal()">
          <i class="bi bi-plus-lg me-1"></i> Tambah Jadwal Acara Baru
        </button>` : ''}
      </div>`;
    printTableHtml = `
      <div class="text-center py-4 text-muted border rounded-3 mt-3">
        <p class="mb-0 fw-semibold">Belum ada jadwal acara yang tercatat.</p>
      </div>`;
  } else {
    // Grouping by date (tanggal)
    const grouped = {};
    sortedJadwal.forEach(e => {
      const dateKey = e.tanggal || `${e.hari}-${e.tgl}-${e.bulan}`;
      if (!grouped[dateKey]) grouped[dateKey] = [];
      grouped[dateKey].push(e);
    });

    // 1. Build Web View (Grouped by date header)
    Object.keys(grouped).forEach(dateKey => {
      const groupItems = grouped[dateKey];
      const first = groupItems[0];
      const formattedDate = `${first.hari}, ${first.tgl} ${first.bulan} 2026`;

      itemsHtml += `
        <div class="rundown-date-group mb-4">
          <div class="d-flex align-items-center gap-2 mb-2 pb-2 border-bottom">
            <span class="badge bg-primary text-white fw-bold px-3 py-1.5 rounded-pill" style="font-size: 0.85rem;">
              <i class="bi bi-calendar-event me-1"></i> ${formattedDate}
            </span>
          </div>
          <div class="ps-1">
      `;

      groupItems.forEach(e => {
        const subInfo = [];
        if (e.pic) subInfo.push(`PIC: ${e.pic}`);
        if (e.keterangan && e.keterangan !== '-') subInfo.push(e.keterangan);
        const subTextHtml = subInfo.length > 0 ? `<div class="rundown-item-sub">${subInfo.join(' &middot; ')}</div>` : '';

        itemsHtml += `
          <div class="rundown-item">
            <div class="rundown-time">${e.waktu}</div>
            <div class="rundown-arrow">&longrightarrow;</div>
            <div class="rundown-agenda">
              <div>${e.agenda}</div>
              ${subTextHtml}
            </div>
            ${(!isPrint && !isViewer) ? `
            <div class="rundown-actions d-print-none ms-auto">
              <button class="rundown-btn-action edit" title="Edit Item" onclick="editJadwalAcaraDariTimeline(${e.id}, '${e.tanggal}', '${(e.waktu || '').replace(/'/g, "\\'")}', '${(e.agenda || '').replace(/'/g, "\\'")}', '${(e.pic || '').replace(/'/g, "\\'")}', '${(e.keterangan || '').replace(/'/g, "\\'")}')">
                <i class="bi bi-pencil me-1"></i>Edit
              </button>
              <button class="rundown-btn-action delete" title="Hapus Item" onclick="hapusJadwalAcaraItem(${e.id})">
                <i class="bi bi-trash me-1"></i>Hapus
              </button>
            </div>` : ''}
          </div>`;
      });

      itemsHtml += `
          </div>
        </div>`;
    });

    // 2. Build PDF Print View (Formal Table with Kop Surat & Signatures)
    if (isPrint) {
      let rowsArr = [];
      Object.keys(grouped).forEach(dateKey => {
        const groupItems = grouped[dateKey];
        const first = groupItems[0];
        const formattedDate = `${first.hari}, ${first.tgl} ${first.bulan} 2026`;

        groupItems.forEach((e, idx) => {
          const tempatPic = [e.pic, (e.keterangan && e.keterangan !== '-') ? e.keterangan : ''].filter(Boolean).join(' - ') || '-';
          if (idx === 0) {
            rowsArr.push(`
              <tr>
                <td rowspan="${groupItems.length}" style="border: 1px solid #000; padding: 7px 8px; font-weight: 600; vertical-align: middle; text-align: center; font-size: 9pt; background-color: #f8fafc;">${formattedDate}</td>
                <td style="border: 1px solid #000; padding: 7px 8px; text-align: center; font-weight: 600; font-size: 9pt;">${e.waktu}</td>
                <td style="border: 1px solid #000; padding: 7px 8px; font-size: 9pt;">${e.agenda}</td>
                <td style="border: 1px solid #000; padding: 7px 8px; text-align: center; font-size: 9pt;">${tempatPic}</td>
              </tr>`);
          } else {
            rowsArr.push(`
              <tr>
                <td style="border: 1px solid #000; padding: 7px 8px; text-align: center; font-weight: 600; font-size: 9pt;">${e.waktu}</td>
                <td style="border: 1px solid #000; padding: 7px 8px; font-size: 9pt;">${e.agenda}</td>
                <td style="border: 1px solid #000; padding: 7px 8px; text-align: center; font-size: 9pt;">${tempatPic}</td>
              </tr>`);
          }
        });
      });

      return `
        <div class="print-only-pdf-container">
          <!-- Print Only Header -->
          <div class="print-only-header mb-2" style="border-bottom: 3px double #000; padding-bottom: 8px; font-family: 'Times New Roman', Times, serif; color: #000;">
              <table style="width: 100%; border: none !important; border-collapse: collapse; margin: 0; padding: 0;">
                  <tr>
                      <td style="width: 80px; text-align: left; vertical-align: middle; border: none !important; padding: 0 !important;">
                          <img src="${logoUmpSrc}" alt="Logo UMP" style="height: 70px; width: auto; display: block; object-fit: contain;">
                      </td>
                      <td style="text-align: center; vertical-align: middle; border: none !important; padding: 0 10px !important;">
                          <h2 style="font-size: 13.5pt; font-weight: bold; letter-spacing: 0.5px; text-transform: uppercase; margin: 0 0 3px; font-family: 'Times New Roman', Times, serif; line-height: 1.2;">UNIVERSITAS MUHAMMADIYAH PONTIANAK</h2>
                          <h3 style="font-size: 11.5pt; font-weight: bold; text-transform: uppercase; margin: 0 0 3px; font-family: 'Times New Roman', Times, serif; line-height: 1.2;">KULIAH KERJA NYATA (KKN) KELOMPOK 12</h3>
                          <h4 style="font-size: 10.5pt; font-weight: bold; text-transform: uppercase; color: #000; margin: 0 0 4px; font-family: 'Times New Roman', Times, serif; line-height: 1.2;">JADWAL ACARA &amp; RUNDOWN KEGIATAN KKN</h4>
                          <p style="font-size: 9.5pt; margin: 0 0 2px; font-family: 'Times New Roman', Times, serif; line-height: 1.2;">Kelurahan Pal Lima &middot; Kecamatan Pontianak Barat &middot; Kota Pontianak</p>
                          <p style="font-size: 9pt; font-style: italic; color: #334155; margin: 0; font-family: 'Times New Roman', Times, serif; line-height: 1.2;">Periode Pelaksanaan: ${dateText}</p>
                      </td>
                      <td style="width: 80px; text-align: right; vertical-align: middle; border: none !important; padding: 0 !important;">
                          <img src="${logoKknSrc}" alt="Logo KKN 12" style="height: 65px; width: auto; display: block; margin-left: auto; object-fit: contain;">
                      </td>
                  </tr>
              </table>
          </div>

          <!-- Report Table -->
          <div class="mt-1">
            <table class="formal-report-table" style="width: 100%; border-collapse: collapse; margin-top: 8px; font-family: 'Times New Roman', Times, serif; table-layout: fixed;">
              <thead>
                <tr style="background-color: #f8fafc;">
                  <th style="width: 25%; border: 1px solid #000; padding: 7px 8px; text-align: center; font-weight: bold; font-size: 9.5pt;">Hari / Tanggal</th>
                  <th style="width: 18%; border: 1px solid #000; padding: 7px 8px; text-align: center; font-weight: bold; font-size: 9.5pt;">Waktu</th>
                  <th style="width: 37%; border: 1px solid #000; padding: 7px 8px; text-align: left; font-weight: bold; font-size: 9.5pt;">Agenda Kegiatan</th>
                  <th style="width: 20%; border: 1px solid #000; padding: 7px 8px; text-align: center; font-weight: bold; font-size: 9.5pt;">PIC / Lokasi</th>
                </tr>
              </thead>
              <tbody>
                ${rowsArr.join('')}
              </tbody>
            </table>
          </div>

          <!-- Signature Block -->
          <div class="print-only-signatures mt-4 pt-3" style="font-family: 'Times New Roman', Times, serif; color: #000; page-break-inside: avoid;">
            <div class="d-flex justify-content-between text-center" style="margin-top: 30px;">
              <div style="width: 40%;">
                <p class="mb-1">Mengetahui,</p>
                <p class="fw-bold mb-5">Dosen Pembimbing Lapangan (DPL)</p>
                <p class="fw-bold mb-0 text-decoration-underline">(...................................................)</p>
                <p class="small text-muted" style="font-size: 8.5pt;">NIDN. .....................................</p>
              </div>
              <div style="width: 40%;">
                <p class="mb-1">Pontianak, 30 Agustus 2026</p>
                <p class="fw-bold mb-5">Ketua KKN Kelompok 12</p>
                <p class="fw-bold mb-0 text-decoration-underline">Rizki Tri Saputra</p>
                <p class="small text-muted" style="font-size: 8.5pt;">NIM. .....................................</p>
              </div>
            </div>
          </div>
        </div>
      `;
    }
  }

  return `
    <div class="rundown-card-wrapper">
      <!-- Top Branding Header with Dual Logos -->
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
        <div class="d-flex align-items-center gap-3">
          <img src="${logoUmpSrc}" alt="Logo UMP" style="height: 54px; width: auto; object-fit: contain;">
          <div>
            <div class="fw-bold text-dark lh-sm" style="font-size: 0.95rem;">Universitas Muhammadiyah Pontianak</div>
            <div class="text-muted small">Kuliah Kerja Nyata (KKN) &middot; Kelurahan Pal Lima</div>
          </div>
        </div>

        <div class="d-flex align-items-center gap-3 ms-auto">
          <div class="d-flex align-items-center gap-2">
            <img src="${logoKknSrc}" alt="Logo KKN 12" style="height: 48px; width: auto; object-fit: contain;">
            <div class="d-none d-sm-block text-end">
              <div class="fw-bold text-primary lh-sm" style="font-size: 0.9rem;">Kelompok 12</div>
              <div class="text-muted" style="font-size: 0.75rem;">KKN 2026</div>
            </div>
          </div>

          ${!isPrint ? `
          <div class="d-flex gap-2 d-print-none ms-2">
            <button class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1.5 fw-semibold" onclick="bukaKelolaJadwalAcaraModal()">
              <i class="bi bi-gear-fill me-1"></i> Kelola
            </button>
            <button class="btn btn-primary btn-sm rounded-pill px-3 py-1.5 fw-semibold" onclick="siapkanJadwalPDF()">
              <i class="bi bi-download me-1"></i> Unduh PDF
            </button>
          </div>` : ''}
        </div>
      </div>

      <!-- Rundown Sub-Header -->
      <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="badge bg-primary text-white fw-bold px-3 py-1.5 rounded-pill text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">RUNDOWN ACARA</span>
          <span class="badge bg-light text-dark border px-3 py-1.5 rounded-pill small fw-semibold" ${!isPrint ? 'contenteditable="true" title="Klik untuk mengedit tanggal" onblur="simpanDateTextRundown(this.innerText)"' : ''}>${dateText}</span>
        </div>
        <h3 class="fw-bold text-dark mb-0 fs-3" ${!isPrint ? 'contenteditable="true" title="Klik me-edit judul" onblur="simpanSubTitleRundown(this.innerText)"' : ''}>${titleText}</h3>
      </div>

      <!-- Items Section -->
      <div class="rundown-list-container">
        ${itemsHtml}
      </div>
    </div>
  `;
}

function renderJadwalTimeline() {
  const container = document.getElementById('timeline-jadwal-acara');
  if (!container) return;
  const sortedJadwal = [...jadwalAcaraEntries].sort((a, b) => new Date(a.tanggal) - new Date(b.tanggal));
  container.innerHTML = generateRundownPosterHtml(sortedJadwal, false);
}

function editJadwalAcaraDariTimeline(id, tanggal, waktu, agenda, pic, keterangan) {
  // Buka modal
  const modalEl = document.getElementById('kelolaJadwalAcaraModal');
  const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
  modal.show();
  // Jalankan edit
  editJadwalAcaraItem(id, tanggal, waktu, agenda, pic, keterangan);
}

function update(keepOpenId){
  renderTimeline(getFilteredEntries(), keepOpenId);
  renderJadwalTimeline();
}

function tambahKegiatanBaru() {
  const modalTitle = document.querySelector('#kegiatanModal .modal-title');
  if (modalTitle) {
    modalTitle.innerHTML = '<i class="bi bi-journal-plus text-primary me-2"></i>Catat Kegiatan Baru';
  }

  const form = document.getElementById('kegiatanForm');
  form.reset();
  document.getElementById('kegiatanId').value = '';

  const submitBtnText = document.getElementById('kegiatanBtnText');
  if (submitBtnText) submitBtnText.textContent = 'Simpan Kegiatan';

  const alertBox = document.getElementById('kegiatanAlert');
  if (alertBox) alertBox.classList.add('d-none');
}

function editKegiatan(id) {
  const entry = entries.find(e => e.id === id);
  if (!entry) return;

  // Set modal title
  const modalTitle = document.querySelector('#kegiatanModal .modal-title');
  if (modalTitle) {
    modalTitle.innerHTML = '<i class="bi bi-pencil-square text-primary me-2"></i>Edit Kegiatan';
  }

  // Prepopulate form fields
  const form = document.getElementById('kegiatanForm');
  form.reset();

  document.getElementById('kegiatanId').value = entry.id;
  form.querySelector('[name="judul"]').value = entry.title;
  form.querySelector('[name="bidang"]').value = entry.divisiLabel === '-' ? '' : entry.divisiLabel;
  form.querySelector('[name="tanggal"]').value = entry.tanggal;
  form.querySelector('[name="jam_mulai"]').value = entry.jam_mulai;
  form.querySelector('[name="jam_selesai"]').value = entry.jam_selesai;
  form.querySelector('[name="lokasi"]').value = entry.lokasi === '-' ? '' : entry.lokasi;
  form.querySelector('[name="sasaran"]').value = entry.sasaran === '-' ? '' : entry.sasaran;
  form.querySelector('[name="deskripsi"]').value = entry.desc === '-' ? '' : entry.desc;
  form.querySelector('[name="hasil"]').value = entry.hasil === '-' ? '' : entry.hasil;

  const submitBtnText = document.getElementById('kegiatanBtnText');
  if (submitBtnText) submitBtnText.textContent = 'Perbarui Kegiatan';

  const alertBox = document.getElementById('kegiatanAlert');
  if (alertBox) alertBox.classList.add('d-none');

  // Show the modal
  const modal = new bootstrap.Modal(document.getElementById('kegiatanModal'));
  modal.show();
}

let kegiatanIdUntukDihapus = null;

function hapusKegiatan(id) {
  kegiatanIdUntukDihapus = id;
  const modalEl = document.getElementById('hapusKegiatanModal');
  if (modalEl) {
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
  }
}

// Bind confirmation button click
const confirmHapusBtnEl = document.getElementById('confirmHapusBtn');
if (confirmHapusBtnEl) {
  confirmHapusBtnEl.addEventListener('click', () => {
    if (!kegiatanIdUntukDihapus) return;

    const btn = document.getElementById('confirmHapusBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menghapus...';

    const formData = new FormData();
    formData.append('id', kegiatanIdUntukDihapus);

    fetch('hapus_kegiatan.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      btn.disabled = false;
      btn.textContent = 'Ya, Hapus';
      if (data.success) {
        const modalEl = document.getElementById('hapusKegiatanModal');
        if (modalEl) {
          const modal = bootstrap.Modal.getInstance(modalEl);
          if (modal) modal.hide();
        }
        
        // Remove from memory & update UI instantly without page reload!
        const targetId = kegiatanIdUntukDihapus;
        entries = entries.filter(e => e.id !== targetId);
        kegiatanIdUntukDihapus = null;
        update();
      } else {
        alert(data.message || 'Gagal menghapus kegiatan.');
      }
    })
    .catch((err) => {
      console.error('Error delete kegiatan:', err);
      alert('Terjadi kesalahan jaringan atau server.');
      btn.disabled = false;
      btn.textContent = 'Ya, Hapus';
    });
  });
}

function uploadFotoKegiatanDirect(files, kegiatanId, inputEl) {
  if (!files || files.length === 0) return;

  const statusEl = document.getElementById(`upload-status-${kegiatanId}`);
  if (statusEl) {
    statusEl.classList.remove('d-none');
    statusEl.innerHTML = '<span class="spinner-border spinner-border-sm me-1 text-primary"></span>Mengunggah foto...';
  }

  const formData = new FormData();
  formData.append('kegiatan_id', kegiatanId);
  for (const file of files) {
    formData.append('foto[]', file);
  }

  fetch('simpan_foto.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if (statusEl) statusEl.classList.add('d-none');
      if (inputEl) inputEl.value = '';

      if (data.success) {
        const newPaths = data.paths || [];
        const entry = entries.find(e => e.id === kegiatanId);
        if (entry) {
          if (!entry.foto) entry.foto = [];
          newPaths.forEach(p => {
            if (!entry.foto.includes(p)) entry.foto.push(p);
          });
        }
        // Update timeline instantly without full page refresh!
        renderTimeline(getFilteredEntries(), kegiatanId);
        showUploadToast();
      } else {
        alert(data.message || 'Gagal mengunggah foto.');
      }
    })
    .catch(err => {
      if (statusEl) statusEl.classList.add('d-none');
      if (inputEl) inputEl.value = '';
      console.error(err);
      alert('Terjadi kesalahan jaringan saat mengunggah foto.');
    });
}

document.getElementById('gdriveFotoForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const submitBtn = document.getElementById('gdriveSubmitBtn');
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
  
  const kegiatanId = parseInt(document.getElementById('gdriveKegiatanId').value, 10);
  const formData = new FormData(this);

  fetch('simpan_foto.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Simpan Foto';
      if (data.success) {
        const modalEl = document.getElementById('gdriveFotoModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        
        const newPaths = data.paths || [];
        const entry = entries.find(e => e.id === kegiatanId);
        if (entry) {
          if (!entry.foto) entry.foto = [];
          newPaths.forEach(p => {
            if (!entry.foto.includes(p)) entry.foto.push(p);
          });
        }
        renderTimeline(getFilteredEntries(), kegiatanId);
        showUploadToast();
      } else {
        alert(data.message || 'Gagal menyimpan foto dari Google Drive.');
      }
    })
    .catch(err => {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Simpan Foto';
      alert('Terjadi kesalahan jaringan.');
    });
});

function hapusFotoKegiatan(event, src, kegiatanId, btnEl) {
  event.stopPropagation(); // Mencegah lightbox terbuka
  
  if (!confirm('Apakah Anda yakin ingin menghapus foto dokumentasi ini?')) {
    return;
  }

  const photoItem = btnEl ? btnEl.closest('.t-photo-item') : null;
  if (photoItem) {
    photoItem.style.opacity = '0.4';
  }

  const formData = new FormData();
  formData.append('kegiatan_id', kegiatanId);
  formData.append('path_foto', src);

  fetch('hapus_foto_kegiatan.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      // 1. Update in-memory entries array
      const entry = entries.find(e => e.id === kegiatanId);
      if (entry && entry.foto) {
        entry.foto = entry.foto.filter(f => f !== src);
      }

      // 2. Instant DOM removal without page refresh!
      if (photoItem) {
        photoItem.remove();
      }

      // 3. Update count label and card header badge
      const countEl = document.getElementById(`photo-count-num-${kegiatanId}`);
      if (countEl && entry) {
        countEl.textContent = entry.foto.length;
      }
      
      const badgeWrap = document.getElementById(`badge-photo-wrap-${kegiatanId}`);
      if (badgeWrap && entry) {
        if (entry.foto.length > 0) {
          badgeWrap.innerHTML = `<span class="badge bg-primary text-white rounded-pill px-2.5 py-1 small badge-camera" style="font-size: 0.75rem;"><i class="bi bi-camera-fill me-1"></i>${entry.foto.length} Foto</span>`;
        } else {
          badgeWrap.innerHTML = '';
        }
      }
    } else {
      if (photoItem) photoItem.style.opacity = '1';
      alert(data.message || 'Gagal menghapus foto.');
    }
  })
  .catch(() => {
    if (photoItem) photoItem.style.opacity = '1';
    alert('Terjadi kesalahan jaringan.');
  });
}

let currentRundownKegiatanId = null;

function bukaRundownModal(kegiatanId) {
  currentRundownKegiatanId = kegiatanId;
  const entry = entries.find(e => e.id === kegiatanId);
  if (!entry) return;

  // Set modal title
  document.getElementById('rundownModalTitle').innerHTML = '<i class="bi bi-calendar3 text-primary me-2"></i>Kelola Rundown - ' + entry.title;
  
  // Set hidden field
  document.getElementById('rundownKegiatanId').value = kegiatanId;
  resetRundownForm();

  // Load list
  loadRundownList(kegiatanId);

  // Show modal
  const modal = new bootstrap.Modal(document.getElementById('kegiatanRundownModal'));
  modal.show();
}

function resetRundownForm() {
  const form = document.getElementById('rundownForm');
  form.reset();
  document.getElementById('rundownId').value = '';
  document.getElementById('rundownFormLabel').innerHTML = '<i class="bi bi-plus-circle me-1 text-primary"></i>Tambah Agenda Baru';
  document.getElementById('rundownSubmitBtn').textContent = 'Simpan';
}

function loadRundownList(kegiatanId) {
  const tbody = document.getElementById('rundownTableBody');
  tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3"><span class="spinner-border spinner-border-sm me-2"></span>Memuat data...</td></tr>';

  fetch(`proses_rundown.php?action=list&kegiatan_id=${kegiatanId}`)
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        if (res.data.length === 0) {
          tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Belum ada agenda rundown untuk kegiatan ini.</td></tr>';
          return;
        }

        tbody.innerHTML = res.data.map(item => `
          <tr>
            <td><strong>${item.waktu}</strong></td>
            <td>${item.agenda}</td>
            <td>${item.pic || '-'}</td>
            <td>${item.keterangan || '-'}</td>
            <td class="text-end">
              <button class="btn btn-sm btn-link text-primary p-1 me-1" onclick="editRundownItem(${item.id}, '${item.waktu.replace(/'/g, "\\'")}', '${item.agenda.replace(/'/g, "\\'")}', '${(item.pic || '').replace(/'/g, "\\'")}', '${(item.keterangan || '').replace(/'/g, "\\'")}')" title="Edit"><i class="bi bi-pencil-fill"></i></button>
              <button class="btn btn-sm btn-link text-danger p-1" onclick="hapusRundownItem(${item.id})" title="Hapus"><i class="bi bi-trash-fill"></i></button>
            </td>
          </tr>
        `).join('');
      } else {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-3">${res.message || 'Gagal memuat data.'}</td></tr>`;
      }
    })
    .catch(() => {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-3">Terjadi kesalahan jaringan.</td></tr>';
    });
}

function editRundownItem(id, waktu, agenda, pic, keterangan) {
  document.getElementById('rundownId').value = id;
  document.getElementById('rundownWaktu').value = waktu;
  document.getElementById('rundownAgenda').value = agenda;
  document.getElementById('rundownPic').value = pic;
  document.getElementById('rundownKeterangan').value = keterangan;

  document.getElementById('rundownFormLabel').innerHTML = '<i class="bi bi-pencil-fill me-1 text-primary"></i>Edit Agenda';
  document.getElementById('rundownSubmitBtn').textContent = 'Update';
}

function hapusRundownItem(id) {
  if (!confirm('Apakah Anda yakin ingin menghapus agenda rundown ini?')) {
    return;
  }

  const formData = new FormData();
  formData.append('id', id);

  fetch('proses_rundown.php?action=delete', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        loadRundownList(currentRundownKegiatanId);
      } else {
        alert(res.message || 'Gagal menghapus agenda.');
      }
    })
    .catch(() => {
      alert('Terjadi kesalahan jaringan.');
    });
}

// Bind rundownForm submit
document.getElementById('rundownForm').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const submitBtn = document.getElementById('rundownSubmitBtn');
  submitBtn.disabled = true;
  submitBtn.textContent = 'Menyimpan...';

  fetch('proses_rundown.php?action=save', {
    method: 'POST',
    body: new FormData(this)
  })
    .then(res => res.json())
    .then(res => {
      submitBtn.disabled = false;
      if (res.success) {
        resetRundownForm();
        loadRundownList(currentRundownKegiatanId);
      } else {
        alert(res.message || 'Gagal menyimpan agenda.');
        submitBtn.textContent = 'Simpan';
      }
    })
    .catch(() => {
      alert('Terjadi kesalahan jaringan.');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Simpan';
    });
});

// Refresh main page when rundown modal is closed
document.getElementById('kegiatanRundownModal').addEventListener('hidden.bs.modal', function () {
  location.reload();
});

function ensureHtml2PdfLoaded(callback) {
  if (typeof html2pdf !== 'undefined') {
    callback();
    return;
  }
  const script = document.createElement('script');
  script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
  script.onload = callback;
  script.onerror = function() {
    alert('Gagal memuat modul PDF. Silakan gunakan opsi "Cetak / Print" untuk mencetak/menyimpan PDF.');
  };
  document.head.appendChild(script);
}

function siapkanPDF() {
  ensureHtml2PdfLoaded(function() {
    document.querySelectorAll('.chip').forEach(c=>c.classList.remove('active'));
    document.querySelector('.chip[data-filter="semua"]').classList.add('active');
    document.getElementById('searchInput').value = "";
    const clearBtn = document.getElementById('searchClear');
    if (clearBtn) clearBtn.style.display = "none";
    update();
    document.querySelectorAll('.entry').forEach(el => el.classList.add('open'));

    const btn = document.querySelector('button[onclick="siapkanPDF()"]');
    const originalHtml = btn ? btn.innerHTML : '';
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Menyiapkan PDF...';
    }

    setTimeout(() => {
      window.scrollTo(0, 0);

      const RENDER_WIDTH = 750;

      const wrapper = document.createElement('div');
      wrapper.style.cssText = 'position:fixed;left:0;top:0;width:' + RENDER_WIDTH + 'px;z-index:-99999;opacity:0.01;pointer-events:none;background:#fff;';
      document.body.appendChild(wrapper);

      const tempDiv = document.createElement('div');
      tempDiv.className = 'html2pdf-container';
      tempDiv.style.cssText = 'background:#fff;color:#000;padding:12px 15px;font-family:"Times New Roman",Times,serif;width:' + RENDER_WIDTH + 'px;box-sizing:border-box;margin:0;';

      const printHeader = document.querySelector('.print-only-header');
      const printReport = document.querySelector('.print-only-report');
      
      if (!printHeader || !printReport) {
        alert('Elemen cetak tidak ditemukan.');
        document.body.removeChild(wrapper);
        if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
        return;
      }

      const headerClone = printHeader.cloneNode(true);
      const reportClone = printReport.cloneNode(true);
      headerClone.style.setProperty('display', 'block', 'important');
      reportClone.style.setProperty('display', 'block', 'important');

      tempDiv.appendChild(headerClone);
      tempDiv.appendChild(reportClone);
      wrapper.appendChild(tempDiv);

      // Force reflow
      void tempDiv.offsetHeight;

      const opt = {
        margin:       [10, 8, 12, 8],
        filename:     'Buku_Lapangan_KKN_Kelompok_12.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { 
          scale: 2, 
          useCORS: true, 
          allowTaint: true,
          logging: false, 
          width: RENDER_WIDTH, 
          windowWidth: RENDER_WIDTH, 
          x: 0,
          y: 0,
          scrollX: 0, 
          scrollY: 0 
        },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak:    { mode: ['css', 'legacy'] }
      };

      html2pdf().set(opt).from(tempDiv).save().then(() => {
        if (wrapper.parentNode) document.body.removeChild(wrapper);
        if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
      }).catch(err => {
        console.error(err);
        alert('Gagal membuat PDF. Silakan coba lagi.');
        if (wrapper.parentNode) document.body.removeChild(wrapper);
        if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
      });
    }, 300);
  });
}

function siapkanJadwalPDF() {
  const pContainer = document.getElementById('printJadwalContainer');
  if (!pContainer) return;

  const sortedEntries = [...jadwalAcaraEntries].sort((a, b) => {
    return new Date(a.tanggal) - new Date(b.tanggal);
  });

  pContainer.innerHTML = generateRundownPosterHtml(sortedEntries, true);

  const btn = document.querySelector('button[onclick="siapkanJadwalPDF()"]');
  const originalHtml = btn ? btn.innerHTML : '';
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Menyiapkan PDF...';
  }

  setTimeout(() => {
    window.scrollTo(0, 0);

    const RENDER_WIDTH = 750;

    const wrapper = document.createElement('div');
    wrapper.style.cssText = 'position:fixed;left:0;top:0;width:' + RENDER_WIDTH + 'px;z-index:-99999;opacity:0.01;pointer-events:none;background:#fff;';
    document.body.appendChild(wrapper);

    const tempDiv = document.createElement('div');
    tempDiv.className = 'html2pdf-container';
    tempDiv.style.cssText = 'background:#fff;color:#000;padding:12px 15px;font-family:"Times New Roman",Times,serif;width:' + RENDER_WIDTH + 'px;box-sizing:border-box;margin:0;';

    const contentClone = pContainer.cloneNode(true);
    contentClone.style.setProperty('display', 'block', 'important');

    tempDiv.appendChild(contentClone);
    wrapper.appendChild(tempDiv);

    void tempDiv.offsetHeight;

    const opt = {
      margin:       [10, 8, 12, 8],
      filename:     'Jadwal_Acara_KKN_Kelompok_12.pdf',
      image:        { type: 'jpeg', quality: 0.98 },
      html2canvas:  { 
        scale: 2, 
        useCORS: true, 
        allowTaint: true,
        logging: false, 
        width: RENDER_WIDTH, 
        windowWidth: RENDER_WIDTH, 
        x: 0,
        y: 0,
        scrollX: 0, 
        scrollY: 0 
      },
      jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
      pagebreak:    { mode: ['css', 'legacy'] }
    };

    html2pdf().set(opt).from(tempDiv).outputPdf('blob').then(blob => {
      const blobUrl = URL.createObjectURL(blob);
      window.open(blobUrl, '_blank');
      document.body.removeChild(wrapper);
      if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
    }).catch(err => {
      console.error(err);
      alert('Gagal membuat PDF. Silakan coba lagi.');
      if (wrapper.parentNode) document.body.removeChild(wrapper);
      if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
    });
  }, 300);
}

document.querySelectorAll('.chip').forEach(chip=>{
  chip.addEventListener('click', ()=>{
    document.querySelectorAll('.chip').forEach(c=>c.classList.remove('active'));
    chip.classList.add('active');
    update();
    
    // Auto scroll ke timeline hasil filter
    const timelineEl = document.getElementById('timeline');
    if (timelineEl) {
      timelineEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});

const searchInputEl = document.getElementById('searchInput');
const searchClearEl = document.getElementById('searchClear');
let searchScrollTimer = null;

searchInputEl.addEventListener('input', ()=>{
  searchClearEl.style.display = searchInputEl.value ? "flex" : "none";
  update();

  // Otomatis scroll ke bawah (timeline hasil pencarian) saat mengetik
  if (searchInputEl.value.trim().length > 0) {
    clearTimeout(searchScrollTimer);
    searchScrollTimer = setTimeout(() => {
      const timelineEl = document.getElementById('timeline');
      if (timelineEl) {
        timelineEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }, 350);
  }
});

searchClearEl.addEventListener('click', ()=>{
  searchInputEl.value = "";
  searchClearEl.style.display = "none";
  searchInputEl.focus();
  update();
});

document.getElementById('kegiatanForm').addEventListener('submit', function(e){
  e.preventDefault();
  const alertBox = document.getElementById('kegiatanAlert');
  const btnText = document.getElementById('kegiatanBtnText');
  const btnSpinner = document.getElementById('kegiatanBtnSpinner');
  const submitBtn = document.getElementById('kegiatanSubmitBtn');

  alertBox.classList.add('d-none');
  submitBtn.disabled = true;
  btnText.textContent = 'Menyimpan...';
  btnSpinner.classList.remove('d-none');

  const formData = new FormData(this);

  fetch('simpan_kegiatan.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        location.reload();
      } else {
        alertBox.textContent = data.message || 'Gagal menyimpan kegiatan.';
        alertBox.classList.remove('d-none');
        submitBtn.disabled = false;
        btnText.textContent = 'Simpan Kegiatan';
        btnSpinner.classList.add('d-none');
      }
    })
    .catch(() => {
      alertBox.textContent = 'Terjadi kesalahan koneksi.';
      alertBox.classList.remove('d-none');
      submitBtn.disabled = false;
      btnText.textContent = 'Simpan Kegiatan';
      btnSpinner.classList.add('d-none');
    });
});

function populateTargetDropdown(){
  const sel = document.getElementById('uTargetEntry');
  if (!sel) return;
  const currentVal = sel.value;
  sel.innerHTML = '<option value="">Pilih kegiatan...</option>' +
    entries.map((e) => `<option value="${e.id}">${e.bulan} ${e.tgl} — ${e.title}</option>`).join('');
  if(currentVal) sel.value = currentVal;
}

const uploadBox = document.getElementById('uploadBox');
const uPhotosInput = document.getElementById('uPhotos');
const previewStrip = document.getElementById('previewStrip');
const previewCount = document.getElementById('previewCount');
let photoBuffer = [];

if (uploadBox && uPhotosInput) {
  uploadBox.addEventListener('click', (e) => {
    if (e.target !== uPhotosInput) {
      uPhotosInput.click();
    }
  });
  uploadBox.addEventListener('dragover', e => { e.preventDefault(); uploadBox.style.borderColor = 'var(--primary)'; });
  uploadBox.addEventListener('dragleave', () => { uploadBox.style.borderColor = 'var(--border)'; });
  uploadBox.addEventListener('drop', e => {
    e.preventDefault();
    uploadBox.style.borderColor = 'var(--border)';
    uPhotosInput.files = e.dataTransfer.files;
    handleFiles(e.dataTransfer.files);
  });
  uPhotosInput.addEventListener('change', () => handleFiles(uPhotosInput.files));
}

function handleFiles(fileList){
  photoBuffer = [];
  if (previewStrip) previewStrip.innerHTML = '';
  Array.from(fileList).forEach(file => {
    if(!file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = e => {
      photoBuffer.push(e.target.result);
      renderPreview();
    };
    reader.readAsDataURL(file);
  });
}

function renderPreview(){
  if (previewCount) {
    previewCount.style.display = photoBuffer.length ? "block" : "none";
    previewCount.textContent = photoBuffer.length ? `${photoBuffer.length} foto dipilih` : "";
  }
  if (previewStrip) {
    previewStrip.innerHTML = photoBuffer.map((src) => `
      <div class="preview-thumb">
        <img src="${src}">
      </div>
    `).join('');
  }
}

const uploadFormEl = document.getElementById('uploadForm');
if (uploadFormEl) {
  uploadFormEl.addEventListener('submit', function(e){
    e.preventDefault();
    const sel = document.getElementById('uTargetEntry');
    const targetId = sel ? sel.value : "";

    if(targetId === ""){
      alert("Pilih kegiatan yang terkait dulu ya.");
      return;
    }
    if(!uPhotosInput || uPhotosInput.files.length === 0){
      alert("Pilih minimal satu foto dulu.");
      return;
    }

    const formData = new FormData();
    formData.append('kegiatan_id', targetId);
    for (const file of uPhotosInput.files) {
      formData.append('foto[]', file);
    }

    const submitBtn = document.getElementById('uploadSubmitBtn');
    if (submitBtn) submitBtn.disabled = true;

    fetch('simpan_foto.php', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        if (submitBtn) submitBtn.disabled = false;
        if (data.success) {
          showUploadToast();
          setTimeout(() => location.reload(), 900);
        } else {
          alert(data.message || 'Gagal upload foto.');
        }
      })
      .catch(() => {
        if (submitBtn) submitBtn.disabled = false;
        alert('Terjadi kesalahan koneksi saat upload.');
      });
  });
}

function showUploadToast(){
  const t = document.getElementById('uploadToast');
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2600);
}

function renderGaleri(){
  const grid = document.getElementById('galeriGrid');
  if(!grid) return;
  const allPhotos = [];
  
  // Foto dari kegiatan harian
  entries.forEach(e => {
    (e.fotoObjects || []).forEach(f => {
      allPhotos.push({
        id: f.id,
        type: 'kegiatan',
        src: f.path_foto,
        caption: f.keterangan || e.title
      });
    });
  });

  // Foto dari program kerja
  (prokjaPhotos || []).forEach(p => {
    if (p.path_foto) {
      allPhotos.push({
        id: p.id,
        type: 'prokja',
        src: p.path_foto,
        caption: p.judul || p.deskripsi || p.program_judul || 'Dokumentasi Program Kerja'
      });
    }
  });

  if(allPhotos.length === 0){
    grid.innerHTML = `<p class="galeri-empty text-center w-100 py-4 text-muted"><i class="bi bi-images display-5 opacity-50 d-block mb-2"></i>Belum ada foto. Upload dokumentasi lewat form di atas untuk mengisi galeri ini.</p>`;
    return;
  }

  grid.innerHTML = allPhotos.map(p => `
    <div class="g-item position-relative">
      <div class="g-actions-bar d-print-none">
        <button class="g-btn-action edit" title="Edit Deskripsi Foto" onclick="bukaEditFotoGaleri(event, '${p.type}', ${p.id}, '${(p.caption || '').replace(/'/g, "\\'")}', '${p.src}')">
          <i class="bi bi-pencil-fill"></i>
        </button>
        <button class="g-btn-action delete" title="Hapus Foto" onclick="hapusFotoGaleri(event, '${p.type}', ${p.id}, '${p.src}')">
          <i class="bi bi-trash-fill"></i>
        </button>
      </div>
      <div onclick="openLightboxSrc('${formatPhotoUrl(p.src)}','${(p.caption || '').replace(/'/g,"\\'")}')" style="width:100%; height:100%;">
        <img src="${formatPhotoUrl(p.src)}" alt="${(p.caption || '').replace(/"/g, '&quot;')}">
        <div class="g-overlay"><div class="g-cap">${p.caption}</div></div>
      </div>
    </div>
  `).join('');
}

function bukaEditFotoGaleri(event, type, id, caption, src) {
  event.stopPropagation();
  document.getElementById('editFotoGaleriId').value = id;
  document.getElementById('editFotoGaleriType').value = type;
  document.getElementById('editFotoGaleriCaption').value = caption;
  document.getElementById('editFotoGaleriPreview').src = src;

  const modalEl = document.getElementById('editFotoGaleriModal');
  const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
  modal.show();
}

function hapusFotoGaleri(event, type, id, src) {
  event.stopPropagation();
  if (!confirm('Apakah Anda yakin ingin menghapus foto dokumentasi ini dari galeri?')) {
    return;
  }

  const formData = new FormData();
  formData.append('type', type);
  formData.append('id', id);

  fetch('proses_foto_galeri.php?action=delete', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        showUploadToast();
        setTimeout(() => location.reload(), 600);
      } else {
        alert(res.message || 'Gagal menghapus foto.');
      }
    })
    .catch(() => alert('Terjadi kesalahan jaringan.'));
}

// Bind editFotoGaleriForm submit
document.getElementById('editFotoGaleriForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const submitBtn = document.getElementById('editFotoGaleriSubmitBtn');
  submitBtn.disabled = true;

  fetch('proses_foto_galeri.php?action=update_caption', {
    method: 'POST',
    body: new FormData(this)
  })
    .then(res => res.json())
    .then(res => {
      submitBtn.disabled = false;
      if (res.success) {
        const modalEl = document.getElementById('editFotoGaleriModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        setTimeout(() => location.reload(), 500);
      } else {
        alert(res.message || 'Gagal memperbarui deskripsi foto.');
      }
    })
    .catch(() => {
      submitBtn.disabled = false;
      alert('Terjadi kesalahan jaringan.');
    });
});

const lightbox = document.getElementById('lightbox');
const lightboxImg = document.getElementById('lightboxImg');
const lightboxCaption = document.getElementById('lightboxCaption');

function openLightboxSrc(src, caption){
  lightboxImg.src = src;
  lightboxCaption.textContent = caption;
  lightbox.classList.add('open');
}
document.getElementById('lightboxClose').addEventListener('click', () => lightbox.classList.remove('open'));
lightbox.addEventListener('click', e => { if(e.target === lightbox) lightbox.classList.remove('open'); });
document.addEventListener('keydown', e => { if(e.key === 'Escape') lightbox.classList.remove('open'); });


// === KELOLA PROGRAM KERJA JS ===
let allPrograms = [];

function loadProgramKerja() {
  fetch('proses_program_kerja.php?action=list')
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        allPrograms = res.data;
        renderProgramKerjaTable();
      } else {
        alert(res.message || 'Gagal memuat program kerja.');
      }
    })
    .catch(() => alert('Gagal memuat program kerja. Terjadi kesalahan koneksi.'));
}

function renderProgramKerjaTable() {
  const tbody = document.getElementById('prokjaTableBody');
  if (allPrograms.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Belum ada program kerja yang dicatat.</td></tr>';
    return;
  }

  tbody.innerHTML = allPrograms.map(p => {
    // Cari cover foto
    const coverFoto = p.fotos.find(f => parseInt(f.is_cover) === 1);
    const coverImgSrc = coverFoto ? coverFoto.path_foto : 'img/20171130105645.jpg'; // fallback
    
    return `
      <tr>
        <td><span class="badge bg-secondary text-uppercase">${p.bidang_kode}</span></td>
        <td><strong>${p.judul}</strong><br><small class="text-muted">${p.deskripsi.substring(0, 70)}${p.deskripsi.length > 70 ? '...' : ''}</small></td>
        <td>${p.periode}</td>
        <td>
          <span class="badge ${p.status === 'terbit' ? 'bg-success' : 'bg-warning'}">
            ${p.status === 'terbit' ? 'Terbit' : 'Draft'}
          </span>
        </td>
        <td>
          <img src="${coverImgSrc}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
        </td>
        <td class="text-end">
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-primary" onclick="editProgramKerja(${p.id})" title="Edit Detail"><i class="bi bi-pencil-square"></i></button>
            <button class="btn btn-outline-info" onclick="manageProgramPhotos(${p.id})" title="Kelola Foto"><i class="bi bi-camera"></i></button>
            <button class="btn btn-outline-danger" onclick="deleteProgramKerja(${p.id})" title="Hapus"><i class="bi bi-trash"></i></button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

function resetProkjaForm() {
  document.getElementById('prokjaForm').reset();
  document.getElementById('prokjaId').value = '';
  document.getElementById('prokjaLink').value = 'https://kkn12.ct.ws/?i=1';
  document.getElementById('prokjaAlert').classList.add('d-none');
}

function onProkjaFormTabClick() {
  if (document.getElementById('prokjaId').value === '') {
    resetProkjaForm();
  }
}

function editProgramKerja(id) {
  const p = allPrograms.find(item => item.id === id);
  if (!p) return;

  document.getElementById('prokjaId').value = p.id;
  document.getElementById('prokjaBidangId').value = p.bidang_id;
  document.getElementById('prokjaJudul').value = p.judul;
  document.getElementById('prokjaPeriode').value = p.periode;
  document.getElementById('prokjaLink').value = p.link || 'https://kkn12.ct.ws/?i=1';
  document.getElementById('prokjaDeskripsi').value = p.deskripsi;
  document.getElementById('prokjaStatus').value = p.status;

  // Switch to form tab
  document.getElementById('form-tab').click();
}

document.getElementById('prokjaForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const alertBox = document.getElementById('prokjaAlert');
  const submitBtn = document.getElementById('prokjaSubmitBtn');
  
  alertBox.classList.add('d-none');
  submitBtn.disabled = true;

  const formData = new FormData(this);
  formData.append('action', 'save');

  fetch('proses_program_kerja.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      submitBtn.disabled = false;
      if (data.success) {
        loadProgramKerja();
        document.getElementById('list-tab').click();
      } else {
        alertBox.textContent = data.message || 'Gagal menyimpan program kerja.';
        alertBox.classList.remove('d-none');
      }
    })
    .catch(() => {
      submitBtn.disabled = false;
      alertBox.textContent = 'Terjadi kesalahan koneksi.';
      alertBox.classList.remove('d-none');
    });
});

function deleteProgramKerja(id) {
  if (!confirm('Apakah Anda yakin ingin menghapus program kerja ini beserta seluruh dokumentasi fotonya?')) {
    return;
  }

  const formData = new FormData();
  formData.append('action', 'delete');
  formData.append('id', id);

  fetch('proses_program_kerja.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        loadProgramKerja();
      } else {
        alert(data.message || 'Gagal menghapus program kerja.');
      }
    })
    .catch(() => alert('Terjadi kesalahan koneksi saat menghapus.'));
}

function manageProgramPhotos(id) {
  const p = allPrograms.find(item => item.id === id);
  if (!p) return;

  document.getElementById('photoProgramId').value = p.id;
  document.getElementById('photoProgramTitle').innerHTML = `Program Kerja: <span class="text-primary">${p.judul}</span>`;
  
  // Show the nav tab for photos
  document.getElementById('tab-photo-nav').classList.remove('d-none');
  document.getElementById('photo-tab').click();
  
  renderProgramPhotos(p);
}

function renderProgramPhotos(program) {
  const grid = document.getElementById('prokjaPhotoGrid');
  
  if (!program.fotos || program.fotos.length === 0) {
    grid.innerHTML = '<div class="col-12 text-center text-muted py-3">Belum ada foto yang diunggah untuk program kerja ini.</div>';
    return;
  }

  grid.innerHTML = program.fotos.map(f => {
    const isCover = parseInt(f.is_cover) === 1;
    return `
      <div class="col-md-4 col-sm-6">
        <div class="card h-100 shadow-sm position-relative overflow-hidden">
          <img src="${f.path_foto}" class="card-img-top" style="height: 150px; object-fit: cover;">
          ${isCover ? '<span class="position-absolute top-0 start-0 m-2 badge bg-success"><i class="bi bi-star-fill me-1"></i>Cover Sampul</span>' : ''}
          <div class="card-body p-2">
            <h6 class="fw-bold mb-1 small text-truncate" title="${f.judul || '-'}">${f.judul || '(Tanpa Judul)'}</h6>
            <p class="text-muted mb-2 small" style="font-size: 0.75rem; height: 32px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;" title="${f.deskripsi || '-'}">${f.deskripsi || '(Tanpa Deskripsi)'}</p>
            <div class="d-flex gap-1 justify-content-between">
              ${!isCover ? `<button class="btn btn-xxs btn-outline-success py-1 px-2" style="font-size:0.7rem;" onclick="setProkjaCover(${f.id}, ${program.id})"><i class="bi bi-star"></i> Set Cover</button>` : '<span></span>'}
              <button class="btn btn-xxs btn-danger py-1 px-2" style="font-size:0.7rem;" onclick="deleteProkjaPhoto(${f.id}, ${program.id})"><i class="bi bi-trash"></i> Hapus</button>
            </div>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

function setProkjaCover(photoId, programId) {
  const formData = new FormData();
  formData.append('action', 'set_cover');
  formData.append('foto_id', photoId);
  formData.append('program_kerja_id', programId);

  fetch('proses_program_kerja.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        // Reload list program kerja to update local cache
        fetch('proses_program_kerja.php?action=list')
          .then(res => res.json())
          .then(res => {
            if (res.success) {
              allPrograms = res.data;
              renderProgramKerjaTable();
              const p = allPrograms.find(item => item.id === programId);
              if (p) renderProgramPhotos(p);
            }
          });
      } else {
        alert(data.message || 'Gagal mengubah cover.');
      }
    })
    .catch(() => alert('Koneksi bermasalah.'));
}

function deleteProkjaPhoto(photoId, programId) {
  if (!confirm('Apakah Anda yakin ingin menghapus foto ini?')) return;

  const formData = new FormData();
  formData.append('action', 'delete_foto');
  formData.append('foto_id', photoId);

  fetch('proses_program_kerja.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        // Reload
        fetch('proses_program_kerja.php?action=list')
          .then(res => res.json())
          .then(res => {
            if (res.success) {
              allPrograms = res.data;
              renderProgramKerjaTable();
              const p = allPrograms.find(item => item.id === programId);
              if (p) renderProgramPhotos(p);
            }
          });
      } else {
        alert(data.message || 'Gagal menghapus foto.');
      }
    })
    .catch(() => alert('Koneksi bermasalah.'));
}

document.getElementById('prokjaPhotoForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const submitBtn = document.getElementById('photoSubmitBtn');
  const programId = parseInt(document.getElementById('photoProgramId').value);
  
  if (!programId || isNaN(programId)) {
    alert('Pilih program kerja terlebih dahulu.');
    return;
  }

  submitBtn.disabled = true;

  const formData = new FormData(this);
  formData.append('action', 'upload_foto');

  fetch('proses_program_kerja.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      submitBtn.disabled = false;
      if (data.success) {
        document.getElementById('prokjaPhotoForm').reset();
        document.getElementById('photoProgramId').value = programId; // restore program ID
        
        // Reload
        fetch('proses_program_kerja.php?action=list')
          .then(res => res.json())
          .then(res => {
            if (res.success) {
              allPrograms = res.data;
              renderProgramKerjaTable();
              const p = allPrograms.find(item => item.id === programId);
              if (p) renderProgramPhotos(p);
            }
          });
      } else {
        alert(data.message || 'Gagal mengunggah foto.');
      }
    })
    .catch(() => {
      submitBtn.disabled = false;
      alert('Koneksi bermasalah.');
    });
});

// Hide Photo Tab when switching to lists or form manually
document.getElementById('list-tab').addEventListener('click', () => {
  document.getElementById('tab-photo-nav').classList.add('d-none');
});
document.getElementById('form-tab').addEventListener('click', () => {
  document.getElementById('tab-photo-nav').classList.add('d-none');
});

function bukaKelolaJadwalAcaraModal() {
  resetJadwalAcaraForm();
  loadJadwalAcaraList();
  const modal = new bootstrap.Modal(document.getElementById('kelolaJadwalAcaraModal'));
  modal.show();
}

function resetJadwalAcaraForm() {
  const form = document.getElementById('jadwalAcaraForm');
  form.reset();
  document.getElementById('jadwalAcaraId').value = '';
  document.getElementById('jadwalAcaraFormLabel').innerHTML = '<i class="bi bi-plus-circle me-1 text-primary"></i>Tambah Jadwal Acara Baru';
  document.getElementById('jadwalAcaraSubmitBtn').textContent = 'Simpan';
}

function loadJadwalAcaraList() {
  const tbody = document.getElementById('jadwalAcaraTableBody');
  tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3"><span class="spinner-border spinner-border-sm me-2"></span>Memuat data...</td></tr>';

  fetch('proses_jadwal_acara.php?action=list')
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        if (res.data.length === 0) {
          tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Belum ada jadwal acara KKN yang dibuat.</td></tr>';
          return;
        }

        const namaBulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        tbody.innerHTML = res.data.map(item => {
          const tglParts = item.tanggal.split('-');
          const tglFormatted = `${item.hari}, ${parseInt(tglParts[2])} ${namaBulan[parseInt(tglParts[1]) - 1]} ${tglParts[0]}`;
          return `
            <tr>
              <td><strong>${tglFormatted}</strong></td>
              <td><strong>${item.waktu}</strong></td>
              <td>${item.agenda}</td>
              <td><span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-geo-alt me-1 text-danger"></i>${item.pic || '-'}</span></td>
              <td class="text-end">
                <button class="btn btn-sm btn-link text-primary p-1 me-1" onclick="editJadwalAcaraItem(${item.id}, '${item.tanggal}', '${item.waktu.replace(/'/g, "\\'")}', '${item.agenda.replace(/'/g, "\\'")}', '${(item.pic || '').replace(/'/g, "\\'")}', '${(item.keterangan || '').replace(/'/g, "\\'")}')" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                <button class="btn btn-sm btn-link text-danger p-1" onclick="hapusJadwalAcaraItem(${item.id})" title="Hapus"><i class="bi bi-trash-fill"></i></button>
              </td>
            </tr>
          `;
        }).join('');
      } else {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-3">${res.message || 'Gagal memuat data.'}</td></tr>`;
      }
    })
    .catch(() => {
      tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Terjadi kesalahan jaringan.</td></tr>';
    });
}

function editJadwalAcaraItem(id, tanggal, waktu, agenda, pic, keterangan) {
  document.getElementById('jadwalAcaraId').value = id;
  document.getElementById('jadwalAcaraTanggal').value = tanggal;
  document.getElementById('jadwalAcaraWaktu').value = waktu;
  document.getElementById('jadwalAcaraAgenda').value = agenda;
  document.getElementById('jadwalAcaraPic').value = pic;
  document.getElementById('jadwalAcaraKeterangan').value = keterangan;

  document.getElementById('jadwalAcaraFormLabel').innerHTML = '<i class="bi bi-pencil-fill me-1 text-primary"></i>Edit Jadwal Acara';
  document.getElementById('jadwalAcaraSubmitBtn').textContent = 'Update';
}

function hapusJadwalAcaraItem(id) {
  if (!confirm('Apakah Anda yakin ingin menghapus jadwal acara ini?')) {
    return;
  }

  const formData = new FormData();
  formData.append('id', id);

  fetch('proses_jadwal_acara.php?action=delete', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        location.reload();
      } else {
        alert(res.message || 'Gagal menghapus jadwal.');
      }
    })
    .catch(() => {
      alert('Terjadi kesalahan jaringan.');
    });
}

// Bind jadwalAcaraForm submit
document.getElementById('jadwalAcaraForm').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const submitBtn = document.getElementById('jadwalAcaraSubmitBtn');
  submitBtn.disabled = true;
  submitBtn.textContent = 'Menyimpan...';

  fetch('proses_jadwal_acara.php?action=save', {
    method: 'POST',
    body: new FormData(this)
  })
    .then(res => res.json())
    .then(res => {
      submitBtn.disabled = false;
      if (res.success) {
        resetJadwalAcaraForm();
        loadJadwalAcaraList();
      } else {
        alert(res.message || 'Gagal menyimpan jadwal.');
        submitBtn.textContent = 'Simpan';
      }
    })
    .catch(() => {
      alert('Terjadi kesalahan jaringan.');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Simpan';
    });
});

// Refresh page when schedule modal is closed so that jadwalAcaraEntries update
document.getElementById('kelolaJadwalAcaraModal').addEventListener('hidden.bs.modal', function () {
  location.reload();
});

function onBidangSelectChange(val) {
    const wrap = document.getElementById('customBidangWrapper');
    const customInput = document.getElementById('prokjaBidangCustom');
    if (val === 'custom') {
        if (wrap) wrap.classList.remove('d-none');
        if (customInput) {
            customInput.required = true;
            customInput.focus();
        }
    } else {
        if (wrap) wrap.classList.add('d-none');
        if (customInput) {
            customInput.required = false;
            customInput.value = '';
        }
    }
}

function toggleCustomBidangInput() {
    const sel = document.getElementById('prokjaBidangId');
    if (sel) {
        sel.value = 'custom';
        onBidangSelectChange('custom');
    }
}

// === KELOLA BIDANG KKN JS ===
function bukaKelolaBidang() {
  resetBidangForm();
  loadBidangList();
}

function resetBidangForm() {
  const form = document.getElementById('bidangForm');
  if (form) form.reset();
  document.getElementById('bidangId').value = '';
  document.getElementById('bidangAlert').classList.add('d-none');
  document.getElementById('bidangFormLabel').innerHTML = '<i class="bi bi-plus-circle me-1 text-primary"></i>Tambah Bidang Baru';
  document.getElementById('bidangSubmitBtn').textContent = 'Simpan';
}

function loadBidangList() {
  const tbody = document.getElementById('bidangTableBody');
  tbody.innerHTML = '<tr><td colspan="4" class="text-center py-3"><span class="spinner-border spinner-border-sm me-2"></span>Memuat bidang...</td></tr>';

  fetch('proses_program_kerja.php?action=list_bidang')
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        if (res.data.length === 0) {
          tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Belum ada bidang KKN.</td></tr>';
          return;
        }

        tbody.innerHTML = res.data.map(item => {
          return `
            <tr>
              <td><strong>${item.id}</strong></td>
              <td><span class="badge bg-secondary text-uppercase">${item.kode}</span></td>
              <td><strong>${item.nama}</strong></td>
              <td class="text-end">
                <button class="btn btn-sm btn-link text-primary p-1 me-1" onclick="editBidangItem(${item.id}, '${item.nama.replace(/'/g, "\\'")}')" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                <button class="btn btn-sm btn-link text-danger p-1" onclick="deleteBidangItem(${item.id})" title="Hapus"><i class="bi bi-trash-fill"></i></button>
              </td>
            </tr>
          `;
        }).join('');
      } else {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-3">${res.message || 'Gagal memuat data.'}</td></tr>`;
      }
    })
    .catch(() => {
      tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-3">Terjadi kesalahan jaringan.</td></tr>';
    });
}

function editBidangItem(id, nama) {
  document.getElementById('bidangId').value = id;
  document.getElementById('bidangNama').value = nama;
  document.getElementById('bidangFormLabel').innerHTML = '<i class="bi bi-pencil-fill me-1 text-primary"></i>Edit Bidang KKN';
  document.getElementById('bidangSubmitBtn').textContent = 'Update';
}

function deleteBidangItem(id) {
  if (!confirm('Apakah Anda yakin ingin menghapus bidang ini?')) {
    return;
  }

  const formData = new FormData();
  formData.append('id', id);

  fetch('proses_program_kerja.php?action=delete_bidang', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        loadBidangList();
      } else {
        alert(res.message || 'Gagal menghapus bidang.');
      }
    })
    .catch(() => {
      alert('Terjadi kesalahan jaringan.');
    });
}

// Bind bidangForm submit
document.getElementById('bidangForm').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const submitBtn = document.getElementById('bidangSubmitBtn');
  const alertBox = document.getElementById('bidangAlert');
  alertBox.classList.add('d-none');
  
  submitBtn.disabled = true;
  submitBtn.textContent = 'Menyimpan...';

  fetch('proses_program_kerja.php?action=save_bidang', {
    method: 'POST',
    body: new FormData(this)
  })
    .then(res => res.json())
    .then(res => {
      submitBtn.disabled = false;
      if (res.success) {
        resetBidangForm();
        loadBidangList();
      } else {
        alertBox.textContent = res.message || 'Gagal menyimpan bidang.';
        alertBox.classList.remove('d-none');
        submitBtn.textContent = 'Simpan';
      }
    })
    .catch(() => {
      alert('Terjadi kesalahan jaringan.');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Simpan';
    });
});

// Refresh page when program modal is closed so that bidang options populate correctly
document.getElementById('programKerjaModal').addEventListener('hidden.bs.modal', function () {
  location.reload();
});

// === JADWAL PIKET POSKO KKN JS ===
function generateJadwalPiketPosterHtml(piketData, isPrint = false) {
  let dateText = "20 Juli — 30 Agustus 2026";
  let titleText = "Jadwal Piket Posko KKN Kelompok 12";

  let cardsHtml = "";
  if (piketData.length === 0) {
    cardsHtml = `
      <div class="text-center py-5 text-muted">
        <i class="bi bi-people display-4 text-muted opacity-50 mb-3 d-block"></i>
        <p class="mb-2 fw-semibold">Belum ada jadwal piket yang dibuat.</p>
        ${!isPrint ? `<button class="btn btn-sm btn-primary rounded-pill px-4 py-2 mt-2" onclick="bukaKelolaJadwalPiketModal()">
          <i class="bi bi-plus-lg me-1"></i> Tambah Jadwal Piket
        </button>` : ''}
      </div>`;
  } else {
    if (!isPrint) {
      // Web Grid Card Layout
      cardsHtml = `<div class="row g-3">` + piketData.map(item => {
        const petugasList = item.petugas ? item.petugas.split(',').map(p => p.trim()) : [];
        const petugasBadges = petugasList.map(name => `<span class="badge bg-light text-dark border px-2.5 py-1.5 rounded-pill fw-medium me-1 mb-1"><i class="bi bi-person-fill text-primary me-1"></i>${name}</span>`).join('');

        return `
          <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 p-3 bg-white hover-shadow transition" style="border: 1px solid rgba(0,0,0,0.06) !important;">
              <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                <span class="badge bg-primary text-white fw-bold px-3 py-1.5 rounded-pill fs-6">${item.hari}</span>
                <span class="badge bg-light text-muted border px-2.5 py-1 rounded-pill font-monospace small"><i class="bi bi-clock me-1"></i>${item.shift || '08:00 - 17:00'}</span>
              </div>
              <div class="mb-2">
                <div class="fw-bold text-dark mb-1" style="font-size: 0.95rem;"><i class="bi bi-clipboard-check text-primary me-1.5"></i>${item.tugas}</div>
                ${item.keterangan ? `<div class="text-muted small mb-2"><i class="bi bi-info-circle me-1"></i>${item.keterangan}</div>` : ''}
              </div>
              <div class="mb-3">
                <div class="text-muted small mb-1.5 fw-semibold" style="font-size: 0.75rem; text-transform: uppercase;">Petugas Piket:</div>
                <div class="d-flex flex-wrap">${petugasBadges}</div>
              </div>
              <div class="d-flex justify-content-end gap-1 mt-auto pt-2 border-top d-print-none">
                <button class="btn btn-sm btn-light border p-1 px-2.5 rounded-pill small" title="Edit" onclick="editJadwalPiketDariTimeline(${item.id}, '${(item.hari||'').replace(/'/g,"\\'")}', '${(item.shift||'').replace(/'/g,"\\'")}', '${(item.tugas||'').replace(/'/g,"\\'")}', '${(item.petugas||'').replace(/'/g,"\\'")}', '${(item.keterangan||'').replace(/'/g,"\\'")}')">
                  <i class="bi bi-pencil text-primary me-1"></i>Edit
                </button>
                <button class="btn btn-sm btn-light border p-1 px-2.5 rounded-pill small" title="Hapus" onclick="hapusJadwalPiketItem(${item.id})">
                  <i class="bi bi-trash text-danger me-1"></i>Hapus
                </button>
              </div>
            </div>
          </div>`;
      }).join('') + `</div>`;
    } else {
      // PDF Table Layout for Piket
      let rowsStr = piketData.map(item => `
        <tr>
          <td style="border: 1px solid #000; padding: 7px 8px; font-weight: bold; text-align: center; font-size: 9pt; background-color: #f8fafc;">${item.hari}</td>
          <td style="border: 1px solid #000; padding: 7px 8px; text-align: center; font-weight: 600; font-size: 9pt;">${item.shift || '-'}</td>
          <td style="border: 1px solid #000; padding: 7px 8px; font-weight: 600; font-size: 9pt;">${item.tugas}</td>
          <td style="border: 1px solid #000; padding: 7px 8px; font-size: 9pt;">${item.petugas}</td>
          <td style="border: 1px solid #000; padding: 7px 8px; font-size: 9pt;">${item.keterangan || '-'}</td>
        </tr>
      `).join('');

      return `
        <div class="print-only-pdf-container">
          <!-- Print Only Header -->
          <div class="print-only-header mb-2" style="border-bottom: 3px double #000; padding-bottom: 8px; font-family: 'Times New Roman', Times, serif; color: #000;">
              <table style="width: 100%; border: none !important; border-collapse: collapse; margin: 0; padding: 0;">
                  <tr>
                      <td style="width: 80px; text-align: left; vertical-align: middle; border: none !important; padding: 0 !important;">
                          <img src="${logoUmpSrc}" alt="Logo UMP" style="height: 70px; width: auto; display: block; object-fit: contain;">
                      </td>
                      <td style="text-align: center; vertical-align: middle; border: none !important; padding: 0 10px !important;">
                          <h2 style="font-size: 13.5pt; font-weight: bold; letter-spacing: 0.5px; text-transform: uppercase; margin: 0 0 3px; font-family: 'Times New Roman', Times, serif; line-height: 1.2;">UNIVERSITAS MUHAMMADIYAH PONTIANAK</h2>
                          <h3 style="font-size: 11.5pt; font-weight: bold; text-transform: uppercase; margin: 0 0 3px; font-family: 'Times New Roman', Times, serif; line-height: 1.2;">KULIAH KERJA NYATA (KKN) KELOMPOK 12</h3>
                          <h4 style="font-size: 10.5pt; font-weight: bold; text-transform: uppercase; color: #000; margin: 0 0 4px; font-family: 'Times New Roman', Times, serif; line-height: 1.2;">JADWAL PIKET POSKO KKN KELOMPOK 12</h4>
                          <p style="font-size: 9.5pt; margin: 0 0 2px; font-family: 'Times New Roman', Times, serif; line-height: 1.2;">Kelurahan Pal Lima &middot; Kecamatan Pontianak Barat &middot; Kota Pontianak</p>
                          <p style="font-size: 9pt; font-style: italic; color: #334155; margin: 0; font-family: 'Times New Roman', Times, serif; line-height: 1.2;">Periode Pelaksanaan: ${dateText}</p>
                      </td>
                      <td style="width: 80px; text-align: right; vertical-align: middle; border: none !important; padding: 0 !important;">
                          <img src="${logoKknSrc}" alt="Logo KKN 12" style="height: 65px; width: auto; display: block; margin-left: auto; object-fit: contain;">
                      </td>
                  </tr>
              </table>
          </div>

          <!-- Report Table -->
          <div class="mt-1">
            <table class="formal-report-table" style="width: 100%; border-collapse: collapse; margin-top: 8px; font-family: 'Times New Roman', Times, serif; table-layout: fixed;">
              <thead>
                <tr style="background-color: #f8fafc;">
                  <th style="width: 14%; border: 1px solid #000; padding: 7px 8px; text-align: center; font-weight: bold; font-size: 9.5pt;">Hari</th>
                  <th style="width: 16%; border: 1px solid #000; padding: 7px 8px; text-align: center; font-weight: bold; font-size: 9.5pt;">Shift / Jam</th>
                  <th style="width: 25%; border: 1px solid #000; padding: 7px 8px; text-align: left; font-weight: bold; font-size: 9.5pt;">Tugas Piket</th>
                  <th style="width: 30%; border: 1px solid #000; padding: 7px 8px; text-align: left; font-weight: bold; font-size: 9.5pt;">Petugas Piket</th>
                  <th style="width: 15%; border: 1px solid #000; padding: 7px 8px; text-align: left; font-weight: bold; font-size: 9.5pt;">Catatan</th>
                </tr>
              </thead>
              <tbody>
                ${rowsStr}
              </tbody>
            </table>
          </div>

          <!-- Signature Block -->
          <div class="print-only-signatures mt-4 pt-3" style="font-family: 'Times New Roman', Times, serif; color: #000; page-break-inside: avoid;">
            <div class="d-flex justify-content-between text-center" style="margin-top: 30px;">
              <div style="width: 40%;">
                <p class="mb-1">Mengetahui,</p>
                <p class="fw-bold mb-5">Dosen Pembimbing Lapangan (DPL)</p>
                <p class="fw-bold mb-0 text-decoration-underline">(...................................................)</p>
                <p class="small text-muted" style="font-size: 8.5pt;">NIDN. .....................................</p>
              </div>
              <div style="width: 40%;">
                <p class="mb-1">Pontianak, 30 Agustus 2026</p>
                <p class="fw-bold mb-5">Ketua KKN Kelompok 12</p>
                <p class="fw-bold mb-0 text-decoration-underline">Rizki Tri Saputra</p>
                <p class="small text-muted" style="font-size: 8.5pt;">NIM. .....................................</p>
              </div>
            </div>
          </div>
        </div>
      `;
    }
  }

  return `
    <div class="rundown-card-wrapper">
      <!-- Top Branding Header with Dual Logos -->
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
        <div class="d-flex align-items-center gap-3">
          <img src="${logoUmpSrc}" alt="Logo UMP" style="height: 54px; width: auto; object-fit: contain;">
          <div>
            <div class="fw-bold text-dark lh-sm" style="font-size: 0.95rem;">Universitas Muhammadiyah Pontianak</div>
            <div class="text-muted small">Kuliah Kerja Nyata (KKN) &middot; Kelurahan Pal Lima</div>
          </div>
        </div>

        <div class="d-flex align-items-center gap-3 ms-auto">
          <div class="d-flex align-items-center gap-2">
            <img src="${logoKknSrc}" alt="Logo KKN 12" style="height: 48px; width: auto; object-fit: contain;">
            <div class="d-none d-sm-block text-end">
              <div class="fw-bold text-primary lh-sm" style="font-size: 0.9rem;">Kelompok 12</div>
              <div class="text-muted" style="font-size: 0.75rem;">KKN 2026</div>
            </div>
          </div>

          ${!isPrint ? `
          <div class="d-flex gap-2 d-print-none ms-2">
            <button class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1.5 fw-semibold" onclick="bukaKelolaJadwalPiketModal()">
              <i class="bi bi-gear-fill me-1"></i> Kelola Piket
            </button>
            <button class="btn btn-primary btn-sm rounded-pill px-3 py-1.5 fw-semibold" onclick="siapkanPiketPDF()">
              <i class="bi bi-download me-1"></i> Unduh PDF Piket
            </button>
          </div>` : ''}
        </div>
      </div>

      <!-- Piket Sub-Header -->
      <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="badge bg-primary text-white fw-bold px-3 py-1.5 rounded-pill text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">JADWAL PIKET POSKO</span>
          <span class="badge bg-light text-dark border px-3 py-1.5 rounded-pill small fw-semibold">${dateText}</span>
        </div>
        <h3 class="fw-bold text-dark mb-0 fs-3">${titleText}</h3>
      </div>

      <!-- Content Section -->
      ${cardsHtml}
    </div>
  `;
}

function renderJadwalPiketTimeline() {
  const container = document.getElementById('timeline-jadwal-piket');
  if (!container) return;
  container.innerHTML = generateJadwalPiketPosterHtml(jadwalPiketEntries, false);
}

function bukaKelolaJadwalPiketModal() {
  resetJadwalPiketForm();
  loadJadwalPiketList();
  const modal = new bootstrap.Modal(document.getElementById('kelolaJadwalPiketModal'));
  modal.show();
}

function resetJadwalPiketForm() {
  const form = document.getElementById('jadwalPiketForm');
  if (form) form.reset();
  document.getElementById('jadwalPiketId').value = '';
  document.querySelectorAll('.piket-petugas-cb').forEach(cb => cb.checked = false);
  document.getElementById('jadwalPiketFormLabel').innerHTML = '<i class="bi bi-plus-circle me-1 text-primary"></i>Input / Edit Jadwal Piket';
  document.getElementById('jadwalPiketSubmitBtn').innerHTML = '<i class="bi bi-save me-1"></i> Simpan Jadwal Piket';
}

function loadJadwalPiketList() {
  const tbody = document.getElementById('jadwalPiketTableBody');
  if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3"><span class="spinner-border spinner-border-sm me-2"></span>Memuat data piket...</td></tr>';

  fetch('proses_jadwal_piket.php?action=list')
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        if (res.data.length === 0) {
          tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Belum ada jadwal piket yang dibuat.</td></tr>';
          return;
        }

        tbody.innerHTML = res.data.map(item => {
          return `
            <tr>
              <td><span class="badge bg-primary text-white fw-bold px-2.5 py-1 rounded-pill">${item.hari}</span></td>
              <td><strong>${item.shift || '-'}</strong></td>
              <td><strong>${item.tugas}</strong></td>
              <td>${item.petugas}</td>
              <td class="text-end">
                <button class="btn btn-sm btn-link text-primary p-1 me-1" onclick="editJadwalPiketItem(${item.id}, '${item.hari.replace(/'/g, "\\'")}', '${(item.shift||'').replace(/'/g, "\\'")}', '${(item.tugas||'').replace(/'/g, "\\'")}', '${(item.petugas||'').replace(/'/g, "\\'")}', '${(item.keterangan||'').replace(/'/g, "\\'")}')" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                <button class="btn btn-sm btn-link text-danger p-1" onclick="hapusJadwalPiketItem(${item.id})" title="Hapus"><i class="bi bi-trash-fill"></i></button>
              </td>
            </tr>
          `;
        }).join('');
      } else {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-3">${res.message || 'Gagal memuat data.'}</td></tr>`;
      }
    })
    .catch(() => {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-3">Terjadi kesalahan jaringan.</td></tr>';
    });
}

function editJadwalPiketDariTimeline(id, hari, shift, tugas, petugas, keterangan) {
  const modalEl = document.getElementById('kelolaJadwalPiketModal');
  const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
  modal.show();
  editJadwalPiketItem(id, hari, shift, tugas, petugas, keterangan);
}

function editJadwalPiketItem(id, hari, shift, tugas, petugas, keterangan) {
  document.getElementById('jadwalPiketId').value = id;
  document.getElementById('jadwalPiketHari').value = hari;
  document.getElementById('jadwalPiketShift').value = shift;
  document.getElementById('jadwalPiketTugas').value = tugas;
  document.getElementById('jadwalPiketKeterangan').value = keterangan;

  // Uncheck all first
  document.querySelectorAll('.piket-petugas-cb').forEach(cb => cb.checked = false);
  if (petugas) {
    const names = petugas.split(',').map(s => s.trim());
    document.querySelectorAll('.piket-petugas-cb').forEach(cb => {
      if (names.includes(cb.value)) cb.checked = true;
    });
  }

  document.getElementById('jadwalPiketFormLabel').innerHTML = '<i class="bi bi-pencil-fill me-1 text-primary"></i>Edit Jadwal Piket (' + hari + ')';
  document.getElementById('jadwalPiketSubmitBtn').innerHTML = '<i class="bi bi-pencil-square me-1"></i> Update Jadwal Piket';
}

function hapusJadwalPiketItem(id) {
  if (!confirm('Apakah Anda yakin ingin menghapus jadwal piket ini?')) return;

  const formData = new FormData();
  formData.append('id', id);

  fetch('proses_jadwal_piket.php?action=delete', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        loadJadwalPiketList();
        fetch('proses_jadwal_piket.php?action=list')
          .then(r => r.json())
          .then(r => {
            if (r.success) {
              jadwalPiketEntries.length = 0;
              jadwalPiketEntries.push(...r.data);
              renderJadwalPiketTimeline();
            }
          });
      } else {
        alert(res.message || 'Gagal menghapus jadwal piket.');
      }
    })
    .catch(() => alert('Terjadi kesalahan jaringan.'));
}

// Bind jadwalPiketForm submit
document.getElementById('jadwalPiketForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const submitBtn = document.getElementById('jadwalPiketSubmitBtn');
  submitBtn.disabled = true;

  fetch('proses_jadwal_piket.php?action=save', {
    method: 'POST',
    body: new FormData(this)
  })
    .then(res => res.json())
    .then(res => {
      submitBtn.disabled = false;
      if (res.success) {
        resetJadwalPiketForm();
        loadJadwalPiketList();
        fetch('proses_jadwal_piket.php?action=list')
          .then(r => r.json())
          .then(r => {
            if (r.success) {
              jadwalPiketEntries.length = 0;
              jadwalPiketEntries.push(...r.data);
              renderJadwalPiketTimeline();
            }
          });
      } else {
        alert(res.message || 'Gagal menyimpan jadwal piket.');
      }
    })
    .catch(() => {
      submitBtn.disabled = false;
      alert('Terjadi kesalahan jaringan.');
    });
});

function siapkanPiketPDF() {
  const pContainer = document.getElementById('printJadwalContainer');
  if (!pContainer) return;

  pContainer.innerHTML = generateJadwalPiketPosterHtml(jadwalPiketEntries, true);

  const btn = document.querySelector('button[onclick="siapkanPiketPDF()"]');
  const originalHtml = btn ? btn.innerHTML : '';
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Menyiapkan PDF...';
  }

  setTimeout(() => {
    window.scrollTo(0, 0);

    const RENDER_WIDTH = 750;

    const wrapper = document.createElement('div');
    wrapper.style.cssText = 'position:fixed;left:0;top:0;width:' + RENDER_WIDTH + 'px;z-index:-99999;opacity:0.01;pointer-events:none;background:#fff;';
    document.body.appendChild(wrapper);

    const tempDiv = document.createElement('div');
    tempDiv.className = 'html2pdf-container';
    tempDiv.style.cssText = 'background:#fff;color:#000;padding:12px 15px;font-family:"Times New Roman",Times,serif;width:' + RENDER_WIDTH + 'px;box-sizing:border-box;margin:0;';

    const contentClone = pContainer.cloneNode(true);
    contentClone.style.setProperty('display', 'block', 'important');

    tempDiv.appendChild(contentClone);
    wrapper.appendChild(tempDiv);

    void tempDiv.offsetHeight;

    const opt = {
      margin:       [10, 8, 12, 8],
      filename:     'Jadwal_Piket_KKN_Kelompok_12.pdf',
      image:        { type: 'jpeg', quality: 0.98 },
      html2canvas:  { 
        scale: 2, 
        useCORS: true, 
        allowTaint: true,
        logging: false, 
        width: RENDER_WIDTH, 
        windowWidth: RENDER_WIDTH, 
        x: 0,
        y: 0,
        scrollX: 0, 
        scrollY: 0 
      },
      jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
      pagebreak:    { mode: ['css', 'legacy'] }
    };

    html2pdf().set(opt).from(tempDiv).outputPdf('blob').then(blob => {
      const blobUrl = URL.createObjectURL(blob);
      window.open(blobUrl, '_blank');
      document.body.removeChild(wrapper);
      if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
    }).catch(err => {
      console.error(err);
      alert('Gagal membuat PDF. Silakan coba lagi.');
      if (wrapper.parentNode) document.body.removeChild(wrapper);
      if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
    });
  }, 300);
}

// === INFORMASI POSKO KKN JS ===
function renderInfoPoskoCards() {
  const container = document.getElementById('infoPoskoCardsContainer');
  const countBadge = document.getElementById('infoPoskoCountBadge');
  if (!container) return;

  if (countBadge) {
    countBadge.innerHTML = `<i class="bi bi-box-seam me-1 text-primary"></i>${infoPoskoEntries.length} Item Perlengkapan`;
  }

  if (infoPoskoEntries.length === 0) {
    container.innerHTML = `
      <div class="col-12 text-center py-5 text-muted">
        <i class="bi bi-box-seam display-4 opacity-50 mb-3 d-block"></i>
        <p class="mb-2 fw-semibold">Belum ada informasi perlengkapan posko.</p>
        <button class="btn btn-sm btn-primary rounded-pill px-4 py-2 mt-2" onclick="bukaKelolaInfoPoskoModal()">
          <i class="bi bi-plus-lg me-1"></i> Tambah Informasi Baru
        </button>
      </div>`;
    return;
  }

  const iconMap = {
    'kompor': 'bi-fire text-danger bg-danger-subtle',
    'gas': 'bi-fuel-pump text-warning bg-warning-subtle',
    'dandang': 'bi-box-seam text-primary bg-primary-subtle',
    'kuali': 'bi-egg-fried text-success bg-success-subtle',
    'gelas': 'bi-cup-hot text-secondary bg-secondary-subtle',
    'galon': 'bi-droplet-fill text-info bg-info-subtle',
    'pisau': 'bi-scissors text-dark bg-dark-subtle',
    'talenan': 'bi-square text-success bg-success-subtle',
    'sudip': 'bi-spoon text-primary bg-primary-subtle',
    'baskom': 'bi-circle text-warning bg-warning-subtle',
    'lauk': 'bi-inbox text-secondary bg-secondary-subtle',
    'nyedot': 'bi-arrow-down-up text-info bg-info-subtle'
  };

  container.innerHTML = infoPoskoEntries.map(item => {
    const lowerName = item.nama_barang.toLowerCase();
    let matchedIcon = 'bi-box-seam text-primary bg-primary-subtle';
    for (let key in iconMap) {
      if (lowerName.includes(key)) {
        matchedIcon = iconMap[key];
        break;
      }
    }

    const [iconClass, textClass, bgClass] = matchedIcon.split(' ');

    return `
      <div class="col-md-6 col-lg-4">
        <div class="p-3 border rounded-3 bg-white shadow-sm h-100 d-flex flex-column justify-content-between hover-shadow transition">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-circle ${bgClass} ${textClass} p-2.5 d-flex align-items-center justify-content-center" style="width:42px; height:42px;">
                <i class="bi ${iconClass} fs-5"></i>
              </div>
              <div>
                <div class="fw-bold text-dark">${item.nama_barang}</div>
                <small class="text-muted">${item.kategori || 'Perlengkapan Posko'}</small>
              </div>
            </div>
            <span class="badge bg-primary text-white rounded-pill px-3 py-1.5 fw-semibold">${item.penanggung_jawab}</span>
          </div>

          <div class="d-flex justify-content-end gap-1 mt-2 pt-2 border-top d-print-none">
            <button class="btn btn-sm btn-light border p-1 px-2.5 rounded-pill small" title="Edit" onclick="editInfoPoskoItem(${item.id}, '${item.nama_barang.replace(/'/g, "\\'")}', '${(item.kategori||'').replace(/'/g, "\\'")}', '${item.penanggung_jawab.replace(/'/g, "\\'")}', '${(item.keterangan||'').replace(/'/g, "\\'")}')">
              <i class="bi bi-pencil text-primary me-1"></i>Edit
            </button>
            <button class="btn btn-sm btn-light border p-1 px-2.5 rounded-pill small" title="Hapus" onclick="hapusInfoPoskoItem(${item.id})">
              <i class="bi bi-trash text-danger me-1"></i>Hapus
            </button>
          </div>
        </div>
      </div>`;
  }).join('');
}

function bukaKelolaInfoPoskoModal() {
  resetInfoPoskoForm();
  loadInfoPoskoList();
  const modal = new bootstrap.Modal(document.getElementById('kelolaInfoPoskoModal'));
  modal.show();
}

function resetInfoPoskoForm() {
  const form = document.getElementById('infoPoskoForm');
  if (form) form.reset();
  document.getElementById('infoPoskoId').value = '';
  document.getElementById('infoPoskoFormLabel').innerHTML = '<i class="bi bi-plus-circle me-1 text-primary"></i>Tambah / Edit Informasi Posko';
  document.getElementById('infoPoskoSubmitBtn').innerHTML = '<i class="bi bi-save me-1"></i> Simpan Data';
}

function loadInfoPoskoList() {
  const tbody = document.getElementById('infoPoskoTableBody');
  if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3"><span class="spinner-border spinner-border-sm me-2"></span>Memuat data posko...</td></tr>';

  fetch('proses_informasi_posko.php?action=list')
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        if (res.data.length === 0) {
          tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Belum ada data informasi posko.</td></tr>';
          return;
        }

        tbody.innerHTML = res.data.map(item => {
          return `
            <tr>
              <td><strong>${item.nama_barang}</strong></td>
              <td><span class="badge bg-light text-dark border">${item.kategori || 'Perlengkapan'}</span></td>
              <td><span class="badge bg-primary">${item.penanggung_jawab}</span></td>
              <td class="small text-muted">${item.keterangan || '-'}</td>
              <td class="text-end">
                <button class="btn btn-sm btn-link text-primary p-1 me-1" onclick="editInfoPoskoItem(${item.id}, '${item.nama_barang.replace(/'/g, "\\'")}', '${(item.kategori||'').replace(/'/g, "\\'")}', '${item.penanggung_jawab.replace(/'/g, "\\'")}', '${(item.keterangan||'').replace(/'/g, "\\'")}')" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                <button class="btn btn-sm btn-link text-danger p-1" onclick="hapusInfoPoskoItem(${item.id})" title="Hapus"><i class="bi bi-trash-fill"></i></button>
              </td>
            </tr>
          `;
        }).join('');
      } else {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-3">${res.message || 'Gagal memuat data.'}</td></tr>`;
      }
    })
    .catch(() => {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-3">Terjadi kesalahan jaringan.</td></tr>';
    });
}

function editInfoPoskoItem(id, nama_barang, kategori, penanggung_jawab, keterangan) {
  const modalEl = document.getElementById('kelolaInfoPoskoModal');
  const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
  modal.show();

  document.getElementById('infoPoskoId').value = id;
  document.getElementById('infoPoskoNama').value = nama_barang;
  document.getElementById('infoPoskoKategori').value = kategori;
  document.getElementById('infoPoskoPj').value = penanggung_jawab;
  document.getElementById('infoPoskoKeterangan').value = keterangan;

  document.getElementById('infoPoskoFormLabel').innerHTML = '<i class="bi bi-pencil-fill me-1 text-primary"></i>Edit Data (' + nama_barang + ')';
  document.getElementById('infoPoskoSubmitBtn').innerHTML = '<i class="bi bi-pencil-square me-1"></i> Update Data';
}

function hapusInfoPoskoItem(id) {
  if (!confirm('Apakah Anda yakin ingin menghapus barang / tugas posko ini?')) return;

  const formData = new FormData();
  formData.append('id', id);

  fetch('proses_informasi_posko.php?action=delete', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        loadInfoPoskoList();
        fetch('proses_informasi_posko.php?action=list')
          .then(r => r.json())
          .then(r => {
            if (r.success) {
              infoPoskoEntries.length = 0;
              infoPoskoEntries.push(...r.data);
              renderInfoPoskoCards();
            }
          });
      } else {
        alert(res.message || 'Gagal menghapus data.');
      }
    })
    .catch(() => alert('Terjadi kesalahan jaringan.'));
}

// Bind infoPoskoForm submit
document.getElementById('infoPoskoForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const submitBtn = document.getElementById('infoPoskoSubmitBtn');
  submitBtn.disabled = true;

  fetch('proses_informasi_posko.php?action=save', {
    method: 'POST',
    body: new FormData(this)
  })
    .then(res => res.json())
    .then(res => {
      submitBtn.disabled = false;
      if (res.success) {
        resetInfoPoskoForm();
        loadInfoPoskoList();
        fetch('proses_informasi_posko.php?action=list')
          .then(r => r.json())
          .then(r => {
            if (r.success) {
              infoPoskoEntries.length = 0;
              infoPoskoEntries.push(...r.data);
              renderInfoPoskoCards();
            }
          });
      } else {
        alert(res.message || 'Gagal menyimpan data.');
      }
    })
    .catch(() => {
      submitBtn.disabled = false;
      alert('Terjadi kesalahan jaringan.');
    });
});

// === KATA-KATA HARI INI MARQUEE JS ===
function renderKataKataMarquee() {
  const track = document.getElementById('quoteMarqueeTrack');
  if (!track) return;

  fetch('proses_kata_kata.php?action=list')
    .then(res => res.json())
    .then(res => {
      if (res.success && res.data.length > 0) {
        // Double array content for seamless infinite marquee loop
        const doubled = [...res.data, ...res.data];
        track.innerHTML = doubled.map(item => `
          <span class="quote-item-badge">
            <i class="bi bi-quote text-info fs-5 opacity-75"></i>
            <span class="quote-item-text">"${item.kata_kata}"</span>
            <span class="quote-item-author">— ${item.nama}</span>
            <span class="mx-2 opacity-50">&middot;</span>
          </span>
        `).join('');
      } else {
        track.innerHTML = '<span class="text-white-50 italic">Belum ada kata-kata hari ini. Klik tombol Edit Kata-Kata untuk menambahkan!</span>';
      }
    })
    .catch(() => {
      track.innerHTML = '<span class="text-white-50">Gagal memuat kata-kata hari ini.</span>';
    });
}

function bukaKelolaKataKataModal() {
  const autoInput = document.getElementById('autoInputKataKata');
  if (autoInput) {
    document.getElementById('kataKataInput').value = autoInput.value;
  }
  const modal = new bootstrap.Modal(document.getElementById('kelolaKataKataModal'));
  modal.show();
}

document.getElementById('kataKataForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const btn = document.getElementById('simpanKataKataBtn');
  btn.disabled = true;

  fetch('proses_kata_kata.php?action=save', {
    method: 'POST',
    body: new FormData(this)
  })
    .then(res => res.json())
    .then(res => {
      btn.disabled = false;
      if (res.success) {
        const newMsg = document.getElementById('kataKataInput').value.trim();
        const autoInput = document.getElementById('autoInputKataKata');
        if (autoInput) autoInput.value = newMsg;
        const dplDisplay = document.getElementById('dplMessageTextDisplay');
        if (isDplUser && dplDisplay) {
          dplDisplay.innerHTML = `"${newMsg}"`;
        }
        bootstrap.Modal.getInstance(document.getElementById('kelolaKataKataModal')).hide();
        renderKataKataMarquee();
      } else {
        alert(res.message || 'Gagal menyimpan kata-kata.');
      }
    })
    .catch(() => {
      btn.disabled = false;
      alert('Terjadi kesalahan jaringan.');
    });
});

function bukaPopUpKataKataAuto() {
  const modal = new bootstrap.Modal(document.getElementById('autoPopUpKataKataModal'));
  modal.show();
}

function bukaPopUpKataKataDpl() {
  bukaPopUpKataKataAuto();
}

function resetKataKataKania() {
  if (!confirm('Apakah Anda yakin ingin mereset kata-kata Ibu Kania? Setelah direset, pop-up kata-kata akan muncul kembali setiap kali login dan masuk ke Buku Lapangan.')) {
    return;
  }

  const fd = new FormData();
  if (typeof dplAnggotaId !== 'undefined' && dplAnggotaId) {
    fd.append('anggota_id', dplAnggotaId);
  }

  fetch('proses_kata_kata.php?action=reset_dpl', {
    method: 'POST',
    body: fd
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        alert(res.message || 'Kata-kata Ibu Kania berhasil direset!');

        const dplDisplay = document.getElementById('dplMessageTextDisplay');
        if (dplDisplay) {
          dplDisplay.innerHTML = '<span class="opacity-75">Belum ada kata-kata dari Ibu DPL. Kata-kata/pesan motivasi akan tampil di sini setelah Ibu Kania mengisinya pada pop-up.</span>';
        }

        const autoInput = document.getElementById('autoInputKataKata');
        if (autoInput) autoInput.value = '';
        const kataInput = document.getElementById('kataKataInput');
        if (kataInput) kataInput.value = '';

        isQuoteEmpty = true;

        const kelolaModalEl = document.getElementById('kelolaKataKataModal');
        if (kelolaModalEl) {
          const km = bootstrap.Modal.getInstance(kelolaModalEl);
          if (km) km.hide();
        }

        bukaPopUpKataKataAuto();

        if (typeof renderKataKataMarquee === 'function') {
          renderKataKataMarquee();
        }
      } else {
        alert(res.message || 'Gagal mereset kata-kata.');
      }
    })
    .catch(err => {
      console.error(err);
      alert('Terjadi kesalahan jaringan saat mereset kata-kata.');
    });
}

document.addEventListener('DOMContentLoaded', function() {
  // HANYA tampilkan pop-up otomatis jika anggota/DPL BELUM mengisi kata-katanya (isQuoteEmpty === true)
  if (isQuoteEmpty) {
    bukaPopUpKataKataAuto();
  }
});

document.getElementById('autoFormKataKata').addEventListener('submit', function(e) {
  e.preventDefault();
  const btn = document.getElementById('autoBtnSimpanKataKata');
  btn.disabled = true;

  fetch('proses_kata_kata.php?action=save', {
    method: 'POST',
    body: new FormData(this)
  })
    .then(res => res.json())
    .then(res => {
      btn.disabled = false;
      if (res.success) {
        isQuoteEmpty = false;
        const newMsg = document.getElementById('autoInputKataKata').value.trim();
        const dplDisplay = document.getElementById('dplMessageTextDisplay');
        if (isDplUser && dplDisplay) {
          dplDisplay.innerHTML = `"${newMsg}"`;
        }
        const modalEl = document.getElementById('autoPopUpKataKataModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        if (typeof renderKataKataMarquee === 'function') renderKataKataMarquee();
      } else {
        alert(res.message || 'Gagal menyimpan kata-kata.');
      }
    })
    .catch(() => {
      btn.disabled = false;
      alert('Terjadi kesalahan jaringan.');
    });
});

populateTargetDropdown();
renderGaleri();
update();
renderJadwalPiketTimeline();
renderInfoPoskoCards();
renderKataKataMarquee();

document.addEventListener('DOMContentLoaded', function() {
  populateTargetDropdown();
  update();
  renderGaleri();
  loadDaftarSuratList();
});

// === GENERATOR SURAT RESMI / DINAS KKN KELOMPOK 12 ===
let dataSuratTersimpanList = [];
let idSuratAktif = 0;

function ensureHtml2PdfLoaded(callback) {
  if (typeof html2pdf !== 'undefined') {
    callback();
    return;
  }
  const script = document.createElement('script');
  script.src = "https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js";
  script.onload = callback;
  script.onerror = function() {
    alert("Gagal memuat library pembuat PDF (html2pdf). Silakan periksa koneksi internet Anda.");
  };
  document.head.appendChild(script);
}

function bukaModalBuatSurat() {
  const modalEl = document.getElementById('suratKknModal');
  if (!modalEl) return;
  const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
  modal.show();
  updateSuratPreview();
}

function pilihTemplateSurat(type) {
  idSuratAktif = 0;
  const tglInput = document.getElementById('suratTanggalInput');
  const nomorInput = document.getElementById('suratNomorInput');
  const lampiranInput = document.getElementById('suratLampiranInput');
  const halInput = document.getElementById('suratHalInput');
  const kepadaInput = document.getElementById('suratKepadaInput');
  const pembukaInput = document.getElementById('suratPembukaInput');
  const isiInput = document.getElementById('suratIsiInput');
  const penutupInput = document.getElementById('suratPenutupInput');

  if (type === 'edaran') {
    if (nomorInput) nomorInput.value = '012/KKN-12/UMP/VII/2026';
    if (lampiranInput) lampiranInput.value = '-';
    if (halInput) halInput.value = 'Pelaksanaan Kegiatan KKN Kelompok 12';
    if (kepadaInput) kepadaInput.value = 'Yth. 1. Bapak Kepala Kelurahan Pal Lima\n2. Ketua RW/RT Kelurahan Pal Lima\n3. Tokoh Masyarakat Sub-Kelurahan\ndi -\nPontianak';
    if (pembukaInput) pembukaInput.value = 'Memperhatikan pelaksanaan kegiatan Kuliah Kerja Nyata (KKN) Kelompok 12 Universitas Muhammadiyah Pontianak di wilayah Kelurahan Pal Lima, Pontianak Barat, perlu kami sampaikan hal-hal sebagai berikut:';
    if (isiInput) isiInput.value = '1. Masa pelaksanaan kegiatan Kuliah Kerja Nyata (KKN) Kelompok 12 berlangsung mulai tanggal 20 Juli sampai dengan 30 Agustus 2026;\n2. Seluruh mahasiswa KKN Kelompok 12 wajib melaksanakan program kerja yang telah disetujui oleh Dosen Pembimbing Lapangan dan pihak Kelurahan;\n3. Dalam pelaksanaan program kerja, seluruh anggota hendaknya senantiasa berkoordinasi dengan pengurus RT, RW, dan tokoh masyarakat setempat;\n4. Seluruh warga masyarakat diimbau untuk turut serta berpartisipasi aktif dan mendukung kelancaran program kegiatan KKN Kelompok 12.';
    if (penutupInput) penutupInput.value = 'Demikian surat ini kami sampaikan, untuk menjadi pedoman dan dilaksanakan dengan penuh tanggung jawab.';
  } else if (type === 'undangan') {
    if (nomorInput) nomorInput.value = '013/UND/KKN-12/UMP/VII/2026';
    if (lampiranInput) lampiranInput.value = '1 (Satu) Lembar Susunan Acara';
    if (halInput) halInput.value = 'Undangan Kegiatan Pembukaan KKN 12';
    if (kepadaInput) kepadaInput.value = 'Yth. 1. Bapak Camat Pontianak Barat\n2. Bapak Kepala Kelurahan Pal Lima\n3. Para Ketua RW & RT se-Kelurahan Pal Lima\ndi -\nPontianak';
    if (pembukaInput) pembukaInput.value = 'Sehubungan dengan pembukaan dan sosialisasi program kerja Kuliah Kerja Nyata (KKN) Kelompok 12 Universitas Muhammadiyah Pontianak, dengan ini kami mengundang Bapak/Ibu untuk hadir pada:';
    if (isiInput) isiInput.value = 'Hari / Tanggal: Senin, 27 Juli 2026\nWaktu: Pukul 09.00 WIB s/d Selesai\nTempat: Aula Kelurahan Pal Lima, Kec. Pontianak Barat\nAgenda: Pembukaan Resmi & Sosialisasi Program Kerja KKN Kelompok 12 UMP';
    if (penutupInput) penutupInput.value = 'Mengingat pentingnya acara ini, kami sangat mengharapkan kehadiran Bapak/Ibu tepat pada waktunya. Atas perhatian dan kerja samanya kami sampaikan terima kasih.';
  } else if (type === 'permohonan') {
    if (nomorInput) nomorInput.value = '014/PMH/KKN-12/UMP/VII/2026';
    if (lampiranInput) lampiranInput.value = '1 (Satu) Berkas Proposal';
    if (halInput) halInput.value = 'Permohonan Izin Tempat & Dukungan Kegiatan';
    if (kepadaInput) kepadaInput.value = 'Yth. Pengurus Tempat / Tokoh Masyarakat\nKelurahan Pal Lima, Pontianak Barat\ndi -\nPontianak';
    if (pembukaInput) pembukaInput.value = 'Dalam rangka pelaksanaan program kerja Kuliah Kerja Nyata (KKN) Kelompok 12 Universitas Muhammadiyah Pontianak, kami mengajukan permohonan izin penggunaan lokasi dan dukungan fasilitas untuk kegiatan:';
    if (isiInput) isiInput.value = 'Nama Kegiatan: Edukasi Kesehatan & Gotong Royong Posko KKN 12\nWaktu Pelaksanaan: Sabtu, 1 Agustus 2026 (Pukul 08:00 WIB)\nLokasi yang Dimohonkan: Halaman Posko KKN & Balai Warga Pal Lima\nPenanggung Jawab: Rizki Tri Saputra (Ketua KKN Kelompok 12)';
    if (penutupInput) penutupInput.value = 'Demikian permohonan izin ini kami sampaikan. Atas bantuan, izin, dan kerja sama yang diberikan, kami mengucapkan terima kasih.';
  }

  updateSuratPreview();
}

function updateSuratPreview() {
  const tgl = document.getElementById('suratTanggalInput')?.value || 'Pontianak, 26 Juli 2026';
  const nomor = document.getElementById('suratNomorInput')?.value || '012/KKN-12/UMP/VII/2026';
  const lampiran = document.getElementById('suratLampiranInput')?.value || '-';
  const hal = document.getElementById('suratHalInput')?.value || 'Pelaksanaan Kegiatan KKN Kelompok 12';
  const kepada = document.getElementById('suratKepadaInput')?.value || 'Yth. 1. Bapak Kepala Kelurahan Pal Lima\n2. Ketua RW/RT Kelurahan Pal Lima\ndi -\nPontianak';
  const pembuka = document.getElementById('suratPembukaInput')?.value || '';
  const isiPoin = document.getElementById('suratIsiInput')?.value || '';
  const penutup = document.getElementById('suratPenutupInput')?.value || '';

  const jabatanKiri = document.getElementById('suratJabatanKiriInput')?.value || 'Kepala Kelurahan Pal Lima';
  const namaKiri = document.getElementById('suratNamaKiriInput')?.value || '.....................................';
  const nidnKiri = document.getElementById('suratNidnKiriInput')?.value || 'NIP. .....................................';

  const jabatanKanan = document.getElementById('suratJabatanKananInput')?.value || 'Ketua KKN Kelompok 12';
  const namaKanan = document.getElementById('suratNamaKananInput')?.value || 'Rizki Tri Saputra';
  const nimKanan = document.getElementById('suratNimKananInput')?.value || 'NIM. .....................................';

  if (document.getElementById('prevSuratTanggal')) document.getElementById('prevSuratTanggal').innerText = tgl;
  if (document.getElementById('prevSuratNomor')) document.getElementById('prevSuratNomor').innerText = nomor;
  if (document.getElementById('prevSuratLampiran')) document.getElementById('prevSuratLampiran').innerText = lampiran;
  if (document.getElementById('prevSuratHal')) document.getElementById('prevSuratHal').innerText = hal;
  if (document.getElementById('prevSuratKepada')) document.getElementById('prevSuratKepada').innerHTML = kepada.replace(/\n/g, '<br>');
  if (document.getElementById('prevSuratPembuka')) document.getElementById('prevSuratPembuka').innerText = pembuka;

  // Format isi poin: Deteksi pintar key-value (tabel aligned) vs numbered list & header pengantar
  if (document.getElementById('prevSuratIsiPoin')) {
    const lines = isiPoin.split('\n').map(l => l.trim()).filter(Boolean);
    if (lines.length > 0) {
      // Key-value pattern: Memiliki titik dua ':' tetapi BUKAN di akhir baris saja, dan label sebelum ':' <= 35 karakter
      const kvMatches = lines.filter(line => {
        const colonIdx = line.indexOf(':');
        return colonIdx > 0 && colonIdx < line.length - 1 && line.substring(0, colonIdx).trim().length <= 35;
      });
      const hasKeyValuePattern = kvMatches.length >= 2 || (lines.length === 1 && kvMatches.length === 1 && lines[0].indexOf(':') > 0 && lines[0].indexOf(':') < lines[0].length - 1);

      if (hasKeyValuePattern) {
        let tableRows = lines.map(line => {
          const clean = line.replace(/^\d+[\.\)]\s*/, '');
          const colonIdx = clean.indexOf(':');
          if (colonIdx !== -1 && colonIdx < clean.length - 1 && clean.substring(0, colonIdx).trim().length <= 35) {
            const label = clean.substring(0, colonIdx).trim();
            const val = clean.substring(colonIdx + 1).trim();
            return `
              <tr>
                <td style="width: 145px; vertical-align: top; padding: 3px 0; font-weight: 500;">${label}</td>
                <td style="width: 15px; vertical-align: top; padding: 3px 0;">:</td>
                <td style="vertical-align: top; padding: 3px 0; font-weight: 600;">${val}</td>
              </tr>`;
          } else {
            return `<tr><td colspan="3" style="padding: 3px 0; text-align: justify;">${clean}</td></tr>`;
          }
        }).join('');

        document.getElementById('prevSuratIsiPoin').innerHTML = `
          <table style="width: 88%; margin: 10px auto 16px 35px; border-collapse: collapse; border: none !important; font-size: 11pt;">
            ${tableRows}
          </table>`;
      } else {
        // Render mixed text (header intro + list items)
        let htmlOut = '';
        let listItems = [];

        const flushList = () => {
          if (listItems.length > 0) {
            const lis = listItems.map(item => `<li style="margin-bottom: 6px; text-align: justify;">${item}</li>`).join('');
            htmlOut += `<ol style="margin: 6px 0 12px; padding-left: 32px;">${lis}</ol>`;
            listItems = [];
          }
        };

        lines.forEach(line => {
          const isNumbered = /^\d+[\.\)]\s*/.test(line) || /^[\-&bull;\*]\s*/.test(line);
          const cleanLine = line.replace(/^(\d+[\.\)]|[\-&bull;\*])\s*/, '');

          // Jika baris berakhir ':' atau diawali kata pengantar seperti 'adapun'/'berikut' tanpa nomor, jadikan paragraf pengantar
          if (!isNumbered && (line.endsWith(':') || line.toLowerCase().startsWith('adapun') || line.toLowerCase().startsWith('berikut'))) {
            flushList();
            htmlOut += `<p style="margin: 8px 0 6px; text-align: justify; text-indent: 32px; line-height: 1.6;">${line}</p>`;
          } else {
            listItems.push(cleanLine);
          }
        });
        flushList();

        document.getElementById('prevSuratIsiPoin').innerHTML = htmlOut;
      }
    } else {
      document.getElementById('prevSuratIsiPoin').innerHTML = '';
    }
  }

  const activeEl = document.activeElement;
  const isEditingPaper = activeEl && activeEl.isContentEditable;

  if (!isEditingPaper) {
    if (document.getElementById('prevSuratTanggal')) document.getElementById('prevSuratTanggal').innerText = tgl;
    if (document.getElementById('prevSuratNomor')) document.getElementById('prevSuratNomor').innerText = nomor;
    if (document.getElementById('prevSuratLampiran')) document.getElementById('prevSuratLampiran').innerText = lampiran;
    if (document.getElementById('prevSuratHal')) document.getElementById('prevSuratHal').innerText = hal;
    if (document.getElementById('prevSuratKepada')) document.getElementById('prevSuratKepada').innerHTML = kepada.replace(/\n/g, '<br>');
    if (document.getElementById('prevSuratPembuka')) document.getElementById('prevSuratPembuka').innerText = pembuka;
    if (document.getElementById('prevSuratPenutup')) document.getElementById('prevSuratPenutup').innerText = penutup;

    if (document.getElementById('prevSuratJabatanKiri')) document.getElementById('prevSuratJabatanKiri').innerText = jabatanKiri;
    if (document.getElementById('prevSuratNamaKiri')) {
      const cleanNamaKiri = namaKiri.replace(/^\(|\)$/g, '');
      document.getElementById('prevSuratNamaKiri').innerText = `( ${cleanNamaKiri} )`;
    }
    if (document.getElementById('prevSuratNidnKiri')) document.getElementById('prevSuratNidnKiri').innerText = nidnKiri;

    if (document.getElementById('prevSuratJabatanKanan')) document.getElementById('prevSuratJabatanKanan').innerText = jabatanKanan;
    if (document.getElementById('prevSuratNamaKanan')) {
      const cleanNamaKanan = namaKanan.replace(/^\(|\)$/g, '');
      document.getElementById('prevSuratNamaKanan').innerText = `( ${cleanNamaKanan} )`;
    }
    if (document.getElementById('prevSuratNimKanan')) document.getElementById('prevSuratNimKanan').innerText = nimKanan;
  }
}

// Fitur Edit Teks Langsung di Kertas & Switch Toggle Mode Edit
function toggleEditKertasMode() {
  const container = document.getElementById('suratPreviewContainer');
  const badge = document.getElementById('statusEditKertasBadge');
  if (!container) return;

  const currentEditable = container.getAttribute('data-editable') !== 'false';
  const newEditable = !currentEditable;
  container.setAttribute('data-editable', newEditable ? 'true' : 'false');

  const editables = container.querySelectorAll('[contenteditable]');
  editables.forEach(el => {
    el.setAttribute('contenteditable', newEditable ? 'true' : 'false');
  });

  if (badge) {
    badge.className = newEditable ? 'badge bg-success ms-1' : 'badge bg-secondary ms-1';
    badge.innerText = newEditable ? 'AKTIF' : 'NONAKTIF';
  }
}

// Event Listener Sync Real-time dari Lembar Kertas Preview ke Input Form
document.addEventListener('DOMContentLoaded', function() {
  const container = document.getElementById('suratPreviewContainer');
  if (!container) return;

  container.addEventListener('input', function(e) {
    const target = e.target;
    if (!target) return;

    if (target.id === 'prevSuratTanggal') {
      const input = document.getElementById('suratTanggalInput');
      if (input) input.value = target.innerText.trim();
    } else if (target.id === 'prevSuratNomor') {
      const input = document.getElementById('suratNomorInput');
      if (input) input.value = target.innerText.trim();
    } else if (target.id === 'prevSuratLampiran') {
      const input = document.getElementById('suratLampiranInput');
      if (input) input.value = target.innerText.trim();
    } else if (target.id === 'prevSuratHal') {
      const input = document.getElementById('suratHalInput');
      if (input) input.value = target.innerText.trim();
    } else if (target.id === 'prevSuratKepada') {
      const input = document.getElementById('suratKepadaInput');
      if (input) input.value = target.innerText.replace(/<br\s*[\/]?>/gi, '\n').trim();
    } else if (target.id === 'prevSuratPembuka') {
      const input = document.getElementById('suratPembukaInput');
      if (input) input.value = target.innerText.trim();
    } else if (target.id === 'prevSuratPenutup') {
      const input = document.getElementById('suratPenutupInput');
      if (input) input.value = target.innerText.trim();
    } else if (target.id === 'prevSuratNamaKiri') {
      const input = document.getElementById('suratNamaKiriInput');
      if (input) input.value = target.innerText.replace(/^\(\s*|\s*\)$/g, '').trim();
    } else if (target.id === 'prevSuratJabatanKiri') {
      const input = document.getElementById('suratJabatanKiriInput');
      if (input) input.value = target.innerText.trim();
    } else if (target.id === 'prevSuratNidnKiri') {
      const input = document.getElementById('suratNidnKiriInput');
      if (input) input.value = target.innerText.trim();
    } else if (target.id === 'prevSuratNamaKanan') {
      const input = document.getElementById('suratNamaKananInput');
      if (input) input.value = target.innerText.replace(/^\(\s*|\s*\)$/g, '').trim();
    } else if (target.id === 'prevSuratJabatanKanan') {
      const input = document.getElementById('suratJabatanKananInput');
      if (input) input.value = target.innerText.trim();
    } else if (target.id === 'prevSuratNimKanan') {
      const input = document.getElementById('suratNimKananInput');
      if (input) input.value = target.innerText.trim();
    } else if (target.id === 'prevTabelJudul') {
      const input = document.getElementById('tabelJudulInput');
      if (input) input.value = target.innerText.trim();
    } else if (target.id === 'prevTabelDpl') {
      const input = document.getElementById('tabelDplInput');
      if (input) input.value = target.innerText.trim();
    } else if (target.id === 'prevTabelLokasi') {
      const input = document.getElementById('tabelLokasiInput');
      if (input) input.value = target.innerText.trim();
    }
  });

  // Sync edit sel di tabel pratinjau ke data list
  document.getElementById('tbodyPrevAnggotaGrid')?.addEventListener('input', function(e) {
    const target = e.target;
    if (!target) return;
    const row = target.closest('tr');
    if (!row) return;
    const idx = parseInt(row.getAttribute('data-row-index'), 10);
    const field = target.getAttribute('data-field');

    if (!isNaN(idx) && dataAnggotaSuratList[idx] && field) {
      if (field === 'nama') dataAnggotaSuratList[idx].nama = target.innerText.trim();
      if (field === 'nim') dataAnggotaSuratList[idx].nim = target.innerText.trim();
      if (field === 'prodi') dataAnggotaSuratList[idx].prodi = target.innerText.trim();
      if (field === 'jabatan') dataAnggotaSuratList[idx].jabatan = target.innerText.trim();

      renderInputTabelAnggotaForm();
    }
  });

  // Inisialisasi awal tabel anggota
  renderInputTabelAnggotaForm();
  renderPreviewTabelAnggota();
});

// State & Manajemen Tabel Daftar Peserta KKN untuk Lampiran Surat
let dataAnggotaSuratList = [
  { nama: 'Rizki Tri Saputra', nim: '231710035', prodi: 'Ilmu Hukum', jabatan: 'Ketua' },
  { nama: 'Virahmanda Abelia Ismaya', nim: '231510034', prodi: 'Ilmu Kesehatan Masyarakat', jabatan: 'Anggota' },
  { nama: 'Khairunisa Salsabila', nim: '231510090', prodi: 'Ilmu Kesehatan Masyarakat', jabatan: 'Anggota' },
  { nama: 'Muhammad Fiqri Mahendra', nim: '231220040', prodi: 'Teknik Informatika', jabatan: 'Anggota' },
  { nama: 'Siti Aliyyah', nim: '231310057', prodi: 'Manajemen', jabatan: 'Anggota' },
  { nama: 'Halimah Tusa’diah', nim: '231210140', prodi: 'Manajemen', jabatan: 'Anggota' },
  { nama: 'Anggi Rahmawati', nim: '231310246', prodi: 'Manajemen', jabatan: 'Anggota' },
  { nama: 'Tiara Fitriani', nim: '231310273', prodi: 'Manajemen', jabatan: 'Anggota' },
  { nama: 'Fathurrahman', nim: '231810019', prodi: 'Psikologi', jabatan: 'Anggota' },
  { nama: 'Sebastianus Aditia', nim: '231810120', prodi: 'Psikologi', jabatan: 'Anggota' }
];

function toggleTampilkanTabelAnggota() {
  const check = document.getElementById('tampilkanTabelAnggotaCheck');
  const wrapper = document.getElementById('wrapperInputTabelAnggota');
  const prevContainer = document.getElementById('prevSuratContainerTabelAnggota');

  if (check && check.checked) {
    if (wrapper) wrapper.classList.remove('d-none');
    if (prevContainer) prevContainer.style.display = 'block';
  } else {
    if (wrapper) wrapper.classList.add('d-none');
    if (prevContainer) prevContainer.style.display = 'none';
  }
  renderInputTabelAnggotaForm();
  renderPreviewTabelAnggota();
}

function renderInputTabelAnggotaForm() {
  const tbody = document.getElementById('tbodyInputAnggotaSurat');
  if (!tbody) return;

  let html = '';
  dataAnggotaSuratList.forEach((item, idx) => {
    html += `
      <tr>
        <td class="text-center fw-bold">${idx + 1}</td>
        <td><input type="text" class="form-control form-control-sm py-0.5 px-1" value="${item.nama || ''}" oninput="updateDataAnggotaSuratItem(${idx}, 'nama', this.value)"></td>
        <td><input type="text" class="form-control form-control-sm py-0.5 px-1" value="${item.nim || ''}" oninput="updateDataAnggotaSuratItem(${idx}, 'nim', this.value)"></td>
        <td><input type="text" class="form-control form-control-sm py-0.5 px-1" value="${item.prodi || ''}" oninput="updateDataAnggotaSuratItem(${idx}, 'prodi', this.value)"></td>
        <td>
          <select class="form-select form-select-sm py-0.5 px-1" onchange="updateDataAnggotaSuratItem(${idx}, 'jabatan', this.value)">
            <option value="Ketua" ${item.jabatan === 'Ketua' ? 'selected' : ''}>Ketua</option>
            <option value="Anggota" ${item.jabatan === 'Anggota' ? 'selected' : ''}>Anggota</option>
            <option value="Sekretaris" ${item.jabatan === 'Sekretaris' ? 'selected' : ''}>Sekretaris</option>
            <option value="Bendahara" ${item.jabatan === 'Bendahara' ? 'selected' : ''}>Bendahara</option>
            <option value="Wakil Ketua" ${item.jabatan === 'Wakil Ketua' ? 'selected' : ''}>Wakil Ketua</option>
          </select>
        </td>
        <td class="text-center">
          <button type="button" class="btn btn-xs btn-outline-danger p-0 px-1" onclick="hapusBarisAnggotaTabel(${idx})" title="Hapus baris">&times;</button>
        </td>
      </tr>`;
  });
  tbody.innerHTML = html;
}

function updateDataAnggotaSuratItem(idx, key, val) {
  if (dataAnggotaSuratList[idx]) {
    dataAnggotaSuratList[idx][key] = val;
    renderPreviewTabelAnggota();
  }
}

function tambahBarisAnggotaTabel() {
  dataAnggotaSuratList.push({ nama: '', nim: '', prodi: '', jabatan: 'Anggota' });
  renderInputTabelAnggotaForm();
  renderPreviewTabelAnggota();
}

function hapusBarisAnggotaTabel(idx) {
  dataAnggotaSuratList.splice(idx, 1);
  renderInputTabelAnggotaForm();
  renderPreviewTabelAnggota();
}

function resetContohAnggotaTabel() {
  dataAnggotaSuratList = [
    { nama: 'Rizki Tri Saputra', nim: '231710035', prodi: 'Ilmu Hukum', jabatan: 'Ketua' },
    { nama: 'Virahmanda Abelia Ismaya', nim: '231510034', prodi: 'Ilmu Kesehatan Masyarakat', jabatan: 'Anggota' },
    { nama: 'Khairunisa Salsabila', nim: '231510090', prodi: 'Ilmu Kesehatan Masyarakat', jabatan: 'Anggota' },
    { nama: 'Muhammad Fiqri Mahendra', nim: '231220040', prodi: 'Teknik Informatika', jabatan: 'Anggota' },
    { nama: 'Siti Aliyyah', nim: '231310057', prodi: 'Manajemen', jabatan: 'Anggota' },
    { nama: 'Halimah Tusa’diah', nim: '231210140', prodi: 'Manajemen', jabatan: 'Anggota' },
    { nama: 'Anggi Rahmawati', nim: '231310246', prodi: 'Manajemen', jabatan: 'Anggota' },
    { nama: 'Tiara Fitriani', nim: '231310273', prodi: 'Manajemen', jabatan: 'Anggota' },
    { nama: 'Fathurrahman', nim: '231810019', prodi: 'Psikologi', jabatan: 'Anggota' },
    { nama: 'Sebastianus Aditia', nim: '231810120', prodi: 'Psikologi', jabatan: 'Anggota' }
  ];
  if (document.getElementById('tabelJudulInput')) document.getElementById('tabelJudulInput').value = 'DAFTAR PESERTA KKN 2026 KELOMPOK 12';
  if (document.getElementById('tabelDplInput')) document.getElementById('tabelDplInput').value = 'Kania Khairunnisa, M.Psi., Psikolog';
  if (document.getElementById('tabelLokasiInput')) document.getElementById('tabelLokasiInput').value = 'Kelurahan Pal Lima, Kec. Pontianak Barat, Kota Pontianak';
  renderInputTabelAnggotaForm();
  renderPreviewTabelAnggota();
}

function renderPreviewTabelAnggota() {
  const judul = document.getElementById('tabelJudulInput')?.value || 'DAFTAR PESERTA KKN 2026 KELOMPOK 12';
  const dpl = document.getElementById('tabelDplInput')?.value || 'Kania Khairunnisa, M.Psi., Psikolog';
  const lokasi = document.getElementById('tabelLokasiInput')?.value || 'Desa Arang Limbung, Kec. Sungai Raya, Kubu Raya';

  if (document.getElementById('prevTabelJudul')) document.getElementById('prevTabelJudul').innerText = judul;
  if (document.getElementById('prevTabelDpl')) document.getElementById('prevTabelDpl').innerText = dpl;
  if (document.getElementById('prevTabelLokasi')) document.getElementById('prevTabelLokasi').innerText = lokasi;

  const tbody = document.getElementById('tbodyPrevAnggotaGrid');
  if (!tbody) return;

  if (dataAnggotaSuratList.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-2" style="border: 1px solid #000;">Belum ada anggota. Klik "+ Tambah" di form sebelah kiri.</td></tr>';
    return;
  }

  let html = '';
  let i = 0;
  while (i < dataAnggotaSuratList.length) {
    let currentJabatan = dataAnggotaSuratList[i].jabatan || 'Anggota';
    let count = 1;

    while (i + count < dataAnggotaSuratList.length && (dataAnggotaSuratList[i + count].jabatan || 'Anggota') === currentJabatan) {
      count++;
    }

    for (let j = 0; j < count; j++) {
      const idx = i + j;
      const item = dataAnggotaSuratList[idx];

      html += `<tr data-row-index="${idx}">
        <td style="border: 1px solid #000; padding: 5px 6px; text-align: center;" contenteditable="true" data-field="no">${idx + 1}.</td>
        <td style="border: 1px solid #000; padding: 5px 8px;" contenteditable="true" data-field="nama">${item.nama || ''}</td>
        <td style="border: 1px solid #000; padding: 5px 8px;" contenteditable="true" data-field="nim">${item.nim || ''}</td>
        <td style="border: 1px solid #000; padding: 5px 8px;" contenteditable="true" data-field="prodi">${item.prodi || ''}</td>`;

      if (j === 0) {
        if (count > 1) {
          html += `<td rowspan="${count}" style="border: 1px solid #000; padding: 5px 8px; text-align: center; vertical-align: middle;" contenteditable="true" data-field="jabatan">${currentJabatan}</td>`;
        } else {
          html += `<td style="border: 1px solid #000; padding: 5px 8px; text-align: center; vertical-align: middle;" contenteditable="true" data-field="jabatan">${currentJabatan}</td>`;
        }
      }
      html += `</tr>`;
    }
    i += count;
  }
  tbody.innerHTML = html;
}

function unduhSuratPDF() {
  ensureHtml2PdfLoaded(function() {
    const element = document.getElementById('suratPreviewContainer');
    if (!element) return;

    simpanSuratSaatIni(true);

    const btnList = document.querySelectorAll('button[onclick="unduhSuratPDF()"]');
    const originalStates = [];
    btnList.forEach(b => {
      originalStates.push(b.innerHTML);
      b.disabled = true;
      b.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyiapkan PDF...';
    });

    setTimeout(() => {
      window.scrollTo(0, 0);

      const RENDER_WIDTH = 750;

      const wrapper = document.createElement('div');
      wrapper.style.cssText = 'position:fixed;left:0;top:0;width:' + RENDER_WIDTH + 'px;z-index:-99999;opacity:0.01;pointer-events:none;background:#fff;';
      document.body.appendChild(wrapper);

      const tempDiv = document.createElement('div');
      tempDiv.className = 'html2pdf-container';
      tempDiv.style.cssText = 'background:#fff;color:#000;padding:12px 18px;font-family:"Times New Roman",Times,serif;width:' + RENDER_WIDTH + 'px;box-sizing:border-box;margin:0;font-size:11pt;line-height:1.5;';

      const contentClone = element.cloneNode(true);
      contentClone.style.cssText = 'width:100%!important;max-width:100%!important;padding:0!important;margin:0!important;border:none!important;box-shadow:none!important;border-radius:0!important;background:#ffffff!important;';

      // Bersihkan atribut contenteditable agar hasil PDF bersih tanpa border edit
      contentClone.querySelectorAll('[contenteditable]').forEach(el => el.removeAttribute('contenteditable'));

      tempDiv.appendChild(contentClone);
      wrapper.appendChild(tempDiv);

      void tempDiv.offsetHeight;

      const nomorVal = document.getElementById('suratNomorInput')?.value || '012';
      const cleanNum = nomorVal.replace(/[^a-zA-Z0-9]/g, '_');
      const filename = `Surat_KKN_12_UMP_${cleanNum}.pdf`;

      const opt = {
        margin:       [10, 10, 12, 10],
        filename:     filename,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { 
          scale: 2, 
          useCORS: true, 
          allowTaint: true,
          logging: false,
          width: RENDER_WIDTH,
          windowWidth: RENDER_WIDTH,
          x: 0,
          y: 0,
          scrollX: 0,
          scrollY: 0
        },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak:    { mode: ['css', 'legacy'] }
      };

      html2pdf().set(opt).from(tempDiv).save().then(() => {
        if (wrapper.parentNode) document.body.removeChild(wrapper);
        btnList.forEach((b, idx) => {
          b.disabled = false;
          b.innerHTML = originalStates[idx];
        });
        const alertToast = document.getElementById('uploadToast');
        if (alertToast) {
          alertToast.innerHTML = '<i class="bi bi-check-circle-fill me-1 text-success"></i> File PDF Surat berhasil diunduh!';
          alertToast.classList.add('show');
          setTimeout(() => alertToast.classList.remove('show'), 3000);
        }
      }).catch(err => {
        console.error(err);
        alert('Gagal mengunduh PDF. Silakan gunakan tombol "Cetak / Print" atau "Unduh Word (.docx)" sebagai alternatif.');
        if (wrapper.parentNode) document.body.removeChild(wrapper);
        btnList.forEach((b, idx) => {
          b.disabled = false;
          b.innerHTML = originalStates[idx];
        });
      });
    }, 250);
  });
}

function unduhSuratWord() {
  const element = document.getElementById('suratPreviewContainer');
  if (!element) return;

  simpanSuratSaatIni(true);

  const clone = element.cloneNode(true);
  clone.querySelectorAll('[contenteditable]').forEach(el => el.removeAttribute('contenteditable'));

  const nomorVal = document.getElementById('suratNomorInput')?.value || '012';
  const cleanNum = nomorVal.replace(/[^a-zA-Z0-9]/g, '_');
  const filename = `Surat_KKN_12_UMP_${cleanNum}.doc`;

  const header = `<html xmlns:o='urn:schemas-microsoft-com:office:office' 
                        xmlns:w='urn:schemas-microsoft-com:office:word' 
                        xmlns='http://www.w3.org/TR/REC-html40'>
  <head>
    <meta charset='utf-8'>
    <title>Surat KKN 12 UMP</title>
    <!--[if gte mso 9]>
    <xml>
      <w:WordDocument>
        <w:View>Print</w:View>
        <w:Zoom>100</w:Zoom>
        <w:DoNotOptimizeForCustomXSL/>
      </w:WordDocument>
    </xml>
    <![endif]-->
    <style>
      @page WordSection1 {
        size: 210mm 297mm;
        margin: 20mm 20mm 20mm 20mm;
      }
      div.WordSection1 {
        page: WordSection1;
      }
      body {
        font-family: 'Times New Roman', Times, serif;
        font-size: 11pt;
        line-height: 1.5;
        color: #000000;
      }
      table {
        border-collapse: collapse;
      }
    </style>
  </head>
  <body>
    <div class="WordSection1">
      ${clone.innerHTML}
    </div>
  </body>
  </html>`;

  const blob = new Blob(['\ufeff', header], {
    type: 'application/msword;charset=utf-8'
  });

  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);

  const alertToast = document.getElementById('uploadToast');
  if (alertToast) {
    alertToast.innerHTML = '<i class="bi bi-file-earmark-word-fill me-1 text-primary"></i> Berkas Word Surat (.doc) berhasil diunduh dan dapat diedit di Word!';
    alertToast.classList.add('show');
    setTimeout(() => alertToast.classList.remove('show'), 3000);
  }
}

function cetakSuratLangsung() {
  const element = document.getElementById('suratPreviewContainer');
  if (!element) return;

  simpanSuratSaatIni(true);

  const printWin = window.open('', '_blank');
  if (!printWin) {
    alert('Pop-up terblokir. Izinkan pop-up untuk mencetak surat.');
    return;
  }

  printWin.document.write(`
    <!DOCTYPE html>
    <html lang="id">
    <head>
      <meta charset="UTF-8">
      <title>Cetak Surat KKN Kelompok 12</title>
      <link href="https://fonts.googleapis.com/css2?family=Times+New+Roman&display=swap" rel="stylesheet">
      <style>
        @page {
          size: A4 portrait;
          margin: 15mm 15mm 15mm 15mm;
        }
        body {
          font-family: 'Times New Roman', Times, serif;
          color: #000;
          background: #fff;
          margin: 0;
          padding: 0;
          font-size: 11pt;
          line-height: 1.5;
        }
        .surat-preview-paper {
          width: 100% !important;
          max-width: 100% !important;
          padding: 0 !important;
          margin: 0 !important;
          border: none !important;
          box-shadow: none !important;
          border-radius: 0 !important;
          background: #fff !important;
        }
      </style>
    </head>
    <body onload="window.print(); setTimeout(function(){ window.close(); }, 500);">
      ${element.outerHTML}
    </body>
    </html>
  `);
  printWin.document.close();
}

function buatSuratBaru() {
  idSuratAktif = 0;
  pilihTemplateSurat('edaran');
  const alertToast = document.getElementById('uploadToast');
  if (alertToast) {
    alertToast.innerHTML = '<i class="bi bi-file-earmark-plus me-1"></i> Form diset untuk membuat surat baru';
    alertToast.classList.add('show');
    setTimeout(() => alertToast.classList.remove('show'), 2500);
  }
}

function loadDaftarSuratList() {
  const localList = JSON.parse(localStorage.getItem('surat_kkn_list') || '[]');

  fetch('proses_surat.php?action=list')
    .then(res => res.json())
    .then(res => {
      if (res.success && Array.isArray(res.data)) {
        const map = new Map();
        localList.forEach((item, index) => {
          const key = item.id ? 'id_' + item.id : 'loc_' + index;
          map.set(key, item);
        });
        res.data.forEach(item => {
          const key = 'id_' + item.id;
          map.set(key, item);
        });
        const combined = Array.from(map.values());
        localStorage.setItem('surat_kkn_list', JSON.stringify(combined));
        dataSuratTersimpanList = combined;
        renderDaftarSuratTable(combined);
      } else {
        dataSuratTersimpanList = localList;
        renderDaftarSuratTable(localList);
      }
    })
    .catch(() => {
      dataSuratTersimpanList = localList;
      renderDaftarSuratTable(localList);
    });
}

function renderDaftarSuratTable(list) {
  const tbody = document.getElementById('daftarSuratTableBody');
  const badge = document.getElementById('jumlahSuratTersimpanBadge');

  if (badge) badge.innerText = list.length;
  if (!tbody) return;

  if (list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-inbox fs-4 d-block mb-1"></i>Belum ada surat yang disimpan. Klik "Simpan Surat" untuk mengarsipkan.</td></tr>`;
    return;
  }

  tbody.innerHTML = list.map((item, idx) => `
    <tr>
      <td class="fw-bold text-center">${idx + 1}</td>
      <td class="fw-bold text-primary">${item.nomor_surat}</td>
      <td>${item.hal}</td>
      <td><span class="badge bg-light text-dark border">${item.tanggal_surat}</span></td>
      <td class="text-end">
        <div class="d-flex justify-content-end gap-1">
          <button class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-2.5 py-1 shadow-sm" onclick="muatSuratTersimpanByPos(${idx})" title="Edit Surat Ini">
            <i class="bi bi-pencil-square me-1"></i>Edit
          </button>
          <button class="btn btn-sm btn-primary rounded-pill px-2.5 py-1 shadow-sm" onclick="muatSuratTersimpanByPos(${idx})" title="Buka & Pratinjau Surat Ini">
            <i class="bi bi-folder-symlink me-1"></i>Buka
          </button>
          <button class="btn btn-sm btn-outline-danger rounded-circle p-1 d-flex align-items-center justify-content-center shadow-sm" style="width:28px;height:28px;" onclick="hapusSuratTersimpanByPos(${idx})" title="Hapus Surat">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </td>
    </tr>
  `).join('');
}

function toggleDaftarSuratTersimpan() {
  const panel = document.getElementById('panelSuratTersimpan');
  if (!panel) return;

  if (panel.classList.contains('d-none')) {
    loadDaftarSuratList();
    panel.classList.remove('d-none');
  } else {
    panel.classList.add('d-none');
  }
}

function simpanSuratSaatIni(silent = false) {
  const nomor = document.getElementById('suratNomorInput')?.value || '';
  const hal = document.getElementById('suratHalInput')?.value || '';

  if (!nomor || !hal) {
    if (!silent) alert('Nomor Surat dan Hal / Perihal harus diisi sebelum menyimpan.');
    return;
  }

  const newUniqueId = idSuratAktif > 0 ? idSuratAktif : Date.now();

  const currentObj = {
    id: newUniqueId,
    nomor_surat: nomor,
    tanggal_surat: document.getElementById('suratTanggalInput')?.value || '',
    lampiran: document.getElementById('suratLampiranInput')?.value || '-',
    hal: hal,
    kepada: document.getElementById('suratKepadaInput')?.value || '',
    pembuka: document.getElementById('suratPembukaInput')?.value || '',
    isi_poin: document.getElementById('suratIsiInput')?.value || '',
    penutup: document.getElementById('suratPenutupInput')?.value || '',
    jabatan_kiri: document.getElementById('suratJabatanKiriInput')?.value || '',
    nama_kiri: document.getElementById('suratNamaKiriInput')?.value || '',
    nidn_kiri: document.getElementById('suratNidnKiriInput')?.value || '',
    jabatan_kanan: document.getElementById('suratJabatanKananInput')?.value || '',
    nama_kanan: document.getElementById('suratNamaKananInput')?.value || '',
    nim_kanan: document.getElementById('suratNimKananInput')?.value || ''
  };

  // 1. Simpan ke localStorage (Berdasarkan ID Unik)
  let list = JSON.parse(localStorage.getItem('surat_kkn_list') || '[]');
  const idx = list.findIndex(l => l.id && String(l.id) === String(currentObj.id));
  if (idx !== -1) {
    list[idx] = currentObj;
  } else {
    list.unshift(currentObj);
  }
  localStorage.setItem('surat_kkn_list', JSON.stringify(list));
  dataSuratTersimpanList = list;
  renderDaftarSuratTable(list);

  if (!silent) {
    const alertToast = document.getElementById('uploadToast');
    if (alertToast) {
      alertToast.innerHTML = '<i class="bi bi-bookmark-check-fill me-1 text-success"></i> Surat "' + hal + '" berhasil disimpan ke arsip!';
      alertToast.classList.add('show');
      setTimeout(() => alertToast.classList.remove('show'), 2600);
    }
  }

  // 2. Simpan ke database MySQL server
  const formData = new FormData();
  formData.append('id', idSuratAktif);
  formData.append('nomor_surat', nomor);
  formData.append('tanggal_surat', currentObj.tanggal_surat);
  formData.append('lampiran', currentObj.lampiran);
  formData.append('hal', hal);
  formData.append('kepada', currentObj.kepada);
  formData.append('pembuka', currentObj.pembuka);
  formData.append('isi_poin', currentObj.isi_poin);
  formData.append('penutup', currentObj.penutup);
  formData.append('jabatan_kiri', currentObj.jabatan_kiri);
  formData.append('nama_kiri', currentObj.nama_kiri);
  formData.append('nidn_kiri', currentObj.nidn_kiri);
  formData.append('jabatan_kanan', currentObj.jabatan_kanan);
  formData.append('nama_kanan', currentObj.nama_kanan);
  formData.append('nim_kanan', currentObj.nim_kanan);

  fetch('proses_surat.php?action=save', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(res => {
      if (res.success && res.id) {
        idSuratAktif = res.id;
        currentObj.id = res.id;
        let updatedList = JSON.parse(localStorage.getItem('surat_kkn_list') || '[]');
        const uIdx = updatedList.findIndex(l => String(l.id) === String(newUniqueId));
        if (uIdx !== -1) updatedList[uIdx].id = res.id;
        localStorage.setItem('surat_kkn_list', JSON.stringify(updatedList));
        dataSuratTersimpanList = updatedList;
        renderDaftarSuratTable(updatedList);
      }
    })
    .catch(() => {});
}

function muatSuratTersimpanByPos(index) {
  const surat = dataSuratTersimpanList[index];
  if (!surat) {
    alert('Data surat tidak ditemukan dalam arsip.');
    return;
  }

  idSuratAktif = surat.id || 0;

  if (document.getElementById('suratTanggalInput')) document.getElementById('suratTanggalInput').value = surat.tanggal_surat || '';
  if (document.getElementById('suratNomorInput')) document.getElementById('suratNomorInput').value = surat.nomor_surat || '';
  if (document.getElementById('suratLampiranInput')) document.getElementById('suratLampiranInput').value = surat.lampiran || '-';
  if (document.getElementById('suratHalInput')) document.getElementById('suratHalInput').value = surat.hal || '';
  if (document.getElementById('suratKepadaInput')) document.getElementById('suratKepadaInput').value = surat.kepada || '';
  if (document.getElementById('suratPembukaInput')) document.getElementById('suratPembukaInput').value = surat.pembuka || '';
  if (document.getElementById('suratIsiInput')) document.getElementById('suratIsiInput').value = surat.isi_poin || '';
  if (document.getElementById('suratPenutupInput')) document.getElementById('suratPenutupInput').value = surat.penutup || '';
  if (document.getElementById('suratJabatanKiriInput')) document.getElementById('suratJabatanKiriInput').value = surat.jabatan_kiri || '';
  if (document.getElementById('suratNamaKiriInput')) document.getElementById('suratNamaKiriInput').value = surat.nama_kiri || '';
  if (document.getElementById('suratNidnKiriInput')) document.getElementById('suratNidnKiriInput').value = surat.nidn_kiri || '';
  if (document.getElementById('suratJabatanKananInput')) document.getElementById('suratJabatanKananInput').value = surat.jabatan_kanan || '';
  if (document.getElementById('suratNamaKananInput')) document.getElementById('suratNamaKananInput').value = surat.nama_kanan || '';
  if (document.getElementById('suratNimKananInput')) document.getElementById('suratNimKananInput').value = surat.nim_kanan || '';

  // Live update pratinjau A4
  updateSuratPreview();

  // Sembunyikan panel arsip agar editor terlihat jelas
  const panel = document.getElementById('panelSuratTersimpan');
  if (panel) panel.classList.add('d-none');

  // Highlight form untuk konfirmasi visual
  const formCard = document.querySelector('#suratForm')?.closest('.card');
  if (formCard) {
    formCard.style.transition = 'all 0.4s ease';
    formCard.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.4)';
    setTimeout(() => { formCard.style.boxShadow = ''; }, 1500);
  }

  const alertToast = document.getElementById('uploadToast');
  if (alertToast) {
    alertToast.innerHTML = '<i class="bi bi-folder-symlink me-1 text-primary"></i> Surat "' + (surat.nomor_surat || '') + '" berhasil dimuat ke editor!';
    alertToast.classList.add('show');
    setTimeout(() => alertToast.classList.remove('show'), 2600);
  }
}

function hapusSuratTersimpanByPos(index) {
  const surat = dataSuratTersimpanList[index];
  if (!surat) return;

  if (!confirm('Apakah Anda yakin ingin menghapus surat "' + (surat.hal || surat.nomor_surat) + '" dari arsip?')) return;

  // Hapus HANYA 1 surat spesifik ini berdasarkan ID unik
  let list = JSON.parse(localStorage.getItem('surat_kkn_list') || '[]');
  
  if (surat.id) {
    list = list.filter(l => String(l.id) !== String(surat.id));
  } else {
    list.splice(index, 1);
  }
  
  localStorage.setItem('surat_kkn_list', JSON.stringify(list));
  dataSuratTersimpanList = list;
  renderDaftarSuratTable(list);

  if (idSuratAktif && String(idSuratAktif) === String(surat.id)) {
    idSuratAktif = 0;
  }

  if (surat.id) {
    const formData = new FormData();
    formData.append('id', surat.id);
    fetch('proses_surat.php?action=delete', {
      method: 'POST',
      body: formData
    }).then(res => res.json()).then(() => {
      loadDaftarSuratList();
    }).catch(() => {});
  }

  const alertToast = document.getElementById('uploadToast');
  if (alertToast) {
    alertToast.innerHTML = '<i class="bi bi-trash me-1 text-danger"></i> Surat "' + (surat.hal || surat.nomor_surat) + '" berhasil dihapus.';
    alertToast.classList.add('show');
    setTimeout(() => alertToast.classList.remove('show'), 2500);
  }
}

// === FITUR NOTULENSI RAPAT & AGENDA SURAT MASUK (KESEKRETARIATAN) ===
let dataNotulensiList = [];
let dataSuratMasukList = [];

function bukaModalNotulensi() {
  const modalEl = document.getElementById('notulensiSekretarisModal');
  if (!modalEl) return;
  const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
  modal.show();

  // Set default dates
  const today = new Date().toISOString().split('T')[0];
  if (document.getElementById('notulensiTanggal')) document.getElementById('notulensiTanggal').value = today;
  if (document.getElementById('suratMasukTanggal')) document.getElementById('suratMasukTanggal').value = today;

  loadNotulensiList();
  loadSuratMasukList();
}

function resetFormNotulensi() {
  const form = document.getElementById('notulensiForm');
  if (form) form.reset();
  document.getElementById('notulensiId').value = '0';
  document.getElementById('notulensiTanggal').value = new Date().toISOString().split('T')[0];
  document.getElementById('notulensiSubmitBtn').innerHTML = '<i class="bi bi-save me-1"></i> Simpan Notulensi Rapat';
}

function loadNotulensiList() {
  const local = JSON.parse(localStorage.getItem('kkn_notulensi_list') || '[]');
  fetch('proses_sekretaris.php?action=list_notulensi')
    .then(r => r.json())
    .then(r => {
      if (r.success && Array.isArray(r.data)) {
        const map = new Map();
        local.forEach((item, idx) => map.set(item.id ? 'id_' + item.id : 'loc_' + idx, item));
        r.data.forEach(item => map.set('id_' + item.id, item));
        const combined = Array.from(map.values());
        localStorage.setItem('kkn_notulensi_list', JSON.stringify(combined));
        dataNotulensiList = combined;
        renderNotulensiTable(combined);
      } else {
        dataNotulensiList = local;
        renderNotulensiTable(local);
      }
    })
    .catch(() => {
      dataNotulensiList = local;
      renderNotulensiTable(local);
    });
}

function renderNotulensiTable(list) {
  const tbody = document.getElementById('notulensiTableBody');
  const badge = document.getElementById('badgeTotalNotulensi');
  if (badge) badge.innerText = list.length + ' Notulensi';
  if (!tbody) return;

  if (list.length === 0) {
    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4"><i class="bi bi-journal-x fs-4 d-block mb-1"></i>Belum ada notulensi rapat yang dicatat.</td></tr>';
    return;
  }

  tbody.innerHTML = list.map((item, idx) => `
    <tr>
      <td><span class="badge bg-light text-dark border">${item.tanggal || '-'}</span></td>
      <td><strong>${item.judul_rapat || '-'}</strong><br><small class="text-muted">Peserta: ${item.peserta || '-'}</small></td>
      <td><small>${(item.keputusan || item.pembahasan || '-').substring(0, 60)}...</small></td>
      <td class="text-end">
        <div class="d-flex justify-content-end gap-1">
          <button class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-2.5 py-1 shadow-sm" onclick="editNotulensiItem(${idx})" title="Edit">
            <i class="bi bi-pencil-square"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger rounded-circle p-1 d-flex align-items-center justify-content-center shadow-sm" style="width:28px;height:28px;" onclick="hapusNotulensiItem(${idx})" title="Hapus">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </td>
    </tr>
  `).join('');
}

function simpanNotulensi(e) {
  e.preventDefault();
  const idVal = parseInt(document.getElementById('notulensiId').value || '0');
  const uniqueId = idVal > 0 ? idVal : Date.now();
  const obj = {
    id: uniqueId,
    tanggal: document.getElementById('notulensiTanggal').value,
    judul_rapat: document.getElementById('notulensiJudul').value,
    peserta: document.getElementById('notulensiPeserta').value,
    pembahasan: document.getElementById('notulensiPembahasan').value,
    keputusan: document.getElementById('notulensiKeputusan').value
  };

  let list = JSON.parse(localStorage.getItem('kkn_notulensi_list') || '[]');
  const idx = list.findIndex(l => String(l.id) === String(uniqueId));
  if (idx !== -1) {
    list[idx] = obj;
  } else {
    list.unshift(obj);
  }
  localStorage.setItem('kkn_notulensi_list', JSON.stringify(list));
  dataNotulensiList = list;
  renderNotulensiTable(list);

  const fd = new FormData();
  fd.append('id', idVal);
  fd.append('tanggal', obj.tanggal);
  fd.append('judul_rapat', obj.judul_rapat);
  fd.append('peserta', obj.peserta);
  fd.append('pembahasan', obj.pembahasan);
  fd.append('keputusan', obj.keputusan);

  fetch('proses_sekretaris.php?action=save_notulensi', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success && res.id) {
        obj.id = res.id;
        let up = JSON.parse(localStorage.getItem('kkn_notulensi_list') || '[]');
        const uIdx = up.findIndex(l => String(l.id) === String(uniqueId));
        if (uIdx !== -1) up[uIdx].id = res.id;
        localStorage.setItem('kkn_notulensi_list', JSON.stringify(up));
        dataNotulensiList = up;
        renderNotulensiTable(up);
      }
    }).catch(() => {});

  resetFormNotulensi();
}

function editNotulensiItem(idx) {
  const item = dataNotulensiList[idx];
  if (!item) return;
  document.getElementById('notulensiId').value = item.id || '0';
  document.getElementById('notulensiTanggal').value = item.tanggal || '';
  document.getElementById('notulensiJudul').value = item.judul_rapat || '';
  document.getElementById('notulensiPeserta').value = item.peserta || '';
  document.getElementById('notulensiPembahasan').value = item.pembahasan || '';
  document.getElementById('notulensiKeputusan').value = item.keputusan || '';
  document.getElementById('notulensiSubmitBtn').innerHTML = '<i class="bi bi-check-circle me-1"></i> Update Notulensi';
}

function hapusNotulensiItem(idx) {
  const item = dataNotulensiList[idx];
  if (!item) return;
  if (!confirm('Hapus notulensi rapat ini?')) return;

  let list = JSON.parse(localStorage.getItem('kkn_notulensi_list') || '[]');
  list = list.filter(l => String(l.id) !== String(item.id));
  localStorage.setItem('kkn_notulensi_list', JSON.stringify(list));
  dataNotulensiList = list;
  renderNotulensiTable(list);

  if (item.id) {
    const fd = new FormData();
    fd.append('id', item.id);
    fetch('proses_sekretaris.php?action=delete_notulensi', { method: 'POST', body: fd });
  }
}

function resetFormSuratMasuk() {
  const form = document.getElementById('suratMasukForm');
  if (form) form.reset();
  document.getElementById('suratMasukId').value = '0';
  document.getElementById('suratMasukTanggal').value = new Date().toISOString().split('T')[0];
  document.getElementById('suratMasukSubmitBtn').innerHTML = '<i class="bi bi-save me-1"></i> Simpan Agenda Surat Masuk';
}

function loadSuratMasukList() {
  const local = JSON.parse(localStorage.getItem('kkn_surat_masuk_list') || '[]');
  fetch('proses_sekretaris.php?action=list_surat_masuk')
    .then(r => r.json())
    .then(r => {
      if (r.success && Array.isArray(r.data)) {
        const map = new Map();
        local.forEach((item, idx) => map.set(item.id ? 'id_' + item.id : 'loc_' + idx, item));
        r.data.forEach(item => map.set('id_' + item.id, item));
        const combined = Array.from(map.values());
        localStorage.setItem('kkn_surat_masuk_list', JSON.stringify(combined));
        dataSuratMasukList = combined;
        renderSuratMasukTable(combined);
      } else {
        dataSuratMasukList = local;
        renderSuratMasukTable(local);
      }
    })
    .catch(() => {
      dataSuratMasukList = local;
      renderSuratMasukTable(local);
    });
}

function renderSuratMasukTable(list) {
  const tbody = document.getElementById('suratMasukTableBody');
  const badge = document.getElementById('badgeTotalSuratMasuk');
  if (badge) badge.innerText = list.length + ' Surat';
  if (!tbody) return;

  if (list.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-inbox fs-4 d-block mb-1"></i>Belum ada surat masuk yang dicatat.</td></tr>';
    return;
  }

  tbody.innerHTML = list.map((item, idx) => `
    <tr>
      <td><span class="badge bg-light text-dark border">${item.tanggal_terima || '-'}</span></td>
      <td><strong class="text-primary">${item.nomor_surat || '-'}</strong></td>
      <td>${item.pengirim || '-'}</td>
      <td>${item.perihal || '-'}</td>
      <td class="text-end">
        <div class="d-flex justify-content-end gap-1">
          <button class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-2.5 py-1 shadow-sm" onclick="editSuratMasukItem(${idx})" title="Edit">
            <i class="bi bi-pencil-square"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger rounded-circle p-1 d-flex align-items-center justify-content-center shadow-sm" style="width:28px;height:28px;" onclick="hapusSuratMasukItem(${idx})" title="Hapus">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </td>
    </tr>
  `).join('');
}

function simpanSuratMasuk(e) {
  e.preventDefault();
  const idVal = parseInt(document.getElementById('suratMasukId').value || '0');
  const uniqueId = idVal > 0 ? idVal : Date.now();
  const obj = {
    id: uniqueId,
    tanggal_terima: document.getElementById('suratMasukTanggal').value,
    nomor_surat: document.getElementById('suratMasukNomor').value,
    pengirim: document.getElementById('suratMasukPengirim').value,
    perihal: document.getElementById('suratMasukPerihal').value,
    keterangan: document.getElementById('suratMasukKeterangan').value
  };

  let list = JSON.parse(localStorage.getItem('kkn_surat_masuk_list') || '[]');
  const idx = list.findIndex(l => String(l.id) === String(uniqueId));
  if (idx !== -1) {
    list[idx] = obj;
  } else {
    list.unshift(obj);
  }
  localStorage.setItem('kkn_surat_masuk_list', JSON.stringify(list));
  dataSuratMasukList = list;
  renderSuratMasukTable(list);

  const fd = new FormData();
  fd.append('id', idVal);
  fd.append('tanggal_terima', obj.tanggal_terima);
  fd.append('nomor_surat', obj.nomor_surat);
  fd.append('pengirim', obj.pengirim);
  fd.append('perihal', obj.perihal);
  fd.append('keterangan', obj.keterangan);

  fetch('proses_sekretaris.php?action=save_surat_masuk', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success && res.id) {
        obj.id = res.id;
        let up = JSON.parse(localStorage.getItem('kkn_surat_masuk_list') || '[]');
        const uIdx = up.findIndex(l => String(l.id) === String(uniqueId));
        if (uIdx !== -1) up[uIdx].id = res.id;
        localStorage.setItem('kkn_surat_masuk_list', JSON.stringify(up));
        dataSuratMasukList = up;
        renderSuratMasukTable(up);
      }
    }).catch(() => {});

  resetFormSuratMasuk();
}

function editSuratMasukItem(idx) {
  const item = dataSuratMasukList[idx];
  if (!item) return;
  document.getElementById('suratMasukId').value = item.id || '0';
  document.getElementById('suratMasukTanggal').value = item.tanggal_terima || '';
  document.getElementById('suratMasukNomor').value = item.nomor_surat || '';
  document.getElementById('suratMasukPengirim').value = item.pengirim || '';
  document.getElementById('suratMasukPerihal').value = item.perihal || '';
  document.getElementById('suratMasukKeterangan').value = item.keterangan || '';
  document.getElementById('suratMasukSubmitBtn').innerHTML = '<i class="bi bi-check-circle me-1"></i> Update Surat Masuk';
}

function hapusSuratMasukItem(idx) {
  const item = dataSuratMasukList[idx];
  if (!item) return;
  if (!confirm('Hapus agenda surat masuk ini?')) return;

  let list = JSON.parse(localStorage.getItem('kkn_surat_masuk_list') || '[]');
  list = list.filter(l => String(l.id) !== String(item.id));
  localStorage.setItem('kkn_surat_masuk_list', JSON.stringify(list));
  dataSuratMasukList = list;
  renderSuratMasukTable(list);

  if (item.id) {
    const fd = new FormData();
    fd.append('id', item.id);
    fetch('proses_sekretaris.php?action=delete_surat_masuk', { method: 'POST', body: fd });
  }
}

// === FITUR DIREKTORI KONTAK HUMAS & STAKEHOLDER ===
let dataKontakHumasList = [];

function bukaModalKontakHumas() {
  const modalEl = document.getElementById('kontakHumasModal');
  if (!modalEl) return;
  const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
  modal.show();

  loadKontakHumasList();
}

function resetFormKontakHumas() {
  const form = document.getElementById('kontakHumasForm');
  if (form) form.reset();
  document.getElementById('kontakHumasId').value = '0';
  document.getElementById('kontakHumasSubmitBtn').innerHTML = '<i class="bi bi-save me-1"></i> Simpan Kontak Humas';
}

function loadKontakHumasList() {
  const local = JSON.parse(localStorage.getItem('kkn_kontak_humas_list') || '[]');
  fetch('proses_kontak_humas.php?action=list')
    .then(r => r.json())
    .then(r => {
      if (r.success && Array.isArray(r.data) && r.data.length > 0) {
        const map = new Map();
        local.forEach((item, idx) => map.set(item.id ? 'id_' + item.id : 'loc_' + idx, item));
        r.data.forEach(item => map.set('id_' + item.id, item));
        const combined = Array.from(map.values());
        localStorage.setItem('kkn_kontak_humas_list', JSON.stringify(combined));
        dataKontakHumasList = combined;
        renderKontakHumasTable(combined);
      } else {
        dataKontakHumasList = local;
        renderKontakHumasTable(local);
      }
    })
    .catch(() => {
      dataKontakHumasList = local;
      renderKontakHumasTable(local);
    });
}

function renderKontakHumasTable(list) {
  const tbody = document.getElementById('kontakHumasTableBody');
  const badge = document.getElementById('badgeTotalKontakHumas');
  if (badge) badge.innerText = list.length + ' Kontak';
  if (!tbody) return;

  if (list.length === 0) {
    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4"><i class="bi bi-person-x fs-4 d-block mb-1"></i>Belum ada kontak stakeholder.</td></tr>';
    return;
  }

  tbody.innerHTML = list.map((item, idx) => {
    let cleanHp = (item.no_hp || '').replace(/[^0-9]/g, '');
    if (cleanHp.startsWith('0')) cleanHp = '62' + cleanHp.substring(1);
    const waUrl = cleanHp ? `https://wa.me/${cleanHp}` : '#';

    return `
      <tr>
        <td><strong>${item.nama || '-'}</strong><br><small class="text-muted">${item.keterangan || '-'}</small></td>
        <td><span class="badge bg-success-subtle text-success fw-bold px-2 py-1">${item.jabatan || '-'}</span><br><small class="text-muted">${item.wilayah || '-'}</small></td>
        <td><strong class="text-dark">${item.no_hp || '-'}</strong></td>
        <td class="text-end">
          <div class="d-flex justify-content-end align-items-center gap-1">
            ${cleanHp ? `<a href="${waUrl}" target="_blank" class="btn btn-sm btn-success rounded-pill px-2.5 py-1 text-white shadow-sm" title="Chat WhatsApp"><i class="bi bi-whatsapp me-1"></i>WA</a>` : ''}
            <button class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-2 py-1 shadow-sm" onclick="editKontakHumasItem(${idx})" title="Edit"><i class="bi bi-pencil-square"></i></button>
            <button class="btn btn-sm btn-outline-danger rounded-circle p-1 d-flex align-items-center justify-content-center shadow-sm" style="width:28px;height:28px;" onclick="hapusKontakHumasItem(${idx})" title="Hapus"><i class="bi bi-trash"></i></button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

function simpanKontakHumas(e) {
  e.preventDefault();
  const idVal = parseInt(document.getElementById('kontakHumasId').value || '0');
  const uniqueId = idVal > 0 ? idVal : Date.now();
  const obj = {
    id: uniqueId,
    nama: document.getElementById('kontakHumasNama').value,
    jabatan: document.getElementById('kontakHumasJabatan').value,
    no_hp: document.getElementById('kontakHumasNoHp').value,
    wilayah: document.getElementById('kontakHumasWilayah').value,
    keterangan: document.getElementById('kontakHumasKeterangan').value
  };

  let list = JSON.parse(localStorage.getItem('kkn_kontak_humas_list') || '[]');
  const idx = list.findIndex(l => String(l.id) === String(uniqueId));
  if (idx !== -1) {
    list[idx] = obj;
  } else {
    list.unshift(obj);
  }
  localStorage.setItem('kkn_kontak_humas_list', JSON.stringify(list));
  dataKontakHumasList = list;
  renderKontakHumasTable(list);

  const fd = new FormData();
  fd.append('id', idVal);
  fd.append('nama', obj.nama);
  fd.append('jabatan', obj.jabatan);
  fd.append('no_hp', obj.no_hp);
  fd.append('wilayah', obj.wilayah);
  fd.append('keterangan', obj.keterangan);

  fetch('proses_kontak_humas.php?action=save', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success && res.id) {
        obj.id = res.id;
        let up = JSON.parse(localStorage.getItem('kkn_kontak_humas_list') || '[]');
        const uIdx = up.findIndex(l => String(l.id) === String(uniqueId));
        if (uIdx !== -1) up[uIdx].id = res.id;
        localStorage.setItem('kkn_kontak_humas_list', JSON.stringify(up));
        dataKontakHumasList = up;
        renderKontakHumasTable(up);
      }
    }).catch(() => {});

  resetFormKontakHumas();
}

function editKontakHumasItem(idx) {
  const item = dataKontakHumasList[idx];
  if (!item) return;
  document.getElementById('kontakHumasId').value = item.id || '0';
  document.getElementById('kontakHumasNama').value = item.nama || '';
  document.getElementById('kontakHumasJabatan').value = item.jabatan || '';
  document.getElementById('kontakHumasNoHp').value = item.no_hp || '';
  document.getElementById('kontakHumasWilayah').value = item.wilayah || '';
  document.getElementById('kontakHumasKeterangan').value = item.keterangan || '';
  document.getElementById('kontakHumasSubmitBtn').innerHTML = '<i class="bi bi-check-circle me-1"></i> Update Kontak';
}

function hapusKontakHumasItem(idx) {
  const item = dataKontakHumasList[idx];
  if (!item) return;
  if (!confirm('Hapus kontak stakeholder ini?')) return;

  let list = JSON.parse(localStorage.getItem('kkn_kontak_humas_list') || '[]');
  list = list.filter(l => String(l.id) !== String(item.id));
  localStorage.setItem('kkn_kontak_humas_list', JSON.stringify(list));
  dataKontakHumasList = list;
  renderKontakHumasTable(list);

  if (item.id) {
    const fd = new FormData();
    fd.append('id', item.id);
    fetch('proses_kontak_humas.php?action=delete', { method: 'POST', body: fd });
  }
}
</script>
</body>
</html>