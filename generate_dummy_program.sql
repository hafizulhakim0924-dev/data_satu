-- ============================================
-- GENERATE DUMMY PROGRAM CSR
-- Script untuk membuat data dummy program CSR
-- ============================================

-- Hapus data dummy sebelumnya (opsional)
-- DELETE FROM program_csr WHERE nama_program LIKE '%Dummy%' OR deskripsi LIKE '%Dummy%';

-- Generate 50 dummy program
INSERT INTO `program_csr` (
    `nama_program`, 
    `kategori`, 
    `deskripsi`, 
    `lokasi`, 
    `latitude`, 
    `longitude`, 
    `tanggal_mulai`, 
    `tanggal_selesai`, 
    `budget`, 
    `realisasi_budget`,
    `status`, 
    `progress`,
    `pic`,
    `created_at`
)
SELECT 
    CONCAT(
        CASE (p.id * 7) % 10
            WHEN 0 THEN 'Program Pendidikan Anak Yatim'
            WHEN 1 THEN 'Program Bantuan Pangan'
            WHEN 2 THEN 'Program Kesehatan Masyarakat'
            WHEN 3 THEN 'Program Bencana Alam'
            WHEN 4 THEN 'Program Infrastruktur Desa'
            WHEN 5 THEN 'Program Beasiswa'
            WHEN 6 THEN 'Program Kesejahteraan Lansia'
            WHEN 7 THEN 'Program Lingkungan Hidup'
            WHEN 8 THEN 'Program Ekonomi Kreatif'
            ELSE 'Program Dakwah'
        END,
        ' - ', 
        CASE (p.id * 11) % 10
            WHEN 0 THEN 'Padang'
            WHEN 1 THEN 'Bukittinggi'
            WHEN 2 THEN 'Solok'
            WHEN 3 THEN 'Pariaman'
            WHEN 4 THEN 'Payakumbuh'
            WHEN 5 THEN 'Sawahlunto'
            WHEN 6 THEN 'Padang Panjang'
            WHEN 7 THEN 'Lubuk Sikaping'
            WHEN 8 THEN 'Batusangkar'
            ELSE 'Sijunjung'
        END
    ) AS nama_program,
    CASE (p.id * 3) % 6
        WHEN 0 THEN 'pendidikan'
        WHEN 1 THEN 'kesehatan'
        WHEN 2 THEN 'sosial'
        WHEN 3 THEN 'lingkungan'
        WHEN 4 THEN 'ekonomi'
        ELSE 'bencana'
    END AS kategori,
    CONCAT(
        'Program CSR untuk meningkatkan kesejahteraan masyarakat di ',
        CASE (p.id * 11) % 10
            WHEN 0 THEN 'Padang'
            WHEN 1 THEN 'Bukittinggi'
            WHEN 2 THEN 'Solok'
            WHEN 3 THEN 'Pariaman'
            WHEN 4 THEN 'Payakumbuh'
            WHEN 5 THEN 'Sawahlunto'
            WHEN 6 THEN 'Padang Panjang'
            WHEN 7 THEN 'Lubuk Sikaping'
            WHEN 8 THEN 'Batusangkar'
            ELSE 'Sijunjung'
        END,
        '. Program ini bertujuan untuk memberikan manfaat langsung kepada masyarakat sekitar.'
    ) AS deskripsi,
    CONCAT(
        'Jl. ',
        CASE (p.id * 13) % 10
            WHEN 0 THEN 'Sudirman'
            WHEN 1 THEN 'Ahmad Yani'
            WHEN 2 THEN 'Merdeka'
            WHEN 3 THEN 'Diponegoro'
            WHEN 4 THEN 'Gatot Subroto'
            WHEN 5 THEN 'Imam Bonjol'
            WHEN 6 THEN 'Thamrin'
            WHEN 7 THEN 'Hayam Wuruk'
            WHEN 8 THEN 'Juanda'
            ELSE 'Kartini'
        END,
        ' No. ',
        FLOOR(1 + (p.id * 17) % 200),
        ', ',
        CASE (p.id * 11) % 10
            WHEN 0 THEN 'Padang'
            WHEN 1 THEN 'Bukittinggi'
            WHEN 2 THEN 'Solok'
            WHEN 3 THEN 'Pariaman'
            WHEN 4 THEN 'Payakumbuh'
            WHEN 5 THEN 'Sawahlunto'
            WHEN 6 THEN 'Padang Panjang'
            WHEN 7 THEN 'Lubuk Sikaping'
            WHEN 8 THEN 'Batusangkar'
            ELSE 'Sijunjung'
        END,
        ', Sumatera Barat'
    ) AS lokasi,
    -- Latitude Sumatera Barat (sekitar -0.95 sampai -1.5)
    -0.95 - ((p.id * 19) % 55) / 100 AS latitude,
    -- Longitude Sumatera Barat (sekitar 100.0 sampai 101.5)
    100.0 + ((p.id * 23) % 150) / 100 AS longitude,
    -- Tanggal mulai (dalam 1 tahun terakhir sampai 1 tahun ke depan)
    DATE_SUB(CURDATE(), INTERVAL (p.id * 31) % 365 DAY) AS tanggal_mulai,
    -- Tanggal selesai (30-365 hari setelah mulai)
    DATE_ADD(
        DATE_SUB(CURDATE(), INTERVAL (p.id * 31) % 365 DAY),
        INTERVAL 30 + ((p.id * 37) % 335) DAY
    ) AS tanggal_selesai,
    -- Budget (antara 10jt - 500jt)
    CASE 
        WHEN (p.id * 41) % 10 < 3 THEN FLOOR(10000000 + (p.id * 123) % 9000000) -- 10jt - 100jt
        WHEN (p.id * 41) % 10 < 6 THEN FLOOR(100000000 + (p.id * 456) % 200000000) -- 100jt - 300jt
        ELSE FLOOR(300000000 + (p.id * 789) % 200000000) -- 300jt - 500jt
    END AS budget,
    -- Realisasi budget (0-100% dari budget)
    CASE 
        WHEN (p.id * 43) % 10 < 2 THEN 0 -- Belum ada realisasi
        WHEN (p.id * 43) % 10 < 5 THEN FLOOR((p.id * 17) % 50) / 100 * 
            CASE 
                WHEN (p.id * 41) % 10 < 3 THEN FLOOR(10000000 + (p.id * 123) % 9000000)
                WHEN (p.id * 41) % 10 < 6 THEN FLOOR(100000000 + (p.id * 456) % 200000000)
                ELSE FLOOR(300000000 + (p.id * 789) % 200000000)
            END
        WHEN (p.id * 43) % 10 < 8 THEN FLOOR(50 + (p.id * 23) % 30) / 100 * 
            CASE 
                WHEN (p.id * 41) % 10 < 3 THEN FLOOR(10000000 + (p.id * 123) % 9000000)
                WHEN (p.id * 41) % 10 < 6 THEN FLOOR(100000000 + (p.id * 456) % 200000000)
                ELSE FLOOR(300000000 + (p.id * 789) % 200000000)
            END
        ELSE 
            CASE 
                WHEN (p.id * 41) % 10 < 3 THEN FLOOR(10000000 + (p.id * 123) % 9000000)
                WHEN (p.id * 41) % 10 < 6 THEN FLOOR(100000000 + (p.id * 456) % 200000000)
                ELSE FLOOR(300000000 + (p.id * 789) % 200000000)
            END
    END AS realisasi_budget,
    -- Status program
    CASE 
        WHEN (p.id * 47) % 10 < 2 THEN 'planning'
        WHEN (p.id * 47) % 10 < 7 THEN 'ongoing'
        WHEN (p.id * 47) % 10 < 9 THEN 'completed'
        ELSE 'cancelled'
    END AS status,
    -- Progress (0-100%)
    CASE 
        WHEN (p.id * 47) % 10 < 2 THEN 0 -- Planning
        WHEN (p.id * 47) % 10 < 7 THEN 10 + ((p.id * 19) % 80) -- Ongoing (10-90%)
        WHEN (p.id * 47) % 10 < 9 THEN 100 -- Completed
        ELSE 0 -- Cancelled
    END AS progress,
    -- PIC (ambil dari users jika ada, atau NULL)
    NULL AS pic,
    -- Created at
    DATE_SUB(NOW(), INTERVAL (p.id * 53) % 365 DAY) AS created_at
FROM (
    SELECT @row := @row + 1 AS id
    FROM (SELECT 0 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t1,
         (SELECT 0 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t2,
         (SELECT @row := 0) r
    LIMIT 50
) p;

-- Generate dummy penyaluran untuk program yang sudah ada
INSERT INTO `program_penyaluran` (
    `program_id`,
    `tanggal_penyaluran`,
    `jumlah_penyaluran`,
    `sasaran`,
    `lokasi_penyaluran`,
    `metode_penyaluran`,
    `keterangan`,
    `created_at`
)
SELECT 
    p.id AS program_id,
    DATE_ADD(
        p.tanggal_mulai,
        INTERVAL (pen.id * 17) % DATEDIFF(p.tanggal_selesai, p.tanggal_mulai) DAY
    ) AS tanggal_penyaluran,
    -- Jumlah penyaluran (10-50% dari budget)
    FLOOR((10 + (pen.id * 7) % 40) / 100 * p.budget) AS jumlah_penyaluran,
    CONCAT(
        FLOOR(10 + (pen.id * 11) % 90),
        ' ',
        CASE (pen.id * 13) % 5
            WHEN 0 THEN 'keluarga'
            WHEN 1 THEN 'anak yatim'
            WHEN 2 THEN 'lansia'
            WHEN 3 THEN 'pelajar'
            ELSE 'warga'
        END
    ) AS sasaran,
    p.lokasi AS lokasi_penyaluran,
    CASE (pen.id * 19) % 5
        WHEN 0 THEN 'tunai'
        WHEN 1 THEN 'barang'
        WHEN 2 THEN 'jasa'
        WHEN 3 THEN 'voucher'
        ELSE 'lainnya'
    END AS metode_penyaluran,
    CONCAT('Penyaluran ke-', pen.id, ' untuk program ', p.nama_program) AS keterangan,
    DATE_ADD(
        p.tanggal_mulai,
        INTERVAL (pen.id * 17) % DATEDIFF(p.tanggal_selesai, p.tanggal_mulai) DAY
    ) AS created_at
FROM program_csr p
CROSS JOIN (
    SELECT @row := @row + 1 AS id
    FROM (SELECT 0 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t1,
         (SELECT @row := 0) r
    LIMIT 10
) pen
WHERE p.status IN ('ongoing', 'completed')
LIMIT 200;

-- Generate dummy dampak untuk program yang sudah ada
INSERT INTO `program_dampak` (
    `program_id`,
    `tanggal_pengukuran`,
    `indikator`,
    `nilai`,
    `satuan`,
    `deskripsi`,
    `kategori_dampak`,
    `created_at`
)
SELECT 
    p.id AS program_id,
    DATE_ADD(
        p.tanggal_mulai,
        INTERVAL (damp.id * 23) % DATEDIFF(COALESCE(p.tanggal_selesai, CURDATE()), p.tanggal_mulai) DAY
    ) AS tanggal_pengukuran,
    CASE (damp.id * 7) % 10
        WHEN 0 THEN 'Jumlah Penerima Manfaat'
        WHEN 1 THEN 'Tingkat Kepuasan'
        WHEN 2 THEN 'Peningkatan Pendapatan'
        WHEN 3 THEN 'Peningkatan Akses Pendidikan'
        WHEN 4 THEN 'Peningkatan Akses Kesehatan'
        WHEN 5 THEN 'Pengurangan Kemiskinan'
        WHEN 6 THEN 'Peningkatan Kualitas Lingkungan'
        WHEN 7 THEN 'Jumlah Pekerjaan Tercipta'
        WHEN 8 THEN 'Peningkatan Literasi'
        ELSE 'Kesejahteraan Masyarakat'
    END AS indikator,
    FLOOR(10 + (damp.id * 11) % 990) AS nilai,
    CASE (damp.id * 13) % 6
        WHEN 0 THEN 'orang'
        WHEN 1 THEN 'keluarga'
        WHEN 2 THEN '%'
        WHEN 3 THEN 'unit'
        WHEN 4 THEN 'kg'
        ELSE 'point'
    END AS satuan,
    CONCAT('Pengukuran dampak program ', p.nama_program, ' pada indikator ', 
        CASE (damp.id * 7) % 10
            WHEN 0 THEN 'Jumlah Penerima Manfaat'
            WHEN 1 THEN 'Tingkat Kepuasan'
            WHEN 2 THEN 'Peningkatan Pendapatan'
            WHEN 3 THEN 'Peningkatan Akses Pendidikan'
            WHEN 4 THEN 'Peningkatan Akses Kesehatan'
            WHEN 5 THEN 'Pengurangan Kemiskinan'
            WHEN 6 THEN 'Peningkatan Kualitas Lingkungan'
            WHEN 7 THEN 'Jumlah Pekerjaan Tercipta'
            WHEN 8 THEN 'Peningkatan Literasi'
            ELSE 'Kesejahteraan Masyarakat'
        END
    ) AS deskripsi,
    CASE (damp.id * 17) % 6
        WHEN 0 THEN 'ekonomi'
        WHEN 1 THEN 'sosial'
        WHEN 2 THEN 'pendidikan'
        WHEN 3 THEN 'kesehatan'
        WHEN 4 THEN 'lingkungan'
        ELSE 'lainnya'
    END AS kategori_dampak,
    DATE_ADD(
        p.tanggal_mulai,
        INTERVAL (damp.id * 23) % DATEDIFF(COALESCE(p.tanggal_selesai, CURDATE()), p.tanggal_mulai) DAY
    ) AS created_at
FROM program_csr p
CROSS JOIN (
    SELECT @row := @row + 1 AS id
    FROM (SELECT 0 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t1,
         (SELECT @row := 0) r
    LIMIT 8
) damp
WHERE p.status IN ('ongoing', 'completed')
LIMIT 150;

-- ============================================
-- VERIFIKASI DATA
-- ============================================
SELECT 
    COUNT(*) AS total_program,
    COUNT(CASE WHEN status = 'planning' THEN 1 END) AS planning,
    COUNT(CASE WHEN status = 'ongoing' THEN 1 END) AS ongoing,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) AS completed,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) AS cancelled,
    SUM(budget) AS total_budget,
    SUM(realisasi_budget) AS total_realisasi
FROM program_csr;

SELECT 
    COUNT(*) AS total_penyaluran,
    SUM(jumlah_penyaluran) AS total_nominal_penyaluran
FROM program_penyaluran;

SELECT 
    COUNT(*) AS total_dampak,
    COUNT(DISTINCT program_id) AS program_terukur
FROM program_dampak;

-- ============================================
-- SELESAI
-- ============================================

