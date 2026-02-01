-- ============================================
-- DATABASE SCHEMA: RANGKIANG PEDULI NEGERI
-- Sistem Manajemen CSR Lengkap
-- ============================================

-- Tabel Users (Data User Lengkap)
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `nip` varchar(50) DEFAULT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `departemen` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `role` enum('admin','manager','staff','volunteer') DEFAULT 'staff',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Donatur (Data Donatur Lengkap)
CREATE TABLE IF NOT EXISTS `donatur` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `tipe` enum('individu','perusahaan','yayasan','lembaga') DEFAULT 'individu',
  `npwp` varchar(50) DEFAULT NULL,
  `nama_perusahaan` varchar(100) DEFAULT NULL,
  `pic` varchar(100) DEFAULT NULL,
  `kategori` enum('rutin','sporadis','corporate','zakat','infaq','sedekah') DEFAULT 'rutin',
  `status` enum('active','inactive') DEFAULT 'active',
  `catatan` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Donasi (Update dari csr_donations)
CREATE TABLE IF NOT EXISTS `csr_donations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `donatur_id` int(11) DEFAULT NULL,
  `nama_donatur` varchar(100) NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `tanggal` date NOT NULL,
  `metode_pembayaran` enum('tunai','transfer','kartu','qris','lainnya') DEFAULT 'tunai',
  `kategori` enum('zakat','infaq','sedekah','wakaf','csr','donasi_umum') DEFAULT 'donasi_umum',
  `program` varchar(100) DEFAULT NULL,
  `bukti_transfer` varchar(255) DEFAULT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `keterangan` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `donatur_id` (`donatur_id`),
  KEY `created_by` (`created_by`),
  FOREIGN KEY (`donatur_id`) REFERENCES `donatur`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Karyawan/Staff (SDM)
CREATE TABLE IF NOT EXISTS `karyawan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `nip` varchar(50) NOT NULL UNIQUE,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `tempat_lahir` varchar(50) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `agama` varchar(20) DEFAULT NULL,
  `status_pernikahan` enum('belum_menikah','menikah','cerai') DEFAULT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `departemen` varchar(100) DEFAULT NULL,
  `tanggal_masuk` date DEFAULT NULL,
  `status_karyawan` enum('kontrak','tetap','magang','volunteer') DEFAULT 'kontrak',
  `gaji_pokok` decimal(15,2) DEFAULT NULL,
  `bank` varchar(50) DEFAULT NULL,
  `no_rekening` varchar(50) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('active','resign','cuti') DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Gaji
CREATE TABLE IF NOT EXISTS `gaji` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `karyawan_id` int(11) NOT NULL,
  `periode` varchar(20) NOT NULL COMMENT 'Format: YYYY-MM',
  `gaji_pokok` decimal(15,2) NOT NULL DEFAULT 0,
  `tunjangan` decimal(15,2) DEFAULT 0,
  `bonus` decimal(15,2) DEFAULT 0,
  `lembur` decimal(15,2) DEFAULT 0,
  `potongan` decimal(15,2) DEFAULT 0,
  `total_gaji` decimal(15,2) NOT NULL,
  `status` enum('draft','approved','paid','rejected') DEFAULT 'draft',
  `tanggal_pembayaran` date DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_periode` (`karyawan_id`,`periode`),
  KEY `created_by` (`created_by`),
  FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Kehadiran
CREATE TABLE IF NOT EXISTS `kehadiran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `karyawan_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_keluar` time DEFAULT NULL,
  `status` enum('hadir','izin','sakit','cuti','alpha','libur') DEFAULT 'hadir',
  `keterangan` text DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_kehadiran` (`karyawan_id`,`tanggal`),
  FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Performa Karyawan
CREATE TABLE IF NOT EXISTS `performa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `karyawan_id` int(11) NOT NULL,
  `periode` varchar(20) NOT NULL COMMENT 'Format: YYYY-MM',
  `target` text DEFAULT NULL,
  `pencapaian` text DEFAULT NULL,
  `nilai_kinerja` decimal(5,2) DEFAULT NULL COMMENT 'Skala 0-100',
  `aspek_kerja` text DEFAULT NULL COMMENT 'JSON format',
  `catatan` text DEFAULT NULL,
  `penilai` int(11) DEFAULT NULL,
  `status` enum('draft','reviewed','approved') DEFAULT 'draft',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `karyawan_id` (`karyawan_id`),
  KEY `penilai` (`penilai`),
  FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`penilai`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Skill/Kompetensi
CREATE TABLE IF NOT EXISTS `skill` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `karyawan_id` int(11) NOT NULL,
  `nama_skill` varchar(100) NOT NULL,
  `kategori` enum('hard_skill','soft_skill','bahasa','sertifikasi') DEFAULT 'hard_skill',
  `tingkat` enum('pemula','menengah','mahir','expert') DEFAULT 'menengah',
  `sertifikat` varchar(255) DEFAULT NULL,
  `tanggal_terbit` date DEFAULT NULL,
  `tanggal_expired` date DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `karyawan_id` (`karyawan_id`),
  FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Volunteer
CREATE TABLE IF NOT EXISTS `volunteer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) NOT NULL,
  `alamat` text DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `pekerjaan` varchar(100) DEFAULT NULL,
  `instansi` varchar(100) DEFAULT NULL,
  `skill` text DEFAULT NULL,
  `minat_program` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','blacklist') DEFAULT 'active',
  `tanggal_bergabung` date DEFAULT NULL,
  `total_jam_kerja` int(11) DEFAULT 0,
  `catatan` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Aktivitas Volunteer
CREATE TABLE IF NOT EXISTS `aktivitas_volunteer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `volunteer_id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `nama_program` varchar(100) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  `durasi_jam` decimal(5,2) DEFAULT NULL,
  `tugas` text DEFAULT NULL,
  `evaluasi` text DEFAULT NULL,
  `status` enum('terjadwal','selesai','batal') DEFAULT 'terjadwal',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `volunteer_id` (`volunteer_id`),
  FOREIGN KEY (`volunteer_id`) REFERENCES `volunteer`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Keuangan (Pemasukan)
CREATE TABLE IF NOT EXISTS `pemasukan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kategori` enum('donasi','hibah','investasi','lainnya') DEFAULT 'donasi',
  `sumber` varchar(100) NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `tanggal` date NOT NULL,
  `metode_pembayaran` varchar(50) DEFAULT NULL,
  `bukti` varchar(255) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Keuangan (Pengeluaran)
CREATE TABLE IF NOT EXISTS `pengeluaran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kategori` enum('operasional','program','gaji','utilities','transport','lainnya') DEFAULT 'operasional',
  `program_id` int(11) DEFAULT NULL,
  `nama_program` varchar(100) DEFAULT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `tanggal` date NOT NULL,
  `metode_pembayaran` varchar(50) DEFAULT NULL,
  `vendor` varchar(100) DEFAULT NULL,
  `bukti` varchar(255) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `status` enum('draft','approved','paid','rejected') DEFAULT 'draft',
  `approved_by` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `approved_by` (`approved_by`),
  KEY `created_by` (`created_by`),
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Program CSR
CREATE TABLE IF NOT EXISTS `program_csr` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_program` varchar(100) NOT NULL,
  `kategori` enum('pendidikan','kesehatan','sosial','lingkungan','ekonomi','bencana') DEFAULT 'sosial',
  `deskripsi` text DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT NULL,
  `status` enum('planning','ongoing','completed','cancelled') DEFAULT 'planning',
  `pic` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pic` (`pic`),
  KEY `created_by` (`created_by`),
  FOREIGN KEY (`pic`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Laporan
CREATE TABLE IF NOT EXISTS `laporan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipe` enum('bulanan','tahunan','program','khusus') DEFAULT 'bulanan',
  `judul` varchar(200) NOT NULL,
  `periode` varchar(50) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `status` enum('draft','published') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ALTER TABLE (untuk update tabel yang sudah ada)
-- Jalankan perintah ini jika tabel users sudah ada tanpa kolom email
-- ============================================

-- Hapus komentar di bawah ini jika perlu menambahkan kolom email ke tabel yang sudah ada
/*
ALTER TABLE `users` 
ADD COLUMN `email` varchar(100) DEFAULT NULL AFTER `username`,
ADD UNIQUE KEY `users_email_unique` (`email`);
*/

-- ============================================
-- Insert Admin Default
-- ============================================
-- Catatan: Jika tabel users sudah ada tanpa kolom email,
-- jalankan terlebih dahulu file fix_users_table.sql

INSERT INTO `users` (`username`, `email`, `password`, `nama_lengkap`, `role`, `status`) 
VALUES ('admin', 'admin@rangkiangpedulinegeri.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 'active')
ON DUPLICATE KEY UPDATE 
    `email` = VALUES(`email`),
    `password` = VALUES(`password`),
    `nama_lengkap` = VALUES(`nama_lengkap`),
    `role` = VALUES(`role`),
    `status` = VALUES(`status`);

