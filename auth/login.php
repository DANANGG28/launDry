<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: ../views/dashboard.php");
    exit;
}
require_once __DIR__ . '/../config/helper.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — launDry</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

  <div class="login-page">
    <div class="login-left">
      <div class="login-illustration">
        <h2 style="display:flex;align-items:center;gap:8px;">Kelola Usaha<br>Laundry Anda<br>Dengan Mudah <?= getIcon('sparkles', 'w-8 h-8 inline-block') ?></h2>
        <p>Sistem manajemen laundry modern yang membantu Anda mencatat transaksi, melacak status cucian, dan mengelola pembayaran secara efisien.</p>
        <div class="login-features">
          <div class="login-feature">
            <div class="feat-icon"><?= getIcon('clipboard-document-list', 'w-6 h-6 text-indigo-500') ?></div>
            <span>Pencatatan order otomatis & terstruktur</span>
          </div>
          <div class="login-feature">
            <div class="feat-icon"><?= getIcon('chart-bar', 'w-6 h-6 text-indigo-500') ?></div>
            <span>Dashboard ringkasan real-time</span>
          </div>
          <div class="login-feature">
            <div class="feat-icon"><?= getIcon('arrow-path', 'w-6 h-6 text-indigo-500') ?></div>
            <span>Pelacakan status cucian langkah demi langkah</span>
          </div>
          <div class="login-feature">
            <div class="feat-icon"><?= getIcon('banknotes', 'w-6 h-6 text-indigo-500') ?></div>
            <span>Manajemen pembayaran yang transparan</span>
          </div>
        </div>
      </div>
    </div>

    <div class="login-right">
      <div class="login-form-wrap">
        <div class="login-logo">
          <div class="logo-icon"><?= getIcon('sparkles', 'w-8 h-8') ?></div>
          <div class="logo-text">
            <h1>launDry</h1>
            <p>Management System</p>
          </div>
        </div>

        <div class="login-heading">
          <h2>Selamat Datang! <?= getIcon('users', 'w-6 h-6 inline-block') ?></h2>
          <p>Masuk ke akun Anda untuk melanjutkan</p>
        </div>

        <form action="proses_login.php" method="POST">
          <div class="form-group">
            <label class="form-label">Username</label>
            <div class="input-icon-wrap">
              <span class="input-icon"><?= getIcon('users', 'w-5 h-5 text-slate-400') ?></span>
              <input type="text" class="form-control" name="username" placeholder="Masukkan username" required autocomplete="username">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Password</label>
            <div class="input-icon-wrap">
              <span class="input-icon"><?= getIcon('lock-closed', 'w-5 h-5 text-slate-400') ?></span>
              <input type="password" class="form-control" id="inp-password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
              <button type="button" class="password-toggle" id="toggle-password" aria-label="Tampilkan password"><?= getIcon('eye', 'w-5 h-5 text-slate-400') ?></button>
            </div>
          </div>

          <?php if (isset($_SESSION['error'])): ?>
            <div class="form-error mb-3" style="display:block">
              <?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
          <?php endif; ?>

          <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px; padding:12px; font-size:.95rem; display:flex; align-items:center; justify-content:center; gap:8px;">
            Masuk <?= getIcon('chevron-right', 'w-5 h-5') ?>
          </button>
        </form>

        <div style="margin-top:28px; padding:16px; background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0">
          <p class="text-xs font-semibold" style="margin-bottom:8px; color:#64748b">Demo Akun:</p>
          <div style="display:flex; gap:12px">
            <div style="flex:1">
              <p class="text-xs text-muted">Admin</p>
              <p class="text-sm font-medium">admin / admin123</p>
            </div>
            <div style="flex:1">
              <p class="text-xs text-muted">Petugas</p>
              <p class="text-sm font-medium">petugas1 / petugas123</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.getElementById('toggle-password').addEventListener('click', () => {
      const inp = document.getElementById('inp-password');
      const btn = document.getElementById('toggle-password');
      if (inp.type === 'password') {
        inp.type = 'text';
        btn.innerHTML = `<?= getIcon('eye-slash', 'w-5 h-5 text-slate-400') ?>`;
      } else {
        inp.type = 'password';
        btn.innerHTML = `<?= getIcon('eye', 'w-5 h-5 text-slate-400') ?>`;
      }
    });
  </script>
</body>
</html>
