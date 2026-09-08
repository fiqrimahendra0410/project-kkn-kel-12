<?php
require 'proteksi.php';
require 'koneksi.php';

$pesan = '';
$sukses = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baru   = $_POST['password_baru'] ?? '';
    $ulang  = $_POST['password_ulang'] ?? '';

    if (strlen($baru) < 6) {
        $pesan = 'Password baru minimal 6 karakter.';
    } elseif ($baru !== $ulang) {
        $pesan = 'Konfirmasi password tidak cocok.';
    } else {
        $hash = password_hash($baru, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE akun_anggota SET password_hash = :h, harus_ganti_pw = 0 WHERE anggota_id = :id");
        $upd->execute([':h' => $hash, ':id' => $_SESSION['anggota_id']]);
        $sukses = true;
        $pesan = 'Password berhasil diganti. Mengalihkan ke Buku Lapangan...';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ganti Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container" style="max-width:420px;margin-top:80px;">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-1">Halo, <?= htmlspecialchars($_SESSION['nama']) ?> 👋</h5>
            <p class="text-muted small mb-4">Ini login pertama kamu. Silakan ganti password default sebelum lanjut.</p>

            <?php if ($pesan): ?>
                <div class="alert <?= $sukses ? 'alert-success' : 'alert-danger' ?> small"><?= htmlspecialchars($pesan) ?></div>
            <?php endif; ?>

            <?php if (!$sukses): ?>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Password Baru</label>
                    <input type="password" name="password_baru" class="form-control" required minlength="6">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Ulangi Password Baru</label>
                    <input type="password" name="password_ulang" class="form-control" required minlength="6">
                </div>
                <button class="btn btn-primary w-100" type="submit">Simpan Password</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($sukses): ?>
<script>
setTimeout(() => { window.location.href = 'buku-lapangan-kkn.php'; }, 1800);
</script>
<?php endif; ?>
</body>
</html>