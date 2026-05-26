<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: ../views/dashboard.php");
    exit;
}
require_once __DIR__ . '/../config/helper.php';

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Sistem Manajemen Laundry — Login untuk mengakses dashboard">
  <title>Login — launDry</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

  <div class="login-page">
    <!-- Left Illustration Side -->
    <div class="login-left">
      <div class="login-illustration">
        <h2>Kelola Usaha Laundry Anda Dengan Mudah</h2>
        <p>Sistem manajemen laundry modern yang membantu Anda mencatat transaksi, melacak status cucian, dan mengelola pembayaran secara efisien.</p>
        <div class="login-features">
          <div class="login-feature">
            <div class="feat-icon"><?= getIcon('clipboard-document-list', 'icon') ?></div>
            <span>Pencatatan order otomatis &amp; terstruktur</span>
          </div>
          <div class="login-feature">
            <div class="feat-icon"><?= getIcon('chart-bar', 'icon') ?></div>
            <span>Dashboard ringkasan real-time</span>
          </div>
          <div class="login-feature">
            <div class="feat-icon"><?= getIcon('arrow-path', 'icon') ?></div>
            <span>Pelacakan status cucian langkah demi langkah</span>
          </div>
          <div class="login-feature">
            <div class="feat-icon"><?= getIcon('banknotes', 'icon') ?></div>
            <span>Manajemen pembayaran yang transparan</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Form Side -->
    <div class="login-right">
      <div class="login-form-wrap">
        <div class="login-logo">
          <div class="logo-icon"><?= getIcon('sparkles', 'icon') ?></div>
          <div class="logo-text">
            <h1>launDry</h1>
            <p>Management System</p>
          </div>
        </div>

        <div class="login-heading">
          <h2>Selamat Datang! <?= getIcon('sparkles', 'icon-inline') ?></h2>
          <p>Masuk ke akun Anda untuk melanjutkan</p>
        </div>

        <form id="login-form" action="proses_login.php" method="POST" novalidate>
          <div class="form-group">
            <label class="form-label" for="inp-username">Username</label>
            <div class="input-icon-wrap">
              <span class="input-icon"><?= getIcon('users', 'icon-sm') ?></span>
              <input type="text" class="form-control" id="inp-username" name="username"
                     placeholder="Masukkan username" required autocomplete="username"
                     value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES) ?>">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="inp-password">Password</label>
            <div class="input-icon-wrap">
              <span class="input-icon"><?= getIcon('lock-closed', 'icon-sm') ?></span>
              <input type="password" class="form-control" id="inp-password" name="password"
                     placeholder="Masukkan password" required autocomplete="current-password">
              <button type="button" class="password-toggle" id="toggle-password" aria-label="Tampilkan password">
                <?= getIcon('eye', 'icon-sm') ?>
              </button>
            </div>
          </div>

          <div id="login-error" class="form-error mb-3 <?= $error ? '' : 'is-hidden' ?>" role="alert">
            <?= htmlspecialchars($error, ENT_QUOTES) ?>
          </div>

          <button type="submit" class="btn btn-primary btn-block" id="login-btn">
            Masuk <?= getIcon('chevron-right', 'icon-sm') ?>
          </button>
        </form>

        <div class="login-demo">
          <p class="login-demo-title">Demo Akun</p>
          <div class="login-demo-grid">
            <div class="login-demo-item">
              <span class="login-demo-label">Admin</span>
              <span class="login-demo-cred">admin / admin123</span>
            </div>
            <div class="login-demo-item">
              <span class="login-demo-label">Petugas</span>
              <span class="login-demo-cred">petugas1 / petugas123</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Toggle password visibility
    (function () {
      const inp = document.getElementById('inp-password');
      const btn = document.getElementById('toggle-password');
      const eyeIcon      = `<?= getIcon('eye', 'icon-sm') ?>`;
      const eyeSlashIcon = `<?= getIcon('eye-slash', 'icon-sm') ?>`;

      btn.addEventListener('click', () => {
        if (inp.type === 'password') {
          inp.type = 'text';
          btn.innerHTML = eyeSlashIcon;
          btn.setAttribute('aria-label', 'Sembunyikan password');
        } else {
          inp.type = 'password';
          btn.innerHTML = eyeIcon;
          btn.setAttribute('aria-label', 'Tampilkan password');
        }
      });

      // Client-side guard — biar error langsung muncul tanpa round-trip
      const form = document.getElementById('login-form');
      const errDiv = document.getElementById('login-error');
      form.addEventListener('submit', (e) => {
        const u = document.getElementById('inp-username').value.trim();
        const p = inp.value;
        if (!u || !p) {
          e.preventDefault();
          errDiv.textContent = 'Username dan password wajib diisi!';
          errDiv.classList.remove('is-hidden');
        }
      });
    })();
  </script>
</body>
</html>
