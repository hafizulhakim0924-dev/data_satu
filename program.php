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
            $stmt = $pdo->prepare("INSERT INTO program_csr (nama_program, kategori, deskripsi, lokasi, tanggal_mulai, tanggal_selesai, budget, status, pic) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $_POST['nama_program'] ?? '',
                $_POST['kategori'] ?? '',
                $_POST['deskripsi'] ?? '',
                $_POST['lokasi'] ?? '',
                $_POST['tanggal_mulai'] ?: null,
                $_POST['tanggal_selesai'] ?: null,
                $_POST['budget'] ?? 0,
                $_POST['status'] ?? 'planning',
                $_POST['pic'] ?: null
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
            $stmt = $pdo->prepare("UPDATE program_csr SET nama_program=?, kategori=?, deskripsi=?, lokasi=?, tanggal_mulai=?, tanggal_selesai=?, budget=?, status=?, pic=? WHERE id=?");
            $stmt->execute([
                $_POST['nama_program'] ?? '',
                $_POST['kategori'] ?? '',
                $_POST['deskripsi'] ?? '',
                $_POST['lokasi'] ?? '',
                $_POST['tanggal_mulai'] ?: null,
                $_POST['tanggal_selesai'] ?: null,
                $_POST['budget'] ?? 0,
                $_POST['status'] ?? 'planning',
                $_POST['pic'] ?: null,
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

// Load programs
try {
    $table_check = $pdo->query("SHOW TABLES LIKE 'program_csr'")->fetch();
    if ($table_check) {
        $program_list = $pdo->query("
            SELECT p.*, 
                u.nama_lengkap as pic_name
            FROM program_csr p 
            LEFT JOIN users u ON p.pic=u.id 
            ORDER BY p.tanggal_mulai DESC
        ")->fetchAll();
    } else {
        $error_msg = "Tabel 'program_csr' belum ada. Jalankan fix_all_program_tables.sql";
    }
} catch(PDOException $e) {
    $program_list = [];
    $error_msg = "Error loading programs: " . $e->getMessage();
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
        .modal-content { background:#fff; margin:5% auto; padding:20px; border-radius:8px; width:90%; max-width:600px; max-height:90vh; overflow-y:auto }
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
                    <a href="?edit=<?= $p['id'] ?>" class="btn" style="padding:5px 10px; font-size:12px">Edit</a>
                    <button class="btn btn-danger" style="padding:5px 10px; font-size:12px" onclick="deleteProgram(<?= $p['id'] ?>)">Hapus</button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
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
                <input type="text" name="lokasi" value="<?= htmlspecialchars($edit_program['lokasi'] ?? '') ?>">
            </div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px">
                <div class="form-group">
                    <label>Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" value="<?= $edit_program['tanggal_mulai'] ?? '' ?>">
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
