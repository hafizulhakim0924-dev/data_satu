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
$tab = $_GET['tab'] ?? 'transaksi';

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

// Daily summary (pendapatan per hari) + detail per tanggal for modal
$daily_donasi_summary = [];
$daily_donasi_details = [];
try {
    $donaturFilter = $donatur_id ? " AND donatur_id = " . (int)$donatur_id : "";

    // Ringkasan: total verified per hari (last 30 days)
    $daily_donasi_summary = $pdo->query("
        SELECT 
            DATE(tanggal) as tgl,
            SUM(jumlah) as total,
            COUNT(*) as jumlah_transaksi
        FROM csr_donations
        WHERE status='verified'
          AND tanggal >= CURDATE() - INTERVAL 30 DAY
          {$donaturFilter}
        GROUP BY DATE(tanggal)
        ORDER BY tgl DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Detail: semua transaksi verified untuk last 30 days (untuk ditampilkan saat tanggal diklik)
    $rows = $pdo->query("
        SELECT d.*,
            (SELECT nama FROM donatur WHERE id=d.donatur_id) as nama_donatur_db
        FROM csr_donations d
        WHERE status='verified'
          AND tanggal >= CURDATE() - INTERVAL 30 DAY
          {$donaturFilter}
        ORDER BY d.tanggal DESC
        LIMIT 5000
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $key = date('Y-m-d', strtotime($r['tanggal']));
        $daily_donasi_details[$key][] = $r;
    }
} catch (PDOException $e) {
    $daily_donasi_summary = [];
    $daily_donasi_details = [];
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

    <?php $donaturParam = $donatur_id ? '&donatur_id=' . urlencode((string)$donatur_id) : ''; ?>
    <div class="tabs">
        <a href="donasi.php?tab=transaksi<?= $donaturParam ?>" class="<?= ($tab === 'transaksi') ? 'active' : '' ?>">📋 Transaksi</a>
        <a href="donasi.php?tab=harian<?= $donaturParam ?>" class="<?= ($tab === 'harian') ? 'active' : '' ?>">📅 Harian</a>
    </div>

    <?php if ($tab === 'harian'): ?>
        <div class="card">
            <div class="card-header">
                <h3>Ringkasan Pendapatan Harian (30 Hari Terakhir)</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Tgl Donasi</th>
                        <th>Total Pendapatan</th>
                        <th>Jumlah Transaksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daily_donasi_summary)): ?>
                        <tr>
                            <td colspan="3" style="text-align:center; padding:40px; color:#999">Belum ada data donasi harian.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daily_donasi_summary as $row): 
                            $tglKey = $row['tgl'];
                            $tglHuman = $tglKey ? date('d/m/Y', strtotime($tglKey)) : '-';
                            ?>
                        <tr>
                            <td>
                                <a href="javascript:void(0)"
                                   onclick="showDonasiDetailByDate(<?= json_encode($tglKey) ?>)"
                                   style="color:var(--primary-color); font-weight:700; text-decoration:none; cursor:pointer;">
                                    <?= htmlspecialchars($tglHuman) ?>
                                </a>
                            </td>
                            <td class="text-success"><strong><?= formatRupiah($row['total'] ?? 0) ?></strong></td>
                            <td><?= (int)($row['jumlah_transaksi'] ?? 0) ?> transaksi</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
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
    <?php endif; ?>

    <!-- Modal Detail Donasi Harian -->
    <div id="modalDonasiDetail" class="modal">
        <div class="modal-content" style="max-width:900px">
            <span class="close" onclick="document.getElementById('modalDonasiDetail').style.display='none'">&times;</span>
            <h2 id="modalDonasiDetailTitle">Detail Donasi</h2>
            <div id="modalDonasiDetailContent">Memuat data...</div>
        </div>
    </div>
    
</div>

<script>
const dailyDonasiData = <?= json_encode($daily_donasi_details ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

function formatRupiahJS(angka) {
    const val = Number(angka || 0);
    return 'Rp ' + val.toLocaleString('id-ID');
}

function formatDateJS(dateString) {
    if (!dateString) return '-';
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return '-';
    return d.toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' });
}

function showDonasiDetailByDate(tglKey) {
    const modal = document.getElementById('modalDonasiDetail');
    const title = document.getElementById('modalDonasiDetailTitle');
    const content = document.getElementById('modalDonasiDetailContent');

    const list = dailyDonasiData[tglKey] || [];
    title.textContent = 'Detail Donasi - ' + (tglKey || '');

    if (list.length === 0) {
        content.innerHTML = '<p style="text-align:center; padding:40px; color:#999">Belum ada data donasi pada tanggal ini.</p>';
        modal.style.display = 'block';
        return;
    }

    let total = 0;
    list.forEach(function(d) { total += Number(d.jumlah || 0); });

    let html = '';
    html += '<div class="grid" style="grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:15px">';
    html += '<div class="card"><strong>Total</strong><div class="big text-success" style="margin-top:6px">' + formatRupiahJS(total) + '</div></div>';
    html += '<div class="card"><strong>Jumlah Transaksi</strong><div class="big" style="margin-top:6px">' + list.length + '</div></div>';
    html += '<div class="card"><strong>Rata-rata</strong><div class="big" style="margin-top:6px">' + formatRupiahJS(total / list.length) + '</div></div>';
    html += '</div>';

    html += '<table><thead><tr>' +
        '<th>Tanggal</th>' +
        '<th>Donatur</th>' +
        '<th>Jumlah</th>' +
        '<th>Kategori</th>' +
        '<th>Program</th>' +
        '<th>Metode</th>' +
        '<th>Status</th>' +
        '</tr></thead><tbody>';

    list.forEach(function(d) {
        const status = (d.status || '').toLowerCase();
        const badgeClass = 'badge-' + status;
        const statusLabel = status ? (status.charAt(0).toUpperCase() + status.slice(1)) : '-';

        html += '<tr>';
        html += '<td>' + formatDateJS(d.tanggal) + '</td>';
        html += '<td><strong>' + (d.nama_donatur_db || d.nama_donatur || '-') + '</strong></td>';
        html += '<td class="text-success"><strong>' + formatRupiahJS(d.jumlah) + '</strong></td>';
        html += '<td>' + (d.kategori || '-') + '</td>';
        html += '<td>' + (d.program || '-') + '</td>';
        html += '<td>' + (d.metode_pembayaran || '-') + '</td>';
        html += '<td><span class="badge ' + badgeClass + '">' + statusLabel + '</span></td>';
        html += '</tr>';
    });

    html += '</tbody></table>';
    content.innerHTML = html;
    modal.style.display = 'block';
}

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

