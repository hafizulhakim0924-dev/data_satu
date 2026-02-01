<?php
require_once 'config.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add_donatur') {
        $stmt = $pdo->prepare("INSERT INTO donatur (nama, email, no_hp, alamat, tipe, npwp, nama_perusahaan, pic, kategori, status, catatan) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$_POST['nama'], $_POST['email'], $_POST['no_hp'], $_POST['alamat'], $_POST['tipe'], $_POST['npwp'], $_POST['nama_perusahaan'], $_POST['pic'], $_POST['kategori'], 'active', $_POST['catatan']]);
        header("Location: donatur.php?msg=Donatur berhasil ditambahkan");
        exit;
    }
    
    if ($action == 'update_donatur') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE donatur SET nama=?, email=?, no_hp=?, alamat=?, tipe=?, npwp=?, nama_perusahaan=?, pic=?, kategori=?, status=?, catatan=? WHERE id=?");
        $stmt->execute([$_POST['nama'], $_POST['email'], $_POST['no_hp'], $_POST['alamat'], $_POST['tipe'], $_POST['npwp'], $_POST['nama_perusahaan'], $_POST['pic'], $_POST['kategori'], $_POST['status'], $_POST['catatan'], $id]);
        header("Location: donatur.php?msg=Donatur berhasil diupdate");
        exit;
    }
}

$donatur_list = $pdo->query("SELECT d.*, 
    (SELECT SUM(jumlah) FROM csr_donations WHERE donatur_id=d.id) as total_donasi,
    (SELECT COUNT(*) FROM csr_donations WHERE donatur_id=d.id) as jumlah_donasi
    FROM donatur d ORDER BY d.nama")->fetchAll();

$edit_id = $_GET['edit'] ?? null;
$edit_donatur = null;
if ($edit_id) {
    $edit_donatur = $pdo->prepare("SELECT * FROM donatur WHERE id=?");
    $edit_donatur->execute([$edit_id]);
    $edit_donatur = $edit_donatur->fetch();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Donatur - Rangkiang Peduli Negeri</title>
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
.modal-content { background:#fff; margin:5% auto; padding:20px; border-radius:8px; width:90%; max-width:700px; max-height:90vh; overflow-y:auto }
.close { float:right; font-size:28px; font-weight:bold; cursor:pointer }
.alert { padding:15px; margin-bottom:20px; border-radius:5px }
.alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb }
.badge { padding:5px 10px; border-radius:3px; font-size:12px; font-weight:600; display:inline-block }
.badge-individu { background:#3498db; color:#fff }
.badge-perusahaan { background:#f39c12; color:#fff }
.badge-yayasan { background:#9b59b6; color:#fff }
.badge-lembaga { background:#27ae60; color:#fff }
.text-success { color:#27ae60; font-weight:600 }
</style>
</head>
<body>

<div class="navbar">
    <h1>🏛️ Rangkiang Peduli Negeri</h1>
    <?= getNavMenu() ?>
</div>

<div class="container">
    <h1 style="margin:20px 0">🤝 Manajemen Donatur</h1>
    
    <?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <h2>Data Donatur</h2>
            <button class="btn" onclick="document.getElementById('modalDonatur').style.display='block'">+ Tambah Donatur</button>
        </div>
        
        <div class="grid" style="margin-bottom:20px">
            <div class="card">
                <h3>Total Donatur</h3>
                <div style="font-size:24px; font-weight:bold; color:#2c3e50"><?= count($donatur_list) ?></div>
            </div>
            <div class="card">
                <h3>Donatur Aktif</h3>
                <div style="font-size:24px; font-weight:bold; color:#27ae60"><?= count(array_filter($donatur_list, fn($d) => $d['status'] == 'active')) ?></div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Tipe</th>
                    <th>Kontak</th>
                    <th>Kategori</th>
                    <th>Total Donasi</th>
                    <th>Jumlah Donasi</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($donatur_list as $d): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($d['nama']) ?></strong>
                        <?php if($d['nama_perusahaan']): ?>
                        <br><small style="color:#666"><?= htmlspecialchars($d['nama_perusahaan']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-<?= $d['tipe'] ?>"><?= ucfirst($d['tipe']) ?></span>
                    </td>
                    <td>
                        <?php if($d['email']): ?>
                        <div><?= htmlspecialchars($d['email']) ?></div>
                        <?php endif; ?>
                        <?php if($d['no_hp']): ?>
                        <div><?= htmlspecialchars($d['no_hp']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($d['kategori']) ?></td>
                    <td class="text-success"><?= formatRupiah($d['total_donasi'] ?? 0) ?></td>
                    <td><?= $d['jumlah_donasi'] ?? 0 ?>x</td>
                    <td><?= htmlspecialchars($d['status']) ?></td>
                    <td>
                        <a href="?edit=<?= $d['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="donasi.php?donatur_id=<?= $d['id'] ?>" class="btn btn-sm">Donasi</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Modal Tambah/Edit Donatur -->
    <div id="modalDonatur" class="modal" style="<?= $edit_donatur ? 'display:block' : '' ?>">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modalDonatur').style.display='none'; window.location.href='donatur.php'">&times;</span>
            <h2><?= $edit_donatur ? 'Edit Donatur' : 'Tambah Donatur' ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?= $edit_donatur ? 'update_donatur' : 'add_donatur' ?>">
                <?php if($edit_donatur): ?>
                <input type="hidden" name="id" value="<?= $edit_donatur['id'] ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label>Nama *</label>
                    <input type="text" name="nama" value="<?= $edit_donatur['nama'] ?? '' ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= $edit_donatur['email'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>No HP</label>
                        <input type="text" name="no_hp" value="<?= $edit_donatur['no_hp'] ?? '' ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tipe *</label>
                        <select name="tipe" required>
                            <option value="individu" <?= ($edit_donatur['tipe'] ?? '') == 'individu' ? 'selected' : '' ?>>Individu</option>
                            <option value="perusahaan" <?= ($edit_donatur['tipe'] ?? '') == 'perusahaan' ? 'selected' : '' ?>>Perusahaan</option>
                            <option value="yayasan" <?= ($edit_donatur['tipe'] ?? '') == 'yayasan' ? 'selected' : '' ?>>Yayasan</option>
                            <option value="lembaga" <?= ($edit_donatur['tipe'] ?? '') == 'lembaga' ? 'selected' : '' ?>>Lembaga</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="kategori">
                            <option value="rutin" <?= ($edit_donatur['kategori'] ?? '') == 'rutin' ? 'selected' : '' ?>>Rutin</option>
                            <option value="sporadis" <?= ($edit_donatur['kategori'] ?? '') == 'sporadis' ? 'selected' : '' ?>>Sporadis</option>
                            <option value="corporate" <?= ($edit_donatur['kategori'] ?? '') == 'corporate' ? 'selected' : '' ?>>Corporate</option>
                            <option value="zakat" <?= ($edit_donatur['kategori'] ?? '') == 'zakat' ? 'selected' : '' ?>>Zakat</option>
                            <option value="infaq" <?= ($edit_donatur['kategori'] ?? '') == 'infaq' ? 'selected' : '' ?>>Infaq</option>
                            <option value="sedekah" <?= ($edit_donatur['kategori'] ?? '') == 'sedekah' ? 'selected' : '' ?>>Sedekah</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Perusahaan (jika perusahaan/yayasan)</label>
                        <input type="text" name="nama_perusahaan" value="<?= $edit_donatur['nama_perusahaan'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>NPWP</label>
                        <input type="text" name="npwp" value="<?= $edit_donatur['npwp'] ?? '' ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>PIC (Person In Charge)</label>
                    <input type="text" name="pic" value="<?= $edit_donatur['pic'] ?? '' ?>" placeholder="Nama penanggung jawab">
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat"><?= $edit_donatur['alamat'] ?? '' ?></textarea>
                </div>
                <?php if($edit_donatur): ?>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="active" <?= $edit_donatur['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $edit_donatur['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Catatan</label>
                    <textarea name="catatan"><?= $edit_donatur['catatan'] ?? '' ?></textarea>
                </div>
                <button type="submit" class="btn btn-success">Simpan</button>
                <button type="button" class="btn" onclick="document.getElementById('modalDonatur').style.display='none'; window.location.href='donatur.php'">Batal</button>
            </form>
        </div>
    </div>
    
</div>

<script>
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        window.location.href = 'donatur.php';
    }
}
</script>

</body>
</html>

