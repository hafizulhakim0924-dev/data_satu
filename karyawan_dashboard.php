<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['karyawan_id'])) {
    header("Location: karyawan_login.php");
    exit;
}

$karyawan_id = $_SESSION['karyawan_id'];
$karyawan = $pdo->prepare("SELECT * FROM karyawan WHERE id=?");
$karyawan->execute([$karyawan_id]);
$karyawan = $karyawan->fetch();

// Get today's attendance
$today = date('Y-m-d');
$kehadiran_hari_ini = $pdo->prepare("SELECT * FROM kehadiran WHERE karyawan_id=? AND tanggal=?");
$kehadiran_hari_ini->execute([$karyawan_id, $today]);
$kehadiran_hari_ini = $kehadiran_hari_ini->fetch();

// Get monthly attendance stats
$bulan_ini = date('Y-m');
$statistik_bulan = $pdo->prepare("
    SELECT 
        COUNT(*) as total_hari,
        SUM(CASE WHEN status='hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN status='izin' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN status='sakit' THEN 1 ELSE 0 END) as sakit
    FROM kehadiran
    WHERE karyawan_id=? AND DATE_FORMAT(tanggal, '%Y-%m')=?
");
$statistik_bulan->execute([$karyawan_id, $bulan_ini]);
$statistik = $statistik_bulan->fetch();

// Recent attendance
$recent_attendance = $pdo->prepare("
    SELECT * FROM kehadiran 
    WHERE karyawan_id=? 
    ORDER BY tanggal DESC 
    LIMIT 10
");
$recent_attendance->execute([$karyawan_id]);
$recent_attendance = $recent_attendance->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<title>Dashboard Karyawan - Rangkiang Peduli Negeri</title>
<style>
* { margin:0; padding:0; box-sizing:border-box }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f4f6f8 }
.navbar { background:#2c3e50; color:#fff; padding:15px 20px; box-shadow:0 2px 5px rgba(0,0,0,0.1) }
.navbar h1 { display:inline-block; margin-right:30px; font-size:20px }
.navbar a { color:#fff; text-decoration:none; padding:10px 15px; margin:0 5px; border-radius:5px; display:inline-block }
.navbar a:hover { background:#34495e }
.container { max-width:1200px; margin:20px auto; padding:0 20px }
.card { background:#fff; padding:20px; border-radius:8px; margin-bottom:15px; box-shadow:0 2px 4px rgba(0,0,0,0.1) }
.grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(250px,1fr)); gap:15px }
.big { font-size:24px; font-weight:bold; color:#2c3e50 }
.btn { display:inline-block; padding:15px 30px; background:#3498db; color:#fff; text-decoration:none; border-radius:5px; border:none; cursor:pointer; font-size:16px; font-weight:600 }
.btn:hover { background:#2980b9 }
.btn-success { background:#27ae60 }
.btn-success:hover { background:#229954 }
.btn-danger { background:#e74c3c }
.btn-danger:hover { background:#c0392b }
table { width:100%; border-collapse:collapse; margin-top:15px }
table th, table td { padding:12px; text-align:left; border-bottom:1px solid #ddd }
table th { background:#34495e; color:#fff; font-weight:600 }
.qr-scanner { text-align:center; padding:40px }
.qr-code-display { background:#f8f9fa; padding:20px; border-radius:10px; margin:20px 0 }
.qr-code-display img { max-width:300px; height:auto }
</style>
</head>
<body>

<div class="navbar">
    <h1>🏛️ Rangkiang Peduli Negeri</h1>
    <a href="karyawan_dashboard.php">Dashboard</a>
    <a href="karyawan_presensi.php">Presensi QRIS</a>
    <a href="karyawan_logout.php" style="float:right; background:#e74c3c">Logout</a>
</div>

<div class="container">
    <h1 style="margin:20px 0">👤 Dashboard Karyawan</h1>
    
    <div class="card">
        <h2>Selamat Datang, <?= htmlspecialchars($karyawan['nama_lengkap']) ?>!</h2>
        <p>NIP: <?= htmlspecialchars($karyawan['nip']) ?> | Jabatan: <?= htmlspecialchars($karyawan['jabatan'] ?? '-') ?></p>
    </div>
    
    <div class="grid">
        <div class="card">
            <h3>Kehadiran Hari Ini</h3>
            <?php if($kehadiran_hari_ini): ?>
                <div class="big" style="color:#27ae60">✓ Sudah Absen</div>
                <p>Jam Masuk: <?= $kehadiran_hari_ini['jam_masuk'] ? date('H:i', strtotime($kehadiran_hari_ini['jam_masuk'])) : '-' ?></p>
                <p>Status: <?= ucfirst($kehadiran_hari_ini['status']) ?></p>
            <?php else: ?>
                <div class="big" style="color:#e74c3c">Belum Absen</div>
                <a href="karyawan_presensi.php" class="btn btn-success" style="margin-top:10px">Absen Sekarang</a>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3>Statistik Bulan Ini</h3>
            <p>Total Hari: <strong><?= $statistik['total_hari'] ?? 0 ?></strong></p>
            <p>Hadir: <strong style="color:#27ae60"><?= $statistik['hadir'] ?? 0 ?></strong></p>
            <p>Izin: <strong style="color:#f39c12"><?= $statistik['izin'] ?? 0 ?></strong></p>
            <p>Sakit: <strong style="color:#e74c3c"><?= $statistik['sakit'] ?? 0 ?></strong></p>
        </div>
    </div>
    
    <div class="card">
        <h3>Riwayat Kehadiran Terakhir</h3>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jam Masuk</th>
                    <th>Jam Keluar</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($recent_attendance)): ?>
                <tr>
                    <td colspan="4" style="text-align:center; padding:20px; color:#999">Belum ada data kehadiran</td>
                </tr>
                <?php else: ?>
                <?php foreach($recent_attendance as $a): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($a['tanggal'])) ?></td>
                    <td><?= $a['jam_masuk'] ? date('H:i', strtotime($a['jam_masuk'])) : '-' ?></td>
                    <td><?= $a['jam_keluar'] ? date('H:i', strtotime($a['jam_keluar'])) : '-' ?></td>
                    <td><?= ucfirst($a['status']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>

