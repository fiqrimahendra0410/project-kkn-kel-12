<?php
// Matikan tampilan error PHP ke layar (biar gak bocor ke response JSON),
// tapi tetap dicatat kalau perlu debug manual lewat log server.
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Tangkap semua output yang gak sengaja kekirim sebelum JSON (misal notice/warning)
ob_start();

session_start();

header('Content-Type: application/json');

try {
    require 'koneksi.php';

    $rawUsername = trim($_POST['username'] ?? '');
    $username    = strtolower(str_replace(' ', '', $rawUsername));
    $password    = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Username dan password wajib diisi.']);
        exit;
    }

    // Batas percobaan login: max 5x gagal dalam 10 menit per username
    $cek = $pdo->prepare("
        SELECT COUNT(*) FROM login_log
        WHERE username = :u AND berhasil = 0 AND waktu > (NOW() - INTERVAL 10 MINUTE)
    ");
    $cek->execute([':u' => $username]);
    if ($cek->fetchColumn() >= 5) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Terlalu banyak percobaan gagal. Coba lagi 10 menit lagi.']);
        exit;
    }

    // Ambil seluruh akun dengan LEFT JOIN agar tidak ada akun yang terlewat
    $stmt = $pdo->query("
        SELECT ak.id AS akun_id, ak.username, ak.password_hash, ak.harus_ganti_pw, ak.role,
               a.id AS anggota_id, a.nama, a.jabatan, a.foto
        FROM akun_anggota ak
        LEFT JOIN anggota a ON a.id = ak.anggota_id
    ");
    $semuaAkun = $stmt->fetchAll();

    $akun = null;
    $cleanInput = preg_replace('/[^a-z0-9]/', '', strtolower($rawUsername));

    foreach ($semuaAkun as $item) {
        $cleanUsername = preg_replace('/[^a-z0-9]/', '', strtolower($item['username'] ?? ''));
        $namaLengkap   = $item['nama'] ?? $item['username'] ?? '';
        $cleanNama     = preg_replace('/[^a-z0-9]/', '', strtolower($namaLengkap));
        
        $namaParts = explode(' ', strtolower(trim($namaLengkap)));
        $namaDepan = preg_replace('/[^a-z0-9]/', '', $namaParts[0] ?? '');

        if (
            $cleanInput === $cleanUsername ||
            $cleanInput === $cleanNama ||
            ($cleanInput !== '' && $cleanUsername !== '' && (strpos($cleanInput, $cleanUsername) !== false || strpos($cleanUsername, $cleanInput) !== false)) ||
            ($cleanInput !== '' && $cleanNama !== '' && (strpos($cleanInput, $cleanNama) !== false || strpos($cleanNama, $cleanInput) !== false)) ||
            ($cleanInput !== '' && strlen($cleanInput) >= 3 && $cleanInput === $namaDepan)
        ) {
            $akun = $item;
            break;
        }
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $log = $pdo->prepare("INSERT INTO login_log (username, berhasil, ip_address) VALUES (:u, :b, :ip)");

    // Verifikasi Password: Cek password_hash standar atau fallback password default kkn2026
    $isPasswordValid = false;
    if ($akun) {
        if (password_verify($password, $akun['password_hash']) || $password === 'kkn2026') {
            $isPasswordValid = true;
        }
    }

    if (!$akun || !$isPasswordValid) {
        $log->execute([':u' => $username, ':b' => 0, ':ip' => $ip]);
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Username atau password salah.']);
        exit;
    }

    // Login berhasil
    $log->execute([':u' => $username, ':b' => 1, ':ip' => $ip]);

    $fotoMap = [
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

    $namaUserLogged = !empty($akun['nama']) ? $akun['nama'] : $akun['username'];

    $_SESSION['anggota_id']   = $akun['anggota_id'];
    $_SESSION['akun_id']      = $akun['akun_id'];
    $_SESSION['nama']         = $namaUserLogged;
    $_SESSION['jabatan']      = !empty($akun['jabatan']) ? $akun['jabatan'] : 'Anggota KKN';
    $_SESSION['foto']         = !empty($akun['foto']) ? $akun['foto'] : ($fotoMap[$namaUserLogged] ?? '');
    $_SESSION['role']         = $akun['role'] ?? 'admin';
    $_SESSION['login_time']   = time();

    ob_end_clean();
    echo json_encode([
        'success'        => true,
        'nama'           => $akun['nama'],
        'must_change_pw' => (bool) $akun['harus_ganti_pw'],
        'redirect'       => $akun['harus_ganti_pw'] ? 'ganti-password.php' : 'buku-lapangan-kkn.php',
    ]);
    exit;

} catch (Throwable $e) {
    // Kalau ada error apapun (koneksi DB gagal, query salah, dll),
    // tetap balas dalam format JSON yang rapi, bukan halaman error PHP mentah.
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server.',
        'debug'   => $e->getMessage(), // hapus baris ini kalau sudah live/production
    ]);
    exit;
}