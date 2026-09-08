-- Tabel materi_presentasi untuk menyimpan file presentasi program kerja
CREATE TABLE IF NOT EXISTS `materi_presentasi` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `program_kerja_id` INT NULL,
    `judul_materi` VARCHAR(255) NOT NULL,
    `nama_kelompok` VARCHAR(150) NOT NULL,
    `pemateri_nama` VARCHAR(150) NOT NULL,
    `pemateri_foto` VARCHAR(255) NOT NULL,
    `path_file` VARCHAR(255) NOT NULL,
    `tipe_file` VARCHAR(50) NULL,
    `ukuran_file` INT NULL,
    `dibuat_pada` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`program_kerja_id`) REFERENCES `program_kerja`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample seed data (jika tabel baru dibuat)
INSERT INTO `materi_presentasi` (`id`, `program_kerja_id`, `judul_materi`, `nama_kelompok`, `pemateri_nama`, `pemateri_foto`, `path_file`, `tipe_file`, `ukuran_file`) VALUES
(1, 1, 'Materi Literasi Digital & Pendampingan Siswa SD', 'Divisi Acara', 'Muhammad Fiqri Mahendra', 'img/Muhammad Fiqri Mahendra.jpg', 'img/20171130105645.jpg', 'pdf', 1048576),
(2, 2, 'Penyuluhan Pencegahan Stunting & Gizi Sehat', 'Divisi Acara', 'Virahmanda Abelia Ismaya', 'img/Virahmanda Abelia Ismaya.jpg', 'img/kkn26.jpeg', 'pptx', 2097152),
(3, 3, 'Strategi Digital Marketing & Desain Logo UMKM', 'Divisi Humas', 'Khairunisa Salsabila', 'img/Khairunisa Salsabila.jpg', 'img/foto org.jpg', 'pdf', 1572864)
ON DUPLICATE KEY UPDATE `judul_materi`=VALUES(`judul_materi`);
