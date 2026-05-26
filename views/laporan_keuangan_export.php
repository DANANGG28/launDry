<?php
// views/laporan_keuangan_export.php
// Export laporan keuangan ke Excel (.xls) memakai HTML table + MIME header.
// Tidak pakai library pihak ketiga — cukup PHP native, file langsung kebuka di Excel.

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/helper.php';

// Auth check — hanya user login yang boleh download
if (session_status() === PHP_SESSION_NONE) session_start();
requireAuth();

// ── Filter Periode (sama persis seperti laporan_keuangan.php) ──
$tgl_awal  = isset($_GET['tgl_awal'])  && $_GET['tgl_awal']  !== '' ? $_GET['tgl_awal']  : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) && $_GET['tgl_akhir'] !== '' ? $_GET['tgl_akhir'] : date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_awal))  $tgl_awal  = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_akhir)) $tgl_akhir = date('Y-m-d');

if (strtotime($tgl_awal) > strtotime($tgl_akhir)) {
    $tmp = $tgl_awal; $tgl_awal = $tgl_akhir; $tgl_akhir = $tmp;
}

$range_awal  = $tgl_awal  . ' 00:00:00';
$range_akhir = $tgl_akhir . ' 23:59:59';

// ── Query Ringkasan ─────────────────────────────────────────────
$q_summary = "SELECT
        COUNT(id) AS jml_order,
        SUM(CASE WHEN status_bayar = 'lunas' THEN 1 ELSE 0 END) AS jml_lunas,
        SUM(CASE WHEN status_bayar = 'belum' THEN 1 ELSE 0 END) AS jml_belum,
        SUM(total_harga) AS omset_kotor,
        SUM(CASE WHEN status_bayar = 'lunas' THEN total_harga ELSE 0 END) AS omset_bersih,
        SUM(CASE WHEN status_bayar = 'belum' THEN total_harga ELSE 0 END) AS piutang,
        SUM(berat) AS total_berat
    FROM orders
    WHERE tgl_masuk BETWEEN '$range_awal' AND '$range_akhir'";
$res_summary = mysqli_query($koneksi, $q_summary);
$sum = mysqli_fetch_assoc($res_summary);
foreach ($sum as $k => $v) { if (is_null($v)) $sum[$k] = 0; }

// ── Rekap Per Paket ─────────────────────────────────────────────
$q_paket = "SELECT
        paket,
        COUNT(id) AS jml,
        SUM(berat) AS total_berat,
        SUM(total_harga) AS subtotal,
        SUM(CASE WHEN status_bayar = 'lunas' THEN total_harga ELSE 0 END) AS subtotal_lunas
    FROM orders
    WHERE tgl_masuk BETWEEN '$range_awal' AND '$range_akhir'
    GROUP BY paket
    ORDER BY subtotal DESC";
$res_paket = mysqli_query($koneksi, $q_paket);

$labelPaket = [
    'cuci_kering'  => 'Cuci Kering',
    'cuci_setrika' => 'Cuci Setrika',
    'express'      => 'Express 1 Jam'
];

// ── Detail Transaksi ────────────────────────────────────────────
$q_detail = "SELECT * FROM orders
             WHERE tgl_masuk BETWEEN '$range_awal' AND '$range_akhir'
             ORDER BY tgl_masuk ASC";
$res_detail = mysqli_query($koneksi, $q_detail);

// ── Set HTTP Header agar browser download sebagai .xls ──────────
$namaFile = 'Laporan_Keuangan_' . $tgl_awal . '_sd_' . $tgl_akhir . '.xls';

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$namaFile\"");
header("Cache-Control: max-age=0");
header("Pragma: public");

// BOM UTF-8 supaya karakter Indonesia (Rp, é, dll.) tidak rusak di Excel
echo "\xEF\xBB\xBF";
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
  <meta charset="UTF-8">
  <!--[if gte mso 9]>
  <xml>
    <x:ExcelWorkbook>
      <x:ExcelWorksheets>
        <x:ExcelWorksheet>
          <x:Name>Laporan Keuangan</x:Name>
          <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
        </x:ExcelWorksheet>
      </x:ExcelWorksheets>
    </x:ExcelWorkbook>
  </xml>
  <![endif]-->
  <style>
    table { border-collapse: collapse; }
    th, td { border: 1px solid #333; padding: 6px 10px; font-family: Calibri, Arial, sans-serif; font-size: 11pt; }
    th { background: #4f46e5; color: #fff; text-align: center; }
    .title { font-size: 16pt; font-weight: bold; }
    .subtitle { font-size: 10pt; color: #555; }
    .section { background: #e0e7ff; font-weight: bold; }
    .right { text-align: right; }
    .center { text-align: center; }
    .total { background: #f1f5f9; font-weight: bold; }
    .grand-total { background: #ecfdf5; font-weight: bold; color: #059669; }
  </style>
</head>
<body>

<!-- ── Judul Laporan ─────────────────────────────────────────── -->
<table>
  <tr><td colspan="8" class="title">LAPORAN KEUANGAN — launDry</td></tr>
  <tr><td colspan="8" class="subtitle">Periode: <?= htmlspecialchars(formatTanggal($tgl_awal)) ?> s/d <?= htmlspecialchars(formatTanggal($tgl_akhir)) ?></td></tr>
  <tr><td colspan="8" class="subtitle">Dicetak: <?= date('d-m-Y H:i:s') ?></td></tr>
  <tr><td colspan="8">&nbsp;</td></tr>
</table>

<!-- ── Ringkasan Omset ──────────────────────────────────────── -->
<table>
  <tr><td colspan="2" class="section">RINGKASAN OMSET</td></tr>
  <tr><td>Jumlah Transaksi</td><td class="right"><?= (int)$sum['jml_order'] ?></td></tr>
  <tr><td>Order Lunas</td><td class="right"><?= (int)$sum['jml_lunas'] ?></td></tr>
  <tr><td>Order Belum Bayar</td><td class="right"><?= (int)$sum['jml_belum'] ?></td></tr>
  <tr><td>Total Berat Cucian</td><td class="right"><?= number_format($sum['total_berat'], 1, ',', '.') ?> Kg</td></tr>
  <tr><td>Omset Kotor</td><td class="right"><?= formatRupiah($sum['omset_kotor']) ?></td></tr>
  <tr class="grand-total"><td>Omset Bersih (Lunas)</td><td class="right"><?= formatRupiah($sum['omset_bersih']) ?></td></tr>
  <tr><td>Piutang (Belum Bayar)</td><td class="right"><?= formatRupiah($sum['piutang']) ?></td></tr>
</table>

<br>

<!-- ── Rekap Per Paket ──────────────────────────────────────── -->
<table>
  <tr><td colspan="6" class="section">REKAP PER PAKET</td></tr>
  <tr>
    <th>Paket</th>
    <th>Jumlah Order</th>
    <th>Total Berat (Kg)</th>
    <th>Subtotal Kotor</th>
    <th>Subtotal Bersih</th>
    <th>Kontribusi</th>
  </tr>
  <?php if (mysqli_num_rows($res_paket) > 0): ?>
    <?php while ($p = mysqli_fetch_assoc($res_paket)):
        $kontribusi = $sum['omset_kotor'] > 0 ? ($p['subtotal'] / $sum['omset_kotor']) * 100 : 0;
        $namaPaket = isset($labelPaket[$p['paket']]) ? $labelPaket[$p['paket']] : ucfirst($p['paket']);
    ?>
    <tr>
      <td><?= htmlspecialchars($namaPaket) ?></td>
      <td class="center"><?= (int)$p['jml'] ?></td>
      <td class="center"><?= number_format($p['total_berat'], 1, ',', '.') ?></td>
      <td class="right"><?= formatRupiah($p['subtotal']) ?></td>
      <td class="right"><?= formatRupiah($p['subtotal_lunas']) ?></td>
      <td class="center"><?= number_format($kontribusi, 1, ',', '.') ?>%</td>
    </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="6" class="center">Tidak ada data paket pada periode ini</td></tr>
  <?php endif; ?>
</table>

<br>

<!-- ── Detail Transaksi ─────────────────────────────────────── -->
<table>
  <tr><td colspan="8" class="section">DETAIL TRANSAKSI</td></tr>
  <tr>
    <th>No</th>
    <th>Kode Invoice</th>
    <th>Tgl Masuk</th>
    <th>Pelanggan</th>
    <th>Paket</th>
    <th>Berat (Kg)</th>
    <th>Status</th>
    <th>Bayar</th>
    <th>Total Harga</th>
  </tr>
  <?php
  $no = 1;
  if (mysqli_num_rows($res_detail) > 0):
    while ($o = mysqli_fetch_assoc($res_detail)):
      $namaPaket = isset($labelPaket[$o['paket']]) ? $labelPaket[$o['paket']] : ucfirst($o['paket']);
  ?>
    <tr>
      <td class="center"><?= $no++ ?></td>
      <td><?= htmlspecialchars($o['kode_invoice']) ?></td>
      <td><?= htmlspecialchars(formatTanggal($o['tgl_masuk'])) ?></td>
      <td>
        <?= htmlspecialchars($o['nama_pelanggan']) ?>
        <?php if (!empty($o['no_telepon'])): ?>
          (<?= htmlspecialchars($o['no_telepon']) ?>)
        <?php endif; ?>
      </td>
      <td><?= htmlspecialchars($namaPaket) ?></td>
      <td class="center"><?= number_format($o['berat'], 1, ',', '.') ?></td>
      <td class="center"><?= htmlspecialchars(ucfirst($o['status_proses'])) ?></td>
      <td class="center"><?= htmlspecialchars(ucfirst($o['status_bayar'])) ?></td>
      <td class="right"><?= formatRupiah($o['total_harga']) ?></td>
    </tr>
  <?php
    endwhile;
  else:
  ?>
    <tr><td colspan="9" class="center">Tidak ada transaksi pada periode ini</td></tr>
  <?php endif; ?>
  <tr class="total">
    <td colspan="5" class="right">TOTAL OMSET KOTOR</td>
    <td class="center"><?= number_format($sum['total_berat'], 1, ',', '.') ?></td>
    <td colspan="2"></td>
    <td class="right"><?= formatRupiah($sum['omset_kotor']) ?></td>
  </tr>
  <tr class="grand-total">
    <td colspan="8" class="right">TOTAL OMSET BERSIH (LUNAS)</td>
    <td class="right"><?= formatRupiah($sum['omset_bersih']) ?></td>
  </tr>
</table>

</body>
</html>
