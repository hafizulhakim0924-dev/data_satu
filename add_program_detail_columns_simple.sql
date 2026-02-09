-- SQL Script SEDERHANA untuk menambahkan kolom detail program
-- Versi ini tidak menggunakan stored procedure atau INFORMATION_SCHEMA
-- Jika ada error "Duplicate column name", berarti kolom sudah ada dan bisa diabaikan

-- Tambahkan kolom-kolom detail program satu per satu
-- Jika kolom sudah ada, akan muncul error "Duplicate column name" - IGNORE error tersebut

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

-- Untuk melihat struktur tabel setelah menambahkan kolom:
-- DESCRIBE program_csr;

