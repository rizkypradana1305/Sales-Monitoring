<?php
require_once 'config.php';
require_login();
require_role(['Administrator']);
$pageTitle = 'Manajemen Pengguna';
$current_page = 'users';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $peran = in_array($_POST['peran'] ?? '', ['Administrator','Manajer','Operator']) ? $_POST['peran'] : 'Operator';
        $telepon = trim($_POST['telepon'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['Aktif','Nonaktif']) ? $_POST['status'] : 'Aktif';
        $password = $_POST['password'] ?? '';

        if (!$nama || !$email) {
            flash('error', 'Nama dan email wajib diisi.');
            redirect('manajemen_pengguna.php');
        }

        if ($id > 0) {
            // Update
            if ($password) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET nama=?, email=?, peran=?, telepon=?, status=?, password=? WHERE id=?");
                $stmt->execute([$nama, $email, $peran, $telepon ?: null, $status, $hash, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET nama=?, email=?, peran=?, telepon=?, status=? WHERE id=?");
                $stmt->execute([$nama, $email, $peran, $telepon ?: null, $status, $id]);
            }
            flash('success', "Pengguna \"$nama\" berhasil diperbarui.");
        } else {
            // Create
            if (!$password) $password = 'admin123';
            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, peran, telepon, status) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$nama, $email, $hash, $peran, $telepon ?: null, $status]);
                flash('success', "Pengguna baru \"$nama\" ditambahkan. Password default: $password");
            } catch (PDOException $e) {
                flash('error', 'Gagal menambah pengguna: email mungkin sudah terdaftar.');
            }
        }
        redirect('manajemen_pengguna.php');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id === (int)current_user()['id']) {
            flash('error', 'Tidak dapat menghapus akun sendiri.');
        } else {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            flash('success', 'Pengguna berhasil dihapus.');
        }
        redirect('manajemen_pengguna.php');
    } elseif ($action === 'toggle_status') {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE users SET status = IF(status='Aktif','Nonaktif','Aktif') WHERE id = ?")->execute([$id]);
        flash('success', 'Status pengguna diperbarui.');
        redirect('manajemen_pengguna.php');
    }
}

$search = trim($_GET['q'] ?? '');
$where = $search ? "WHERE nama LIKE ? OR email LIKE ?" : "";
$params = $search ? ["%$search%", "%$search%"] : [];

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 8;
$total = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$total->execute($params);
$totalRows = (int)$total->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY id ASC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll();

include 'includes/header.php';
?>
<div class="page-header">
    <div class="page-title">
        <h2>Manajemen Pengguna</h2>
        <p>Kelola akun pengguna sistem alokasi sales Telkom Madiun beserta peran dan statusnya.</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="openUserModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Pengguna
        </button>
    </div>
</div>

<div class="table-card">
    <div class="table-toolbar">
        <div class="toolbar-left">
            <input type="text" class="table-search" placeholder="Cari nama atau email..." value="<?= e($search) ?>" id="searchInput" onkeypress="if(event.key==='Enter')location.href='?q='+encodeURIComponent(this.value)">
        </div>
        <div class="toolbar-right">
            <span style="font-size:13px;color:#6B7280;">Total: <?= $totalRows ?> pengguna</span>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:50px">No</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Peran</th>
                    <th>Telepon</th>
                    <th>Status</th>
                    <th>Dibuat</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr><td colspan="8" style="text-align:center;padding:32px;color:#9CA3AF;">Belum ada pengguna.</td></tr>
                <?php else: $no=$offset+1; foreach ($users as $u): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <div class="cell-user">
                            <div class="avatar-xs"><?= e(initials($u['nama'])) ?></div>
                            <div class="user-meta">
                                <div class="uname"><?= e($u['nama']) ?></div>
                                <div class="uinfo"><?= $u['id'] === (int)current_user()['id'] ? 'Anda' : '' ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= e($u['email']) ?></td>
                    <td>
                        <?php $badge = $u['peran']==='Administrator'?'badge-red':($u['peran']==='Manajer'?'badge-blue':'badge-gray'); ?>
                        <span class="badge <?= $badge ?>"><?= e($u['peran']) ?></span>
                    </td>
                    <td><?= e($u['telepon'] ?: '-') ?></td>
                    <td>
                        <?php if ($u['status']==='Aktif'): ?>
                            <span class="badge badge-green">Aktif</span>
                        <?php else: ?>
                            <span class="badge badge-gray">Nonaktif</span>
                        <?php endif; ?>
                    </td>
                    <td><?= format_date($u['created_at']) ?></td>
                    <td class="text-right">
                        <div class="cell-actions">
                            <button class="act-view" onclick="toggleStatus(<?= $u['id'] ?>)">
                                <?= $u['status']==='Aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
                            </button>
                            <button class="act-edit" onclick='openUserModal(<?= json_encode($u, JSON_UNESCAPED_UNICODE|JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Edit</button>
                            <button class="act-delete" onclick="deleteUser(<?= $u['id'] ?>, '<?= e($u['nama']) ?>')">Hapus</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="table-footer">
        <div>Menampilkan <?= count($users) ?> dari <?= $totalRows ?> pengguna</div>
        <div class="pagination">
            <a class="page-btn" href="?<?= http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)])) ?>" <?= $page<=1?'disabled':'' ?>>‹</a>
            <?php for ($i=1; $i<=$totalPages; $i++): ?>
            <a class="page-btn <?= $i===$page?'active':'' ?>" href="?<?= http_build_query(array_merge($_GET, ['page'=>$i])) ?>"><?= $i ?></a>
            <?php endfor; ?>
            <a class="page-btn" href="?<?= http_build_query(array_merge($_GET, ['page' => min($totalPages, $page + 1)])) ?>" <?= $page>=$totalPages?'disabled':'' ?>>›</a>
        </div>
    </div>
</div>

<!-- Role legend -->
<div class="card">
    <div class="card-title">Keterangan Peran Pengguna</div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:12px;">
        <div style="padding:16px;border:1px solid #E5E7EB;border-radius:8px;">
            <span class="badge badge-red">Administrator</span>
            <p style="font-size:12px;color:#6B7280;margin-top:8px;">Akses penuh ke seluruh fitur sistem, termasuk manajemen pengguna dan pengaturan.</p>
        </div>
        <div style="padding:16px;border:1px solid #E5E7EB;border-radius:8px;">
            <span class="badge badge-blue">Manajer</span>
            <p style="font-size:12px;color:#6B7280;margin-top:8px;">Akses ke dashboard, data, optimasi, dan laporan. Tidak bisa kelola pengguna.</p>
        </div>
        <div style="padding:16px;border:1px solid #E5E7EB;border-radius:8px;">
            <span class="badge badge-gray">Operator</span>
            <p style="font-size:12px;color:#6B7280;margin-top:8px;">Akses ke dashboard, data sales/pelanggan, dan peta. Tidak bisa optimasi atau laporan.</p>
        </div>
    </div>
</div>

<script>
function openUserModal(u) {
    const user = (typeof u === 'object' && u.id) ? u : null;
    openModal(`
        <div class="modal-overlay" style="display:flex">
            <div class="modal">
                <div class="modal-header">
                    <h3>${user ? 'Edit Pengguna' : 'Tambah Pengguna'}</h3>
                    <button class="modal-close" data-close-modal>&times;</button>
                </div>
                <div class="modal-body">
                    <form id="userForm" method="POST" action="manajemen_pengguna.php">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" value="${user ? user.id : ''}">
                        <div class="form-group">
                            <label>Nama Lengkap <span style="color:#E31E24">*</span></label>
                            <input type="text" class="form-control" name="nama" value="${user ? escapeHtml(user.nama) : ''}" required>
                        </div>
                        <div class="form-group">
                            <label>Email <span style="color:#E31E24">*</span></label>
                            <input type="email" class="form-control" name="email" value="${user ? escapeHtml(user.email) : ''}" required>
                        </div>
                        <div class="form-group">
                            <label>Password ${user ? '(kosongkan jika tidak diubah)' : ''}</label>
                            <input type="password" class="form-control" name="password" placeholder="${user ? '••••••••' : 'default: admin123'}">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Peran</label>
                                <select class="form-control" name="peran">
                                    <option value="Operator" ${user&&user.peran==='Operator'?'selected':''}>Operator</option>
                                    <option value="Manajer" ${user&&user.peran==='Manajer'?'selected':''}>Manajer</option>
                                    <option value="Administrator" ${user&&user.peran==='Administrator'?'selected':''}>Administrator</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" name="status">
                                    <option value="Aktif" ${(!user||user.status==='Aktif')?'selected':''}>Aktif</option>
                                    <option value="Nonaktif" ${user&&user.status==='Nonaktif'?'selected':''}>Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Telepon</label>
                            <input type="text" class="form-control" name="telepon" value="${user ? escapeHtml(user.telepon||'') : ''}">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline" data-close-modal>Batal</button>
                    <button class="btn btn-primary" onclick="document.getElementById('userForm').submit()">${user ? 'Simpan Perubahan' : 'Tambah Pengguna'}</button>
                </div>
            </div>
        </div>
    `);
}

function deleteUser(id, nama) {
    confirmDialog({
        title: 'Hapus Pengguna',
        message: `Yakin ingin menghapus "${nama}"? Tindakan ini permanen.`,
        confirmText: 'Hapus',
        onConfirm: () => {
            const form = document.createElement('form');
            form.method = 'POST'; form.action = 'manajemen_pengguna.php';
            form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${id}">`;
            document.body.appendChild(form); form.submit();
        }
    });
}

function toggleStatus(id) {
    const form = document.createElement('form');
    form.method = 'POST'; form.action = 'manajemen_pengguna.php';
    form.innerHTML = `<input type="hidden" name="action" value="toggle_status"><input type="hidden" name="id" value="${id}">`;
    document.body.appendChild(form); form.submit();
}
</script>
<?php include 'includes/footer.php'; ?>
