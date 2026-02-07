-- ============================================
-- FIX CSR_DONATIONS TABLE - Semua Kolom
-- Script untuk memastikan semua kolom ada di tabel csr_donations
-- Menggunakan IF NOT EXISTS untuk keamanan
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
        SELECT CONCAT('Column ', columnName, ' added successfully') AS message;
    ELSE
        SELECT CONCAT('Column ', columnName, ' already exists') AS message;
    END IF;
END$$

DELIMITER ;

-- Tambahkan kolom donatur_id jika belum ada
CALL AddColumnIfNotExists('csr_donations', 'donatur_id', 'int(11) DEFAULT NULL AFTER `id`');

-- Tambahkan index untuk donatur_id jika belum ada
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'csr_donations'
      AND INDEX_NAME = 'donatur_id'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE `csr_donations` ADD KEY `donatur_id` (`donatur_id`)',
    'SELECT "Index donatur_id already exists" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Tambahkan kolom nama_donatur jika belum ada
CALL AddColumnIfNotExists('csr_donations', 'nama_donatur', 'varchar(100) NOT NULL AFTER `donatur_id`');

-- Tambahkan kolom jumlah jika belum ada
CALL AddColumnIfNotExists('csr_donations', 'jumlah', 'decimal(15,2) NOT NULL AFTER `nama_donatur`');

-- Tambahkan kolom tanggal jika belum ada
CALL AddColumnIfNotExists('csr_donations', 'tanggal', 'date NOT NULL AFTER `jumlah`');

-- Tambahkan kolom metode_pembayaran jika belum ada
CALL AddColumnIfNotExists('csr_donations', 'metode_pembayaran', "enum('tunai','transfer','kartu','qris','lainnya') DEFAULT 'tunai' AFTER `tanggal`");

-- Tambahkan kolom kategori jika belum ada
CALL AddColumnIfNotExists('csr_donations', 'kategori', "enum('zakat','infaq','sedekah','wakaf','csr','donasi_umum') DEFAULT 'donasi_umum' AFTER `metode_pembayaran`");

-- Tambahkan kolom program jika belum ada
CALL AddColumnIfNotExists('csr_donations', 'program', 'varchar(100) DEFAULT NULL AFTER `kategori`');

-- Tambahkan kolom bukti_transfer jika belum ada
CALL AddColumnIfNotExists('csr_donations', 'bukti_transfer', 'varchar(255) DEFAULT NULL AFTER `program`');

-- Tambahkan kolom status jika belum ada
CALL AddColumnIfNotExists('csr_donations', 'status', "enum('pending','verified','rejected') DEFAULT 'pending' AFTER `bukti_transfer`");

-- Tambahkan kolom keterangan jika belum ada
CALL AddColumnIfNotExists('csr_donations', 'keterangan', 'text DEFAULT NULL AFTER `status`');

-- Tambahkan kolom created_by jika belum ada
CALL AddColumnIfNotExists('csr_donations', 'created_by', 'int(11) DEFAULT NULL AFTER `keterangan`');

-- Tambahkan index untuk created_by jika belum ada
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'csr_donations'
      AND INDEX_NAME = 'created_by'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE `csr_donations` ADD KEY `created_by` (`created_by`)',
    'SELECT "Index created_by already exists" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Tambahkan kolom created_at jika belum ada
CALL AddColumnIfNotExists('csr_donations', 'created_at', 'datetime DEFAULT CURRENT_TIMESTAMP AFTER `created_by`');

-- Tambahkan kolom updated_at jika belum ada
CALL AddColumnIfNotExists('csr_donations', 'updated_at', 'datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');

-- Tambahkan foreign key untuk donatur_id jika belum ada
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'csr_donations'
      AND COLUMN_NAME = 'donatur_id'
      AND REFERENCED_TABLE_NAME = 'donatur'
);
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE `csr_donations` ADD CONSTRAINT `fk_csr_donations_donatur` FOREIGN KEY (`donatur_id`) REFERENCES `donatur`(`id`) ON DELETE SET NULL',
    'SELECT "Foreign key donatur_id already exists" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Tambahkan foreign key untuk created_by jika belum ada
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'csr_donations'
      AND COLUMN_NAME = 'created_by'
      AND REFERENCED_TABLE_NAME = 'users'
);
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE `csr_donations` ADD CONSTRAINT `fk_csr_donations_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL',
    'SELECT "Foreign key created_by already exists" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Hapus stored procedure setelah digunakan
DROP PROCEDURE IF EXISTS AddColumnIfNotExists;

-- ============================================
-- Selesai
-- ============================================
SELECT 'All columns checked and added if needed' AS status;

