-- ============================================
-- FIX CSR_DONATIONS TABLE - Tambah Kolom donatur_id
-- Script untuk menambahkan kolom donatur_id jika belum ada
-- INSTRUKSI: 
-- 1. Jika ada error "Duplicate column", IGNORE saja (kolom sudah ada)
-- 2. Atau gunakan file fix_csr_donations_safe.sql yang lengkap
-- ============================================

-- Tambahkan kolom donatur_id (jika belum ada)
-- IGNORE jika error "Duplicate column name 'donatur_id'"
ALTER TABLE `csr_donations` ADD COLUMN `donatur_id` int(11) DEFAULT NULL AFTER `id`;

-- Tambahkan index untuk donatur_id (jika belum ada)
-- IGNORE jika error "Duplicate key name"
ALTER TABLE `csr_donations` ADD KEY `donatur_id` (`donatur_id`);

-- Tambahkan foreign key (jika belum ada)
-- IGNORE jika error "Duplicate foreign key"
-- Catatan: Jika error, hapus constraint yang sudah ada terlebih dahulu dengan:
-- ALTER TABLE `csr_donations` DROP FOREIGN KEY `csr_donations_ibfk_1`;
ALTER TABLE `csr_donations` 
ADD CONSTRAINT `fk_csr_donations_donatur` 
FOREIGN KEY (`donatur_id`) REFERENCES `donatur`(`id`) ON DELETE SET NULL;

