<?php
require_once 'config.php';
require_login();
$pageTitle = 'Data Sales';
$current_page = 'sales';

$pdo = db();

// Filters
$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$wilayah = $_GET['wilayah'] ?? '';
$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(nama LIKE ? OR email LIKE ? OR telepon LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($status !== '') { $where[] = "status = ?"; $params[] = $status; }
if ($wilayah !== '') { $where[] = "wilayah = ?"; $params[] = $wilayah; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$total = $pdo->prepare("SELECT COUNT(*) FROM sales $whereSql");
$total->execute($params);
$totalRows = (int)$total->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT s.*,
        (SELECT COUNT(*) FROM pelanggan p WHERE p.sales_id = s.id) AS jml_pelanggan
        FROM sales s
        $whereSql
        ORDER BY s.id DESC
        LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// For filter dropdown
$wilayah_list = $pdo->query("SELECT DISTINCT wilayah FROM sales ORDER BY wilayah")->fetchAll(PDO::FETCH_COLUMN);

include 'includes/header.php';
?>
<div class="page-header">
    <div class="page-title">
        <h2>Data Sales</h2>
        <p>Daftar tim sales Telkom Madiun beserta kapasitas dan jumlah pelanggan yang ditangani.</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-outline" onclick="exportSalesCSV()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Ekspor CSV
        </button>
        <button class="btn btn-primary" onclick="openSalesModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Sales
        </button>
    </div>
</div>

<div class="table-card">
    <div class="table-toolbar">
        <div class="toolbar-left">
            <input type="text" class="table-search" placeholder="Cari nama, email, telepon..." value="<?= e($search) ?>" id="searchInput" onkeypress="if(event.key==='Enter')document.location='?q='+encodeURIComponent(this.value)+(document.getElementById('fStatus').value?'&status='+document.getElementById('fStatus').value:'')+(document.getElementById('fWilayah').value?'&wilayah='+document.getElementById('fWilayah').value:'')">
            <select class="table-select" id="fStatus" onchange="this.form ? this.form.submit() : applyFilters()">
                <option value="">Semua Status</option>
                <option value="Aktif" <?= $status==='Aktif'?'selected':'' ?>>Aktif</option>
                <option value="Cuti" <?= $status==='Cuti'?'selected':'' ?>>Cuti</option>
                <option value="Nonaktif" <?= $status==='Nonaktif'?'selected':'' ?>>Nonaktif</option>
            </select>
            <select class="table-select" id="fWilayah" onchange="applyFilters()">
                <option value="">Semua Wilayah</option>
                <?php foreach ($wilayah_list as $w): ?>
                <option value="<?= e($w) ?>" <?= $wilayah===$w?'selected':'' ?>><?= e($w) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="toolbar-right">
            <button class="btn btn-outline btn-sm" onclick="applyFilters()">Terapkan Filter</button>
            <a href="data_sales.php" class="btn btn-ghost btn-sm">Reset</a>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table" id="salesTable">
            <thead>
                <tr>
                    <th style="width:50px">No</th>
                    <th>Nama Sales</th>
                    <th>Wilayah</th>
                    <th>Telepon</th>
                    <th>Kapasitas</th>
                    <th>Pelanggan</th>
                    <th>Status</th>
                    <th>Bergabung</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                <tr><td colspan="9" style="text-align:center; padding: 32px; color: #9CA3AF;">Belum ada data sales. Klik "Tambah Sales" untuk menambahkan.</td></tr>
                <?php else: $no = $offset + 1; foreach ($rows as $s): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <div class="cell-user">
                            <div class="avatar-xs"><?= e(initials($s['nama'])) ?></div>
                            <div class="user-meta">
                                <div class="uname"><?= e($s['nama']) ?></div>
                                <div class="uinfo"><?= e($s['email'] ?: '-') ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= e($s['wilayah']) ?></td>
                    <td><?= e($s['telepon'] ?: '-') ?></td>
                    <td><strong><?= (int)$s['kapasitas'] ?></strong> plgn</td>
                    <td>
                        <?php
                            $util = $s['kapasitas'] > 0 ? min(100, $s['jml_pelanggan'] / $s['kapasitas'] * 100) : 0;
                            $badge = $util >= 90 ? 'badge-red' : ($util >= 50 ? 'badge-yellow' : 'badge-green');
                        ?>
                        <span class="badge <?= $badge ?>"><?= (int)$s['jml_pelanggan'] ?> / <?= (int)$s['kapasitas'] ?></span>
                    </td>
                    <td>
                        <?php if ($s['status'] === 'Aktif'): ?>
                            <span class="badge badge-green">Aktif</span>
                        <?php elseif ($s['status'] === 'Cuti'): ?>
                            <span class="badge badge-yellow">Cuti</span>
                        <?php else: ?>
                            <span class="badge badge-gray">Nonaktif</span>
                        <?php endif; ?>
                    </td>
                    <td><?= format_date($s['tanggal_bergabung']) ?></td>
                    <td class="text-right">
                        <div class="cell-actions">
                            <a href="detail_sales.php?id=<?= $s['id'] ?>" class="act-view">Lihat</a>
                            <button class="act-edit" onclick="openSalesModal(<?= $s['id'] ?>)">Edit</button>
                            <button class="act-delete" onclick="deleteSales(<?= $s['id'] ?>, '<?= e($s['nama']) ?>')">Hapus</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        <div>Menampilkan <?= count($rows) ?> dari <?= $totalRows ?> sales</div>
        <div class="pagination">
            <a class="page-btn" href="?<?= http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)])) ?>" <?= $page <= 1 ? 'disabled' : '' ?>>‹</a>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a class="page-btn <?= $i === $page ? 'active' : '' ?>" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
            <?php endfor; ?>
            <a class="page-btn" href="?<?= http_build_query(array_merge($_GET, ['page' => min($totalPages, $page + 1)])) ?>" <?= $page >= $totalPages ? 'disabled' : '' ?>>›</a>
        </div>
    </div>
</div>

<?php
$page_js = 'data_sales.js';
include 'includes/footer.php';
?>
<script>
    window.__salesData = <?= json_encode($rows, JSON_UNESCAPED_UNICODE) ?>;
    window.__wilayahList = <?= json_encode($wilayah_list, JSON_UNESCAPED_UNICODE) ?>;
</script>
