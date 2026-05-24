<?php
require_once __DIR__ . '/config/koneksi.php';

echo "Memulai migrasi database...\n";

$sql = [
    "ALTER TABLE orders ADD COLUMN paket ENUM('cuci_kering', 'cuci_setrika', 'express') NOT NULL DEFAULT 'cuci_kering' AFTER tgl_ambil",
    "ALTER TABLE orders ADD COLUMN berat FLOAT NOT NULL DEFAULT 1 AFTER paket"
];

foreach ($sql as $q) {
    if (mysqli_query($koneksi, $q)) {
        echo "Query berhasil: $q\n";
    } else {
        echo "Gagal eksekusi: " . mysqli_error($koneksi) . " (Mungkin sudah ada)\n";
    }
}
echo "Selesai.\n";
?>
