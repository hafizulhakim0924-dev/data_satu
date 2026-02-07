-- ============================================
-- GENERATE 500 DUMMY DONATUR
-- Script untuk membuat 500 data dummy donatur
-- Menggunakan stored procedure untuk generate data secara otomatis
-- ============================================

-- Hapus data dummy sebelumnya (opsional, comment jika tidak ingin menghapus)
-- DELETE FROM donatur WHERE email LIKE '%dummy%' OR nama LIKE '%Dummy%';

DELIMITER $$

DROP PROCEDURE IF EXISTS GenerateDummyDonatur$$

CREATE PROCEDURE GenerateDummyDonatur(IN jumlah INT)
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE nama_donatur VARCHAR(100);
    DECLARE email_donatur VARCHAR(100);
    DECLARE no_hp_donatur VARCHAR(20);
    DECLARE alamat_donatur TEXT;
    DECLARE tipe_donatur ENUM('individu','perusahaan','yayasan','lembaga');
    DECLARE kategori_donatur ENUM('rutin','sporadis','corporate','zakat','infaq','sedekah');
    DECLARE npwp_donatur VARCHAR(50);
    DECLARE nama_perusahaan_donatur VARCHAR(100);
    DECLARE pic_donatur VARCHAR(100);
    DECLARE rand_val DOUBLE;
    
    -- Array nama depan dan belakang
    DECLARE nama_depan VARCHAR(50);
    DECLARE nama_belakang VARCHAR(50);
    
    WHILE i <= jumlah DO
        -- Generate nama depan
        SET rand_val = RAND();
        SET nama_depan = CASE 
            WHEN rand_val < 0.05 THEN 'Ahmad'
            WHEN rand_val < 0.10 THEN 'Siti'
            WHEN rand_val < 0.15 THEN 'Budi'
            WHEN rand_val < 0.20 THEN 'Rina'
            WHEN rand_val < 0.25 THEN 'Dedi'
            WHEN rand_val < 0.30 THEN 'Maya'
            WHEN rand_val < 0.35 THEN 'Eko'
            WHEN rand_val < 0.40 THEN 'Lina'
            WHEN rand_val < 0.45 THEN 'Fajar'
            WHEN rand_val < 0.50 THEN 'Nurul'
            WHEN rand_val < 0.55 THEN 'Hendra'
            WHEN rand_val < 0.60 THEN 'Sari'
            WHEN rand_val < 0.65 THEN 'Agus'
            WHEN rand_val < 0.70 THEN 'Dewi'
            WHEN rand_val < 0.75 THEN 'Rudi'
            WHEN rand_val < 0.80 THEN 'Indah'
            WHEN rand_val < 0.85 THEN 'Joko'
            WHEN rand_val < 0.90 THEN 'Sinta'
            WHEN rand_val < 0.95 THEN 'Ari'
            ELSE 'Yuni'
        END;
        
        -- Generate nama belakang
        SET rand_val = RAND();
        SET nama_belakang = CASE 
            WHEN rand_val < 0.05 THEN 'Fauzi'
            WHEN rand_val < 0.10 THEN 'Nurhaliza'
            WHEN rand_val < 0.15 THEN 'Santoso'
            WHEN rand_val < 0.20 THEN 'Wati'
            WHEN rand_val < 0.25 THEN 'Kurniawan'
            WHEN rand_val < 0.30 THEN 'Sari'
            WHEN rand_val < 0.35 THEN 'Prasetyo'
            WHEN rand_val < 0.40 THEN 'Marlina'
            WHEN rand_val < 0.45 THEN 'Nugroho'
            WHEN rand_val < 0.50 THEN 'Hikmah'
            WHEN rand_val < 0.55 THEN 'Wijaya'
            WHEN rand_val < 0.60 THEN 'Kusuma'
            WHEN rand_val < 0.65 THEN 'Setiawan'
            WHEN rand_val < 0.70 THEN 'Putri'
            WHEN rand_val < 0.75 THEN 'Rahman'
            WHEN rand_val < 0.80 THEN 'Lestari'
            WHEN rand_val < 0.85 THEN 'Saputra'
            WHEN rand_val < 0.90 THEN 'Purnama'
            WHEN rand_val < 0.95 THEN 'Siregar'
            ELSE 'Nasution'
        END;
        
        SET nama_donatur = CONCAT(nama_depan, ' ', nama_belakang);
        
        -- Generate email
        SET email_donatur = CONCAT(LOWER(REPLACE(nama_donatur, ' ', '.')), '.dummy', i, '@email.com');
        
        -- Generate no HP
        SET no_hp_donatur = CONCAT('08', LPAD(FLOOR(100000000 + RAND() * 900000000), 9, '0'));
        
        -- Generate alamat
        SET rand_val = RAND();
        SET alamat_donatur = CONCAT('Jl. ', 
            CASE 
                WHEN rand_val < 0.1 THEN 'Sudirman'
                WHEN rand_val < 0.2 THEN 'Ahmad Yani'
                WHEN rand_val < 0.3 THEN 'Merdeka'
                WHEN rand_val < 0.4 THEN 'Diponegoro'
                WHEN rand_val < 0.5 THEN 'Gatot Subroto'
                WHEN rand_val < 0.6 THEN 'Imam Bonjol'
                WHEN rand_val < 0.7 THEN 'Thamrin'
                WHEN rand_val < 0.8 THEN 'Hayam Wuruk'
                WHEN rand_val < 0.9 THEN 'Juanda'
                ELSE 'Kartini'
            END,
            ' No. ', FLOOR(1 + RAND() * 200), ', ',
            CASE 
                WHEN rand_val < 0.1 THEN 'Padang'
                WHEN rand_val < 0.2 THEN 'Bukittinggi'
                WHEN rand_val < 0.3 THEN 'Solok'
                WHEN rand_val < 0.4 THEN 'Pariaman'
                WHEN rand_val < 0.5 THEN 'Payakumbuh'
                WHEN rand_val < 0.6 THEN 'Sawahlunto'
                WHEN rand_val < 0.7 THEN 'Padang Panjang'
                WHEN rand_val < 0.8 THEN 'Lubuk Sikaping'
                WHEN rand_val < 0.9 THEN 'Batusangkar'
                ELSE 'Sijunjung'
            END,
            ', Sumatera Barat');
        
        -- Generate tipe (70% individu, 20% perusahaan, 5% yayasan, 5% lembaga)
        SET rand_val = RAND();
        SET tipe_donatur = CASE 
            WHEN rand_val < 0.70 THEN 'individu'
            WHEN rand_val < 0.90 THEN 'perusahaan'
            WHEN rand_val < 0.95 THEN 'yayasan'
            ELSE 'lembaga'
        END;
        
        -- Generate kategori
        SET rand_val = RAND();
        SET kategori_donatur = CASE 
            WHEN rand_val < 0.2 THEN 'rutin'
            WHEN rand_val < 0.35 THEN 'sporadis'
            WHEN rand_val < 0.45 THEN 'corporate'
            WHEN rand_val < 0.6 THEN 'zakat'
            WHEN rand_val < 0.75 THEN 'infaq'
            ELSE 'sedekah'
        END;
        
        -- Generate NPWP untuk perusahaan/yayasan/lembaga
        IF tipe_donatur != 'individu' THEN
            SET npwp_donatur = CONCAT('12.', 
                LPAD(FLOOR(100 + RAND() * 900), 3, '0'), '.', 
                LPAD(FLOOR(100 + RAND() * 900), 3, '0'), '.', 
                LPAD(FLOOR(100 + RAND() * 900), 3, '0'), '-', 
                LPAD(FLOOR(100 + RAND() * 900), 3, '0'), '.', 
                LPAD(FLOOR(10 + RAND() * 90), 2, '0'));
            
            IF tipe_donatur = 'perusahaan' THEN
                SET nama_perusahaan_donatur = CONCAT('PT ', nama_donatur, ' Sejahtera');
            ELSEIF tipe_donatur = 'yayasan' THEN
                SET nama_perusahaan_donatur = CONCAT('Yayasan ', nama_donatur);
            ELSE
                SET nama_perusahaan_donatur = CONCAT('Lembaga ', nama_donatur);
            END IF;
            
            SET pic_donatur = nama_donatur;
        ELSE
            SET npwp_donatur = NULL;
            SET nama_perusahaan_donatur = NULL;
            SET pic_donatur = NULL;
        END IF;
        
        -- Insert data
        INSERT INTO `donatur` (`nama`, `email`, `no_hp`, `alamat`, `tipe`, `npwp`, `nama_perusahaan`, `pic`, `kategori`, `status`, `catatan`, `created_at`)
        VALUES (
            nama_donatur,
            email_donatur,
            no_hp_donatur,
            alamat_donatur,
            tipe_donatur,
            npwp_donatur,
            nama_perusahaan_donatur,
            pic_donatur,
            kategori_donatur,
            'active',
            CONCAT('Dummy donatur ke-', i, ' - Generated by script'),
            NOW() - INTERVAL FLOOR(RAND() * 730) DAY
        );
        
        SET i = i + 1;
    END WHILE;
    
    SELECT CONCAT('✅ Berhasil generate ', jumlah, ' dummy donatur') AS hasil;
END$$

DELIMITER ;

-- Jalankan stored procedure untuk generate 500 dummy donatur
CALL GenerateDummyDonatur(500);

-- Hapus stored procedure setelah digunakan
DROP PROCEDURE IF EXISTS GenerateDummyDonatur;

-- ============================================
-- VERIFIKASI DATA
-- ============================================
SELECT 
    COUNT(*) AS total_donatur,
    COUNT(CASE WHEN tipe = 'individu' THEN 1 END) AS individu,
    COUNT(CASE WHEN tipe = 'perusahaan' THEN 1 END) AS perusahaan,
    COUNT(CASE WHEN tipe = 'yayasan' THEN 1 END) AS yayasan,
    COUNT(CASE WHEN tipe = 'lembaga' THEN 1 END) AS lembaga,
    COUNT(CASE WHEN status = 'active' THEN 1 END) AS aktif
FROM donatur;

-- ============================================
-- SELESAI
-- ============================================
