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

// Handle QR code scan submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'scan_qr') {
    $qr_code = $_POST['qr_code'] ?? '';
    $current_time = date('H:i:s');
    
    // Verify QR code format: ATTENDANCE:{secret}
    if (preg_match('/^ATTENDANCE:(.+)$/', $qr_code, $matches)) {
        $qr_secret = $matches[1];
        $expected_secret = 'RPN_' . date('Y-m-d') . '_' . md5('rangkiang_peduli_negeri_' . date('Y-m-d'));
        
        // Verify QR code is valid for today
        if ($qr_secret === $expected_secret) {
            // Check if already recorded today
            $existing = $pdo->prepare("SELECT * FROM kehadiran WHERE karyawan_id=? AND tanggal=?");
            $existing->execute([$karyawan_id, $today]);
            $existing_data = $existing->fetch();
            
            if ($existing_data) {
                // Already has jam_masuk, update jam_keluar
                if ($existing_data['jam_masuk'] && !$existing_data['jam_keluar']) {
                    $stmt = $pdo->prepare("UPDATE kehadiran SET jam_keluar=?, status='hadir' WHERE karyawan_id=? AND tanggal=?");
                    $stmt->execute([$current_time, $karyawan_id, $today]);
                    $message = "success:Absen keluar berhasil dicatat pada jam " . date('H:i');
                } else {
                    $message = "info:Anda sudah melakukan absen masuk dan keluar hari ini";
                }
            } else {
                // Insert new attendance (jam masuk)
                $stmt = $pdo->prepare("INSERT INTO kehadiran (karyawan_id, tanggal, jam_masuk, status) VALUES (?,?,?,'hadir')");
                $stmt->execute([$karyawan_id, $today, $current_time]);
                $message = "success:Absen masuk berhasil dicatat pada jam " . date('H:i');
            }
        } else {
            $message = "error:QR Code tidak valid atau sudah kadaluarsa";
        }
    } else {
        $message = "error:Format QR Code tidak valid";
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
        <h3>📷 Scan QR Code untuk Absensi</h3>
        <div class="qr-scanner-area">
            <div id="video-container" style="position:relative; display:none">
                <video id="video" autoplay playsinline style="width:100%; max-width:500px; border-radius:10px"></video>
                <canvas id="canvas" style="display:none"></canvas>
            </div>
            <div id="scanner-placeholder" style="text-align:center; padding:40px">
                <p style="font-size:18px; color:#666; margin-bottom:20px">Klik tombol di bawah untuk memulai scan</p>
            </div>
            <div style="text-align:center; margin-top:20px">
                <button class="scan-button" onclick="startScan()" id="btnStart">📷 Mulai Scan QR Code</button>
                <button class="scan-button" onclick="stopScan()" id="btnStop" style="background:#e74c3c; display:none">⏹ Stop Scan</button>
            </div>
        </div>
        <form method="POST" id="qrForm" style="display:none">
            <input type="hidden" name="action" value="scan_qr">
            <input type="hidden" name="qr_code" id="qr_code">
        </form>
        <div id="scanResult" style="margin-top:20px; padding:15px; background:#f8f9fa; border-radius:5px; display:none"></div>
    </div>
    
    <div class="card">
        <h3>ℹ️ Cara Menggunakan</h3>
        <ol style="text-align:left; max-width:500px; margin:20px auto">
            <li>Klik tombol "Mulai Scan QR Code"</li>
            <li>Izinkan akses kamera jika diminta</li>
            <li>Arahkan kamera ke QR Code yang ditampilkan di halaman SDM</li>
            <li>Tunggu hingga QR Code terdeteksi</li>
            <li>Absensi akan tercatat otomatis</li>
        </ol>
        <p style="color:#666; margin-top:20px">
            <strong>Catatan:</strong> Scan pertama untuk absen masuk, scan kedua untuk absen keluar.
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
let scanning = false;
let stream = null;
let scanInterval = null;

function startScan() {
    if (scanning) return;
    
    scanning = true;
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const videoContainer = document.getElementById('video-container');
    const scannerPlaceholder = document.getElementById('scanner-placeholder');
    const btnStart = document.getElementById('btnStart');
    const btnStop = document.getElementById('btnStop');
    
    if (!video || !canvas) {
        alert('Error: Video atau canvas element tidak ditemukan');
        scanning = false;
        return;
    }
    
    navigator.mediaDevices.getUserMedia({ 
        video: { 
            facingMode: 'environment',
            width: { ideal: 1280 },
            height: { ideal: 720 }
        } 
    })
    .then(function(mediaStream) {
        stream = mediaStream;
        video.srcObject = mediaStream;
        video.play();
        
        // Show video, hide placeholder
        videoContainer.style.display = 'block';
        scannerPlaceholder.style.display = 'none';
        btnStart.style.display = 'none';
        btnStop.style.display = 'inline-block';
        
        const ctx = canvas.getContext('2d');
        
        // Start scanning loop
        scanInterval = setInterval(function() {
            if (!scanning || video.readyState !== video.HAVE_ENOUGH_DATA) return;
            
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            
            // Use jsQR library to detect QR code
            if (typeof jsQR !== 'undefined') {
                const code = jsQR(imageData.data, imageData.width, imageData.height, {
                    inversionAttempts: 'dontInvert'
                });
                
                if (code) {
                    const qrData = code.data;
                    console.log('QR Code detected:', qrData);
                    
                    // Verify QR code format
                    if (qrData.startsWith('ATTENDANCE:')) {
                        document.getElementById('qr_code').value = qrData;
                        document.getElementById('scanResult').innerHTML = '<p style="color:#27ae60">✓ QR Code terdeteksi!</p><p>Mencatat absensi...</p>';
                        document.getElementById('scanResult').style.display = 'block';
                        
                        // Submit form
                        setTimeout(function() {
                            document.getElementById('qrForm').submit();
                        }, 500);
                        
                        stopScan();
                    } else {
                        document.getElementById('scanResult').innerHTML = '<p style="color:#e74c3c">✗ QR Code tidak valid untuk kehadiran</p>';
                        document.getElementById('scanResult').style.display = 'block';
                    }
                }
            } else {
                console.error('jsQR library not loaded');
                stopScan();
                alert('Error: Library QR Code tidak ter-load. Silakan refresh halaman.');
            }
        }, 100); // Check every 100ms
    })
    .catch(function(err) {
        console.error('Error accessing camera:', err);
        alert('Error mengakses kamera: ' + err.message + '\n\nPastikan Anda memberikan izin akses kamera.');
        scanning = false;
        btnStart.style.display = 'inline-block';
        btnStop.style.display = 'none';
    });
}

function stopScan() {
    scanning = false;
    
    if (scanInterval) {
        clearInterval(scanInterval);
        scanInterval = null;
    }
    
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    
    const video = document.getElementById('video');
    const videoContainer = document.getElementById('video-container');
    const scannerPlaceholder = document.getElementById('scanner-placeholder');
    const btnStart = document.getElementById('btnStart');
    const btnStop = document.getElementById('btnStop');
    
    if (video) {
        video.srcObject = null;
    }
    
    if (videoContainer) videoContainer.style.display = 'none';
    if (scannerPlaceholder) scannerPlaceholder.style.display = 'block';
    if (btnStart) btnStart.style.display = 'inline-block';
    if (btnStop) btnStop.style.display = 'none';
}

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    stopScan();
});
</script>

</body>
</html>

