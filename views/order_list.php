<?php
$pageTitle = 'Daftar Order';
$activeMenu = 'order_list';
$pageSubtitle = 'Kelola semua transaksi laundry';
require_once '../templates/header.php';

// Proses Hapus
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    $stmt = mysqli_prepare($koneksi, "DELETE FROM orders WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_hapus);
    if (mysqli_stmt_execute($stmt)) {
        redirect('order_list.php', 'Order berhasil dihapus!', 'success');
    } else {
        redirect('order_list.php', 'Gagal menghapus order!', 'error');
    }
}

// Filter and Search
$search = isset($_GET['search']) ? sanitize($koneksi, $_GET['search']) : '';
$f_status = isset($_GET['status']) ? sanitize($koneksi, $_GET['status']) : 'semua';
$f_bayar = isset($_GET['bayar']) ? sanitize($koneksi, $_GET['bayar']) : 'semua';
$page_num = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page_num < 1) $page_num = 1;
$limit = 8;
$offset = ($page_num - 1) * $limit;

// Build Query
$where = "WHERE 1=1";
if ($f_status !== 'semua') $where .= " AND status_proses = '$f_status'";
if ($f_bayar !== 'semua') $where .= " AND status_bayar = '$f_bayar'";
if ($search !== '') {
    $where .= " AND (kode_invoice LIKE '%$search%' OR nama_pelanggan LIKE '%$search%' OR no_telepon LIKE '%$search%')";
}

// Count total for pagination
$q_count = "SELECT COUNT(id) as total FROM orders $where";
$res_count = mysqli_query($koneksi, $q_count);
$row_count = mysqli_fetch_assoc($res_count);
$total_data = $row_count['total'];
$total_pages = ceil($total_data / $limit);

// Fetch data
$q_data = "SELECT o.*, (SELECT COUNT(id) FROM order_details WHERE order_id = o.id) as jml_item 
           FROM orders o $where ORDER BY o.tgl_masuk DESC LIMIT $limit OFFSET $offset";
$res_data = mysqli_query($koneksi, $q_data);
?>

<div class="card mb-5">
  <div class="card-body">
    <form method="GET" class="filter-bar">
      <div class="search-box">
        <span class="search-icon" style="display:flex;align-items:center;justify-content:center;"><?= getIcon('magnifying-glass', 'w-4 h-4 text-slate-400') ?></span>
        <input type="text" name="search" class="form-control" placeholder="Cari invoice, nama, atau telepon..." value="<?= htmlspecialchars($search) ?>" style="padding-left:36px">
      </div>
      <select name="status" class="form-control" style="max-width:180px;" onchange="this.form.submit()">
        <option value="semua" <?= $f_status=='semua'?'selected':'' ?>>Semua Status</option>
        <option value="antrean" <?= $f_status=='antrean'?'selected':'' ?>>Antrean</option>
        <option value="proses" <?= $f_status=='proses'?'selected':'' ?>>Proses</option>
        <option value="selesai" <?= $f_status=='selesai'?'selected':'' ?>>Selesai</option>
        <option value="diambil" <?= $f_status=='diambil'?'selected':'' ?>>Diambil</option>
      </select>
      <select name="bayar" class="form-control" style="max-width:180px;" onchange="this.form.submit()">
        <option value="semua" <?= $f_bayar=='semua'?'selected':'' ?>>Semua Bayar</option>
        <option value="lunas" <?= $f_bayar=='lunas'?'selected':'' ?>>Lunas</option>
        <option value="belum" <?= $f_bayar=='belum'?'selected':'' ?>>Belum</option>
      </select>
      <button type="submit" class="btn btn-outline btn-sm" style="display:none">Filter</button>
      <a href="order_tambah.php" class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:4px;"><?= getIcon('plus', 'w-4 h-4') ?> Order Baru</a>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title" style="display:flex;align-items:center;gap:8px;"><?= getIcon('clipboard-document-list', 'w-5 h-5') ?> <?= $total_data ?> Transaksi Ditemukan</span>
  </div>
  <?php if(mysqli_num_rows($res_data) > 0): ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Invoice</th>
            <th>Pelanggan</th>
            <th>Tanggal Masuk</th>
            <th>Paket & Berat</th>
            <th>Item</th>
            <th>Status</th>
            <th>Pembayaran</th>
            <th>Total</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while($o = mysqli_fetch_assoc($res_data)): ?>
            <tr>
              <td><strong><?= htmlspecialchars($o['kode_invoice']) ?></strong></td>
              <td>
                <div class="font-medium"><?= htmlspecialchars($o['nama_pelanggan']) ?></div>
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
              <td><?= $o['jml_item'] ?> jenis</td>
              <td><?= badgeStatus($o['status_proses']) ?></td>
              <td><?= badgeBayar($o['status_bayar']) ?></td>
              <td><strong><?= formatRupiah($o['total_harga']) ?></strong></td>
              <td>
                <div class="d-flex gap-2">
                  <a href="order_detail.php?id=<?= $o['id'] ?>" class="btn btn-ghost btn-sm" title="Detail" style="padding:4px"><?= getIcon('eye', 'w-5 h-5') ?></a>
                  <a href="order_edit.php?id=<?= $o['id'] ?>&ref=order_list.php" class="btn btn-ghost btn-sm" title="Edit" style="padding:4px"><?= getIcon('pencil', 'w-5 h-5') ?></a>
                  <a href="?hapus=<?= $o['id'] ?>" class="btn btn-ghost btn-sm text-danger" title="Hapus" onclick="return confirm('Yakin ingin menghapus order <?= $o['kode_invoice'] ?>?');" style="padding:4px"><?= getIcon('trash', 'w-5 h-5') ?></a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <div class="pagination">
      <div class="pagination-info">
        Menampilkan <?= $offset + 1 ?>–<?= min($offset + $limit, $total_data) ?> dari <?= $total_data ?>
      </div>
      <div class="pagination-btns">
        <?php 
          // Build query string for pagination links
          $qs = $_GET;
          
          if ($page_num > 1) {
              $qs['page'] = $page_num - 1;
              echo '<a href="?'.http_build_query($qs).'" class="page-btn">←</a>';
          } else {
              echo '<button class="page-btn" disabled>←</button>';
          }

          for ($i = 1; $i <= $total_pages; $i++) {
              $qs['page'] = $i;
              $active = ($i == $page_num) ? 'active' : '';
              echo '<a href="?'.http_build_query($qs).'" class="page-btn '.$active.'">'.$i.'</a>';
          }

          if ($page_num < $total_pages) {
              $qs['page'] = $page_num + 1;
              echo '<a href="?'.http_build_query($qs).'" class="page-btn">→</a>';
          } else {
              echo '<button class="page-btn" disabled>→</button>';
          }
        ?>
      </div>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <div class="empty-icon"><?= getIcon('inbox', 'w-16 h-16 text-slate-300 mx-auto') ?></div>
      <h3>Tidak ada transaksi</h3>
      <p>Tidak ditemukan data yang sesuai dengan filter Anda.</p>
    </div>
  <?php endif; ?>
</div>

<?php require_once '../templates/footer.php'; ?>
