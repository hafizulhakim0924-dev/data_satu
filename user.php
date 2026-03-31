<?php
require_once 'config.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add_user') {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, nama_lengkap, nip, jabatan, departemen, no_hp, alamat, role, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$_POST['username'], $_POST['email'], $password, $_POST['nama_lengkap'], $_POST['nip'], $_POST['jabatan'], $_POST['departemen'], $_POST['no_hp'], $_POST['alamat'], $_POST['role'], 'active']);
        header("Location: user.php?msg=User berhasil ditambahkan");
        exit;
    }
    
    if ($action == 'update_user') {
        $id = $_POST['id'];
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, password=?, nama_lengkap=?, nip=?, jabatan=?, departemen=?, no_hp=?, alamat=?, role=?, status=? WHERE id=?");
            $stmt->execute([$_POST['username'], $_POST['email'], $password, $_POST['nama_lengkap'], $_POST['nip'], $_POST['jabatan'], $_POST['departemen'], $_POST['no_hp'], $_POST['alamat'], $_POST['role'], $_POST['status'], $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, nama_lengkap=?, nip=?, jabatan=?, departemen=?, no_hp=?, alamat=?, role=?, status=? WHERE id=?");
            $stmt->execute([$_POST['username'], $_POST['email'], $_POST['nama_lengkap'], $_POST['nip'], $_POST['jabatan'], $_POST['departemen'], $_POST['no_hp'], $_POST['alamat'], $_POST['role'], $_POST['status'], $id]);
        }
        header("Location: user.php?msg=User berhasil diupdate");
        exit;
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY nama_lengkap")->fetchAll();
$edit_id = $_GET['edit'] ?? null;
$edit_user = null;
if ($edit_id) {
    $edit_user = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $edit_user->execute([$edit_id]);
    $edit_user = $edit_user->fetch();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>User Management - Rangkiang Peduli Negeri</title>
<?= getCssLink() ?>
</head>
<body>

<div class="navbar">
    <h1>🏛️ Rangkiang Peduli Negeri</h1>
    <?= getNavMenu() ?>
</div>

<div class="container">
    <h1 style="margin:20px 0">👤 Manajemen User</h1>
    
    <?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <h2>Data User</h2>
            <button class="btn" onclick="document.getElementById('modalUser').style.display='block'">+ Tambah User</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Nama Lengkap</th>
                    <th>Email</th>
                    <th>NIP</th>
                    <th>Jabatan</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['nip'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($u['jabatan'] ?? '-') ?></td>
                    <td><span class="badge badge-<?= $u['role'] ?>"><?= strtoupper($u['role']) ?></span></td>
                    <td><?= htmlspecialchars($u['status']) ?></td>
                    <td>
                        <a href="?edit=<?= $u['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Modal Tambah/Edit User -->
    <div id="modalUser" class="modal" style="<?= $edit_user ? 'display:block' : '' ?>">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modalUser').style.display='none'; window.location.href='user.php'">&times;</span>
            <h2><?= $edit_user ? 'Edit User' : 'Tambah User' ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?= $edit_user ? 'update_user' : 'add_user' ?>">
                <?php if($edit_user): ?>
                <input type="hidden" name="id" value="<?= $edit_user['id'] ?>">
                <?php endif; ?>
                <div class="form-row">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" value="<?= $edit_user['username'] ?? '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" value="<?= $edit_user['email'] ?? '' ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Password <?= $edit_user ? '(kosongkan jika tidak diubah)' : '*' ?></label>
                    <input type="password" name="password" <?= $edit_user ? '' : 'required' ?>>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" value="<?= $edit_user['nama_lengkap'] ?? '' ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>NIP</label>
                        <input type="text" name="nip" value="<?= $edit_user['nip'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Jabatan</label>
                        <input type="text" name="jabatan" value="<?= $edit_user['jabatan'] ?? '' ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Departemen</label>
                        <input type="text" name="departemen" value="<?= $edit_user['departemen'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>No HP</label>
                        <input type="text" name="no_hp" value="<?= $edit_user['no_hp'] ?? '' ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" required>
                            <option value="admin" <?= ($edit_user['role'] ?? '') == 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="manager" <?= ($edit_user['role'] ?? '') == 'manager' ? 'selected' : '' ?>>Manager</option>
                            <option value="staff" <?= ($edit_user['role'] ?? '') == 'staff' ? 'selected' : '' ?>>Staff</option>
                            <option value="volunteer" <?= ($edit_user['role'] ?? '') == 'volunteer' ? 'selected' : '' ?>>Volunteer</option>
                        </select>
                    </div>
                    <?php if($edit_user): ?>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="active" <?= $edit_user['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $edit_user['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat"><?= $edit_user['alamat'] ?? '' ?></textarea>
                </div>
                <button type="submit" class="btn btn-success">Simpan</button>
                <button type="button" class="btn" onclick="document.getElementById('modalUser').style.display='none'; window.location.href='user.php'">Batal</button>
            </form>
        </div>
    </div>
    
</div>

<script>
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        window.location.href = 'user.php';
    }
}
</script>

<?= getGlobalUiEnhancer() ?>
</body>
</html>

