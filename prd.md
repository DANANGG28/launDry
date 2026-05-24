Product Requirement Document (PRD) & Rencana PengembanganSistem Manajemen Laundry (Berbasis Web)Dokumen ini disusun sebagai panduan pengembangan aplikasi Sistem Laundry berbasis web menggunakan PHP Native dan MySQL. Sistem ini dirancang untuk memenuhi kebutuhan tugas kuliah dengan struktur kode yang rapi, bersih, dan mudah dipahami.1. Deskripsi ProdukSistem Laundry adalah aplikasi berbasis web yang berfungsi untuk mendigitalisasi proses transaksi pada usaha laundry. Aplikasi ini menangani pencatatan order masuk, penyimpanan detail pakaian per transaksi, pelacakan status proses pencucian, pengambilan barang, hingga manajemen pembayaran.2. Spesifikasi Teknis (Tech Stack)Bahasa Pemrograman: PHP Native (Pendekatan prosedural terstruktur yang bersih agar mudah dijelaskan saat presentasi/sidang tugas).Database: MySQL / MariaDB (Menggunakan MySQLi atau PDO).Antarmuka (UI): HTML5, CSS3 (Menggunakan Tailwind CSS atau Bootstrap 5 via CDN agar praktis tanpa instalasi lokal), dan JavaScript minimal untuk interaksi dinamis.3. Panduan Desain Antarmuka (UI/UX)Untuk memastikan aplikasi terlihat profesional untuk tugas kuliah, implementasi UI wajib mengikuti prinsip berikut:A. Konsep EstetikaModern & Minimalis: Mengutamakan ruang kosong (whitespace) yang cukup agar tidak terasa sesak. Menggunakan sudut melengkung (rounded corners), bayangan halus (soft shadows), dan garis batas tipis yang bersih.Palet Warna Netral & Profesional: * Warna Utama: Slate/Gray (sebagai latar belakang dan teks utama).Warna Aksen: Indigo/Blue (untuk tombol utama, tautan aktif, atau elemen penting).Warna Status: Emerald/Green (Lunas/Selesai), Amber/Yellow (Proses), Rose/Red (Belum Bayar/Antrean).B. Responsivitas (Mobile-First / Fluid Layout)Tata Letak Adaptif: Layout harus menyesuaikan diri secara otomatis saat dibuka di HP, tablet, maupun laptop.Sidebar Kolaps: Navigasi samping (sidebar) pada tampilan desktop harus otomatis disembunyikan ke dalam tombol menu (hamburger menu) jika diakses lewat perangkat mobile.Scrollable Tables: Tabel daftar transaksi harus dibungkus dengan kelas pembatas responsif agar tidak merusak lebar layar pada perangkat kecil (menggunakan scroll horizontal khusus tabel saja).Ukuran Target Sentuh: Tombol aksi (edit, hapus, ubah status) harus memiliki ukuran minimal $44 \times 44\text{ px}$ agar mudah ditekan menggunakan jari di layar sentuh.4. Arsitektur Database (Skema Tabel)Berikut adalah rancangan tabel database yang saling berelasi:a. Tabel users (Manajemen Pengguna & Auth)Menyimpan data petugas atau admin yang memiliki hak akses untuk mengoperasikan sistem.CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'petugas') DEFAULT 'petugas',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
b. Tabel orders (Data Utama Transaksi)Menyimpan informasi utama dari setiap transaksi laundry yang masuk.CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_invoice VARCHAR(20) NOT NULL UNIQUE,
    nama_pelanggan VARCHAR(100) NOT NULL,
    no_telepon VARCHAR(15),
    tgl_masuk DATETIME DEFAULT CURRENT_TIMESTAMP,
    tgl_ambil DATETIME NULL,
    status_proses ENUM('antrean', 'proses', 'selesai', 'diambil') DEFAULT 'antrean',
    status_bayar ENUM('belum', 'lunas') DEFAULT 'belum',
    total_harga INT DEFAULT 0,
    user_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
c. Tabel order_details (Detail Item Pakaian)Menyimpan rincian pakaian yang dititipkan dalam satu nomor transaksi (Order). Satu transaksi dapat memiliki banyak baris detail pakaian (One-to-Many).CREATE TABLE order_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    jenis_pakaian VARCHAR(50) NOT NULL, -- Contoh: Kaos, Celana Jeans, Selimut, Jas
    jumlah INT NOT NULL,
    keterangan VARCHAR(255) NULL,       -- Contoh: Warna merah, ada noda luntur
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
5. Alur Kerja Sistem & Fitur UtamaA. Sistem Autentikasi (Auth)Login & Logout: Mengamankan aplikasi menggunakan mekanisme session_start() pada PHP.Keamanan Password: Menggunakan enkripsi searah bawaan PHP yaitu password_hash() saat registrasi user, dan divalidasi menggunakan password_verify() saat login.Proteksi Halaman (Middleware Sederhana): Setiap halaman admin/petugas wajib memeriksa keberadaan session aktif. Jika session tidak ditemukan, pengguna langsung diarahkan kembali ke halaman login.B. Manajemen Transaksi (CRUD Order)Pencatatan Baru: Mengisi form data pelanggan (nama, nomor telepon, estimasi selesai) sekaligus menambahkan baris detail pakaian secara dinamis menggunakan JavaScript sederhana atau input baris berulang.Simpangan Relasi: Sistem menyimpan data utama ke tabel orders terlebih dahulu, lalu mengambil insert_id terakhir untuk mengikat data pakaian ke tabel order_details.C. Pelacakan Status & Alur PengambilanPelacakan Status: Status pengerjaan dapat diperbarui secara bertahap oleh petugas:$$\text{Antrean} \longrightarrow \text{Proses} \longrightarrow \text{Selesai} \longrightarrow \text{Diambil}$$Sistem Pengambilan: Saat status diubah menjadi Diambil, aplikasi secara otomatis mengisi kolom tgl_ambil dengan waktu saat itu (NOW()).D. Manajemen PembayaranPengaturan status pembayaran secara instan (Belum Dibayar / Lunas).Total harga otomatis dihitung berdasarkan kuantitas atau berat bawaan yang diinput oleh petugas saat pendaftaran order.6. Struktur Berkas Proyek (Rapi & Standar)Struktur folder dibuat modular agar mudah dipelajari, dikembangkan, dan terlihat profesional saat diperiksa oleh dosen penguji:laundry_app/
│
├── config/
│   └── koneksi.php       # Berkas konfigurasi koneksi database MySQLi
│
├── auth/
│   ├── login.php         # Tampilan form login masuk sistem
│   ├── proses_login.php  # Proses verifikasi username & password
│   └── logout.php        # Proses menghapus session & redirect
│
├── views/                # Folder khusus modul fitur utama
│   ├── dashboard.php     # Halaman dashboard (Berisi info ringkas/statistik)
│   ├── order_tambah.php  # Form pembuatan transaksi laundry baru
│   ├── order_list.php    # Tabel daftar transaksi yang berjalan
│   ├── order_detail.php  # Detail pakaian & tombol aksi ubah status
│   └── order_edit.php    # Form perubahan data pelanggan/transaksi
│
├── templates/            # Bagian visual yang digunakan berulang
│   ├── header.php        # Bagian atas halaman & menu navigasi samping
│   └── footer.php        # Bagian penutup halaman & script JS
│
└── index.php             # Halaman utama (Gateway pengarah ke login/dashboard)
7. Rencana Tahapan Implementasi (Timeline Kerja)Untuk memastikan proyek ini selesai tepat waktu sebelum batas pengumpulan tugas kuliah, berikut adalah rencana kerja terstruktur:TahapFokus PekerjaanDetail AktivitasTahap 1Basis Data & KoneksiMembuat database db_laundry di phpMyAdmin, menyusun tabel dan relasi foreign key, serta menguji file koneksi.php.Tahap 2Autentikasi PenggunaMembuat halaman login, fungsi enkripsi password, penanganan session PHP, dan proteksi URL.Tahap 3Transaksi & RelasiMembangun antarmuka form pembuatan order baru, fitur input dinamis untuk detail pakaian, dan menyimpan data secara bersamaan ke dua tabel terkait.Tahap 4Manajemen StatusMengimplementasikan fitur pelacakan status laundry, integrasi tombol update status, serta pencatatan otomatis tanggal pengambilan.Tahap 5Sentuhan Akhir & DemoMelakukan validasi input form (mencegah SQL Injection sederhana & form kosong), merapikan layout CSS, serta uji coba skenario transaksi penuh (end-to-end).