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
$total_karyawan = $pdo->query("SELECT COUNT(*) FROM karyawan WHERE status='active'")->fetchColumn();
$total_volunteer = $pdo->query("SELECT COUNT(*) FROM volunteer WHERE status='active'")->fetchColumn();
$total_program = $pdo->query("SELECT COUNT(*) FROM program_csr")->fetchColumn();
$program_ongoing = $pdo->query("SELECT COUNT(*) FROM program_csr WHERE status='ongoing'")->fetchColumn();

$total_pemasukan = $pdo->query("SELECT SUM(jumlah) FROM pemasukan WHERE status='verified'")->fetchColumn();
$total_pengeluaran = $pdo->query("SELECT SUM(jumlah) FROM pengeluaran WHERE status='paid'")->fetchColumn();
$saldo = $total_pemasukan - $total_pengeluaran;
?>

<div class="grid">
    <div class="card">
        <h3>Total Donatur</h3>
        <div class="big"><?= number_format($total_donatur ?? 0, 0, ',', '.') ?></div>
    </div>
    <div class="card">
        <h3>Total Karyawan</h3>
        <div class="big"><?= number_format($total_karyawan ?? 0, 0, ',', '.') ?></div>
    </div>
    <div class="card">
        <h3>Total Volunteer</h3>
        <div class="big"><?= number_format($total_volunteer ?? 0, 0, ',', '.') ?></div>
    </div>
    <div class="card">
        <h3>Total Program</h3>
        <div class="big"><?= number_format($total_program ?? 0, 0, ',', '.') ?></div>
        <small>Ongoing: <?= $program_ongoing ?? 0 ?></small>
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
    <h3>📈 Trend Donasi 7 Hari Terakhir</h3>
    <canvas id="trendChart" style="max-height:300px"></canvas>
</div>

<script>
const labels = <?= json_encode(array_column($trend,'tgl')) ?>;
const data = <?= json_encode(array_column($trend,'total')) ?>;

new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Donasi (Rp)',
            data: data,
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
                        return 'Rp ' + value.toLocaleString('id-ID');
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
