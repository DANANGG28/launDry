<?php
$pageTitle = 'Edit Order';
$activeMenu = 'order_list';
$pageSubtitle = 'Perbarui data transaksi';
require_once '../templates/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch Order
$stmt = mysqli_prepare($koneksi, "SELECT * FROM orders WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

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

// Process Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = sanitize($koneksi, $_POST['nama_pelanggan']);
    $telp = sanitize($koneksi, $_POST['no_telepon']);
    $paket = sanitize($koneksi, $_POST['paket'] ?? 'cuci_kering');
    $berat = (float)($_POST['berat'] ?? 1);
    $status_proses = sanitize($koneksi, $_POST['status_proses']);
    
    // Validasi & Kalkulasi harga server-side
    $harga_per_kg = 0;
    if ($paket === 'cuci_kering') {
        $harga_per_kg = 4000;
        $harga = $berat * $harga_per_kg;
    } elseif ($paket === 'cuci_setrika') {
        $harga_per_kg = 5000;
        $harga = $berat * $harga_per_kg;
    } elseif ($paket === 'express') {
        $harga_per_kg = 8000;
        $harga = ($berat < 5 ? 5 : $berat) * $harga_per_kg;
    } else {
        $harga = 0;
    }
    $status_bayar = sanitize($koneksi, $_POST['status_bayar']);
    
    // Tgl ambil logic
    $tgl_ambil = $order['tgl_ambil'];
    if ($status_proses === 'diambil' && !$tgl_ambil) {
        $tgl_ambil = date('Y-m-d H:i:s');
    } else if ($status_proses !== 'diambil') {
        $tgl_ambil = null;
    }
    
    $jenis_arr = $_POST['jenis_pakaian'] ?? [];
    $jumlah_arr = $_POST['jumlah'] ?? [];
    $ket_arr = $_POST['keterangan'] ?? [];

    if (empty($nama)) {
        $_SESSION['flash_msg'] = 'Nama pelanggan dan minimal satu item pakaian harus diisi!';
        $_SESSION['flash_type'] = 'error';
    } else {
        mysqli_begin_transaction($koneksi);
        try {
            // Update Order
            $stmt = mysqli_prepare($koneksi, "UPDATE orders SET nama_pelanggan=?, no_telepon=?, paket=?, berat=?, total_harga=?, status_proses=?, status_bayar=?, tgl_ambil=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "sssdisssi", $nama, $telp, $paket, $berat, $harga, $status_proses, $status_bayar, $tgl_ambil, $id);
            mysqli_stmt_execute($stmt);

            // Delete old details
            mysqli_query($koneksi, "DELETE FROM order_details WHERE order_id = $id");

            // Insert New Details
            $stmt_dtl = mysqli_prepare($koneksi, "INSERT INTO order_details (order_id, jenis_pakaian, jumlah, keterangan) VALUES (?, ?, ?, ?)");
            foreach ($jenis_arr as $i => $jenis) {
                $jenis = sanitize($koneksi, $jenis);
                $jumlah = (int)$jumlah_arr[$i];
                $ket = sanitize($koneksi, $ket_arr[$i]);
                
                if (!empty($jenis)) {
                    mysqli_stmt_bind_param($stmt_dtl, "isis", $id, $jenis, $jumlah, $ket);
                    mysqli_stmt_execute($stmt_dtl);
                }
            }
            mysqli_commit($koneksi);
            redirect("order_detail.php?id=$id", "Order {$order['kode_invoice']} berhasil diperbarui!", 'success');
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $_SESSION['flash_msg'] = 'Gagal memperbarui order: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
        }
    }
}
?>

<div style="max-width:800px; margin: 0 auto;">
  <div class="mb-4">
    <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:4px;"><?= getIcon('chevron-left', 'w-4 h-4') ?> Kembali</a>
  </div>

  <div class="card">
  <div class="card-header">
    <span class="card-title" style="display:flex;align-items:center;gap:8px;"><?= getIcon('pencil', 'w-5 h-5') ?> Edit Order — <?= htmlspecialchars($order['kode_invoice']) ?></span>
  </div>
  <div class="card-body">
    <form method="POST" id="edit-form">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Nama Pelanggan <span class="required">*</span></label>
          <input type="text" name="nama_pelanggan" class="form-control" value="<?= htmlspecialchars($order['nama_pelanggan']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">No. Telepon</label>
          <input type="tel" name="no_telepon" class="form-control" value="<?= htmlspecialchars($order['no_telepon']) ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Pilihan Paket <span class="required">*</span></label>
          <select name="paket" id="paket" class="form-control" required>
            <option value="cuci_kering" <?= $order['paket'] == 'cuci_kering' ? 'selected' : '' ?>>Cuci Kering (Rp 4.000/kg)</option>
            <option value="cuci_setrika" <?= $order['paket'] == 'cuci_setrika' ? 'selected' : '' ?>>Cuci Setrika (Rp 5.000/kg)</option>
            <option value="express" <?= $order['paket'] == 'express' ? 'selected' : '' ?>>Express 1 Jam (Rp 8.000/kg, Min 5kg)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Berat (Kg) <span class="required">*</span></label>
          <input type="number" name="berat" id="berat" class="form-control" value="<?= $order['berat'] ?>" step="0.1" min="0.1" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Total Harga (Rp) <span class="required">*</span></label>
          <input type="number" name="total_harga" id="total_harga" class="form-control" value="<?= $order['total_harga'] ?>" style="background:#f1f5f9; cursor:not-allowed;" readonly required>
        </div>
        <div class="form-group">
          <label class="form-label">Status Proses</label>
          <select name="status_proses" class="form-control">
            <option value="antrean" <?= $order['status_proses']=='antrean'?'selected':'' ?>>Antrean</option>
            <option value="proses" <?= $order['status_proses']=='proses'?'selected':'' ?>>Proses</option>
            <option value="selesai" <?= $order['status_proses']=='selesai'?'selected':'' ?>>Selesai</option>
            <option value="diambil" <?= $order['status_proses']=='diambil'?'selected':'' ?>>Diambil</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Status Pembayaran</label>
        <select name="status_bayar" class="form-control">
          <option value="belum" <?= $order['status_bayar']=='belum'?'selected':'' ?>>Belum Bayar</option>
          <option value="lunas" <?= $order['status_bayar']=='lunas'?'selected':'' ?>>Lunas</option>
        </select>
      </div>

      <div class="divider"></div>

      <div class="d-flex justify-between align-center mb-3">
        <label class="form-label" style="margin-bottom:0">Detail Pakaian</label>
        <button type="button" class="btn btn-outline btn-sm" id="add-item-btn" style="display:inline-flex;align-items:center;gap:4px;"><?= getIcon('plus', 'w-4 h-4') ?> Tambah Item</button>
      </div>

      <div id="items-container">
        <!-- Template row, hidden -->
        <div class="item-row template-row" style="display:none;">
          <div class="form-group" style="margin-bottom:0">
            <input type="text" class="form-control item-jenis" placeholder="Contoh: Kaos, Celana" disabled>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <input type="number" class="form-control item-jumlah" min="1" value="1" placeholder="1" disabled>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <input type="text" class="form-control item-ket" placeholder="Opsional..." disabled>
          </div>
          <button type="button" class="remove-btn remove-item-btn" title="Hapus item" style="display:inline-flex;align-items:center;justify-content:center;"><?= getIcon('x-mark', 'w-4 h-4') ?></button>
        </div>

        <?php foreach($items as $i => $item): ?>
        <div class="item-row">
          <div class="form-group" style="margin-bottom:0">
            <?= $i == 0 ? '<label class="form-label">Jenis Pakaian</label>' : '' ?>
            <input type="text" name="jenis_pakaian[]" class="form-control" value="<?= htmlspecialchars($item['jenis_pakaian']) ?>">
          </div>
          <div class="form-group" style="margin-bottom:0">
            <?= $i == 0 ? '<label class="form-label">Jumlah</label>' : '' ?>
            <input type="number" name="jumlah[]" class="form-control" min="1" value="<?= $item['jumlah'] ?>">
          </div>
          <div class="form-group" style="margin-bottom:0">
            <?= $i == 0 ? '<label class="form-label">Keterangan</label>' : '' ?>
            <input type="text" name="keterangan[]" class="form-control" value="<?= htmlspecialchars($item['keterangan']) ?>">
          </div>
          <button type="button" class="remove-btn remove-item-btn" title="Hapus item" <?= count($items) <= 1 ? 'disabled style="opacity:0.3;display:inline-flex;align-items:center;justify-content:center;"' : 'style="display:inline-flex;align-items:center;justify-content:center;"' ?>><?= getIcon('x-mark', 'w-4 h-4') ?></button>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="divider"></div>

      <div class="d-flex gap-3" style="justify-content:flex-end">
        <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:4px;"><?= getIcon('check', 'w-5 h-5') ?> Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>
</div>

<script>
  document.getElementById('add-item-btn').addEventListener('click', () => {
    const container = document.getElementById('items-container');
    const template = container.querySelector('.template-row');
    const newRow = template.cloneNode(true);
    
    newRow.classList.remove('template-row');
    newRow.style.display = 'grid'; // .item-row is display:grid
    
    // Enable inputs
    newRow.querySelector('.item-jenis').disabled = false;
    newRow.querySelector('.item-jenis').name = "jenis_pakaian[]";
    
    newRow.querySelector('.item-jumlah').disabled = false;
    newRow.querySelector('.item-jumlah').name = "jumlah[]";
    
    newRow.querySelector('.item-ket').disabled = false;
    newRow.querySelector('.item-ket').name = "keterangan[]";
    
    // Enable all remove buttons since we have > 1 row
    document.querySelectorAll('.remove-item-btn').forEach(b => {
        b.disabled = false;
        b.style.opacity = '1';
    });
    
    container.appendChild(newRow);
  });

  document.getElementById('items-container').addEventListener('click', (e) => {
    if (e.target.classList.contains('remove-item-btn')) {
      const rows = document.querySelectorAll('#items-container .item-row:not(.template-row)');
      if (rows.length > 1) {
        e.target.closest('.item-row').remove();
      }
      
      const newRows = document.querySelectorAll('#items-container .item-row:not(.template-row)');
      if (newRows.length <= 1) {
        newRows[0].querySelector('.remove-item-btn').disabled = true;
        newRows[0].querySelector('.remove-item-btn').style.opacity = '0.3';
      }
    }
  });

  // Kalkulasi harga otomatis
  function calculateTotal() {
    const paket = document.getElementById('paket').value;
    const berat = parseFloat(document.getElementById('berat').value) || 0;
    let total = 0;
    
    if (paket === 'cuci_kering') {
      total = berat * 4000;
    } else if (paket === 'cuci_setrika') {
      total = berat * 5000;
    } else if (paket === 'express') {
      total = (berat < 5 ? 5 : berat) * 8000;
    }
    
    document.getElementById('total_harga').value = total;
  }
  
  document.getElementById('paket').addEventListener('change', calculateTotal);
  document.getElementById('berat').addEventListener('input', calculateTotal);
</script>

<?php require_once '../templates/footer.php'; ?>
