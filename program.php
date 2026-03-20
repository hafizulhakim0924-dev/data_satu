<?php
// Simple and safe version of program.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Start output buffering
@ob_start();

require_once 'config.php';

/**
 * Cek kolom geo di program_csr
 */
function program_csr_geo_columns(PDO $pdo) {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    try {
        $c = $pdo->query("SHOW COLUMNS FROM program_csr")->fetchAll(PDO::FETCH_COLUMN);
        $cache = [
            'lat' => in_array('latitude', $c, true),
            'lng' => in_array('longitude', $c, true),
        ];
    } catch (Exception $e) {
        $cache = ['lat' => false, 'lng' => false];
    }
    return $cache;
}

/**
 * Koordinat perkiraan kota di Indonesia (untuk pin jika lat/lng DB kosong)
 */
function program_map_resolve_coords($kota, $provinsi) {
    $k = mb_strtolower(trim((string)$kota));
    $p = mb_strtolower(trim((string)$provinsi));
    $map = [
        'padang' => [-0.9492, 100.3543],
        'bukittinggi' => [-0.3056, 100.3692],
        'solok' => [-0.8006, 100.6567],
        'pariaman' => [-0.6267, 100.1208],
        'payakumbuh' => [-0.2206, 100.6331],
        'jakarta' => [-6.2088, 106.8456],
        'bandung' => [-6.9175, 107.6191],
        'surabaya' => [-7.2575, 112.7521],
        'medan' => [3.5952, 98.6722],
        'semarang' => [-6.9667, 110.4167],
        'yogyakarta' => [-7.7956, 110.3695],
        'makassar' => [-5.1477, 119.4327],
        'palembang' => [-2.9761, 104.7754],
        'manado' => [1.4748, 124.8421],
        'denpasar' => [-8.6705, 115.2126],
        'lombok' => [-8.5833, 116.1167],
        'mataram' => [-8.5833, 116.1167],
        'aceh' => [5.5483, 95.3238],
        'pekanbaru' => [0.5071, 101.4478],
        'batam' => [1.0456, 104.0305],
        'pontianak' => [-0.0263, 109.3425],
        'banjarmasin' => [-3.3194, 114.5908],
        'balikpapan' => [-1.2675, 116.8289],
    ];
    foreach ($map as $name => $coord) {
        if ($k !== '' && (strpos($k, $name) !== false || $k === $name)) {
            return $coord;
        }
    }
    // fallback provinsi kasar
    if (strpos($p, 'sumatera barat') !== false || $p === 'sumbar') {
        return [-0.7394, 100.8008];
    }
    if (strpos($p, 'jawa barat') !== false) {
        return [-6.8892, 107.6405];
    }
    if (strpos($p, 'jawa timur') !== false) {
        return [-7.5361, 112.2384];
    }
    if (strpos($p, 'sumatera utara') !== false) {
        return [3.5970, 98.6783];
    }
    // jitter agar kota tak dikenal tidak semua bertumpuk di titik sama
    $key = $k . '|' . $p;
    $h = crc32($key);
    $lat = -2.2 + (($h % 200) / 100 - 1) * 1.8;
    $lng = 114 + (($h % 300) / 100 - 0.5) * 12;
    return [$lat, $lng];
}

// Initialize variables
$error_msg = null;
$program_list = [];
$users = [];
$map_pins = [];
$program_has_geo_cols = false;
$tab_program = $_GET['tab'] ?? 'daftar';
if (!in_array($tab_program, ['daftar', 'peta'], true)) {
    $tab_program = 'daftar';
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add_program') {
        try {
            $geo = program_csr_geo_columns($pdo);
            $latIn = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
            $lngIn = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;

            if ($geo['lat'] && $geo['lng']) {
                $stmt = $pdo->prepare("INSERT INTO program_csr (nama_program, kategori, deskripsi, lokasi, kecamatan, kota, provinsi, tanggal_mulai, tanggal_selesai, budget, status, pic, jenis_bantuan, jumlah_bantuan, satuan, jumlah_penerima_manfaat, jumlah_relawan_terlibat, latitude, longitude) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([
                    $_POST['nama_program'] ?? '',
                    $_POST['kategori'] ?? '',
                    $_POST['deskripsi'] ?? '',
                    $_POST['lokasi'] ?? '',
                    $_POST['kecamatan'] ?? '',
                    $_POST['kota'] ?? '',
                    $_POST['provinsi'] ?? '',
                    $_POST['tanggal_mulai'] ?: null,
                    $_POST['tanggal_selesai'] ?: null,
                    $_POST['budget'] ?? 0,
                    $_POST['status'] ?? 'planning',
                    $_POST['pic'] ?: null,
                    $_POST['jenis_bantuan'] ?? '',
                    $_POST['jumlah_bantuan'] ?? 0,
                    $_POST['satuan'] ?? '',
                    $_POST['jumlah_penerima_manfaat'] ?? 0,
                    $_POST['jumlah_relawan_terlibat'] ?? 0,
                    $latIn,
                    $lngIn
                ]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO program_csr (nama_program, kategori, deskripsi, lokasi, kecamatan, kota, provinsi, tanggal_mulai, tanggal_selesai, budget, status, pic, jenis_bantuan, jumlah_bantuan, satuan, jumlah_penerima_manfaat, jumlah_relawan_terlibat) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([
                    $_POST['nama_program'] ?? '',
                    $_POST['kategori'] ?? '',
                    $_POST['deskripsi'] ?? '',
                    $_POST['lokasi'] ?? '',
                    $_POST['kecamatan'] ?? '',
                    $_POST['kota'] ?? '',
                    $_POST['provinsi'] ?? '',
                    $_POST['tanggal_mulai'] ?: null,
                    $_POST['tanggal_selesai'] ?: null,
                    $_POST['budget'] ?? 0,
                    $_POST['status'] ?? 'planning',
                    $_POST['pic'] ?: null,
                    $_POST['jenis_bantuan'] ?? '',
                    $_POST['jumlah_bantuan'] ?? 0,
                    $_POST['satuan'] ?? '',
                    $_POST['jumlah_penerima_manfaat'] ?? 0,
                    $_POST['jumlah_relawan_terlibat'] ?? 0
                ]);
            }
            @ob_end_clean();
            header("Location: program.php?msg=Program berhasil ditambahkan");
            exit;
        } catch(PDOException $e) {
            $error_msg = "Error: " . $e->getMessage();
        }
    }
    
    if ($action == 'update_program') {
        try {
            $id = $_POST['id'] ?? 0;
            $geo = program_csr_geo_columns($pdo);
            $latIn = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
            $lngIn = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;

            if ($geo['lat'] && $geo['lng']) {
                $stmt = $pdo->prepare("UPDATE program_csr SET nama_program=?, kategori=?, deskripsi=?, lokasi=?, kecamatan=?, kota=?, provinsi=?, tanggal_mulai=?, tanggal_selesai=?, budget=?, status=?, pic=?, jenis_bantuan=?, jumlah_bantuan=?, satuan=?, jumlah_penerima_manfaat=?, jumlah_relawan_terlibat=?, latitude=?, longitude=? WHERE id=?");
                $stmt->execute([
                    $_POST['nama_program'] ?? '',
                    $_POST['kategori'] ?? '',
                    $_POST['deskripsi'] ?? '',
                    $_POST['lokasi'] ?? '',
                    $_POST['kecamatan'] ?? '',
                    $_POST['kota'] ?? '',
                    $_POST['provinsi'] ?? '',
                    $_POST['tanggal_mulai'] ?: null,
                    $_POST['tanggal_selesai'] ?: null,
                    $_POST['budget'] ?? 0,
                    $_POST['status'] ?? 'planning',
                    $_POST['pic'] ?: null,
                    $_POST['jenis_bantuan'] ?? '',
                    $_POST['jumlah_bantuan'] ?? 0,
                    $_POST['satuan'] ?? '',
                    $_POST['jumlah_penerima_manfaat'] ?? 0,
                    $_POST['jumlah_relawan_terlibat'] ?? 0,
                    $latIn,
                    $lngIn,
                    $id
                ]);
            } else {
                $stmt = $pdo->prepare("UPDATE program_csr SET nama_program=?, kategori=?, deskripsi=?, lokasi=?, kecamatan=?, kota=?, provinsi=?, tanggal_mulai=?, tanggal_selesai=?, budget=?, status=?, pic=?, jenis_bantuan=?, jumlah_bantuan=?, satuan=?, jumlah_penerima_manfaat=?, jumlah_relawan_terlibat=? WHERE id=?");
                $stmt->execute([
                    $_POST['nama_program'] ?? '',
                    $_POST['kategori'] ?? '',
                    $_POST['deskripsi'] ?? '',
                    $_POST['lokasi'] ?? '',
                    $_POST['kecamatan'] ?? '',
                    $_POST['kota'] ?? '',
                    $_POST['provinsi'] ?? '',
                    $_POST['tanggal_mulai'] ?: null,
                    $_POST['tanggal_selesai'] ?: null,
                    $_POST['budget'] ?? 0,
                    $_POST['status'] ?? 'planning',
                    $_POST['pic'] ?: null,
                    $_POST['jenis_bantuan'] ?? '',
                    $_POST['jumlah_bantuan'] ?? 0,
                    $_POST['satuan'] ?? '',
                    $_POST['jumlah_penerima_manfaat'] ?? 0,
                    $_POST['jumlah_relawan_terlibat'] ?? 0,
                    $id
                ]);
            }
            @ob_end_clean();
            header("Location: program.php?msg=Program berhasil diupdate");
            exit;
        } catch(PDOException $e) {
            $error_msg = "Error: " . $e->getMessage();
        }
    }
    
    if ($action == 'delete_program') {
        try {
            $id = $_POST['id'] ?? 0;
            $stmt = $pdo->prepare("DELETE FROM program_csr WHERE id=?");
            $stmt->execute([$id]);
            @ob_end_clean();
            header("Location: program.php?msg=Program berhasil dihapus");
            exit;
        } catch(PDOException $e) {
            $error_msg = "Error: " . $e->getMessage();
        }
    }
}

// Load users
try {
    $users = $pdo->query("SELECT id, nama_lengkap FROM users WHERE role IN ('admin','manager') ORDER BY nama_lengkap")->fetchAll();
} catch(PDOException $e) {
    $users = [];
}

// Get filter parameters
$filter_status = $_GET['status'] ?? '';
$filter_capaian = $_GET['capaian'] ?? '';
$filter_kategori = $_GET['kategori'] ?? '';

// Load programs with filters
try {
    $table_check = $pdo->query("SHOW TABLES LIKE 'program_csr'")->fetch();
    if ($table_check) {
        // Build query with filters
        $where_conditions = [];
        $params = [];
        
        if ($filter_status) {
            $where_conditions[] = "p.status = ?";
            $params[] = $filter_status;
        }
        
        if ($filter_kategori) {
            $where_conditions[] = "p.kategori = ?";
            $params[] = $filter_kategori;
        }
        
        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
        
        // Get programs with progress/realisasi data
        $query = "
            SELECT p.*, 
                u.nama_lengkap as pic_name,
                COALESCE(p.progress, 0) as progress,
                COALESCE(p.realisasi_budget, 0) as realisasi_budget,
                COALESCE(p.budget, 0) as budget,
                CASE 
                    WHEN p.budget > 0 THEN (p.realisasi_budget / p.budget * 100)
                    ELSE 0
                END as capaian_persen
            FROM program_csr p 
            LEFT JOIN users u ON p.pic=u.id 
            $where_clause
        ";
        
        // Add ordering based on capaian filter
        if ($filter_capaian == 'tertinggi') {
            $query .= " ORDER BY capaian_persen DESC, p.progress DESC, p.tanggal_mulai DESC";
        } elseif ($filter_capaian == 'terendah') {
            $query .= " ORDER BY capaian_persen ASC, p.progress ASC, p.tanggal_mulai DESC";
        } else {
            $query .= " ORDER BY p.tanggal_mulai DESC";
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $program_list = $stmt->fetchAll();
        
        // Get statistics
        $stats = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status='planning' THEN 1 ELSE 0 END) as planning,
                SUM(CASE WHEN status='ongoing' THEN 1 ELSE 0 END) as ongoing,
                SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled,
                AVG(COALESCE(progress, 0)) as avg_progress
            FROM program_csr
        ")->fetch();

        $geoFlags = program_csr_geo_columns($pdo);
        $program_has_geo_cols = !empty($geoFlags['lat']) && !empty($geoFlags['lng']);

        // Data peta: agregasi per kota (bantuan RPN per wilayah)
        try {
            $geo = program_csr_geo_columns($pdo);
            $sqlMap = "
                SELECT 
                    TRIM(COALESCE(kota, '')) AS kota,
                    TRIM(COALESCE(provinsi, '')) AS provinsi,
                    COUNT(*) AS jumlah_program,
                    SUM(COALESCE(jumlah_penerima_manfaat, 0)) AS total_penerima,
                    SUBSTRING(
                        GROUP_CONCAT(DISTINCT nama_program ORDER BY nama_program SEPARATOR ' • '),
                        1,
                        400
                    ) AS contoh_nama
            ";
            if ($geo['lat'] && $geo['lng']) {
                $sqlMap .= ",
                    AVG(NULLIF(latitude, 0)) AS lat,
                    AVG(NULLIF(longitude, 0)) AS lng
                ";
            }
            $sqlMap .= "
                FROM program_csr
                WHERE kota IS NOT NULL AND TRIM(kota) != ''
                GROUP BY TRIM(kota), TRIM(COALESCE(provinsi, ''))
                ORDER BY jumlah_program DESC, kota ASC
            ";
            $agg = $pdo->query($sqlMap)->fetchAll(PDO::FETCH_ASSOC);
            $map_pins = [];
            foreach ($agg as $row) {
                $lat = isset($row['lat']) ? (float)$row['lat'] : null;
                $lng = isset($row['lng']) ? (float)$row['lng'] : null;
                if ($lat === null || $lng === null || abs($lat) < 0.0001 || abs($lng) < 0.0001) {
                    list($lat, $lng) = program_map_resolve_coords($row['kota'], $row['provinsi']);
                }
                $map_pins[] = [
                    'kota' => $row['kota'],
                    'provinsi' => $row['provinsi'],
                    'jumlah_program' => (int)$row['jumlah_program'],
                    'total_penerima' => (int)$row['total_penerima'],
                    'contoh_nama' => $row['contoh_nama'] ?? '',
                    'lat' => round($lat, 6),
                    'lng' => round($lng, 6),
                ];
            }
        } catch (PDOException $e) {
            $map_pins = [];
        }
    } else {
        $error_msg = "Tabel 'program_csr' belum ada. Jalankan fix_all_program_tables.sql";
        $stats = null;
    }
} catch(PDOException $e) {
    $program_list = [];
    $error_msg = "Error loading programs: " . $e->getMessage();
    $stats = null;
}

// Get edit program
$edit_id = $_GET['edit'] ?? null;
$edit_program = null;
if ($edit_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM program_csr WHERE id=?");
        $stmt->execute([$edit_id]);
        $edit_program = $stmt->fetch();
    } catch(PDOException $e) {
        $edit_program = null;
    }
}

// Get view program
$view_id = $_GET['view'] ?? null;
$view_program = null;
if ($view_id) {
    try {
        $stmt = $pdo->prepare("SELECT p.*, u.nama_lengkap as pic_name FROM program_csr p LEFT JOIN users u ON p.pic=u.id WHERE p.id=?");
        $stmt->execute([$view_id]);
        $view_program = $stmt->fetch();
    } catch(PDOException $e) {
        $view_program = null;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manajemen Program CSR</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= getCssLink() ?>
    <?php if (!$view_program && $tab_program === 'peta'): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" crossorigin="anonymous">
    <?php endif; ?>
    <style>
        /* Tinggi eksplisit wajib agar Leaflet bisa menggambar tile */
        #mapProgramIndonesia {
            height: 480px;
            width: 100%;
            min-height: 320px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: #e8eef5;
        }
        #mapProgramIndonesia .leaflet-container {
            height: 100%;
            width: 100%;
            font-family: inherit;
        }
        .map-legend { font-size: 12px; color: var(--light-text); margin-top: 8px; }
    </style>
</head>
<body>

<div class="navbar">
    <h1>🏛️ Rangkiang Peduli Negeri</h1>
    <?= getNavMenu() ?>
</div>

<div class="container">
    <div class="card-header">
        <h1 style="margin:0">📋 Manajemen Program CSR</h1>
        <button class="btn btn-success" onclick="openModal('add')">+ Tambah Program</button>
    </div>

    <?php if (!$view_program): ?>
    <div class="tabs" style="margin-bottom: 14px;">
        <a href="program.php?tab=daftar" class="<?= ($tab_program === 'daftar') ? 'active' : '' ?>">📋 Daftar</a>
        <a href="program.php?tab=peta" class="<?= ($tab_program === 'peta') ? 'active' : '' ?>">🗺️ Peta Indonesia</a>
    </div>
    <?php endif; ?>
    
    <?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    
    <?php if($error_msg): ?>
    <div class="alert alert-error">
        <strong>⚠️ Error:</strong> <?= htmlspecialchars($error_msg) ?>
    </div>
    <?php endif; ?>
    
    <?php if($view_program): ?>
    <!-- Detail Program View -->
    <div style="margin-bottom:20px">
        <a href="program.php" class="btn" style="margin-bottom:15px">← Kembali ke Daftar</a>
        <div class="detail-section">
            <h3>📋 Informasi Program</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Nama Program:</label>
                    <span><?= htmlspecialchars($view_program['nama_program']) ?></span>
                </div>
                <div class="detail-item">
                    <label>Kategori:</label>
                    <span><?= htmlspecialchars($view_program['kategori'] ?? '-') ?></span>
                </div>
                <div class="detail-item">
                    <label>Status:</label>
                    <span class="badge badge-<?= $view_program['status'] ?>"><?= ucfirst($view_program['status']) ?></span>
                </div>
                <div class="detail-item">
                    <label>PIC:</label>
                    <span><?= htmlspecialchars($view_program['pic_name'] ?? '-') ?></span>
                </div>
            </div>
            <?php if($view_program['deskripsi']): ?>
            <div class="detail-item" style="margin-top:15px">
                <label>Deskripsi:</label>
                <span><?= nl2br(htmlspecialchars($view_program['deskripsi'])) ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="detail-section">
            <h3>📍 Informasi Lokasi</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Tanggal / Hari:</label>
                    <span><?= $view_program['tanggal_mulai'] ? date('d/m/Y', strtotime($view_program['tanggal_mulai'])) : '-' ?></span>
                </div>
                <div class="detail-item">
                    <label>Lokasi:</label>
                    <span><?= htmlspecialchars($view_program['lokasi'] ?? '-') ?></span>
                </div>
                <div class="detail-item">
                    <label>Kecamatan:</label>
                    <span><?= htmlspecialchars($view_program['kecamatan'] ?? '-') ?></span>
                </div>
                <div class="detail-item">
                    <label>Kota:</label>
                    <span><?= htmlspecialchars($view_program['kota'] ?? '-') ?></span>
                </div>
                <div class="detail-item">
                    <label>Provinsi:</label>
                    <span><?= htmlspecialchars($view_program['provinsi'] ?? '-') ?></span>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
            <h3>💼 Informasi Bantuan</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Jenis Bantuan:</label>
                    <span><?= htmlspecialchars($view_program['jenis_bantuan'] ?? '-') ?></span>
                </div>
                <div class="detail-item">
                    <label>Jumlah Bantuan:</label>
                    <span><?= number_format($view_program['jumlah_bantuan'] ?? 0, 0, ',', '.') ?> <?= htmlspecialchars($view_program['satuan'] ?? '') ?></span>
                </div>
                <div class="detail-item">
                    <label>Jumlah Penerima Manfaat:</label>
                    <span><?= number_format($view_program['jumlah_penerima_manfaat'] ?? 0, 0, ',', '.') ?> orang</span>
                </div>
                <div class="detail-item">
                    <label>Jumlah Relawan Terlibat:</label>
                    <span><?= number_format($view_program['jumlah_relawan_terlibat'] ?? 0, 0, ',', '.') ?> orang</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
            <h3>💰 Informasi Keuangan</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Budget:</label>
                    <span><?= formatRupiah($view_program['budget'] ?? 0) ?></span>
                </div>
                <div class="detail-item">
                    <label>Realisasi Budget:</label>
                    <span><?= formatRupiah($view_program['realisasi_budget'] ?? 0) ?></span>
                </div>
                <div class="detail-item">
                    <label>Progress:</label>
                    <span><?= $view_program['progress'] ?? 0 ?>%</span>
                </div>
                <div class="detail-item">
                    <label>Tanggal Selesai:</label>
                    <span><?= $view_program['tanggal_selesai'] ? date('d/m/Y', strtotime($view_program['tanggal_selesai'])) : '-' ?></span>
                </div>
            </div>
        </div>
        
        <div style="margin-top:20px">
            <a href="?edit=<?= $view_program['id'] ?>" class="btn btn-success">Edit Program</a>
        </div>
    </div>
    <?php elseif ($tab_program === 'peta'): ?>
    <div class="card">
        <div class="card-header">
            <h3 style="margin:0">Sebaran bantuan RPN per kota</h3>
        </div>
        <p class="map-legend">📍 Pin lokasi dari data <strong>Kota</strong> program (satu pin per kota). Angka di pin = jumlah program di kota itu. Isi <strong>Latitude / Longitude</strong> untuk penempatan lebih akurat (jika kolom tersedia di database).</p>
        <div id="mapProgramIndonesia"></div>
        <?php if (empty($map_pins)): ?>
            <p style="margin-top:12px; color:var(--light-text);">Belum ada program dengan kolom kota terisi. Tambah program dan isi kota untuk menampilkan pin.</p>
        <?php endif; ?>
    </div>
    <?php else: ?>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Program</th>
                <th>Kategori</th>
                <th>Lokasi</th>
                <th>Tanggal Mulai</th>
                <th>Tanggal Selesai</th>
                <th>Budget</th>
                <th>Status</th>
                <th>PIC</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($program_list)): ?>
            <tr>
                <td colspan="10" style="text-align:center; padding:40px; color:#999">
                    Belum ada program. Klik "Tambah Program" untuk menambahkan.
                </td>
            </tr>
            <?php else: ?>
            <?php foreach($program_list as $idx => $p): ?>
            <tr>
                <td><?= $idx + 1 ?></td>
                <td><strong><?= htmlspecialchars($p['nama_program']) ?></strong></td>
                <td><?= htmlspecialchars($p['kategori'] ?? '-') ?></td>
                <td><?= htmlspecialchars($p['lokasi'] ?? '-') ?></td>
                <td><?= $p['tanggal_mulai'] ? date('d/m/Y', strtotime($p['tanggal_mulai'])) : '-' ?></td>
                <td><?= $p['tanggal_selesai'] ? date('d/m/Y', strtotime($p['tanggal_selesai'])) : '-' ?></td>
                <td><?= formatRupiah($p['budget'] ?? 0) ?></td>
                <td>
                    <span class="badge badge-<?= $p['status'] ?>">
                        <?= ucfirst($p['status']) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($p['pic_name'] ?? '-') ?></td>
                <td>
                    <a href="?view=<?= $p['id'] ?>" class="btn" style="padding:5px 10px; font-size:12px; background:#17a2b8">Detail</a>
                    <a href="?edit=<?= $p['id'] ?>" class="btn" style="padding:5px 10px; font-size:12px">Edit</a>
                    <button class="btn btn-danger" style="padding:5px 10px; font-size:12px" onclick="deleteProgram(<?= $p['id'] ?>)">Hapus</button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Modal Add/Edit -->
<div id="modalProgram" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2><?= $edit_program ? 'Edit' : 'Tambah' ?> Program</h2>
        <form method="POST" action="program.php">
            <input type="hidden" name="action" value="<?= $edit_program ? 'update_program' : 'add_program' ?>">
            <?php if($edit_program): ?>
            <input type="hidden" name="id" value="<?= $edit_program['id'] ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Nama Program *</label>
                <input type="text" name="nama_program" value="<?= htmlspecialchars($edit_program['nama_program'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Kategori</label>
                <select name="kategori">
                    <option value="">Pilih Kategori</option>
                    <option value="pendidikan" <?= ($edit_program['kategori'] ?? '') == 'pendidikan' ? 'selected' : '' ?>>Pendidikan</option>
                    <option value="kesehatan" <?= ($edit_program['kategori'] ?? '') == 'kesehatan' ? 'selected' : '' ?>>Kesehatan</option>
                    <option value="sosial" <?= ($edit_program['kategori'] ?? '') == 'sosial' ? 'selected' : '' ?>>Sosial</option>
                    <option value="lingkungan" <?= ($edit_program['kategori'] ?? '') == 'lingkungan' ? 'selected' : '' ?>>Lingkungan</option>
                    <option value="ekonomi" <?= ($edit_program['kategori'] ?? '') == 'ekonomi' ? 'selected' : '' ?>>Ekonomi</option>
                    <option value="bencana" <?= ($edit_program['kategori'] ?? '') == 'bencana' ? 'selected' : '' ?>>Bencana</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="deskripsi"><?= htmlspecialchars($edit_program['deskripsi'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Lokasi</label>
                <input type="text" name="lokasi" value="<?= htmlspecialchars($edit_program['lokasi'] ?? '') ?>" placeholder="Alamat lengkap">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Kecamatan</label>
                    <input type="text" name="kecamatan" value="<?= htmlspecialchars($edit_program['kecamatan'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>Kota</label>
                    <input type="text" name="kota" value="<?= htmlspecialchars($edit_program['kota'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>Provinsi</label>
                <input type="text" name="provinsi" value="<?= htmlspecialchars($edit_program['provinsi'] ?? 'Sumatera Barat') ?>" placeholder="Sumatera Barat">
            </div>

            <?php if ($program_has_geo_cols): ?>
            <div class="form-row">
                <div class="form-group">
                    <label>Latitude (pin peta, opsional)</label>
                    <input type="text" name="latitude" value="<?= htmlspecialchars($edit_program['latitude'] ?? '') ?>" placeholder="-0.94924">
                </div>
                <div class="form-group">
                    <label>Longitude</label>
                    <input type="text" name="longitude" value="<?= htmlspecialchars($edit_program['longitude'] ?? '') ?>" placeholder="100.35427">
                </div>
            </div>
            <?php else: ?>
            <p style="font-size:12px; color:var(--light-text); margin-bottom:12px;">Untuk pin manual di peta, pastikan tabel <code>program_csr</code> memiliki kolom <code>latitude</code> dan <code>longitude</code> (lihat <code>create_program_csr_with_details.sql</code>).</p>
            <?php endif; ?>
            
            <div class="form-group">
                <label>Jenis Bantuan</label>
                <input type="text" name="jenis_bantuan" value="<?= htmlspecialchars($edit_program['jenis_bantuan'] ?? '') ?>" placeholder="Contoh: Bantuan Sembako, Bantuan Pendidikan, dll">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Jumlah Bantuan</label>
                    <input type="number" name="jumlah_bantuan" step="0.01" value="<?= $edit_program['jumlah_bantuan'] ?? 0 ?>" min="0">
                </div>
                
                <div class="form-group">
                    <label>Satuan</label>
                    <input type="text" name="satuan" value="<?= htmlspecialchars($edit_program['satuan'] ?? '') ?>" placeholder="Contoh: Paket, Unit, Liter, dll">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Jumlah Penerima Manfaat</label>
                    <input type="number" name="jumlah_penerima_manfaat" value="<?= $edit_program['jumlah_penerima_manfaat'] ?? 0 ?>" min="0" placeholder="Jumlah orang yang menerima manfaat">
                </div>
                
                <div class="form-group">
                    <label>Jumlah Relawan Terlibat</label>
                    <input type="number" name="jumlah_relawan_terlibat" value="<?= $edit_program['jumlah_relawan_terlibat'] ?? 0 ?>" min="0" placeholder="Jumlah relawan yang terlibat">
                </div>
            </div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px">
                <div class="form-group">
                    <label>Tanggal / Hari *</label>
                    <input type="date" name="tanggal_mulai" value="<?= $edit_program['tanggal_mulai'] ?? '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" value="<?= $edit_program['tanggal_selesai'] ?? '' ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>Budget</label>
                <input type="number" name="budget" step="0.01" value="<?= $edit_program['budget'] ?? 0 ?>" min="0">
            </div>
            
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="planning" <?= ($edit_program['status'] ?? 'planning') == 'planning' ? 'selected' : '' ?>>Planning</option>
                    <option value="ongoing" <?= ($edit_program['status'] ?? '') == 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                    <option value="completed" <?= ($edit_program['status'] ?? '') == 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= ($edit_program['status'] ?? '') == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>PIC</label>
                <select name="pic">
                    <option value="">Pilih PIC</option>
                    <?php foreach($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= ($edit_program['pic'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['nama_lengkap']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="text-right" style="margin-top:20px">
                <button type="button" class="btn" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn btn-success">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php if (!$view_program && $tab_program === 'peta'): ?>
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js" crossorigin="anonymous"></script>
<?php endif; ?>

<script>
function openModal(type) {
    document.getElementById('modalProgram').style.display = 'block';
}

function closeModal() {
    document.getElementById('modalProgram').style.display = 'none';
    <?php if($edit_program): ?>
    window.location.href = 'program.php';
    <?php endif; ?>
}

function deleteProgram(id) {
    if (confirm('Yakin ingin menghapus program ini?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'program.php';
        
        const action = document.createElement('input');
        action.type = 'hidden';
        action.name = 'action';
        action.value = 'delete_program';
        form.appendChild(action);
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;
        form.appendChild(idInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Open modal if editing (jangan pakai window.onload — bisa bentrok dan mengganggu init peta)
<?php if($edit_program): ?>
window.addEventListener('load', function() {
    openModal('edit');
});
<?php endif; ?>

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('modalProgram');
    if (modal && event.target === modal) {
        closeModal();
    }
});

<?php if (!$view_program && $tab_program === 'peta'): ?>
(function() {
    var pins = <?= json_encode($map_pins, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    function esc(s) {
        if (!s) return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
    function addTiles(map) {
        // OpenStreetMap — sering lebih andal di jaringan Indonesia dibanding tile CDN tertentu
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
    }
    /** Pin oranye (SVG) + badge jumlah program */
    function makeProgramPinIcon(jumlahProgram) {
        var n = Math.max(1, parseInt(jumlahProgram, 10) || 1);
        var badge = n > 1 ? ('<span class="rpn-map-pin-badge">' + (n > 99 ? '99+' : n) + '</span>') : '';
        var svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 48" width="34" height="46" aria-hidden="true">'
            + '<path fill="#ff7a00" stroke="#c55a00" stroke-width="1.2" d="M18 2C10.3 2 4 8.3 4 16c0 11.5 11.2 24.5 13.3 27 0.4 0.5 1 0.5 1.4 0C20.8 40.5 32 27.5 32 16 32 8.3 25.7 2 18 2z"/>'
            + '<circle fill="#fff" cx="18" cy="16" r="5.5"/>'
            + '<circle fill="#ff7a00" cx="18" cy="16" r="2.2"/>'
            + '</svg>';
        return L.divIcon({
            className: 'rpn-map-pin-outer',
            html: '<div class="rpn-map-pin">' + svg + badge + '</div>',
            iconSize: [36, 48],
            iconAnchor: [18, 48],
            popupAnchor: [0, -46]
        });
    }
    function initMapProgram() {
        var el = document.getElementById('mapProgramIndonesia');
        if (!el) return;
        if (typeof L === 'undefined') {
            el.innerHTML = '<p style="padding:24px;color:#c00;">Peta gagal dimuat. Periksa koneksi internet atau blokir script CDN (Leaflet).</p>';
            return;
        }
        var map = L.map(el, { zoomControl: true }).setView([-2.5, 118.0], 5);
        addTiles(map);
        if (pins && pins.length) {
            var bounds = [];
            pins.forEach(function(p) {
                var lat = parseFloat(p.lat);
                var lng = parseFloat(p.lng);
                if (isNaN(lat) || isNaN(lng)) return;
                var mk = L.marker([lat, lng], {
                    icon: makeProgramPinIcon(p.jumlah_program)
                }).addTo(map);
                var html = '<div style="min-width:200px"><strong>' + esc(p.kota) + '</strong><br>' +
                    esc(p.provinsi) + '<br><hr style="margin:8px 0;border:none;border-top:1px solid #eee">' +
                    '<b>' + (p.jumlah_program || 0) + '</b> program bantuan<br>' +
                    '<b>' + (p.total_penerima || 0).toLocaleString('id-ID') + '</b> penerima manfaat (jumlah)';
                if (p.contoh_nama) {
                    html += '<br><small style="color:#666">' + esc(p.contoh_nama) + '</small>';
                }
                html += '</div>';
                mk.bindPopup(html);
                bounds.push([lat, lng]);
            });
            if (bounds.length > 0) {
                try {
                    map.fitBounds(bounds, { padding: [48, 48], maxZoom: 10 });
                } catch (e) {}
            }
        }
        function fixSize() {
            try { map.invalidateSize(true); } catch (e) {}
        }
        window.addEventListener('resize', fixSize);
        // Setelah window load + paint: ukuran container final, tile/img CSS sudah diterapkan
        setTimeout(fixSize, 50);
        setTimeout(fixSize, 300);
        setTimeout(fixSize, 800);
    }
    if (document.readyState === 'complete') {
        initMapProgram();
    } else {
        window.addEventListener('load', initMapProgram);
    }
})();
<?php endif; ?>
</script>

</body>
</html>
<?php
@ob_end_flush();
?>
