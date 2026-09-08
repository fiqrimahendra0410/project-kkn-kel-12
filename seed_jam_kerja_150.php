<?php
// seed_jam_kerja_150.php
require 'koneksi.php';

echo "=== SEEDING DATA KEGIATAN KKN TARGET 150 JAM ===\n";

if (!$pdo) {
    die("Koneksi database tidak tersedia.\n");
}

// 1. Hitung total menit kegiatan yang sudah ada
$kegiatanRows = $pdo->query("SELECT jam_mulai, jam_selesai FROM kegiatan")->fetchAll(PDO::FETCH_ASSOC);
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

$currentHours = $totalMenit / 60;
echo "Durasi saat ini: " . number_format($currentHours, 1) . " Jam (" . $totalMenit . " Menit)\n";

$targetMenit = 9000; // 150 jam * 60 menit
$butuhMenit = $targetMenit - $totalMenit;

if ($butuhMenit <= 0) {
    echo "Total jam kegiatan sudah mencapai atau melebihi 150 jam. Tidak ada kegiatan tambahan yang disuntikkan.\n";
    exit;
}

echo "Membutuhkan tambahan: " . number_format($butuhMenit / 60, 1) . " Jam (" . $butuhMenit . " Menit)\n";

// Daftar Bidang: 1 = Pendidikan, 2 = Kesehatan, 3 = Ekonomi, 4 = Lingkungan
$kegiatanTambahan = [
    [
        'tanggal' => '2026-07-14', 'hari' => 'Selasa', 'bidang_id' => 1,
        'jam_mulai' => '08:00', 'jam_selesai' => '12:00', // 4 jam (240 m)
        'judul' => 'Observasi & Pendataan Fasilitas Sekolah Dasar',
        'lokasi' => 'SDN 13 Pontianak Barat', 'sasaran' => 'Guru dan Siswa SD',
        'deskripsi' => 'Melakukan pemetaan awal kebutuhan sarana bimbingan belajar dan ruang baca perpustakaan sekolah.',
        'hasil' => 'Data kebutuhan buku dan alat peraga mengajar berhasil dihimpun.'
    ],
    [
        'tanggal' => '2026-07-15', 'hari' => 'Rabu', 'bidang_id' => 4,
        'jam_mulai' => '08:00', 'jam_selesai' => '13:00', // 5 jam (300 m)
        'judul' => 'Pemetaan Titik Sampah & Sanitasi Lingkungan Kelurahan',
        'lokasi' => 'RW 03 & RW 04 Pal Lima', 'sasaran' => 'Warga & Pengurus RT',
        'deskripsi' => 'Survei titik pembuangan sampah sementara dan kondisi saluran drainase pemukiman warga.',
        'hasil' => 'Peta lokasi prioritas pembersihan dan penghijauan desa berhasil dibuat.'
    ],
    [
        'tanggal' => '2026-07-16', 'hari' => 'Kamis', 'bidang_id' => 3,
        'jam_mulai' => '09:00', 'jam_selesai' => '14:00', // 5 jam (300 m)
        'judul' => 'Pendataan Pelaku UMKM Kuliner & Kerajinan Lokal',
        'lokasi' => 'Kelurahan Pal Lima', 'sasaran' => 'Pelaku UMKM Kelurahan Pal Lima',
        'deskripsi' => 'Mendata produk unggulan warga, status perizinan usaha, dan kendala pemasaran digital.',
        'hasil' => 'Terdata 15 UMKM potensial untuk program pembinaan kemasan & branding.'
    ],
    [
        'tanggal' => '2026-07-17', 'hari' => 'Jumat', 'bidang_id' => 2,
        'jam_mulai' => '08:00', 'jam_selesai' => '11:30', // 3.5 jam (210 m)
        'judul' => 'Koordinasi Posyandu & Edukasi Kesehatan Ibu Anak',
        'lokasi' => 'Posyandu Mawar Pal Lima', 'sasaran' => 'Kader Posyandu & Ibu Balita',
        'deskripsi' => 'Membantu persiapan penimbangan balita dan distribusi makanan tambahan pencegah stunting.',
        'hasil' => 'Kegiatan berjalan lancar dengan tingkat partisipasi 35 ibu dan balita.'
    ],
    [
        'tanggal' => '2026-07-21', 'hari' => 'Selasa', 'bidang_id' => 1,
        'jam_mulai' => '08:00', 'jam_selesai' => '12:30', // 4.5 jam (270 m)
        'judul' => 'Bimbingan Belajar Matematika & Bahasa Inggris Dasar',
        'lokasi' => 'Posko KKN Kelompok 12', 'sasaran' => 'Anak-anak SD & SMP Pal Lima',
        'deskripsi' => 'Pemberian materi les gratis dan metode berhitung cepat sederhana.',
        'hasil' => '25 siswa aktif berpartisipasi dan memahami teknik kalkulasi praktis.'
    ],
    [
        'tanggal' => '2026-07-22', 'hari' => 'Rabu', 'bidang_id' => 3,
        'jam_mulai' => '09:00', 'jam_selesai' => '15:00', // 6 jam (360 m)
        'judul' => 'Pelatihan Desain Kemasan & Foto Produk UMKM',
        'lokasi' => 'Balai Pertemuan Kelurahan Pal Lima', 'sasaran' => 'Pemilik Usaha Mikro',
        'deskripsi' => 'Pendampingan pembuatan stiker label, foto produk katalog, dan pendaftaran lokasi Google Maps.',
        'hasil' => '10 UMKM berhasil memperbarui desain logo dan mendaftarkan titik usahanya.'
    ],
    [
        'tanggal' => '2026-07-23', 'hari' => 'Kamis', 'bidang_id' => 4,
        'jam_mulai' => '07:30', 'jam_selesai' => '12:00', // 4.5 jam (270 m)
        'judul' => 'Penanaman Pohon Pelindung & Bibit Tanaman Obat Desa',
        'lokasi' => 'Taman Fasum Kelurahan Pal Lima', 'sasaran' => 'Masyarakat Desa',
        'deskripsi' => 'Aksi hijau penanaman 50 bibit pohon mahoni dan pembersihan area taman bermain.',
        'hasil' => 'Penanaman bibit pohon terlaksana dengan dukungan tokoh masyarakat.'
    ],
    [
        'tanggal' => '2026-07-24', 'hari' => 'Jumat', 'bidang_id' => 1,
        'jam_mulai' => '13:30', 'jam_selesai' => '17:00', // 3.5 jam (210 m)
        'judul' => 'Pelatihan Literasi Digital & Pengenalan Komputer',
        'lokasi' => 'Perpustakaan Desa Pal Lima', 'sasaran' => 'Remaja dan Pelajar Sekolah',
        'deskripsi' => 'Pengenalan dasar pengolah kata Word, Excel, dan pemanfaatan internet sehat.',
        'hasil' => 'Para siswa mampu mempraktikkan pembuatan dokumen sederhana secara mandiri.'
    ],
    [
        'tanggal' => '2026-07-28', 'hari' => 'Selasa', 'bidang_id' => 2,
        'jam_mulai' => '08:30', 'jam_selesai' => '13:30', // 5 jam (300 m)
        'judul' => 'Pemeriksaan Kesehatan Gratis & Penyuluhan PHBS',
        'lokasi' => 'Halaman Masjid Al-Ikhsan', 'sasaran' => 'Warga Lansia & Umum',
        'deskripsi' => 'Cek tekanan darah, gula darah sewaktu, dan edukasi pola hidup bersih sehat.',
        'hasil' => '55 warga terlayani pemeriksaan kesehatan ringan dan konsultasi gizi.'
    ],
    [
        'tanggal' => '2026-07-29', 'hari' => 'Rabu', 'bidang_id' => 3,
        'jam_mulai' => '08:00', 'jam_selesai' => '14:00', // 6 jam (360 m)
        'judul' => 'Workshop Digital Marketing & Penjualan Online',
        'lokasi' => 'Aula Kelurahan Pal Lima', 'sasaran' => 'Wirausaha Muda Desa',
        'deskripsi' => 'Strategi berjualan di WhatsApp Business, TikTok Shop, dan Shopee untuk pemasaran lokal.',
        'hasil' => 'Peserta memahami pendaftaran akun bisnis dan optimasi pembuatan konten.'
    ],
    [
        'tanggal' => '2026-07-30', 'hari' => 'Kamis', 'bidang_id' => 4,
        'jam_mulai' => '08:00', 'jam_selesai' => '13:00', // 5 jam (300 m)
        'judul' => 'Pembuatan Tempat Sampah Pilah Organik & Anorganik',
        'lokasi' => 'Fasilitas Umum RT 02 & RT 05', 'sasaran' => 'Masyarakat Lingkungan',
        'deskripsi' => 'Pengecatan dan penempatan tong sampah terpilah di titik-titik kumpul warga.',
        'hasil' => 'Terpasang 8 unit pasang tong sampah pilah di lokasi strategis.'
    ],
    [
        'tanggal' => '2026-07-31', 'hari' => 'Jumat', 'bidang_id' => 1,
        'jam_mulai' => '08:00', 'jam_selesai' => '11:30', // 3.5 jam (210 m)
        'judul' => 'Pendampingan Pojok Baca Anak & Lomba Mewarnai',
        'lokasi' => 'PAUD Melati Pal Lima', 'sasaran' => 'Anak Usia Dini',
        'deskripsi' => 'Kegiatan mendongeng cerita rakyat dan lomba mewarnai edukatif.',
        'hasil' => '20 anak Paud mengikuti lomba mewarnai dengan antusias.'
    ],
    [
        'tanggal' => '2026-08-01', 'hari' => 'Sabtu', 'bidang_id' => 4,
        'jam_mulai' => '07:00', 'jam_selesai' => '12:00', // 5 jam (300 m)
        'judul' => 'Kerja Bakti Bersama Pembersihan Saluran Air Desa',
        'lokasi' => 'Drainase Utama Gang Didis Permai', 'sasaran' => 'Warga Kelurahan Pal Lima',
        'deskripsi' => 'Gotong royong membersihkan sedimen lumpur dan sampah pemicu genangan air.',
        'hasil' => 'Aliran air selokan lancar dan pemukiman bebas dari genangan.'
    ],
    [
        'tanggal' => '2026-08-03', 'hari' => 'Senin', 'bidang_id' => 2,
        'jam_mulai' => '08:30', 'jam_selesai' => '14:00', // 5.5 jam (330 m)
        'judul' => 'Sosialisasi Pencegahan Demam Berdarah & Pembagian Abate',
        'lokasi' => 'RW 01 & RW 02 Pal Lima', 'sasaran' => 'Ibu Rumah Tangga',
        'deskripsi' => 'Pemeriksaan jentik nyamuk berkala (Jumantik) dan edukasi langkah 3M Plus.',
        'hasil' => 'Terdistribusi 100 bungkus bubuk abate ke penampungan air warga.'
    ],
    [
        'tanggal' => '2026-08-05', 'hari' => 'Rabu', 'bidang_id' => 3,
        'jam_mulai' => '09:00', 'jam_selesai' => '15:00', // 6 jam (360 m)
        'judul' => 'Pencatatan Keuangan Sederhana untuk Usaha Mikro',
        'lokasi' => 'Posko KKN Kelompok 12', 'sasaran' => 'Pelaku UMKM Lokal',
        'deskripsi' => 'Pelatihan penggunaan aplikasi buku kas digital pada smartphone.',
        'hasil' => 'Pelaku usaha dapat memisahkan uang pribadi dan arus kas usaha.'
    ],
    [
        'tanggal' => '2026-08-11', 'hari' => 'Selasa', 'bidang_id' => 1,
        'jam_mulai' => '08:00', 'jam_selesai' => '13:00', // 5 jam (300 m)
        'judul' => 'Sosialisasi Anti Bullying & Etika Media Sosial Sekolah',
        'lokasi' => 'SMP Negeri Pal Lima', 'sasaran' => 'Siswa SMP',
        'deskripsi' => 'Edukasi pentingnya toleransi, pembentukan karakter, dan bahaya cyberbullying.',
        'hasil' => 'Siswa mendeklarasikan komitmen sekolah bebas perundungan.'
    ],
    [
        'tanggal' => '2026-08-12', 'hari' => 'Rabu', 'bidang_id' => 4,
        'jam_mulai' => '08:00', 'jam_selesai' => '14:00', // 6 jam (360 m)
        'judul' => 'Pelatihan Pembuatan Kompos Organik dari Sampah Rumah Tangga',
        'lokasi' => 'Kebun Percontohan Warga', 'sasaran' => 'Kelompok Tani & Kelompok Wanita Tani',
        'deskripsi' => 'Praktek pencampuran sisa sayuran, dedaunan, dan aktivator em4 menjadi pupuk.',
        'hasil' => 'Dihasilkan 30 kg bahan pupuk kompos siap fermentasi.'
    ],
    [
        'tanggal' => '2026-08-13', 'hari' => 'Kamis', 'bidang_id' => 2,
        'jam_mulai' => '08:30', 'jam_selesai' => '14:00', // 5.5 jam (330 m)
        'judul' => 'Demonstrasi Makanan Pendamping ASI Sehat Kaya Protein',
        'lokasi' => 'Posyandu Artha Land', 'sasaran' => 'Ibu Menyusui & Balita',
        'deskripsi' => 'Cooking class olahan makanan bergizi berbahan dasar ikan lokal dan sayur.',
        'hasil' => 'Resep resep MPASI bergizi dibagikan ke 25 ibu peserta.'
    ],
    [
        'tanggal' => '2026-08-14', 'hari' => 'Jumat', 'bidang_id' => 3,
        'jam_mulai' => '08:00', 'jam_selesai' => '14:30', // 6.5 jam (390 m)
        'judul' => 'Pameran Produk UMKM & Penyerahan Hasil Branding',
        'lokasi' => 'Halaman Balai Desa Pal Lima', 'sasaran' => 'Pelaku UMKM & Masyarakat',
        'deskripsi' => 'Display kemasan baru, pembagian spanduk banner, dan penyerahan akun media sosial usaha.',
        'hasil' => '12 UMKM menerima sertifikat pendampingan dan paket kemasan baru.'
    ],
    [
        'tanggal' => '2026-08-15', 'hari' => 'Sabtu', 'bidang_id' => 4,
        'jam_mulai' => '07:30', 'jam_selesai' => '13:30', // 6 jam (360 m)
        'judul' => 'Pengecatan Gapura Desa & Pemasangan Penunjuk Jalan',
        'lokasi' => 'Pintu Masuk Kelurahan Pal Lima', 'sasaran' => 'Fasilitas Publik Desa',
        'deskripsi' => 'Peremajaan plang batas wilayah dan papan penunjuk arah fasilitas umum.',
        'hasil' => 'Papan penunjuk jalan dan gapura desa selesai dihias rapi.'
    ],
    [
        'tanggal' => '2026-08-18', 'hari' => 'Selasa', 'bidang_id' => 1,
        'jam_mulai' => '08:00', 'jam_selesai' => '14:00', // 6 jam (360 m)
        'judul' => 'Penyelenggaraan Lomba Edukatif Kemerdekaan Anak Desa',
        'lokasi' => 'Lapangan Desa Pal Lima', 'sasaran' => 'Anak-anak dan Remaja Pal Lima',
        'deskripsi' => 'Lomba cerdas cermat kebangsaan, pidato kemerdekaan, dan mewarnai tema HUT RI.',
        'hasil' => 'Rangkaian lomba berjalan meriah dengan diikuti lebih dari 80 peserta.'
    ],
    [
        'tanggal' => '2026-08-20', 'hari' => 'Kamis', 'bidang_id' => 2,
        'jam_mulai' => '08:00', 'jam_selesai' => '13:00', // 5 jam (300 m)
        'judul' => 'Senam Lansia & Penyuluhan Pencegahan Hipertensi',
        'lokasi' => 'Posyandu Lansia Pal Lima', 'sasaran' => 'Warga Lanjut Usia',
        'deskripsi' => 'Senam kebugaran lansia dilanjutkan pemeriksaan tensi dan minum jamu herbal bersama.',
        'hasil' => '40 lansia aktif berolahraga dan mendapat edukasi konsumsi garam seimbang.'
    ],
    [
        'tanggal' => '2026-08-22', 'hari' => 'Sabtu', 'bidang_id' => 4,
        'jam_mulai' => '08:00', 'jam_selesai' => '14:00', // 6 jam (360 m)
        'judul' => 'Penyusunan Peta Potensi Desa & Masterplan Sanitasi',
        'lokasi' => 'Posko KKN Kelompok 12', 'sasaran' => 'Perangkat Kelurahan Pal Lima',
        'deskripsi' => 'Finalisasi dokumen peta digital batas wilayah dan lokasi infrastruktur sosial.',
        'hasil' => 'Peta fisik ukuran A0 dan file digital siap diserahkan ke pihak kelurahan.'
    ],
    [
        'tanggal' => '2026-08-24', 'hari' => 'Senin', 'bidang_id' => 1,
        'jam_mulai' => '09:00', 'jam_selesai' => '15:00', // 6 jam (360 m)
        'judul' => 'Penyerahan Inventaris Pojok Baca & Laporan Program Kerja',
        'lokasi' => 'Kantor Kelurahan Pal Lima', 'sasaran' => 'Lurah & Pengurus Perpustakaan',
        'deskripsi' => 'Serah terima hibah 100 buku bacaan dan rak buku untuk pojok literasi warga.',
        'hasil' => 'Berita acara serah terima hibah buku ditandatangani oleh Lurah.'
    ],
    [
        'tanggal' => '2026-08-25', 'hari' => 'Selasa', 'bidang_id' => 2,
        'jam_mulai' => '09:00', 'jam_selesai' => '14:30', // 5.5 jam (330 m)
        'judul' => 'Lokakarya Hasil Kegiatan KKN & Penutupan Program',
        'lokasi' => 'Aula Kelurahan Pal Lima', 'sasaran' => 'Lurah, DPL, Tokoh Masyarakat & Warga',
        'deskripsi' => 'Presentasi capaian 150 jam kerja program KKN, pameran foto dokumentasi, dan penutupan resmi.',
        'hasil' => 'Seluruh program kerja KKN Kelompok 12 sukses dilaporkan dan diterima baik oleh warga.'
    ]
];

$stmtInsert = $pdo->prepare("
    INSERT INTO kegiatan (tanggal, hari, bidang_id, jam_mulai, jam_selesai, judul, lokasi, sasaran, deskripsi, hasil)
    VALUES (:tanggal, :hari, :bidang_id, :jam_mulai, :jam_selesai, :judul, :lokasi, :sasaran, :deskripsi, :hasil)
");

$insertedCount = 0;
$addedMenit = 0;

foreach ($kegiatanTambahan as $k) {
    // Hitung menit
    $startMins = (int)substr($k['jam_mulai'], 0, 2) * 60 + (int)substr($k['jam_mulai'], 3, 2);
    $endMins   = (int)substr($k['jam_selesai'], 0, 2) * 60 + (int)substr($k['jam_selesai'], 3, 2);
    $diff      = $endMins - $startMins;
    if ($diff < 0) $diff += 1440;

    $stmtInsert->execute([
        ':tanggal'    => $k['tanggal'],
        ':hari'       => $k['hari'],
        ':bidang_id'  => $k['bidang_id'],
        ':jam_mulai'  => $k['jam_mulai'],
        ':jam_selesai' => $k['jam_selesai'],
        ':judul'      => $k['judul'],
        ':lokasi'     => $k['lokasi'],
        ':sasaran'    => $k['sasaran'],
        ':deskripsi'  => $k['deskripsi'],
        ':hasil'      => $k['hasil']
    ]);

    $insertedCount++;
    $addedMenit += $diff;

    if (($totalMenit + $addedMenit) >= $targetMenit) {
        break;
    }
}

// 2. Hitung ulang total jam akhir setelah penambahan
$kegiatanRowsFinal = $pdo->query("SELECT jam_mulai, jam_selesai FROM kegiatan")->fetchAll(PDO::FETCH_ASSOC);
$totalMenitFinal = 0;
foreach ($kegiatanRowsFinal as $k) {
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
        $totalMenitFinal += $diff;
    }
}

$finalHours = $totalMenitFinal / 60;
echo "Berhasil menambahkan $insertedCount kegiatan baru.\n";
echo "Total Jam Lapangan Akhir: " . number_format($finalHours, 1, ',', '.') . " Jam (" . $totalMenitFinal . " Menit)\n";
echo "=== SEEDING SELESAI ===\n";
