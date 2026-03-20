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
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?= getCssLink() ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="navbar">
    <div class="navbar-brand">
        <?php
        $logoFile = __DIR__ . '/logo.php';
        if (is_readable($logoFile)) {
            include $logoFile;
        } else {
            echo '<h1 class="navbar-title-fallback">🏛️ Rangkiang Peduli Negeri</h1>';
        }
        ?>
    </div>
    <?= getNavMenu('dashboard') ?>
</div>

<div class="container">
    <div class="card-header">
        <h1 style="margin:0">📊 Dashboard</h1>
        <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('modalDashboardSettings').style.display='block'; loadDashboardSettingsForm();" title="Tampilkan / sembunyikan kartu">⚙️ Settings</button>
    </div>

    <div class="grid" data-dash="stats_donasi">
        <div class="stat-card">
            <div class="icon">💰</div>
            <h3>Total Donasi</h3>
            <div class="big"><?= formatRupiah($total ?? 0) ?></div>
        </div>
        <div class="stat-card">
            <div class="icon">📅</div>
            <h3>Hari Ini</h3>
            <div class="big"><?= formatRupiah($hari_ini ?? 0) ?></div>
            <small style="opacity:0.8">Kemarin: <?= formatRupiah($kemarin ?? 0) ?></small>
        </div>
        <div class="stat-card">
            <div class="icon">📆</div>
            <h3>Bulan Ini</h3>
            <div class="big"><?= formatRupiah($bulan_ini ?? 0) ?></div>
            <small style="opacity:0.8">Bulan Lalu: <?= formatRupiah($bulan_lalu ?? 0) ?></small>
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

    <div class="grid" data-dash="stats_entitas">
        <div class="stat-card">
            <div class="icon">🤝</div>
            <h3>Total Donatur</h3>
            <div class="big"><?= number_format($total_donatur ?? 0, 0, ',', '.') ?></div>
        </div>
        <div class="stat-card">
            <div class="icon">👥</div>
            <h3>Total Karyawan</h3>
            <div class="big"><?= number_format($total_karyawan, 0, ',', '.') ?></div>
        </div>
        <div class="stat-card">
            <div class="icon">🙋</div>
            <h3>Total Volunteer</h3>
            <div class="big"><?= number_format($total_volunteer, 0, ',', '.') ?></div>
        </div>
        <div class="stat-card">
            <div class="icon">📋</div>
            <h3>Total Program</h3>
            <div class="big"><?= number_format($total_program ?? 0, 0, ',', '.') ?></div>
            <small style="opacity:0.8">Ongoing: <?= $program_ongoing ?? 0 ?></small>
        </div>
    </div>

    <div class="grid" data-dash="stats_keuangan">
        <div class="stat-card">
            <div class="icon">💵</div>
            <h3>Total Pemasukan</h3>
            <div class="big"><?= formatRupiah($total_pemasukan ?? 0) ?></div>
        </div>
        <div class="stat-card">
            <div class="icon">💸</div>
            <h3>Total Pengeluaran</h3>
            <div class="big"><?= formatRupiah($total_pengeluaran ?? 0) ?></div>
        </div>
        <div class="stat-card">
            <div class="icon">💳</div>
            <h3>Saldo</h3>
            <div class="big"><?= formatRupiah($saldo ?? 0) ?></div>
        </div>
    </div>

    <div class="card" data-dash="table_daily_movement">
        <div class="card-header">
            <h3>📊 Tabel Pergerakan Donasi Harian (30 Hari Terakhir)</h3>
        </div>
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

    <div class="card" data-dash="chart_daily">
        <div class="card-header">
            <h3>📈 Chart Pergerakan Donasi Harian (30 Hari Terakhir)</h3>
        </div>
        <canvas id="dailyChart" style="max-height:300px"></canvas>
    </div>

    <div class="card" data-dash="chart_monthly">
        <div class="card-header">
            <h3>📅 Chart Donasi Bulanan (12 Bulan Terakhir)</h3>
        </div>
        <canvas id="monthlyChart" style="max-height:300px"></canvas>
    </div>

    <div class="card" data-dash="chart_comparison">
        <div class="card-header">
            <h3>📊 Perbandingan Harian vs Bulanan</h3>
        </div>
        <canvas id="comparisonChart" style="max-height:300px"></canvas>
    </div>

    <!-- Settings: tampilkan / sembunyikan kartu dashboard -->
    <div id="modalDashboardSettings" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modalDashboardSettings').style.display='none'">&times;</span>
            <h2>Pengaturan Dashboard</h2>
            <p style="color:var(--light-text); font-size:12px; margin-bottom:12px;">Pilih kartu yang ingin ditampilkan. Pengaturan disimpan di perangkat ini (browser).</p>
            <div class="dashboard-settings-panel" id="dashboardSettingsForm"></div>
            <div style="margin-top:16px; display:flex; gap:8px; flex-wrap:wrap;">
                <button type="button" class="btn btn-primary btn-sm" onclick="saveDashboardSettings();">Simpan</button>
                <button type="button" class="btn btn-sm" onclick="resetDashboardSettings();">Reset semua tampil</button>
            </div>
        </div>
    </div>

<script>
(function(){
    const KEY = 'rpn_dashboard_cards_v1';
    const LABELS = {
        stats_donasi: 'Statistik donasi (Total / Hari ini / Bulan ini)',
        stats_entitas: 'Statistik entitas (Donatur, Karyawan, Volunteer, Program)',
        stats_keuangan: 'Statistik keuangan (Pemasukan, Pengeluaran, Saldo)',
        table_daily_movement: 'Tabel pergerakan donasi harian (30 hari)',
        chart_daily: 'Chart donasi harian (30 hari)',
        chart_monthly: 'Chart donasi bulanan (12 bulan)',
        chart_comparison: 'Chart perbandingan harian vs bulanan'
    };
    const keys = Object.keys(LABELS);

    window.__dashboardCardKeys = keys;
    window.__dashboardCardLabels = LABELS;
    window.__dashboardStorageKey = KEY;

    function readSettings() {
        let raw = {};
        try {
            raw = JSON.parse(localStorage.getItem(KEY) || '{}') || {};
        } catch (e) {
            raw = {};
        }
        const out = {};
        keys.forEach(function(k) {
            out[k] = raw[k] === undefined ? true : !!raw[k];
        });
        return out;
    }

    window.applyDashboardVisibility = function() {
        const s = readSettings();
        keys.forEach(function(k) {
            document.querySelectorAll('[data-dash=\"' + k + '\"]').forEach(function(el) {
                el.classList.toggle('dash-hidden', !s[k]);
            });
        });
    };

    window.loadDashboardSettingsForm = function() {
        const s = readSettings();
        const box = document.getElementById('dashboardSettingsForm');
        if (!box) return;
        let html = '';
        keys.forEach(function(k) {
            const id = 'dash_setting_' + k;
            html += '<div class=\"form-group\"><label for=\"' + id + '\"><input type=\"checkbox\" id=\"' + id + '\" data-key=\"' + k + '\"' + (s[k] ? ' checked' : '') + '> ' + LABELS[k] + '</label></div>';
        });
        box.innerHTML = html;
    };

    window.saveDashboardSettings = function() {
        const next = {};
        keys.forEach(function(k) {
            const cb = document.getElementById('dash_setting_' + k);
            next[k] = cb ? cb.checked : true;
        });
        localStorage.setItem(KEY, JSON.stringify(next));
        applyDashboardVisibility();
        document.getElementById('modalDashboardSettings').style.display = 'none';
        location.reload();
    };

    window.resetDashboardSettings = function() {
        localStorage.removeItem(KEY);
        applyDashboardVisibility();
        loadDashboardSettingsForm();
        location.reload();
    };

    applyDashboardVisibility();
})();

function sectionVisible(key) {
    var el = document.querySelector('[data-dash=\"' + key + '\"]');
    return el && !el.classList.contains('dash-hidden');
}

// Daily Chart
const dailyLabels = <?= json_encode(array_map(function($d) { return date('d/m', strtotime($d['tgl'])); }, $daily_movement)) ?>;
const dailyData = <?= json_encode(array_column($daily_movement, 'total')) ?>;

if (sectionVisible('chart_daily')) {
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
}

// Monthly Chart
const monthlyLabels = <?= json_encode(array_column($monthly_donation, 'bulan_label')) ?>;
const monthlyData = <?= json_encode(array_column($monthly_donation, 'total')) ?>;

if (sectionVisible('chart_monthly')) {
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
}

// Comparison Chart
const comparisonLabels = ['Harian (Rata-rata)', 'Bulanan (Rata-rata)'];
const avgDaily = <?= count($daily_movement) > 0 ? array_sum(array_column($daily_movement, 'total')) / count($daily_movement) : 0 ?>;
const avgMonthly = <?= count($monthly_donation) > 0 ? array_sum(array_column($monthly_donation, 'total')) / count($monthly_donation) : 0 ?>;

if (sectionVisible('chart_comparison')) {
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
}
</script>

<script>
window.addEventListener('click', function(ev) {
    var m = document.getElementById('modalDashboardSettings');
    if (ev.target === m) m.style.display = 'none';
});
</script>

</div>
</body>
</html>
