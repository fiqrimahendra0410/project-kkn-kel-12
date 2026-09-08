<?php
/**
 * buat_akun.php
 * -------------------------------------------------------------
 * SCRIPT SEKALI JALAN untuk generate akun login semua anggota
 * yang belum punya akun di tabel akun_anggota.
 *
 * Username  : dari nama anggota (huruf kecil, tanpa spasi)
 * Password  : default "kkn2026" untuk semua (WAJIB diganti nanti)
 *
 * CARA PAKAI:
 * 1. Pastikan tabel anggota sudah diisi nama asli (bukan "Nama Anggota" placeholder)
 * 2. Buka file ini lewat browser: http://localhost/project-kkn/buat_akun.php
 * 3. Catat / screenshot daftar username+password yang tampil
 * 4. Bagikan ke masing-masing teman KKN lewat WA pribadi (JANGAN di grup)
 * 5. HAPUS file ini dari server setelah selesai dipakai
 * -------------------------------------------------------------
 */

require 'koneksi.php';

$password_default = 'kkn2026';
$hash_default = password_hash($password_default, PASSWORD_DEFAULT);

$stmt = $pdo->query("
    SELECT a.id, a.nama
    FROM anggota a
    LEFT JOIN akun_anggota ak ON ak.anggota_id = a.id
    WHERE ak.id IS NULL
    ORDER BY a.urutan_carousel
");
$anggotaBaru = $stmt->fetchAll();

if (empty($anggotaBaru)) {
    echo "<p>Semua anggota sudah punya akun. Tidak ada yang perlu dibuat.</p>";
    exit;
}

$usernameDipakai = $pdo->query("SELECT username FROM akun_anggota")->fetchAll(PDO::FETCH_COLUMN);

function buatUsername($nama, &$usernameDipakai) {
    $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nama));
    if ($base === '') $base = 'anggota';
    $username = $base;
    $i = 1;
    while (in_array($username, $usernameDipakai)) {
        $i++;
        $username = $base . $i;
    }
    $usernameDipakai[] = $username;
    return $username;
}

$insert = $pdo->prepare("
    INSERT INTO akun_anggota (anggota_id, username, password_hash, harus_ganti_pw)
    VALUES (:anggota_id, :username, :password_hash, 1)
");

$hasil = [];

foreach ($anggotaBaru as $a) {
    $username = buatUsername($a['nama'], $usernameDipakai);
    $insert->execute([
        ':anggota_id'    => $a['id'],
        ':username'      => $username,
        ':password_hash' => $hash_default,
    ]);
    $hasil[] = ['nama' => $a['nama'], 'username' => $username];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Akun Berhasil Dibuat</title>
    <style>
        body{font-family:system-ui,sans-serif;max-width:700px;margin:40px auto;padding:0 20px;}
        table{width:100%;border-collapse:collapse;margin-top:16px;}
        th,td{padding:10px 14px;border:1px solid #ddd;text-align:left;font-size:.9rem;}
        th{background:#0d6efd;color:#fff;}
        .warn{background:#fff3cd;border:1px solid #ffe69c;padding:14px 18px;border-radius:8px;margin-top:20px;font-size:.9rem;}
    </style>
</head>
<body>
    <h2>✅ <?= count($hasil) ?> akun berhasil dibuat</h2>
    <p>Password default untuk semua: <b><?= $password_default ?></b> (wajib diganti saat login pertama)</p>
    <table>
        <tr><th>Nama</th><th>Username</th></tr>
        <?php foreach ($hasil as $h): ?>
        <tr><td><?= htmlspecialchars($h['nama']) ?></td><td><?= htmlspecialchars($h['username']) ?></td></tr>
        <?php endforeach; ?>
    </table>
    <div class="warn">
        ⚠️ Segera <b>hapus file buat_akun.php ini dari server</b> setelah selesai.
    </div>
</body>
</html>