CREATE DATABASE IF NOT EXISTS db_laundry;
USE db_laundry;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'petugas') DEFAULT 'petugas',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_invoice VARCHAR(20) NOT NULL UNIQUE,
    nama_pelanggan VARCHAR(100) NOT NULL,
    no_telepon VARCHAR(15),
    tgl_masuk DATETIME DEFAULT CURRENT_TIMESTAMP,
    tgl_ambil DATETIME NULL,
    paket ENUM('cuci_kering', 'cuci_setrika', 'express') NOT NULL DEFAULT 'cuci_kering',
    berat FLOAT NOT NULL DEFAULT 1,
    status_proses ENUM('antrean', 'proses', 'selesai', 'diambil') DEFAULT 'antrean',
    status_bayar ENUM('belum', 'lunas') DEFAULT 'belum',
    total_harga INT DEFAULT 0,
    user_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS order_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    jenis_pakaian VARCHAR(50) NOT NULL,
    jumlah INT NOT NULL,
    keterangan VARCHAR(255) NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Insert default admin: password is 'admin123'
INSERT IGNORE INTO users (username, password, nama_lengkap, role) VALUES 
('admin', '$2y$10$a5MEJPxPdWZT5LO9kV1nK.WiMVMzjDWBZ9SAIPgUKYH31tPkudxs.', 'Administrator', 'admin'),
('petugas1', '$2y$10$a5MEJPxPdWZT5LO9kV1nK.WiMVMzjDWBZ9SAIPgUKYH31tPkudxs.', 'Petugas Frontdesk', 'petugas');
