-- SQL Script untuk menambahkan kolom detail program ke tabel program_csr
-- JALANKAN SCRIPT INI HANYA JIKA TABEL program_csr SUDAH ADA
-- Jika tabel belum ada, jalankan: create_program_csr_with_details.sql

-- Cek apakah tabel ada terlebih dahulu
-- Jika error "Table doesn't exist", jalankan create_program_csr_with_details.sql

-- Tambahkan kolom-kolom detail program
-- Jika kolom sudah ada, akan muncul error "Duplicate column name" yang bisa diabaikan

ALTER TABLE `program_csr` 
ADD COLUMN `kecamatan` varchar(100) DEFAULT NULL AFTER `lokasi`;

ALTER TABLE `program_csr` 
ADD COLUMN `kota` varchar(100) DEFAULT NULL AFTER `kecamatan`;

ALTER TABLE `program_csr` 
ADD COLUMN `provinsi` varchar(100) DEFAULT NULL AFTER `kota`;

ALTER TABLE `program_csr` 
ADD COLUMN `jenis_bantuan` varchar(255) DEFAULT NULL AFTER `kategori`;

ALTER TABLE `program_csr` 
ADD COLUMN `jumlah_bantuan` decimal(15,2) DEFAULT 0.00 AFTER `jenis_bantuan`;

ALTER TABLE `program_csr` 
ADD COLUMN `satuan` varchar(50) DEFAULT NULL AFTER `jumlah_bantuan`;

ALTER TABLE `program_csr` 
ADD COLUMN `jumlah_penerima_manfaat` int(11) DEFAULT 0 AFTER `satuan`;

ALTER TABLE `program_csr` 
ADD COLUMN `jumlah_relawan_terlibat` int(11) DEFAULT 0 AFTER `jumlah_penerima_manfaat`;

-- Verifikasi kolom yang telah ditambahkan
-- Gunakan query ini untuk melihat struktur tabel
DESCRIBE program_csr;
