<?php
$pageTitle = 'Tambah Order Baru';
$activeMenu = 'order_add';
$pageSubtitle = 'Buat transaksi laundry baru';
require_once '../templates/header.php';

// Simpan halaman asal agar bisa kembali setelah CRUD
$ref = isset($_GET['ref']) ? $_GET['ref'] : 'order_list.php';
// Whitelist referrer yang diizinkan (keamanan)
$allowed_refs = ['order_list.php', 'dashboard.php'];
if (!in_array($ref, $allowed_refs)) $ref = 'order_list.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = sanitize($koneksi, $_POST['nama_pelanggan']);
    $telp = sanitize($koneksi, $_POST['no_telepon']);
    $paket = sanitize($koneksi, $_POST['paket'] ?? 'cuci_kering');
    $berat = (float)($_POST['berat'] ?? 1);
    
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
        $harga = 0; // fallback
    }
    
    $user_id = $user['id'];
    
    // Generate Invoice
    $inv_prefix = 'INV-' . date('Ymd');
    // Cari urutan terakhir hari ini
    $q_last = "SELECT kode_invoice FROM orders WHERE kode_invoice LIKE '$inv_prefix%' ORDER BY id DESC LIMIT 1";
    $res_last = mysqli_query($koneksi, $q_last);
    $urut = 1;
    if (mysqli_num_rows($res_last) > 0) {
        $last_inv = mysqli_fetch_assoc($res_last)['kode_invoice'];
        $urut = (int)substr($last_inv, -3) + 1;
    }
    $invoice = $inv_prefix . sprintf('%03d', $urut);
    
    $jenis_arr = $_POST['jenis_pakaian'] ?? [];
    $jumlah_arr = $_POST['jumlah'] ?? [];
    $ket_arr = $_POST['keterangan'] ?? [];

    if (empty($nama)) {
        $_SESSION['flash_msg'] = 'Nama pelanggan wajib diisi!';
        $_SESSION['flash_type'] = 'error';
    } else {
        mysqli_begin_transaction($koneksi);
        try {
            // Insert Order
            $stmt = mysqli_prepare($koneksi, "INSERT INTO orders (kode_invoice, nama_pelanggan, no_telepon, paket, berat, total_harga, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssssdii", $invoice, $nama, $telp, $paket, $berat, $harga, $user_id);
            mysqli_stmt_execute($stmt);
            $order_id = mysqli_insert_id($koneksi);

            // Insert Details
            $stmt_dtl = mysqli_prepare($koneksi, "INSERT INTO order_details (order_id, jenis_pakaian, jumlah, keterangan) VALUES (?, ?, ?, ?)");
            foreach ($jenis_arr as $i => $jenis) {
                $jenis = sanitize($koneksi, $jenis);
                $jumlah = (int)$jumlah_arr[$i];
                $ket = sanitize($koneksi, $ket_arr[$i]);
                
                if (!empty($jenis)) {
                    mysqli_stmt_bind_param($stmt_dtl, "isis", $order_id, $jenis, $jumlah, $ket);
                    mysqli_stmt_execute($stmt_dtl);
                }
            }
            mysqli_commit($koneksi);
            redirect($ref, "Order $invoice berhasil dibuat!", 'success');
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $_SESSION['flash_msg'] = 'Gagal menyimpan order: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
        }
    }
}
?>

<div style="max-width:800px; margin: 0 auto;">
  <div class="mb-4">
    <a href="<?= htmlspecialchars($ref) ?>" class="btn btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:4px;"><?= getIcon('chevron-left', 'w-4 h-4') ?> Kembali</a>
  </div>

  <div class="card">
  <div class="card-header">
    <span class="card-title" style="display:flex;align-items:center;gap:8px;"><?= getIcon('document-text', 'w-5 h-5') ?> Formulir Order Baru</span>
  </div>
  <div class="card-body">
    <form method="POST" id="order-form" action="?ref=<?= urlencode($ref) ?>">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Nama Pelanggan <span class="required">*</span></label>
          <input type="text" name="nama_pelanggan" class="form-control" placeholder="Masukkan nama lengkap" required>
        </div>
        <div class="form-group">
          <label class="form-label">No. Telepon</label>
          <input type="tel" name="no_telepon" class="form-control" placeholder="08xxxxxxxxxx">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Pilihan Paket <span class="required">*</span></label>
          <select name="paket" id="paket" class="form-control" required>
            <option value="" disabled selected>Pilih Paket</option>
            <option value="cuci_kering">Cuci Kering (Rp 4.000/kg)</option>
            <option value="cuci_setrika">Cuci Setrika (Rp 5.000/kg)</option>
            <option value="express">Express 1 Jam (Rp 8.000/kg, Min 5kg)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Berat (Kg) <span class="required">*</span></label>
          <input type="number" name="berat" id="berat" class="form-control" placeholder="Contoh: 2.5" step="0.1" min="0.1" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Total Harga (Rp) <span class="required">*</span></label>
        <input type="number" name="total_harga" id="total_harga" class="form-control" style="background:#f1f5f9; cursor:not-allowed;" readonly required>
        <div class="form-hint">Total harga dihitung otomatis berdasarkan paket dan berat.</div>
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

        <div class="item-row">
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Jenis Pakaian</label>
            <input type="text" name="jenis_pakaian[]" class="form-control" placeholder="Contoh: Kaos, Celana">
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Jumlah</label>
            <input type="number" name="jumlah[]" class="form-control" min="1" value="1" placeholder="1">
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Keterangan</label>
            <input type="text" name="keterangan[]" class="form-control" placeholder="Opsional...">
          </div>
          <button type="button" class="remove-btn remove-item-btn" title="Hapus item" disabled style="opacity:0.3;display:inline-flex;align-items:center;justify-content:center;"><?= getIcon('x-mark', 'w-4 h-4') ?></button>
        </div>
      </div>

      <div class="divider"></div>

      <div class="d-flex gap-3" style="justify-content:flex-end">
        <a href="<?= htmlspecialchars($ref) ?>" class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:4px;"><?= getIcon('check', 'w-5 h-5') ?> Simpan Order</button>
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
