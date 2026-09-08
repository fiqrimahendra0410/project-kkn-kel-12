<?php
require 'koneksi.php';
echo "Koneksi berhasil!<br><br>";
$data = $pdo->query("SELECT nama, jabatan FROM anggota")->fetchAll();
foreach ($data as $row) {
    echo $row['nama'] . " - " . $row['jabatan'] . "<br>";
}