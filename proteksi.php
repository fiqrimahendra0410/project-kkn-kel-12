<?php
// proteksi.php — WAJIB di-include paling atas di setiap halaman/endpoint yang butuh login

session_start();

function tolakAkses(string $alasan): void {
    // Deteksi apakah ini request AJAX/fetch (mengharapkan JSON), bukan navigasi browser biasa
    $isAjax = (
        (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    );

    if ($isAjax) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Sesi login berakhir, silakan login ulang.']);
        exit;
    }

    header('Location: index.php?login=' . $alasan);
    exit;
}

$batas_waktu = 7200; // 2 jam
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $batas_waktu) {
    $_SESSION = [];
    session_destroy();
    tolakAkses('expired');
}

if (!isset($_SESSION['anggota_id'])) {
    tolakAkses('required');
}

$_SESSION['login_time'] = time();

function tolakAksesViewer(): void {
    if (($_SESSION['role'] ?? '') === 'viewer') {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Akun Pelihat (Viewer) hanya dapat melihat data dan tidak diizinkan membuat, mengedit, atau menghapus data.']);
        exit;
    }
}