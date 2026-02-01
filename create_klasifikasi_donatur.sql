-- ============================================
-- TABEL KLASIFIKASI DONATUR
-- Untuk mengelompokkan donatur dengan warna dan indikator
-- ============================================

CREATE TABLE IF NOT EXISTS `klasifikasi_donatur` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_klasifikasi` varchar(100) NOT NULL,
  `kode` varchar(50) DEFAULT NULL,
  `warna` varchar(20) DEFAULT '#3498db' COMMENT 'Hex color code',
  `indikator` varchar(50) DEFAULT NULL COMMENT 'Icon/emoji untuk indikator',
  `deskripsi` text DEFAULT NULL,
  `minimal_donasi` decimal(15,2) DEFAULT NULL COMMENT 'Minimal total donasi untuk masuk klasifikasi ini',
  `prioritas` int(11) DEFAULT 0 COMMENT 'Urutan prioritas (0 = terendah)',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tambahkan kolom klasifikasi_id di tabel donatur (jika belum ada)
-- IGNORE jika error "Duplicate column name 'klasifikasi_id'"
ALTER TABLE `donatur` ADD COLUMN `klasifikasi_id` int(11) DEFAULT NULL AFTER `kategori`;

-- Tambahkan foreign key (jika belum ada)
-- IGNORE jika error "Duplicate foreign key constraint"
-- Catatan: Jika error, hapus constraint yang sudah ada terlebih dahulu dengan:
-- ALTER TABLE `donatur` DROP FOREIGN KEY `fk_donatur_klasifikasi`;
ALTER TABLE `donatur` 
ADD CONSTRAINT `fk_donatur_klasifikasi` 
FOREIGN KEY (`klasifikasi_id`) REFERENCES `klasifikasi_donatur`(`id`) ON DELETE SET NULL;

-- Insert data klasifikasi default
INSERT INTO `klasifikasi_donatur` (`nama_klasifikasi`, `kode`, `warna`, `indikator`, `deskripsi`, `minimal_donasi`, `prioritas`) VALUES
('Donatur Utama', 'UTAMA', '#e74c3c', '⭐', 'Donatur dengan kontribusi sangat besar', 100000000, 5),
('Donatur Besar', 'BESAR', '#f39c12', '💎', 'Donatur dengan kontribusi besar', 50000000, 4),
('Donatur Menengah', 'MENENGAH', '#3498db', '💵', 'Donatur dengan kontribusi menengah', 10000000, 3),
('Donatur Kecil', 'KECIL', '#27ae60', '💰', 'Donatur dengan kontribusi kecil', 1000000, 2),
('Donatur Baru', 'BARU', '#95a5a6', '🆕', 'Donatur baru yang belum banyak berkontribusi', 0, 1),
('Donatur Rutin', 'RUTIN', '#9b59b6', '🔄', 'Donatur yang rutin memberikan donasi', NULL, 3),
('Donatur Sporadis', 'SPORADIS', '#34495e', '📅', 'Donatur yang memberikan donasi tidak teratur', NULL, 2);

