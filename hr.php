<?php
require_once 'config.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add_karyawan') {
        $stmt = $pdo->prepare("INSERT INTO karyawan (nip, nama_lengkap, email, no_hp, alamat, jabatan, departemen, tanggal_masuk, status_karyawan, gaji_pokok, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$_POST['nip'], $_POST['nama'], $_POST['email'], $_POST['no_hp'], $_POST['alamat'], $_POST['jabatan'], $_POST['departemen'], $_POST['tanggal_masuk'], $_POST['status_karyawan'], $_POST['gaji_pokok'], 'active']);
        header("Location: hr.php?msg=Karyawan berhasil ditambahkan");
        exit;
    }
    
    if ($action == 'add_gaji') {
        $total = $_POST['gaji_pokok'] + $_POST['tunjangan'] + $_POST['bonus'] + $_POST['lembur'] - $_POST['potongan'];
        $stmt = $pdo->prepare("INSERT INTO gaji (karyawan_id, periode, gaji_pokok, tunjangan, bonus, lembur, potongan, total_gaji, status) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$_POST['karyawan_id'], $_POST['periode'], $_POST['gaji_pokok'], $_POST['tunjangan'], $_POST['bonus'], $_POST['lembur'], $_POST['potongan'], $total, 'draft']);
        header("Location: hr.php?tab=gaji&msg=Data gaji berhasil ditambahkan");
        exit;
    }
    
    if ($action == 'add_kehadiran') {
        $stmt = $pdo->prepare("INSERT INTO kehadiran (karyawan_id, tanggal, jam_masuk, jam_keluar, status, keterangan) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE jam_masuk=VALUES(jam_masuk), jam_keluar=VALUES(jam_keluar), status=VALUES(status), keterangan=VALUES(keterangan)");
        $stmt->execute([$_POST['karyawan_id'], $_POST['tanggal'], $_POST['jam_masuk'], $_POST['jam_keluar'], $_POST['status'], $_POST['keterangan']]);
        header("Location: hr.php?tab=kehadiran&msg=Data kehadiran berhasil disimpan");
        exit;
    }
    
    if ($action == 'add_performa') {
        $stmt = $pdo->prepare("INSERT INTO performa (karyawan_id, periode, target, pencapaian, nilai_kinerja, catatan, status) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$_POST['karyawan_id'], $_POST['periode'], $_POST['target'], $_POST['pencapaian'], $_POST['nilai_kinerja'], $_POST['catatan'], 'draft']);
        header("Location: hr.php?tab=performa&msg=Data performa berhasil ditambahkan");
        exit;
    }
    
    if ($action == 'add_skill') {
        $stmt = $pdo->prepare("INSERT INTO skill (karyawan_id, nama_skill, kategori, tingkat, sertifikat, keterangan) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$_POST['karyawan_id'], $_POST['nama_skill'], $_POST['kategori'], $_POST['tingkat'], $_POST['sertifikat'], $_POST['keterangan']]);
        header("Location: hr.php?tab=skill&msg=Skill berhasil ditambahkan");
        exit;
    }
    
    if ($action == 'add_volunteer') {
        $stmt = $pdo->prepare("INSERT INTO volunteer (nama, email, no_hp, alamat, tanggal_lahir, jenis_kelamin, pekerjaan, instansi, skill, minat_program, status, tanggal_bergabung) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$_POST['nama'], $_POST['email'], $_POST['no_hp'], $_POST['alamat'], $_POST['tanggal_lahir'], $_POST['jenis_kelamin'], $_POST['pekerjaan'], $_POST['instansi'], $_POST['skill'], $_POST['minat_program'], 'active', date('Y-m-d')]);
        header("Location: hr.php?tab=volunteer&msg=Volunteer berhasil ditambahkan");
        exit;
    }
    
    if ($action == 'delete_volunteer') {
        $id = $_POST['id'] ?? $_GET['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM volunteer WHERE id=?");
            $stmt->execute([$id]);
            header("Location: hr.php?tab=volunteer&msg=Volunteer berhasil dihapus");
            exit;
        }
    }
}

$tab = $_GET['tab'] ?? 'karyawan';
$karyawan_list = $pdo->query("SELECT * FROM karyawan WHERE status='active' ORDER BY nama_lengkap")->fetchAll();
$volunteer_list = $pdo->query("SELECT * FROM volunteer WHERE status='active' ORDER BY nama")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<title>SDM - Rangkiang Peduli Negeri</title>
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
.tabs { display:flex; gap:10px; margin-bottom:20px; border-bottom:2px solid #ddd }
.tabs a { padding:15px 20px; text-decoration:none; color:#555; font-weight:600; border-bottom:3px solid transparent; transition:all 0.3s }
.tabs a:hover, .tabs a.active { color:#2c3e50; border-bottom-color:#3498db }
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
</style>
</head>
<body>

<div class="navbar">
    <h1>🏛️ Rangkiang Peduli Negeri</h1>
    <?= getNavMenu() ?>
</div>

<div class="container">
    <h1 style="margin:20px 0">👥 Manajemen SDM</h1>
    
    <?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    
    <div class="tabs">
        <a href="?tab=karyawan" class="<?= $tab=='karyawan'?'active':'' ?>">👤 Karyawan</a>
        <a href="?tab=gaji" class="<?= $tab=='gaji'?'active':'' ?>">💰 Gaji</a>
        <a href="?tab=kehadiran" class="<?= $tab=='kehadiran'?'active':'' ?>">📅 Kehadiran</a>
        <a href="?tab=performa" class="<?= $tab=='performa'?'active':'' ?>">⭐ Performa</a>
        <a href="?tab=skill" class="<?= $tab=='skill'?'active':'' ?>">🎯 Skill</a>
        <a href="?tab=volunteer" class="<?= $tab=='volunteer'?'active':'' ?>">🤝 Volunteer</a>
    </div>
    
    <?php if($tab == 'karyawan'): ?>
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <h2>Data Karyawan</h2>
            <div>
                <a href="export_excel.php?type=karyawan" class="btn btn-success">📥 Export Excel</a>
                <button class="btn" onclick="document.getElementById('modalKaryawan').style.display='block'">+ Tambah Karyawan</button>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>NIP</th>
                    <th>Nama</th>
                    <th>Jabatan</th>
                    <th>Departemen</th>
                    <th>Status</th>
                    <th>Gaji Pokok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($karyawan_list as $k): ?>
                <tr>
                    <td><?= htmlspecialchars($k['nip']) ?></td>
                    <td><?= htmlspecialchars($k['nama_lengkap']) ?></td>
                    <td><?= htmlspecialchars($k['jabatan'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($k['departemen'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($k['status_karyawan']) ?></td>
                    <td><?= formatRupiah($k['gaji_pokok']) ?></td>
                    <td>
                        <a href="?tab=karyawan&edit=<?= $k['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Modal Tambah Karyawan -->
    <div id="modalKaryawan" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modalKaryawan').style.display='none'">&times;</span>
            <h2>Tambah Karyawan</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_karyawan">
                <div class="form-group">
                    <label>NIP *</label>
                    <input type="text" name="nip" required>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="nama" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email">
                    </div>
                    <div class="form-group">
                        <label>No HP</label>
                        <input type="text" name="no_hp">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Jabatan</label>
                        <input type="text" name="jabatan">
                    </div>
                    <div class="form-group">
                        <label>Departemen</label>
                        <input type="text" name="departemen">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal Masuk</label>
                        <input type="date" name="tanggal_masuk">
                    </div>
                    <div class="form-group">
                        <label>Status Karyawan</label>
                        <select name="status_karyawan">
                            <option value="kontrak">Kontrak</option>
                            <option value="tetap">Tetap</option>
                            <option value="magang">Magang</option>
                            <option value="volunteer">Volunteer</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Gaji Pokok</label>
                    <input type="number" name="gaji_pokok" step="0.01">
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat"></textarea>
                </div>
                <button type="submit" class="btn btn-success">Simpan</button>
                <button type="button" class="btn" onclick="document.getElementById('modalKaryawan').style.display='none'">Batal</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if($tab == 'gaji'): ?>
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <h2>Data Gaji</h2>
            <button class="btn" onclick="document.getElementById('modalGaji').style.display='block'">+ Tambah Data Gaji</button>
        </div>
        <?php
        $gaji_list = $pdo->query("SELECT g.*, k.nama_lengkap, k.nip FROM gaji g JOIN karyawan k ON g.karyawan_id=k.id ORDER BY g.periode DESC, k.nama_lengkap")->fetchAll();
        ?>
        <table>
            <thead>
                <tr>
                    <th>Periode</th>
                    <th>Nama</th>
                    <th>Gaji Pokok</th>
                    <th>Tunjangan</th>
                    <th>Bonus</th>
                    <th>Potongan</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($gaji_list as $g): ?>
                <tr>
                    <td><?= htmlspecialchars($g['periode']) ?></td>
                    <td><?= htmlspecialchars($g['nama_lengkap']) ?></td>
                    <td><?= formatRupiah($g['gaji_pokok']) ?></td>
                    <td><?= formatRupiah($g['tunjangan']) ?></td>
                    <td><?= formatRupiah($g['bonus']) ?></td>
                    <td><?= formatRupiah($g['potongan']) ?></td>
                    <td><strong><?= formatRupiah($g['total_gaji']) ?></strong></td>
                    <td><?= htmlspecialchars($g['status']) ?></td>
                    <td>
                        <a href="?tab=gaji&edit=<?= $g['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Modal Tambah Gaji -->
    <div id="modalGaji" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modalGaji').style.display='none'">&times;</span>
            <h2>Tambah Data Gaji</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_gaji">
                <div class="form-row">
                    <div class="form-group">
                        <label>Karyawan *</label>
                        <select name="karyawan_id" required>
                            <option value="">Pilih Karyawan</option>
                            <?php foreach($karyawan_list as $k): ?>
                            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_lengkap']) ?> (<?= $k['nip'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Periode (YYYY-MM) *</label>
                        <input type="text" name="periode" placeholder="2024-01" required pattern="\d{4}-\d{2}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Gaji Pokok</label>
                        <input type="number" name="gaji_pokok" step="0.01" value="0">
                    </div>
                    <div class="form-group">
                        <label>Tunjangan</label>
                        <input type="number" name="tunjangan" step="0.01" value="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Bonus</label>
                        <input type="number" name="bonus" step="0.01" value="0">
                    </div>
                    <div class="form-group">
                        <label>Lembur</label>
                        <input type="number" name="lembur" step="0.01" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label>Potongan</label>
                    <input type="number" name="potongan" step="0.01" value="0">
                </div>
                <button type="submit" class="btn btn-success">Simpan</button>
                <button type="button" class="btn" onclick="document.getElementById('modalGaji').style.display='none'">Batal</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if($tab == 'kehadiran'): ?>
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <h2>Data Kehadiran</h2>
            <button class="btn" onclick="document.getElementById('modalKehadiran').style.display='block'">+ Tambah Kehadiran</button>
        </div>
        <?php
        $kehadiran_list = $pdo->query("SELECT k.*, ka.nama_lengkap, ka.nip FROM kehadiran k JOIN karyawan ka ON k.karyawan_id=ka.id ORDER BY k.tanggal DESC LIMIT 100")->fetchAll();
        ?>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>Jam Masuk</th>
                    <th>Jam Keluar</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($kehadiran_list as $h): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($h['tanggal'])) ?></td>
                    <td><?= htmlspecialchars($h['nama_lengkap']) ?></td>
                    <td><?= $h['jam_masuk'] ? date('H:i', strtotime($h['jam_masuk'])) : '-' ?></td>
                    <td><?= $h['jam_keluar'] ? date('H:i', strtotime($h['jam_keluar'])) : '-' ?></td>
                    <td><?= htmlspecialchars($h['status']) ?></td>
                    <td><?= htmlspecialchars($h['keterangan'] ?? '-') ?></td>
                    <td>
                        <a href="?tab=kehadiran&edit=<?= $h['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Modal Tambah Kehadiran -->
    <div id="modalKehadiran" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modalKehadiran').style.display='none'">&times;</span>
            <h2>Tambah Kehadiran</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_kehadiran">
                <div class="form-row">
                    <div class="form-group">
                        <label>Karyawan *</label>
                        <select name="karyawan_id" required>
                            <option value="">Pilih Karyawan</option>
                            <?php foreach($karyawan_list as $k): ?>
                            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_lengkap']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal *</label>
                        <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Jam Masuk</label>
                        <input type="time" name="jam_masuk">
                    </div>
                    <div class="form-group">
                        <label>Jam Keluar</label>
                        <input type="time" name="jam_keluar">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Status *</label>
                        <select name="status" required>
                            <option value="hadir">Hadir</option>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                            <option value="cuti">Cuti</option>
                            <option value="alpha">Alpha</option>
                            <option value="libur">Libur</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <textarea name="keterangan"></textarea>
                </div>
                <button type="submit" class="btn btn-success">Simpan</button>
                <button type="button" class="btn" onclick="document.getElementById('modalKehadiran').style.display='none'">Batal</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if($tab == 'performa'): ?>
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <h2>Data Performa</h2>
            <button class="btn" onclick="document.getElementById('modalPerforma').style.display='block'">+ Tambah Performa</button>
        </div>
        <?php
        $performa_list = $pdo->query("SELECT p.*, k.nama_lengkap, k.nip FROM performa p JOIN karyawan k ON p.karyawan_id=k.id ORDER BY p.periode DESC")->fetchAll();
        ?>
        <table>
            <thead>
                <tr>
                    <th>Periode</th>
                    <th>Nama</th>
                    <th>Nilai Kinerja</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($performa_list as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['periode']) ?></td>
                    <td><?= htmlspecialchars($p['nama_lengkap']) ?></td>
                    <td><strong><?= number_format($p['nilai_kinerja'] ?? 0, 2) ?></strong></td>
                    <td><?= htmlspecialchars($p['status']) ?></td>
                    <td>
                        <a href="?tab=performa&view=<?= $p['id'] ?>" class="btn btn-sm">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Modal Tambah Performa -->
    <div id="modalPerforma" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modalPerforma').style.display='none'">&times;</span>
            <h2>Tambah Performa</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_performa">
                <div class="form-row">
                    <div class="form-group">
                        <label>Karyawan *</label>
                        <select name="karyawan_id" required>
                            <option value="">Pilih Karyawan</option>
                            <?php foreach($karyawan_list as $k): ?>
                            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_lengkap']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Periode (YYYY-MM) *</label>
                        <input type="text" name="periode" placeholder="2024-01" required pattern="\d{4}-\d{2}">
                    </div>
                </div>
                <div class="form-group">
                    <label>Target</label>
                    <textarea name="target"></textarea>
                </div>
                <div class="form-group">
                    <label>Pencapaian</label>
                    <textarea name="pencapaian"></textarea>
                </div>
                <div class="form-group">
                    <label>Nilai Kinerja (0-100)</label>
                    <input type="number" name="nilai_kinerja" min="0" max="100" step="0.01">
                </div>
                <div class="form-group">
                    <label>Catatan</label>
                    <textarea name="catatan"></textarea>
                </div>
                <button type="submit" class="btn btn-success">Simpan</button>
                <button type="button" class="btn" onclick="document.getElementById('modalPerforma').style.display='none'">Batal</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if($tab == 'skill'): ?>
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <h2>Data Skill/Kompetensi</h2>
            <button class="btn" onclick="document.getElementById('modalSkill').style.display='block'">+ Tambah Skill</button>
        </div>
        <?php
        $skill_list = $pdo->query("SELECT s.*, k.nama_lengkap FROM skill s JOIN karyawan k ON s.karyawan_id=k.id ORDER BY k.nama_lengkap, s.nama_skill")->fetchAll();
        ?>
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Skill</th>
                    <th>Kategori</th>
                    <th>Tingkat</th>
                    <th>Sertifikat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($skill_list as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['nama_lengkap']) ?></td>
                    <td><?= htmlspecialchars($s['nama_skill']) ?></td>
                    <td><?= htmlspecialchars($s['kategori']) ?></td>
                    <td><?= htmlspecialchars($s['tingkat']) ?></td>
                    <td><?= $s['sertifikat'] ? '✓' : '-' ?></td>
                    <td>
                        <a href="?tab=skill&edit=<?= $s['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Modal Tambah Skill -->
    <div id="modalSkill" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modalSkill').style.display='none'">&times;</span>
            <h2>Tambah Skill</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_skill">
                <div class="form-group">
                    <label>Karyawan *</label>
                    <select name="karyawan_id" required>
                        <option value="">Pilih Karyawan</option>
                        <?php foreach($karyawan_list as $k): ?>
                        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_lengkap']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Skill *</label>
                        <input type="text" name="nama_skill" required>
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="kategori">
                            <option value="hard_skill">Hard Skill</option>
                            <option value="soft_skill">Soft Skill</option>
                            <option value="bahasa">Bahasa</option>
                            <option value="sertifikasi">Sertifikasi</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tingkat</label>
                        <select name="tingkat">
                            <option value="pemula">Pemula</option>
                            <option value="menengah">Menengah</option>
                            <option value="mahir">Mahir</option>
                            <option value="expert">Expert</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>No Sertifikat</label>
                        <input type="text" name="sertifikat">
                    </div>
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <textarea name="keterangan"></textarea>
                </div>
                <button type="submit" class="btn btn-success">Simpan</button>
                <button type="button" class="btn" onclick="document.getElementById('modalSkill').style.display='none'">Batal</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if($tab == 'volunteer'): ?>
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <h2>Data Volunteer</h2>
            <button class="btn" onclick="document.getElementById('modalVolunteer').style.display='block'">+ Tambah Volunteer</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>No HP</th>
                    <th>Email</th>
                    <th>Pekerjaan</th>
                    <th>Instansi</th>
                    <th>Total Jam</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($volunteer_list as $v): ?>
                <tr>
                    <td><?= htmlspecialchars($v['nama']) ?></td>
                    <td><?= htmlspecialchars($v['no_hp']) ?></td>
                    <td><?= htmlspecialchars($v['email'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($v['pekerjaan'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($v['instansi'] ?? '-') ?></td>
                    <td><?= $v['total_jam_kerja'] ?> jam</td>
                    <td><?= htmlspecialchars($v['status']) ?></td>
                    <td>
                        <a href="?tab=volunteer&view=<?= $v['id'] ?>" class="btn btn-sm">View</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Yakin ingin menghapus volunteer ini?')">
                            <input type="hidden" name="action" value="delete_volunteer">
                            <input type="hidden" name="id" value="<?= $v['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Modal Tambah Volunteer -->
    <div id="modalVolunteer" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modalVolunteer').style.display='none'">&times;</span>
            <h2>Tambah Volunteer</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_volunteer">
                <div class="form-group">
                    <label>Nama *</label>
                    <input type="text" name="nama" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email">
                    </div>
                    <div class="form-group">
                        <label>No HP *</label>
                        <input type="text" name="no_hp" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir">
                    </div>
                    <div class="form-group">
                        <label>Jenis Kelamin</label>
                        <select name="jenis_kelamin">
                            <option value="">Pilih</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Pekerjaan</label>
                        <input type="text" name="pekerjaan">
                    </div>
                    <div class="form-group">
                        <label>Instansi</label>
                        <input type="text" name="instansi">
                    </div>
                </div>
                <div class="form-group">
                    <label>Skill</label>
                    <textarea name="skill" placeholder="Pisahkan dengan koma"></textarea>
                </div>
                <div class="form-group">
                    <label>Minat Program</label>
                    <input type="text" name="minat_program" placeholder="Pisahkan dengan koma">
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat"></textarea>
                </div>
                <button type="submit" class="btn btn-success">Simpan</button>
                <button type="button" class="btn" onclick="document.getElementById('modalVolunteer').style.display='none'">Batal</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
</div>

<script>
// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

</body>
</html>

