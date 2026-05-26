<?php
// templates/header.php
ob_start(); // Buffer output so header() redirects always work
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/helper.php';

$user = requireAuth();

$page = isset($pageTitle) ? $pageTitle : 'launDry';
$activePage = isset($activeMenu) ? $activeMenu : '';
$subtitle = isset($pageSubtitle) ? $pageSubtitle : '';

// Flash message
$flashMsg = '';
if (isset($_SESSION['flash_msg'])) {
    $msg = $_SESSION['flash_msg'];
    $icon = ['success' => getIcon('check-circle', 'w-6 h-6 text-emerald-500'), 'error' => getIcon('x-circle', 'w-6 h-6 text-rose-500'), 'info' => getIcon('info', 'w-6 h-6 text-blue-500')][$type] ?? getIcon('info', 'w-6 h-6 text-blue-500');
    
    $flashMsg = "
    <div id='php-toast' class='toast $type' style='opacity: 0; transform: translateX(20px); transition: all 0.3s ease; display:flex; align-items:center; gap:8px;'>
        $icon <span>$msg</span>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                container.className = 'toast-container';
                document.body.appendChild(container);
            }
            const t = document.getElementById('php-toast');
            container.appendChild(t);
            setTimeout(() => {
                t.style.opacity = '1';
                t.style.transform = 'translateX(0)';
            }, 100);
            setTimeout(() => {
                t.style.opacity = '0';
                t.style.transform = 'translateX(20px)';
                setTimeout(() => t.remove(), 300);
            }, 3000);
        });
    </script>
    ";
    
    unset($_SESSION['flash_msg']);
    unset($_SESSION['flash_type']);
}

$initials = strtoupper(substr($user['nama_lengkap'], 0, 2));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page) ?> — launDry</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <?= $flashMsg ?>
  <div class="app-layout">
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-brand">
        <div class="brand-icon"><?= getIcon('sparkles', 'w-8 h-8') ?></div>
        <div>
          <div class="brand-name">launDry</div>
          <div class="brand-sub">Management System</div>
        </div>
      </div>

      <nav class="sidebar-nav">
        <div class="nav-section-label">Menu Utama</div>
        <a href="dashboard.php" class="nav-item <?= $activePage == 'dashboard' ? 'active' : '' ?>">
          <span class="nav-icon"><?= getIcon('chart-bar', 'w-5 h-5') ?></span>
          <span>Dashboard</span>
        </a>
        <a href="order_list.php" class="nav-item <?= $activePage == 'order_list' ? 'active' : '' ?>">
          <span class="nav-icon"><?= getIcon('clipboard-document-list', 'w-5 h-5') ?></span>
          <span>Daftar Order</span>
        </a>
        
        <?php if($user['role'] === 'admin'): ?>
        <div class="nav-section-label" style="margin-top:16px">Manajemen</div>
        <a href="user_list.php" class="nav-item <?= $activePage == 'user_list' ? 'active' : '' ?>">
          <span class="nav-icon"><?= getIcon('users', 'w-5 h-5') ?></span>
          <span>Manajemen Pengguna</span>
        </a>
        <?php endif; ?>

        <div class="nav-section-label" style="margin-top:16px">Laporan</div>
        <a href="laporan_keuangan.php" class="nav-item <?= $activePage == 'laporan_keuangan' ? 'active' : '' ?>">
          <span class="nav-icon"><?= getIcon('chart-pie', 'w-5 h-5') ?></span>
          <span>Laporan Keuangan</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar"><?= $initials ?></div>
          <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
            <div class="user-role"><?= $user['role'] == 'admin' ? 'Administrator' : 'Petugas' ?></div>
          </div>
          <button class="logout-btn" onclick="if(confirm('Yakin ingin keluar?')) window.location.href='../auth/logout.php'" title="Keluar">
            <?= getIcon('arrow-right-on-rectangle', 'w-5 h-5') ?>
          </button>
        </div>
      </div>
    </aside>
    
    <div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>
    
    <div class="main-content">
      <header class="topbar" id="topbar">
        <button class="topbar-hamburger" id="hamburger-btn" onclick="toggleSidebar()" aria-label="Toggle menu">
          <?= getIcon('bars-3', 'w-6 h-6') ?>
        </button>
        <div class="topbar-title">
          <h1><?= htmlspecialchars($page) ?></h1>
          <?php if($subtitle): ?><p><?= htmlspecialchars($subtitle) ?></p><?php endif; ?>
        </div>
      </header>
      
      <main class="page-content" id="page-content">
