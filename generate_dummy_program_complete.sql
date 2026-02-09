-- ============================================
-- GENERATE DUMMY PROGRAM CSR LENGKAP
-- Script untuk membuat data dummy program CSR dengan semua field detail
-- ============================================

-- Generate 50 dummy program dengan data lengkap
INSERT INTO `program_csr` (
    `nama_program`, 
    `kategori`, 
    `deskripsi`, 
    `lokasi`,
    `kecamatan`,
    `kota`,
    `provinsi`,
    `latitude`, 
    `longitude`, 
    `tanggal_mulai`, 
    `tanggal_selesai`, 
    `budget`, 
    `realisasi_budget`,
    `status`, 
    `progress`,
    `pic`,
    `jenis_bantuan`,
    `jumlah_bantuan`,
    `satuan`,
    `jumlah_penerima_manfaat`,
    `jumlah_relawan_terlibat`,
    `created_at`
)
VALUES
-- Program 1-10: Pendidikan
('Program Beasiswa Anak Yatim Piatu - Padang', 'pendidikan', 'Program beasiswa untuk anak yatim piatu di wilayah Padang. Memberikan bantuan pendidikan mulai dari SD hingga SMA dengan dukungan biaya sekolah, buku, dan seragam.', 'Jl. Sudirman No. 45', 'Padang Barat', 'Padang', 'Sumatera Barat', -0.949240, 100.354271, '2024-01-15', '2024-12-31', 500000000, 350000000, 'ongoing', 70, NULL, 'Beasiswa Pendidikan', 150, 'Paket', 150, 25, NOW()),
('Program Perpustakaan Keliling - Bukittinggi', 'pendidikan', 'Menyediakan perpustakaan keliling untuk meningkatkan minat baca anak-anak di daerah terpencil Bukittinggi.', 'Jl. Ahmad Yani No. 12', 'Guguk Panjang', 'Bukittinggi', 'Sumatera Barat', -0.305556, 100.369167, '2024-02-01', '2024-11-30', 250000000, 180000000, 'ongoing', 72, NULL, 'Buku dan Peralatan', 500, 'Paket', 2000, 15, NOW()),
('Program Pelatihan Komputer Gratis - Solok', 'pendidikan', 'Pelatihan komputer gratis untuk remaja dan pemuda di Solok untuk meningkatkan skill digital.', 'Jl. Merdeka No. 88', 'Lembah Segar', 'Solok', 'Sumatera Barat', -0.800556, 100.656667, '2024-03-10', '2024-12-15', 300000000, 220000000, 'ongoing', 73, NULL, 'Pelatihan Komputer', 200, 'Sesi', 200, 20, NOW()),
('Program Bantuan Alat Tulis Sekolah - Pariaman', 'pendidikan', 'Bantuan alat tulis sekolah untuk siswa kurang mampu di Pariaman.', 'Jl. Diponegoro No. 33', 'Pariaman Selatan', 'Pariaman', 'Sumatera Barat', -0.626667, 100.120833, '2024-01-20', '2024-12-20', 150000000, 120000000, 'ongoing', 80, NULL, 'Alat Tulis Sekolah', 1000, 'Paket', 1000, 30, NOW()),
('Program Renovasi Sekolah - Payakumbuh', 'pendidikan', 'Renovasi gedung sekolah yang rusak di Payakumbuh untuk menciptakan lingkungan belajar yang lebih baik.', 'Jl. Gatot Subroto No. 55', 'Payakumbuh Barat', 'Payakumbuh', 'Sumatera Barat', -0.220556, 100.633056, '2024-04-01', '2024-10-31', 750000000, 600000000, 'ongoing', 80, NULL, 'Renovasi Bangunan', 1, 'Unit', 500, 50, NOW()),

-- Program 11-20: Kesehatan
('Program Posyandu Plus - Padang', 'kesehatan', 'Program posyandu plus dengan layanan kesehatan lengkap untuk ibu hamil dan balita di Padang.', 'Jl. Imam Bonjol No. 77', 'Padang Timur', 'Padang', 'Sumatera Barat', -0.949240, 100.354271, '2024-02-15', '2024-12-31', 400000000, 280000000, 'ongoing', 70, NULL, 'Layanan Kesehatan', 50, 'Paket', 500, 40, NOW()),
('Program Vaksinasi Gratis - Bukittinggi', 'kesehatan', 'Program vaksinasi gratis untuk anak-anak dan lansia di Bukittinggi.', 'Jl. Thamrin No. 22', 'Mandiangin Koto Selayan', 'Bukittinggi', 'Sumatera Barat', -0.305556, 100.369167, '2024-03-01', '2024-11-30', 350000000, 250000000, 'ongoing', 71, NULL, 'Vaksin', 2000, 'Dosis', 2000, 35, NOW()),
('Program Pemeriksaan Kesehatan Gratis - Solok', 'kesehatan', 'Pemeriksaan kesehatan gratis untuk masyarakat kurang mampu di Solok.', 'Jl. Hayam Wuruk No. 99', 'Tanah Garam', 'Solok', 'Sumatera Barat', -0.800556, 100.656667, '2024-04-10', '2024-12-15', 300000000, 200000000, 'ongoing', 67, NULL, 'Pemeriksaan Kesehatan', 1000, 'Orang', 1000, 30, NOW()),
('Program Bantuan Obat Gratis - Pariaman', 'kesehatan', 'Bantuan obat-obatan gratis untuk pasien tidak mampu di Pariaman.', 'Jl. Juanda No. 44', 'Pariaman Tengah', 'Pariaman', 'Sumatera Barat', -0.626667, 100.120833, '2024-05-01', '2024-12-31', 450000000, 320000000, 'ongoing', 71, NULL, 'Obat-obatan', 500, 'Paket', 500, 25, NOW()),
('Program Ambulans Gratis - Payakumbuh', 'kesehatan', 'Layanan ambulans gratis untuk masyarakat yang membutuhkan di Payakumbuh.', 'Jl. Kartini No. 66', 'Payakumbuh Utara', 'Payakumbuh', 'Sumatera Barat', -0.220556, 100.633056, '2024-06-01', '2024-12-31', 600000000, 450000000, 'ongoing', 75, NULL, 'Layanan Ambulans', 1, 'Unit', 100, 10, NOW()),

-- Program 21-30: Sosial
('Program Bantuan Sembako - Padang', 'sosial', 'Bantuan sembako untuk keluarga kurang mampu di Padang setiap bulan.', 'Jl. Sudirman No. 123', 'Padang Selatan', 'Padang', 'Sumatera Barat', -0.949240, 100.354271, '2024-01-01', '2024-12-31', 600000000, 480000000, 'ongoing', 80, NULL, 'Sembako', 500, 'Paket', 500, 45, NOW()),
('Program Bantuan Pakaian - Bukittinggi', 'sosial', 'Bantuan pakaian layak pakai untuk masyarakat kurang mampu di Bukittinggi.', 'Jl. Ahmad Yani No. 234', 'Aur Birugo Tigo Baleh', 'Bukittinggi', 'Sumatera Barat', -0.305556, 100.369167, '2024-02-01', '2024-11-30', 200000000, 150000000, 'ongoing', 75, NULL, 'Pakaian', 1000, 'Paket', 1000, 30, NOW()),
('Program Bantuan Rumah Layak Huni - Solok', 'sosial', 'Renovasi rumah tidak layak huni menjadi layak huni untuk keluarga miskin di Solok.', 'Jl. Merdeka No. 345', 'Lubuk Sikarah', 'Solok', 'Sumatera Barat', -0.800556, 100.656667, '2024-03-15', '2024-12-31', 1000000000, 750000000, 'ongoing', 75, NULL, 'Renovasi Rumah', 20, 'Unit', 20, 60, NOW()),
('Program Santunan Anak Yatim - Pariaman', 'sosial', 'Program santunan bulanan untuk anak yatim di Pariaman.', 'Jl. Diponegoro No. 456', 'Pariaman Utara', 'Pariaman', 'Sumatera Barat', -0.626667, 100.120833, '2024-04-01', '2024-12-31', 400000000, 300000000, 'ongoing', 75, NULL, 'Santunan', 200, 'Paket', 200, 20, NOW()),
('Program Bantuan Modal Usaha - Payakumbuh', 'sosial', 'Bantuan modal usaha untuk UMKM di Payakumbuh.', 'Jl. Gatot Subroto No. 567', 'Payakumbuh Selatan', 'Payakumbuh', 'Sumatera Barat', -0.220556, 100.633056, '2024-05-01', '2024-12-31', 800000000, 600000000, 'ongoing', 75, NULL, 'Modal Usaha', 50, 'Paket', 50, 25, NOW()),

-- Program 31-40: Lingkungan
('Program Penanaman Pohon - Padang', 'lingkungan', 'Program penanaman pohon untuk penghijauan di Padang.', 'Jl. Imam Bonjol No. 678', 'Padang Utara', 'Padang', 'Sumatera Barat', -0.949240, 100.354271, '2024-01-10', '2024-12-31', 250000000, 200000000, 'ongoing', 80, NULL, 'Bibit Pohon', 5000, 'Pohon', 1000, 100, NOW()),
('Program Pembersihan Sungai - Bukittinggi', 'lingkungan', 'Program pembersihan sungai dari sampah di Bukittinggi.', 'Jl. Thamrin No. 789', 'Aur Tajungkang Tangah Sawah', 'Bukittinggi', 'Sumatera Barat', -0.305556, 100.369167, '2024-02-15', '2024-11-30', 300000000, 220000000, 'ongoing', 73, NULL, 'Pembersihan', 10, 'Lokasi', 500, 80, NOW()),
('Program Bank Sampah - Solok', 'lingkungan', 'Program bank sampah untuk mengelola sampah menjadi bernilai ekonomi di Solok.', 'Jl. Hayam Wuruk No. 890', 'Tanjung Harapan', 'Solok', 'Sumatera Barat', -0.800556, 100.656667, '2024-03-20', '2024-12-31', 350000000, 250000000, 'ongoing', 71, NULL, 'Bank Sampah', 5, 'Unit', 200, 30, NOW()),
('Program Pengolahan Sampah Organik - Pariaman', 'lingkungan', 'Program pengolahan sampah organik menjadi kompos di Pariaman.', 'Jl. Juanda No. 901', 'Pariaman Timur', 'Pariaman', 'Sumatera Barat', -0.626667, 100.120833, '2024-04-10', '2024-12-15', 200000000, 150000000, 'ongoing', 75, NULL, 'Komposter', 20, 'Unit', 100, 25, NOW()),
('Program Konservasi Air - Payakumbuh', 'lingkungan', 'Program konservasi air dengan pembuatan sumur resapan di Payakumbuh.', 'Jl. Kartini No. 102', 'Payakumbuh Timur', 'Payakumbuh', 'Sumatera Barat', -0.220556, 100.633056, '2024-05-15', '2024-12-31', 400000000, 300000000, 'ongoing', 75, NULL, 'Sumur Resapan', 50, 'Unit', 200, 40, NOW()),

-- Program 41-50: Ekonomi
('Program Pelatihan Wirausaha - Padang', 'ekonomi', 'Pelatihan wirausaha untuk meningkatkan ekonomi masyarakat di Padang.', 'Jl. Sudirman No. 111', 'Padang Barat', 'Padang', 'Sumatera Barat', -0.949240, 100.354271, '2024-01-05', '2024-12-31', 500000000, 400000000, 'ongoing', 80, NULL, 'Pelatihan', 100, 'Sesi', 100, 15, NOW()),
('Program Pasar Murah - Bukittinggi', 'ekonomi', 'Program pasar murah untuk membantu masyarakat mendapatkan kebutuhan dengan harga terjangkau di Bukittinggi.', 'Jl. Ahmad Yani No. 222', 'Guguk Panjang', 'Bukittinggi', 'Sumatera Barat', -0.305556, 100.369167, '2024-02-10', '2024-11-30', 300000000, 240000000, 'ongoing', 80, NULL, 'Pasar Murah', 12, 'Kali', 5000, 50, NOW()),
('Program Koperasi Simpan Pinjam - Solok', 'ekonomi', 'Program koperasi simpan pinjam untuk membantu permodalan usaha kecil di Solok.', 'Jl. Merdeka No. 333', 'Lembah Segar', 'Solok', 'Sumatera Barat', -0.800556, 100.656667, '2024-03-15', '2024-12-31', 600000000, 450000000, 'ongoing', 75, NULL, 'Koperasi', 1, 'Unit', 200, 20, NOW()),
('Program Pemasaran Produk UMKM - Pariaman', 'ekonomi', 'Program membantu pemasaran produk UMKM melalui platform digital di Pariaman.', 'Jl. Diponegoro No. 444', 'Pariaman Selatan', 'Pariaman', 'Sumatera Barat', -0.626667, 100.120833, '2024-04-20', '2024-12-15', 350000000, 280000000, 'ongoing', 80, NULL, 'Pelatihan Digital', 50, 'Sesi', 50, 10, NOW()),
('Program Bantuan Alat Produksi - Payakumbuh', 'ekonomi', 'Bantuan alat produksi untuk meningkatkan produktivitas UMKM di Payakumbuh.', 'Jl. Gatot Subroto No. 555', 'Payakumbuh Barat', 'Payakumbuh', 'Sumatera Barat', -0.220556, 100.633056, '2024-05-25', '2024-12-31', 700000000, 525000000, 'ongoing', 75, NULL, 'Alat Produksi', 30, 'Paket', 30, 15, NOW()),

-- Program 51-60: Bencana
('Program Bantuan Korban Banjir - Padang', 'bencana', 'Bantuan untuk korban banjir di Padang berupa sembako, pakaian, dan tempat tinggal sementara.', 'Jl. Imam Bonjol No. 666', 'Padang Timur', 'Padang', 'Sumatera Barat', -0.949240, 100.354271, '2024-01-20', '2024-06-30', 1000000000, 950000000, 'completed', 95, NULL, 'Bantuan Bencana', 200, 'Paket', 200, 60, NOW()),
('Program Rehabilitasi Pasca Gempa - Bukittinggi', 'bencana', 'Program rehabilitasi rumah dan fasilitas umum pasca gempa di Bukittinggi.', 'Jl. Thamrin No. 777', 'Mandiangin Koto Selayan', 'Bukittinggi', 'Sumatera Barat', -0.305556, 100.369167, '2024-02-25', '2024-08-31', 1500000000, 1200000000, 'ongoing', 80, NULL, 'Rehabilitasi', 50, 'Unit', 50, 80, NOW()),
('Program Bantuan Korban Longsor - Solok', 'bencana', 'Bantuan untuk korban longsor di Solok berupa evakuasi, tempat tinggal sementara, dan kebutuhan dasar.', 'Jl. Hayam Wuruk No. 888', 'Tanah Garam', 'Solok', 'Sumatera Barat', -0.800556, 100.656667, '2024-03-30', '2024-09-30', 800000000, 720000000, 'ongoing', 90, NULL, 'Bantuan Bencana', 100, 'Paket', 100, 50, NOW()),
('Program Penanggulangan Kebakaran - Pariaman', 'bencana', 'Program penanggulangan dan pencegahan kebakaran di Pariaman.', 'Jl. Juanda No. 999', 'Pariaman Tengah', 'Pariaman', 'Sumatera Barat', -0.626667, 100.120833, '2024-04-05', '2024-12-31', 400000000, 300000000, 'ongoing', 75, NULL, 'Alat Pemadam', 20, 'Unit', 500, 30, NOW()),
('Program Bantuan Korban Kekeringan - Payakumbuh', 'bencana', 'Bantuan air bersih dan kebutuhan dasar untuk korban kekeringan di Payakumbuh.', 'Jl. Kartini No. 1010', 'Payakumbuh Utara', 'Payakumbuh', 'Sumatera Barat', -0.220556, 100.633056, '2024-05-10', '2024-11-30', 500000000, 400000000, 'ongoing', 80, NULL, 'Bantuan Air', 1000, 'Liter', 500, 40, NOW());

-- Verifikasi data
SELECT 
    COUNT(*) as total_program,
    COUNT(CASE WHEN status='ongoing' THEN 1 END) as ongoing,
    COUNT(CASE WHEN status='completed' THEN 1 END) as completed,
    COUNT(CASE WHEN status='planning' THEN 1 END) as planning,
    SUM(budget) as total_budget,
    SUM(realisasi_budget) as total_realisasi
FROM program_csr;

