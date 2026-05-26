<?php
$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
$pageSubtitle = 'Ringkasan aktivitas laundry hari ini';
require_once '../templates/header.php';

// Get Stats
$q_stats = "SELECT 
    COUNT(id) as total,
    SUM(CASE WHEN status_proses = 'antrean' THEN 1 ELSE 0 END) as antrean,
    SUM(CASE WHEN status_proses = 'proses' THEN 1 ELSE 0 END) as proses,
    SUM(CASE WHEN status_proses = 'selesai' THEN 1 ELSE 0 END) as selesai,
    SUM(CASE WHEN status_proses = 'diambil' THEN 1 ELSE 0 END) as diambil,
    SUM(CASE WHEN status_bayar = 'lunas' THEN 1 ELSE 0 END) as lunas,
    SUM(CASE WHEN status_bayar = 'lunas' THEN total_harga ELSE 0 END) as total_pendapatan,
    SUM(CASE WHEN status_bayar = 'belum' THEN total_harga ELSE 0 END) as total_piutang
FROM orders";
$res_stats = mysqli_query($koneksi, $q_stats);
$stats = mysqli_fetch_assoc($res_stats);

foreach($stats as $key => $val) {
    if (is_null($val)) $stats[$key] = 0;
}

// Recent orders
$q_recent = "SELECT * FROM orders ORDER BY tgl_masuk DESC LIMIT 5";
$res_recent = mysqli_query($koneksi, $q_recent);
?>

<!-- Stats Grid -->
<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-icon indigo"><?= getIcon('cube', 'w-8 h-8') ?></div>
    <div class="stat-info">
      <div class="stat-label">Total Order</div>
      <div class="stat-value"><?= $stats['total'] ?></div>
      <div class="stat-sub">Semua transaksi</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon rose"><?= getIcon('clock', 'w-8 h-8') ?></div>
    <div class="stat-info">
      <div class="stat-label">Antrean</div>
      <div class="stat-value"><?= $stats['antrean'] ?></div>
      <div class="stat-sub">Menunggu dikerjakan</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon amber"><?= getIcon('arrow-path', 'w-8 h-8') ?></div>
    <div class="stat-info">
      <div class="stat-label">Sedang Proses</div>
      <div class="stat-value"><?= $stats['proses'] ?></div>
      <div class="stat-sub">Sedang dicuci</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon emerald"><?= getIcon('banknotes', 'w-8 h-8') ?></div>
    <div class="stat-info">
      <div class="stat-label">Pendapatan</div>
      <div class="stat-value"><?= formatRupiah($stats['total_pendapatan']) ?></div>
      <div class="stat-sub"><?= $stats['lunas'] ?> order lunas</div>
    </div>
  </div>
</div>

<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:16px; margin-bottom:24px;">
  <!-- Quick Status Card -->
  <div class="card">
    <div class="card-header">
      <span class="card-title" style="display:flex;align-items:center;gap:8px;">
        <?= getIcon('pin', 'w-5 h-5') ?> Ringkasan Status
      </span>
      <span class="badge" style="background:#eef2ff; color:var(--accent); border-color:#c7d2fe;">
        <?= $stats['total'] ?> total
      </span>
    </div>
    <div class="card-body">
      <div style="display:flex; flex-direction:column; gap:14px;">
        <?php
        $statuses = [
            ['label' => 'Antrean', 'count' => $stats['antrean'], 'color' => '#f43f5e'],
            ['label' => 'Proses',  'count' => $stats['proses'],  'color' => '#f59e0b'],
            ['label' => 'Selesai', 'count' => $stats['selesai'], 'color' => '#10b981'],
            ['label' => 'Diambil', 'count' => $stats['diambil'], 'color' => '#94a3b8']
        ];

        foreach ($statuses as $st):
            $pct = $stats['total'] > 0 ? ($st['count'] / $stats['total']) * 100 : 0;
        ?>
        <div style="display:flex; align-items:center; gap:12px;">
          <span class="text-sm font-medium" style="display:flex;align-items:center;gap:6px; min-width:90px;">
            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background-color:<?= $st['color'] ?>"></span>
            <?= $st['label'] ?>
          </span>
          <div style="flex:1; height:8px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
            <div style="width:<?= $pct ?>%; height:100%; background:<?= $st['color'] ?>; border-radius:4px; transition:width .5s;"></div>
          </div>
          <span class="font-bold" style="min-width:32px; text-align:right;"><?= $st['count'] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Pendapatan vs Piutang Card -->
  <div class="card">
    <div class="card-header">
      <span class="card-title" style="display:flex;align-items:center;gap:8px;">
        <?= getIcon('banknotes', 'w-5 h-5') ?> Cashflow
      </span>
    </div>
    <div class="card-body">
      <div style="display:flex; flex-direction:column; gap:14px;">
        <?php
          $totalCashflow = $stats['total_pendapatan'] + $stats['total_piutang'];
          $pctLunas    = $totalCashflow > 0 ? ($stats['total_pendapatan'] / $totalCashflow) * 100 : 0;
          $pctPiutang  = $totalCashflow > 0 ? ($stats['total_piutang']    / $totalCashflow) * 100 : 0;
        ?>

        <div style="background:#ecfdf5; border:1px solid #a7f3d0; border-radius:10px; padding:14px;">
          <div class="text-xs" style="color:#047857; margin-bottom:4px; font-weight:600;">PENDAPATAN LUNAS</div>
          <div style="font-size:1.25rem; font-weight:700; color:#059669;">
            <?= formatRupiah($stats['total_pendapatan']) ?>
          </div>
          <div class="text-xs text-muted" style="margin-top:4px;"><?= $stats['lunas'] ?> order lunas · <?= number_format($pctLunas, 1, ',', '.') ?>%</div>
        </div>

        <div style="background:#fff1f2; border:1px solid #fecdd3; border-radius:10px; padding:14px;">
          <div class="text-xs" style="color:#be123c; margin-bottom:4px; font-weight:600;">BELUM BAYAR</div>
          <div style="font-size:1.25rem; font-weight:700; color:#e11d48;">
            <?= formatRupiah($stats['total_piutang']) ?>
          </div>
          <div class="text-xs text-muted" style="margin-top:4px;"><?= number_format($pctPiutang, 1, ',', '.') ?>% dari total tagihan</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Recent Orders Table -->
<div class="card">
  <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
    <span class="card-title" style="display:flex;align-items:center;gap:8px;">
      <?= getIcon('clock', 'w-5 h-5') ?> Order Terbaru
    </span>
    <a href="order_list.php" class="btn btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:4px;">
      Lihat Semua →
    </a>
  </div>
  <div class="table-wrap">
    <table style="min-width:880px;">
      <thead>
        <tr>
          <th>Invoice</th>
          <th>Pelanggan</th>
          <th>Tanggal Masuk</th>
          <th>Paket &amp; Berat</th>
          <th class="text-center">Status</th>
          <th class="text-center">Bayar</th>
          <th style="text-align:right;">Total</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if(mysqli_num_rows($res_recent) > 0): ?>
          <?php while($o = mysqli_fetch_assoc($res_recent)): ?>
          <tr>
            <td><strong><?= htmlspecialchars($o['kode_invoice']) ?></strong></td>
            <td>
              <div class="font-medium"><?= htmlspecialchars($o['nama_pelanggan']) ?></div>
              <?php if (!empty($o['no_telepon'])): ?>
                <div class="text-xs text-muted"><?= htmlspecialchars($o['no_telepon']) ?></div>
              <?php endif; ?>
            </td>
            <td style="white-space:nowrap;"><?= formatTanggal($o['tgl_masuk']) ?></td>
            <td>
              <div class="font-medium">
                <?php
                if ($o['paket'] == 'cuci_kering') echo 'Cuci Kering';
                elseif ($o['paket'] == 'cuci_setrika') echo 'Cuci Setrika';
                elseif ($o['paket'] == 'express') echo 'Express 1 Jam';
                else echo ucfirst($o['paket']);
                ?>
              </div>
              <div class="text-xs text-muted"><?= number_format($o['berat'], 1, ',', '.') ?> Kg</div>
            </td>
            <td class="text-center"><?= badgeStatus($o['status_proses']) ?></td>
            <td class="text-center"><?= badgeBayar($o['status_bayar']) ?></td>
            <td style="text-align:right; white-space:nowrap;"><strong><?= formatRupiah($o['total_harga']) ?></strong></td>
            <td class="text-center">
              <a href="order_detail.php?id=<?= $o['id'] ?>" class="btn btn-ghost btn-sm">Detail →</a>
            </td>
          </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="8" class="text-center text-muted" style="padding:32px;">Belum ada order.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../templates/footer.php'; ?>
