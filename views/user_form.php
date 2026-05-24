<?php
$pageTitle = 'Form Pengguna';
$activeMenu = 'user_list';
require_once '../templates/header.php';
requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$edit_mode = $id > 0;

$u_username = '';
$u_nama = '';
$u_role = 'petugas';

if ($edit_mode) {
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $u_username = $row['username'];
        $u_nama = $row['nama_lengkap'];
        $u_role = $row['role'];
    } else {
        redirect('user_list.php', 'Pengguna tidak ditemukan!', 'error');
    }
}

// Proses form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = sanitize($koneksi, $_POST['nama_lengkap']);
    $username = sanitize($koneksi, $_POST['username']);
    $role = sanitize($koneksi, $_POST['role']);
    $password = $_POST['password'];
    
    // Cek username duplikat
    $cek_query = "SELECT id FROM users WHERE username = ? AND id != ?";
    $stmt_cek = mysqli_prepare($koneksi, $cek_query);
    mysqli_stmt_bind_param($stmt_cek, "si", $username, $id);
    mysqli_stmt_execute($stmt_cek);
    if (mysqli_num_rows(mysqli_stmt_get_result($stmt_cek)) > 0) {
        $error = "Username sudah digunakan oleh orang lain!";
    } else {
        if ($edit_mode) {
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($koneksi, "UPDATE users SET nama_lengkap=?, username=?, role=?, password=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "ssssi", $nama, $username, $role, $hashed, $id);
            } else {
                $stmt = mysqli_prepare($koneksi, "UPDATE users SET nama_lengkap=?, username=?, role=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "sssi", $nama, $username, $role, $id);
            }
            $msg = "Pengguna berhasil diperbarui!";
        } else {
            if (empty($password)) {
                $error = "Password wajib diisi untuk pengguna baru!";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($koneksi, "INSERT INTO users (nama_lengkap, username, role, password) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssss", $nama, $username, $role, $hashed);
                $msg = "Pengguna berhasil ditambahkan!";
            }
        }
        
        if (!isset($error)) {
            if (mysqli_stmt_execute($stmt)) {
                redirect('user_list.php', $msg, 'success');
            } else {
                $error = "Terjadi kesalahan sistem: " . mysqli_error($koneksi);
            }
        }
    }
}
?>

<div style="max-width:600px; margin: 0 auto;">
  <div class="mb-4">
    <a href="user_list.php" class="btn btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:4px;"><?= getIcon('chevron-left', 'w-4 h-4') ?> Kembali</a>
  </div>

  <div class="card">
  <div class="card-header">
    <span class="card-title" style="display:flex;align-items:center;gap:8px;"><?= $edit_mode ? getIcon('pencil', 'w-5 h-5') : getIcon('plus', 'w-5 h-5') ?> <?= $edit_mode ? 'Edit' : 'Tambah' ?> Pengguna</span>
  </div>
  <div class="card-body">
    <?php if(isset($error)): ?>
      <div class="form-error mb-3" style="display:flex; align-items:center; gap:8px; padding:10px; background:#fff1f2; border:1px solid #fecdd3; border-radius:4px; color:#e11d48">
        <?= getIcon('x-circle', 'w-5 h-5') ?> <?= $error ?>
      </div>
    <?php endif; ?>
    
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Nama Lengkap <span class="required">*</span></label>
        <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($u_nama) ?>" required>
      </div>
      
      <div class="form-group">
        <label class="form-label">Username <span class="required">*</span></label>
        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($u_username) ?>" required>
      </div>
      
      <div class="form-group">
        <label class="form-label">Role / Hak Akses <span class="required">*</span></label>
        <select name="role" class="form-control" required>
          <option value="petugas" <?= $u_role == 'petugas' ? 'selected' : '' ?>>Petugas (Frontdesk)</option>
          <option value="admin" <?= $u_role == 'admin' ? 'selected' : '' ?>>Administrator</option>
        </select>
      </div>
      
      <div class="form-group">
        <label class="form-label">Password <?= $edit_mode ? '(Kosongkan jika tidak ingin diubah)' : '<span class="required">*</span>' ?></label>
        <input type="password" name="password" class="form-control" <?= $edit_mode ? '' : 'required' ?>>
      </div>
      
      <div class="d-flex gap-3 mt-4" style="justify-content:flex-end">
        <a href="user_list.php" class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:4px;"><?= getIcon('check', 'w-5 h-5') ?> Simpan</button>
      </div>
    </form>
  </div>
</div>
</div>

<?php require_once '../templates/footer.php'; ?>
