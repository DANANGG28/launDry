<?php
$pageTitle = 'Laporan Keuangan';
$activeMenu = 'laporan_keuangan';
$pageSubtitle = 'Ringkasan omset kotor dan bersih per periode';
require_once '../templates/header.php';

// ── Filter Periode ────────────────────────────────────────────
// Default: bulan berjalan
$tgl_awal  = isset($_GET['tgl_awal'])  && $_GET['tgl_awal']  !== '' ? $_GET['tgl_awal']  : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) && $_GET['tgl_akhir'] !== '' ? $_GET['tgl_akhir'] : date('Y-m-d');

// Sanitasi sederhana (format YYYY-MM-DD saja)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_awal))  $tgl_awal  = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_akhir)) $tgl_akhir = date('Y-m-d');

// Pastikan tgl_awal <= tgl_akhir, kalau terbalik kita tukar
if (strtotime($tgl_awal) > strtotime($tgl_akhir)) {
    $tmp = $tgl_awal;
    $tgl_awal = $tgl_akhir;
    $tgl_akhir = $tmp;
}

// Range BETWEEN harus mencakup seluruh hari akhir → tambahkan jam 23:59:59
$range_awal  = $tgl_awal  . ' 00:00:00';
$range_akhir = $tgl_akhir . ' 23:59:59';

// ── Query Ringkasan ───────────────────────────────────────────
// Omset kotor   = total seluruh transaksi (lunas + belum)
// Omset bersih  = total transaksi yang sudah lunas saja
// Piutang       = transaksi yang belum lunas
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

// ── Rekap Per Paket ───────────────────────────────────────────
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

// Mapping label paket biar konsisten
$labelPaket = [
    'cuci_kering'  => 'Cuci Kering',
    'cuci_setrika' => 'Cuci Setrika',
    'express'      => 'Express 1 Jam'
];

// ── Detail Transaksi pada Periode ─────────────────────────────
$q_detail = "SELECT * FROM orders 
             WHERE tgl_masuk BETWEEN '$range_awal' AND '$range_akhir'
             ORDER BY tgl_masuk DESC";
$res_detail = mysqli_query($koneksi, $q_detail);

// Helper untuk tampilkan label periode
function labelPeriode($awal, $akhir) {
    if ($awal === $akhir) return formatTanggal($awal);
    return formatTanggal($awal) . ' → ' . formatTanggal($akhir);
}
?>

<!-- ── Filter Periode Form ─────────────────────────────────── -->
<div class="card mb-5">
  <div class="card-body">
    <form method="GET" class="filter-bar" style="flex-wrap:wrap; gap:16px; align-items:flex-end;">
      <div class="form-group" style="margin-bottom:0; min-width:170px; flex:1;">
        <label class="form-label" style="font-size:12px;">Tanggal Mulai</label>
        <input type="date" name="tgl_awal" class="form-control" value="<?= htmlspecialchars($tgl_awal) ?>">
      </div>
      <div class="form-group" style="margin-bottom:0; min-width:170px; flex:1;">
        <label class="form-label" style="font-size:12px;">Tanggal Akhir</label>
        <input type="date" name="tgl_akhir" class="form-control" value="<?= htmlspecialchars($tgl_akhir) ?>">
      </div>
      <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:6px;">
          <?= getIcon('magnifying-glass', 'w-4 h-4') ?> Tampilkan
        </button>
        <a href="laporan_keuangan.php" class="btn btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:6px;">
          Reset
        </a>
        <button type="button" onclick="window.print()" class="btn btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:6px;">
          <?= getIcon('document-text', 'w-4 h-4') ?> Cetak
        </button>
        <a href="laporan_keuangan_export.php?tgl_awal=<?= urlencode($tgl_awal) ?>&tgl_akhir=<?= urlencode($tgl_akhir) ?>"
           class="btn btn-success btn-sm"
           style="display:inline-flex;align-items:center;gap:6px;">
          <?= getIcon('document-text', 'w-4 h-4') ?> Export Excel
        </a>
      </div>
    </form>
    <div class="text-xs text-muted" style="margin-top:12px; padding-top:12px; border-top:1px dashed #e2e8f0;">
      Periode aktif: <strong style="color:var(--accent);"><?= labelPeriode($tgl_awal, $tgl_akhir) ?></strong>
    </div>
  </div>
</div>

<!-- ── Stat Cards Omset ───────────────────────────────────── -->
<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-icon indigo"><?= getIcon('banknotes', 'w-8 h-8') ?></div>
    <div class="stat-info">
      <div class="stat-label">Omset Kotor</div>
      <div class="stat-value"><?= formatRupiah($sum['omset_kotor']) ?></div>
      <div class="stat-sub"><?= $sum['jml_order'] ?> transaksi</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon emerald"><?= getIcon('check-circle', 'w-8 h-8') ?></div>
    <div class="stat-info">
      <div class="stat-label">Omset Bersih (Lunas)</div>
      <div class="stat-value"><?= formatRupiah($sum['omset_bersih']) ?></div>
      <div class="stat-sub"><?= $sum['jml_lunas'] ?> order lunas</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon rose"><?= getIcon('clock', 'w-8 h-8') ?></div>
    <div class="stat-info">
      <div class="stat-label">Piutang (Belum Bayar)</div>
      <div class="stat-value"><?= formatRupiah($sum['piutang']) ?></div>
      <div class="stat-sub"><?= $sum['jml_belum'] ?> order belum lunas</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon amber"><?= getIcon('cube', 'w-8 h-8') ?></div>
    <div class="stat-info">
      <div class="stat-label">Total Berat Cucian</div>
      <div class="stat-value"><?= number_format($sum['total_berat'], 1, ',', '.') ?> Kg</div>
      <div class="stat-sub">Akumulasi periode</div>
    </div>
  </div>
</div>

<!-- ── Rekap Per Paket ────────────────────────────────────── -->
<div class="card mb-5">
  <div class="card-header">
    <span class="card-title" style="display:flex;align-items:center;gap:8px;">
      <?= getIcon('chart-pie', 'w-5 h-5') ?> Rekap Per Paket
    </span>
  </div>
  <?php if (mysqli_num_rows($res_paket) > 0): ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Paket</th>
            <th class="text-center">Jumlah Order</th>
            <th class="text-center">Total Berat</th>
            <th>Subtotal Kotor</th>
            <th>Subtotal Bersih</th>
            <th class="text-center">Kontribusi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($p = mysqli_fetch_assoc($res_paket)):
              $kontribusi = $sum['omset_kotor'] > 0
                  ? ($p['subtotal'] / $sum['omset_kotor']) * 100
                  : 0;
          ?>
          <tr>
            <td>
              <div class="font-medium">
                <?= isset($labelPaket[$p['paket']]) ? $labelPaket[$p['paket']] : ucfirst($p['paket']) ?>
              </div>
            </td>
            <td class="text-center"><?= $p['jml'] ?></td>
            <td class="text-center"><?= number_format($p['total_berat'], 1, ',', '.') ?> Kg</td>
            <td><strong><?= formatRupiah($p['subtotal']) ?></strong></td>
            <td style="color:#059669;"><strong><?= formatRupiah($p['subtotal_lunas']) ?></strong></td>
            <td>
              <div style="display:flex; align-items:center; gap:8px;">
                <div style="flex:1; height:8px; background:#f1f5f9; border-radius:4px; overflow:hidden; min-width:60px;">
                  <div style="width:<?= $kontribusi ?>%; height:100%; background:var(--accent); border-radius:4px;"></div>
                </div>
                <span class="text-xs font-medium" style="min-width:42px; text-align:right;">
                  <?= number_format($kontribusi, 1) ?>%
                </span>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <div class="empty-icon"><?= getIcon('inbox', 'w-16 h-16 text-slate-300 mx-auto') ?></div>
      <h3>Belum ada transaksi</h3>
      <p>Tidak ada data transaksi pada rentang periode yang dipilih.</p>
    </div>
  <?php endif; ?>
</div>

<!-- ── Detail Transaksi ───────────────────────────────────── -->
<div class="card">
  <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
    <span class="card-title" style="display:flex;align-items:center;gap:8px;">
      <?= getIcon('clipboard-document-list', 'w-5 h-5') ?>
      Detail Transaksi
    </span>
    <span class="badge" style="background:#eef2ff; color:var(--accent); border-color:#c7d2fe;">
      <?= mysqli_num_rows($res_detail) ?> transaksi
    </span>
  </div>
  <?php if (mysqli_num_rows($res_detail) > 0): ?>
    <div class="table-wrap">
      <table style="min-width:880px;">
        <thead>
          <tr>
            <th style="width:48px;" class="text-center">No</th>
            <th>Invoice</th>
            <th>Tanggal</th>
            <th>Pelanggan</th>
            <th>Paket</th>
            <th class="text-center">Berat</th>
            <th class="text-center">Status</th>
            <th class="text-center">Bayar</th>
            <th style="text-align:right;">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php $no = 1; while ($o = mysqli_fetch_assoc($res_detail)): ?>
          <tr>
            <td class="text-center text-muted"><?= $no++ ?></td>
            <td><strong><?= htmlspecialchars($o['kode_invoice']) ?></strong></td>
            <td style="white-space:nowrap;"><?= formatTanggal($o['tgl_masuk']) ?></td>
            <td>
              <div class="font-medium"><?= htmlspecialchars($o['nama_pelanggan']) ?></div>
              <?php if (!empty($o['no_telepon'])): ?>
                <div class="text-xs text-muted"><?= htmlspecialchars($o['no_telepon']) ?></div>
              <?php endif; ?>
            </td>
            <td><?= isset($labelPaket[$o['paket']]) ? $labelPaket[$o['paket']] : ucfirst($o['paket']) ?></td>
            <td class="text-center" style="white-space:nowrap;"><?= number_format($o['berat'], 1, ',', '.') ?> Kg</td>
            <td class="text-center"><?= badgeStatus($o['status_proses']) ?></td>
            <td class="text-center"><?= badgeBayar($o['status_bayar']) ?></td>
            <td style="text-align:right; white-space:nowrap;"><strong><?= formatRupiah($o['total_harga']) ?></strong></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- ── Ringkasan Footer (di luar tabel biar tidak sempit) ── -->
    <div style="border-top:1px solid var(--border); padding:16px 20px; background:#f8fafc;
                display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px;">
      <div>
        <div class="text-xs text-muted" style="margin-bottom:4px;">Total Berat</div>
        <div style="font-size:1.05rem; font-weight:700;">
          <?= number_format($sum['total_berat'], 1, ',', '.') ?> Kg
        </div>
      </div>
      <div>
        <div class="text-xs text-muted" style="margin-bottom:4px;">Total Omset Kotor</div>
        <div style="font-size:1.05rem; font-weight:700;">
          <?= formatRupiah($sum['omset_kotor']) ?>
        </div>
      </div>
      <div style="background:#ecfdf5; padding:10px 14px; border-radius:8px; border:1px solid #a7f3d0;">
        <div class="text-xs" style="color:#047857; margin-bottom:4px;">Total Omset Bersih (Lunas)</div>
        <div style="font-size:1.05rem; font-weight:700; color:#059669;">
          <?= formatRupiah($sum['omset_bersih']) ?>
        </div>
      </div>
      <div style="background:#fff1f2; padding:10px 14px; border-radius:8px; border:1px solid #fecdd3;">
        <div class="text-xs" style="color:#be123c; margin-bottom:4px;">Total Piutang (Belum)</div>
        <div style="font-size:1.05rem; font-weight:700; color:#e11d48;">
          <?= formatRupiah($sum['piutang']) ?>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <div class="empty-icon"><?= getIcon('inbox', 'w-16 h-16 text-slate-300 mx-auto') ?></div>
      <h3>Tidak ada transaksi</h3>
      <p>Tidak ditemukan transaksi pada periode ini. Coba ubah rentang tanggal.</p>
    </div>
  <?php endif; ?>
</div>

<style>
  /* Sederhana untuk tampilan saat di-print */
  @media print {
    .sidebar, .topbar, .filter-bar, .sidebar-overlay { display: none !important; }
    .main-content { margin-left: 0 !important; }
    .page-content { padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #e2e8f0; }
    body { background: #fff !important; }
  }
</style>

<?php require_once '../templates/footer.php'; ?>
