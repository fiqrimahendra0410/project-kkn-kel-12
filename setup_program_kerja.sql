-- CREATE TABLE program_kerja_foto
CREATE TABLE IF NOT EXISTS program_kerja_foto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_kerja_id INT NOT NULL,
    path_foto VARCHAR(255) NOT NULL,
    judul VARCHAR(150) NULL,
    deskripsi TEXT NULL,
    is_cover TINYINT(1) DEFAULT 0,
    dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_kerja_id) REFERENCES program_kerja(id) ON DELETE CASCADE
);

-- ALTER TABLE program_kerja ADD COLUMN link VARCHAR(255) NULL;

-- SEED TABLE program_kerja with default categories
INSERT INTO program_kerja (id, bidang_id, judul, periode, deskripsi, link, status) VALUES
(1, 1, 'Program Pendidikan', 'Juli – Agustus 2026', 'Mengajar di sekolah dasar, bimbingan belajar, pelatihan literasi digital, serta pendampingan siswa meningkatkan kemampuan membaca dan berhitung.', 'https://kkn12.ct.ws/?i=1', 'terbit'),
(2, 2, 'Program Kesehatan', 'Juli – Agustus 2026', 'Penyuluhan kesehatan, edukasi pencegahan stunting, pemeriksaan kesehatan ringan, serta kegiatan Posyandu bersama kader desa.', 'https://kkn12.ct.ws/?i=1', 'terbit'),
(3, 3, 'Program Ekonomi', 'Juli – Agustus 2026', 'Pendampingan UMKM, pelatihan pemasaran digital, pembuatan logo produk, desain kemasan, dan promosi melalui media sosial.', 'https://kkn12.ct.ws/?i=1', 'terbit'),
(4, 4, 'Program Lingkungan', 'Juli – Agustus 2026', 'Kerja bakti bersama warga, penghijauan desa, penanaman pohon, dan edukasi pengelolaan sampah berbasis lingkungan.', 'https://kkn12.ct.ws/?i=1', 'terbit')
ON DUPLICATE KEY UPDATE judul=VALUES(judul), deskripsi=VALUES(deskripsi), link=VALUES(link);

-- SEED TABLE program_kerja_foto with initial photos
INSERT INTO program_kerja_foto (program_kerja_id, path_foto, judul, deskripsi, is_cover) VALUES
(1, 'img/20171130105645.jpg', 'Kegiatan Belajar Mengajar', 'Proses belajar mengajar bersama anak-anak Desa Sumber Rejo.', 1),
(2, 'img/kkn26.jpeg', 'Penyuluhan Kesehatan', 'Kegiatan posyandu dan penyuluhan gizi bagi masyarakat.', 1),
(3, 'img/foto org.jpg', 'Pelatihan UMKM', 'Pemberian materi digital marketing kepada pelaku UMKM.', 1),
(4, 'img/12.jpeg', 'Kerja Bakti Desa', 'Kerja bakti pembersihan lingkungan balai desa.', 1)
ON DUPLICATE KEY UPDATE path_foto=VALUES(path_foto);
