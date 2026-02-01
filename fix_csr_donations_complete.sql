-- ============================================
-- FIX CSR_DONATIONS TABLE - LENGKAP
-- Script untuk menambahkan semua kolom yang diperlukan
-- Jalankan perintah ini satu per satu, skip jika error "Duplicate column"
-- ============================================

-- 1. Tambahkan kolom kategori (jika belum ada)
-- IGNORE jika error "Duplicate column name 'kategori'"
ALTER TABLE `csr_donations` ADD COLUMN `kategori` enum('zakat','infaq','sedekah','wakaf','csr','donasi_umum') DEFAULT 'donasi_umum';

-- 2. Tambahkan kolom program (jika belum ada)
-- IGNORE jika error "Duplicate column name 'program'"
ALTER TABLE `csr_donations` ADD COLUMN `program` varchar(100) DEFAULT NULL;

-- 3. Tambahkan kolom bukti_transfer (jika belum ada)
ALTER TABLE `csr_donations` ADD COLUMN `bukti_transfer` varchar(255) DEFAULT NULL;

-- 4. Tambahkan kolom status (jika belum ada)
ALTER TABLE `csr_donations` ADD COLUMN `status` enum('pending','verified','rejected') DEFAULT 'pending';

-- 5. Tambahkan kolom keterangan (jika belum ada)
ALTER TABLE `csr_donations` ADD COLUMN `keterangan` text DEFAULT NULL;

-- 6. Tambahkan kolom created_by (jika belum ada)
ALTER TABLE `csr_donations` ADD COLUMN `created_by` int(11) DEFAULT NULL;

-- 7. Tambahkan kolom created_at (jika belum ada)
ALTER TABLE `csr_donations` ADD COLUMN `created_at` datetime DEFAULT CURRENT_TIMESTAMP;

-- 8. Tambahkan kolom updated_at (jika belum ada)
ALTER TABLE `csr_donations` ADD COLUMN `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

