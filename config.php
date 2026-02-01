<?php
/* =========================
   DATABASE CONFIG
========================= */
$host = "localhost";
$db   = "rank3598_bankdata";
$user = "rank3598_bankdata";
$pass = "Hakim123!";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Helper Functions
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka ?? 0, 0, ',', '.');
}

function getNavMenu($current = '') {
    $menu = [
        'index.php' => ['icon' => '📊', 'label' => 'Dashboard'],
        'hr.php' => ['icon' => '👥', 'label' => 'SDM'],
        'keuangan.php' => ['icon' => '💰', 'label' => 'Keuangan'],
        'user.php' => ['icon' => '👤', 'label' => 'User'],
        'donatur.php' => ['icon' => '🤝', 'label' => 'Donatur'],
        'donasi.php' => ['icon' => '💵', 'label' => 'Donasi'],
        'program.php' => ['icon' => '📋', 'label' => 'Program'],
    ];
    
    $html = '<nav class="nav-menu">';
    foreach($menu as $file => $data) {
        $active = (basename($_SERVER['PHP_SELF']) == $file || ($file == 'index.php' && $current == 'dashboard')) ? 'active' : '';
        $html .= '<a href="' . $file . '" class="' . $active . '">' . $data['icon'] . ' ' . $data['label'] . '</a>';
    }
    $html .= '</nav>';
    return $html;
}
?>

