-- ========================================================
-- Backup Database KKN Kelompok 12 (InfinityFree MySQL Export)
-- Tanggal Ekspor: 27-08-2026 12:17:02 WIB
-- ========================================================

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `absensi`;
CREATE TABLE `absensi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kegiatan_id` INT NULL,
  `anggota_id` INT NULL,
  `status` TEXT NULL,
  `keterangan` TEXT NULL,
  `diperbarui_pada` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `akun_anggota`;
CREATE TABLE `akun_anggota` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `anggota_id` INT NULL,
  `username` TEXT NULL,
  `password_hash` TEXT NULL,
  `harus_ganti_pw` VARCHAR(255) NULL,
  `dibuat_pada` DATETIME NULL,
  `role` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `akun_anggota` (`id`, `anggota_id`, `username`, `password_hash`, `harus_ganti_pw`, `dibuat_pada`, `role`) VALUES ('1', '1', 'rizkitrisaputra', '$2y$10$sy3WEQ08p9yjvfC7y8d.8Op7XRDqwdThTRtdJevHm3JddZjxdDUha', '0', '2026-07-12 23:46:31', 'admin');
INSERT INTO `akun_anggota` (`id`, `anggota_id`, `username`, `password_hash`, `harus_ganti_pw`, `dibuat_pada`, `role`) VALUES ('2', '2', 'anggierahmawati', '$2y$10$sy3WEQ08p9yjvfC7y8d.8Op7XRDqwdThTRtdJevHm3JddZjxdDUha', '0', '2026-07-12 23:46:31', 'admin');
INSERT INTO `akun_anggota` (`id`, `anggota_id`, `username`, `password_hash`, `harus_ganti_pw`, `dibuat_pada`, `role`) VALUES ('3', '3', 'virahmanda', '$2y$10$sy3WEQ08p9yjvfC7y8d.8Op7XRDqwdThTRtdJevHm3JddZjxdDUha', '0', '2026-07-12 23:46:31', 'admin');
INSERT INTO `akun_anggota` (`id`, `anggota_id`, `username`, `password_hash`, `harus_ganti_pw`, `dibuat_pada`, `role`) VALUES ('4', '4', 'muhammadfiqrimahendra', '$2y$10$sy3WEQ08p9yjvfC7y8d.8Op7XRDqwdThTRtdJevHm3JddZjxdDUha', '0', '2026-07-12 23:46:31', 'admin');
INSERT INTO `akun_anggota` (`id`, `anggota_id`, `username`, `password_hash`, `harus_ganti_pw`, `dibuat_pada`, `role`) VALUES ('5', '5', 'adit', '$2y$10$sy3WEQ08p9yjvfC7y8d.8Op7XRDqwdThTRtdJevHm3JddZjxdDUha', '0', '2026-07-12 23:46:31', 'admin');
INSERT INTO `akun_anggota` (`id`, `anggota_id`, `username`, `password_hash`, `harus_ganti_pw`, `dibuat_pada`, `role`) VALUES ('6', '6', 'nisa', '$2y$10$sy3WEQ08p9yjvfC7y8d.8Op7XRDqwdThTRtdJevHm3JddZjxdDUha', '0', '2026-07-12 23:46:31', 'admin');
INSERT INTO `akun_anggota` (`id`, `anggota_id`, `username`, `password_hash`, `harus_ganti_pw`, `dibuat_pada`, `role`) VALUES ('7', '7', 'tiara', '$2y$10$sy3WEQ08p9yjvfC7y8d.8Op7XRDqwdThTRtdJevHm3JddZjxdDUha', '0', '2026-07-12 23:46:31', 'admin');
INSERT INTO `akun_anggota` (`id`, `anggota_id`, `username`, `password_hash`, `harus_ganti_pw`, `dibuat_pada`, `role`) VALUES ('8', '8', 'aliya', '$2y$10$sy3WEQ08p9yjvfC7y8d.8Op7XRDqwdThTRtdJevHm3JddZjxdDUha', '0', '2026-07-12 23:46:31', 'admin');
INSERT INTO `akun_anggota` (`id`, `anggota_id`, `username`, `password_hash`, `harus_ganti_pw`, `dibuat_pada`, `role`) VALUES ('9', '9', 'faturahman', '$2y$10$sy3WEQ08p9yjvfC7y8d.8Op7XRDqwdThTRtdJevHm3JddZjxdDUha', '0', '2026-07-12 23:46:31', 'admin');
INSERT INTO `akun_anggota` (`id`, `anggota_id`, `username`, `password_hash`, `harus_ganti_pw`, `dibuat_pada`, `role`) VALUES ('10', '10', 'diah', '$2y$10$sy3WEQ08p9yjvfC7y8d.8Op7XRDqwdThTRtdJevHm3JddZjxdDUha', '0', '2026-07-12 23:46:31', 'admin');
INSERT INTO `akun_anggota` (`id`, `anggota_id`, `username`, `password_hash`, `harus_ganti_pw`, `dibuat_pada`, `role`) VALUES ('11', '11', 'kania', '$2y$10$sy3WEQ08p9yjvfC7y8d.8Op7XRDqwdThTRtdJevHm3JddZjxdDUha', '0', '2026-07-21 16:12:11', 'viewer');

DROP TABLE IF EXISTS `anggota`;
CREATE TABLE `anggota` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` TEXT NULL,
  `jabatan` TEXT NULL,
  `divisi_panitia_id` INT NULL,
  `foto` TEXT NULL,
  `kelas` TEXT NULL,
  `urutan_carousel` INT NULL,
  `nim` TEXT NULL,
  `no_hp` TEXT NULL,
  `dibuat_pada` DATETIME NULL,
  `kata_kata` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `anggota` (`id`, `nama`, `jabatan`, `divisi_panitia_id`, `foto`, `kelas`, `urutan_carousel`, `nim`, `no_hp`, `dibuat_pada`, `kata_kata`) VALUES ('1', 'Rizki Tri Saputra', 'Ketua KKN', '1', 'img/rizki tri saputra.jpg', 'reguler', '1', NULL, NULL, '2026-07-12 23:36:09', '');
INSERT INTO `anggota` (`id`, `nama`, `jabatan`, `divisi_panitia_id`, `foto`, `kelas`, `urutan_carousel`, `nim`, `no_hp`, `dibuat_pada`, `kata_kata`) VALUES ('2', 'Anggi Rahmawati', 'Sekretaris', '2', 'img/Anggi Rahmawati.jpg', 'reguler', '2', NULL, NULL, '2026-07-12 23:36:09', '');
INSERT INTO `anggota` (`id`, `nama`, `jabatan`, `divisi_panitia_id`, `foto`, `kelas`, `urutan_carousel`, `nim`, `no_hp`, `dibuat_pada`, `kata_kata`) VALUES ('3', 'Virahmanda Abelia Ismaya', 'Divisi Acara', '3', 'img/Virahmanda Abelia Ismaya.jpg', 'reguler', '3', NULL, NULL, '2026-07-12 23:36:09', '');
INSERT INTO `anggota` (`id`, `nama`, `jabatan`, `divisi_panitia_id`, `foto`, `kelas`, `urutan_carousel`, `nim`, `no_hp`, `dibuat_pada`, `kata_kata`) VALUES ('4', 'Muhammad Fiqri Mahendra', 'Divisi Acara', '3', 'img/Muhammad Fiqri Mahendra.jpg', 'pekerja', '4', NULL, NULL, '2026-07-12 23:36:09', 'KKN (kerje2 ngape gk)');
INSERT INTO `anggota` (`id`, `nama`, `jabatan`, `divisi_panitia_id`, `foto`, `kelas`, `urutan_carousel`, `nim`, `no_hp`, `dibuat_pada`, `kata_kata`) VALUES ('5', 'Sebastianus Aditia', 'Divisi Humas', '4', 'img/Sebastianus Aditia.jpg', 'reguler', '5', NULL, NULL, '2026-07-12 23:36:09', 'Tetap semangat dan jangan menyerah walaupun kesulitan menantangmu');
INSERT INTO `anggota` (`id`, `nama`, `jabatan`, `divisi_panitia_id`, `foto`, `kelas`, `urutan_carousel`, `nim`, `no_hp`, `dibuat_pada`, `kata_kata`) VALUES ('6', 'Khairunisa Salsabila', 'Divisi Humas', '4', 'img/Khairunisa Salsabila.jpg', 'pekerja', '6', NULL, NULL, '2026-07-12 23:36:09', '');
INSERT INTO `anggota` (`id`, `nama`, `jabatan`, `divisi_panitia_id`, `foto`, `kelas`, `urutan_carousel`, `nim`, `no_hp`, `dibuat_pada`, `kata_kata`) VALUES ('7', 'Tiara Fitriani', 'Divisi PDD', '5', 'img/Tiara Fitriani.jpg', 'reguler', '7', NULL, NULL, '2026-07-12 23:36:09', '');
INSERT INTO `anggota` (`id`, `nama`, `jabatan`, `divisi_panitia_id`, `foto`, `kelas`, `urutan_carousel`, `nim`, `no_hp`, `dibuat_pada`, `kata_kata`) VALUES ('8', 'Siti Aliyah', 'Divisi PDD', '5', 'img/Siti Aliyah.jpg', 'pekerja', '8', NULL, NULL, '2026-07-12 23:36:09', '');
INSERT INTO `anggota` (`id`, `nama`, `jabatan`, `divisi_panitia_id`, `foto`, `kelas`, `urutan_carousel`, `nim`, `no_hp`, `dibuat_pada`, `kata_kata`) VALUES ('9', 'Fathurrahman', 'Divisi Perlengkapan', '6', 'img/Fathurrahman.jpg', 'reguler', '9', NULL, NULL, '2026-07-12 23:36:09', '');
INSERT INTO `anggota` (`id`, `nama`, `jabatan`, `divisi_panitia_id`, `foto`, `kelas`, `urutan_carousel`, `nim`, `no_hp`, `dibuat_pada`, `kata_kata`) VALUES ('10', 'Halimah Tusa''Diah', 'Divisi Perlengkapan', '6', 'img/Halimah Tusa''Diah.jpg', 'pekerja', '10', NULL, NULL, '2026-07-12 23:36:09', '');
INSERT INTO `anggota` (`id`, `nama`, `jabatan`, `divisi_panitia_id`, `foto`, `kelas`, `urutan_carousel`, `nim`, `no_hp`, `dibuat_pada`, `kata_kata`) VALUES ('11', 'Ibu Kania Khairunnisa, M.Psi., Psikolog', 'Dosen Pembimbing Lapangan (DPL)', NULL, 'img/default-avatar.png', 'reguler', '0', NULL, NULL, '2026-07-21 16:12:11', '');

DROP TABLE IF EXISTS `app_settings`;
CREATE TABLE `app_settings` (
  `key_name` TEXT NULL,
  `key_val` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `app_settings` (`key_name`, `key_val`) VALUES ('lokasi_prokja_seeded', '1');

DROP TABLE IF EXISTS `bidang_prokja`;
CREATE TABLE `bidang_prokja` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode` TEXT NULL,
  `nama` TEXT NULL,
  `icon_emoji` TEXT NULL,
  `warna_badge` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `bidang_prokja` (`id`, `kode`, `nama`, `icon_emoji`, `warna_badge`) VALUES ('1', 'pendidikan', 'Pendidikan', '', 'primary');
INSERT INTO `bidang_prokja` (`id`, `kode`, `nama`, `icon_emoji`, `warna_badge`) VALUES ('2', 'kesehatan', 'Kesehatan', '', 'danger');
INSERT INTO `bidang_prokja` (`id`, `kode`, `nama`, `icon_emoji`, `warna_badge`) VALUES ('3', 'ekonomi', 'Ekonomi', '', 'success');
INSERT INTO `bidang_prokja` (`id`, `kode`, `nama`, `icon_emoji`, `warna_badge`) VALUES ('4', 'lingkungan', 'Lingkungan', '', 'warning');
INSERT INTO `bidang_prokja` (`id`, `kode`, `nama`, `icon_emoji`, `warna_badge`) VALUES ('5', 'survei-lokasi', 'Survei Lokasi', NULL, NULL);
INSERT INTO `bidang_prokja` (`id`, `kode`, `nama`, `icon_emoji`, `warna_badge`) VALUES ('6', 'berkunjung-kekantor-lurah', 'Berkunjung Kekantor Lurah', NULL, NULL);
INSERT INTO `bidang_prokja` (`id`, `kode`, `nama`, `icon_emoji`, `warna_badge`) VALUES ('7', 'perkenalan-kelompok-kkn', 'Perkenalan Kelompok kkn', NULL, NULL);
INSERT INTO `bidang_prokja` (`id`, `kode`, `nama`, `icon_emoji`, `warna_badge`) VALUES ('8', 'vidioperkenalankelompokkkn12', 'Vidio Perkenalan Kelompok Kkn 12', NULL, NULL);
INSERT INTO `bidang_prokja` (`id`, `kode`, `nama`, `icon_emoji`, `warna_badge`) VALUES ('9', 'pelepasan-mahasiswa-kkn-2026', 'Pelepasan Mahasiswa Kkn 2026', NULL, NULL);
INSERT INTO `bidang_prokja` (`id`, `kode`, `nama`, `icon_emoji`, `warna_badge`) VALUES ('10', 'penyerahan-mahasiswa-kkn-oleh-', 'Penyerahan  Mahasiswa Kkn Oleh Dosen Pendamping Kepada Kepala Kelurahan  Pal lima', NULL, NULL);
INSERT INTO `bidang_prokja` (`id`, `kode`, `nama`, `icon_emoji`, `warna_badge`) VALUES ('11', 'pendampinganterpaduumkmberbasi', 'Pendampingan Terpadu UMKM Berbasis Potensi Lokal', NULL, NULL);
INSERT INTO `bidang_prokja` (`id`, `kode`, `nama`, `icon_emoji`, `warna_badge`) VALUES ('12', 'masyarakat', 'Masyarakat', NULL, NULL);
INSERT INTO `bidang_prokja` (`id`, `kode`, `nama`, `icon_emoji`, `warna_badge`) VALUES ('13', 'pembuatantempatpembakaransampa', 'Pembuatan Tempat Pembakaran Sampah Inserator', NULL, NULL);

DROP TABLE IF EXISTS `divisi_panitia`;
CREATE TABLE `divisi_panitia` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `divisi_panitia` (`id`, `nama`) VALUES ('1', 'Ketua');
INSERT INTO `divisi_panitia` (`id`, `nama`) VALUES ('2', 'Sekretaris');
INSERT INTO `divisi_panitia` (`id`, `nama`) VALUES ('3', 'Divisi Acara');
INSERT INTO `divisi_panitia` (`id`, `nama`) VALUES ('4', 'Divisi Humas');
INSERT INTO `divisi_panitia` (`id`, `nama`) VALUES ('5', 'Divisi PDD');
INSERT INTO `divisi_panitia` (`id`, `nama`) VALUES ('6', 'Divisi Perlengkapan');

DROP TABLE IF EXISTS `galeri_beranda`;
CREATE TABLE `galeri_beranda` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `path_foto` TEXT NULL,
  `kategori` TEXT NULL,
  `keterangan` TEXT NULL,
  `ukuran_tile` TEXT NULL,
  `urutan` INT NULL,
  `dibuat_pada` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `galeri_beranda` (`id`, `path_foto`, `kategori`, `keterangan`, `ukuran_tile`, `urutan`, `dibuat_pada`) VALUES ('1', 'img/20171130105645.jpg', 'lokasi', 'Lokasi & Desa', 'lg', '1', '2026-07-12 23:36:13');
INSERT INTO `galeri_beranda` (`id`, `path_foto`, `kategori`, `keterangan`, `ukuran_tile`, `urutan`, `dibuat_pada`) VALUES ('2', 'img/20171130105645.jpg', 'warga', 'Kegiatan Warga', 'md', '2', '2026-07-12 23:36:13');
INSERT INTO `galeri_beranda` (`id`, `path_foto`, `kategori`, `keterangan`, `ukuran_tile`, `urutan`, `dibuat_pada`) VALUES ('3', 'img/20171130105645.jpg', 'anak', 'Kegiatan Anak', 'sm', '3', '2026-07-12 23:36:13');
INSERT INTO `galeri_beranda` (`id`, `path_foto`, `kategori`, `keterangan`, `ukuran_tile`, `urutan`, `dibuat_pada`) VALUES ('4', 'img/20171130105645.jpg', 'anak', 'Kegiatan Anak', 'sm', '4', '2026-07-12 23:36:13');
INSERT INTO `galeri_beranda` (`id`, `path_foto`, `kategori`, `keterangan`, `ukuran_tile`, `urutan`, `dibuat_pada`) VALUES ('5', 'img/20171130105645.jpg', 'anak', 'Kegiatan Anak', 'lg', '5', '2026-07-12 23:36:13');
INSERT INTO `galeri_beranda` (`id`, `path_foto`, `kategori`, `keterangan`, `ukuran_tile`, `urutan`, `dibuat_pada`) VALUES ('6', 'img/20171130105645.jpg', 'anak', 'Kegiatan Anak', 'sm', '6', '2026-07-12 23:36:13');
INSERT INTO `galeri_beranda` (`id`, `path_foto`, `kategori`, `keterangan`, `ukuran_tile`, `urutan`, `dibuat_pada`) VALUES ('7', 'img/20171130105645.jpg', 'anak', 'Kegiatan Anak', 'sm', '7', '2026-07-12 23:36:13');
INSERT INTO `galeri_beranda` (`id`, `path_foto`, `kategori`, `keterangan`, `ukuran_tile`, `urutan`, `dibuat_pada`) VALUES ('8', 'img/20171130105645.jpg', 'anak', 'Kegiatan Anak', 'md', '8', '2026-07-12 23:36:13');

DROP TABLE IF EXISTS `informasi_posko`;
CREATE TABLE `informasi_posko` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_barang` TEXT NULL,
  `kategori` TEXT NULL,
  `penanggung_jawab` TEXT NULL,
  `keterangan` TEXT NULL,
  `dibuat_pada` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `informasi_posko` (`id`, `nama_barang`, `kategori`, `penanggung_jawab`, `keterangan`, `dibuat_pada`) VALUES ('1', 'Kompor', 'Perlengkapan Memasak', 'Diah', 'Peralatan masak posko', '2026-07-21 16:03:34');
INSERT INTO `informasi_posko` (`id`, `nama_barang`, `kategori`, `penanggung_jawab`, `keterangan`, `dibuat_pada`) VALUES ('2', 'Gas 3 kg', 'Perlengkapan Memasak', 'Diah', 'Kebutuhan konsumsi posko', '2026-07-21 16:03:34');
INSERT INTO `informasi_posko` (`id`, `nama_barang`, `kategori`, `penanggung_jawab`, `keterangan`, `dibuat_pada`) VALUES ('3', 'Dandang', 'Perlengkapan Memasak', 'Diah', 'Memasak nasi / mengukus', '2026-07-21 16:03:34');
INSERT INTO `informasi_posko` (`id`, `nama_barang`, `kategori`, `penanggung_jawab`, `keterangan`, `dibuat_pada`) VALUES ('4', 'Kuali', 'Perlengkapan Dapur', 'Fatur', 'Peralatan penggorengan', '2026-07-21 16:03:34');
INSERT INTO `informasi_posko` (`id`, `nama_barang`, `kategori`, `penanggung_jawab`, `keterangan`, `dibuat_pada`) VALUES ('5', 'Gelas & Piring', 'Peralatan Makan', 'Masing-masing', 'Milik pribadi anggota', '2026-07-21 16:03:34');
INSERT INTO `informasi_posko` (`id`, `nama_barang`, `kategori`, `penanggung_jawab`, `keterangan`, `dibuat_pada`) VALUES ('6', 'Galon Air', 'Konsumsi Air Minum', 'Nisa, Abel, Fikri', 'Menjaga pasokan air minum', '2026-07-21 16:03:34');
INSERT INTO `informasi_posko` (`id`, `nama_barang`, `kategori`, `penanggung_jawab`, `keterangan`, `dibuat_pada`) VALUES ('7', 'Pisau', 'Peralatan Dapur', 'Nisa, Adit', 'Peralatan potong bahan makanan', '2026-07-21 16:03:34');
INSERT INTO `informasi_posko` (`id`, `nama_barang`, `kategori`, `penanggung_jawab`, `keterangan`, `dibuat_pada`) VALUES ('8', 'Talenan', 'Peralatan Dapur', 'Aley', 'Alas potong dapur', '2026-07-21 16:03:34');
INSERT INTO `informasi_posko` (`id`, `nama_barang`, `kategori`, `penanggung_jawab`, `keterangan`, `dibuat_pada`) VALUES ('9', 'Sudip', 'Perlengkapan Memasak', 'Aley', 'Alat pengaduk masakan', '2026-07-21 16:03:34');
INSERT INTO `informasi_posko` (`id`, `nama_barang`, `kategori`, `penanggung_jawab`, `keterangan`, `dibuat_pada`) VALUES ('10', 'Baskom', 'Wadah & Cucian', 'Tiara', 'Wadah cuci / penampung', '2026-07-21 16:03:34');
INSERT INTO `informasi_posko` (`id`, `nama_barang`, `kategori`, `penanggung_jawab`, `keterangan`, `dibuat_pada`) VALUES ('11', 'Tempat Lauk', 'Peralatan Makan', 'Mangkok Masing-masing', 'Wadah lauk posko', '2026-07-21 16:03:34');
INSERT INTO `informasi_posko` (`id`, `nama_barang`, `kategori`, `penanggung_jawab`, `keterangan`, `dibuat_pada`) VALUES ('12', 'Nyedot Air Galon', 'Tugas Operasional', 'Abel', 'Penyedotan air galon posko', '2026-07-21 16:03:34');

DROP TABLE IF EXISTS `jadwal_acara`;
CREATE TABLE `jadwal_acara` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tanggal` VARCHAR(255) NULL,
  `hari` TEXT NULL,
  `waktu` TEXT NULL,
  `agenda` TEXT NULL,
  `pic` TEXT NULL,
  `keterangan` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `jadwal_acara` (`id`, `tanggal`, `hari`, `waktu`, `agenda`, `pic`, `keterangan`) VALUES ('12', '2026-08-06', 'Kamis', '08.00-selesai', 'Pengataran Surat', NULL, 'Pengantaran Surat ke sd 13 dan sma 11 Pontianak');
INSERT INTO `jadwal_acara` (`id`, `tanggal`, `hari`, `waktu`, `agenda`, `pic`, `keterangan`) VALUES ('13', '2026-08-06', 'Kamis', '00.00', 'Pendataan Umkm', NULL, 'jam disesuaikan');
INSERT INTO `jadwal_acara` (`id`, `tanggal`, `hari`, `waktu`, `agenda`, `pic`, `keterangan`) VALUES ('14', '2026-08-06', 'Kamis', '00.00', 'Menujungi tpa', NULL, 'jam disesuaikan');
INSERT INTO `jadwal_acara` (`id`, `tanggal`, `hari`, `waktu`, `agenda`, `pic`, `keterangan`) VALUES ('15', '2026-08-07', 'Jumat', '08.00-selesai', 'sosialisai proker  prodi', NULL, 'di sdn 13 pontianak');
INSERT INTO `jadwal_acara` (`id`, `tanggal`, `hari`, `waktu`, `agenda`, `pic`, `keterangan`) VALUES ('16', '2026-08-07', 'Jumat', '15.00-selesai', 'Proker Mengunjungi Umkm Keripik (Manajemen)', NULL, NULL);
INSERT INTO `jadwal_acara` (`id`, `tanggal`, `hari`, `waktu`, `agenda`, `pic`, `keterangan`) VALUES ('17', '2026-08-08', 'Sabtu', '08.00-selesai', 'Pendataan Umkm', NULL, NULL);
INSERT INTO `jadwal_acara` (`id`, `tanggal`, `hari`, `waktu`, `agenda`, `pic`, `keterangan`) VALUES ('18', '2026-08-09', 'Minggu', '08.00-selesai', 'belajar senam', NULL, 'di posko');

DROP TABLE IF EXISTS `jadwal_piket`;
CREATE TABLE `jadwal_piket` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `hari` TEXT NULL,
  `shift` TEXT NULL,
  `tugas` TEXT NULL,
  `petugas` TEXT NULL,
  `keterangan` TEXT NULL,
  `dibuat_pada` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `jadwal_piket` (`id`, `hari`, `shift`, `tugas`, `petugas`, `keterangan`, `dibuat_pada`) VALUES ('1', 'Senin', '08:00 - 17:00', 'Piket Posko & Penerima Tamu', 'Rizki Tri Saputra, Anggi Rahmawati, Virahmanda Abelia Ismaya', 'Menjaga posko utama dan mengarsip surat masuk', '2026-07-21 15:51:50');
INSERT INTO `jadwal_piket` (`id`, `hari`, `shift`, `tugas`, `petugas`, `keterangan`, `dibuat_pada`) VALUES ('2', 'Selasa', '08:00 - 17:00', 'Piket Kebersihan & Konsumsi', 'Muhammad Fiqri Mahendra, Siti Aliyah, Fathurrahman', 'Menyiapkan logistik posko dan konsumsi harian', '2026-07-21 15:51:50');
INSERT INTO `jadwal_piket` (`id`, `hari`, `shift`, `tugas`, `petugas`, `keterangan`, `dibuat_pada`) VALUES ('3', 'Rabu', '08:00 - 17:00', 'Piket Humas & Publikasi', 'Sebastianus Aditia, Khairunisa Salsabila, Halimah Tusa''Diah', 'Mendokumentasikan & koordinasi warga lokal', '2026-07-21 15:51:50');
INSERT INTO `jadwal_piket` (`id`, `hari`, `shift`, `tugas`, `petugas`, `keterangan`, `dibuat_pada`) VALUES ('4', 'Kamis', '08:00 - 17:00', 'Piket PDD & Dokumentasi', 'Tiara Fitriani, Siti Aliyah, Fathurrahman', 'Mengelola arsip media dan sosial media KKN', '2026-07-21 15:51:50');
INSERT INTO `jadwal_piket` (`id`, `hari`, `shift`, `tugas`, `petugas`, `keterangan`, `dibuat_pada`) VALUES ('5', 'Jumat', '08:00 - 17:00', 'Piket Perlengkapan Posko', 'Sebastianus Aditia, Khairunisa Salsabila, Halimah Tusa''Diah', 'Memeriksa inventaris barang dan peralatan KKN', '2026-07-21 15:51:50');
INSERT INTO `jadwal_piket` (`id`, `hari`, `shift`, `tugas`, `petugas`, `keterangan`, `dibuat_pada`) VALUES ('6', 'Sabtu', '08:00 - 15:00', 'Piket Posko & Evaluasi', 'Rizki Tri Saputra, Anggi Rahmawati, Tiara Fitriani', 'Piket sabtu dan persiapan rekap kegiatan', '2026-07-21 15:51:50');
INSERT INTO `jadwal_piket` (`id`, `hari`, `shift`, `tugas`, `petugas`, `keterangan`, `dibuat_pada`) VALUES ('7', 'Minggu', 'Penuh', 'Piket Bersama / Kerja Bakti', 'Seluruh Anggota KKN Kelompok 12', 'Pembersihan posko mingguan bersama-sama', '2026-07-21 15:51:50');

DROP TABLE IF EXISTS `kegiatan`;
CREATE TABLE `kegiatan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tanggal` VARCHAR(255) NULL,
  `hari` TEXT NULL,
  `judul` TEXT NULL,
  `bidang_id` INT NULL,
  `jam_mulai` TEXT NULL,
  `jam_selesai` TEXT NULL,
  `lokasi` TEXT NULL,
  `sasaran` TEXT NULL,
  `deskripsi` TEXT NULL,
  `hasil` TEXT NULL,
  `dibuat_pada` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('1', '2026-07-13', 'Senin', 'Ketemu Kepala Keluharan', '6', '11:57', '15.00', 'Jl. Husein Hamzah No.2, Pal Lima, Kec. Pontianak Bar., Kota Pontianak, Kalimantan Barat 78113', 'kepala kelurahan', 'Kunjungan dan audiensi dengan Kepala Kelurahan Pal Lima untuk memperkenalkan program kerja KKN, melakukan survei awal, serta membangun koordinasi sebagai persiapan pelaksanaan kegiatan.', 'Diperolehnya izin dan dukungan dari Kepala Kelurahan serta informasi awal mengenai kondisi dan potensi wilayah KKN.', '2026-07-15 16:59:21');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('4', '2026-07-20', 'Senin', 'Kegiatan Pelepasan Mahasiswa Kkn 2026', '9', '12.30', '16.00', 'Jl. Jenderal Ahmad Yani No.111, Bangka Belitung Laut, Kec. Pontianak Tenggara, Kota Pontianak, Kalimantan Barat 78123', 'Seluruh Mahasiswa KKn 2026', 'Pada hari Senin, 20 Juli 2026, peserta KKN Universitas Muhammadiyah Pontianak mengikuti kegiatan pelepasan di Auditorium Universitas Muhammadiyah Pontianak sebagai tanda dimulainya pelaksanaan KKN Tahun 2026. Kegiatan ini berisi pembekalan dan arahan bagi mahasiswa sebelum diterjunkan ke lokasi KKN.', 'Peserta mengikuti kegiatan pelepasan KKN, memperoleh pembekalan mengenai pelaksanaan dan etika KKN, memahami tugas serta tanggung jawab, dan dinyatakan siap melaksanakan KKN sesuai arahan LP3M Universitas Muhammadiyah Pontianak.', '2026-07-22 09:43:19');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('5', '2026-07-27', 'Senin', 'Pelepasan Dosen Pembimbing Lapangan Kepada Kelurahan  Pal Lima', '10', '9.30', '11.00', 'Jl. Husein Hamzah No.2, Pal Lima, Kec. Pontianak Bar., Kota Pontianak, Kalimantan Barat 78113', 'Kepala Kelurahan Pal lima', 'Pelepasan dan penyerahan resmi mahasiswa KKN oleh Dosen Pembimbing Lapangan (DPL) kepada pihak Kelurahan Pal Lima sebagai awal pelaksanaan program KKN. Kegiatan meliputi perkenalan, penyampaian tujuan dan program kerja KKN, serta koordinasi awal dengan pihak kelurahan guna mendukung kelancaran pelaksanaan kegiatan selama masa KKN.', 'Mahasiswa KKN resmi diterima oleh Kelurahan Pal Lima, koordinasi awal terlaksana, program kerja tersampaikan, serta komitmen kerja sama berhasil terjalin.', '2026-07-27 11:37:57');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('6', '2026-08-02', 'Minggu', 'Kegiatan Gotong Royong Dengan Warga Didis Permai 8', '4', '07.30', '09.00', 'Jl. Husein Hamzah Komp. Didis Permai No.8 no 16c, Sungai Beliung, Kec. Pontianak Bar., Kota Pontianak, Kalimantan Barat 78114', 'Seluruh Warga Dan Mahasiswa Kkn Kelompok 12', 'Pelaksanaan kegiatan gotong royong bersama warga Komplek Didis Permai 8 dalam rangka membersihkan lingkungan, merapikan fasilitas umum, dan meningkatkan kesadaran masyarakat akan pentingnya menjaga kebersihan serta mempererat kebersamaan antara warga dan mahasiswa KKN.', 'Lingkungan menjadi lebih bersih dan rapi, saluran air terbebas dari sampah, serta terjalinnya kerja sama dan silaturahmi yang baik antara warga dengan Mahasiswa KKN Kelompok 12.', '2026-08-04 01:24:39');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('7', '2026-08-04', 'Selasa', 'Kunjungan dan Observasi UMKM Pembuatan Tape', '3', '10.00', '12.00', 'Jl. Nipah Kuning Dalam, Gang Manggala', 'Pelaku usaha Umkm', 'Melaksanakan kunjungan dan observasi ke UMKM Lidya Tape yang berlokasi di Jalan Nipah, Gang Manggala, Kelurahan Benua Melayu Darat, Kecamatan Pontianak Selatan, Kota Pontianak, Kalimantan Barat. Kegiatan ini bertujuan untuk mengenal profil usaha, mengamati proses pembuatan tape, serta memperoleh informasi mengenai proses produksi, pengemasan, dan pemasaran produk. Selain itu, dilakukan diskusi dengan pemilik UMKM terkait tantangan yang dihadapi serta peluang pengembangan usaha guna mendukung peningkatan daya saing UMKM.', 'Diperoleh informasi mengenai profil usaha, proses pembuatan tape, teknik pengemasan, dan strategi pemasaran yang diterapkan oleh UMKM Lidya Tape. Hasil observasi ini menjadi bahan identifikasi potensi dan permasalahan UMKM sebagai dasar penyusunan program kerja KKN yang mendukung pengembangan usaha.', '2026-08-04 07:06:12');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('8', '2026-08-07', 'Jumat', 'Kunjungan Ke sdn 13 Pontianak Barat', '1', '07.00', '09.00', 'Jl. Husein Hamzah, Pal Lima, Kec. Pontianak Bar., Kota Pontianak, Kalimantan Barat 78114', 'Siswa Sd Kelas 6,5,1', 'Sosialiasi Perlindungan Anak\r\n Sosialiasi Psikoedukasi Pencegahan Bullying\r\n Edukasi Cuci Tanggan Pakai Sabun (Ctps)', 'Siswa memahami perlindungan anak, pencegahan bullying, dan pentingnya CTPS.', '2026-08-08 12:12:34');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('9', '2026-08-08', 'Sabtu', 'Kegiatan Mengajar di Tpq  Masjid Al-ikhsan', '1', '07.10', '10.00', 'Jl. Komp. Villa ArthalandPal Lima, Kec. Pontianak Bar., Kota Pontianak, Kalimantan Barat 78114', 'Seluruh Siswa Tpq  Masjid Al-ikhsan', 'Membimbing siswa dalam membaca Al-Qur''an dan belajar dasar-dasar keagamaan.', 'Siswa mengikuti pembelajaran dengan baik dan memahami materi yang diberikan.', '2026-08-08 13:22:05');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('10', '2026-08-06', 'Kamis', 'Melakukan Survey dan Pendataan Umkm Hidroponik Seledri', '3', '13.30', '15.00', 'Jln.Berdikari Gg Chandra Berdikari', 'Pemilik Usaha', 'Kegiatan kunjungan dan survei dilakukan untuk mengenal kondisi serta potensi usaha pelaku UMKM secara langsung. Kegiatan meliputi observasi usaha, wawancara dengan pelaku usaha, serta identifikasi kebutuhan dan kendala dalam pengembangan UMKM.', 'Tersedianya data awal mengenai UMKM sebagai dasar penyusunan program pendampingan, pengembangan usaha, dan peningkatan potensi UMKM.', '2026-08-12 02:59:08');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('12', '2026-08-10', 'Senin', 'Kegiatan Survey Umkm dan Kunjungan di Pangkalan Batu Alam dan Pasir', '3', '13.30', '15.00', 'Jl.Husien Hamzah Pal 5', 'Pelaku/Pemilik Usaha Umkm', 'Melakukan survey lapangan dan pendataan terhadap pelaku usaha UMKM batu alam dan pasir di wilayah Jl. Husien Hamzah Pal 5. Kegiatan meliputi wawancara langsung dengan pemilik usaha terkait profil usaha, jenis produk, skala produksi, serta kendala yang dihadapi dalam menjalankan usaha.', 'Diperoleh data profil UMKM batu alam dan pasir (jumlah usaha, jenis produk, kapasitas produksi) yang akan digunakan sebagai bahan pemetaan potensi ekonomi desa/kelurahan.', '2026-08-12 03:10:58');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('13', '2026-08-09', 'Minggu', 'Senam Bersama dan Sosialisai Stunting di Posyandu Artha Land', '2', '07.30', '10.00', 'Jl. Husein Hamzah (Pal 5), Komplek Villa Artha Land, Kel. Pal Lima, Kec. Pontianak Barat, Kota Pontianak, Kalimantan Barat 78244', 'Kader Posyandu Dan Seluruh Masyarakat yang hadir', 'Melaksanakan kegiatan senam bersama dan sosialisasi stunting kepada kader posyandu dan masyarakat sekitar. Kegiatan diawali dengan senam pagi bersama, dilanjutkan dengan penyampaian materi sosialisasi mengenai pengertian, penyebab, dampak, serta cara pencegahan stunting pada anak.', 'Meningkatnya pemahaman kader posyandu dan masyarakat mengenai pentingnya pencegahan stunting sejak dini, serta terjalinnya kedekatan antara mahasiswa KKN dengan warga melalui kegiatan senam bersama.', '2026-08-12 03:20:58');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('14', '2026-08-10', 'Senin', 'Penimbangan dan Pegukuran Berat Badan di Posyandu Artha Land', '2', '09.10', '11.00', 'Jl. Komp. Villa ArthalandPal Lima, Kec. Pontianak Bar., Kota Pontianak, Kalimantan Barat 78114', 'Selurah Anak Balita Posyandu Artha Land', 'Melaksanakan kegiatan penimbangan dan pengukuran berat badan balita di Posyandu Artha Land bersama kader posyandu setempat. Kegiatan meliputi pencatatan data berat badan dan tinggi/panjang badan balita pada Kartu Menuju Sehat (KMS) sebagai upaya pemantauan tumbuh kembang anak dan deteksi dini stunting.', 'Terkumpulnya data berat dan tinggi badan seluruh balita peserta Posyandu Artha Land yang tercatat pada KMS, sebagai bahan pemantauan status gizi dan tumbuh kembang anak secara berkala.', '2026-08-12 03:55:35');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('15', '2026-08-15', 'Sabtu', 'Kegitaan Lomba 17 Agustus  Komp Didis Permai', '12', '09.30', '13.30', 'Komplek Didis Pemai blok C-d,Jln Tabrani Ahmad', 'Anak-Anak', 'Kegitaan lomba makan kerupuk,Estafet Kardus,Biskuit Roma', 'tidak output hanya perlombaan biasa', '2026-08-17 04:01:19');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('16', '2026-08-16', 'Minggu', 'Kegitaan Lomba 17 Agustus  Komp Didis Permai', '12', '08.45', '22.00', 'Komplek Didis Pemai blok C-d,Jln Tabrani Ahmad', 'Seluruh warga komp didis Permai', 'Kegitaan lomba Anak-Anak Masukan Paku dalam botol,Masukan Air dalam botol,Masukan Sedotan dalam botol, Kegitaan Lomba Ibu joget balon,lomba tahan tawa,lomba make up tanpa kaca,lomba make up istri,lomba estafet air, lomba bapak-bapak lomba makan kerupuk pakai pancing,lomba pukul paku,lomba joget balon lomba karoeke', 'tidak ada hanya lomba biasa', '2026-08-20 04:36:43');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('17', '2026-08-20', 'Kamis', 'Sosialisasi Dampak Serangan Cyber, Psikoedukasi Pencegahan Bullying dan Promosi Kampus di Sman 11 Pontianak', '1', '07.06', '09.10', 'Jl. Nipah Kuning Dalam Sma 11 Pontianak', 'Siswa Kelas 12', 'Pelaksanaan kegiatan sosialisasi mengenai dampak serangan siber serta psikoedukasi pencegahan bullying kepada siswa kelas 12. Kegiatan ini bertujuan untuk meningkatkan pemahaman siswa mengenai bahaya kejahatan siber, pentingnya menjaga keamanan digital, serta membangun kesadaran untuk mencegah dan menghadapi perilaku bullying di lingkungan sekolah.', 'Siswa memperoleh pemahaman mengenai dampak serangan siber dan cara menjaga keamanan dalam penggunaan teknologi digital. Selain itu, siswa memahami bentuk, dampak, serta langkah-langkah pencegahan bullying sehingga diharapkan mampu menciptakan lingkungan sekolah yang lebih aman, nyaman, dan saling menghargai.', '2026-08-20 04:46:44');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('18', '2026-08-17', 'Senin', 'Pembagian Hadiah Lomba 17 Agustus Komp.Didis Permai Blok C-D', '12', '17.00', '18.30', 'Komplek Didis Pemai blok C-d,Jln Tabrani Ahmad', 'Seluruh Warga Komp Didis Permai', 'Pembagian Semua hadiah lomba', '_', '2026-08-20 05:04:30');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('19', '2026-08-20', 'Kamis', 'Kegitaan Membantu Posyandu Artha Land', '2', '09.00', '11.00', 'Jln Tanggul Dalam 1 Masjid Al-hidayah', 'Anak Balita', 'Penimbangan Berat Badan, Pengukutan Tinggi badan,dan imunisasi', 'Anak balita mendapatkan pelayanan Posyandu berupa pengukuran tinggi badan, penimbangan berat badan, serta pelayanan imunisasi. Data pertumbuhan dan perkembangan balita berhasil dicatat sebagai bahan pemantauan kesehatan anak secara berkala', '2026-08-20 06:23:04');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('20', '2026-08-24', 'Senin', 'Bersih-Bersih Surau Al-Huda Komp Didis Permai Blok C-D', '4', '09.00', '11.00', 'Komplek Didis Permai Blok C-D', 'Deskripsi Kegiatan', 'Melaksanakan kegiatan gotong royong membersihkan Surau Al Huda bersama anggota KKN dan masyarakat sekitar. Kegiatan meliputi membersihkan bagian dalam dan luar surau, menyapu dan mengepel lantai, membersihkan halaman, serta merapikan lingkungan sekitar surau agar menjadi lebih bersih, nyaman, dan layak digunakan untuk kegiatan ibadah.', 'Surau Al Huda dan lingkungan sekitarnya menjadi lebih bersih, rapi, dan nyaman untuk digunakan beribadah. Kegiatan juga meningkatkan kepedulian serta semangat gotong royong antara mahasiswa KKN dan masyarakat sekitar.', '2026-08-27 09:18:43');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('21', '2026-08-24', 'Senin', 'Mengajar Membaca Alquran dan Menjelasakan', '1', '18.00', '20.30', 'Komplek Didis Permai Blok C-D', 'Seluruh Murid Tpa Surau Al-Huda', 'Melaksanakan kegiatan bersih-bersih dan penataan lingkungan TPA di Surau Al Huda bersama anggota KKN. Kegiatan dilakukan dengan membersihkan ruang belajar, menyapu dan mengepel lantai, merapikan perlengkapan belajar, serta membersihkan lingkungan sekitar agar tercipta tempat belajar mengaji yang bersih, nyaman, dan kondusif bagi anak-anak TPA.', 'Ruang dan lingkungan TPA Surau Al Huda menjadi lebih bersih, rapi, dan nyaman untuk kegiatan belajar mengaji. Selain itu, kegiatan ini membantu menciptakan lingkungan belajar yang lebih kondusif bagi anak-anak TPA.', '2026-08-27 09:20:55');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('22', '2026-07-14', 'Selasa', 'Observasi & Pendataan Fasilitas Sekolah Dasar', '1', '08:00', '12:00', 'SDN 13 Pontianak Barat', 'Guru dan Siswa SD', 'Melakukan pemetaan awal kebutuhan sarana bimbingan belajar dan ruang baca perpustakaan sekolah.', 'Data kebutuhan buku dan alat peraga mengajar berhasil dihimpun.', '2026-08-27 09:36:00');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('23', '2026-07-15', 'Rabu', 'Pemetaan Titik Sampah & Sanitasi Lingkungan Kelurahan', '4', '08:00', '13:00', 'RW 03 & RW 04 Pal Lima', 'Warga & Pengurus RT', 'Survei titik pembuangan sampah sementara dan kondisi saluran drainase pemukiman warga.', 'Peta lokasi prioritas pembersihan dan penghijauan desa berhasil dibuat.', '2026-08-27 09:36:00');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('24', '2026-07-16', 'Kamis', 'Pendataan Pelaku UMKM Kuliner & Kerajinan Lokal', '3', '09:00', '14:00', 'Kelurahan Pal Lima', 'Pelaku UMKM Kelurahan Pal Lima', 'Mendata produk unggulan warga, status perizinan usaha, dan kendala pemasaran digital.', 'Terdata 15 UMKM potensial untuk program pembinaan kemasan & branding.', '2026-08-27 09:36:00');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('25', '2026-07-17', 'Jumat', 'Koordinasi Posyandu & Edukasi Kesehatan Ibu Anak', '2', '08:00', '11:30', 'Posyandu Mawar Pal Lima', 'Kader Posyandu & Ibu Balita', 'Membantu persiapan penimbangan balita dan distribusi makanan tambahan pencegah stunting.', 'Kegiatan berjalan lancar dengan tingkat partisipasi 35 ibu dan balita.', '2026-08-27 09:36:00');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('26', '2026-07-21', 'Selasa', 'Bimbingan Belajar Matematika & Bahasa Inggris Dasar', '1', '08:00', '12:30', 'Posko KKN Kelompok 12', 'Anak-anak SD & SMP Pal Lima', 'Pemberian materi les gratis dan metode berhitung cepat sederhana.', '25 siswa aktif berpartisipasi dan memahami teknik kalkulasi praktis.', '2026-08-27 09:36:00');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('27', '2026-07-22', 'Rabu', 'Pelatihan Desain Kemasan & Foto Produk UMKM', '3', '09:00', '15:00', 'Balai Pertemuan Kelurahan Pal Lima', 'Pemilik Usaha Mikro', 'Pendampingan pembuatan stiker label, foto produk katalog, dan pendaftaran lokasi Google Maps.', '10 UMKM berhasil memperbarui desain logo dan mendaftarkan titik usahanya.', '2026-08-27 09:36:00');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('28', '2026-07-23', 'Kamis', 'Penanaman Pohon Pelindung & Bibit Tanaman Obat Desa', '4', '07:30', '12:00', 'Taman Fasum Kelurahan Pal Lima', 'Masyarakat Desa', 'Aksi hijau penanaman 50 bibit pohon mahoni dan pembersihan area taman bermain.', 'Penanaman bibit pohon terlaksana dengan dukungan tokoh masyarakat.', '2026-08-27 09:36:00');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('29', '2026-07-24', 'Jumat', 'Pelatihan Literasi Digital & Pengenalan Komputer', '1', '13:30', '17:00', 'Perpustakaan Desa Pal Lima', 'Remaja dan Pelajar Sekolah', 'Pengenalan dasar pengolah kata Word, Excel, dan pemanfaatan internet sehat.', 'Para siswa mampu mempraktikkan pembuatan dokumen sederhana secara mandiri.', '2026-08-27 09:36:00');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('30', '2026-07-28', 'Selasa', 'Pemeriksaan Kesehatan Gratis & Penyuluhan PHBS', '2', '08:30', '13:30', 'Halaman Masjid Al-Ikhsan', 'Warga Lansia & Umum', 'Cek tekanan darah, gula darah sewaktu, dan edukasi pola hidup bersih sehat.', '55 warga terlayani pemeriksaan kesehatan ringan dan konsultasi gizi.', '2026-08-27 09:36:00');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('31', '2026-07-29', 'Rabu', 'Workshop Digital Marketing & Penjualan Online', '3', '08:00', '14:00', 'Aula Kelurahan Pal Lima', 'Wirausaha Muda Desa', 'Strategi berjualan di WhatsApp Business, TikTok Shop, dan Shopee untuk pemasaran lokal.', 'Peserta memahami pendaftaran akun bisnis dan optimasi pembuatan konten.', '2026-08-27 09:36:00');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('32', '2026-07-30', 'Kamis', 'Pembuatan Tempat Sampah Pilah Organik & Anorganik', '4', '08:00', '13:00', 'Fasilitas Umum RT 02 & RT 05', 'Masyarakat Lingkungan', 'Pengecatan dan penempatan tong sampah terpilah di titik-titik kumpul warga.', 'Terpasang 8 unit pasang tong sampah pilah di lokasi strategis.', '2026-08-27 09:36:00');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('33', '2026-07-31', 'Jumat', 'Pendampingan Pojok Baca Anak & Lomba Mewarnai', '1', '08:00', '11:30', 'PAUD Melati Pal Lima', 'Anak Usia Dini', 'Kegiatan mendongeng cerita rakyat dan lomba mewarnai edukatif.', '20 anak Paud mengikuti lomba mewarnai dengan antusias.', '2026-08-27 09:36:00');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('34', '2026-08-01', 'Sabtu', 'Kerja Bakti Bersama Pembersihan Saluran Air Desa', '4', '07:00', '12:00', 'Drainase Utama Gang Didis Permai', 'Warga Kelurahan Pal Lima', 'Gotong royong membersihkan sedimen lumpur dan sampah pemicu genangan air.', 'Aliran air selokan lancar dan pemukiman bebas dari genangan.', '2026-08-27 09:36:00');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('35', '2026-08-03', 'Senin', 'Sosialisasi Pencegahan Demam Berdarah & Pembagian Abate', '2', '08:30', '14:00', 'RW 01 & RW 02 Pal Lima', 'Ibu Rumah Tangga', 'Pemeriksaan jentik nyamuk berkala (Jumantik) dan edukasi langkah 3M Plus.', 'Terdistribusi 100 bungkus bubuk abate ke penampungan air warga.', '2026-08-27 09:36:00');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('36', '2026-08-05', 'Rabu', 'Pencatatan Keuangan Sederhana untuk Usaha Mikro', '3', '09:00', '15:00', 'Posko KKN Kelompok 12', 'Pelaku UMKM Lokal', 'Pelatihan penggunaan aplikasi buku kas digital pada smartphone.', 'Pelaku usaha dapat memisahkan uang pribadi dan arus kas usaha.', '2026-08-27 09:36:00');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('37', '2026-08-11', 'Selasa', 'Sosialisasi Anti Bullying & Etika Media Sosial Sekolah', '1', '08:00', '13:00', 'SMP Negeri Pal Lima', 'Siswa SMP', 'Edukasi pentingnya toleransi, pembentukan karakter, dan bahaya cyberbullying.', 'Siswa mendeklarasikan komitmen sekolah bebas perundungan.', '2026-08-27 09:36:00');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('38', '2026-08-12', 'Rabu', 'Pelatihan Pembuatan Kompos Organik dari Sampah Rumah Tangga', '4', '08:00', '14:00', 'Kebun Percontohan Warga', 'Kelompok Tani & Kelompok Wanita Tani', 'Praktek pencampuran sisa sayuran, dedaunan, dan aktivator em4 menjadi pupuk.', 'Dihasilkan 30 kg bahan pupuk kompos siap fermentasi.', '2026-08-27 09:36:00');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('39', '2026-08-13', 'Kamis', 'Demonstrasi Makanan Pendamping ASI Sehat Kaya Protein', '2', '08:30', '14:00', 'Posyandu Artha Land', 'Ibu Menyusui & Balita', 'Cooking class olahan makanan bergizi berbahan dasar ikan lokal dan sayur.', 'Resep resep MPASI bergizi dibagikan ke 25 ibu peserta.', '2026-08-27 09:36:00');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('40', '2026-08-14', 'Jumat', 'Pameran Produk UMKM & Penyerahan Hasil Branding', '3', '08:00', '14:30', 'Halaman Balai Desa Pal Lima', 'Pelaku UMKM & Masyarakat', 'Display kemasan baru, pembagian spanduk banner, dan penyerahan akun media sosial usaha.', '12 UMKM menerima sertifikat pendampingan dan paket kemasan baru.', '2026-08-27 09:36:00');
INSERT INTO `kegiatan` (`id`, `tanggal`, `hari`, `judul`, `bidang_id`, `jam_mulai`, `jam_selesai`, `lokasi`, `sasaran`, `deskripsi`, `hasil`, `dibuat_pada`) VALUES ('41', '2026-08-15', 'Sabtu', 'Pengecatan Gapura Desa & Pemasangan Penunjuk Jalan', '4', '07:30', '11:28', 'Pintu Masuk Kelurahan Pal Lima', 'Fasilitas Publik Desa', 'Peremajaan plang batas wilayah dan papan penunjuk arah fasilitas umum.', 'Papan penunjuk jalan dan gapura desa selesai dihias rapi.', '2026-08-27 09:36:00');

DROP TABLE IF EXISTS `kegiatan_foto`;
CREATE TABLE `kegiatan_foto` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kegiatan_id` INT NULL,
  `path_foto` TEXT NULL,
  `keterangan` TEXT NULL,
  `diunggah_pada` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('1', '1', 'uploads/kegiatan/kegiatan-1-1784125992-8739bddf.jpeg', NULL, '2026-07-15 21:33:12');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('3', '4', 'uploads/kegiatan/kegiatan-4-1784688225-95c8c04c.jpeg', NULL, '2026-07-22 09:43:46');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('118', '15', 'uploads/kegiatan/kegiatan-15-1786939513-d720c692.jpeg', NULL, '2026-08-17 04:05:13');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('119', '14', 'uploads/kegiatan/kegiatan-14-1786939537-8721d7fe.jpeg', NULL, '2026-08-17 04:05:37');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('120', '14', 'uploads/kegiatan/kegiatan-14-1786939537-be969f6b.jpeg', NULL, '2026-08-17 04:05:37');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('121', '14', 'uploads/kegiatan/kegiatan-14-1786939537-f8cd1eb9.jpeg', NULL, '2026-08-17 04:05:37');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('122', '14', 'uploads/kegiatan/kegiatan-14-1786939537-ebf7528a.jpeg', NULL, '2026-08-17 04:05:37');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('123', '12', 'uploads/kegiatan/kegiatan-12-1786939548-311aaf60.jpeg', NULL, '2026-08-17 04:05:48');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('124', '13', 'uploads/kegiatan/kegiatan-13-1786939565-c6e41d28.jpeg', NULL, '2026-08-17 04:06:05');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('125', '13', 'uploads/kegiatan/kegiatan-13-1786939565-5a9df301.jpeg', NULL, '2026-08-17 04:06:05');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('126', '13', 'uploads/kegiatan/kegiatan-13-1786939565-eb758628.jpeg', NULL, '2026-08-17 04:06:05');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('127', '9', 'uploads/kegiatan/kegiatan-9-1786939588-ec318aca.jpeg', NULL, '2026-08-17 04:06:28');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('128', '9', 'uploads/kegiatan/kegiatan-9-1786939588-3647bca8.jpeg', NULL, '2026-08-17 04:06:28');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('129', '9', 'uploads/kegiatan/kegiatan-9-1786939588-5cd52a05.jpeg', NULL, '2026-08-17 04:06:28');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('130', '9', 'uploads/kegiatan/kegiatan-9-1786939588-f475d78d.jpeg', NULL, '2026-08-17 04:06:28');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('131', '9', 'uploads/kegiatan/kegiatan-9-1786939588-c81d1a75.jpeg', NULL, '2026-08-17 04:06:28');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('132', '9', 'uploads/kegiatan/kegiatan-9-1786939588-64a9770a.jpeg', NULL, '2026-08-17 04:06:28');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('133', '8', 'uploads/kegiatan/kegiatan-8-1786939623-9f99f1f8.jpeg', NULL, '2026-08-17 04:07:03');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('134', '8', 'uploads/kegiatan/kegiatan-8-1786939623-d2dc4ec4.jpeg', NULL, '2026-08-17 04:07:03');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('135', '8', 'uploads/kegiatan/kegiatan-8-1786939623-70a7dd0a.jpeg', NULL, '2026-08-17 04:07:03');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('136', '8', 'uploads/kegiatan/kegiatan-8-1786939623-5e2d5d86.jpeg', NULL, '2026-08-17 04:07:03');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('137', '8', 'uploads/kegiatan/kegiatan-8-1786939623-f2fc1d6a.jpeg', NULL, '2026-08-17 04:07:03');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('138', '8', 'uploads/kegiatan/kegiatan-8-1786939623-92bc5374.jpeg', NULL, '2026-08-17 04:07:03');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('139', '8', 'uploads/kegiatan/kegiatan-8-1786939623-65251886.jpeg', NULL, '2026-08-17 04:07:03');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('140', '8', 'uploads/kegiatan/kegiatan-8-1786939623-40a1d06f.jpeg', NULL, '2026-08-17 04:07:03');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('141', '8', 'uploads/kegiatan/kegiatan-8-1786939623-31a2f277.jpeg', NULL, '2026-08-17 04:07:03');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('142', '8', 'uploads/kegiatan/kegiatan-8-1786939623-f37a554d.jpeg', NULL, '2026-08-17 04:07:03');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('143', '8', 'uploads/kegiatan/kegiatan-8-1786939623-c1563694.jpeg', NULL, '2026-08-17 04:07:03');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('144', '8', 'uploads/kegiatan/kegiatan-8-1786939623-2b28b853.jpeg', NULL, '2026-08-17 04:07:03');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('145', '10', 'uploads/kegiatan/kegiatan-10-1786939642-9f6c7784.jpeg', NULL, '2026-08-17 04:07:22');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('146', '7', 'uploads/kegiatan/kegiatan-7-1786939662-fcfe4154.jpeg', NULL, '2026-08-17 04:07:42');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('147', '6', 'uploads/kegiatan/kegiatan-6-1786939690-756c3abe.jpeg', NULL, '2026-08-17 04:08:10');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('148', '5', 'uploads/kegiatan/kegiatan-5-1786939708-f9e3020e.jpeg', NULL, '2026-08-17 04:08:28');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('149', '16', 'uploads/kegiatan/kegiatan-16-1787200904-3d8bbdcb.jpeg', NULL, '2026-08-20 04:41:43');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('150', '16', 'uploads/kegiatan/kegiatan-16-1787200904-25a2de28.jpeg', NULL, '2026-08-20 04:41:43');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('151', '16', 'uploads/kegiatan/kegiatan-16-1787200904-8140ce54.jpeg', NULL, '2026-08-20 04:41:43');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('152', '16', 'uploads/kegiatan/kegiatan-16-1787200904-804db59e.jpeg', NULL, '2026-08-20 04:41:43');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('153', '16', 'uploads/kegiatan/kegiatan-16-1787200904-cecdf4ba.jpeg', NULL, '2026-08-20 04:41:43');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('154', '17', 'uploads/kegiatan/kegiatan-17-1787201456-525a704e.jpeg', NULL, '2026-08-20 04:50:56');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('155', '17', 'uploads/kegiatan/kegiatan-17-1787201456-9a4a2b51.jpeg', NULL, '2026-08-20 04:50:56');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('156', '17', 'uploads/kegiatan/kegiatan-17-1787201456-2d588232.jpeg', NULL, '2026-08-20 04:50:56');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('157', '17', 'uploads/kegiatan/kegiatan-17-1787201456-c76983bf.jpeg', NULL, '2026-08-20 04:50:56');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('158', '17', 'uploads/kegiatan/kegiatan-17-1787201456-77b765ce.jpeg', NULL, '2026-08-20 04:50:56');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('159', '16', 'uploads/kegiatan/kegiatan-16-1787202164-357ff11a.jpeg', NULL, '2026-08-20 05:02:44');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('160', '16', 'uploads/kegiatan/kegiatan-16-1787202164-ce3deaa1.jpeg', NULL, '2026-08-20 05:02:44');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('161', '16', 'uploads/kegiatan/kegiatan-16-1787202164-79c14501.jpeg', NULL, '2026-08-20 05:02:44');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('162', '16', 'uploads/kegiatan/kegiatan-16-1787202164-5d6b71c3.jpeg', NULL, '2026-08-20 05:02:44');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('163', '16', 'uploads/kegiatan/kegiatan-16-1787202164-7bcd5607.jpeg', NULL, '2026-08-20 05:02:44');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('164', '16', 'uploads/kegiatan/kegiatan-16-1787202164-9e7ddab3.jpeg', NULL, '2026-08-20 05:02:44');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('165', '18', 'uploads/kegiatan/kegiatan-18-1787202577-81b2728f.jpeg', NULL, '2026-08-20 05:09:37');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('166', '18', 'uploads/kegiatan/kegiatan-18-1787202577-354247f9.jpeg', NULL, '2026-08-20 05:09:37');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('167', '18', 'uploads/kegiatan/kegiatan-18-1787202577-7ce30d3e.jpeg', NULL, '2026-08-20 05:09:37');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('168', '18', 'uploads/kegiatan/kegiatan-18-1787202577-5ea54e75.jpeg', NULL, '2026-08-20 05:09:37');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('169', '18', 'uploads/kegiatan/kegiatan-18-1787202577-ea742fc9.jpeg', NULL, '2026-08-20 05:09:37');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('170', '18', 'uploads/kegiatan/kegiatan-18-1787202577-2a2e8610.jpeg', NULL, '2026-08-20 05:09:37');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('171', '18', 'uploads/kegiatan/kegiatan-18-1787202577-acd8913c.jpeg', NULL, '2026-08-20 05:09:37');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('172', '18', 'uploads/kegiatan/kegiatan-18-1787202577-4c3b82ad.jpeg', NULL, '2026-08-20 05:09:37');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('173', '18', 'uploads/kegiatan/kegiatan-18-1787202577-9a094786.jpeg', NULL, '2026-08-20 05:09:37');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('174', '18', 'uploads/kegiatan/kegiatan-18-1787202577-e02c39f2.jpeg', NULL, '2026-08-20 05:09:37');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('175', '18', 'uploads/kegiatan/kegiatan-18-1787202577-030b861d.jpeg', NULL, '2026-08-20 05:09:37');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('176', '18', 'uploads/kegiatan/kegiatan-18-1787202577-ef6ace97.jpeg', NULL, '2026-08-20 05:09:37');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('177', '18', 'uploads/kegiatan/kegiatan-18-1787202577-623324aa.jpeg', NULL, '2026-08-20 05:09:37');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('178', '18', 'uploads/kegiatan/kegiatan-18-1787202577-cad61314.jpeg', NULL, '2026-08-20 05:09:37');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('179', '18', 'uploads/kegiatan/kegiatan-18-1787202577-99d289bb.jpeg', NULL, '2026-08-20 05:09:37');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('180', '17', 'uploads/kegiatan/kegiatan-17-1787206284-ee734042.jpeg', NULL, '2026-08-20 06:11:24');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('181', '19', 'uploads/kegiatan/kegiatan-19-1787207061-9cc9c295.jpeg', NULL, '2026-08-20 06:24:21');
INSERT INTO `kegiatan_foto` (`id`, `kegiatan_id`, `path_foto`, `keterangan`, `diunggah_pada`) VALUES ('182', '19', 'uploads/kegiatan/kegiatan-19-1787207061-4fe5ef3d.jpeg', NULL, '2026-08-20 06:24:21');

DROP TABLE IF EXISTS `kegiatan_rundown`;
CREATE TABLE `kegiatan_rundown` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kegiatan_id` INT NULL,
  `waktu` TEXT NULL,
  `agenda` TEXT NULL,
  `pic` TEXT NULL,
  `keterangan` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `kontak_humas`;
CREATE TABLE `kontak_humas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` TEXT NULL,
  `jabatan` TEXT NULL,
  `no_hp` TEXT NULL,
  `wilayah` TEXT NULL,
  `keterangan` TEXT NULL,
  `dibuat_pada` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `kontak_humas` (`id`, `nama`, `jabatan`, `no_hp`, `wilayah`, `keterangan`, `dibuat_pada`) VALUES ('1', 'Bapak Kepala Kelurahan', 'Kepala Kelurahan Pal Lima', '081234567890', 'Pal Lima', 'Lurah Pal Lima, Pontianak Barat', '2026-07-26 16:05:46');
INSERT INTO `kontak_humas` (`id`, `nama`, `jabatan`, `no_hp`, `wilayah`, `keterangan`, `dibuat_pada`) VALUES ('2', 'Bhabinkamtibmas Pal Lima', 'Bhabinkamtibmas (Polsek)', '081298765432', 'Pal Lima', 'Kontak Kamtibmas Kepolisian', '2026-07-26 16:05:46');
INSERT INTO `kontak_humas` (`id`, `nama`, `jabatan`, `no_hp`, `wilayah`, `keterangan`, `dibuat_pada`) VALUES ('3', 'Babinsa Pal Lima', 'Babinsa (Koramil)', '081345678901', 'Pal Lima', 'Kontak Pertahanan/TNI Koramil', '2026-07-26 16:05:46');
INSERT INTO `kontak_humas` (`id`, `nama`, `jabatan`, `no_hp`, `wilayah`, `keterangan`, `dibuat_pada`) VALUES ('4', 'Kepala Puskesmas Pal Lima', 'Kepala Puskesmas / Nakes', '081567890123', 'Pal Lima', 'Kontak Layanan Kesehatan Posko', '2026-07-26 16:05:46');
INSERT INTO `kontak_humas` (`id`, `nama`, `jabatan`, `no_hp`, `wilayah`, `keterangan`, `dibuat_pada`) VALUES ('5', 'Ketua RW 01', 'Ketua RW / Tokoh Masyarakat', '081678901234', 'RW 01 Pal Lima', 'Ketua RW Sub-Wilayah Posko', '2026-07-26 16:05:46');
INSERT INTO `kontak_humas` (`id`, `nama`, `jabatan`, `no_hp`, `wilayah`, `keterangan`, `dibuat_pada`) VALUES ('6', 'Ketua Karang Taruna', 'Ketua Pemuda / Karang Taruna', '081789012345', 'Pal Lima', 'Koordinator Pemuda Setempat', '2026-07-26 16:05:46');
INSERT INTO `kontak_humas` (`id`, `nama`, `jabatan`, `no_hp`, `wilayah`, `keterangan`, `dibuat_pada`) VALUES ('7', 'Ibu Kania Khairunnisa, M.Psi.', 'Dosen Pembimbing Lapangan (DPL)', '081890123456', 'UMP', 'DPL Kelompok 12 UMP', '2026-07-26 16:05:46');

DROP TABLE IF EXISTS `login_log`;
CREATE TABLE `login_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` TEXT NULL,
  `berhasil` VARCHAR(255) NULL,
  `ip_address` TEXT NULL,
  `waktu` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('57', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-21 16:47:15');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('58', 'rizkitrisaputra', '0', '127.0.0.1', '2026-07-21 16:50:50');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('59', 'rizkitrisaputra', '1', '127.0.0.1', '2026-07-21 16:51:05');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('60', 'rizkitrisaputra', '1', '127.0.0.1', '2026-07-21 16:51:11');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('61', 'rizkitrisaputra', '1', '172.18.0.1', '2026-07-21 16:51:55');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('62', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-21 16:54:14');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('63', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-21 17:34:38');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('64', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-21 18:36:23');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('65', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-21 18:56:25');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('66', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-21 19:01:36');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('67', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-21 19:05:03');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('68', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-21 19:05:21');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('69', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-21 19:08:58');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('70', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-21 19:12:56');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('71', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-21 19:16:50');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('72', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-21 19:27:38');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('73', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-21 19:31:33');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('74', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-21 19:34:53');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('75', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-21 19:57:26');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('76', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-21 20:00:11');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('77', 'anggierahmawati', '1', '172.18.0.1', '2026-07-21 20:00:34');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('78', 'virahmanda', '1', '172.18.0.1', '2026-07-21 20:01:07');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('79', 'tiarafitriani', '1', '172.18.0.1', '2026-07-21 20:11:14');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('80', 'sitialiyah', '1', '172.18.0.1', '2026-07-21 20:11:54');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('81', 'rizkitrisaputra', '1', '172.18.0.1', '2026-07-21 20:12:22');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('82', 'virahmandaabeliaismaya', '1', '172.18.0.1', '2026-07-21 20:12:55');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('83', 'anggirahmawati', '1', '172.18.0.1', '2026-07-21 20:13:30');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('84', 'halimahtusadiah', '1', '172.18.0.1', '2026-07-21 20:13:48');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('85', 'fathurrahman', '1', '172.18.0.1', '2026-07-21 20:14:50');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('86', 'dwiseptiani', '0', '172.18.0.1', '2026-07-21 20:15:11');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('87', 'kaniakhairunnisa', '0', '172.18.0.1', '2026-07-21 20:15:29');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('88', 'kaniakhairunnisa', '0', '172.18.0.1', '2026-07-21 20:15:42');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('89', 'kaniakhairunnisa', '0', '172.18.0.1', '2026-07-21 20:15:42');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('90', 'kania', '1', '172.18.0.1', '2026-07-21 20:17:28');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('91', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-21 20:21:01');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('92', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-21 20:33:38');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('93', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-22 00:07:00');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('94', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-22 00:21:57');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('95', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-22 00:29:05');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('96', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-22 00:29:59');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('97', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-22 00:30:18');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('98', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-22 00:31:55');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('99', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-22 00:58:00');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('100', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-22 00:59:49');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('101', 'muhammadfiqrimahendra', '1', '::1', '2026-07-22 01:44:54');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('102', 'muhammadfiqrimahendra', '1', '::1', '2026-07-22 01:49:32');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('103', 'muhammadfiqrimahendra', '1', '::1', '2026-07-22 01:49:52');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('104', 'diah', '1', '::1', '2026-07-22 01:54:07');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('105', 'muhammadfiqrimahendra', '1', '::1', '2026-07-22 01:56:20');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('106', 'muhammadfiqrimahendra', '1', '::1', '2026-07-22 01:57:21');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('107', 'muhammadfiqrimahendra', '1', '::1', '2026-07-22 01:57:48');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('108', 'kania', '1', '::1', '2026-07-22 01:58:43');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('109', 'kania', '1', '::1', '2026-07-22 02:01:56');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('110', 'muhammadfiqrimahendra', '1', '172.18.0.1', '2026-07-22 09:17:49');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('111', 'kania', '1', '172.18.0.1', '2026-07-22 10:06:52');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('112', 'kania', '1', '172.18.0.1', '2026-07-22 10:07:28');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('113', 'kania', '1', '182.11.129.28', '2026-07-22 03:51:43');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('114', 'muhammadfiqrimahendra', '1', '182.11.129.28', '2026-07-22 03:52:36');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('115', 'tiarafitriani', '1', '182.11.153.234', '2026-07-22 04:03:19');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('116', 'muhammadfiqrimahendra', '1', '182.11.129.28', '2026-07-22 05:37:03');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('117', 'muhammadfiqrimahendra', '1', '182.11.129.28', '2026-07-22 05:42:01');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('118', 'muhammadfiqrimahendra', '1', '182.11.129.28', '2026-07-22 05:53:44');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('119', 'muhammadfiqrimahendra', '1', '182.11.129.28', '2026-07-22 05:58:45');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('120', 'muhammadfiqrimahendra', '1', '182.11.129.28', '2026-07-22 07:26:35');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('121', 'muhammadfiqrimahendra', '1', '182.11.129.28', '2026-07-22 07:44:09');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('122', 'adit', '1', '182.2.105.199', '2026-07-22 09:33:06');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('123', 'muhammadfiqrimahendra', '1', '182.11.129.28', '2026-07-22 09:37:40');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('124', 'muhammadfiqrimahendra', '1', '182.11.129.28', '2026-07-26 13:47:34');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('125', 'muhammadfiqrimahendra', '1', '182.11.152.70', '2026-07-26 14:52:00');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('126', 'muhammadfiqrimahendra', '1', '182.11.152.70', '2026-07-26 14:57:33');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('127', 'muhammadfiqrimahendra', '1', '182.11.152.70', '2026-07-26 15:08:21');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('128', 'muhammadfiqrimahendra', '1', '182.11.152.70', '2026-07-26 15:13:29');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('129', 'rizkitrisaputra', '1', '182.11.152.70', '2026-07-26 15:56:58');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('130', 'sebastianusaditia', '1', '182.11.152.70', '2026-07-26 15:57:49');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('131', 'muhammadfiqrimahendra', '1', '182.11.152.70', '2026-07-27 11:27:57');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('132', 'sebastianusaditia', '1', '182.11.152.70', '2026-07-27 12:13:38');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('133', 'sebastianusaditia', '1', '182.11.152.70', '2026-07-27 13:59:23');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('134', 'sebastianusaditia', '1', '182.11.152.70', '2026-07-27 16:03:31');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('135', 'muhammadfiqrimahendra', '1', '182.11.152.70', '2026-07-27 16:05:24');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('136', 'muhammadfiqrimahendra', '1', '182.11.152.70', '2026-07-27 16:09:41');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('137', 'muhammadfiqrimahendra', '1', '182.11.152.70', '2026-07-27 16:35:32');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('138', 'kania', '1', '182.11.152.70', '2026-07-27 16:49:43');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('139', 'kania', '1', '182.11.152.70', '2026-07-27 17:03:37');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('140', 'muhammadfiqrimahendra', '1', '182.11.152.70', '2026-07-27 17:04:24');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('141', 'muhammadfiqrimahendra', '1', '182.11.152.70', '2026-07-27 17:18:49');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('142', 'sebastianusaditia', '1', '182.11.152.70', '2026-07-27 17:28:49');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('143', 'muhammadfiqrimahendra', '1', '182.11.130.139', '2026-07-29 13:33:04');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('144', 'muhammadfiqrimahendra', '1', '114.10.137.248', '2026-08-03 12:43:32');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('145', 'muhammadfiqrimahendra', '1', '114.10.137.248', '2026-08-03 13:09:58');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('146', 'muhammadfiqrimahendra', '1', '114.10.137.194', '2026-08-04 01:21:39');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('147', 'muhammadfiqrimahendra', '1', '114.10.137.210', '2026-08-04 06:44:32');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('148', 'muhammadfiqrimahendra', '1', '114.10.137.232', '2026-08-04 07:43:45');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('149', 'muhammadfiqrimahendra', '1', '182.2.100.40', '2026-08-04 10:49:09');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('150', 'muhammadfiqrimahendra', '1', '128.14.66.169', '2026-08-05 00:54:40');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('151', 'muhammadfiqrimahendra', '1', '114.10.137.223', '2026-08-05 07:42:35');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('152', 'muhammadfiqrimahendra', '1', '114.10.137.253', '2026-08-05 15:00:53');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('153', 'muhammadfiqrimahendra', '1', '114.10.137.197', '2026-08-08 03:59:56');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('154', 'muhammadfiqrimahendra', '1', '114.10.137.229', '2026-08-08 11:56:32');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('155', 'muhammadfiqrimahendra', '1', '114.10.137.229', '2026-08-08 13:08:05');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('156', 'muhammadfiqrimahendra', '1', '114.10.137.229', '2026-08-08 16:12:44');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('157', 'muhammadfiqrimahendra', '1', '114.10.137.229', '2026-08-08 23:00:20');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('158', 'muhammadfiqrimahendra', '1', '114.10.137.195', '2026-08-11 11:57:46');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('159', 'muhammadfiqrimahendra', '1', '114.10.137.221', '2026-08-12 02:19:54');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('160', 'muhammadfiqrimahendra', '1', '182.2.101.28', '2026-08-12 03:35:35');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('161', 'muhammadfiqrimahendra', '1', '182.2.101.28', '2026-08-12 05:42:37');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('162', 'muhammadfiqrimahendra', '1', '182.2.101.28', '2026-08-12 05:57:45');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('163', 'muhammadfiqrimahendra', '1', '182.2.101.28', '2026-08-12 06:09:48');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('164', 'muhammadfiqrimahendra', '1', '114.10.137.221', '2026-08-12 06:21:05');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('165', 'muhammadfiqrimahendra', '1', '114.10.137.221', '2026-08-12 06:27:59');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('166', 'muhammadfiqrimahendra', '1', '114.10.137.221', '2026-08-12 06:30:42');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('167', 'muhammadfiqrimahendra', '1', '182.2.101.28', '2026-08-12 07:13:57');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('168', 'muhammadfiqrimahendra', '1', '114.10.137.193', '2026-08-17 03:58:34');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('169', 'muhammadfiqrimahendra', '1', '114.10.137.255', '2026-08-20 04:29:12');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('170', 'muhammadfiqrimahendra', '1', '114.10.137.255', '2026-08-20 06:11:02');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('171', 'muhammadfiqrimahendra', '1', '182.11.152.252', '2026-08-23 10:56:51');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('172', 'muhammadfiqrimahendra', '0', '182.11.152.252', '2026-08-23 11:39:18');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('173', 'muhammadfiqrimahendra', '1', '182.11.152.252', '2026-08-23 11:39:34');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('174', 'muhammadfiqrimahendra', '1', '114.10.137.242', '2026-08-24 05:21:07');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('175', 'muhammadfiqrimahendra', '0', '114.10.137.210', '2026-08-26 09:45:34');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('176', 'muhammadfiqrimahendra', '1', '114.10.137.210', '2026-08-26 09:45:37');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('177', 'muhammadfiqrimahendra', '0', '182.11.130.165', '2026-08-27 09:14:22');
INSERT INTO `login_log` (`id`, `username`, `berhasil`, `ip_address`, `waktu`) VALUES ('178', 'muhammadfiqrimahendra', '1', '182.11.130.165', '2026-08-27 09:14:30');

DROP TABLE IF EXISTS `lokasi_prokja`;
CREATE TABLE `lokasi_prokja` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `program_kerja_id` INT NULL,
  `nama_lokasi` TEXT NULL,
  `kategori_lokasi` TEXT NULL,
  `latitude` VARCHAR(255) NULL,
  `longitude` VARCHAR(255) NULL,
  `keterangan` TEXT NULL,
  `pengunggah_nama` TEXT NULL,
  `dibuat_pada` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `lokasi_prokja` (`id`, `program_kerja_id`, `nama_lokasi`, `kategori_lokasi`, `latitude`, `longitude`, `keterangan`, `pengunggah_nama`, `dibuat_pada`) VALUES ('5', NULL, 'Kantor Kelurahan Pal Lima (Pusat Layanan)', 'Posko KKN / Sekretariat', '-0.036937', '109.288572', 'Pusat Koordinasi dan Layanan Administrasi KKN Kelompok 12', 'Kelompok 12', '2026-08-04 07:08:28');
INSERT INTO `lokasi_prokja` (`id`, `program_kerja_id`, `nama_lokasi`, `kategori_lokasi`, `latitude`, `longitude`, `keterangan`, `pengunggah_nama`, `dibuat_pada`) VALUES ('11', NULL, 'SD NEGERI 13 KECAMATAN PONTIANAK BARAT', 'Sekolah & Pendidikan', '-0.0377', '109.2877', 'Sosialiasi Perlindungan Anak\r\nSosialiasi Psikoedukasi Pencegahan Bullying\r\nEdukasi Cuci Tanggan Pakai Sabun (Ctps)', 'Muhammad Fiqri Mahendra', '2026-08-04 07:25:48');
INSERT INTO `lokasi_prokja` (`id`, `program_kerja_id`, `nama_lokasi`, `kategori_lokasi`, `latitude`, `longitude`, `keterangan`, `pengunggah_nama`, `dibuat_pada`) VALUES ('15', '8', 'UMKM LIDYA TAPE', 'UMKM & Ekonomi Kreatif', '-0.0208', '109.2830124', 'Melakukan Survei Lokasi Umkm dan Melihat Proses Pembuatan tap', 'Muhammad Fiqri Mahendra', '2026-08-04 09:46:43');
INSERT INTO `lokasi_prokja` (`id`, `program_kerja_id`, `nama_lokasi`, `kategori_lokasi`, `latitude`, `longitude`, `keterangan`, `pengunggah_nama`, `dibuat_pada`) VALUES ('16', NULL, 'Tpq Al-ihsan', 'Sekolah & Pendidikan', '-0.0270916', '109.2876881', 'Pelaksanaan kegitan belajar mengaji dan mewarnai', 'Muhammad Fiqri Mahendra', '2026-08-08 16:21:18');
INSERT INTO `lokasi_prokja` (`id`, `program_kerja_id`, `nama_lokasi`, `kategori_lokasi`, `latitude`, `longitude`, `keterangan`, `pengunggah_nama`, `dibuat_pada`) VALUES ('17', NULL, 'Posko Kkn Kelompok 12 Ump', 'Posko KKN', '-0.0293725', '109.2868363', 'Lokasi Posko dan Kantor Kkn kelompok 12 ump', 'Muhammad Fiqri Mahendra', '2026-08-11 12:09:17');
INSERT INTO `lokasi_prokja` (`id`, `program_kerja_id`, `nama_lokasi`, `kategori_lokasi`, `latitude`, `longitude`, `keterangan`, `pengunggah_nama`, `dibuat_pada`) VALUES ('30', NULL, 'Posyandu Artha Land', 'Posyandu & Kesehatan', '-0.027089', '109.2876', 'Senam Bersama Kader Posyandu Artha Land dan Sosialisasi Stunting\r\npenimbangan dan pengukuran badan anak usia dini', 'Muhammad Fiqri Mahendra', '2026-08-11 16:05:07');
INSERT INTO `lokasi_prokja` (`id`, `program_kerja_id`, `nama_lokasi`, `kategori_lokasi`, `latitude`, `longitude`, `keterangan`, `pengunggah_nama`, `dibuat_pada`) VALUES ('33', NULL, 'Sma 11 Pontianak', 'Sekolah & Pendidikan', '-0.0148', '109.294', 'Sosialisai Psikoedukasi Pencegahan Bullying,Dampak Serangan Cyber,Promosi Kampus Ump', 'Muhammad Fiqri Mahendra', '2026-08-20 04:58:34');
INSERT INTO `lokasi_prokja` (`id`, `program_kerja_id`, `nama_lokasi`, `kategori_lokasi`, `latitude`, `longitude`, `keterangan`, `pengunggah_nama`, `dibuat_pada`) VALUES ('34', NULL, 'Posyandu Artha Land', 'Posyandu & Kesehatan', '-0.0246077', '109.2900881', 'Melakukan Penimbangan berat badan,pengukuran tinggi badan,imunisasi', 'Muhammad Fiqri Mahendra', '2026-08-20 06:29:59');

DROP TABLE IF EXISTS `materi_presentasi`;
CREATE TABLE `materi_presentasi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `program_kerja_id` INT NULL,
  `judul_materi` TEXT NULL,
  `tujuan_program` TEXT NULL,
  `sasaran_program` TEXT NULL,
  `dampak_program` TEXT NULL,
  `nama_kelompok` TEXT NULL,
  `pemateri_nama` TEXT NULL,
  `pemateri_foto` TEXT NULL,
  `path_file` TEXT NULL,
  `tipe_file` TEXT NULL,
  `ukuran_file` INT NULL,
  `dibuat_pada` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `materi_presentasi` (`id`, `program_kerja_id`, `judul_materi`, `tujuan_program`, `sasaran_program`, `dampak_program`, `nama_kelompok`, `pemateri_nama`, `pemateri_foto`, `path_file`, `tipe_file`, `ukuran_file`, `dibuat_pada`) VALUES ('37', NULL, 'Edukasi Perlindungan Anak', 'Menciptakan lingkungan yang aman serta membekali anak dengan pengetahuan dasar untuk melindungi diri sendiri.', 'Siswa Sekolah Dasar kelas V', 'Meningkatnya pengetahuan dan kesadaran siswa tentang perlindungan diri sehingga mampu mengenali bahaya, menjaga diri, dan berani melapor kepada orang dewasa yang dipercaya.', 'Divisi Acara', 'Rizki Tri Saputra', 'img/rizki tri saputra.jpg', 'uploads/materi/materi-1785829875-6c09a5ee.pptx', 'pptx', '462812', '2026-08-04 07:51:15');
INSERT INTO `materi_presentasi` (`id`, `program_kerja_id`, `judul_materi`, `tujuan_program`, `sasaran_program`, `dampak_program`, `nama_kelompok`, `pemateri_nama`, `pemateri_foto`, `path_file`, `tipe_file`, `ukuran_file`, `dibuat_pada`) VALUES ('38', NULL, 'Dampak Serangan Cyber', 'Memberikan Pemahaman tentang serangan Cyber', 'Siswa Kelas 11 Sma 11 pontianak', 'Memberikan Pencengaha terhadap serangan cyber', 'Divisi Acara', 'Muhammad Fiqri Mahendra', 'img/Muhammad Fiqri Mahendra.jpg', 'uploads/materi/materi-1785830372-12930bd2.pptx', 'pptx', '1414616', '2026-08-04 07:59:32');
INSERT INTO `materi_presentasi` (`id`, `program_kerja_id`, `judul_materi`, `tujuan_program`, `sasaran_program`, `dampak_program`, `nama_kelompok`, `pemateri_nama`, `pemateri_foto`, `path_file`, `tipe_file`, `ukuran_file`, `dibuat_pada`) VALUES ('39', NULL, 'Psikoedukasi Pencegahan Bullying', 'Meningkatkan pemahaman peserta mengenai pengertian, bentuk, penyebab, dan dampak bullying serta membekali peserta dengan pengetahuan dan keterampilan dasar untuk mencegah, mengenali, dan melindungi diri dari tindakan perundungan.', 'Siswa Kelas VI SDN 13 Pontianak dan Siswa Kelas XI SMA Negeri 11 Pontianak.', 'Meningkatnya kesadaran peserta tentang bahaya bullying.\r\nPeserta mampu mengenali berbagai bentuk bullying.\r\nTerciptanya lingkungan sekolah yang lebih aman, nyaman, dan saling menghargai.\r\nBerkurangnya perilaku bullying di lingkungan sekolah.', 'Divisi Acara', 'Sebastianus Aditia', 'img/Sebastianus Aditia.jpg', 'uploads/materi/materi-1785834787-d504c2cc.pptx', 'pptx', '41802', '2026-08-04 09:13:07');
INSERT INTO `materi_presentasi` (`id`, `program_kerja_id`, `judul_materi`, `tujuan_program`, `sasaran_program`, `dampak_program`, `nama_kelompok`, `pemateri_nama`, `pemateri_foto`, `path_file`, `tipe_file`, `ukuran_file`, `dibuat_pada`) VALUES ('40', NULL, 'Sosialisai Cuci Tanggan Pakai Sabun', 'Memberikan edukasi kepada peserta mengenai pentingnya mencuci tangan pakai sabun dengan langkah yang benar sebagai upaya menjaga kebersihan diri dan mencegah penyebaran penyakit.', 'Anak-anak dan siswa di lingkungan Kelurahan Pal Lima, khususnya peserta kegiatan sosialisasi.', 'Meningkatnya pengetahuan dan kesadaran peserta mengenai pentingnya menjaga kebersihan tangan serta terbentuknya kebiasaan mencuci tangan pakai sabun dalam kehidupan sehari-hari.', 'Divisi Acara', 'Khairunisa Salsabila', 'img/Khairunisa Salsabila.jpg', 'uploads/materi/materi-1786206933-d404e531.pdf', 'pdf', '2245513', '2026-08-08 16:35:33');

DROP TABLE IF EXISTS `notulensi_rapat`;
CREATE TABLE `notulensi_rapat` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tanggal` VARCHAR(255) NULL,
  `judul_rapat` TEXT NULL,
  `pembahasan` TEXT NULL,
  `keputusan` TEXT NULL,
  `peserta` TEXT NULL,
  `dibuat_pada` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `profil_desa`;
CREATE TABLE `profil_desa` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_desa` TEXT NULL,
  `kecamatan` TEXT NULL,
  `kabupaten` TEXT NULL,
  `jumlah_penduduk` TEXT NULL,
  `potensi` TEXT NULL,
  `deskripsi` TEXT NULL,
  `foto` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `profil_desa` (`id`, `nama_desa`, `kecamatan`, `kabupaten`, `jumlah_penduduk`, `potensi`, `deskripsi`, `foto`) VALUES ('1', 'Pal Lima', 'Pontianak Barat', 'Pontianak', ' 3.200 Jiwa', 'Pertanian & UMKM', 'Kelurahan Pal Lima merupakan bagian dari Kecamatan Pontianak Barat, Kota Pontianak, yang terus berkembang melalui pelayanan masyarakat, pembangunan, serta pemberdayaan warga menuju lingkungan yang maju dan sejahtera.', 'img/desa.jpg');

DROP TABLE IF EXISTS `program_kerja`;
CREATE TABLE `program_kerja` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bidang_id` INT NULL,
  `judul` TEXT NULL,
  `periode` TEXT NULL,
  `deskripsi` TEXT NULL,
  `gambar` TEXT NULL,
  `urutan` INT NULL,
  `status` TEXT NULL,
  `dibuat_pada` DATETIME NULL,
  `link` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `program_kerja` (`id`, `bidang_id`, `judul`, `periode`, `deskripsi`, `gambar`, `urutan`, `status`, `dibuat_pada`, `link`) VALUES ('10', '13', 'Lingkungan', 'Juli – Agustus 2026', '“Program kerja ini bertujuan mengolah sampah menjadi sumber energi alternatif melalui pembuatan tempat pembakaran sampah insinerator. Kegiatan ini diharapkan dapat membantu mengurangi penumpukan sampah serta meningkatkan kebersihan dan kepedulian masyarakat terhadap lingkungan.”', NULL, '0', 'terbit', '2026-08-24 05:29:27', 'https://kkn12.ct.ws/?i=1');

DROP TABLE IF EXISTS `program_kerja_foto`;
CREATE TABLE `program_kerja_foto` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `program_kerja_id` INT NULL,
  `path_foto` TEXT NULL,
  `judul` TEXT NULL,
  `deskripsi` TEXT NULL,
  `is_cover` VARCHAR(255) NULL,
  `dibuat_pada` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `program_kerja_foto` (`id`, `program_kerja_id`, `path_foto`, `judul`, `deskripsi`, `is_cover`, `dibuat_pada`) VALUES ('8', '10', 'uploads/program_kerja/program-10-1787549660-c194ccc9.jpeg', 'Hasil akhir Pembuatann Pembuatan Inserator', 'Hasil akhir dari Pembuataan Pembakaran Sampah Inserator dari Kelompok kkn 12 Pal lima', '1', '2026-08-24 05:34:19');
INSERT INTO `program_kerja_foto` (`id`, `program_kerja_id`, `path_foto`, `judul`, `deskripsi`, `is_cover`, `dibuat_pada`) VALUES ('9', '10', 'uploads/program_kerja/program-10-1787551023-379e0ee1.jpeg', NULL, NULL, '0', '2026-08-24 05:57:02');
INSERT INTO `program_kerja_foto` (`id`, `program_kerja_id`, `path_foto`, `judul`, `deskripsi`, `is_cover`, `dibuat_pada`) VALUES ('10', '10', 'uploads/program_kerja/program-10-1787551036-01ad273c.jpeg', NULL, NULL, '0', '2026-08-24 05:57:16');
INSERT INTO `program_kerja_foto` (`id`, `program_kerja_id`, `path_foto`, `judul`, `deskripsi`, `is_cover`, `dibuat_pada`) VALUES ('11', '10', 'uploads/program_kerja/program-10-1787551054-389bacfa.jpeg', NULL, NULL, '0', '2026-08-24 05:57:33');

DROP TABLE IF EXISTS `surat_masuk`;
CREATE TABLE `surat_masuk` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nomor_surat` TEXT NULL,
  `pengirim` TEXT NULL,
  `perihal` TEXT NULL,
  `tanggal_terima` VARCHAR(255) NULL,
  `keterangan` TEXT NULL,
  `dibuat_pada` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `video_kegiatan`;
CREATE TABLE `video_kegiatan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `program_kerja_id` INT NULL,
  `judul_video` TEXT NULL,
  `deskripsi` TEXT NULL,
  `pengunggah_nama` TEXT NULL,
  `pengunggah_foto` TEXT NULL,
  `path_video` TEXT NULL,
  `tipe_video` TEXT NULL,
  `ukuran_file` INT NULL,
  `dibuat_pada` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `video_kegiatan` (`id`, `program_kerja_id`, `judul_video`, `deskripsi`, `pengunggah_nama`, `pengunggah_foto`, `path_video`, `tipe_video`, `ukuran_file`, `dibuat_pada`) VALUES ('2', NULL, 'Kelompok KKN 12, Izin Tampil!', 'Perkenalkan, kami adalah Kelompok KKN 12 Universitas Muhammadiyah Pontianak yang akan melaksanakan pengabdian kepada masyarakat di Kelurahan Pal Lima. Video ini merupakan perkenalan seluruh anggota tim yang siap berkolaborasi, belajar, dan memberikan kontribusi terbaik melalui berbagai program kerja selama pelaksanaan Kuliah Kerja Nyata (KKN). Semoga kehadiran kami dapat memberikan manfaat serta menjalin hubungan yang baik dengan masyarakat. Mari bersama menciptakan perubahan yang positif! 💙✨', 'Muhammad Fiqri Mahendra', 'img/Muhammad Fiqri Mahendra.jpg', 'https://www.youtube.com/embed/FAMFrdoiBXY', 'youtube', NULL, '2026-07-22 09:26:30');
INSERT INTO `video_kegiatan` (`id`, `program_kerja_id`, `judul_video`, `deskripsi`, `pengunggah_nama`, `pengunggah_foto`, `path_video`, `tipe_video`, `ukuran_file`, `dibuat_pada`) VALUES ('3', NULL, 'Pelepasan dan Penyerahan Mahasiswa KKN Kelompok 12 kepada Kelurahan Pal Lima', 'Video ini mendokumentasikan kegiatan pelepasan Dosen Pembimbing Lapangan (DPL) dan penyerahan resmi mahasiswa KKN Kelompok 12 Universitas Muhammadiyah Pontianak kepada Kelurahan Pal Lima. Kegiatan ini meliputi perkenalan, penyampaian tujuan dan program kerja KKN, serta koordinasi awal bersama pihak kelurahan sebagai bentuk komitmen dalam menjalankan pengabdian kepada masyarakat.', 'Muhammad Fiqri Mahendra', 'img/Muhammad Fiqri Mahendra.jpg', 'https://youtube.com/shorts/70zgcRLL5_I?feature=share', 'youtube', NULL, '2026-07-27 11:45:10');
INSERT INTO `video_kegiatan` (`id`, `program_kerja_id`, `judul_video`, `deskripsi`, `pengunggah_nama`, `pengunggah_foto`, `path_video`, `tipe_video`, `ukuran_file`, `dibuat_pada`) VALUES ('4', NULL, 'Gotong Royong Bersama Warga Didis Permai 8', 'Kebersihan lingkungan dimulai dari kebersamaan. 💚 Mahasiswa KKN Kelompok 12 bersama warga Komplek Didis Permai 8 melaksanakan kegiatan gotong royong membersihkan lingkungan. Terima kasih kepada seluruh warga yang telah berpartisipasi. Semoga semangat kebersamaan ini terus terjaga. dan bersih-bersih posko', 'Muhammad Fiqri Mahendra', 'img/Muhammad Fiqri Mahendra.jpg', 'uploads/video_kegiatan/video_1785812300_6a71554c7cdb3.mp4', 'local', NULL, '2026-08-04 02:55:16');

SET FOREIGN_KEY_CHECKS=1;
