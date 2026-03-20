<?php
require_once 'config.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add_pemasukan') {
        $stmt = $pdo->prepare("INSERT INTO pemasukan (kategori, sumber, jumlah, tanggal, metode_pembayaran, keterangan, status) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$_POST['kategori'], $_POST['sumber'], $_POST['jumlah'], $_POST['tanggal'], $_POST['metode_pembayaran'], $_POST['keterangan'], 'pending']);
        header("Location: keuangan.php?tab=pemasukan&msg=Pemasukan berhasil ditambahkan");
        exit;
    }
    
    if ($action == 'add_pengeluaran') {
        $stmt = $pdo->prepare("INSERT INTO pengeluaran (kategori, program_id, nama_program, jumlah, tanggal, metode_pembayaran, vendor, keterangan, status) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$_POST['kategori'], $_POST['program_id'], $_POST['nama_program'], $_POST['jumlah'], $_POST['tanggal'], $_POST['metode_pembayaran'], $_POST['vendor'], $_POST['keterangan'], 'draft']);
        header("Location: keuangan.php?tab=pengeluaran&msg=Pengeluaran berhasil ditambahkan");
        exit;
    }
}

$tab = $_GET['tab'] ?? 'dashboard';

// Get Summary
$total_pemasukan = $pdo->query("SELECT SUM(jumlah) FROM pemasukan WHERE status='verified'")->fetchColumn();
$total_pengeluaran = $pdo->query("SELECT SUM(jumlah) FROM pengeluaran WHERE status='paid'")->fetchColumn();
$saldo = $total_pemasukan - $total_pengeluaran;

$pemasukan_bulan = $pdo->query("SELECT SUM(jumlah) FROM pemasukan WHERE MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE()) AND status='verified'")->fetchColumn();
$pengeluaran_bulan = $pdo->query("SELECT SUM(jumlah) FROM pengeluaran WHERE MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE()) AND status='paid'")->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head>
<title>Keuangan - Rangkiang Peduli Negeri</title>
<?= getCssLink() ?>
</head>
<body>

<div class="navbar">
    <h1>🏛️ Rangkiang Peduli Negeri</h1>
    <?= getNavMenu() ?>
</div>

<div class="container">
    <h1 style="margin:20px 0">💰 Manajemen Keuangan</h1>
    
    <?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    
    <div class="tabs">
        <a href="?tab=dashboard" class="<?= $tab=='dashboard'?'active':'' ?>">📊 Dashboard</a>
        <a href="?tab=pemasukan" class="<?= $tab=='pemasukan'?'active':'' ?>">💵 Pemasukan</a>
        <a href="?tab=pengeluaran" class="<?= $tab=='pengeluaran'?'active':'' ?>">💸 Pengeluaran</a>
        <a href="?tab=laporan" class="<?= $tab=='laporan'?'active':'' ?>">📄 Laporan</a>
    </div>
    
    <?php if($tab == 'dashboard'): ?>
    <div class="grid">
        <div class="card">
            <h3>Total Pemasukan</h3>
            <div class="big text-success"><?= formatRupiah($total_pemasukan) ?></div>
        </div>
        <div class="card">
            <h3>Total Pengeluaran</h3>
            <div class="big text-danger"><?= formatRupiah($total_pengeluaran) ?></div>
        </div>
        <div class="card">
            <h3>Saldo</h3>
            <div class="big" style="color:<?= $saldo >= 0 ? '#27ae60' : '#e74c3c' ?>"><?= formatRupiah($saldo) ?></div>
        </div>
        <div class="card">
            <h3>Bulan Ini</h3>
            <div>Pemasukan: <span class="text-success"><?= formatRupiah($pemasukan_bulan) ?></span></div>
            <div>Pengeluaran: <span class="text-danger"><?= formatRupiah($pengeluaran_bulan) ?></span></div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if($tab == 'pemasukan'): ?>
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <h2>Data Pemasukan</h2>
            <div>
                <a href="export_excel.php?type=keuangan&subtype=pemasukan" class="btn btn-success">📥 Export Excel</a>
                <button class="btn" onclick="document.getElementById('modalPemasukan').style.display='block'">+ Tambah Pemasukan</button>
            </div>
        </div>
        <?php
        $pemasukan_list = $pdo->query("SELECT * FROM pemasukan ORDER BY tanggal DESC LIMIT 100")->fetchAll();
        ?>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Sumber</th>
                    <th>Jumlah</th>
                    <th>Metode</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($pemasukan_list as $p): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($p['tanggal'])) ?></td>
                    <td><?= htmlspecialchars($p['kategori']) ?></td>
                    <td><?= htmlspecialchars($p['sumber']) ?></td>
                    <td><strong><?= formatRupiah($p['jumlah']) ?></strong></td>
                    <td><?= htmlspecialchars($p['metode_pembayaran'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['status']) ?></td>
                    <td>
                        <a href="?tab=pemasukan&edit=<?= $p['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Modal Tambah Pemasukan -->
    <div id="modalPemasukan" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modalPemasukan').style.display='none'">&times;</span>
            <h2>Tambah Pemasukan</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_pemasukan">
                <div class="form-row">
                    <div class="form-group">
                        <label>Kategori *</label>
                        <select name="kategori" required>
                            <option value="donasi">Donasi</option>
                            <option value="hibah">Hibah</option>
                            <option value="investasi">Investasi</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal *</label>
                        <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Sumber *</label>
                    <input type="text" name="sumber" required placeholder="Nama donatur/sumber">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Jumlah *</label>
                        <input type="number" name="jumlah" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Metode Pembayaran</label>
                        <select name="metode_pembayaran">
                            <option value="tunai">Tunai</option>
                            <option value="transfer">Transfer</option>
                            <option value="kartu">Kartu</option>
                            <option value="qris">QRIS</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <textarea name="keterangan"></textarea>
                </div>
                <button type="submit" class="btn btn-success">Simpan</button>
                <button type="button" class="btn" onclick="document.getElementById('modalPemasukan').style.display='none'">Batal</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if($tab == 'pengeluaran'): ?>
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <h2>Data Pengeluaran</h2>
            <div>
                <a href="export_excel.php?type=keuangan&subtype=pengeluaran" class="btn btn-success">📥 Export Excel</a>
                <button class="btn" onclick="document.getElementById('modalPengeluaran').style.display='block'">+ Tambah Pengeluaran</button>
            </div>
        </div>
        <?php
        $pengeluaran_list = $pdo->query("SELECT * FROM pengeluaran ORDER BY tanggal DESC LIMIT 100")->fetchAll();
        ?>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Program</th>
                    <th>Vendor</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($pengeluaran_list as $p): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($p['tanggal'])) ?></td>
                    <td><?= htmlspecialchars($p['kategori']) ?></td>
                    <td><?= htmlspecialchars($p['nama_program'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['vendor'] ?? '-') ?></td>
                    <td><strong class="text-danger"><?= formatRupiah($p['jumlah']) ?></strong></td>
                    <td><?= htmlspecialchars($p['status']) ?></td>
                    <td>
                        <a href="?tab=pengeluaran&edit=<?= $p['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Modal Tambah Pengeluaran -->
    <div id="modalPengeluaran" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modalPengeluaran').style.display='none'">&times;</span>
            <h2>Tambah Pengeluaran</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_pengeluaran">
                <div class="form-row">
                    <div class="form-group">
                        <label>Kategori *</label>
                        <select name="kategori" required>
                            <option value="operasional">Operasional</option>
                            <option value="program">Program</option>
                            <option value="gaji">Gaji</option>
                            <option value="utilities">Utilities</option>
                            <option value="transport">Transport</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal *</label>
                        <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Nama Program</label>
                    <input type="text" name="nama_program" placeholder="Nama program terkait">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Jumlah *</label>
                        <input type="number" name="jumlah" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Vendor</label>
                        <input type="text" name="vendor" placeholder="Nama vendor/supplier">
                    </div>
                </div>
                <div class="form-group">
                    <label>Metode Pembayaran</label>
                    <select name="metode_pembayaran">
                        <option value="tunai">Tunai</option>
                        <option value="transfer">Transfer</option>
                        <option value="kartu">Kartu</option>
                        <option value="cek">Cek</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <textarea name="keterangan"></textarea>
                </div>
                <button type="submit" class="btn btn-success">Simpan</button>
                <button type="button" class="btn" onclick="document.getElementById('modalPengeluaran').style.display='none'">Batal</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if($tab == 'laporan'): ?>
    <div class="card">
        <h2>Laporan Keuangan</h2>
        <p>Fitur laporan keuangan akan segera tersedia</p>
    </div>
    <?php endif; ?>
    
</div>

<script>
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

</body>
</html>

