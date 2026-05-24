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

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:24px">
  <!-- Quick Status Card -->
  <div class="card" style="grid-column: 1 / -1; max-width: 600px;">
    <div class="card-header">
      <span class="card-title" style="display:flex;align-items:center;gap:8px;"><?= getIcon('pin', 'w-5 h-5') ?> Ringkasan Status</span>
    </div>
    <div class="card-body">
      <div style="display:flex; flex-direction:column; gap:14px">
        <?php
        $statuses = [
            ['label' => 'Antrean', 'count' => $stats['antrean'], 'color' => '#f43f5e'],
            ['label' => 'Proses', 'count' => $stats['proses'], 'color' => '#f59e0b'],
            ['label' => 'Selesai', 'count' => $stats['selesai'], 'color' => '#10b981'],
            ['label' => 'Diambil', 'count' => $stats['diambil'], 'color' => '#94a3b8']
        ];
        
        foreach ($statuses as $st):
            $pct = $stats['total'] > 0 ? ($st['count'] / $stats['total']) * 100 : 0;
        ?>
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <span class="text-sm font-medium" style="display:flex;align-items:center;gap:6px;">
            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background-color:<?= $st['color'] ?>"></span>
            <?= $st['label'] ?>
          </span>
          <div style="display:flex; align-items:center; gap:8px; flex:1; margin:0 12px">
            <div style="flex:1;height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden">
              <div style="width:<?= $pct ?>%;height:100%;background:<?= $st['color'] ?>;border-radius:4px;transition:width .5s"></div>
            </div>
          </div>
          <span class="font-bold"><?= $st['count'] ?></span>
        </div>
        <?php endforeach; ?>
        
        <div class="divider"></div>
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <span class="text-sm font-medium" style="display:flex;align-items:center;gap:6px;">
            <?= getIcon('banknotes', 'w-4 h-4') ?> Piutang
          </span>
          <span class="font-bold" style="color:#e11d48"><?= formatRupiah($stats['total_piutang']) ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Recent Orders Table -->
<div class="card">
  <div class="card-header">
    <span class="card-title" style="display:flex;align-items:center;gap:8px;"><?= getIcon('clock', 'w-5 h-5') ?> Order Terbaru</span>
    <a href="order_list.php" class="btn btn-outline btn-sm">Lihat Semua →</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Invoice</th>
          <th>Pelanggan</th>
          <th>Tanggal Masuk</th>
          <th>Paket & Berat</th>
          <th>Status</th>
          <th>Pembayaran</th>
          <th>Total</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if(mysqli_num_rows($res_recent) > 0): ?>
          <?php while($o = mysqli_fetch_assoc($res_recent)): ?>
          <tr>
            <td><strong><?= htmlspecialchars($o['kode_invoice']) ?></strong></td>
            <td>
              <div><?= htmlspecialchars($o['nama_pelanggan']) ?></div>
              <div class="text-xs text-muted"><?= htmlspecialchars($o['no_telepon']) ?></div>
            </td>
            <td><?= formatTanggal($o['tgl_masuk']) ?></td>
            <td>
              <div class="font-medium">
                <?php
                if ($o['paket'] == 'cuci_kering') echo 'Cuci Kering';
                elseif ($o['paket'] == 'cuci_setrika') echo 'Cuci Setrika';
                elseif ($o['paket'] == 'express') echo 'Express 1 Jam';
                else echo ucfirst($o['paket']);
                ?>
              </div>
              <div class="text-xs text-muted"><?= $o['berat'] ?> Kg</div>
            </td>
            <td><?= badgeStatus($o['status_proses']) ?></td>
            <td><?= badgeBayar($o['status_bayar']) ?></td>
            <td><strong><?= formatRupiah($o['total_harga']) ?></strong></td>
            <td>
              <a href="order_detail.php?id=<?= $o['id'] ?>" class="btn btn-ghost btn-sm">Detail →</a>
            </td>
          </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="7" class="text-center text-muted" style="padding: 20px;">Belum ada order.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../templates/footer.php'; ?>
