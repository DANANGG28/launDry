<?php
$pageTitle = 'Detail Order';
$activeMenu = 'order_list';
$pageSubtitle = 'Informasi lengkap transaksi';
require_once '../templates/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Proses Update Status
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action === 'status' && isset($_GET['val'])) {
        $val = sanitize($koneksi, $_GET['val']);
        $q_update = "UPDATE orders SET status_proses = ? WHERE id = ?";
        if ($val === 'diambil') {
            $q_update = "UPDATE orders SET status_proses = ?, tgl_ambil = NOW() WHERE id = ?";
        }
        $stmt = mysqli_prepare($koneksi, $q_update);
        mysqli_stmt_bind_param($stmt, "si", $val, $id);
        mysqli_stmt_execute($stmt);
        redirect("order_detail.php?id=$id", "Status berhasil diubah menjadi $val", "success");
    }
    else if ($action === 'bayar' && isset($_GET['val'])) {
        $val = sanitize($koneksi, $_GET['val']);
        $stmt = mysqli_prepare($koneksi, "UPDATE orders SET status_bayar = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $val, $id);
        mysqli_stmt_execute($stmt);
        redirect("order_detail.php?id=$id", "Status pembayaran berhasil diubah menjadi $val", "success");
    }
}

// Fetch Order
$stmt = mysqli_prepare($koneksi, "SELECT o.*, u.nama_lengkap as petugas_nama FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($res);

if (!$order) {
    echo "<div class='empty-state'><div class='empty-icon'>".getIcon('x-circle', 'w-16 h-16 text-rose-500 mx-auto')."</div><h3>Order Tidak Ditemukan</h3><a href='order_list.php' class='btn btn-primary mt-3'>← Kembali</a></div>";
    require_once '../templates/footer.php';
    exit;
}

// Fetch Details
$q_details = "SELECT * FROM order_details WHERE order_id = $id";
$res_details = mysqli_query($koneksi, $q_details);
$items = [];
while ($row = mysqli_fetch_assoc($res_details)) {
    $items[] = $row;
}

$statusSteps = ['antrean', 'proses', 'selesai', 'diambil'];
$currentIdx = array_search($order['status_proses'], $statusSteps);
if ($currentIdx === false) $currentIdx = 0;
?>

<!-- Back + Actions -->
<div class="d-flex justify-between align-center mb-5">
  <a href="order_list.php" class="btn btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:4px;"><?= getIcon('chevron-left', 'w-4 h-4') ?> Kembali</a>
  <div class="d-flex gap-2">
    <a href="order_edit.php?id=<?= $order['id'] ?>&ref=order_detail.php?id=<?= $order['id'] ?>" class="btn btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:4px;"><?= getIcon('pencil', 'w-4 h-4') ?> Edit</a>
    <a href="order_list.php?hapus=<?= $order['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus order <?= $order['kode_invoice'] ?>?');" style="display:inline-flex;align-items:center;gap:4px;"><?= getIcon('trash', 'w-4 h-4') ?> Hapus</a>
  </div>
</div>

<!-- Status Timeline -->
<div class="card mb-5">
  <div class="card-header">
    <span class="card-title" style="display:flex;align-items:center;gap:8px;"><?= getIcon('pin', 'w-5 h-5') ?> Status Pengerjaan</span>
    <span><?= badgeStatus($order['status_proses']) ?></span>
  </div>
  <div class="card-body">
    <div class="status-timeline">
      <?php foreach ($statusSteps as $i => $step): ?>
        <div class="timeline-step <?= $i < $currentIdx ? 'done' : '' ?> <?= $i === $currentIdx ? 'current' : '' ?>">
          <div class="timeline-dot" style="display:flex;align-items:center;justify-content:center;"><?= $i < $currentIdx ? getIcon('check', 'w-4 h-4') : ($i === $currentIdx ? '<span style="display:inline-block;width:8px;height:8px;background:white;border-radius:50%;"></span>' : ($i+1)) ?></div>
          <div class="timeline-label"><?= ucfirst($step) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($order['status_proses'] !== 'diambil' && $currentIdx < 3): ?>
      <div class="d-flex gap-2 mt-3" style="justify-content:center">
        <?php $nextStatus = $statusSteps[$currentIdx + 1]; ?>
        <a href="?id=<?= $order['id'] ?>&action=status&val=<?= $nextStatus ?>" class="btn btn-primary btn-sm" onclick="return confirm('Ubah status menjadi <?= ucfirst($nextStatus) ?>?');" style="display:inline-flex;align-items:center;gap:4px;">
          Ubah ke "<?= ucfirst($nextStatus) ?>" <?= getIcon('chevron-right', 'w-4 h-4') ?>
        </a>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Two columns: Info + Payment -->
<div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:24px">
  <!-- Customer Info -->
  <div class="card">
    <div class="card-header">
      <span class="card-title" style="display:flex;align-items:center;gap:8px;"><?= getIcon('users', 'w-5 h-5') ?> Informasi Pelanggan</span>
    </div>
    <div class="card-body">
      <div class="info-grid">
        <div class="info-item">
          <div class="info-label">Kode Invoice</div>
          <div class="info-value"><?= htmlspecialchars($order['kode_invoice']) ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">Nama Pelanggan</div>
          <div class="info-value"><?= htmlspecialchars($order['nama_pelanggan']) ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">No. Telepon</div>
          <div class="info-value"><?= htmlspecialchars($order['no_telepon']) ?: '-' ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">Paket Laundry</div>
          <div class="info-value">
            <?php
            if ($order['paket'] == 'cuci_kering') echo 'Cuci Kering';
            elseif ($order['paket'] == 'cuci_setrika') echo 'Cuci Setrika';
            elseif ($order['paket'] == 'express') echo 'Express 1 Jam';
            else echo ucfirst($order['paket']);
            ?>
          </div>
        </div>
        <div class="info-item">
          <div class="info-label">Berat</div>
          <div class="info-value"><?= $order['berat'] ?> Kg</div>
        </div>
        <div class="info-item">
          <div class="info-label">Petugas</div>
          <div class="info-value"><?= htmlspecialchars($order['petugas_nama']) ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">Tanggal Masuk</div>
          <div class="info-value"><?= formatTanggal($order['tgl_masuk']) ?> <?= date('H:i', strtotime($order['tgl_masuk'])) ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">Tanggal Ambil</div>
          <div class="info-value"><?= $order['tgl_ambil'] ? formatTanggal($order['tgl_ambil']) . ' ' . date('H:i', strtotime($order['tgl_ambil'])) : '-' ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Payment -->
  <div class="card">
    <div class="card-header">
      <span class="card-title" style="display:flex;align-items:center;gap:8px;"><?= getIcon('banknotes', 'w-5 h-5') ?> Pembayaran</span>
      <span><?= badgeBayar($order['status_bayar']) ?></span>
    </div>
    <div class="card-body">
      <div class="total-row mb-4">
        <span class="total-label">Total Harga</span>
        <span class="total-value"><?= formatRupiah($order['total_harga']) ?></span>
      </div>
      <?php if ($order['status_bayar'] === 'belum'): ?>
        <a href="?id=<?= $order['id'] ?>&action=bayar&val=lunas" class="btn btn-success btn-block" style="text-align:center;display:flex;align-items:center;justify-content:center;gap:6px;" onclick="return confirm('Tandai pembayaran sebagai Lunas?');"><?= getIcon('banknotes', 'w-4 h-4') ?> Tandai Lunas</a>
      <?php else: ?>
        <a href="?id=<?= $order['id'] ?>&action=bayar&val=belum" class="btn btn-danger btn-block" style="text-align:center;display:flex;align-items:center;justify-content:center;gap:6px;" onclick="return confirm('Batalkan pembayaran (Ubah ke Belum Bayar)?');"><?= getIcon('arrow-path', 'w-4 h-4') ?> Ubah ke Belum Bayar</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Items Table -->
<div class="card">
  <div class="card-header">
    <span class="card-title" style="display:flex;align-items:center;gap:8px;"><?= getIcon('cube', 'w-5 h-5') ?> Detail Pakaian (<?= count($items) ?> jenis)</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>No</th>
          <th>Jenis Pakaian</th>
          <th>Jumlah</th>
          <th>Keterangan</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i => $item): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><strong><?= htmlspecialchars($item['jenis_pakaian']) ?></strong></td>
            <td><?= $item['jumlah'] ?> pcs</td>
            <td class="text-muted"><?= htmlspecialchars($item['keterangan']) ?: '-' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../templates/footer.php'; ?>
