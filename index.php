<?php
/* =========================
   DATABASE CONFIG
========================= */
$host = "localhost";
$db   = "rank3598_bankdata";
$user = "rank3598_bankdata";
$pass = "Hakim123!";

$pdo = new PDO(
    "mysql:host=$host;dbname=$db;charset=utf8",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

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
<title>CSR ZISWAF Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { font-family: Arial; background:#f4f6f8; padding:20px }
.card { background:#fff; padding:20px; border-radius:8px; margin-bottom:15px }
.grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); gap:15px }
h1 { margin-bottom:20px }
.big { font-size:22px; font-weight:bold }
</style>
</head>
<body>

<h1>📊 Dashboard CSR ZISWAF</h1>

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

<div class="card">
    <h3>📈 Trend Donasi 7 Hari Terakhir</h3>
    <canvas id="trendChart"></canvas>
</div>

<script>
const labels = <?= json_encode(array_column($trend,'tgl')) ?>;
const data = <?= json_encode(array_column($trend,'total')) ?>;

new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Donasi',
            data: data,
            borderWidth: 2,
            fill: false
        }]
    }
});
</script>

</body>
</html>
