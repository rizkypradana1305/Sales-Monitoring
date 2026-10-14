-- ============================================================
-- Sales Territory Optimizer - Telkom Madiun
-- Database Schema & Seed Data
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS sales_territory;
CREATE DATABASE sales_territory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sales_territory;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    peran ENUM('Administrator','Manajer','Operator') NOT NULL DEFAULT 'Operator',
    telepon VARCHAR(30),
    status ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: sales
-- --------------------------------------------------------
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(120) NOT NULL,
    email VARCHAR(120),
    telepon VARCHAR(30),
    nik VARCHAR(30),
    wilayah VARCHAR(80) NOT NULL,
    latitude DECIMAL(10,6) NOT NULL,
    longitude DECIMAL(10,6) NOT NULL,
    kapasitas INT NOT NULL DEFAULT 15,
    status ENUM('Aktif','Cuti','Nonaktif') NOT NULL DEFAULT 'Aktif',
    foto VARCHAR(255),
    tanggal_bergabung DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: pelanggan
-- --------------------------------------------------------
CREATE TABLE pelanggan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150) NOT NULL,
    alamat TEXT,
    wilayah VARCHAR(80) NOT NULL,
    latitude DECIMAL(10,6) NOT NULL,
    longitude DECIMAL(10,6) NOT NULL,
    potensi ENUM('Rendah','Sedang','Tinggi') NOT NULL DEFAULT 'Sedang',
    skor_potensi INT NOT NULL DEFAULT 50,
    produk VARCHAR(80),
    status ENUM('Prospek','Aktif','Pending') NOT NULL DEFAULT 'Prospek',
    sales_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sales_id) REFERENCES sales(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: parameter_alokasi
-- --------------------------------------------------------
CREATE TABLE parameter_alokasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(80) NOT NULL DEFAULT 'Parameter Default',
    bobot_jarak DECIMAL(5,2) NOT NULL DEFAULT 60.00,
    bobot_potensi DECIMAL(5,2) NOT NULL DEFAULT 40.00,
    maksimum_kapasitas INT NOT NULL DEFAULT 15,
    toleransi_jarak DECIMAL(10,2) NOT NULL DEFAULT 30.00,
    radius_km DECIMAL(10,2) NOT NULL DEFAULT 25.00,
    mode_solver ENUM('Greedy','ILP','Balanced') NOT NULL DEFAULT 'Greedy',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: optimasi_run
-- --------------------------------------------------------
CREATE TABLE optimasi_run (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(40) NOT NULL,
    status ENUM('Berjalan','Selesai','Gagal') NOT NULL DEFAULT 'Berjalan',
    total_jarak DECIMAL(10,2) DEFAULT 0,
    total_teralokasi INT DEFAULT 0,
    total_pelanggan INT DEFAULT 0,
    durasi_ms INT DEFAULT 0,
    parameter_id INT,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parameter_id) REFERENCES parameter_alokasi(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: hasil_haversine
-- --------------------------------------------------------
CREATE TABLE hasil_haversine (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pelanggan_id INT NOT NULL,
    sales_id INT NOT NULL,
    jarak_km DECIMAL(10,3) NOT NULL,
    run_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE,
    FOREIGN KEY (sales_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (run_id) REFERENCES optimasi_run(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: hasil_alokasi
-- --------------------------------------------------------
CREATE TABLE hasil_alokasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pelanggan_id INT NOT NULL,
    sales_id INT NOT NULL,
    jarak_km DECIMAL(10,3) NOT NULL,
    skor_potensi INT DEFAULT 0,
    status ENUM('Optimal','Alternatif','Manual') NOT NULL DEFAULT 'Optimal',
    run_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE,
    FOREIGN KEY (sales_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (run_id) REFERENCES optimasi_run(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: pengaturan (settings)
-- --------------------------------------------------------
CREATE TABLE pengaturan (
    `key` VARCHAR(60) PRIMARY KEY,
    `value` TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default admin user. Password: admin123
INSERT INTO users (nama, email, password, peran, telepon, status) VALUES
('Admin Sistem', 'admin@telkom.co.id', '$2b$10$J/sj7NeT8kF0lZ/2NNybWOw5qSevdFt/DNMg71nZ0EwXiydBIqZCO', 'Administrator', '081234567890', 'Aktif'),
('Budi Manajer', 'budi@telkom.co.id', '$2b$10$J/sj7NeT8kF0lZ/2NNybWOw5qSevdFt/DNMg71nZ0EwXiydBIqZCO', 'Manajer', '081234567891', 'Aktif'),
('Siti Operator', 'siti@telkom.co.id', '$2b$10$J/sj7NeT8kF0lZ/2NNybWOw5qSevdFt/DNMg71nZ0EwXiydBIqZCO', 'Operator', '081234567892', 'Aktif');

-- ============================================================
-- Seed: Sales (Madiun region)
-- ============================================================
INSERT INTO sales (nama, email, telepon, nik, wilayah, latitude, longitude, kapasitas, status, tanggal_bergabung) VALUES
('Tatang Mu''Alifin', 'tatang@telkom.co.id', '081234500001', '3501010101900001', 'Madiun Kota',    -7.6298, 111.5239, 14, 'Aktif', '2023-01-15'),
('Siti Aminah',       'siti.a@telkom.co.id', '081234500002', '3501010101900002', 'Madiun Barat',  -7.6432, 111.4987, 12, 'Aktif', '2023-03-20'),
('Joko Santoso',      'joko@telkom.co.id',   '081234500003', '3501010101900003', 'Madiun Timur',  -7.6156, 111.5521, 13, 'Aktif', '2023-02-10'),
('Dewi Lestari',      'dewi@telkom.co.id',   '081234500004', '3501010101900004', 'Madiun Selatan',-7.6701, 111.5324, 12, 'Aktif', '2023-04-05'),
('Rudi Hartono',      'rudi@telkom.co.id',   '081234500005', '3501010101900005', 'Madiun Utara',  -7.6089, 111.5145, 15, 'Aktif', '2023-05-12'),
('Ahmad Fauzi',       'ahmad@telkom.co.id',  '081234500006', '3501010101900006', 'Kartoharjo',    -7.6021, 111.5178, 11, 'Cuti',  '2023-06-18'),
('Maya Sari',         'maya@telkom.co.id',   '081234500007', '3501010101900007', 'Taman',         -7.6543, 111.4892, 13, 'Aktif', '2023-07-22'),
('Bambang Wijaya',    'bambang@telkom.co.id','081234500008', '3501010101900008', 'Mangunharjo',   -7.5898, 111.5412, 10, 'Aktif', '2023-08-30');

-- ============================================================
-- Seed: Pelanggan (customers in Madiun region)
-- ============================================================
INSERT INTO pelanggan (nama, alamat, wilayah, latitude, longitude, potensi, skor_potensi, produk, status) VALUES
('Toko Makmur Jaya',         'Jl. Kartini No.15, Madiun',        'Madiun Kota',     -7.6320, 111.5260, 'Tinggi', 90, 'Internet Bisnis', 'Aktif'),
('Warung Bu Tini',           'Jl. Diponegoro No.8, Madiun',     'Madiun Kota',     -7.6285, 111.5210, 'Sedang', 60, 'IndiHome',        'Aktif'),
('CV Sumber Rejeki',         'Jl. Ahmad Yani No.45, Madiun',    'Madiun Kota',     -7.6350, 111.5290, 'Tinggi', 85, 'Internet Bisnis', 'Prospek'),
('Toko Elektronik Maju',     'Jl. Gajah Mada No.22, Madiun',    'Madiun Kota',     -7.6245, 111.5185, 'Tinggi', 88, 'Internet Bisnis', 'Aktif'),
('Pondok Pesantren Al-Hikmah','Jl. Raya Madiun KM 3, Madiun',    'Madiun Barat',    -7.6510, 111.4950, 'Sedang', 70, 'IndiHome',        'Aktif'),
('SMK Negeri 1 Madiun',      'Jl. Gatot Subroto No.5, Madiun',  'Madiun Barat',    -7.6480, 111.4990, 'Tinggi', 95, 'Internet Bisnis', 'Aktif'),
('Hotel Madiun Permai',      'Jl. Merdeka No.1, Madiun',        'Madiun Barat',    -7.6405, 111.5020, 'Tinggi', 92, 'Internet Bisnis', 'Aktif'),
('Resto Padang Sederhana',   'Jl. Dr. Cipto No.30, Madiun',     'Madiun Timur',    -7.6185, 111.5560, 'Sedang', 65, 'IndiHome',        'Prospek'),
('Toko Bangunan Sentosa',    'Jl. Monginsidi No.18, Madiun',    'Madiun Timur',    -7.6140, 111.5485, 'Tinggi', 80, 'Internet Bisnis', 'Aktif'),
('Pasar Sleko Blok B',       'Jl. Ahmad Yani KM 2, Madiun',     'Madiun Timur',    -7.6200, 111.5540, 'Sedang', 55, 'IndiHome',        'Pending'),
('RSUD dr. Haryoto',         'Jl. Dr. Sutomo No.1, Madiun',     'Madiun Selatan',  -7.6720, 111.5300, 'Tinggi', 98, 'Internet Bisnis', 'Aktif'),
('Apotek Kimia Farma',       'Jl. Panjaitan No.25, Madiun',     'Madiun Selatan',  -7.6700, 111.5340, 'Sedang', 75, 'IndiHome',        'Aktif'),
('Toko Spare Part Motor',    'Jl. Yos Sudarso No.10, Madiun',   'Madiun Selatan',  -7.6750, 111.5280, 'Rendah', 40, 'IndiHome',        'Prospek'),
('Bengkel Mobil Jaya',       'Jl. Raya Solo KM 4, Madiun',      'Madiun Selatan',  -7.6800, 111.5250, 'Sedang', 62, 'IndiHome',        'Prospek'),
('SDN Tawangrejo 1',         'Jl. Raya Tawangrejo, Madiun',     'Madiun Utara',    -7.6050, 111.5130, 'Sedang', 70, 'IndiHome',        'Aktif'),
('Puskesmas Banjarejo',      'Jl. Banjarejo No.2, Madiun',      'Madiun Utara',    -7.6100, 111.5160, 'Tinggi', 85, 'Internet Bisnis', 'Aktif'),
('Toko Kelontong Mak Ustad','Jl. Sumbawa No.5, Madiun',        'Madiun Utara',    -7.6135, 111.5120, 'Rendah', 35, 'IndiHome',        'Pending'),
('Warung Kopi Seduh',        'Jl. Patimura No.12, Madiun',      'Madiun Utara',    -7.6075, 111.5190, 'Sedang', 58, 'IndiHome',        'Prospek'),
('Kantor Notaris Madiun',    'Jl. Agus Salim No.7, Madiun',     'Madiun Kota',     -7.6305, 111.5245, 'Tinggi', 92, 'Internet Bisnis', 'Aktif'),
('Toko Material Bangunan',   'Jl. Brigjen Katamso No.30, Madiun','Madiun Timur',   -7.6170, 111.5470, 'Sedang', 68, 'IndiHome',        'Prospek'),
('Salon Cantik Permai',      'Jl. Panderman No.8, Madiun',      'Madiun Selatan',  -7.6680, 111.5310, 'Rendah', 42, 'IndiHome',        'Pending'),
('Warung Sate Ponorogo',     'Jl. Diponegoro No.45, Madiun',    'Madiun Kota',     -7.6260, 111.5230, 'Sedang', 65, 'IndiHome',        'Aktif'),
('Mini Market Indah',        'Jl. Ciptomulyo No.14, Madiun',    'Kartoharjo',      -7.6040, 111.5190, 'Tinggi', 82, 'Internet Bisnis', 'Aktif'),
('Workshop Percetakan',      'Jl. Raya Kartoharjo, Madiun',     'Kartoharjo',      -7.5990, 111.5160, 'Sedang', 60, 'IndiHome',        'Prospek'),
('Kantor Advokat Mitra',     'Jl. Tukum No.5, Madiun',          'Kartoharjo',      -7.6010, 111.5210, 'Tinggi', 87, 'Internet Bisnis', 'Aktif'),
('Toko Sembako Berkah',      'Jl. Wonokromo No.7, Madiun',      'Kartoharjo',      -7.6060, 111.5230, 'Sedang', 55, 'IndiHome',        'Aktif'),
('Hotel Tirtosari',          'Jl. Raya Taman KM 1, Taman',       'Taman',           -7.6580, 111.4870, 'Tinggi', 88, 'Internet Bisnis', 'Aktif'),
('Pabrik Tahu Sejahtera',    'Jl. Raya Taman No.10, Taman',      'Taman',           -7.6610, 111.4910, 'Sedang', 65, 'IndiHome',        'Prospek'),
('Toko Furniture Kayu Jati', 'Jl. Raya Mangunharjo, Madiun',     'Mangunharjo',     -7.5910, 111.5430, 'Tinggi', 80, 'Internet Bisnis', 'Prospek'),
('Kantor Pos Madiun',        'Jl. Jenderal Sudirman No.1, Madiun','Madiun Kota',   -7.6335, 111.5275, 'Tinggi', 90, 'Internet Bisnis', 'Aktif'),
('Toko Komputer Megabyte',   'Jl. Brigjen Katamso No.12, Madiun','Madiun Timur',   -7.6195, 111.5495, 'Tinggi', 85, 'Internet Bisnis', 'Aktif'),
('Studio Foto Kenangan',     'Jl. Pahlawan No.9, Madiun',        'Madiun Selatan',  -7.6690, 111.5335, 'Sedang', 60, 'IndiHome',        'Pending'),
('Warung Makan Bu Rudy',     'Jl. Raya Caruban, Madiun',         'Madiun Timur',    -7.6120, 111.5570, 'Rendah', 45, 'IndiHome',        'Prospek'),
('Toko Buku Pelita',         'Jl. Rajiman No.6, Madiun',         'Madiun Barat',    -7.6450, 111.5005, 'Sedang', 68, 'IndiHome',        'Aktif'),
('Foto Copy Anugerah',       'Jl. Citra No.3, Madiun',           'Madiun Utara',    -7.6140, 111.5175, 'Sedang', 58, 'IndiHome',        'Pending'),
('PT Sumber Makmur',         'Jl. Industri No.8, Madiun',         'Madiun Timur',    -7.6160, 111.5510, 'Tinggi', 88, 'Internet Bisnis', 'Aktif'),
('Klinik Sehat Selalu',      'Jl. Sudirman No.15, Madiun',       'Madiun Kota',     -7.6310, 111.5290, 'Tinggi', 80, 'Internet Bisnis', 'Aktif'),
('Toko Sepatu Murah Meriah',  'Jl. Imogiri No.4, Madiun',        'Madiun Selatan',  -7.6720, 111.5290, 'Rendah', 38, 'IndiHome',        'Prospek'),
('Warung Sembako Langganan', 'Jl. Pajajaran No.9, Madiun',       'Kartoharjo',      -7.6030, 111.5185, 'Sedang', 52, 'IndiHome',        'Aktif'),
('Toko Pakaian Batik',       'Jl. Brawijaya No.10, Madiun',      'Madiun Kota',     -7.6275, 111.5255, 'Tinggi', 78, 'Internet Bisnis', 'Aktif'),
('Service HP Cepat',         'Jl. Kebonsari No.7, Madiun',        'Madiun Timur',    -7.6135, 111.5525, 'Sedang', 62, 'IndiHome',        'Prospek'),
('Cafe Kopi Hitam',          'Jl. Cendrawasih No.5, Madiun',      'Madiun Utara',    -7.6110, 111.5150, 'Sedang', 70, 'IndiHome',        'Aktif'),
('Laundry Kiloan Bersih',    'Jl. Kutilang No.3, Madiun',        'Madiun Selatan',  -7.6685, 111.5320, 'Rendah', 48, 'IndiHome',        'Pending'),
('Toko Cake Lebaran',        'Jl. Cempaka No.8, Madiun',          'Madiun Barat',    -7.6440, 111.4975, 'Tinggi', 84, 'Internet Bisnis', 'Prospek'),
('Bengkel Las Listrik',      'Jl. Industri No.12, Madiun',        'Madiun Timur',   -7.6150, 111.5535, 'Sedang', 60, 'IndiHome',        'Aktif');

-- ============================================================
-- Seed: Parameter Alokasi
-- ============================================================
INSERT INTO parameter_alokasi (nama, bobot_jarak, bobot_potensi, maksimum_kapasitas, toleransi_jarak, radius_km, mode_solver) VALUES
('Parameter Default 2026', 60.00, 40.00, 15, 30.00, 25.00, 'Greedy');

-- ============================================================
-- Seed: Settings
-- ============================================================
INSERT INTO pengaturan (`key`, `value`) VALUES
('app_name', 'Alokasi Sales Telkom Madiun'),
('app_version', '2.6.0'),
('primary_color', '#E31E24'),
('map_default_lat', '-7.6298'),
('map_default_lng', '111.5239'),
('map_default_zoom', '13'),
('map_provider', 'OpenStreetMap'),
('mail_host', 'smtp.telkom.co.id'),
('mail_port', '587'),
('mail_username', 'noreply@telkom.co.id'),
('notification_enabled', '1'),
('auto_allocate_on_create', '0');

SET FOREIGN_KEY_CHECKS = 1;
