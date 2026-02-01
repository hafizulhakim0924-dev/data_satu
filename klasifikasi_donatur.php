<?php
require_once 'config.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add_klasifikasi') {
        $stmt = $pdo->prepare("INSERT INTO klasifikasi_donatur (nama_klasifikasi, kode, warna, indikator, deskripsi, minimal_donasi, prioritas, status) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$_POST['nama_klasifikasi'], $_POST['kode'], $_POST['warna'], $_POST['indikator'], $_POST['deskripsi'], $_POST['minimal_donasi'] ?: null, $_POST['prioritas'], 'active']);
        header("Location: klasifikasi_donatur.php?msg=Klasifikasi berhasil ditambahkan");
        exit;
    }
    
    if ($action == 'update_klasifikasi') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE klasifikasi_donatur SET nama_klasifikasi=?, kode=?, warna=?, indikator=?, deskripsi=?, minimal_donasi=?, prioritas=?, status=? WHERE id=?");
        $stmt->execute([$_POST['nama_klasifikasi'], $_POST['kode'], $_POST['warna'], $_POST['indikator'], $_POST['deskripsi'], $_POST['minimal_donasi'] ?: null, $_POST['prioritas'], $_POST['status'], $id]);
        header("Location: klasifikasi_donatur.php?msg=Klasifikasi berhasil diupdate");
        exit;
    }
    
    if ($action == 'delete_klasifikasi') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE klasifikasi_donatur SET status='inactive' WHERE id=?");
        $stmt->execute([$id]);
        header("Location: klasifikasi_donatur.php?msg=Klasifikasi berhasil dinonaktifkan");
        exit;
    }
}

try {
    $klasifikasi_list = $pdo->query("SELECT k.*, 
        (SELECT COUNT(*) FROM donatur WHERE klasifikasi_id=k.id) as jumlah_donatur
        FROM klasifikasi_donatur k 
        ORDER BY k.prioritas DESC, k.nama_klasifikasi")->fetchAll();
} catch(PDOException $e) {
    $klasifikasi_list = [];
    $error_msg = "Error: " . $e->getMessage();
}

$edit_id = $_GET['edit'] ?? null;
$edit_klasifikasi = null;
if ($edit_id) {
    try {
        $edit_klasifikasi = $pdo->prepare("SELECT * FROM klasifikasi_donatur WHERE id=?");
        $edit_klasifikasi->execute([$edit_id]);
        $edit_klasifikasi = $edit_klasifikasi->fetch();
    } catch(PDOException $e) {
        $edit_klasifikasi = null;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Klasifikasi Donatur - Rangkiang Peduli Negeri</title>
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
.grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(250px,1fr)); gap:15px }
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
.modal-content { background:#fff; margin:5% auto; padding:20px; border-radius:8px; width:90%; max-width:600px; max-height:90vh; overflow-y:auto }
.close { float:right; font-size:28px; font-weight:bold; cursor:pointer }
.alert { padding:15px; margin-bottom:20px; border-radius:5px }
.alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb }
.color-preview { width:40px; height:40px; border-radius:4px; display:inline-block; border:2px solid #ddd; vertical-align:middle; margin-right:10px }
</style>
</head>
<body>

<div class="navbar">
    <h1>🏛️ Rangkiang Peduli Negeri</h1>
    <?= getNavMenu() ?>
</div>

<div class="container">
    <h1 style="margin:20px 0">🏷️ Manajemen Klasifikasi Donatur</h1>
    
    <?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    
    <?php if(isset($error_msg)): ?>
    <div class="alert" style="background:#f8d7da; color:#721c24; border:1px solid #f5c6cb">
        <strong>⚠️ Error Database:</strong> <?= htmlspecialchars($error_msg) ?>
        <br><small>Pastikan tabel 'klasifikasi_donatur' sudah dibuat. Jalankan file create_klasifikasi_donatur.sql terlebih dahulu.</small>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <h2>Data Klasifikasi</h2>
            <button class="btn" onclick="document.getElementById('modalKlasifikasi').style.display='block'">+ Tambah Klasifikasi</button>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Klasifikasi</th>
                    <th>Kode</th>
                    <th>Warna</th>
                    <th>Indikator</th>
                    <th>Minimal Donasi</th>
                    <th>Prioritas</th>
                    <th>Jumlah Donatur</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($klasifikasi_list)): ?>
                <tr>
                    <td colspan="9" style="text-align:center; padding:40px; color:#999">
                        <div style="font-size:48px; margin-bottom:10px">🏷️</div>
                        <div>Belum ada data klasifikasi</div>
                        <div style="margin-top:10px">
                            <button class="btn" onclick="document.getElementById('modalKlasifikasi').style.display='block'">+ Tambah Klasifikasi Pertama</button>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach($klasifikasi_list as $k): ?>
                <tr style="border-left: 4px solid <?= htmlspecialchars($k['warna']) ?>">
                    <td>
                        <strong><?= htmlspecialchars($k['nama_klasifikasi']) ?></strong>
                        <?php if($k['deskripsi']): ?>
                        <br><small style="color:#666"><?= htmlspecialchars($k['deskripsi']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><code><?= htmlspecialchars($k['kode'] ?? '-') ?></code></td>
                    <td>
                        <div class="color-preview" style="background:<?= htmlspecialchars($k['warna']) ?>"></div>
                        <code><?= htmlspecialchars($k['warna']) ?></code>
                    </td>
                    <td style="font-size:24px"><?= htmlspecialchars($k['indikator'] ?? '-') ?></td>
                    <td><?= $k['minimal_donasi'] ? formatRupiah($k['minimal_donasi']) : '-' ?></td>
                    <td><?= $k['prioritas'] ?></td>
                    <td><strong><?= $k['jumlah_donatur'] ?? 0 ?></strong> donatur</td>
                    <td><?= htmlspecialchars($k['status']) ?></td>
                    <td>
                        <a href="?edit=<?= $k['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Modal Tambah/Edit Klasifikasi -->
    <div id="modalKlasifikasi" class="modal" style="<?= $edit_klasifikasi ? 'display:block' : '' ?>">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modalKlasifikasi').style.display='none'; window.location.href='klasifikasi_donatur.php'">&times;</span>
            <h2><?= $edit_klasifikasi ? 'Edit Klasifikasi' : 'Tambah Klasifikasi' ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?= $edit_klasifikasi ? 'update_klasifikasi' : 'add_klasifikasi' ?>">
                <?php if($edit_klasifikasi): ?>
                <input type="hidden" name="id" value="<?= $edit_klasifikasi['id'] ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label>Nama Klasifikasi *</label>
                    <input type="text" name="nama_klasifikasi" value="<?= $edit_klasifikasi['nama_klasifikasi'] ?? '' ?>" required placeholder="Contoh: Donatur Utama">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Kode</label>
                        <input type="text" name="kode" value="<?= $edit_klasifikasi['kode'] ?? '' ?>" placeholder="Contoh: UTAMA" style="text-transform:uppercase">
                    </div>
                    <div class="form-group">
                        <label>Prioritas</label>
                        <input type="number" name="prioritas" value="<?= $edit_klasifikasi['prioritas'] ?? 0 ?>" min="0" max="10">
                        <small style="color:#666">Semakin tinggi angka, semakin tinggi prioritas</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Warna (Hex Code) *</label>
                        <input type="color" name="warna" value="<?= $edit_klasifikasi['warna'] ?? '#3498db' ?>" style="width:100%; height:50px">
                        <input type="text" name="warna_text" value="<?= $edit_klasifikasi['warna'] ?? '#3498db' ?>" placeholder="#3498db" onchange="document.querySelector('input[name=warna]').value=this.value" style="margin-top:5px">
                        <small style="color:#666">Pilih warna atau masukkan kode hex (contoh: #e74c3c)</small>
                    </div>
                    <div class="form-group">
                        <label>Indikator (Emoji/Icon)</label>
                        <input type="text" name="indikator" value="<?= $edit_klasifikasi['indikator'] ?? '' ?>" placeholder="⭐ 💎 💵 💰 🆕 🔄">
                        <small style="color:#666">Emoji atau simbol untuk indikator</small>
                    </div>
                </div>
                <div class="form-group">
                    <label>Minimal Donasi (Opsional)</label>
                    <input type="number" name="minimal_donasi" step="0.01" value="<?= $edit_klasifikasi['minimal_donasi'] ?? '' ?>" placeholder="Minimal total donasi untuk masuk klasifikasi ini">
                    <small style="color:#666">Kosongkan jika tidak ada batas minimal</small>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" rows="3"><?= $edit_klasifikasi['deskripsi'] ?? '' ?></textarea>
                </div>
                <?php if($edit_klasifikasi): ?>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="active" <?= $edit_klasifikasi['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $edit_klasifikasi['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-success">Simpan</button>
                <button type="button" class="btn" onclick="document.getElementById('modalKlasifikasi').style.display='none'; window.location.href='klasifikasi_donatur.php'">Batal</button>
            </form>
        </div>
    </div>
    
</div>

<script>
// Sync color picker dengan text input
document.querySelector('input[name=warna]')?.addEventListener('input', function(e) {
    document.querySelector('input[name=warna_text]').value = e.target.value;
});

document.querySelector('input[name=warna_text]')?.addEventListener('input', function(e) {
    if(/^#[0-9A-F]{6}$/i.test(e.target.value)) {
        document.querySelector('input[name=warna]').value = e.target.value;
    }
});

// Update form warna saat submit
document.querySelector('form')?.addEventListener('submit', function(e) {
    const colorInput = document.querySelector('input[name=warna]');
    const colorText = document.querySelector('input[name=warna_text]');
    if(colorInput && colorText) {
        colorText.value = colorInput.value;
    }
});

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        window.location.href = 'klasifikasi_donatur.php';
    }
}
</script>

</body>
</html>

