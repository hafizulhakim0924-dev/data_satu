-- ============================================
-- FIX ALL TABLES FOR PROGRAM.PHP
-- Script ini memastikan semua tabel dan kolom yang diperlukan ada
-- ============================================

-- 1. Pastikan tabel program_csr ada dengan semua kolom
CREATE TABLE IF NOT EXISTS `program_csr` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_program` varchar(255) NOT NULL,
  `kategori` varchar(100) DEFAULT NULL,
  `deskripsi` text,
  `lokasi` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(11,7) DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT 0.00,
  `realisasi_budget` decimal(15,2) DEFAULT 0.00,
  `progress` int(11) DEFAULT 0,
  `status` enum('planning','ongoing','completed','cancelled') DEFAULT 'planning',
  `pic` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_kategori` (`kategori`),
  KEY `idx_pic` (`pic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Pastikan tabel program_penyaluran ada
CREATE TABLE IF NOT EXISTS `program_penyaluran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` int(11) NOT NULL,
  `tanggal_penyaluran` date NOT NULL,
  `jumlah_penyaluran` decimal(15,2) NOT NULL DEFAULT 0.00,
  `jenis_penyaluran` varchar(100) DEFAULT NULL,
  `penerima` varchar(255) DEFAULT NULL,
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_program_id` (`program_id`),
  KEY `idx_tanggal` (`tanggal_penyaluran`),
  CONSTRAINT `fk_penyaluran_program` FOREIGN KEY (`program_id`) REFERENCES `program_csr` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Pastikan tabel program_dampak ada
CREATE TABLE IF NOT EXISTS `program_dampak` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` int(11) NOT NULL,
  `tanggal_pengukuran` date NOT NULL,
  `kategori_dampak` varchar(100) DEFAULT NULL,
  `indikator` varchar(255) DEFAULT NULL,
  `nilai` decimal(10,2) DEFAULT 0.00,
  `satuan` varchar(50) DEFAULT NULL,
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_program_id` (`program_id`),
  KEY `idx_kategori` (`kategori_dampak`),
  KEY `idx_tanggal` (`tanggal_pengukuran`),
  CONSTRAINT `fk_dampak_program` FOREIGN KEY (`program_id`) REFERENCES `program_csr` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Pastikan tabel lokasi_strategis ada
CREATE TABLE IF NOT EXISTS `lokasi_strategis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_lokasi` varchar(255) NOT NULL,
  `tipe_lokasi` enum('kabupaten','kota','kecamatan','desa','kelurahan') DEFAULT 'desa',
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(11,7) DEFAULT NULL,
  `total_warga` int(11) DEFAULT 0,
  `warga_miskin` int(11) DEFAULT 0,
  `warga_terdampak` int(11) DEFAULT 0,
  `prioritas` enum('sangat_tinggi','tinggi','sedang','rendah') DEFAULT 'sedang',
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_prioritas` (`prioritas`),
  KEY `idx_tipe` (`tipe_lokasi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Pastikan tabel program_lokasi ada
CREATE TABLE IF NOT EXISTS `program_lokasi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` int(11) NOT NULL,
  `lokasi_id` int(11) NOT NULL,
  `jumlah_penerima` int(11) DEFAULT 0,
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_program_id` (`program_id`),
  KEY `idx_lokasi_id` (`lokasi_id`),
  CONSTRAINT `fk_program_lokasi_program` FOREIGN KEY (`program_id`) REFERENCES `program_csr` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_program_lokasi_lokasi` FOREIGN KEY (`lokasi_id`) REFERENCES `lokasi_strategis` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Pastikan tabel csr_donations memiliki kolom 'program'
DELIMITER $$
DROP PROCEDURE IF EXISTS AddColumnIfNotExists$$
CREATE PROCEDURE AddColumnIfNotExists(
    IN tableName VARCHAR(128),
    IN columnName VARCHAR(128),
    IN columnDefinition TEXT
)
BEGIN
    DECLARE columnExists INT DEFAULT 0;
    SELECT COUNT(*) INTO columnExists
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = tableName
      AND COLUMN_NAME = columnName;
    IF columnExists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', tableName, '` ADD COLUMN `', columnName, '` ', columnDefinition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

-- Tambahkan kolom 'program' ke csr_donations jika belum ada
CALL AddColumnIfNotExists('csr_donations', 'program', 'VARCHAR(255) DEFAULT NULL AFTER `kategori`');

-- Tambahkan kolom ke program_csr jika belum ada
CALL AddColumnIfNotExists('program_csr', 'latitude', 'DECIMAL(10,7) DEFAULT NULL');
CALL AddColumnIfNotExists('program_csr', 'longitude', 'DECIMAL(11,7) DEFAULT NULL');
CALL AddColumnIfNotExists('program_csr', 'realisasi_budget', 'DECIMAL(15,2) DEFAULT 0.00');
CALL AddColumnIfNotExists('program_csr', 'progress', 'INT(11) DEFAULT 0');

-- Hapus stored procedure setelah digunakan
DROP PROCEDURE IF EXISTS AddColumnIfNotExists;

-- 7. Pastikan tabel users ada (untuk PIC)
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(255) DEFAULT NULL,
  `role` enum('admin','manager','staff') DEFAULT 'staff',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- VERIFIKASI
-- ============================================
SELECT 'Tabel program_csr' as Tabel, COUNT(*) as Jumlah FROM program_csr
UNION ALL
SELECT 'Tabel program_penyaluran', COUNT(*) FROM program_penyaluran
UNION ALL
SELECT 'Tabel program_dampak', COUNT(*) FROM program_dampak
UNION ALL
SELECT 'Tabel lokasi_strategis', COUNT(*) FROM lokasi_strategis
UNION ALL
SELECT 'Tabel program_lokasi', COUNT(*) FROM program_lokasi;

SELECT 'SUCCESS: Semua tabel sudah siap!' as Status;

