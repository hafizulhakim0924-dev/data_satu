<?php
require_once 'config.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add_donatur') {
        $stmt = $pdo->prepare("INSERT INTO donatur (nama, email, no_hp, alamat, tipe, npwp, nama_perusahaan, pic, kategori, klasifikasi_id, status, catatan) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$_POST['nama'], $_POST['email'], $_POST['no_hp'], $_POST['alamat'], $_POST['tipe'], $_POST['npwp'], $_POST['nama_perusahaan'], $_POST['pic'], $_POST['kategori'], $_POST['klasifikasi_id'] ?: null, 'active', $_POST['catatan']]);
        header("Location: donatur.php?msg=Donatur berhasil ditambahkan");
        exit;
    }
    
    if ($action == 'update_donatur') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE donatur SET nama=?, email=?, no_hp=?, alamat=?, tipe=?, npwp=?, nama_perusahaan=?, pic=?, kategori=?, klasifikasi_id=?, status=?, catatan=? WHERE id=?");
        $stmt->execute([$_POST['nama'], $_POST['email'], $_POST['no_hp'], $_POST['alamat'], $_POST['tipe'], $_POST['npwp'], $_POST['nama_perusahaan'], $_POST['pic'], $_POST['kategori'], $_POST['klasifikasi_id'] ?: null, $_POST['status'], $_POST['catatan'], $id]);
        header("Location: donatur.php?msg=Donatur berhasil diupdate");
        exit;
    }
}

try {
    // Cek apakah kolom donatur_id ada di tabel csr_donations
    $check_column = $pdo->query("SHOW COLUMNS FROM csr_donations LIKE 'donatur_id'")->fetch();
    
    if ($check_column) {
        // Kolom ada, gunakan query normal
        $donatur_list = $pdo->query("SELECT d.*, 
            k.nama_klasifikasi, k.warna, k.indikator, k.kode as kode_klasifikasi,
            (SELECT SUM(jumlah) FROM csr_donations WHERE donatur_id=d.id) as total_donasi,
            (SELECT COUNT(*) FROM csr_donations WHERE donatur_id=d.id) as jumlah_donasi
            FROM donatur d 
            LEFT JOIN klasifikasi_donatur k ON d.klasifikasi_id=k.id
            ORDER BY d.nama")->fetchAll();
        
        // Ambil data donasi untuk setiap donatur (untuk modal detail)
        $donatur_donasi = [];
        foreach($donatur_list as $d) {
            $donasi_list = $pdo->prepare("SELECT * FROM csr_donations WHERE donatur_id = ? ORDER BY tanggal DESC LIMIT 50");
            $donasi_list->execute([$d['id']]);
            $donatur_donasi[$d['id']] = $donasi_list->fetchAll();
        }
        
        // Hitung max total donasi untuk normalisasi bar chart
        $max_total = 0;
        foreach($donatur_list as $d) {
            if($d['total_donasi'] > $max_total) {
                $max_total = $d['total_donasi'];
            }
        }
    } else {
        // Kolom belum ada, gunakan query tanpa subquery donatur_id
        $donatur_list = $pdo->query("SELECT d.*, 
            k.nama_klasifikasi, k.warna, k.indikator, k.kode as kode_klasifikasi,
            0 as total_donasi,
            0 as jumlah_donasi
            FROM donatur d 
            LEFT JOIN klasifikasi_donatur k ON d.klasifikasi_id=k.id
            ORDER BY d.nama")->fetchAll();
        $error_msg = "Kolom 'donatur_id' belum ada di tabel csr_donations. Jalankan file database_schema.sql untuk memperbaiki struktur tabel.";
        $donatur_donasi = [];
        $max_total = 0;
    }
} catch(PDOException $e) {
    $donatur_list = [];
    $error_msg = "Error: " . $e->getMessage();
    $donatur_donasi = [];
    $max_total = 0;
}

// Get klasifikasi list for dropdown
try {
    $klasifikasi_list = $pdo->query("SELECT * FROM klasifikasi_donatur WHERE status='active' ORDER BY prioritas DESC, nama_klasifikasi")->fetchAll();
} catch(PDOException $e) {
    $klasifikasi_list = [];
}

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
.bar-chart-container { transition:transform 0.2s }
.bar-chart-container:hover { transform:scale(1.02) }
.bar-chart-container:hover .bar-fill { background:#229954 !important }
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
    
    <?php if(isset($error_msg)): ?>
    <div class="alert" style="background:#f8d7da; color:#721c24; border:1px solid #f5c6cb">
        <strong>⚠️ Error Database:</strong> <?= htmlspecialchars($error_msg) ?>
        <br><small>Pastikan tabel 'donatur' sudah dibuat. Jalankan file database_schema.sql terlebih dahulu.</small>
        <?php if(strpos($error_msg, 'donatur_id') !== false): ?>
        <br><br><strong>Solusi:</strong> Jalankan file <code>fix_csr_donations_donatur_id.sql</code> untuk menambahkan kolom 'donatur_id' ke tabel csr_donations.
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <h2>Data Donatur</h2>
            <div>
                <a href="export_excel.php?type=donatur" class="btn btn-success">📥 Export Excel</a>
                <button class="btn" onclick="document.getElementById('modalDonatur').style.display='block'">+ Tambah Donatur</button>
            </div>
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
                    <th>Klasifikasi</th>
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
                <?php if(empty($donatur_list)): ?>
                <tr>
                    <td colspan="9" style="text-align:center; padding:40px; color:#999">
                        <div style="font-size:48px; margin-bottom:10px">📭</div>
                        <div>Belum ada data donatur</div>
                        <div style="margin-top:10px">
                            <button class="btn" onclick="document.getElementById('modalDonatur').style.display='block'">+ Tambah Donatur Pertama</button>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach($donatur_list as $d): 
                    $total_donasi = (float)($d['total_donasi'] ?? 0);
                    $jumlah_donasi = (int)($d['jumlah_donasi'] ?? 0);
                    $bar_width = $max_total > 0 ? ($total_donasi / $max_total * 100) : 0;
                    if($bar_width > 100) $bar_width = 100;
                ?>
                <tr style="<?= $d['warna'] ? 'border-left: 4px solid ' . htmlspecialchars($d['warna']) : '' ?>">
                    <td>
                        <div style="display:flex; align-items:center; gap:10px">
                            <div style="flex:1">
                                <strong><?= htmlspecialchars($d['nama']) ?></strong>
                                <?php if($d['nama_perusahaan']): ?>
                                <br><small style="color:#666"><?= htmlspecialchars($d['nama_perusahaan']) ?></small>
                                <?php endif; ?>
                            </div>
                            <?php if($jumlah_donasi > 0): ?>
                            <div class="bar-chart-container" style="flex:2; min-width:150px; cursor:pointer" onclick="showDonasiDetail(<?= $d['id'] ?>, <?= json_encode($d['nama']) ?>)" title="Klik untuk melihat detail donasi">
                                <div style="background:#e8f5e9; border-radius:4px; height:24px; position:relative; overflow:hidden; box-shadow:inset 0 1px 3px rgba(0,0,0,0.1)">
                                    <div class="bar-fill" style="background:#27ae60; height:100%; width:<?= $bar_width ?>%; transition:width 0.3s, background 0.2s; display:flex; align-items:center; justify-content:flex-end; padding-right:5px; box-shadow:0 1px 3px rgba(0,0,0,0.2)">
                                        <span style="color:#fff; font-size:11px; font-weight:bold; text-shadow:0 1px 2px rgba(0,0,0,0.3)"><?= $jumlah_donasi ?>x</span>
                                    </div>
                                </div>
                                <small style="color:#666; font-size:10px; display:block; margin-top:2px; font-weight:500"><?= formatRupiah($total_donasi) ?></small>
                            </div>
                            <?php else: ?>
                            <div style="flex:2; min-width:150px">
                                <div style="background:#f5f5f5; border-radius:4px; height:24px; display:flex; align-items:center; justify-content:center">
                                    <span style="color:#999; font-size:11px">Belum ada donasi</span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php if($d['nama_klasifikasi']): ?>
                        <span class="badge" style="background:<?= htmlspecialchars($d['warna'] ?? '#95a5a6') ?>; color:#fff; padding:5px 10px; border-radius:4px">
                            <?= htmlspecialchars($d['indikator'] ?? '') ?> <?= htmlspecialchars($d['nama_klasifikasi']) ?>
                        </span>
                        <?php else: ?>
                        <span style="color:#999; font-size:12px">-</span>
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
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Modal Tambah/Edit Donatur -->
    <div id="modalDonatur" class="modal" style="<?= $edit_donatur ? 'display:block' : '' ?>">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modalDonatur').style.display='none'; window.location.href='donatur.php'">&times;</span>
            <h2><?= $edit_donatur ? 'Edit Donatur' : 'Tambah Donatur' ?></h2>
            <form method="POST" id="formDonatur">
                <input type="hidden" name="action" value="<?= $edit_donatur ? 'update_donatur' : 'add_donatur' ?>">
                <?php if($edit_donatur): ?>
                <input type="hidden" name="id" value="<?= $edit_donatur['id'] ?>">
                <?php endif; ?>
                
                <div style="border-bottom:2px solid #eee; padding-bottom:15px; margin-bottom:20px">
                    <h3 style="color:#2c3e50; margin-bottom:15px">📋 Informasi Dasar</h3>
                    <div class="form-group">
                        <label>Nama Donatur *</label>
                        <input type="text" name="nama" value="<?= $edit_donatur['nama'] ?? '' ?>" required placeholder="Nama lengkap donatur">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tipe Donatur *</label>
                            <select name="tipe" required id="tipe_donatur" onchange="togglePerusahaanFields()">
                                <option value="individu" <?= ($edit_donatur['tipe'] ?? '') == 'individu' ? 'selected' : '' ?>>👤 Individu</option>
                                <option value="perusahaan" <?= ($edit_donatur['tipe'] ?? '') == 'perusahaan' ? 'selected' : '' ?>>🏢 Perusahaan</option>
                                <option value="yayasan" <?= ($edit_donatur['tipe'] ?? '') == 'yayasan' ? 'selected' : '' ?>>🏛️ Yayasan</option>
                                <option value="lembaga" <?= ($edit_donatur['tipe'] ?? '') == 'lembaga' ? 'selected' : '' ?>>🏛️ Lembaga</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Kategori Donasi</label>
                            <select name="kategori">
                                <option value="rutin" <?= ($edit_donatur['kategori'] ?? '') == 'rutin' ? 'selected' : '' ?>>🔄 Rutin</option>
                                <option value="sporadis" <?= ($edit_donatur['kategori'] ?? '') == 'sporadis' ? 'selected' : '' ?>>📅 Sporadis</option>
                                <option value="corporate" <?= ($edit_donatur['kategori'] ?? '') == 'corporate' ? 'selected' : '' ?>>💼 Corporate</option>
                                <option value="zakat" <?= ($edit_donatur['kategori'] ?? '') == 'zakat' ? 'selected' : '' ?>>🕌 Zakat</option>
                                <option value="infaq" <?= ($edit_donatur['kategori'] ?? '') == 'infaq' ? 'selected' : '' ?>>💝 Infaq</option>
                                <option value="sedekah" <?= ($edit_donatur['kategori'] ?? '') == 'sedekah' ? 'selected' : '' ?>>🤲 Sedekah</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Klasifikasi Donatur</label>
                        <select name="klasifikasi_id">
                            <option value="">Pilih Klasifikasi (Opsional)</option>
                            <?php foreach($klasifikasi_list as $k): ?>
                            <option value="<?= $k['id'] ?>" 
                                <?= ($edit_donatur['klasifikasi_id'] ?? '') == $k['id'] ? 'selected' : '' ?>
                                style="background:<?= htmlspecialchars($k['warna']) ?>20">
                                <?= htmlspecialchars($k['indikator'] ?? '') ?> <?= htmlspecialchars($k['nama_klasifikasi']) ?>
                                <?php if($k['minimal_donasi']): ?>
                                (Min: <?= formatRupiah($k['minimal_donasi']) ?>)
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color:#666">Atau <a href="klasifikasi_donatur.php" target="_blank">kelola klasifikasi</a></small>
                    </div>
                </div>
                
                <div style="border-bottom:2px solid #eee; padding-bottom:15px; margin-bottom:20px">
                    <h3 style="color:#2c3e50; margin-bottom:15px">📞 Kontak</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?= $edit_donatur['email'] ?? '' ?>" placeholder="email@example.com">
                        </div>
                        <div class="form-group">
                            <label>No HP / WhatsApp</label>
                            <input type="text" name="no_hp" value="<?= $edit_donatur['no_hp'] ?? '' ?>" placeholder="081234567890">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Alamat Lengkap</label>
                        <textarea name="alamat" rows="3" placeholder="Alamat lengkap donatur"><?= $edit_donatur['alamat'] ?? '' ?></textarea>
                    </div>
                </div>
                
                <div id="perusahaan_fields" style="border-bottom:2px solid #eee; padding-bottom:15px; margin-bottom:20px; <?= ($edit_donatur['tipe'] ?? 'individu') == 'individu' ? 'display:none' : '' ?>">
                    <h3 style="color:#2c3e50; margin-bottom:15px">🏢 Informasi Perusahaan/Yayasan/Lembaga</h3>
                    <div class="form-group">
                        <label>Nama Perusahaan/Yayasan/Lembaga</label>
                        <input type="text" name="nama_perusahaan" id="nama_perusahaan_field" value="<?= $edit_donatur['nama_perusahaan'] ?? '' ?>" placeholder="Nama perusahaan/yayasan/lembaga">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>NPWP</label>
                            <input type="text" name="npwp" id="npwp_field" value="<?= $edit_donatur['npwp'] ?? '' ?>" placeholder="12.345.678.9-012.345">
                        </div>
                        <div class="form-group">
                            <label>PIC (Person In Charge)</label>
                            <input type="text" name="pic" id="pic_field" value="<?= $edit_donatur['pic'] ?? '' ?>" placeholder="Nama penanggung jawab">
                        </div>
                    </div>
                </div>
                
                <div style="border-bottom:2px solid #eee; padding-bottom:15px; margin-bottom:20px">
                    <h3 style="color:#2c3e50; margin-bottom:15px">📝 Informasi Tambahan</h3>
                    <?php if($edit_donatur): ?>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="active" <?= $edit_donatur['status'] == 'active' ? 'selected' : '' ?>>✅ Active</option>
                            <option value="inactive" <?= $edit_donatur['status'] == 'inactive' ? 'selected' : '' ?>>❌ Inactive</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea name="catatan" rows="4" placeholder="Catatan tambahan tentang donatur"><?= $edit_donatur['catatan'] ?? '' ?></textarea>
                    </div>
                </div>
                
                <div style="text-align:right; margin-top:20px">
                    <button type="button" class="btn" onclick="document.getElementById('modalDonatur').style.display='none'; window.location.href='donatur.php'">Batal</button>
                    <button type="submit" class="btn btn-success">💾 Simpan</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Detail Donasi -->
    <div id="modalDonasiDetail" class="modal">
        <div class="modal-content" style="max-width:900px">
            <span class="close" onclick="document.getElementById('modalDonasiDetail').style.display='none'">&times;</span>
            <h2 id="modalDonasiTitle">Detail Donasi</h2>
            <div id="modalDonasiContent">
                <p>Memuat data...</p>
            </div>
        </div>
    </div>
    
</div>

<script>
// Data donasi untuk setiap donatur (dari PHP)
const donaturDonasiData = <?= json_encode($donatur_donasi ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

function showDonasiDetail(donaturId, namaDonatur) {
    const modal = document.getElementById('modalDonasiDetail');
    const title = document.getElementById('modalDonasiTitle');
    const content = document.getElementById('modalDonasiContent');
    
    title.textContent = 'Detail Donasi - ' + namaDonatur;
    
    const donasiList = donaturDonasiData[donaturId] || [];
    
    if (donasiList.length === 0) {
        content.innerHTML = '<p style="text-align:center; padding:40px; color:#999">Belum ada data donasi untuk donatur ini.</p>';
    } else {
        let total = 0;
        let html = '<div style="margin-bottom:20px">';
        html += '<div class="grid" style="grid-template-columns:repeat(3,1fr); gap:15px; margin-bottom:20px">';
        html += '<div class="card"><strong>Total Donasi</strong><div style="font-size:24px; color:#27ae60">' + formatRupiahJS(donasiList.reduce((sum, d) => sum + parseFloat(d.jumlah || 0), 0)) + '</div></div>';
        html += '<div class="card"><strong>Jumlah Donasi</strong><div style="font-size:24px; color:#3498db">' + donasiList.length + 'x</div></div>';
        html += '<div class="card"><strong>Rata-rata</strong><div style="font-size:24px; color:#f39c12">' + formatRupiahJS(donasiList.reduce((sum, d) => sum + parseFloat(d.jumlah || 0), 0) / donasiList.length) + '</div></div>';
        html += '</div></div>';
        
        html += '<table style="width:100%; margin-top:20px">';
        html += '<thead><tr><th>Tanggal</th><th>Jumlah</th><th>Kategori</th><th>Metode</th><th>Program</th><th>Status</th></tr></thead>';
        html += '<tbody>';
        
        donasiList.forEach(function(d) {
            const statusColor = d.status === 'verified' ? '#27ae60' : d.status === 'pending' ? '#f39c12' : '#e74c3c';
            const statusText = d.status === 'verified' ? '✓ Verified' : d.status === 'pending' ? '⏳ Pending' : '✗ Rejected';
            html += '<tr>';
            html += '<td>' + formatDateJS(d.tanggal) + '</td>';
            html += '<td class="text-success"><strong>' + formatRupiahJS(parseFloat(d.jumlah || 0)) + '</strong></td>';
            html += '<td>' + (d.kategori || '-') + '</td>';
            html += '<td>' + (d.metode_pembayaran || '-') + '</td>';
            html += '<td>' + (d.program || '-') + '</td>';
            html += '<td><span style="color:' + statusColor + '; font-weight:bold">' + statusText + '</span></td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        content.innerHTML = html;
    }
    
    modal.style.display = 'block';
}

function formatRupiahJS(angka) {
    return 'Rp ' + angka.toLocaleString('id-ID');
}

function formatDateJS(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' });
}

function togglePerusahaanFields() {
    const tipe = document.getElementById('tipe_donatur').value;
    const perusahaanFields = document.getElementById('perusahaan_fields');
    const namaPerusahaan = document.getElementById('nama_perusahaan_field');
    const npwp = document.getElementById('npwp_field');
    const pic = document.getElementById('pic_field');
    
    if (tipe === 'perusahaan' || tipe === 'yayasan' || tipe === 'lembaga') {
        perusahaanFields.style.display = 'block';
        if (tipe === 'perusahaan') {
            namaPerusahaan.placeholder = 'Nama Perusahaan';
        } else if (tipe === 'yayasan') {
            namaPerusahaan.placeholder = 'Nama Yayasan';
        } else {
            namaPerusahaan.placeholder = 'Nama Lembaga';
        }
    } else {
        perusahaanFields.style.display = 'none';
        namaPerusahaan.value = '';
        npwp.value = '';
        pic.value = '';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    togglePerusahaanFields();
});

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        window.location.href = 'donatur.php';
    }
}
</script>

</body>
</html>

