-- ============================================
-- GENERATE MAPPING PROGRAM KE LOKASI
-- Menghubungkan program CSR dengan lokasi strategis
-- ============================================

-- Hapus data mapping sebelumnya (opsional)
-- DELETE FROM program_lokasi;

-- Mapping program ke lokasi berdasarkan nama program yang mengandung nama lokasi
INSERT INTO `program_lokasi` (
    `program_id`,
    `lokasi_id`,
    `jumlah_penerima`,
    `dampak`
)
SELECT 
    p.id AS program_id,
    l.id AS lokasi_id,
    -- Jumlah penerima (10-30% dari warga terdampak di lokasi)
    FLOOR((10 + (p.id * 7) % 20) / 100 * l.warga_terdampak) AS jumlah_penerima,
    CONCAT('Program ', p.nama_program, ' memberikan bantuan kepada ', 
        FLOOR((10 + (p.id * 7) % 20) / 100 * l.warga_terdampak),
        ' warga di ', l.nama_lokasi) AS dampak
FROM program_csr p
CROSS JOIN lokasi_strategis l
WHERE l.status = 'aktif'
  AND (
    -- Match berdasarkan nama lokasi dalam nama program
    p.nama_program LIKE CONCAT('%', l.nama_lokasi, '%')
    OR
    -- Match berdasarkan kategori program dan prioritas lokasi
    (p.kategori = 'pendidikan' AND l.prioritas IN ('sangat_tinggi', 'tinggi'))
    OR
    (p.kategori = 'kesehatan' AND l.prioritas IN ('sangat_tinggi', 'tinggi'))
    OR
    (p.kategori = 'sosial' AND l.warga_terdampak > 5000)
    OR
    (p.kategori = 'bencana' AND l.prioritas = 'sangat_tinggi')
  )
  AND (p.id * l.id) % 3 = 0  -- Random selection (sekitar 33% match)
LIMIT 150;

-- Tambahkan beberapa mapping tambahan untuk variasi
INSERT INTO `program_lokasi` (
    `program_id`,
    `lokasi_id`,
    `jumlah_penerima`,
    `dampak`
)
SELECT 
    p.id AS program_id,
    l.id AS lokasi_id,
    FLOOR((15 + (p.id * 11) % 25) / 100 * l.warga_terdampak) AS jumlah_penerima,
    CONCAT('Program ', p.nama_program, ' memberikan bantuan kepada ', 
        FLOOR((15 + (p.id * 11) % 25) / 100 * l.warga_terdampak),
        ' warga di ', l.nama_lokasi) AS dampak
FROM program_csr p
CROSS JOIN lokasi_strategis l
WHERE l.status = 'aktif'
  AND p.status IN ('ongoing', 'completed')
  AND l.prioritas IN ('sangat_tinggi', 'tinggi')
  AND (p.id * l.id * 13) % 5 = 0  -- Random selection
  AND NOT EXISTS (
    SELECT 1 FROM program_lokasi pl 
    WHERE pl.program_id = p.id AND pl.lokasi_id = l.id
  )
LIMIT 100;

-- ============================================
-- VERIFIKASI DATA
-- ============================================
SELECT 
    COUNT(*) AS total_mapping,
    COUNT(DISTINCT program_id) AS program_terhubung,
    COUNT(DISTINCT lokasi_id) AS lokasi_terhubung,
    SUM(jumlah_penerima) AS total_penerima
FROM program_lokasi;

-- Top 10 lokasi dengan program terbanyak
SELECT 
    l.nama_lokasi,
    l.tipe_lokasi,
    l.warga_terdampak,
    COUNT(pl.program_id) AS jumlah_program,
    SUM(pl.jumlah_penerima) AS total_penerima
FROM lokasi_strategis l
LEFT JOIN program_lokasi pl ON l.id = pl.lokasi_id
GROUP BY l.id, l.nama_lokasi, l.tipe_lokasi, l.warga_terdampak
ORDER BY jumlah_program DESC
LIMIT 10;

-- ============================================
-- SELESAI
-- ============================================

