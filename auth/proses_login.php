<?php
session_start();
require_once '../config/koneksi.php';
require_once '../config/helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($koneksi, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'Username dan password wajib diisi!';
        header("Location: login.php");
        exit;
    }

    $stmt = mysqli_prepare($koneksi, "SELECT id, username, password, nama_lengkap, role FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            // Login sukses
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
            $_SESSION['role'] = $row['role'];
            
            header("Location: ../views/dashboard.php");
            exit;
        }
    }

    // Login gagal
    $_SESSION['error'] = 'Username atau password salah! Silakan coba lagi.';
    header("Location: login.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}
?>
