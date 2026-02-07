-- ============================================
-- GENERATE DUMMY DATA LOKASI STRATEGIS SUMATERA BARAT
-- Data lokasi kabupaten/kota/kecamatan dengan koordinat dan jumlah warga
-- ============================================

-- Hapus data dummy sebelumnya (opsional)
-- DELETE FROM lokasi_strategis WHERE nama_lokasi LIKE '%Dummy%';

-- Insert data Kabupaten/Kota di Sumatera Barat
INSERT INTO `lokasi_strategis` (
    `nama_lokasi`, 
    `tipe_lokasi`, 
    `latitude`, 
    `longitude`, 
    `total_warga`, 
    `warga_miskin`, 
    `warga_terdampak`, 
    `prioritas`, 
    `keterangan`,
    `status`
) VALUES
-- Kota Padang (Ibu Kota Provinsi)
('Kota Padang', 'kota', -0.94924, 100.35427, 900000, 135000, 180000, 'sangat_tinggi', 'Ibu kota provinsi Sumatera Barat, pusat pemerintahan dan ekonomi', 'aktif'),
('Kecamatan Padang Barat', 'kecamatan', -0.95111, 100.35417, 85000, 12750, 17000, 'sangat_tinggi', 'Pusat kota, area komersial', 'aktif'),
('Kecamatan Padang Utara', 'kecamatan', -0.92000, 100.36000, 120000, 18000, 24000, 'sangat_tinggi', 'Area padat penduduk', 'aktif'),
('Kecamatan Padang Timur', 'kecamatan', -0.95000, 100.38000, 95000, 14250, 19000, 'tinggi', 'Area industri dan permukiman', 'aktif'),
('Kecamatan Padang Selatan', 'kecamatan', -0.97000, 100.35000, 110000, 16500, 22000, 'tinggi', 'Area pesisir dan permukiman', 'aktif'),

-- Kota Bukittinggi
('Kota Bukittinggi', 'kota', -0.30556, 100.36917, 120000, 18000, 24000, 'tinggi', 'Kota wisata dan budaya', 'aktif'),
('Kecamatan Guguk Panjang', 'kecamatan', -0.31000, 100.37000, 35000, 5250, 7000, 'tinggi', 'Area pusat kota', 'aktif'),
('Kecamatan Mandiangin Koto Selayan', 'kecamatan', -0.30000, 100.36000, 28000, 4200, 5600, 'sedang', 'Area permukiman', 'aktif'),

-- Kota Padang Panjang
('Kota Padang Panjang', 'kota', -0.46000, 100.40000, 55000, 8250, 11000, 'sedang', 'Kota pendidikan dan wisata', 'aktif'),
('Kecamatan Padang Panjang Barat', 'kecamatan', -0.46500, 100.39500, 28000, 4200, 5600, 'sedang', 'Area permukiman', 'aktif'),
('Kecamatan Padang Panjang Timur', 'kecamatan', -0.45500, 100.40500, 27000, 4050, 5400, 'sedang', 'Area permukiman', 'aktif'),

-- Kota Pariaman
('Kota Pariaman', 'kota', -0.62667, 100.12000, 85000, 12750, 17000, 'tinggi', 'Kota pesisir, area pertanian dan perikanan', 'aktif'),
('Kecamatan Pariaman Selatan', 'kecamatan', -0.63000, 100.11500, 45000, 6750, 9000, 'tinggi', 'Area pesisir', 'aktif'),
('Kecamatan Pariaman Utara', 'kecamatan', -0.62000, 100.12500, 40000, 6000, 8000, 'tinggi', 'Area permukiman', 'aktif'),

-- Kota Solok
('Kota Solok', 'kota', -0.80000, 100.65000, 65000, 9750, 13000, 'sedang', 'Kota pertanian dan perdagangan', 'aktif'),
('Kecamatan Lubuk Sikarah', 'kecamatan', -0.80500, 100.65500, 35000, 5250, 7000, 'sedang', 'Pusat kota', 'aktif'),
('Kecamatan Tanjung Harapan', 'kecamatan', -0.79500, 100.64500, 30000, 4500, 6000, 'sedang', 'Area permukiman', 'aktif'),

-- Kota Sawahlunto
('Kota Sawahlunto', 'kota', -0.68111, 100.77722, 65000, 9750, 13000, 'sedang', 'Kota tambang bersejarah', 'aktif'),
('Kecamatan Barangin', 'kecamatan', -0.68500, 100.78000, 22000, 3300, 4400, 'sedang', 'Area tambang', 'aktif'),
('Kecamatan Lembah Segar', 'kecamatan', -0.67700, 100.77400, 21000, 3150, 4200, 'sedang', 'Area permukiman', 'aktif'),

-- Kota Payakumbuh
('Kota Payakumbuh', 'kota', -0.21667, 100.63333, 130000, 19500, 26000, 'tinggi', 'Kota pertanian dan perdagangan', 'aktif'),
('Kecamatan Payakumbuh Barat', 'kecamatan', -0.22000, 100.63000, 45000, 6750, 9000, 'tinggi', 'Pusat kota', 'aktif'),
('Kecamatan Payakumbuh Utara', 'kecamatan', -0.21300, 100.63600, 42000, 6300, 8400, 'tinggi', 'Area permukiman', 'aktif'),

-- Kabupaten Padang Pariaman
('Kabupaten Padang Pariaman', 'kabupaten', -0.60000, 100.30000, 450000, 67500, 90000, 'sangat_tinggi', 'Kabupaten dengan banyak desa terpencil', 'aktif'),
('Kecamatan Batang Anai', 'kecamatan', -0.61000, 100.31000, 35000, 5250, 7000, 'tinggi', 'Area pertanian', 'aktif'),
('Kecamatan Lubuk Alung', 'kecamatan', -0.59000, 100.29000, 42000, 6300, 8400, 'tinggi', 'Area pertanian dan permukiman', 'aktif'),
('Kecamatan Nan Sabaris', 'kecamatan', -0.60500, 100.30500, 38000, 5700, 7600, 'tinggi', 'Area pesisir', 'aktif'),

-- Kabupaten Agam
('Kabupaten Agam', 'kabupaten', -0.25000, 100.15000, 520000, 78000, 104000, 'sangat_tinggi', 'Kabupaten dengan banyak desa terpencil', 'aktif'),
('Kecamatan Tanjung Raya', 'kecamatan', -0.25500, 100.15500, 45000, 6750, 9000, 'tinggi', 'Area Danau Maninjau', 'aktif'),
('Kecamatan Ampek Nagari', 'kecamatan', -0.24500, 100.14500, 42000, 6300, 8400, 'tinggi', 'Area pertanian', 'aktif'),
('Kecamatan Baso', 'kecamatan', -0.25000, 100.15000, 38000, 5700, 7600, 'tinggi', 'Area permukiman', 'aktif'),

-- Kabupaten Tanah Datar
('Kabupaten Tanah Datar', 'kabupaten', -0.46667, 100.58333, 350000, 52500, 70000, 'tinggi', 'Kabupaten budaya dan pertanian', 'aktif'),
('Kecamatan Pariangan', 'kecamatan', -0.47000, 100.58500, 28000, 4200, 5600, 'sedang', 'Area budaya', 'aktif'),
('Kecamatan Sungai Tarab', 'kecamatan', -0.46300, 100.58100, 32000, 4800, 6400, 'sedang', 'Area pertanian', 'aktif'),
('Kecamatan Batipuh', 'kecamatan', -0.46000, 100.58000, 25000, 3750, 5000, 'sedang', 'Area permukiman', 'aktif'),

-- Kabupaten Lima Puluh Kota
('Kabupaten Lima Puluh Kota', 'kabupaten', -0.20000, 100.60000, 380000, 57000, 76000, 'tinggi', 'Kabupaten pertanian', 'aktif'),
('Kecamatan Harau', 'kecamatan', -0.20500, 100.60500, 35000, 5250, 7000, 'tinggi', 'Area pertanian', 'aktif'),
('Kecamatan Pangkalan Koto Baru', 'kecamatan', -0.19500, 100.59500, 32000, 4800, 6400, 'tinggi', 'Area permukiman', 'aktif'),
('Kecamatan Guguak', 'kecamatan', -0.20000, 100.60000, 28000, 4200, 5600, 'sedang', 'Area pertanian', 'aktif'),

-- Kabupaten Pasaman
('Kabupaten Pasaman', 'kabupaten', 0.16667, 100.11667, 280000, 42000, 56000, 'tinggi', 'Kabupaten perbatasan', 'aktif'),
('Kecamatan Lubuk Sikaping', 'kecamatan', 0.17000, 100.12000, 45000, 6750, 9000, 'tinggi', 'Ibu kota kabupaten', 'aktif'),
('Kecamatan Bonjol', 'kecamatan', 0.16300, 100.11300, 38000, 5700, 7600, 'tinggi', 'Area bersejarah', 'aktif'),
('Kecamatan Tigo Nagari', 'kecamatan', 0.16000, 100.11000, 32000, 4800, 6400, 'sedang', 'Area pertanian', 'aktif'),

-- Kabupaten Solok
('Kabupaten Solok', 'kabupaten', -0.80000, 100.65000, 380000, 57000, 76000, 'tinggi', 'Kabupaten pertanian dan pariwisata', 'aktif'),
('Kecamatan X Koto Diatas', 'kecamatan', -0.80500, 100.65500, 35000, 5250, 7000, 'tinggi', 'Area pertanian', 'aktif'),
('Kecamatan X Koto Singkarak', 'kecamatan', -0.79500, 100.64500, 32000, 4800, 6400, 'tinggi', 'Area Danau Singkarak', 'aktif'),
('Kecamatan Junjung Sirih', 'kecamatan', -0.80000, 100.65000, 28000, 4200, 5600, 'sedang', 'Area permukiman', 'aktif'),

-- Kabupaten Sijunjung
('Kabupaten Sijunjung', 'kabupaten', -0.70000, 100.95000, 220000, 33000, 44000, 'sedang', 'Kabupaten pertanian', 'aktif'),
('Kecamatan Sijunjung', 'kecamatan', -0.70500, 100.95500, 35000, 5250, 7000, 'sedang', 'Ibu kota kabupaten', 'aktif'),
('Kecamatan Kamang Baru', 'kecamatan', -0.69500, 100.94500, 28000, 4200, 5600, 'sedang', 'Area pertanian', 'aktif'),

-- Kabupaten Dharmasraya
('Kabupaten Dharmasraya', 'kabupaten', -1.05000, 101.40000, 200000, 30000, 40000, 'sedang', 'Kabupaten perbatasan', 'aktif'),
('Kecamatan Pulau Punjung', 'kecamatan', -1.05500, 101.40500, 35000, 5250, 7000, 'sedang', 'Ibu kota kabupaten', 'aktif'),
('Kecamatan Sitiung', 'kecamatan', -1.04500, 101.39500, 28000, 4200, 5600, 'sedang', 'Area pertanian', 'aktif'),

-- Kabupaten Pasaman Barat
('Kabupaten Pasaman Barat', 'kabupaten', 0.08333, 99.83333, 450000, 67500, 90000, 'sangat_tinggi', 'Kabupaten pesisir dengan banyak desa terpencil', 'aktif'),
('Kecamatan Simpang Empat', 'kecamatan', 0.08500, 99.83500, 45000, 6750, 9000, 'sangat_tinggi', 'Ibu kota kabupaten', 'aktif'),
('Kecamatan Talamau', 'kecamatan', 0.08000, 99.83000, 42000, 6300, 8400, 'tinggi', 'Area pesisir', 'aktif'),
('Kecamatan Kinali', 'kecamatan', 0.08300, 99.83300, 38000, 5700, 7600, 'tinggi', 'Area pertanian', 'aktif'),

-- Kabupaten Pesisir Selatan
('Kabupaten Pesisir Selatan', 'kabupaten', -1.35000, 100.55000, 520000, 78000, 104000, 'sangat_tinggi', 'Kabupaten pesisir terpanjang', 'aktif'),
('Kecamatan Painan', 'kecamatan', -1.35500, 100.55500, 45000, 6750, 9000, 'sangat_tinggi', 'Ibu kota kabupaten', 'aktif'),
('Kecamatan IV Jurai', 'kecamatan', -1.34500, 100.54500, 42000, 6300, 8400, 'tinggi', 'Area pesisir', 'aktif'),
('Kecamatan Bayang', 'kecamatan', -1.35000, 100.55000, 38000, 5700, 7600, 'tinggi', 'Area pesisir dan perikanan', 'aktif'),

-- Kabupaten Kepulauan Mentawai
('Kabupaten Kepulauan Mentawai', 'kabupaten', -2.18333, 99.65000, 85000, 12750, 17000, 'sangat_tinggi', 'Kabupaten kepulauan, akses terbatas', 'aktif'),
('Kecamatan Sikakap', 'kecamatan', -2.18500, 99.65500, 22000, 3300, 4400, 'sangat_tinggi', 'Ibu kota kabupaten', 'aktif'),
('Kecamatan Pagai Utara', 'kecamatan', -2.18000, 99.65000, 18000, 2700, 3600, 'sangat_tinggi', 'Area terpencil', 'aktif'),
('Kecamatan Siberut Selatan', 'kecamatan', -2.19000, 99.66000, 15000, 2250, 3000, 'sangat_tinggi', 'Area terpencil', 'aktif');

-- ============================================
-- VERIFIKASI DATA
-- ============================================
SELECT 
    tipe_lokasi,
    COUNT(*) as jumlah_lokasi,
    SUM(total_warga) as total_warga,
    SUM(warga_miskin) as total_warga_miskin,
    SUM(warga_terdampak) as total_warga_terdampak
FROM lokasi_strategis
GROUP BY tipe_lokasi;

SELECT 
    prioritas,
    COUNT(*) as jumlah,
    SUM(warga_terdampak) as total_warga_terdampak
FROM lokasi_strategis
GROUP BY prioritas;

-- ============================================
-- SELESAI
-- Total: 50+ lokasi strategis di Sumatera Barat
-- ============================================

