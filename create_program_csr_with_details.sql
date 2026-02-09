-- Script lengkap untuk membuat tabel program_csr dengan semua kolom detail
-- Jalankan script ini jika tabel program_csr belum ada

-- Buat tabel program_csr dengan semua kolom termasuk kolom detail
CREATE TABLE IF NOT EXISTS `program_csr` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_program` varchar(255) NOT NULL,
  `kategori` varchar(100) DEFAULT NULL,
  `deskripsi` text,
  `lokasi` varchar(255) DEFAULT NULL,
  `kecamatan` varchar(100) DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `provinsi` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(11,7) DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT 0.00,
  `realisasi_budget` decimal(15,2) DEFAULT 0.00,
  `progress` int(11) DEFAULT 0,
  `status` enum('planning','ongoing','completed','cancelled') DEFAULT 'planning',
  `pic` int(11) DEFAULT NULL,
  `jenis_bantuan` varchar(255) DEFAULT NULL,
  `jumlah_bantuan` decimal(15,2) DEFAULT 0.00,
  `satuan` varchar(50) DEFAULT NULL,
  `jumlah_penerima_manfaat` int(11) DEFAULT 0,
  `jumlah_relawan_terlibat` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_kategori` (`kategori`),
  KEY `idx_pic` (`pic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Verifikasi tabel telah dibuat
SELECT 'Tabel program_csr berhasil dibuat!' as Status;

-- Lihat struktur tabel
DESCRIBE program_csr;

