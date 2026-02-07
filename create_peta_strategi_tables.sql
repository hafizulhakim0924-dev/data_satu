-- ============================================
-- TABEL PETA STRATEGI SUMATERA BARAT
-- Tabel untuk tracking lokasi strategis, jumlah warga, dan program
-- ============================================

-- Tabel Lokasi Strategis (Kabupaten/Kota/Kecamatan)
CREATE TABLE IF NOT EXISTS `lokasi_strategis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_lokasi` varchar(255) NOT NULL,
  `tipe_lokasi` enum('kabupaten','kota','kecamatan','desa','kelurahan') DEFAULT 'kecamatan',
  `latitude` DECIMAL(10, 8) NOT NULL,
  `longitude` DECIMAL(11, 8) NOT NULL,
  `total_warga` int(11) DEFAULT 0 COMMENT 'Total jumlah warga di lokasi ini',
  `warga_miskin` int(11) DEFAULT 0 COMMENT 'Jumlah warga miskin',
  `warga_terdampak` int(11) DEFAULT 0 COMMENT 'Jumlah warga yang terdampak dan perlu bantuan',
  `prioritas` enum('sangat_tinggi','tinggi','sedang','rendah') DEFAULT 'sedang',
  `keterangan` text DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tipe_lokasi` (`tipe_lokasi`),
  KEY `prioritas` (`prioritas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Mapping Program ke Lokasi
CREATE TABLE IF NOT EXISTS `program_lokasi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` int(11) NOT NULL,
  `lokasi_id` int(11) NOT NULL,
  `jumlah_penerima` int(11) DEFAULT 0 COMMENT 'Jumlah warga yang menerima program di lokasi ini',
  `dampak` text DEFAULT NULL COMMENT 'Dampak program di lokasi ini',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `program_id` (`program_id`),
  KEY `lokasi_id` (`lokasi_id`),
  FOREIGN KEY (`program_id`) REFERENCES `program_csr`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lokasi_id`) REFERENCES `lokasi_strategis`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SELESAI
-- ============================================

