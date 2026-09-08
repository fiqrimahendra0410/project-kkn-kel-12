# 📌 Sistem Informasi & Web Portal KKN Kelompok 12

Selamat datang di repository resmi **Sistem Informasi & Web Portal Kegiatan KKN (Kuliah Kerja Nyata) Kelompok 12**. Website ini dirancang untuk mengelola informasi posko, dokumentasi kegiatan harian, buku lapangan, jadwal piket, hingga laporan program kerja secara terintegrasi.

---

## 🌟 Fitur Utama

- 📖 **Buku Lapangan Digital**: Pencatatan jurnal dan logbook kegiatan harian mahasiswa KKN.
- 📋 **Manajemen Program Kerja (Proker)**: Status pelaksanaan, detail kegiatan, dan lokasi prokja.
- 🗺️ **Informasi & Peta Posko**: Peta interaktif lokasi posko dan sebaran kegiatan.
- 📅 **Jadwal & Agenda**: Pengaturan jadwal piket harian posko dan rundown acara kegiatan.
- 📸 **Galeri Dokumentasi**: Upload & galeri foto serta video dokumentasi kegiatan KKN.
- ✉️ **Layanan Administrasi & Surat**: Pengelolaan dokumen dan surat-menyurat posko.
- 📊 **Materi Presentasi & Kontak Humas**: Pusat informasi publik dan materi ekspose KKN.
- 🔒 **Sistem Keamanan & Akun**: Fitur login/logout dan proteksi halaman admin posko.

---

## 🛠️ Teknologi yang Digunakan

- **Backend**: PHP Native
- **Database**: MySQL / MariaDB (disertai pendukung SQLite)
- **Frontend**: HTML5, Vanilla CSS3, JavaScript (ES6)
- **Peta Interaktif**: Mapbox / Leaflet JS
- **Server**: Apache (XAMPP / Hosting InfinityFree)

---

## 🚀 Panduan Cara Menjalankan di Lokal (XAMPP)

1. **Clone / Unduh Repository**:
   Unduh file ZIP dari repository ini atau jalankan:
   ```bash
   git clone https://github.com/fiqrimahendra0410/project-kkn-kel-12.git
   ```
2. **Pindahkan Folder**:
   Simpan folder project ke dalam direktori XAMPP Anda:
   `C:\xampp\htdocs\project kkn\`

3. **Import Database**:
   - Buka `http://localhost/phpmyadmin/`
   - Buat database baru dengan nama `if0_42466670_kkn12` (atau `database_kkn`).
   - Import file `database_kkn.sql` yang berada di dalam folder proyek.

4. **Konfigurasi Database**:
   Sesuaikan pengaturan koneksi pada file [`koneksi.php`](koneksi.php) jika menggunakan kredensial MySQL lokal.

5. **Jalankan Aplikasi**:
   Buka browser Anda dan akses alamat:
   `http://localhost/project%20kkn/`

---

## 👥 Tim KKN Kelompok 12

Terima kasih kepada seluruh anggota tim kelompok 12 atas partisipasi dan kontribusinya dalam pelaksanaan KKN dan pembangunan portal informasi ini.
