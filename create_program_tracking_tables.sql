-- ============================================
-- TABEL TRACKING PROGRAM CSR
-- Tabel untuk tracking penyaluran dan dampak program
-- ============================================

-- Tabel Penyaluran Program (Distribusi dana/program)
CREATE TABLE IF NOT EXISTS `program_penyaluran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` int(11) NOT NULL,
  `tanggal_penyaluran` date NOT NULL,
  `jumlah_penyaluran` decimal(15,2) NOT NULL,
  `sasaran` varchar(255) DEFAULT NULL COMMENT 'Sasaran penerima (contoh: 100 keluarga, 50 anak yatim)',
  `lokasi_penyaluran` varchar(255) DEFAULT NULL,
  `metode_penyaluran` enum('tunai','barang','jasa','voucher','lainnya') DEFAULT 'tunai',
  `keterangan` text DEFAULT NULL,
  `bukti_penyaluran` varchar(255) DEFAULT NULL COMMENT 'File foto/dokumen bukti',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `program_id` (`program_id`),
  KEY `created_by` (`created_by`),
  FOREIGN KEY (`program_id`) REFERENCES `program_csr`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Dampak Program (Impact measurement)
CREATE TABLE IF NOT EXISTS `program_dampak` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` int(11) NOT NULL,
  `tanggal_pengukuran` date NOT NULL,
  `indikator` varchar(255) NOT NULL COMMENT 'Indikator dampak (contoh: Jumlah penerima manfaat, Tingkat kepuasan)',
  `nilai` decimal(15,2) DEFAULT NULL COMMENT 'Nilai/nilai kuantitatif',
  `satuan` varchar(50) DEFAULT NULL COMMENT 'Satuan (contoh: orang, keluarga, %)',
  `deskripsi` text DEFAULT NULL,
  `kategori_dampak` enum('ekonomi','sosial','pendidikan','kesehatan','lingkungan','lainnya') DEFAULT 'sosial',
  `foto_dokumentasi` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `program_id` (`program_id`),
  KEY `created_by` (`created_by`),
  FOREIGN KEY (`program_id`) REFERENCES `program_csr`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tambahkan kolom progress di tabel program_csr (jika belum ada)
-- Catatan: IF NOT EXISTS tidak didukung di ALTER TABLE, jadi skip jika error "Duplicate column"
ALTER TABLE `program_csr` 
ADD COLUMN `progress` INT(3) DEFAULT 0 COMMENT 'Progress program dalam persen (0-100)' AFTER `status`;

ALTER TABLE `program_csr` 
ADD COLUMN `realisasi_budget` DECIMAL(15,2) DEFAULT 0 COMMENT 'Budget yang sudah direalisasikan' AFTER `budget`;

-- ============================================
-- SELESAI
-- ============================================

