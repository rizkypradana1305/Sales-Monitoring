<?php
require_once 'config.php';
require_login();
$pageTitle = 'Data Pelanggan';
$current_page = 'pelanggan';

$pdo = db();

$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$potensi = $_GET['potensi'] ?? '';
$sales_id = $_GET['sales_id'] ?? '';

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(p.nama LIKE ? OR p.alamat LIKE ? OR p.wilayah LIKE ?)";
    $p = "%$search%";
    array_push($params, $p, $p, $p);
}
if ($status !== '') { $where[] = "p.status = ?"; $params[] = $status; }
if ($potensi !== '') { $where[] = "p.potensi = ?"; $params[] = $potensi; }
if ($sales_id !== '') { $where[] = "p.sales_id = ?"; $params[] = (int)$sales_id; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$total = $pdo->prepare("SELECT COUNT(*) FROM pelanggan p $whereSql");
$total->execute($params);
$totalRows = (int)$total->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT p.*, s.nama AS sales_nama
    FROM pelanggan p
    LEFT JOIN sales s ON s.id = p.sales_id
    $whereSql
    ORDER BY p.id DESC
    LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$sales_list = $pdo->query("SELECT id, nama FROM sales WHERE status='Aktif' ORDER BY nama")->fetchAll();

include 'includes/header.php';
?>
<div class="page-header">
    <div class="page-title">
        <h2>Data Pelanggan</h2>
        <p>Daftar pelanggan potensial di wilayah layanan Telkom Madiun beserta informasi potensi dan sales penanggung jawab.</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-outline" onclick="exportPelangganCSV()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/></svg>
            Ekspor
        </button>
        <button class="btn btn-primary" onclick="openPelangganModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Pelanggan
        </button>
    </div>
</div>

<div class="table-card">
    <div class="table-toolbar">
        <div class="toolbar-left">
            <input type="text" class="table-search" placeholder="Cari nama, alamat, wilayah..." value="<?= e($search) ?>" id="searchInput" onkeypress="if(event.key==='Enter')applyFilters()">
            <select class="table-select" id="fStatus">
                <option value="">Semua Status</option>
                <option value="Aktif" <?= $status==='Aktif'?'selected':'' ?>>Aktif</option>
                <option value="Prospek" <?= $status==='Prospek'?'selected':'' ?>>Prospek</option>
                <option value="Pending" <?= $status==='Pending'?'selected':'' ?>>Pending</option>
            </select>
            <select class="table-select" id="fPotensi">
                <option value="">Semua Potensi</option>
                <option value="Tinggi" <?= $potensi==='Tinggi'?'selected':'' ?>>Tinggi</option>
                <option value="Sedang" <?= $potensi==='Sedang'?'selected':'' ?>>Sedang</option>
                <option value="Rendah" <?= $potensi==='Rendah'?'selected':'' ?>>Rendah</option>
            </select>
            <select class="table-select" id="fSales">
                <option value="">Semua Sales</option>
                <?php foreach ($sales_list as $s): ?>
                <option value="<?= $s['id'] ?>" <?= (string)$sales_id===(string)$s['id']?'selected':'' ?>><?= e($s['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="toolbar-right">
            <button class="btn btn-outline btn-sm" onclick="applyFilters()">Terapkan</button>
            <a href="data_pelanggan.php" class="btn btn-ghost btn-sm">Reset</a>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table" id="pelangganTable">
            <thead>
                <tr>
                    <th style="width:50px">No</th>
                    <th>Nama Pelanggan</th>
                    <th>Alamat</th>
                    <th>Wilayah</th>
                    <th>Potensi</th>
                    <th>Produk</th>
                    <th>Status</th>
                    <th>Sales PJ</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                <tr><td colspan="9" style="text-align:center; padding:32px; color:#9CA3AF;">Belum ada data pelanggan.</td></tr>
                <?php else: $no = $offset + 1; foreach ($rows as $p): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <div class="cell-user">
                            <div class="avatar-xs" style="background:<?= $p['potensi']==='Tinggi'?'#FEE2E2':($p['potensi']==='Sedang'?'#FEF3C7':'#F3F4F6') ?>;color:<?= $p['potensi']==='Tinggi'?'#E31E24':($p['potensi']==='Sedang'?'#B45309':'#6B7280') ?>"><?= e(initials($p['nama'])) ?></div>
                            <div class="user-meta">
                                <div class="uname"><?= e($p['nama']) ?></div>
                                <div class="uinfo">Skor: <?= (int)$p['skor_potensi'] ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="max-width:240px"><?= e($p['alamat']) ?></td>
                    <td><?= e($p['wilayah']) ?></td>
                    <td>
                        <?php
                            $badge = $p['potensi']==='Tinggi'?'badge-red':($p['potensi']==='Sedang'?'badge-yellow':'badge-gray');
                        ?>
                        <span class="badge <?= $badge ?>"><?= e($p['potensi']) ?></span>
                    </td>
                    <td><?= e($p['produk'] ?: '-') ?></td>
                    <td>
                        <?php
                            $sBadge = $p['status']==='Aktif'?'badge-green':($p['status']==='Prospek'?'badge-blue':'badge-yellow');
                        ?>
                        <span class="badge <?= $sBadge ?>"><?= e($p['status']) ?></span>
                    </td>
                    <td><?= e($p['sales_nama'] ?: '<span style="color:#9CA3AF">Belum dialokasi</span>') ?></td>
                    <td class="text-right">
                        <div class="cell-actions">
                            <button class="act-edit" onclick="openPelangganModal(<?= $p['id'] ?>)">Edit</button>
                            <button class="act-delete" onclick="deletePelanggan(<?= $p['id'] ?>, '<?= e($p['nama']) ?>')">Hapus</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="table-footer">
        <div>Menampilkan <?= count($rows) ?> dari <?= $totalRows ?> pelanggan</div>
        <div class="pagination">
            <a class="page-btn" href="?<?= http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)])) ?>" <?= $page<=1?'disabled':'' ?>>‹</a>
            <?php for ($i=1; $i<=$totalPages; $i++): ?>
            <a class="page-btn <?= $i===$page?'active':'' ?>" href="?<?= http_build_query(array_merge($_GET, ['page'=>$i])) ?>"><?= $i ?></a>
            <?php endfor; ?>
            <a class="page-btn" href="?<?= http_build_query(array_merge($_GET, ['page' => min($totalPages, $page + 1)])) ?>" <?= $page>=$totalPages?'disabled':'' ?>>›</a>
        </div>
    </div>
</div>
<?php
$page_js = 'data_pelanggan.js';
include 'includes/footer.php';
?>
<script>
    window.__pelangganData = <?= json_encode($rows, JSON_UNESCAPED_UNICODE) ?>;
    window.__salesList = <?= json_encode($sales_list, JSON_UNESCAPED_UNICODE) ?>;
</script>
