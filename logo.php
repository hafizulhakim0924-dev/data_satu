<?php
/**
 * Logo / brand navbar — sesuaikan src gambar atau isi teks sesuai kebutuhan.
 * Dipanggil dari index.php (sidebar). Ganti path gambar jika file Anda di lokasi lain.
 */
$logoImg = 'assets/img/logo.png'; // opsional: hapus baris img jika hanya teks
?>
<a href="index.php" class="navbar-logo-link" title="Beranda">
    <?php if (is_file(__DIR__ . '/' . $logoImg)): ?>
        <img src="<?= htmlspecialchars($logoImg) ?>" alt="Logo" class="navbar-logo-img">
    <?php endif; ?>
    <span class="navbar-logo-text">Rangkiang Peduli Negeri</span>
</a>
