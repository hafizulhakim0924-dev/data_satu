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
            (SELECT SUM(jumlah) FROM csr_donations WHERE program=p.nama_program) as total_donasi
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
    
    <div style="display:flex; gap:10px; margin-bottom:20px; border-bottom:2px solid #ddd; padding-bottom:10px">
        <a href="program.php" class="btn <?= !isset($_GET['tab']) || $_GET['tab'] == 'list' ? 'btn-success' : '' ?>">📋 Daftar Program</a>
        <a href="program.php?tab=sebaran" class="btn <?= isset($_GET['tab']) && $_GET['tab'] == 'sebaran' ? 'btn-success' : '' ?>">🗺️ Sebaran Aksi Program</a>
        <a href="program.php?tab=peta" class="btn <?= isset($_GET['tab']) && $_GET['tab'] == 'peta' ? 'btn-success' : '' ?>">📍 Peta Sebaran</a>
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
                <?php foreach($program_list as $p): ?>
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
let mapForm = null;
let markerForm = null;
let mapInitialized = false;

function initMapForm() {
    // Check if Leaflet is loaded
    if (typeof L === 'undefined') {
        console.error('Leaflet library not loaded, retrying...');
        // Try to load Leaflet manually if not loaded
        if (!document.querySelector('script[src*="leaflet"]')) {
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.onload = function() {
                setTimeout(initMapForm, 200);
            };
            document.head.appendChild(script);
        } else {
            setTimeout(initMapForm, 500);
        }
        return;
    }
    
    // Check if map div exists
    const mapDiv = document.getElementById('mapForm');
    if (!mapDiv) {
        console.error('Map div not found');
        return;
    }
    
    // Check if div is visible (with multiple checks)
    const isVisible = mapDiv.offsetParent !== null || 
                     mapDiv.style.display !== 'none' || 
                     window.getComputedStyle(mapDiv).display !== 'none';
    
    if (!isVisible) {
        // Div is hidden, wait a bit and retry
        setTimeout(initMapForm, 300);
        return;
    }
    
    // Ensure div has dimensions
    if (mapDiv.offsetWidth === 0 || mapDiv.offsetHeight === 0) {
        setTimeout(initMapForm, 300);
        return;
    }
    
    // If map already exists, just refresh it
    if (mapForm) {
        try {
            mapForm.invalidateSize();
            return;
        } catch(e) {
            // If error, reinitialize
            mapForm = null;
        }
    }
    
    try {
        // Initialize map
        mapForm = L.map('mapForm', {
            zoomControl: true
        }).setView([-0.94924, 100.35427], 8);
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{s}/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(mapForm);
        
        // Load existing coordinates if editing
        const latInput = document.getElementById('latitude_input');
        const lngInput = document.getElementById('longitude_input');
        
        if (latInput && lngInput) {
            const lat = latInput.value;
            const lng = lngInput.value;
            
            if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
                setTimeout(function() {
                    addMarkerToForm(parseFloat(lat), parseFloat(lng));
                    mapForm.setView([parseFloat(lat), parseFloat(lng)], 13);
                }, 100);
            }
        }
        
        // Add click event to map
        mapForm.on('click', function(e) {
            addMarkerToForm(e.latlng.lat, e.latlng.lng);
        });
        
        mapInitialized = true;
        console.log('Map initialized successfully');
        
        // Hide loading message
        const loadingDiv = document.getElementById('mapLoading');
        if (loadingDiv) {
            loadingDiv.style.display = 'none';
        }
    } catch(e) {
        console.error('Error initializing map:', e);
        const loadingDiv = document.getElementById('mapLoading');
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
    
    // Add new marker
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
        const pos = markerForm.getLatLng();
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
    mapForm.setView([-0.94924, 100.35427], 8);
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

// Initialize map when modal opens
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalProgram');
    if (modal) {
        // Use MutationObserver to detect when modal is shown
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    if (modal.style.display === 'block' || modal.style.display === '') {
                        setTimeout(function() {
                            initMapForm();
                        }, 500);
                    }
                }
            });
        });
        
        observer.observe(modal, {
            attributes: true,
            attributeFilter: ['style', 'class']
        });
        
        // Also check if modal is already open (for edit mode)
        if (modal.style.display === 'block' || modal.style.display === '') {
            setTimeout(function() {
                initMapForm();
            }, 500);
        }
    }
    
    // Override button clicks to initialize map
    document.addEventListener('click', function(e) {
        const target = e.target;
        if (target && (target.onclick && target.onclick.toString().includes('modalProgram') || 
            target.closest('[onclick*="modalProgram"]') || 
            target.id === 'modalProgram' || 
            target.closest('#modalProgram'))) {
            setTimeout(function() {
                initMapForm();
            }, 500);
        }
    });
    
    // Also try to initialize after a delay (fallback)
    setTimeout(function() {
        const mapDiv = document.getElementById('mapForm');
        if (mapDiv && mapDiv.offsetParent !== null && !mapInitialized) {
            initMapForm();
        }
    }, 1000);
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

