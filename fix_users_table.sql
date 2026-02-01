-- ============================================
-- FIX USERS TABLE
-- Script ini akan menambahkan kolom yang belum ada di tabel users
-- Jalankan perintah satu per satu jika ada error
-- ============================================

-- Cek dan tambahkan kolom email (jika belum ada)
SET @dbname = DATABASE();
SET @tablename = 'users';
SET @columnname = 'email';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT "Column email already exists"',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname, '` varchar(100) DEFAULT NULL AFTER `username`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Cek dan tambahkan kolom password (jika belum ada)
SET @columnname = 'password';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT "Column password already exists"',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname, '` varchar(255) DEFAULT NULL AFTER `email`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Cek dan tambahkan kolom nama_lengkap (jika belum ada)
SET @columnname = 'nama_lengkap';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT "Column nama_lengkap already exists"',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname, '` varchar(100) DEFAULT NULL AFTER `password`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Cek dan tambahkan kolom lainnya
SET @columnname = 'nip';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT "Column nip already exists"',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname, '` varchar(50) DEFAULT NULL AFTER `nama_lengkap`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'jabatan';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT "Column jabatan already exists"',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname, '` varchar(100) DEFAULT NULL AFTER `nip`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'departemen';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT "Column departemen already exists"',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname, '` varchar(100) DEFAULT NULL AFTER `jabatan`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'no_hp';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT "Column no_hp already exists"',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname, '` varchar(20) DEFAULT NULL AFTER `departemen`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'alamat';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT "Column alamat already exists"',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname, '` text DEFAULT NULL AFTER `no_hp`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'foto';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT "Column foto already exists"',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname, '` varchar(255) DEFAULT NULL AFTER `alamat`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'role';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT "Column role already exists"',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname, '` enum(\'admin\',\'manager\',\'staff\',\'volunteer\') DEFAULT \'staff\' AFTER `foto`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'status';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT "Column status already exists"',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname, '` enum(\'active\',\'inactive\') DEFAULT \'active\' AFTER `role`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'created_at';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT "Column created_at already exists"',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname, '` datetime DEFAULT CURRENT_TIMESTAMP AFTER `status`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'updated_at';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT "Column updated_at already exists"',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname, '` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Tambahkan UNIQUE constraint untuk email (jika belum ada)
SET @constraint_name = 'users_email_unique';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (constraint_name = @constraint_name)
  ) > 0,
  'SELECT "Constraint already exists"',
  CONCAT('ALTER TABLE ', @tablename, ' ADD UNIQUE KEY `', @constraint_name, '` (`email`)')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Insert/Update admin default
INSERT INTO `users` (`username`, `email`, `password`, `nama_lengkap`, `role`, `status`) 
VALUES ('admin', 'admin@rangkiangpedulinegeri.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 'active')
ON DUPLICATE KEY UPDATE 
    `email` = VALUES(`email`),
    `password` = VALUES(`password`),
    `nama_lengkap` = VALUES(`nama_lengkap`),
    `role` = VALUES(`role`),
    `status` = VALUES(`status`);

