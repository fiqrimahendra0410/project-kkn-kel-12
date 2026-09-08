<?php
// hapus_materi_lama.php — Script sekali pakai, hapus SEMUA data sampel lama dari database
// Akses: http://localhost/project%20kkn/hapus_materi_lama.php
session_start();
require 'koneksi.php';

if (empty($_SESSION['anggota_id']) && empty($_SESSION['akun_id'])) {
    die('<div style="font-family:sans-serif;color:red;padding:40px;">❌ Harus login dulu di halaman utama, lalu buka URL ini lagi.</div>');
}

$deleted = [];
$errors  = [];

try {
    // 1. Hapus materi presentasi yang path_file-nya pakai img/ (sampel lama)
    $s = $pdo->prepare("DELETE FROM materi_presentasi WHERE path_file LIKE 'img/%'");
    $s->execute();
    $deleted['Materi Presentasi (sampel)'] = $s->rowCount();

    // 2. Hapus video kegiatan yang path_video-nya pakai img/ atau placeholder
    $s = $pdo->prepare("DELETE FROM video_kegiatan WHERE path_video LIKE 'img/%' OR path_video = '' OR path_video IS NULL");
    $s->execute();
    $deleted['Video Kegiatan (sampel)'] = $s->rowCount();

    // 3. Ambil semua program kerja foto yang path_foto-nya pakai img/ (bukan uploads/)
    $s = $pdo->prepare("DELETE FROM program_kerja_foto WHERE path_foto LIKE 'img/%' AND path_foto NOT LIKE 'uploads/%'");
    $s->execute();
    $deleted['Foto Program Kerja (sampel)'] = $s->rowCount();

    // 4. Hapus kegiatan foto yang path_foto-nya pakai img/
    $s = $pdo->prepare("DELETE FROM kegiatan_foto WHERE path_foto LIKE 'img/%' AND path_foto NOT LIKE 'uploads/%'");
    $s->execute();
    $deleted['Foto Kegiatan (sampel)'] = $s->rowCount();

    // Sisa data di tiap tabel
    $sisa = [];
    $tabels = ['materi_presentasi', 'video_kegiatan', 'program_kerja_foto', 'kegiatan_foto', 'program_kerja', 'kegiatan'];
    foreach ($tabels as $t) {
        try {
            $sisa[$t] = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        } catch (Exception $e) {
            $sisa[$t] = 'N/A';
        }
    }

} catch (Exception $e) {
    $errors[] = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Hapus Data Sampel Lama</title>
<style>
* { box-sizing: border-box; }
body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; padding: 40px 20px; }
.wrap { max-width: 680px; margin: auto; }
.card { background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 20px; }
h2 { margin: 0 0 8px; font-size: 1.4rem; }
p.sub { color: #64748b; font-size: 0.9rem; margin: 0 0 24px; }
table { width: 100%; border-collapse: collapse; }
th { background: #f1f5f9; color: #475569; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.05em; padding: 10px 14px; text-align: left; }
td { padding: 11px 14px; border-top: 1px solid #f1f5f9; font-size: 0.9rem; }
.pill { display: inline-block; padding: 3px 12px; border-radius: 999px; font-size: 0.8rem; font-weight: 600; }
.red  { background: #fee2e2; color: #dc2626; }
.green{ background: #dcfce7; color: #16a34a; }
.gray { background: #f1f5f9; color: #475569; }
.btn  { display: inline-block; margin-top: 24px; background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; padding: 12px 28px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 0.95rem; }
.err  { background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 12px 16px; color: #dc2626; font-size: 0.88rem; margin-bottom: 12px; }
</style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h2>🗑️ Hapus Data Sampel Lama</h2>
        <p class="sub">Script ini menghapus semua data sampel/dummy yang path file-nya masih menggunakan <code>img/...</code> (bukan file upload asli).</p>

        <?php foreach ($errors as $e): ?>
        <div class="err">⚠️ <?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <table>
            <tr><th>Tabel</th><th>Dihapus</th></tr>
            <?php foreach ($deleted as $label => $n): ?>
            <tr>
                <td><?= $label ?></td>
                <td><span class="pill <?= $n > 0 ? 'red' : 'green' ?>"><?= $n > 0 ? "✓ $n baris dihapus" : "Sudah bersih" ?></span></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <br>
        <strong>Sisa data di database:</strong>
        <table style="margin-top:10px;">
            <tr><th>Tabel</th><th>Jumlah Baris</th></tr>
            <?php foreach ($sisa as $t => $n): ?>
            <tr>
                <td><code><?= $t ?></code></td>
                <td><span class="pill <?= $n > 0 ? 'green' : 'gray' ?>"><?= $n ?> baris</span></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <p style="font-size:0.82rem;color:#94a3b8;margin-top:16px;">✅ Kode auto-insert sampel sudah dihapus dari sistem — data tidak akan muncul lagi setelah refresh.</p>
        <a href="index.php#program-kerja" class="btn">← Kembali ke Halaman Utama</a>
    </div>
</div>
</body>
</html>
