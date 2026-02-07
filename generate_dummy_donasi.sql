-- ============================================
-- GENERATE DUMMY DONASI (STORED PROCEDURE)
-- Script untuk membuat data dummy donasi yang terkait dengan donatur
-- 
-- CATATAN PENTING:
-- 1. Script ini menggunakan STORED PROCEDURE
-- 2. Jika menggunakan phpMyAdmin, pastikan:
--    - Pilih tab "SQL" 
--    - Copy SEMUA isi file ini (termasuk DELIMITER)
--    - Paste dan jalankan sekaligus
-- 3. Jika masih error, gunakan file: generate_dummy_donasi_simple.sql
--    (versi tanpa stored procedure, lebih mudah dijalankan)
-- ============================================

-- Hapus data dummy sebelumnya (opsional, comment jika tidak ingin menghapus)
-- DELETE FROM csr_donations WHERE nama_donatur LIKE '%dummy%' OR keterangan LIKE '%Dummy%';

DELIMITER $$

DROP PROCEDURE IF EXISTS GenerateDummyDonasi$$

CREATE PROCEDURE GenerateDummyDonasi(IN jumlah_donasi INT)
proc_label: BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE donatur_id_val INT;
    DECLARE nama_donatur_val VARCHAR(100);
    DECLARE jumlah_donasi_val DECIMAL(15,2);
    DECLARE tanggal_donasi DATE;
    DECLARE metode_pembayaran_val ENUM('tunai','transfer','kartu','qris','lainnya');
    DECLARE kategori_donasi_val ENUM('zakat','infaq','sedekah','wakaf','csr','donasi_umum');
    DECLARE program_val VARCHAR(100);
    DECLARE status_val ENUM('pending','verified','rejected');
    DECLARE keterangan_val TEXT;
    DECLARE rand_val DOUBLE;
    DECLARE total_donatur INT;
    DECLARE jumlah_per_donatur INT;
    DECLARE donatur_counter INT DEFAULT 1;
    DECLARE j INT DEFAULT 1;
    
    -- Hitung total donatur yang ada
    SELECT COUNT(*) INTO total_donatur FROM donatur WHERE status = 'active';
    
    IF total_donatur = 0 THEN
        SELECT 'Tidak ada donatur aktif. Silakan generate donatur terlebih dahulu.' AS pesan;
        LEAVE proc_label;
    END IF;
    
    -- Hitung rata-rata jumlah donasi per donatur
    SET jumlah_per_donatur = FLOOR(jumlah_donasi / total_donatur);
    IF jumlah_per_donatur < 1 THEN
        SET jumlah_per_donatur = 1;
    END IF;
    
    -- Loop untuk setiap donatur
    WHILE donatur_counter <= total_donatur AND i <= jumlah_donasi DO
        -- Ambil donatur berdasarkan counter
        SELECT id, nama INTO donatur_id_val, nama_donatur_val
        FROM donatur 
        WHERE status = 'active'
        ORDER BY id
        LIMIT 1 OFFSET (donatur_counter - 1);
        
        -- Generate beberapa donasi untuk donatur ini
        SET j = 1;
        WHILE j <= jumlah_per_donatur AND i <= jumlah_donasi DO
            -- Generate jumlah donasi (antara 10.000 - 50.000.000)
            SET rand_val = RAND();
            SET jumlah_donasi_val = CASE 
                WHEN rand_val < 0.3 THEN FLOOR(10000 + RAND() * 990000) -- 10rb - 1jt (30%)
                WHEN rand_val < 0.6 THEN FLOOR(1000000 + RAND() * 9000000) -- 1jt - 10jt (30%)
                WHEN rand_val < 0.85 THEN FLOOR(10000000 + RAND() * 40000000) -- 10jt - 50jt (25%)
                ELSE FLOOR(50000000 + RAND() * 50000000) -- 50jt - 100jt (15%)
            END;
            
            -- Generate tanggal (dalam 2 tahun terakhir)
            SET tanggal_donasi = DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND() * 730) DAY);
            
            -- Generate metode pembayaran
            SET rand_val = RAND();
            SET metode_pembayaran_val = CASE 
                WHEN rand_val < 0.3 THEN 'tunai'
                WHEN rand_val < 0.6 THEN 'transfer'
                WHEN rand_val < 0.8 THEN 'qris'
                WHEN rand_val < 0.95 THEN 'kartu'
                ELSE 'lainnya'
            END;
            
            -- Generate kategori
            SET rand_val = RAND();
            SET kategori_donasi_val = CASE 
                WHEN rand_val < 0.2 THEN 'zakat'
                WHEN rand_val < 0.35 THEN 'infaq'
                WHEN rand_val < 0.5 THEN 'sedekah'
                WHEN rand_val < 0.65 THEN 'donasi_umum'
                WHEN rand_val < 0.8 THEN 'csr'
                ELSE 'wakaf'
            END;
            
            -- Generate program (opsional, 60% punya program)
            SET rand_val = RAND();
            IF rand_val < 0.6 THEN
                SET program_val = ELT(1 + FLOOR(RAND() * 10),
                    'Program Pendidikan Anak Yatim',
                    'Program Bantuan Pangan',
                    'Program Kesehatan Masyarakat',
                    'Program Bencana Alam',
                    'Program Infrastruktur Desa',
                    'Program Beasiswa',
                    'Program Kesejahteraan Lansia',
                    'Program Lingkungan Hidup',
                    'Program Ekonomi Kreatif',
                    'Program Dakwah');
            ELSE
                SET program_val = NULL;
            END IF;
            
            -- Generate status (80% verified, 15% pending, 5% rejected)
            SET rand_val = RAND();
            SET status_val = CASE 
                WHEN rand_val < 0.8 THEN 'verified'
                WHEN rand_val < 0.95 THEN 'pending'
                ELSE 'rejected'
            END;
            
            -- Generate keterangan
            SET keterangan_val = CONCAT('Dummy donasi ke-', i, ' - Generated by script');
            
            -- Insert donasi
            INSERT INTO `csr_donations` (
                `donatur_id`, 
                `nama_donatur`, 
                `jumlah`, 
                `tanggal`, 
                `metode_pembayaran`, 
                `kategori`, 
                `program`, 
                `status`, 
                `keterangan`, 
                `created_at`
            )
            VALUES (
                donatur_id_val,
                nama_donatur_val,
                jumlah_donasi_val,
                tanggal_donasi,
                metode_pembayaran_val,
                kategori_donasi_val,
                program_val,
                status_val,
                keterangan_val,
                NOW() - INTERVAL FLOOR(RAND() * 730) DAY
            );
            
            SET j = j + 1;
            SET i = i + 1;
        END WHILE;
        
        SET donatur_counter = donatur_counter + 1;
    END WHILE;
    
    -- Generate donasi tambahan untuk beberapa donatur (random)
    WHILE i <= jumlah_donasi DO
        -- Pilih donatur random
        SELECT id, nama INTO donatur_id_val, nama_donatur_val
        FROM donatur 
        WHERE status = 'active'
        ORDER BY RAND()
        LIMIT 1;
        
        -- Generate jumlah donasi
        SET rand_val = RAND();
        SET jumlah_donasi_val = CASE 
            WHEN rand_val < 0.3 THEN FLOOR(10000 + RAND() * 990000)
            WHEN rand_val < 0.6 THEN FLOOR(1000000 + RAND() * 9000000)
            WHEN rand_val < 0.85 THEN FLOOR(10000000 + RAND() * 40000000)
            ELSE FLOOR(50000000 + RAND() * 50000000)
        END;
        
        SET tanggal_donasi = DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND() * 730) DAY);
        
        SET rand_val = RAND();
        SET metode_pembayaran_val = CASE 
            WHEN rand_val < 0.3 THEN 'tunai'
            WHEN rand_val < 0.6 THEN 'transfer'
            WHEN rand_val < 0.8 THEN 'qris'
            WHEN rand_val < 0.95 THEN 'kartu'
            ELSE 'lainnya'
        END;
        
        SET rand_val = RAND();
        SET kategori_donasi_val = CASE 
            WHEN rand_val < 0.2 THEN 'zakat'
            WHEN rand_val < 0.35 THEN 'infaq'
            WHEN rand_val < 0.5 THEN 'sedekah'
            WHEN rand_val < 0.65 THEN 'donasi_umum'
            WHEN rand_val < 0.8 THEN 'csr'
            ELSE 'wakaf'
        END;
        
        SET rand_val = RAND();
        IF rand_val < 0.6 THEN
            SET program_val = ELT(1 + FLOOR(RAND() * 10),
                'Program Pendidikan Anak Yatim',
                'Program Bantuan Pangan',
                'Program Kesehatan Masyarakat',
                'Program Bencana Alam',
                'Program Infrastruktur Desa',
                'Program Beasiswa',
                'Program Kesejahteraan Lansia',
                'Program Lingkungan Hidup',
                'Program Ekonomi Kreatif',
                'Program Dakwah');
        ELSE
            SET program_val = NULL;
        END IF;
        
        SET rand_val = RAND();
        SET status_val = CASE 
            WHEN rand_val < 0.8 THEN 'verified'
            WHEN rand_val < 0.95 THEN 'pending'
            ELSE 'rejected'
        END;
        
        SET keterangan_val = CONCAT('Dummy donasi ke-', i, ' - Generated by script');
        
        INSERT INTO `csr_donations` (
            `donatur_id`, 
            `nama_donatur`, 
            `jumlah`, 
            `tanggal`, 
            `metode_pembayaran`, 
            `kategori`, 
            `program`, 
            `status`, 
            `keterangan`, 
            `created_at`
        )
        VALUES (
            donatur_id_val,
            nama_donatur_val,
            jumlah_donasi_val,
            tanggal_donasi,
            metode_pembayaran_val,
            kategori_donasi_val,
            program_val,
            status_val,
            keterangan_val,
            NOW() - INTERVAL FLOOR(RAND() * 730) DAY
        );
        
        SET i = i + 1;
    END WHILE;
    
    SELECT CONCAT('✅ Berhasil generate ', jumlah_donasi, ' dummy donasi') AS hasil;
END$$

DELIMITER ;

-- Jalankan stored procedure untuk generate 1000 dummy donasi
-- (Sesuaikan jumlah sesuai kebutuhan)
CALL GenerateDummyDonasi(1000);

-- Hapus stored procedure setelah digunakan
DROP PROCEDURE IF EXISTS GenerateDummyDonasi;

-- ============================================
-- VERIFIKASI DATA
-- ============================================
SELECT 
    COUNT(*) AS total_donasi,
    SUM(jumlah) AS total_nominal,
    COUNT(CASE WHEN status = 'verified' THEN 1 END) AS verified,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) AS pending,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) AS rejected,
    COUNT(DISTINCT donatur_id) AS total_donatur_berdonasi
FROM csr_donations;

-- Top 10 donatur berdasarkan total donasi
SELECT 
    d.nama,
    COUNT(ds.id) AS jumlah_donasi,
    SUM(ds.jumlah) AS total_donasi
FROM donatur d
LEFT JOIN csr_donations ds ON d.id = ds.donatur_id
GROUP BY d.id, d.nama
ORDER BY total_donasi DESC
LIMIT 10;

-- ============================================
-- SELESAI
-- ============================================

