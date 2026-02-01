-- ============================================
-- FIX USERS TABLE - VERSI SEDERHANA
-- INSTRUKSI: 
-- 1. Jika ada error "Duplicate column", IGNORE saja (kolom sudah ada)
-- 2. Lanjutkan ke perintah berikutnya
-- 3. Atau gunakan file fix_users_table_safe.sql untuk versi otomatis
-- ============================================

-- 1. Tambahkan kolom email (jika belum ada)
-- IGNORE jika error "Duplicate column name 'email'"
ALTER TABLE `users` ADD COLUMN `email` varchar(100) DEFAULT NULL AFTER `username`;

-- 2. Tambahkan kolom password (jika belum ada)
ALTER TABLE `users` ADD COLUMN `password` varchar(255) DEFAULT NULL AFTER `email`;

-- 3. Tambahkan kolom nama_lengkap (jika belum ada)
ALTER TABLE `users` ADD COLUMN `nama_lengkap` varchar(100) DEFAULT NULL AFTER `password`;

-- 4. Tambahkan kolom nip (jika belum ada)
ALTER TABLE `users` ADD COLUMN `nip` varchar(50) DEFAULT NULL AFTER `nama_lengkap`;

-- 5. Tambahkan kolom jabatan (jika belum ada)
ALTER TABLE `users` ADD COLUMN `jabatan` varchar(100) DEFAULT NULL AFTER `nip`;

-- 6. Tambahkan kolom departemen (jika belum ada)
ALTER TABLE `users` ADD COLUMN `departemen` varchar(100) DEFAULT NULL AFTER `jabatan`;

-- 7. Tambahkan kolom no_hp (jika belum ada)
ALTER TABLE `users` ADD COLUMN `no_hp` varchar(20) DEFAULT NULL AFTER `departemen`;

-- 8. Tambahkan kolom alamat (jika belum ada)
ALTER TABLE `users` ADD COLUMN `alamat` text DEFAULT NULL AFTER `no_hp`;

-- 9. Tambahkan kolom foto (jika belum ada)
ALTER TABLE `users` ADD COLUMN `foto` varchar(255) DEFAULT NULL AFTER `alamat`;

-- 10. Tambahkan kolom role (jika belum ada)
ALTER TABLE `users` ADD COLUMN `role` enum('admin','manager','staff','volunteer') DEFAULT 'staff' AFTER `foto`;

-- 11. Tambahkan kolom status (jika belum ada)
ALTER TABLE `users` ADD COLUMN `status` enum('active','inactive') DEFAULT 'active' AFTER `role`;

-- 12. Tambahkan kolom created_at (jika belum ada)
ALTER TABLE `users` ADD COLUMN `created_at` datetime DEFAULT CURRENT_TIMESTAMP AFTER `status`;

-- 13. Tambahkan kolom updated_at (jika belum ada)
ALTER TABLE `users` ADD COLUMN `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- 14. Tambahkan UNIQUE constraint untuk email (jika belum ada)
ALTER TABLE `users` ADD UNIQUE KEY `users_email_unique` (`email`);

-- 15. Insert/Update admin default
INSERT INTO `users` (`username`, `email`, `password`, `nama_lengkap`, `role`, `status`) 
VALUES ('admin', 'admin@rangkiangpedulinegeri.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 'active')
ON DUPLICATE KEY UPDATE 
    `email` = VALUES(`email`),
    `password` = VALUES(`password`),
    `nama_lengkap` = VALUES(`nama_lengkap`),
    `role` = VALUES(`role`),
    `status` = VALUES(`status`);

