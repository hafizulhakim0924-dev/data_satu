-- ============================================
-- FIX CSR_DONATIONS TABLE - VERSI AMAN
-- Script ini akan otomatis menambahkan kolom jika belum ada
-- ============================================

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

-- Tambahkan kolom kategori jika belum ada
CALL AddColumnIfNotExists('csr_donations', 'kategori', 'enum(\'zakat\',\'infaq\',\'sedekah\',\'wakaf\',\'csr\',\'donasi_umum\') DEFAULT \'donasi_umum\' AFTER `metode_pembayaran`');

-- Tambahkan kolom program jika belum ada
CALL AddColumnIfNotExists('csr_donations', 'program', 'varchar(100) DEFAULT NULL AFTER `kategori`');

-- Hapus stored procedure setelah digunakan
DROP PROCEDURE IF EXISTS AddColumnIfNotExists;

