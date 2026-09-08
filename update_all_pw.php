<?php
// update_all_pw.php — Skrip utilitas pembaruan kata sandi seluruh anggota KKN Kelompok 12

require 'koneksi.php';

try {
    $defaultPassword = 'kkn2026';
    $passwordHash    = password_hash($defaultPassword, PASSWORD_BCRYPT);

    // Update kata sandi seluruh anggota akun
    $stmt = $pdo->prepare("UPDATE akun_anggota SET password_hash = :hash, harus_ganti_pw = 0");
    $stmt->execute([':hash' => $passwordHash]);
    $jumlahBaris = $stmt->rowCount();

    // Hapus log percobaan login yang gagal sebelumnya
    $pdo->exec("DELETE FROM login_log");

    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Pembaruan Kata Sandi Berhasil</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    </head>
    <body class="bg-light d-flex align-items-center justify-content-center min-vh-100 p-3">
        <div class="card border-0 shadow-lg p-4 text-center" style="max-width: 450px; border-radius: 1.25rem;">
            <div class="rounded-circle bg-success-subtle text-success p-3 mx-auto mb-3" style="width: 70px; height: 70px;">
                <i class="bi bi-shield-check fs-1"></i>
            </div>
            <h4 class="fw-bold text-dark mb-2">Kata Sandi Berhasil Diperbarui</h4>
            <p class="text-muted small mb-3">
                Seluruh akun anggota KKN Kelompok 12 (<strong><?= $jumlahBaris ?> Akun</strong>) kini menggunakan kata sandi seragam:
            </p>
            <div class="p-3 bg-light rounded-3 border mb-3">
                <span class="badge bg-primary fs-6 px-3 py-2">kkn2026</span>
            </div>
            <a href="index.php" class="btn btn-primary rounded-pill px-4 fw-semibold">
                Kembali ke Halaman Login
            </a>
        </div>
    </body>
    </html>
    <?php

} catch (PDOException $e) {
    http_response_code(500);
    echo "Gagal memperbarui kata sandi: " . htmlspecialchars($e->getMessage());
}
