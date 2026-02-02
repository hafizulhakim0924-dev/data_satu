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

$today = date('Y-m-d');
$message = '';

// Handle QRIS scan submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'scan_qris') {
    $qris_code = $_POST['qris_code'] ?? '';
    $jam_masuk = date('H:i:s');
    
    // Verify QRIS code (in real implementation, verify against actual QRIS)
    // For now, we'll accept any code that matches a pattern
    if (preg_match('/^QRIS.*/', $qris_code) || strlen($qris_code) > 10) {
        // Check if already recorded today
        $existing = $pdo->prepare("SELECT * FROM kehadiran WHERE karyawan_id=? AND tanggal=?");
        $existing->execute([$karyawan_id, $today]);
        
        if ($existing->fetch()) {
            // Update jam keluar
            $stmt = $pdo->prepare("UPDATE kehadiran SET jam_keluar=?, status='hadir' WHERE karyawan_id=? AND tanggal=?");
            $stmt->execute([$jam_masuk, $karyawan_id, $today]);
            $message = "success:Absen keluar berhasil dicatat pada jam " . date('H:i');
        } else {
            // Insert new attendance
            $stmt = $pdo->prepare("INSERT INTO kehadiran (karyawan_id, tanggal, jam_masuk, status) VALUES (?,?,?,'hadir')");
            $stmt->execute([$karyawan_id, $today, $jam_masuk]);
            $message = "success:Absen masuk berhasil dicatat pada jam " . date('H:i');
        }
    } else {
        $message = "error:Kode QRIS tidak valid";
    }
}

// Get today's attendance
$kehadiran_hari_ini = $pdo->prepare("SELECT * FROM kehadiran WHERE karyawan_id=? AND tanggal=?");
$kehadiran_hari_ini->execute([$karyawan_id, $today]);
$kehadiran_hari_ini = $kehadiran_hari_ini->fetch();
?>
<!DOCTYPE html>
<html>
<head>
<title>Presensi QRIS - Rangkiang Peduli Negeri</title>
<style>
* { margin:0; padding:0; box-sizing:border-box }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f4f6f8 }
.navbar { background:#2c3e50; color:#fff; padding:15px 20px; box-shadow:0 2px 5px rgba(0,0,0,0.1) }
.navbar h1 { display:inline-block; margin-right:30px; font-size:20px }
.navbar a { color:#fff; text-decoration:none; padding:10px 15px; margin:0 5px; border-radius:5px; display:inline-block }
.navbar a:hover { background:#34495e }
.container { max-width:800px; margin:20px auto; padding:0 20px }
.card { background:#fff; padding:30px; border-radius:10px; margin-bottom:15px; box-shadow:0 2px 4px rgba(0,0,0,0.1); text-align:center }
.btn { display:inline-block; padding:15px 30px; background:#3498db; color:#fff; text-decoration:none; border-radius:5px; border:none; cursor:pointer; font-size:16px; font-weight:600; margin:10px }
.btn:hover { background:#2980b9 }
.btn-success { background:#27ae60 }
.btn-success:hover { background:#229954 }
.alert { padding:15px; margin-bottom:20px; border-radius:5px }
.alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb }
.alert-error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb }
.qr-scanner-area { background:#f8f9fa; padding:40px; border-radius:10px; margin:20px 0; border:2px dashed #ddd }
#video { width:100%; max-width:500px; border-radius:10px; margin:20px auto; display:block }
#canvas { display:none }
.scan-button { background:#27ae60; color:#fff; padding:20px 40px; font-size:18px; border:none; border-radius:10px; cursor:pointer; margin:20px }
.scan-button:hover { background:#229954 }
.info-box { background:#e8f4f8; padding:20px; border-radius:8px; margin:20px 0 }
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
    <h1 style="margin:20px 0; text-align:center">📱 Presensi QRIS</h1>
    
    <?php if($message): 
        $msg_parts = explode(':', $message, 2);
        $msg_type = $msg_parts[0];
        $msg_text = $msg_parts[1] ?? '';
    ?>
    <div class="alert alert-<?= $msg_type == 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($msg_text) ?>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <h2>Selamat Datang, <?= htmlspecialchars($karyawan['nama_lengkap']) ?>!</h2>
        <p>NIP: <?= htmlspecialchars($karyawan['nip']) ?></p>
        <p style="margin-top:10px; font-size:18px; color:#666"><?= date('d F Y, H:i') ?></p>
    </div>
    
    <?php if($kehadiran_hari_ini): ?>
    <div class="card">
        <h3 style="color:#27ae60">✓ Anda sudah melakukan absen hari ini</h3>
        <div class="info-box">
            <p><strong>Jam Masuk:</strong> <?= $kehadiran_hari_ini['jam_masuk'] ? date('H:i', strtotime($kehadiran_hari_ini['jam_masuk'])) : '-' ?></p>
            <?php if($kehadiran_hari_ini['jam_keluar']): ?>
            <p><strong>Jam Keluar:</strong> <?= date('H:i', strtotime($kehadiran_hari_ini['jam_keluar'])) ?></p>
            <?php else: ?>
            <p style="color:#e74c3c"><strong>Belum absen keluar</strong></p>
            <?php endif; ?>
            <p><strong>Status:</strong> <?= ucfirst($kehadiran_hari_ini['status']) ?></p>
        </div>
        <?php if(!$kehadiran_hari_ini['jam_keluar']): ?>
        <p>Silakan scan QRIS lagi untuk absen keluar</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <h3>📷 Scan QRIS untuk Absensi</h3>
        <div class="qr-scanner-area">
            <video id="video" autoplay playsinline></video>
            <canvas id="canvas"></canvas>
            <button class="scan-button" onclick="startScan()">Mulai Scan QRIS</button>
            <button class="scan-button" onclick="stopScan()" style="background:#e74c3c">Stop Scan</button>
        </div>
        <form method="POST" id="qrisForm" style="display:none">
            <input type="hidden" name="action" value="scan_qris">
            <input type="hidden" name="qris_code" id="qris_code">
        </form>
        <div id="scanResult" style="margin-top:20px; padding:15px; background:#f8f9fa; border-radius:5px; display:none"></div>
    </div>
    
    <div class="card">
        <h3>ℹ️ Cara Menggunakan</h3>
        <ol style="text-align:left; max-width:500px; margin:20px auto">
            <li>Klik tombol "Mulai Scan QRIS"</li>
            <li>Arahkan kamera ke QRIS code</li>
            <li>Tunggu hingga QRIS terdeteksi</li>
            <li>Absensi akan tercatat otomatis</li>
        </ol>
        <p style="color:#666; margin-top:20px">
            <strong>Catatan:</strong> Scan pertama untuk absen masuk, scan kedua untuk absen keluar.
        </p>
    </div>
</div>

<script>
let scanning = false;
let stream = null;

function startScan() {
    if (scanning) return;
    
    scanning = true;
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const ctx = canvas.getContext('2d');
    
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(function(mediaStream) {
            stream = mediaStream;
            video.srcObject = mediaStream;
            video.play();
            
            // Simple QR code detection (in production, use a proper QR library like jsQR)
            setInterval(function() {
                if (!scanning) return;
                
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0);
                
                // For demo purposes, simulate QR detection
                // In production, use jsQR library: https://github.com/cozmo/jsQR
                const result = detectQRCode(canvas);
                if (result) {
                    document.getElementById('qris_code').value = result;
                    document.getElementById('scanResult').innerHTML = '<p style="color:#27ae60">✓ QRIS terdeteksi: ' + result + '</p><p>Mencatat absensi...</p>';
                    document.getElementById('qrisForm').submit();
                    stopScan();
                }
            }, 500);
        })
        .catch(function(err) {
            alert('Error accessing camera: ' + err.message);
            scanning = false;
        });
}

function stopScan() {
    scanning = false;
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    const video = document.getElementById('video');
    video.srcObject = null;
}

// Simple QR code detection simulation
// In production, replace with actual QR code library
function detectQRCode(canvas) {
    // This is a placeholder - in production use jsQR or similar library
    // For now, return null to require manual input
    return null;
}

// Alternative: Manual QRIS code input
document.addEventListener('DOMContentLoaded', function() {
    const manualInput = document.createElement('div');
    manualInput.innerHTML = `
        <div style="margin-top:20px">
            <h4>Atau masukkan kode QRIS secara manual:</h4>
            <input type="text" id="manualQris" placeholder="Masukkan kode QRIS" style="padding:10px; width:300px; border:1px solid #ddd; border-radius:5px; margin:10px">
            <button onclick="submitManualQris()" class="btn btn-success">Submit</button>
        </div>
    `;
    document.querySelector('.qr-scanner-area').appendChild(manualInput);
});

function submitManualQris() {
    const code = document.getElementById('manualQris').value;
    if (code && code.length > 5) {
        document.getElementById('qris_code').value = 'QRIS-' + code;
        document.getElementById('qrisForm').submit();
    } else {
        alert('Kode QRIS tidak valid');
    }
}
</script>

</body>
</html>

