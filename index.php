<?php
/* ==============================
   KONFIGURASI DATABASE
================================ */
$host = "localhost";
$db   = "rank3598_bankdata";
$user = "rank3598_bankdata";
$pass = "Hakim123!";

/* ==============================
   KONEKSI PDO
================================ */
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

/* ==============================
   BUAT TABEL JIKA BELUM ADA
================================ */
$pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(100),
        username VARCHAR(50),
        password VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

/* ==============================
   TAMBAH DATA
================================ */
if (isset($_POST['simpan'])) {
    $stmt = $pdo->prepare(
        "INSERT INTO users (nama, username, password) VALUES (?, ?, ?)"
    );
    $stmt->execute([
        $_POST['nama'],
        $_POST['username'],
        password_hash($_POST['password'], PASSWORD_DEFAULT)
    ]);
    header("Location: management.php");
    exit;
}

/* ==============================
   HAPUS DATA
================================ */
if (isset($_GET['hapus'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$_GET['hapus']]);
    header("Location: management.php");
    exit;
}

/* ==============================
   EDIT DATA
================================ */
if (isset($_POST['update'])) {
    $sql = "UPDATE users SET nama=?, username=?";
    $params = [$_POST['nama'], $_POST['username']];

    if (!empty($_POST['password'])) {
        $sql .= ", password=?";
        $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }

    $sql .= " WHERE id=?";
    $params[] = $_POST['id'];

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    header("Location: management.php");
    exit;
}

/* ==============================
   AMBIL DATA
================================ */
$data = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Management Data</title>
    <style>
        body { font-family: Arial; padding: 30px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #f2f2f2; }
        input, button { padding: 8px; width: 100%; }
        button { cursor: pointer; }
    </style>
</head>
<body>

<h2><?= $editData ? "Edit User" : "Tambah User" ?></h2>

<form method="post">
    <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">

    Nama<br>
    <input type="text" name="nama" required value="<?= $editData['nama'] ?? '' ?>"><br><br>

    Username<br>
    <input type="text" name="username" required value="<?= $editData['username'] ?? '' ?>"><br><br>

    Password <?= $editData ? "(kosongkan jika tidak diubah)" : "" ?><br>
    <input type="password" name="password"><br><br>

    <button type="submit" name="<?= $editData ? 'update' : 'simpan' ?>">
        <?= $editData ? 'Update' : 'Simpan' ?>
    </button>
</form>

<hr>

<h2>Data Users</h2>
<table>
<tr>
    <th>No</th>
    <th>Nama</th>
    <th>Username</th>
    <th>Tanggal</th>
    <th>Aksi</th>
</tr>

<?php foreach ($data as $i => $row): ?>
<tr>
    <td><?= $i+1 ?></td>
    <td><?= htmlspecialchars($row['nama']) ?></td>
    <td><?= htmlspecialchars($row['username']) ?></td>
    <td><?= $row['created_at'] ?></td>
    <td>
        <a href="?edit=<?= $row['id'] ?>">Edit</a> |
        <a href="?hapus=<?= $row['id'] ?>" onclick="return confirm('Hapus data ini?')">Hapus</a>
    </td>
</tr>
<?php endforeach; ?>
</table>

</body>
</html>
