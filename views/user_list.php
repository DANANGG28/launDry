<?php
$pageTitle = 'Manajemen Pengguna';
$activeMenu = 'user_list';
$pageSubtitle = 'Kelola akses petugas dan administrator';
require_once '../templates/header.php';
requireAdmin(); // Hanya admin yang bisa akses halaman ini

// Proses Hapus
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    // Cegah hapus diri sendiri
    if ($id_hapus === $user['id']) {
        redirect('user_list.php', 'Tidak dapat menghapus akun Anda sendiri!', 'error');
    }
    
    $stmt = mysqli_prepare($koneksi, "DELETE FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_hapus);
    if (mysqli_stmt_execute($stmt)) {
        redirect('user_list.php', 'Pengguna berhasil dihapus!', 'success');
    } else {
        redirect('user_list.php', 'Gagal menghapus pengguna!', 'error');
    }
}

// Ambil data user
$query = "SELECT * FROM users ORDER BY created_at DESC";
$result = mysqli_query($koneksi, $query);
?>

<div class="card">
  <div class="card-header">
    <span class="card-title" style="display:flex;align-items:center;gap:8px;"><?= getIcon('users', 'w-5 h-5') ?> Daftar Pengguna Sistem</span>
    <a href="user_form.php" class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:4px;"><?= getIcon('plus', 'w-4 h-4') ?> Tambah Pengguna</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>No</th>
          <th>Nama Lengkap</th>
          <th>Username</th>
          <th>Hak Akses</th>
          <th>Dibuat Pada</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $no = 1;
        while ($row = mysqli_fetch_assoc($result)): 
        ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong></td>
          <td><?= htmlspecialchars($row['username']) ?></td>
          <td>
            <?php if($row['role'] === 'admin'): ?>
              <span class="badge badge-proses" style="display:inline-flex;align-items:center;gap:4px;"><?= getIcon('check-circle', 'w-4 h-4') ?> Administrator</span>
            <?php else: ?>
              <span class="badge badge-selesai" style="display:inline-flex;align-items:center;gap:4px;"><?= getIcon('users', 'w-4 h-4') ?> Petugas</span>
            <?php endif; ?>
          </td>
          <td><?= formatTanggal($row['created_at']) ?></td>
          <td>
            <div class="d-flex gap-2">
              <a href="user_form.php?id=<?= $row['id'] ?>" class="btn btn-ghost btn-sm" title="Edit" style="padding:4px"><?= getIcon('pencil', 'w-5 h-5') ?></a>
              <?php if($row['id'] !== $user['id']): ?>
              <a href="?hapus=<?= $row['id'] ?>" class="btn btn-ghost btn-sm" title="Hapus" onclick="return confirm('Yakin ingin menghapus pengguna ini?');" style="padding:4px"><?= getIcon('trash', 'w-5 h-5') ?></a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../templates/footer.php'; ?>
