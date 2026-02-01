-- ============================================
-- FIX USERS TABLE - VERSI AMAN (Auto Skip)
-- Script ini akan otomatis melewati kolom yang sudah ada
-- Jalankan seluruh script ini sekaligus
-- ============================================

-- Fungsi helper untuk menambahkan kolom jika belum ada
-- (MySQL tidak support IF NOT EXISTS untuk ALTER TABLE, jadi kita gunakan stored procedure)

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

-- Tambahkan kolom-kolom yang diperlukan
CALL AddColumnIfNotExists('users', 'email', 'varchar(100) DEFAULT NULL AFTER `username`');
CALL AddColumnIfNotExists('users', 'password', 'varchar(255) DEFAULT NULL AFTER `email`');
CALL AddColumnIfNotExists('users', 'nama_lengkap', 'varchar(100) DEFAULT NULL AFTER `password`');
CALL AddColumnIfNotExists('users', 'nip', 'varchar(50) DEFAULT NULL AFTER `nama_lengkap`');
CALL AddColumnIfNotExists('users', 'jabatan', 'varchar(100) DEFAULT NULL AFTER `nip`');
CALL AddColumnIfNotExists('users', 'departemen', 'varchar(100) DEFAULT NULL AFTER `jabatan`');
CALL AddColumnIfNotExists('users', 'no_hp', 'varchar(20) DEFAULT NULL AFTER `departemen`');
CALL AddColumnIfNotExists('users', 'alamat', 'text DEFAULT NULL AFTER `no_hp`');
CALL AddColumnIfNotExists('users', 'foto', 'varchar(255) DEFAULT NULL AFTER `alamat`');
CALL AddColumnIfNotExists('users', 'role', 'enum(\'admin\',\'manager\',\'staff\',\'volunteer\') DEFAULT \'staff\' AFTER `foto`');
CALL AddColumnIfNotExists('users', 'status', 'enum(\'active\',\'inactive\') DEFAULT \'active\' AFTER `role`');
CALL AddColumnIfNotExists('users', 'created_at', 'datetime DEFAULT CURRENT_TIMESTAMP AFTER `status`');
CALL AddColumnIfNotExists('users', 'updated_at', 'datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');

-- Tambahkan UNIQUE constraint untuk email (jika belum ada)
SET @constraint_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND CONSTRAINT_NAME = 'users_email_unique'
);

SET @sql = IF(@constraint_exists = 0,
    'ALTER TABLE `users` ADD UNIQUE KEY `users_email_unique` (`email`)',
    'SELECT "Constraint already exists" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Hapus stored procedure setelah digunakan
DROP PROCEDURE IF EXISTS AddColumnIfNotExists;

-- Insert/Update admin default
INSERT INTO `users` (`username`, `email`, `password`, `nama_lengkap`, `role`, `status`) 
VALUES ('admin', 'admin@rangkiangpedulinegeri.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 'active')
ON DUPLICATE KEY UPDATE 
    `email` = VALUES(`email`),
    `password` = VALUES(`password`),
    `nama_lengkap` = VALUES(`nama_lengkap`),
    `role` = VALUES(`role`),
    `status` = VALUES(`status`);

