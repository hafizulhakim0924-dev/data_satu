-- ============================================
-- FIX CSR_DONATIONS TABLE
-- Script untuk menambahkan kolom yang diperlukan
-- INSTRUKSI: 
-- 1. Jalankan perintah satu per satu
-- 2. Jika ada error "Duplicate column", IGNORE saja (kolom sudah ada)
-- 3. Atau gunakan file fix_csr_donations_safe.sql untuk versi otomatis
-- ============================================

-- 1. Tambahkan kolom kategori jika belum ada (jika diperlukan)
-- IGNORE jika error "Duplicate column name 'kategori'"
ALTER TABLE `csr_donations` ADD COLUMN `kategori` enum('zakat','infaq','sedekah','wakaf','csr','donasi_umum') DEFAULT 'donasi_umum' AFTER `metode_pembayaran`;

-- 2. Tambahkan kolom program (jika belum ada)
-- IGNORE jika error "Duplicate column name 'program'"
ALTER TABLE `csr_donations` ADD COLUMN `program` varchar(100) DEFAULT NULL AFTER `kategori`;

