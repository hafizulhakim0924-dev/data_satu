<?php
require_once 'config.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add_donasi') {
        $stmt = $pdo->prepare("INSERT INTO csr_donations (donatur_id, nama_donatur, jumlah, tanggal, metode_pembayaran, kategori, program, keterangan, status) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$_POST['donatur_id'] ?: null, $_POST['nama_donatur'], $_POST['jumlah'], $_POST['tanggal'], $_POST['metode_pembayaran'], $_POST['kategori'], $_POST['program'], $_POST['keterangan'], 'pending']);
        header("Location: donasi.php?msg=Donasi berhasil ditambahkan");
        exit;
    }
    
    if ($action == 'update_status') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE csr_donations SET status=? WHERE id=?");
        $stmt->execute([$_POST['status'], $id]);
        header("Location: donasi.php?msg=Status donasi berhasil diupdate");
        exit;
    }
}

try {
    $donatur_list = $pdo->query("SELECT id, nama FROM donatur WHERE status='active' ORDER BY nama")->fetchAll();
} catch(PDOException $e) {
    $donatur_list = [];
    $error_msg = "Error: " . $e->getMessage();
}

$donatur_id = $_GET['donatur_id'] ?? null;

try {
    $where = $donatur_id ? "WHERE d.donatur_id = $donatur_id" : "";
    $donasi_list = $pdo->query("SELECT d.*, 
        (SELECT nama FROM donatur WHERE id=d.donatur_id) as nama_donatur_db
        FROM csr_donations d $where ORDER BY d.tanggal DESC LIMIT 200")->fetchAll();
} catch(PDOException $e) {
    $donasi_list = [];
}

// Summary
try {
    $total_donasi = $pdo->query("SELECT SUM(jumlah) FROM csr_donations WHERE status='verified'")->fetchColumn() ?: 0;
    $donasi_hari_ini = $pdo->query("SELECT SUM(jumlah) FROM csr_donations WHERE DATE(tanggal)=CURDATE() AND status='verified'")->fetchColumn() ?: 0;
    $donasi_bulan_ini = $pdo->query("SELECT SUM(jumlah) FROM csr_donations WHERE MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE()) AND status='verified'")->fetchColumn() ?: 0;
    $pending = $pdo->query("SELECT COUNT(*) FROM csr_donations WHERE status='pending'")->fetchColumn() ?: 0;
} catch(PDOException $e) {
    $total_donasi = 0;
    $donasi_hari_ini = 0;
    $donasi_bulan_ini = 0;
    $pending = 0;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Donasi - Rangkiang Peduli Negeri</title>
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
.big { font-size:24px; font-weight:bold; color:#2c3e50 }
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
.badge { padding:5px 10px; border-radius:3px; font-size:12px; font-weight:600; display:inline-block }
.badge-pending { background:#f39c12; color:#fff }
.badge-verified { background:#27ae60; color:#fff }
.badge-rejected { background:#e74c3c; color:#fff }
.text-success { color:#27ae60; font-weight:600 }
</style>
</head>
<body>

<div class="navbar">
    <h1>🏛️ Rangkiang Peduli Negeri</h1>
    <?= getNavMenu() ?>
</div>

<div class="container">
    <h1 style="margin:20px 0">💵 Manajemen Donasi</h1>
    
    <?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    
    <?php if(isset($error_msg)): ?>
    <div class="alert" style="background:#f8d7da; color:#721c24; border:1px solid #f5c6cb">
        <strong>⚠️ Error Database:</strong> <?= htmlspecialchars($error_msg) ?>
        <br><small>Pastikan tabel 'csr_donations' sudah dibuat. Jalankan file database_schema.sql terlebih dahulu.</small>
    </div>
    <?php endif; ?>
    
    <div class="grid">
        <div class="card">
            <h3>Total Donasi</h3>
            <div class="big text-success"><?= formatRupiah($total_donasi) ?></div>
        </div>
        <div class="card">
            <h3>Hari Ini</h3>
            <div class="big"><?= formatRupiah($donasi_hari_ini) ?></div>
        </div>
        <div class="card">
            <h3>Bulan Ini</h3>
            <div class="big"><?= formatRupiah($donasi_bulan_ini) ?></div>
        </div>
        <div class="card">
            <h3>Pending</h3>
            <div class="big"><?= $pending ?> donasi</div>
        </div>
    </div>
    
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <h2>Data Donasi</h2>
            <button class="btn" onclick="document.getElementById('modalDonasi').style.display='block'">+ Tambah Donasi</button>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Donatur</th>
                    <th>Jumlah</th>
                    <th>Kategori</th>
                    <th>Program</th>
                    <th>Metode</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($donasi_list)): ?>
                <tr>
                    <td colspan="8" style="text-align:center; padding:40px; color:#999">
                        <div style="font-size:48px; margin-bottom:10px">💰</div>
                        <div>Belum ada data donasi</div>
                        <div style="margin-top:10px">
                            <button class="btn" onclick="document.getElementById('modalDonasi').style.display='block'">+ Tambah Donasi Pertama</button>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach($donasi_list as $d): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($d['tanggal'])) ?></td>
                    <td>
                        <strong><?= htmlspecialchars($d['nama_donatur_db'] ?? $d['nama_donatur']) ?></strong>
                        <?php if($d['donatur_id']): ?>
                        <br><small style="color:#666">ID: <?= $d['donatur_id'] ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-success"><strong><?= formatRupiah($d['jumlah']) ?></strong></td>
                    <td><?= htmlspecialchars($d['kategori']) ?></td>
                    <td><?= htmlspecialchars($d['program'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($d['metode_pembayaran']) ?></td>
                    <td>
                        <span class="badge badge-<?= $d['status'] ?>"><?= ucfirst($d['status']) ?></span>
                    </td>
                    <td>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?= $d['id'] ?>">
                            <select name="status" onchange="this.form.submit()" style="padding:5px; font-size:12px">
                                <option value="pending" <?= $d['status']=='pending'?'selected':'' ?>>Pending</option>
                                <option value="verified" <?= $d['status']=='verified'?'selected':'' ?>>Verified</option>
                                <option value="rejected" <?= $d['status']=='rejected'?'selected':'' ?>>Rejected</option>
                            </select>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Modal Tambah Donasi -->
    <div id="modalDonasi" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modalDonasi').style.display='none'">&times;</span>
            <h2>Tambah Donasi</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_donasi">
                <div class="form-group">
                    <label>Donatur</label>
                    <select name="donatur_id" id="donatur_select" onchange="updateNamaDonatur()">
                        <option value="">Pilih Donatur (atau isi manual)</option>
                        <?php foreach($donatur_list as $don): ?>
                        <option value="<?= $don['id'] ?>" data-nama="<?= htmlspecialchars($don['nama']) ?>"><?= htmlspecialchars($don['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nama Donatur *</label>
                    <input type="text" name="nama_donatur" id="nama_donatur" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Jumlah *</label>
                        <input type="number" name="jumlah" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Tanggal *</label>
                        <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Kategori *</label>
                        <select name="kategori" required>
                            <option value="zakat">Zakat</option>
                            <option value="infaq">Infaq</option>
                            <option value="sedekah">Sedekah</option>
                            <option value="wakaf">Wakaf</option>
                            <option value="csr">CSR</option>
                            <option value="donasi_umum">Donasi Umum</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Metode Pembayaran *</label>
                        <select name="metode_pembayaran" required>
                            <option value="tunai">Tunai</option>
                            <option value="transfer">Transfer</option>
                            <option value="kartu">Kartu</option>
                            <option value="qris">QRIS</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Program</label>
                    <input type="text" name="program" placeholder="Nama program terkait">
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <textarea name="keterangan"></textarea>
                </div>
                <button type="submit" class="btn btn-success">Simpan</button>
                <button type="button" class="btn" onclick="document.getElementById('modalDonasi').style.display='none'">Batal</button>
            </form>
        </div>
    </div>
    
</div>

<script>
function updateNamaDonatur() {
    const select = document.getElementById('donatur_select');
    const input = document.getElementById('nama_donatur');
    const selected = select.options[select.selectedIndex];
    if (selected.value) {
        input.value = selected.getAttribute('data-nama');
    }
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

</body>
</html>

