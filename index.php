<?php
require_once 'config.php';

/* =========================
   TOTAL DONASI
========================= */
$total = $pdo->query("
    SELECT SUM(jumlah) FROM csr_donations
")->fetchColumn();

/* =========================
   DONASI HARIAN
========================= */
$hari_ini = $pdo->query("
    SELECT SUM(jumlah) FROM csr_donations
    WHERE DATE(tanggal)=CURDATE()
")->fetchColumn();

$kemarin = $pdo->query("
    SELECT SUM(jumlah) FROM csr_donations
    WHERE DATE(tanggal)=CURDATE()-INTERVAL 1 DAY
")->fetchColumn();

/* =========================
   DONASI BULANAN
========================= */
$bulan_ini = $pdo->query("
    SELECT SUM(jumlah) FROM csr_donations
    WHERE MONTH(tanggal)=MONTH(CURDATE())
    AND YEAR(tanggal)=YEAR(CURDATE())
")->fetchColumn();

$bulan_lalu = $pdo->query("
    SELECT SUM(jumlah) FROM csr_donations
    WHERE MONTH(tanggal)=MONTH(CURDATE()-INTERVAL 1 MONTH)
    AND YEAR(tanggal)=YEAR(CURDATE()-INTERVAL 1 MONTH)
")->fetchColumn();

/* =========================
   TREND HARIAN (7 HARI)
========================= */
$trend = $pdo->query("
    SELECT DATE(tanggal) tgl, SUM(jumlah) total
    FROM csr_donations
    WHERE tanggal >= CURDATE()-INTERVAL 6 DAY
    GROUP BY DATE(tanggal)
    ORDER BY tgl
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Rangkiang Peduli Negeri - Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
.grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); gap:15px }
.big { font-size:22px; font-weight:bold; color:#2c3e50 }
.btn { display:inline-block; padding:10px 20px; background:#3498db; color:#fff; text-decoration:none; border-radius:5px; border:none; cursor:pointer; margin:5px }
.btn:hover { background:#2980b9 }
.btn-success { background:#27ae60 }
.btn-danger { background:#e74c3c }
.btn-warning { background:#f39c12 }
table { width:100%; border-collapse:collapse; margin-top:15px }
table th, table td { padding:12px; text-align:left; border-bottom:1px solid #ddd }
table th { background:#34495e; color:#fff; font-weight:600 }
table tr:hover { background:#f5f5f5 }
.form-group { margin-bottom:15px }
.form-group label { display:block; margin-bottom:5px; font-weight:600; color:#2c3e50 }
.form-group input, .form-group select, .form-group textarea { width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; font-size:14px }
.form-group textarea { min-height:100px; resize:vertical }
.text-success { color:#27ae60; font-weight:600 }
.text-danger { color:#e74c3c }
</style>
</head>
<body>

<div class="navbar">
    <h1>🏛️ Rangkiang Peduli Negeri</h1>
    <?= getNavMenu('dashboard') ?>
</div>

<div class="container">
<h1 style="margin:20px 0">📊 Dashboard</h1>

<div class="grid">
    <div class="card">
        Total Donasi
        <div class="big">Rp <?= number_format($total ?? 0,0,',','.') ?></div>
    </div>
    <div class="card">
        Hari Ini
        <div class="big">Rp <?= number_format($hari_ini ?? 0,0,',','.') ?></div>
        Kemarin: Rp <?= number_format($kemarin ?? 0,0,',','.') ?>
    </div>
    <div class="card">
        Bulan Ini
        <div class="big">Rp <?= number_format($bulan_ini ?? 0,0,',','.') ?></div>
        Bulan Lalu: Rp <?= number_format($bulan_lalu ?? 0,0,',','.') ?>
    </div>
</div>

<?php
// Get additional statistics
$total_donatur = $pdo->query("SELECT COUNT(*) FROM donatur WHERE status='active'")->fetchColumn();
try {
    $total_karyawan = $pdo->query("SELECT COUNT(*) FROM karyawan WHERE status='active'")->fetchColumn() ?: 0;
} catch(PDOException $e) {
    $total_karyawan = 0;
}
try {
    $total_volunteer = $pdo->query("SELECT COUNT(*) FROM volunteer WHERE status='active'")->fetchColumn() ?: 0;
} catch(PDOException $e) {
    $total_volunteer = 0;
}
$total_program = $pdo->query("SELECT COUNT(*) FROM program_csr")->fetchColumn();
$program_ongoing = $pdo->query("SELECT COUNT(*) FROM program_csr WHERE status='ongoing'")->fetchColumn();

$total_pemasukan = $pdo->query("SELECT SUM(jumlah) FROM pemasukan WHERE status='verified'")->fetchColumn() ?: 0;
$total_pengeluaran = $pdo->query("SELECT SUM(jumlah) FROM pengeluaran WHERE status='paid'")->fetchColumn() ?: 0;
$saldo = $total_pemasukan - $total_pengeluaran;

// Daily donation movement (30 hari terakhir)
$daily_movement = $pdo->query("
    SELECT DATE(tanggal) as tgl, SUM(jumlah) as total, COUNT(*) as jumlah
    FROM csr_donations
    WHERE tanggal >= CURDATE()-INTERVAL 30 DAY AND status='verified'
    GROUP BY DATE(tanggal)
    ORDER BY tgl DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Monthly donation (12 bulan terakhir)
$monthly_donation = $pdo->query("
    SELECT DATE_FORMAT(tanggal, '%Y-%m') as bulan, 
           DATE_FORMAT(tanggal, '%M %Y') as bulan_label,
           SUM(jumlah) as total, COUNT(*) as jumlah
    FROM csr_donations
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND status='verified'
    GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
    ORDER BY bulan DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Daily total (hari ini)
$daily_total = $pdo->query("
    SELECT SUM(jumlah) as total, COUNT(*) as jumlah
    FROM csr_donations
    WHERE DATE(tanggal) = CURDATE() AND status='verified'
")->fetch(PDO::FETCH_ASSOC);
?>

<div class="grid">
    <div class="card">
        <h3>Total Donatur</h3>
        <div class="big"><?= number_format($total_donatur ?? 0, 0, ',', '.') ?></div>
    </div>
    <div class="card">
        <h3>Total Karyawan</h3>
        <div class="big"><?= number_format($total_karyawan, 0, ',', '.') ?></div>
    </div>
    <div class="card">
        <h3>Total Volunteer</h3>
        <div class="big"><?= number_format($total_volunteer, 0, ',', '.') ?></div>
    </div>
    <div class="card">
        <h3>Total Program</h3>
        <div class="big"><?= number_format($total_program ?? 0, 0, ',', '.') ?></div>
        <small>Ongoing: <?= $program_ongoing ?? 0 ?></small>
    </div>
    <div class="card">
        <h3>Total Harian (Hari Ini)</h3>
        <div class="big" style="color:#27ae60">Rp <?= number_format($daily_total['total'] ?? 0, 0, ',', '.') ?></div>
        <small><?= $daily_total['jumlah'] ?? 0 ?> transaksi</small>
    </div>
</div>

<div class="grid">
    <div class="card">
        <h3>Total Pemasukan</h3>
        <div class="big" style="color:#27ae60">Rp <?= number_format($total_pemasukan ?? 0, 0, ',', '.') ?></div>
    </div>
    <div class="card">
        <h3>Total Pengeluaran</h3>
        <div class="big" style="color:#e74c3c">Rp <?= number_format($total_pengeluaran ?? 0, 0, ',', '.') ?></div>
    </div>
    <div class="card">
        <h3>Saldo</h3>
        <div class="big" style="color:<?= ($saldo ?? 0) >= 0 ? '#27ae60' : '#e74c3c' ?>">Rp <?= number_format($saldo ?? 0, 0, ',', '.') ?></div>
    </div>
</div>

<div class="card">
    <h3>📊 Tabel Pergerakan Donasi Harian (30 Hari Terakhir)</h3>
    <div style="max-height:400px; overflow-y:auto">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jumlah Transaksi</th>
                    <th>Total Donasi</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($daily_movement)): ?>
                <tr>
                    <td colspan="3" style="text-align:center; padding:20px; color:#999">Belum ada data</td>
                </tr>
                <?php else: ?>
                <?php foreach($daily_movement as $dm): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($dm['tgl'])) ?></td>
                    <td><?= $dm['jumlah'] ?> transaksi</td>
                    <td class="text-success"><strong><?= formatRupiah($dm['total']) ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h3>📈 Chart Pergerakan Donasi Harian (30 Hari Terakhir)</h3>
    <canvas id="dailyChart" style="max-height:300px"></canvas>
</div>

<div class="card">
    <h3>📅 Chart Donasi Bulanan (12 Bulan Terakhir)</h3>
    <canvas id="monthlyChart" style="max-height:300px"></canvas>
</div>

<div class="card">
    <h3>📊 Perbandingan Harian vs Bulanan</h3>
    <canvas id="comparisonChart" style="max-height:300px"></canvas>
</div>

<script>
// Daily Chart
const dailyLabels = <?= json_encode(array_map(function($d) { return date('d/m', strtotime($d['tgl'])); }, $daily_movement)) ?>;
const dailyData = <?= json_encode(array_column($daily_movement, 'total')) ?>;

new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels: dailyLabels.reverse(),
        datasets: [{
            label: 'Donasi Harian (Rp)',
            data: dailyData.reverse(),
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + (value/1000000).toFixed(1) + 'M';
                    }
                }
            }
        }
    }
});

// Monthly Chart
const monthlyLabels = <?= json_encode(array_column($monthly_donation, 'bulan_label')) ?>;
const monthlyData = <?= json_encode(array_column($monthly_donation, 'total')) ?>;

new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: monthlyLabels.reverse(),
        datasets: [{
            label: 'Donasi Bulanan (Rp)',
            data: monthlyData.reverse(),
            backgroundColor: 'rgba(46, 204, 113, 0.6)',
            borderColor: '#27ae60',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + (value/1000000).toFixed(1) + 'M';
                    }
                }
            }
        }
    }
});

// Comparison Chart
const comparisonLabels = ['Harian (Rata-rata)', 'Bulanan (Rata-rata)'];
const avgDaily = <?= count($daily_movement) > 0 ? array_sum(array_column($daily_movement, 'total')) / count($daily_movement) : 0 ?>;
const avgMonthly = <?= count($monthly_donation) > 0 ? array_sum(array_column($monthly_donation, 'total')) / count($monthly_donation) : 0 ?>;

new Chart(document.getElementById('comparisonChart'), {
    type: 'bar',
    data: {
        labels: comparisonLabels,
        datasets: [{
            label: 'Rata-rata Donasi',
            data: [avgDaily, avgMonthly],
            backgroundColor: ['rgba(52, 152, 219, 0.6)', 'rgba(46, 204, 113, 0.6)'],
            borderColor: ['#3498db', '#27ae60'],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + (value/1000000).toFixed(1) + 'M';
                    }
                }
            }
        }
    }
});
</script>

</div>
</body>
</html>
