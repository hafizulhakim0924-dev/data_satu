<?php
// Simple and safe version of program.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Start output buffering
@ob_start();

require_once 'config.php';

// Initialize variables
$error_msg = null;
$program_list = [];
$users = [];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add_program') {
        try {
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
    <style>
        * { margin:0; padding:0; box-sizing:border-box }
        body { font-family:Arial,sans-serif; background:#f5f5f5; padding:20px }
        .navbar { background:#2c3e50; color:#fff; padding:15px 20px; margin:-20px -20px 20px -20px }
        .navbar h1 { font-size:20px }
        .container { max-width:1200px; margin:0 auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1) }
        .alert { padding:15px; margin-bottom:20px; border-radius:5px }
        .alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb }
        .alert-error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb }
        .btn { padding:10px 20px; background:#3498db; color:#fff; border:none; border-radius:5px; cursor:pointer; text-decoration:none; display:inline-block; margin:5px }
        .btn:hover { background:#2980b9 }
        .btn-danger { background:#e74c3c }
        .btn-danger:hover { background:#c0392b }
        .btn-success { background:#27ae60 }
        .btn-success:hover { background:#229954 }
        table { width:100%; border-collapse:collapse; margin-top:20px }
        table th, table td { padding:12px; text-align:left; border-bottom:1px solid #ddd }
        table th { background:#34495e; color:#fff; font-weight:600 }
        table tr:hover { background:#f8f9fa }
        .badge { padding:5px 10px; border-radius:3px; font-size:12px; font-weight:600; display:inline-block }
        .badge-planning { background:#95a5a6; color:#fff }
        .badge-ongoing { background:#3498db; color:#fff }
        .badge-completed { background:#27ae60; color:#fff }
        .badge-cancelled { background:#e74c3c; color:#fff }
        .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5) }
        .modal-content { background:#fff; margin:5% auto; padding:20px; border-radius:8px; width:90%; max-width:800px; max-height:90vh; overflow-y:auto }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:15px }
        .detail-section { background:#f8f9fa; padding:15px; border-radius:5px; margin-bottom:20px }
        .detail-section h3 { margin-bottom:15px; color:#2c3e50; border-bottom:2px solid #3498db; padding-bottom:5px }
        .detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px }
        .detail-item { margin-bottom:10px }
        .detail-item label { font-weight:600; color:#555; display:block; margin-bottom:3px }
        .detail-item span { color:#333 }
        .form-group { margin-bottom:15px }
        .form-group label { display:block; margin-bottom:5px; font-weight:600 }
        .form-group input, .form-group select, .form-group textarea { width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; font-size:14px }
        .form-group textarea { min-height:100px; resize:vertical }
        .close { float:right; font-size:28px; font-weight:bold; cursor:pointer; color:#aaa }
        .close:hover { color:#000 }
        .text-right { text-align:right }
    </style>
</head>
<body>

<div class="navbar">
    <h1>🏛️ Rangkiang Peduli Negeri</h1>
    <?= getNavMenu() ?>
</div>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h1>📋 Manajemen Program CSR</h1>
        <button class="btn btn-success" onclick="openModal('add')">+ Tambah Program</button>
    </div>
    
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

// Open modal if editing
<?php if($edit_program): ?>
window.onload = function() {
    openModal('edit');
};
<?php endif; ?>

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('modalProgram');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

</body>
</html>
<?php
@ob_end_flush();
?>
