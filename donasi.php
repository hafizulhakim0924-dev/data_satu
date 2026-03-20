<?php
// Start output buffering to prevent any output before header
ob_start();

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, but log them
ini_set('log_errors', 1);

require_once 'config.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add_donasi') {
        try {
            // Validate required fields
            if (empty($_POST['nama_donatur']) || empty($_POST['jumlah']) || empty($_POST['tanggal'])) {
                ob_end_clean();
                header("Location: donasi.php?error=" . urlencode("Data tidak lengkap. Pastikan nama donatur, jumlah, dan tanggal diisi."));
                exit;
            }
            
            // Prepare values
            $donatur_id = !empty($_POST['donatur_id']) ? (int)$_POST['donatur_id'] : null;
            $nama_donatur = trim($_POST['nama_donatur']);
            $jumlah = (float)$_POST['jumlah'];
            $tanggal = $_POST['tanggal'];
            $metode_pembayaran = $_POST['metode_pembayaran'] ?? 'tunai';
            $kategori = $_POST['kategori'] ?? 'donasi_umum';
            $program = !empty($_POST['program']) ? trim($_POST['program']) : null;
            $keterangan = !empty($_POST['keterangan']) ? trim($_POST['keterangan']) : null;
            
            // Validate jumlah
            if ($jumlah <= 0) {
                if (ob_get_level()) {
                    ob_end_clean();
                }
                header("Location: donasi.php?error=" . urlencode("Jumlah donasi harus lebih dari 0"));
                exit;
            }
            
            // Insert donasi
            $stmt = $pdo->prepare("INSERT INTO csr_donations (donatur_id, nama_donatur, jumlah, tanggal, metode_pembayaran, kategori, program, keterangan, status) VALUES (?,?,?,?,?,?,?,?,?)");
            
            // Execute with error handling
            try {
                $result = $stmt->execute([$donatur_id, $nama_donatur, $jumlah, $tanggal, $metode_pembayaran, $kategori, $program, $keterangan, 'pending']);
                
                if ($result) {
                    // Clear any output buffer before redirect
                    if (ob_get_level()) {
                        ob_end_clean();
                    }
                    header("Location: donasi.php?msg=" . urlencode("Donasi berhasil ditambahkan"));
                    exit;
                } else {
                    throw new Exception("Gagal menyimpan donasi ke database");
                }
            } catch(PDOException $ex) {
                // Re-throw as PDOException to be caught by outer catch
                throw $ex;
            }
        } catch(PDOException $e) {
            // Log error and redirect with error message
            error_log("Error adding donasi: " . $e->getMessage());
            // Clear output buffer
            if (ob_get_level()) {
                ob_end_clean();
            }
            $error_msg = urlencode("Error menyimpan donasi. Pastikan semua kolom di database sudah ada.");
            header("Location: donasi.php?error=" . $error_msg);
            exit;
        } catch(Exception $e) {
            error_log("Error adding donasi: " . $e->getMessage());
            // Clear output buffer
            if (ob_get_level()) {
                ob_end_clean();
            }
            $error_msg = urlencode("Error: " . $e->getMessage());
            header("Location: donasi.php?error=" . $error_msg);
            exit;
        }
    }
    
    if ($action == 'update_status') {
        try {
            $id = (int)$_POST['id'];
            $status = $_POST['status'] ?? 'pending';
            
            $stmt = $pdo->prepare("UPDATE csr_donations SET status=? WHERE id=?");
            $stmt->execute([$status, $id]);
            
            if (ob_get_level()) {
                ob_end_clean();
            }
            header("Location: donasi.php?msg=Status donasi berhasil diupdate");
            exit;
        } catch(PDOException $e) {
            error_log("Error updating status: " . $e->getMessage());
            if (ob_get_level()) {
                ob_end_clean();
            }
            header("Location: donasi.php?error=" . urlencode("Error mengupdate status"));
            exit;
        }
    }
}

// End output buffering for normal page display
ob_end_flush();

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
<?= getCssLink() ?>
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
    
    <?php if(isset($_GET['error'])): ?>
    <div class="alert" style="background:#f8d7da; color:#721c24; border:1px solid #f5c6cb">
        <strong>⚠️ Error:</strong> <?= htmlspecialchars($_GET['error']) ?>
    </div>
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
            <div>
                <a href="export_excel.php?type=donasi" class="btn btn-success">📥 Export Excel</a>
                <button class="btn" onclick="document.getElementById('modalDonasi').style.display='block'">+ Tambah Donasi</button>
            </div>
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

