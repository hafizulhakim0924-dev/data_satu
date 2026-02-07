<?php
require_once 'config.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add_program') {
        $stmt = $pdo->prepare("INSERT INTO program_csr (nama_program, kategori, deskripsi, lokasi, latitude, longitude, tanggal_mulai, tanggal_selesai, budget, status, pic) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$_POST['nama_program'], $_POST['kategori'], $_POST['deskripsi'], $_POST['lokasi'], $_POST['latitude'] ?: null, $_POST['longitude'] ?: null, $_POST['tanggal_mulai'], $_POST['tanggal_selesai'], $_POST['budget'], 'planning', $_POST['pic'] ?: null]);
        header("Location: program.php?msg=Program berhasil ditambahkan");
        exit;
    }
    
    if ($action == 'update_program') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE program_csr SET nama_program=?, kategori=?, deskripsi=?, lokasi=?, latitude=?, longitude=?, tanggal_mulai=?, tanggal_selesai=?, budget=?, status=?, pic=? WHERE id=?");
        $stmt->execute([$_POST['nama_program'], $_POST['kategori'], $_POST['deskripsi'], $_POST['lokasi'], $_POST['latitude'] ?: null, $_POST['longitude'] ?: null, $_POST['tanggal_mulai'], $_POST['tanggal_selesai'], $_POST['budget'], $_POST['status'], $_POST['pic'] ?: null, $id]);
        header("Location: program.php?msg=Program berhasil diupdate");
        exit;
    }
    
    if ($action == 'save_map_pin') {
        header('Content-Type: application/json');
        $id = $_POST['program_id'];
        $lat = $_POST['latitude'];
        $lng = $_POST['longitude'];
        try {
            $stmt = $pdo->prepare("UPDATE program_csr SET latitude=?, longitude=? WHERE id=?");
            $stmt->execute([$lat, $lng, $id]);
            echo json_encode(['success' => true]);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}

try {
    $users = $pdo->query("SELECT id, nama_lengkap FROM users WHERE role IN ('admin','manager') ORDER BY nama_lengkap")->fetchAll();
} catch(PDOException $e) {
    $users = [];
}

try {
    // Cek apakah kolom 'program' ada di tabel csr_donations
    $check_column = $pdo->query("SHOW COLUMNS FROM csr_donations LIKE 'program'")->fetch();
    
    if ($check_column) {
        // Kolom ada, gunakan query normal
        $program_list = $pdo->query("SELECT p.*, 
            u.nama_lengkap as pic_name,
            (SELECT SUM(jumlah) FROM csr_donations WHERE program=p.nama_program) as total_donasi,
            (SELECT SUM(jumlah_penyaluran) FROM program_penyaluran WHERE program_id=p.id) as total_penyaluran,
            COALESCE(p.progress, 0) as progress
            FROM program_csr p 
            LEFT JOIN users u ON p.pic=u.id 
            ORDER BY p.tanggal_mulai DESC")->fetchAll();
    } else {
        // Kolom belum ada, gunakan query tanpa subquery program
        $program_list = $pdo->query("SELECT p.*, 
            u.nama_lengkap as pic_name,
            0 as total_donasi
            FROM program_csr p 
            LEFT JOIN users u ON p.pic=u.id 
            ORDER BY p.tanggal_mulai DESC")->fetchAll();
        $error_msg = "Kolom 'program' belum ada di tabel csr_donations. Jalankan script fix_csr_donations.sql untuk menambahkan kolom.";
    }
} catch(PDOException $e) {
    $program_list = [];
    $error_msg = "Error: " . $e->getMessage();
}

$edit_id = $_GET['edit'] ?? null;
$edit_program = null;
if ($edit_id) {
    $edit_program = $pdo->prepare("SELECT * FROM program_csr WHERE id=?");
    $edit_program->execute([$edit_id]);
    $edit_program = $edit_program->fetch();
}

// Handle View Detail Program
$view_id = $_GET['view'] ?? null;
$view_program = null;
$view_penyaluran = [];
$view_dampak = [];
$view_stats = [];

if ($view_id) {
    try {
        // Get program detail
        $view_program = $pdo->prepare("
            SELECT p.*, 
                u.nama_lengkap as pic_name,
                (SELECT SUM(jumlah) FROM csr_donations WHERE program=p.nama_program) as total_donasi,
                (SELECT SUM(jumlah_penyaluran) FROM program_penyaluran WHERE program_id=p.id) as total_penyaluran,
                (SELECT COUNT(*) FROM program_penyaluran WHERE program_id=p.id) as jumlah_penyaluran,
                (SELECT COUNT(*) FROM program_dampak WHERE program_id=p.id) as jumlah_dampak
            FROM program_csr p 
            LEFT JOIN users u ON p.pic=u.id 
            WHERE p.id=?
        ");
        $view_program->execute([$view_id]);
        $view_program = $view_program->fetch();
        
        if ($view_program) {
            // Get penyaluran data
            $view_penyaluran = $pdo->prepare("
                SELECT * FROM program_penyaluran 
                WHERE program_id = ? 
                ORDER BY tanggal_penyaluran DESC
            ");
            $view_penyaluran->execute([$view_id]);
            $view_penyaluran = $view_penyaluran->fetchAll();
            
            // Get dampak data
            $view_dampak = $pdo->prepare("
                SELECT * FROM program_dampak 
                WHERE program_id = ? 
                ORDER BY tanggal_pengukuran DESC
            ");
            $view_dampak->execute([$view_id]);
            $view_dampak = $view_dampak->fetchAll();
            
            // Get penyaluran by month for chart
            $penyaluran_by_month = $pdo->prepare("
                SELECT 
                    DATE_FORMAT(tanggal_penyaluran, '%Y-%m') as bulan,
                    DATE_FORMAT(tanggal_penyaluran, '%M %Y') as bulan_label,
                    COUNT(*) as jumlah,
                    SUM(jumlah_penyaluran) as total
                FROM program_penyaluran
                WHERE program_id = ?
                GROUP BY DATE_FORMAT(tanggal_penyaluran, '%Y-%m'), DATE_FORMAT(tanggal_penyaluran, '%M %Y')
                ORDER BY bulan ASC
            ");
            $penyaluran_by_month->execute([$view_id]);
            $penyaluran_by_month = $penyaluran_by_month->fetchAll();
            
            // Get dampak by kategori for chart
            $dampak_by_kategori = $pdo->prepare("
                SELECT 
                    kategori_dampak,
                    COUNT(*) as jumlah,
                    AVG(nilai) as rata_rata
                FROM program_dampak
                WHERE program_id = ?
                GROUP BY kategori_dampak
            ");
            $dampak_by_kategori->execute([$view_id]);
            $dampak_by_kategori = $dampak_by_kategori->fetchAll();
            
            // Get top indikator for chart
            $top_indikator = $pdo->prepare("
                SELECT 
                    indikator,
                    COUNT(*) as jumlah_pengukuran,
                    AVG(nilai) as rata_rata_nilai,
                    MAX(nilai) as nilai_max,
                    MIN(nilai) as nilai_min
                FROM program_dampak
                WHERE program_id = ?
                GROUP BY indikator
                ORDER BY jumlah_pengukuran DESC
                LIMIT 10
            ");
            $top_indikator->execute([$view_id]);
            $top_indikator = $top_indikator->fetchAll();
        }
    } catch(PDOException $e) {
        $view_program = null;
        $error_msg = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Program CSR - Rangkiang Peduli Negeri</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
* { margin:0; padding:0; box-sizing:border-box }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f4f6f8 }
.navbar { background:#2c3e50; color:#fff; padding:15px 20px; box-shadow:0 2px 5px rgba(0,0,0,0.1) }
.navbar h1 { display:inline-block; margin-right:30px; font-size:20px }
.nav-menu { display:inline-block; vertical-align:middle }
.nav-menu a { color:#fff; text-decoration:none; padding:10px 15px; margin:0 5px; border-radius:5px; display:inline-block; transition:background 0.3s }
.nav-menu a:hover, .nav-menu a.active { background:#34495e }
.container { max-width:1400px; margin:20px auto; padding:0 20px }
.card { background:#fff; padding:20px; border-radius:8px; margin-bottom:15px; box-shadow:0 2px 4px rgba(0,0,0,0.1) }
.grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(300px,1fr)); gap:15px }
.btn { display:inline-block; padding:10px 20px; background:#3498db; color:#fff; text-decoration:none; border-radius:5px; border:none; cursor:pointer; margin:5px }
.btn:hover { background:#2980b9 }
.btn-success { background:#27ae60 }
.btn-danger { background:#e74c3c }
.btn-warning { background:#f39c12 }
.btn-sm { padding:5px 10px; font-size:12px }
table { width:100%; border-collapse:collapse; margin-top:15px }
table th, table td { padding:12px; text-align:left; border-bottom:1px solid #ddd }
table th { background:#34495e; color:#fff; font-weight:600 }
table tr:hover { background:#f5f5f5 }
.form-group { margin-bottom:15px }
.form-group label { display:block; margin-bottom:5px; font-weight:600; color:#2c3e50 }
.form-group input, .form-group select, .form-group textarea { width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; font-size:14px }
.form-group textarea { min-height:100px; resize:vertical }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:15px }
.modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5) }
.modal-content { background:#fff; margin:5% auto; padding:20px; border-radius:8px; width:90%; max-width:900px; max-height:90vh; overflow-y:auto }
.close { float:right; font-size:28px; font-weight:bold; cursor:pointer }
.alert { padding:15px; margin-bottom:20px; border-radius:5px }
.alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb }
.badge { padding:5px 10px; border-radius:3px; font-size:12px; font-weight:600; display:inline-block }
.badge-planning { background:#95a5a6; color:#fff }
.badge-ongoing { background:#3498db; color:#fff }
.badge-completed { background:#27ae60; color:#fff }
.badge-cancelled { background:#e74c3c; color:#fff }
</style>
</head>
<body>

<div class="navbar">
    <h1>🏛️ Rangkiang Peduli Negeri</h1>
    <?= getNavMenu() ?>
</div>

<div class="container">
    <h1 style="margin:20px 0">📋 Manajemen Program CSR</h1>
    
    <?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    
    <?php if(isset($error_msg)): ?>
    <div class="alert" style="background:#f8d7da; color:#721c24; border:1px solid #f5c6cb">
        <strong>⚠️ Error Database:</strong> <?= htmlspecialchars($error_msg) ?>
        <br><small>Pastikan tabel 'program_csr' sudah dibuat. Jalankan file database_schema.sql terlebih dahulu.</small>
        <?php if(strpos($error_msg, 'program') !== false): ?>
        <br><br><strong>Solusi:</strong> Jalankan file <code>fix_csr_donations_safe.sql</code> untuk menambahkan kolom 'program' ke tabel csr_donations.
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php if($view_program): ?>
    <!-- Detail Program View -->
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <h1><?= htmlspecialchars($view_program['nama_program']) ?></h1>
            <a href="program.php" class="btn">← Kembali ke Daftar</a>
        </div>
        
        <div class="grid" style="margin-bottom:20px">
            <div class="card">
                <h3>📊 Progress Program</h3>
                <?php 
                $progress = (int)($view_program['progress'] ?? 0);
                $progress_color = $progress < 30 ? '#e74c3c' : ($progress < 70 ? '#f39c12' : '#27ae60');
                $budget = (float)($view_program['budget'] ?? 0);
                $realisasi = (float)($view_program['realisasi_budget'] ?? 0);
                $realisasi_persen = $budget > 0 ? ($realisasi / $budget * 100) : 0;
                ?>
                <div style="margin-top:15px">
                    <div style="background:#e8e8e8; border-radius:10px; height:40px; position:relative; overflow:hidden; box-shadow:inset 0 2px 5px rgba(0,0,0,0.1)">
                        <div style="background:<?= $progress_color ?>; height:100%; width:<?= $progress ?>%; transition:width 0.3s; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 5px rgba(0,0,0,0.2)">
                            <span style="color:#fff; font-size:16px; font-weight:bold; text-shadow:0 1px 3px rgba(0,0,0,0.3)"><?= $progress ?>%</span>
                        </div>
                    </div>
                    <div style="margin-top:10px; display:flex; justify-content:space-between">
                        <small style="color:#666">Progress: <?= $progress ?>%</small>
                        <small style="color:#666">Realisasi: <?= formatRupiah($realisasi) ?> (<?= number_format($realisasi_persen, 1) ?>%)</small>
                    </div>
                </div>
            </div>
            <div class="card">
                <h3>💰 Budget</h3>
                <div style="font-size:24px; font-weight:bold; color:#2c3e50; margin-top:10px">
                    <?= formatRupiah($budget) ?>
                </div>
                <div style="margin-top:10px">
                    <small style="color:#666">Realisasi: <?= formatRupiah($realisasi) ?></small><br>
                    <small style="color:#666">Sisa: <?= formatRupiah($budget - $realisasi) ?></small>
                </div>
            </div>
            <div class="card">
                <h3>📈 Statistik</h3>
                <div style="margin-top:10px">
                    <div style="margin-bottom:8px">
                        <strong>Total Donasi:</strong><br>
                        <span style="color:#27ae60; font-size:18px"><?= formatRupiah($view_program['total_donasi'] ?? 0) ?></span>
                    </div>
                    <div style="margin-bottom:8px">
                        <strong>Total Penyaluran:</strong><br>
                        <span style="color:#3498db; font-size:18px"><?= formatRupiah($view_program['total_penyaluran'] ?? 0) ?></span>
                    </div>
                    <div>
                        <strong>Jumlah Penyaluran:</strong> <?= number_format($view_program['jumlah_penyaluran'] ?? 0) ?>x<br>
                        <strong>Jumlah Dampak:</strong> <?= number_format($view_program['jumlah_dampak'] ?? 0) ?>x
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card" style="margin-top:20px">
            <h3>📋 Informasi Program</h3>
            <div class="grid" style="grid-template-columns:1fr 1fr; margin-top:15px">
                <div>
                    <strong>Kategori:</strong> <?= ucfirst($view_program['kategori']) ?><br>
                    <strong>Status:</strong> 
                    <span class="badge badge-<?= $view_program['status'] ?>"><?= ucfirst($view_program['status']) ?></span><br>
                    <strong>Lokasi:</strong> <?= htmlspecialchars($view_program['lokasi'] ?? '-') ?><br>
                    <strong>PIC:</strong> <?= htmlspecialchars($view_program['pic_name'] ?? '-') ?>
                </div>
                <div>
                    <strong>Tanggal Mulai:</strong> <?= $view_program['tanggal_mulai'] ? date('d/m/Y', strtotime($view_program['tanggal_mulai'])) : '-' ?><br>
                    <strong>Tanggal Selesai:</strong> <?= $view_program['tanggal_selesai'] ? date('d/m/Y', strtotime($view_program['tanggal_selesai'])) : '-' ?><br>
                    <?php if($view_program['latitude'] && $view_program['longitude']): ?>
                    <strong>Koordinat:</strong> <?= $view_program['latitude'] ?>, <?= $view_program['longitude'] ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php if($view_program['deskripsi']): ?>
            <div style="margin-top:15px; padding:15px; background:#f8f9fa; border-radius:5px">
                <strong>Deskripsi:</strong><br>
                <?= nl2br(htmlspecialchars($view_program['deskripsi'])) ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="grid" style="margin-top:20px">
            <div class="card">
                <h3>📊 Grafik Penyaluran per Bulan</h3>
                <canvas id="chartPenyaluranDetail" style="max-height:300px"></canvas>
            </div>
            <div class="card">
                <h3>📊 Dampak per Kategori</h3>
                <canvas id="chartDampakKategoriDetail" style="max-height:300px"></canvas>
            </div>
        </div>
        
        <div class="card" style="margin-top:20px">
            <h3>📊 Top Indikator Dampak</h3>
            <canvas id="chartIndikatorDetail" style="max-height:400px"></canvas>
        </div>
        
        <div class="grid" style="margin-top:20px">
            <div class="card">
                <h3>💰 Daftar Penyaluran</h3>
                <table style="margin-top:15px">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jumlah</th>
                            <th>Sasaran</th>
                            <th>Lokasi</th>
                            <th>Metode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($view_penyaluran)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:20px; color:#999">
                                Belum ada data penyaluran
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach($view_penyaluran as $pen): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($pen['tanggal_penyaluran'])) ?></td>
                            <td class="text-success"><strong><?= formatRupiah($pen['jumlah_penyaluran']) ?></strong></td>
                            <td><?= htmlspecialchars($pen['sasaran'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($pen['lokasi_penyaluran'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($pen['metode_penyaluran'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h3>📊 Daftar Pengukuran Dampak</h3>
                <table style="margin-top:15px">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Indikator</th>
                            <th>Nilai</th>
                            <th>Kategori</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($view_dampak)): ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding:20px; color:#999">
                                Belum ada data pengukuran dampak
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach($view_dampak as $damp): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($damp['tanggal_pengukuran'])) ?></td>
                            <td><?= htmlspecialchars($damp['indikator']) ?></td>
                            <td class="text-success">
                                <strong><?= number_format($damp['nilai'] ?? 0, 2) ?></strong>
                                <?php if($damp['satuan']): ?>
                                <small><?= htmlspecialchars($damp['satuan']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge"><?= ucfirst($damp['kategori_dampak']) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Chart Penyaluran per Bulan
    const ctxPenyaluran = document.getElementById('chartPenyaluranDetail');
    if (ctxPenyaluran) {
        new Chart(ctxPenyaluran, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($penyaluran_by_month ?? [], 'bulan_label')) ?>,
                datasets: [{
                    label: 'Total Penyaluran (Rp)',
                    data: <?= json_encode(array_column($penyaluran_by_month ?? [], 'total')) ?>,
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39, 174, 96, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Chart Dampak per Kategori
    const ctxDampakKategori = document.getElementById('chartDampakKategoriDetail');
    if (ctxDampakKategori) {
        new Chart(ctxDampakKategori, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($dampak_by_kategori ?? [], 'kategori_dampak')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($dampak_by_kategori ?? [], 'jumlah')) ?>,
                    backgroundColor: [
                        '#3498db', '#27ae60', '#f39c12', '#e74c3c', '#9b59b6', '#1abc9c'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
    
    // Chart Top Indikator
    const ctxIndikator = document.getElementById('chartIndikatorDetail');
    if (ctxIndikator) {
        new Chart(ctxIndikator, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($top_indikator ?? [], 'indikator')) ?>,
                datasets: [{
                    label: 'Rata-rata Nilai',
                    data: <?= json_encode(array_column($top_indikator ?? [], 'rata_rata_nilai')) ?>,
                    backgroundColor: '#27ae60',
                    borderColor: '#229954',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    </script>
    
    <?php else: ?>
    <!-- Normal View (List/Tabs) -->
    <div style="display:flex; gap:10px; margin-bottom:20px; border-bottom:2px solid #ddd; padding-bottom:10px; flex-wrap:wrap">
        <a href="program.php" class="btn <?= !isset($_GET['tab']) || $_GET['tab'] == 'list' ? 'btn-success' : '' ?>">📋 Daftar Program</a>
        <a href="program.php?tab=sebaran" class="btn <?= isset($_GET['tab']) && $_GET['tab'] == 'sebaran' ? 'btn-success' : '' ?>">🗺️ Sebaran Aksi Program</a>
        <a href="program.php?tab=peta" class="btn <?= isset($_GET['tab']) && $_GET['tab'] == 'peta' ? 'btn-success' : '' ?>">📍 Peta Sebaran</a>
        <a href="program.php?tab=penyaluran" class="btn <?= isset($_GET['tab']) && $_GET['tab'] == 'penyaluran' ? 'btn-success' : '' ?>">💰 Penyaluran</a>
        <a href="program.php?tab=dampak" class="btn <?= isset($_GET['tab']) && $_GET['tab'] == 'dampak' ? 'btn-success' : '' ?>">📊 Dampak Program</a>
    </div>
    
    <?php if(!isset($_GET['tab']) || $_GET['tab'] == 'list'): ?>
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <h2>Data Program</h2>
            <div>
                <a href="export_excel.php?type=program" class="btn btn-success">📥 Export Excel</a>
                <button class="btn" onclick="document.getElementById('modalProgram').style.display='block'; setTimeout(function(){ if(typeof initMapForm === 'function') initMapForm(); }, 500);">+ Tambah Program</button>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Nama Program</th>
                    <th>Kategori</th>
                    <th>Lokasi</th>
                    <th>Periode</th>
                    <th>Budget</th>
                    <th>Progress</th>
                    <th>Total Donasi</th>
                    <th>PIC</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($program_list)): ?>
                <tr>
                    <td colspan="9" style="text-align:center; padding:40px; color:#999">
                        <div style="font-size:48px; margin-bottom:10px">📋</div>
                        <div>Belum ada data program</div>
                        <div style="margin-top:10px">
                            <button class="btn" onclick="document.getElementById('modalProgram').style.display='block'; setTimeout(function(){ if(typeof initMapForm === 'function') initMapForm(); }, 500);">+ Tambah Program Pertama</button>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach($program_list as $p): 
                    $progress = (int)($p['progress'] ?? 0);
                    $progress_color = $progress < 30 ? '#e74c3c' : ($progress < 70 ? '#f39c12' : '#27ae60');
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($p['nama_program']) ?></strong></td>
                    <td><?= htmlspecialchars($p['kategori']) ?></td>
                    <td><?= htmlspecialchars($p['lokasi'] ?? '-') ?></td>
                    <td>
                        <?php if($p['tanggal_mulai']): ?>
                        <?= date('d/m/Y', strtotime($p['tanggal_mulai'])) ?>
                        <?php if($p['tanggal_selesai']): ?>
                        - <?= date('d/m/Y', strtotime($p['tanggal_selesai'])) ?>
                        <?php endif; ?>
                        <?php else: ?>
                        -
                        <?php endif; ?>
                    </td>
                    <td><?= formatRupiah($p['budget']) ?></td>
                    <td>
                        <div style="min-width:150px">
                            <div style="background:#e8e8e8; border-radius:10px; height:24px; position:relative; overflow:hidden; box-shadow:inset 0 1px 3px rgba(0,0,0,0.1)">
                                <div style="background:<?= $progress_color ?>; height:100%; width:<?= $progress ?>%; transition:width 0.3s; display:flex; align-items:center; justify-content:center; box-shadow:0 1px 3px rgba(0,0,0,0.2)">
                                    <span style="color:#fff; font-size:11px; font-weight:bold; text-shadow:0 1px 2px rgba(0,0,0,0.3)"><?= $progress ?>%</span>
                                </div>
                            </div>
                            <small style="color:#666; font-size:10px; display:block; margin-top:2px">
                                Realisasi: <?= formatRupiah($p['realisasi_budget'] ?? 0) ?>
                            </small>
                        </div>
                    </td>
                    <td><?= formatRupiah($p['total_donasi'] ?? 0) ?></td>
                    <td><?= htmlspecialchars($p['pic_name'] ?? '-') ?></td>
                    <td>
                        <span class="badge badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span>
                    </td>
                    <td>
                        <a href="?edit=<?= $p['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="?view=<?= $p['id'] ?>" class="btn btn-sm">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if(isset($_GET['tab']) && $_GET['tab'] == 'sebaran'): ?>
    <?php
    // Get program distribution by category
    $sebaran_kategori = $pdo->query("
        SELECT kategori, COUNT(*) as jumlah, SUM(budget) as total_budget
        FROM program_csr
        GROUP BY kategori
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get program distribution by status
    $sebaran_status = $pdo->query("
        SELECT status, COUNT(*) as jumlah
        FROM program_csr
        GROUP BY status
    ")->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="card">
        <h2>📊 Sebaran Aksi Program</h2>
        <div class="grid" style="margin-top:20px">
            <div class="card">
                <h3>Sebaran Berdasarkan Kategori</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th>Jumlah Program</th>
                            <th>Total Budget</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($sebaran_kategori as $sk): ?>
                        <tr>
                            <td><?= ucfirst($sk['kategori']) ?></td>
                            <td><strong><?= $sk['jumlah'] ?></strong></td>
                            <td><?= formatRupiah($sk['total_budget'] ?? 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card">
                <h3>Sebaran Berdasarkan Status</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Jumlah Program</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($sebaran_status as $ss): ?>
                        <tr>
                            <td><?= ucfirst($ss['status']) ?></td>
                            <td><strong><?= $ss['jumlah'] ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if(isset($_GET['tab']) && $_GET['tab'] == 'peta'): ?>
    <?php
    // Get all programs with coordinates
    $programs_with_coords = $pdo->query("
        SELECT id, nama_program, kategori, lokasi, latitude, longitude, status, budget
        FROM program_csr
        WHERE latitude IS NOT NULL AND longitude IS NOT NULL
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all programs for dropdown
    $all_programs = $pdo->query("SELECT id, nama_program, lokasi FROM program_csr ORDER BY nama_program")->fetchAll();
    ?>
    <div class="card">
        <h2>📍 Peta Sebaran Aksi Program - Sumatera Barat</h2>
        <p style="margin-bottom:20px">Klik pada peta untuk menambahkan atau mengedit pin lokasi program</p>
        
        <div style="margin-bottom:20px">
            <label><strong>Pilih Program untuk Edit Koordinat:</strong></label>
            <select id="programSelect" onchange="loadProgramLocation()" style="padding:10px; width:300px; margin-left:10px">
                <option value="">Pilih Program</option>
                <?php foreach($all_programs as $p): ?>
                <option value="<?= $p['id'] ?>" data-lat="<?= $p['latitude'] ?? '' ?>" data-lng="<?= $p['longitude'] ?? '' ?>">
                    <?= htmlspecialchars($p['nama_program']) ?> - <?= htmlspecialchars($p['lokasi'] ?? '-') ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button onclick="clearSelection()" class="btn btn-warning" style="margin-left:10px">Clear Selection</button>
        </div>
        
        <div id="map" style="height:600px; width:100%; border:2px solid #ddd; border-radius:8px"></div>
        
        <div style="margin-top:20px; padding:15px; background:#f8f9fa; border-radius:5px">
            <h4>Legenda:</h4>
            <div style="display:flex; gap:20px; flex-wrap:wrap; margin-top:10px">
                <div><span style="color:#3498db">●</span> Planning</div>
                <div><span style="color:#27ae60">●</span> Ongoing</div>
                <div><span style="color:#95a5a6">●</span> Completed</div>
                <div><span style="color:#e74c3c">●</span> Cancelled</div>
            </div>
        </div>
    </div>
    
    <script>
    // Initialize map centered on West Sumatra (Padang)
    const map = L.map('map').setView([-0.94924, 100.35427], 8);
    
    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{s}/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 18
    }).addTo(map);
    
    const markers = [];
    let selectedProgramId = null;
    let currentMarker = null;
    
    // Add existing program markers
    const programs = <?= json_encode($programs_with_coords) ?>;
    const statusColors = {
        'planning': '#3498db',
        'ongoing': '#27ae60',
        'completed': '#95a5a6',
        'cancelled': '#e74c3c'
    };
    
    programs.forEach(function(program) {
        const marker = L.marker([parseFloat(program.latitude), parseFloat(program.longitude)], {
            draggable: true
        }).addTo(map);
        
        marker.bindPopup(`
            <strong>${program.nama_program}</strong><br>
            Kategori: ${program.kategori}<br>
            Lokasi: ${program.lokasi || '-'}<br>
            Status: ${program.status}<br>
            Budget: Rp ${parseFloat(program.budget || 0).toLocaleString('id-ID')}
        `);
        
        marker.on('dragend', function() {
            saveMarkerLocation(program.id, marker.getLatLng().lat, marker.getLatLng().lng);
        });
        
        markers.push({id: program.id, marker: marker, program: program});
    });
    
    // Add click event to map for adding new pins
    map.on('click', function(e) {
        if (selectedProgramId) {
            if (currentMarker) {
                map.removeLayer(currentMarker);
            }
            
            currentMarker = L.marker([e.latlng.lat, e.latlng.lng], {
                draggable: true
            }).addTo(map);
            
            currentMarker.on('dragend', function() {
                saveMarkerLocation(selectedProgramId, currentMarker.getLatLng().lat, currentMarker.getLatLng().lng);
            });
            
            saveMarkerLocation(selectedProgramId, e.latlng.lat, e.latlng.lng);
        } else {
            alert('Pilih program terlebih dahulu dari dropdown di atas');
        }
    });
    
    function loadProgramLocation() {
        const select = document.getElementById('programSelect');
        const option = select.options[select.selectedIndex];
        selectedProgramId = select.value;
        
        if (option.dataset.lat && option.dataset.lng) {
            const lat = parseFloat(option.dataset.lat);
            const lng = parseFloat(option.dataset.lng);
            map.setView([lat, lng], 13);
            
            // Find existing marker
            const existing = markers.find(m => m.id == selectedProgramId);
            if (existing) {
                map.setView([lat, lng], 13);
                existing.marker.openPopup();
            } else {
                if (currentMarker) {
                    map.removeLayer(currentMarker);
                }
                currentMarker = L.marker([lat, lng], {
                    draggable: true
                }).addTo(map);
                currentMarker.on('dragend', function() {
                    saveMarkerLocation(selectedProgramId, currentMarker.getLatLng().lat, currentMarker.getLatLng().lng);
                });
            }
        } else {
            // Center on West Sumatra
            map.setView([-0.94924, 100.35427], 8);
        }
    }
    
    function clearSelection() {
        document.getElementById('programSelect').value = '';
        selectedProgramId = null;
        if (currentMarker) {
            map.removeLayer(currentMarker);
            currentMarker = null;
        }
    }
    
    function saveMarkerLocation(programId, lat, lng) {
        const formData = new FormData();
        formData.append('action', 'save_map_pin');
        formData.append('program_id', programId);
        formData.append('latitude', lat);
        formData.append('longitude', lng);
        
        fetch('program.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Koordinat berhasil disimpan!');
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error menyimpan koordinat');
        });
    }
    </script>
    <?php endif; ?>
    
    <?php if(isset($_GET['tab']) && $_GET['tab'] == 'penyaluran'): ?>
    <?php
    // Get penyaluran data
    try {
        $penyaluran_list = $pdo->query("
            SELECT pp.*, p.nama_program, p.kategori, p.status as program_status
            FROM program_penyaluran pp
            LEFT JOIN program_csr p ON pp.program_id = p.id
            ORDER BY pp.tanggal_penyaluran DESC
            LIMIT 100
        ")->fetchAll();
        
        // Get statistics
        $penyaluran_stats = $pdo->query("
            SELECT 
                COUNT(*) as total_penyaluran,
                SUM(jumlah_penyaluran) as total_nominal,
                COUNT(DISTINCT program_id) as total_program,
                AVG(jumlah_penyaluran) as rata_rata
            FROM program_penyaluran
        ")->fetch();
        
        // Get penyaluran by program
        $penyaluran_by_program = $pdo->query("
            SELECT 
                p.nama_program,
                COUNT(pp.id) as jumlah_penyaluran,
                SUM(pp.jumlah_penyaluran) as total_nominal
            FROM program_penyaluran pp
            LEFT JOIN program_csr p ON pp.program_id = p.id
            GROUP BY p.id, p.nama_program
            ORDER BY total_nominal DESC
            LIMIT 10
        ")->fetchAll();
        
        // Get penyaluran by month
        $penyaluran_by_month = $pdo->query("
            SELECT 
                DATE_FORMAT(tanggal_penyaluran, '%Y-%m') as bulan,
                DATE_FORMAT(tanggal_penyaluran, '%M %Y') as bulan_label,
                COUNT(*) as jumlah,
                SUM(jumlah_penyaluran) as total
            FROM program_penyaluran
            WHERE tanggal_penyaluran >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(tanggal_penyaluran, '%Y-%m'), DATE_FORMAT(tanggal_penyaluran, '%M %Y')
            ORDER BY bulan DESC
        ")->fetchAll();
    } catch(PDOException $e) {
        $penyaluran_list = [];
        $penyaluran_stats = ['total_penyaluran' => 0, 'total_nominal' => 0, 'total_program' => 0, 'rata_rata' => 0];
        $penyaluran_by_program = [];
        $penyaluran_by_month = [];
    }
    ?>
    <div class="card">
        <h2>💰 Penyaluran Program</h2>
        
        <div class="grid" style="margin-top:20px; grid-template-columns:repeat(4,1fr)">
            <div class="card">
                <h3>Total Penyaluran</h3>
                <div style="font-size:24px; font-weight:bold; color:#27ae60">
                    <?= formatRupiah($penyaluran_stats['total_nominal'] ?? 0) ?>
                </div>
            </div>
            <div class="card">
                <h3>Jumlah Penyaluran</h3>
                <div style="font-size:24px; font-weight:bold; color:#3498db">
                    <?= number_format($penyaluran_stats['total_penyaluran'] ?? 0) ?>x
                </div>
            </div>
            <div class="card">
                <h3>Program Terdistribusi</h3>
                <div style="font-size:24px; font-weight:bold; color:#f39c12">
                    <?= number_format($penyaluran_stats['total_program'] ?? 0) ?>
                </div>
            </div>
            <div class="card">
                <h3>Rata-rata</h3>
                <div style="font-size:24px; font-weight:bold; color:#9b59b6">
                    <?= formatRupiah($penyaluran_stats['rata_rata'] ?? 0) ?>
                </div>
            </div>
        </div>
        
        <div class="grid" style="margin-top:20px">
            <div class="card">
                <h3>📊 Grafik Penyaluran per Bulan</h3>
                <canvas id="chartPenyaluranBulan" style="max-height:300px"></canvas>
            </div>
            <div class="card">
                <h3>📊 Top 10 Program Penyaluran</h3>
                <canvas id="chartPenyaluranProgram" style="max-height:300px"></canvas>
            </div>
        </div>
        
        <div class="card" style="margin-top:20px">
            <h3>📋 Daftar Penyaluran</h3>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Program</th>
                        <th>Jumlah</th>
                        <th>Sasaran</th>
                        <th>Lokasi</th>
                        <th>Metode</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($penyaluran_list)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:40px; color:#999">
                            Belum ada data penyaluran
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach($penyaluran_list as $pen): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($pen['tanggal_penyaluran'])) ?></td>
                        <td><strong><?= htmlspecialchars($pen['nama_program'] ?? '-') ?></strong></td>
                        <td class="text-success"><?= formatRupiah($pen['jumlah_penyaluran']) ?></td>
                        <td><?= htmlspecialchars($pen['sasaran'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($pen['lokasi_penyaluran'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($pen['metode_penyaluran'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Chart Penyaluran per Bulan
    const ctxBulan = document.getElementById('chartPenyaluranBulan');
    if (ctxBulan) {
        new Chart(ctxBulan, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($penyaluran_by_month, 'bulan_label')) ?>,
                datasets: [{
                    label: 'Total Penyaluran (Rp)',
                    data: <?= json_encode(array_column($penyaluran_by_month, 'total')) ?>,
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39, 174, 96, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Chart Top 10 Program
    const ctxProgram = document.getElementById('chartPenyaluranProgram');
    if (ctxProgram) {
        new Chart(ctxProgram, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($penyaluran_by_program, 'nama_program')) ?>,
                datasets: [{
                    label: 'Total Penyaluran (Rp)',
                    data: <?= json_encode(array_column($penyaluran_by_program, 'total_nominal')) ?>,
                    backgroundColor: '#3498db',
                    borderColor: '#2980b9',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                            }
                        }
                    }
                }
            }
        });
    }
    </script>
    <?php endif; ?>
    
    <?php if(isset($_GET['tab']) && $_GET['tab'] == 'dampak'): ?>
    <?php
    // Get dampak data
    try {
        $dampak_list = $pdo->query("
            SELECT pd.*, p.nama_program, p.kategori
            FROM program_dampak pd
            LEFT JOIN program_csr p ON pd.program_id = p.id
            ORDER BY pd.tanggal_pengukuran DESC
            LIMIT 100
        ")->fetchAll();
        
        // Get statistics
        $dampak_stats = $pdo->query("
            SELECT 
                COUNT(*) as total_pengukuran,
                COUNT(DISTINCT program_id) as total_program,
                COUNT(DISTINCT kategori_dampak) as total_kategori
            FROM program_dampak
        ")->fetch();
        
        // Get dampak by kategori
        $dampak_by_kategori = $pdo->query("
            SELECT 
                kategori_dampak,
                COUNT(*) as jumlah,
                AVG(nilai) as rata_rata
            FROM program_dampak
            GROUP BY kategori_dampak
        ")->fetchAll();
        
        // Get top indikator
        $top_indikator = $pdo->query("
            SELECT 
                indikator,
                COUNT(*) as jumlah_pengukuran,
                AVG(nilai) as rata_rata_nilai
            FROM program_dampak
            GROUP BY indikator
            ORDER BY jumlah_pengukuran DESC
            LIMIT 10
        ")->fetchAll();
    } catch(PDOException $e) {
        $dampak_list = [];
        $dampak_stats = ['total_pengukuran' => 0, 'total_program' => 0, 'total_kategori' => 0];
        $dampak_by_kategori = [];
        $top_indikator = [];
    }
    ?>
    <div class="card">
        <h2>📊 Dampak Program</h2>
        
        <div class="grid" style="margin-top:20px; grid-template-columns:repeat(3,1fr)">
            <div class="card">
                <h3>Total Pengukuran</h3>
                <div style="font-size:24px; font-weight:bold; color:#27ae60">
                    <?= number_format($dampak_stats['total_pengukuran'] ?? 0) ?>
                </div>
            </div>
            <div class="card">
                <h3>Program Terukur</h3>
                <div style="font-size:24px; font-weight:bold; color:#3498db">
                    <?= number_format($dampak_stats['total_program'] ?? 0) ?>
                </div>
            </div>
            <div class="card">
                <h3>Kategori Dampak</h3>
                <div style="font-size:24px; font-weight:bold; color:#f39c12">
                    <?= number_format($dampak_stats['total_kategori'] ?? 0) ?>
                </div>
            </div>
        </div>
        
        <div class="grid" style="margin-top:20px">
            <div class="card">
                <h3>📊 Dampak per Kategori</h3>
                <canvas id="chartDampakKategori" style="max-height:300px"></canvas>
            </div>
            <div class="card">
                <h3>📊 Top 10 Indikator Dampak</h3>
                <canvas id="chartDampakIndikator" style="max-height:300px"></canvas>
            </div>
        </div>
        
        <div class="card" style="margin-top:20px">
            <h3>📋 Daftar Pengukuran Dampak</h3>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Program</th>
                        <th>Indikator</th>
                        <th>Nilai</th>
                        <th>Kategori</th>
                        <th>Deskripsi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($dampak_list)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:40px; color:#999">
                            Belum ada data pengukuran dampak
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach($dampak_list as $damp): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($damp['tanggal_pengukuran'])) ?></td>
                        <td><strong><?= htmlspecialchars($damp['nama_program'] ?? '-') ?></strong></td>
                        <td><?= htmlspecialchars($damp['indikator']) ?></td>
                        <td class="text-success">
                            <strong><?= number_format($damp['nilai'] ?? 0, 2) ?></strong>
                            <?php if($damp['satuan']): ?>
                            <small><?= htmlspecialchars($damp['satuan']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge"><?= ucfirst($damp['kategori_dampak']) ?></span>
                        </td>
                        <td><?= htmlspecialchars(substr($damp['deskripsi'] ?? '', 0, 50)) ?>...</td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Chart Dampak per Kategori
    const ctxKategori = document.getElementById('chartDampakKategori');
    if (ctxKategori) {
        new Chart(ctxKategori, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($dampak_by_kategori, 'kategori_dampak')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($dampak_by_kategori, 'jumlah')) ?>,
                    backgroundColor: [
                        '#3498db', '#27ae60', '#f39c12', '#e74c3c', '#9b59b6', '#1abc9c'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
    
    // Chart Top Indikator
    const ctxIndikator = document.getElementById('chartDampakIndikator');
    if (ctxIndikator) {
        new Chart(ctxIndikator, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($top_indikator, 'indikator')) ?>,
                datasets: [{
                    label: 'Rata-rata Nilai',
                    data: <?= json_encode(array_column($top_indikator, 'rata_rata_nilai')) ?>,
                    backgroundColor: '#27ae60',
                    borderColor: '#229954',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    </script>
    <?php endif; ?>
    
    <!-- Modal Tambah/Edit Program -->
    <div id="modalProgram" class="modal" style="<?= $edit_program ? 'display:block' : '' ?>">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modalProgram').style.display='none'; window.location.href='program.php'">&times;</span>
            <h2><?= $edit_program ? 'Edit Program' : 'Tambah Program' ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?= $edit_program ? 'update_program' : 'add_program' ?>">
                <?php if($edit_program): ?>
                <input type="hidden" name="id" value="<?= $edit_program['id'] ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label>Nama Program *</label>
                    <input type="text" name="nama_program" value="<?= $edit_program['nama_program'] ?? '' ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Kategori *</label>
                        <select name="kategori" required>
                            <option value="pendidikan" <?= ($edit_program['kategori'] ?? '') == 'pendidikan' ? 'selected' : '' ?>>Pendidikan</option>
                            <option value="kesehatan" <?= ($edit_program['kategori'] ?? '') == 'kesehatan' ? 'selected' : '' ?>>Kesehatan</option>
                            <option value="sosial" <?= ($edit_program['kategori'] ?? '') == 'sosial' ? 'selected' : '' ?>>Sosial</option>
                            <option value="lingkungan" <?= ($edit_program['kategori'] ?? '') == 'lingkungan' ? 'selected' : '' ?>>Lingkungan</option>
                            <option value="ekonomi" <?= ($edit_program['kategori'] ?? '') == 'ekonomi' ? 'selected' : '' ?>>Ekonomi</option>
                            <option value="bencana" <?= ($edit_program['kategori'] ?? '') == 'bencana' ? 'selected' : '' ?>>Bencana</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>PIC</label>
                        <select name="pic">
                            <option value="">Pilih PIC</option>
                            <?php foreach($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($edit_program['pic'] ?? '') == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nama_lengkap']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Lokasi</label>
                    <input type="text" name="lokasi" id="lokasi_input" value="<?= $edit_program['lokasi'] ?? '' ?>" placeholder="Alamat/lokasi program">
                </div>
                <div class="form-group">
                    <label>Pilih Lokasi di Peta (Klik pada peta untuk memilih koordinat)</label>
                    <div id="mapForm" style="height:400px; width:100%; border:2px solid #ddd; border-radius:5px; margin-top:10px; background:#f0f0f0; position:relative">
                        <div id="mapLoading" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); text-align:center; color:#666">
                            <div>Memuat peta...</div>
                        </div>
                    </div>
                    <small style="color:#666; display:block; margin-top:5px">
                        💡 Klik pada peta Sumatera Barat untuk memilih koordinat lokasi program
                    </small>
                    <input type="hidden" name="latitude" id="latitude_input" value="<?= $edit_program['latitude'] ?? '' ?>">
                    <input type="hidden" name="longitude" id="longitude_input" value="<?= $edit_program['longitude'] ?? '' ?>">
                    <div id="coordinate_display" style="margin-top:10px; padding:10px; background:#f8f9fa; border-radius:5px; display:none">
                        <strong>Koordinat Terpilih:</strong> 
                        <span id="coord_text">-</span>
                        <button type="button" onclick="clearCoordinates()" class="btn btn-warning btn-sm" style="margin-left:10px">Hapus Koordinat</button>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" value="<?= $edit_program['tanggal_mulai'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" value="<?= $edit_program['tanggal_selesai'] ?? '' ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Budget</label>
                        <input type="number" name="budget" step="0.01" value="<?= $edit_program['budget'] ?? '' ?>" placeholder="Anggaran program">
                    </div>
                    <?php if($edit_program): ?>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="planning" <?= $edit_program['status'] == 'planning' ? 'selected' : '' ?>>Planning</option>
                            <option value="ongoing" <?= $edit_program['status'] == 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                            <option value="completed" <?= $edit_program['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $edit_program['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" rows="5"><?= $edit_program['deskripsi'] ?? '' ?></textarea>
                </div>
                <button type="submit" class="btn btn-success">Simpan</button>
                <button type="button" class="btn" onclick="document.getElementById('modalProgram').style.display='none'; window.location.href='program.php'">Batal</button>
            </form>
        </div>
    </div>
    
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Initialize map for form (centered on West Sumatra)
var mapForm = null;
var markerForm = null;

function initMapForm() {
    // Check if Leaflet is loaded
    if (typeof L === 'undefined') {
        console.error('Leaflet library not loaded');
        setTimeout(initMapForm, 500);
        return;
    }
    
    // Check if map div exists
    var mapDiv = document.getElementById('mapForm');
    if (!mapDiv) {
        console.error('Map div not found');
        return;
    }
    
    // If map already exists, just refresh it
    if (mapForm) {
        try {
            mapForm.invalidateSize();
            return;
        } catch(e) {
            mapForm = null;
        }
    }
    
    try {
        // Initialize map - simple approach like the example
        mapForm = L.map('mapForm').setView([-0.94924, 100.35427], 8);
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(mapForm);
        
        // Load existing coordinates if editing
        var latInput = document.getElementById('latitude_input');
        var lngInput = document.getElementById('longitude_input');
        
        if (latInput && lngInput) {
            var lat = latInput.value;
            var lng = lngInput.value;
            
            if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
                addMarkerToForm(parseFloat(lat), parseFloat(lng));
                mapForm.setView([parseFloat(lat), parseFloat(lng)], 13);
            }
        }
        
        // Add click event to map
        mapForm.on('click', function(e) {
            addMarkerToForm(e.latlng.lat, e.latlng.lng);
        });
        
        // Hide loading message
        var loadingDiv = document.getElementById('mapLoading');
        if (loadingDiv) {
            loadingDiv.style.display = 'none';
        }
        
        console.log('Map initialized successfully');
    } catch(e) {
        console.error('Error initializing map:', e);
        var loadingDiv = document.getElementById('mapLoading');
        if (loadingDiv) {
            loadingDiv.innerHTML = '<div style="color:#e74c3c">Error memuat peta. Silakan refresh halaman.</div>';
        }
    }
}

function addMarkerToForm(lat, lng) {
    // Remove existing marker
    if (markerForm) {
        mapForm.removeLayer(markerForm);
    }
    
    // Add new marker - simple approach
    markerForm = L.marker([lat, lng], {
        draggable: true
    }).addTo(mapForm);
    
    // Update form fields
    document.getElementById('latitude_input').value = lat;
    document.getElementById('longitude_input').value = lng;
    document.getElementById('coord_text').textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
    document.getElementById('coordinate_display').style.display = 'block';
    
    // Update marker position on drag
    markerForm.on('dragend', function() {
        var pos = markerForm.getLatLng();
        document.getElementById('latitude_input').value = pos.lat;
        document.getElementById('longitude_input').value = pos.lng;
        document.getElementById('coord_text').textContent = pos.lat.toFixed(6) + ', ' + pos.lng.toFixed(6);
    });
    
    // Center map on marker
    mapForm.setView([lat, lng], 13);
}

function clearCoordinates() {
    if (markerForm) {
        mapForm.removeLayer(markerForm);
        markerForm = null;
    }
    document.getElementById('latitude_input').value = '';
    document.getElementById('longitude_input').value = '';
    document.getElementById('coordinate_display').style.display = 'none';
    if (mapForm) {
        mapForm.setView([-0.94924, 100.35427], 8);
    }
}

// Function to open modal and initialize map
function openProgramModal() {
    const modal = document.getElementById('modalProgram');
    if (modal) {
        modal.style.display = 'block';
        // Wait for modal to be visible, then initialize map
        setTimeout(function() {
            initMapForm();
        }, 300);
    }
}

// Initialize map when modal opens - simple approach
document.addEventListener('DOMContentLoaded', function() {
    // Check if modal is already open (for edit mode)
    var modal = document.getElementById('modalProgram');
    if (modal && modal.style.display === 'block') {
        setTimeout(initMapForm, 500);
    }
    
    // Watch for modal opening
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                if (modal.style.display === 'block') {
                    setTimeout(initMapForm, 500);
                }
            }
        });
    });
    
    if (modal) {
        observer.observe(modal, {
            attributes: true,
            attributeFilter: ['style']
        });
    }
    
    // Also try after page fully loads
    window.addEventListener('load', function() {
        if (modal && modal.style.display === 'block') {
            setTimeout(initMapForm, 500);
        }
    });
});

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        window.location.href = 'program.php';
    }
}
</script>

</body>
</html>

