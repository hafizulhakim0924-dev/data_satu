-- ============================================
-- FIX DONATUR TABLE - Tambah Kolom Klasifikasi
-- Script untuk menambahkan kolom klasifikasi_id jika belum ada
-- INSTRUKSI: 
-- 1. Jika ada error "Duplicate column", IGNORE saja (kolom sudah ada)
-- 2. Atau gunakan file create_klasifikasi_donatur.sql yang lengkap
-- ============================================

-- Tambahkan kolom klasifikasi_id (jika belum ada)
-- IGNORE jika error "Duplicate column name 'klasifikasi_id'"
ALTER TABLE `donatur` ADD COLUMN `klasifikasi_id` int(11) DEFAULT NULL AFTER `kategori`;

-- Tambahkan foreign key (jika belum ada)
-- IGNORE jika error "Duplicate foreign key"
ALTER TABLE `donatur` 
ADD CONSTRAINT `fk_donatur_klasifikasi` 
FOREIGN KEY (`klasifikasi_id`) REFERENCES `klasifikasi_donatur`(`id`) ON DELETE SET NULL;

