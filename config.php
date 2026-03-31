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
        'klasifikasi_donatur.php' => ['icon' => '🏷️', 'label' => 'Klasifikasi'],
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

function getCssLink() {
    return '<link rel="stylesheet" href="assets/css/style.css">';
}

function getGlobalUiEnhancer() {
    return '
<button type="button" class="fab-schedule" id="fabScheduleBtn" title="Jadwal" aria-label="Buka jadwal">📅 Jadwal</button>

<div id="scheduleModal" class="modal" style="display:none;">
    <div class="modal-content schedule-modal">
        <span class="close" id="scheduleCloseBtn">&times;</span>
        <h2 style="margin-top:0;">Jadwal Cepat</h2>
        <p class="schedule-subtitle">Isi agenda harian Anda. Data tersimpan otomatis di browser ini.</p>
        <form id="scheduleForm">
            <div class="form-row">
                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" id="scheduleDate">
                </div>
                <div class="form-group">
                    <label>Waktu</label>
                    <input type="time" id="scheduleTime">
                </div>
            </div>
            <div class="form-group">
                <label>Judul Jadwal *</label>
                <input type="text" id="scheduleTitle" placeholder="Contoh: Meeting evaluasi program" required>
            </div>
            <div class="form-group">
                <label>Keterangan</label>
                <textarea id="scheduleNote" rows="3" placeholder="Catatan singkat"></textarea>
            </div>
            <div class="text-right" style="display:flex; gap:8px; justify-content:flex-end; margin-top:8px;">
                <button type="button" class="btn" id="scheduleHideBtn">Hide</button>
                <button type="submit" class="btn btn-success">Simpan</button>
            </div>
        </form>
        <div id="scheduleList" class="schedule-list"></div>
    </div>
</div>

<script>
(function(){
    var KEY_ITEMS = "rpn_schedule_items_v1";
    var fab = document.getElementById("fabScheduleBtn");
    var modal = document.getElementById("scheduleModal");
    if (!fab || !modal) return;
    var closeBtn = document.getElementById("scheduleCloseBtn");
    var hideBtn = document.getElementById("scheduleHideBtn");
    var form = document.getElementById("scheduleForm");
    var list = document.getElementById("scheduleList");
    var dateEl = document.getElementById("scheduleDate");
    var timeEl = document.getElementById("scheduleTime");
    var titleEl = document.getElementById("scheduleTitle");
    var noteEl = document.getElementById("scheduleNote");

    function safeParse(json, fallback) {
        try { return JSON.parse(json); } catch (e) { return fallback; }
    }
    function loadItems() {
        return safeParse(localStorage.getItem(KEY_ITEMS) || "[]", []);
    }
    function saveItems(items) {
        localStorage.setItem(KEY_ITEMS, JSON.stringify(items));
    }
    function esc(s) {
        var d = document.createElement("div");
        d.textContent = s == null ? "" : String(s);
        return d.innerHTML;
    }
    function render() {
        var items = loadItems().sort(function(a,b){
            var x = (a.date || "") + " " + (a.time || "");
            var y = (b.date || "") + " " + (b.time || "");
            return x.localeCompare(y);
        });
        if (!items.length) {
            list.innerHTML = "<p class=\"schedule-empty\">Belum ada jadwal.</p>";
            return;
        }
        var html = "";
        items.forEach(function(it){
            html += "<div class=\"schedule-item\">" +
                "<div class=\"schedule-item-head\"><strong>" + esc(it.title) + "</strong>" +
                "<button class=\"btn btn-danger btn-xs\" data-del=\"" + esc(it.id) + "\">Hapus</button></div>" +
                "<div class=\"schedule-item-meta\">" + esc(it.date || "-") + " " + esc(it.time || "") + "</div>" +
                (it.note ? "<div class=\"schedule-item-note\">" + esc(it.note) + "</div>" : "") +
                "</div>";
        });
        list.innerHTML = html;
    }

    fab.addEventListener("click", function(){ modal.style.display = "block"; });
    closeBtn.addEventListener("click", function(){ modal.style.display = "none"; });
    hideBtn.addEventListener("click", function(){
        modal.style.display = "none";
    });
    window.addEventListener("click", function(e){
        if (e.target === modal) modal.style.display = "none";
    });

    form.addEventListener("submit", function(e){
        e.preventDefault();
        var title = (titleEl.value || "").trim();
        if (!title) return;
        var items = loadItems();
        items.push({
            id: String(Date.now()) + "_" + Math.random().toString(36).slice(2,7),
            date: dateEl.value || "",
            time: timeEl.value || "",
            title: title,
            note: (noteEl.value || "").trim()
        });
        saveItems(items);
        form.reset();
        render();
    });
    list.addEventListener("click", function(e){
        var id = e.target && e.target.getAttribute ? e.target.getAttribute("data-del") : "";
        if (!id) return;
        var items = loadItems().filter(function(it){ return it.id !== id; });
        saveItems(items);
        render();
    });

    render();
})();
</script>';
}
?>

