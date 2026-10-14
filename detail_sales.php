<?php
require_once 'config.php';
require_login();
$pageTitle = 'Detail Sales';
$current_page = 'sales';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect('data_sales.php');

$pdo = db();
$s = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
$s->execute([$id]);
$sales = $s->fetch();
if (!$sales) { flash('error', 'Sales tidak ditemukan'); redirect('data_sales.php'); }

// Customers
$pelanggan = $pdo->prepare("SELECT * FROM pelanggan WHERE sales_id = ? ORDER BY id");
$pelanggan->execute([$id]);
$custs = $pelanggan->fetchAll();

$total_jarak = 0;
foreach ($custs as $p) {
    $total_jarak += haversine($sales['latitude'], $sales['longitude'], $p['latitude'], $p['longitude']);
}
$avg_jarak = count($custs) > 0 ? $total_jarak / count($custs) : 0;
$util = $sales['kapasitas'] > 0 ? min(100, count($custs) / $sales['kapasitas'] * 100) : 0;
$avg_potensi = count($custs) > 0 ? array_sum(array_column($custs, 'skor_potensi')) / count($custs) : 0;

include 'includes/header.php';
?>
<div class="page-header">
    <div class="page-title">
        <h2>Detail Sales: <?= e($sales['nama']) ?></h2>
        <p>Wilayah <?= e($sales['wilayah']) ?> · Titik basis <?= format_number($sales['latitude'], 4) ?>, <?= format_number($sales['longitude'], 4) ?> · <?= count($custs) ?> pelanggan teralokasi.</p>
    </div>
    <div class="header-actions">
        <a href="data_sales.php" class="btn btn-outline">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Kembali
        </a>
        <button class="btn btn-primary" onclick="exportDetail()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/></svg>
            Ekspor Detail
        </button>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon red"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
        <div class="stat-label">Jumlah Pelanggan</div>
        <div class="stat-value"><?= count($custs) ?></div>
        <div class="stat-sub">Utilisasi <?= format_number($util, 0) ?>% dari kapasitas <?= $sales['kapasitas'] ?></div>
        <div class="progress-bar" style="margin-top:6px;"><div class="progress-fill" style="width: <?= $util ?>%"></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/></svg></div>
        <div class="stat-label">Total Jarak</div>
        <div class="stat-value"><?= format_number($total_jarak, 1) ?></div>
        <div class="stat-sub">km · Rata-rata <?= format_number($avg_jarak, 1) ?> km/pelanggan</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
        <div class="stat-label">Rata-rata Skor Potensi</div>
        <div class="stat-value"><?= format_number($avg_potensi, 0) ?></div>
        <div class="stat-sub">Dari <?= count($custs) ?> pelanggan</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
        <div class="stat-label">Status Sales</div>
        <div class="stat-value" style="font-size:20px;">
            <?php if ($sales['status']==='Aktif'): ?><span class="badge badge-green" style="font-size:14px;padding:6px 14px;">Aktif</span>
            <?php elseif ($sales['status']==='Cuti'): ?><span class="badge badge-yellow" style="font-size:14px;padding:6px 14px;">Cuti</span>
            <?php else: ?><span class="badge badge-gray" style="font-size:14px;padding:6px 14px;">Nonaktif</span><?php endif; ?>
        </div>
        <div class="stat-sub">Bergabung <?= format_date($sales['tanggal_bergabung']) ?></div>
    </div>
</div>

<div class="charts-grid">
    <!-- Sales info -->
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Informasi Sales</div>
                <div class="card-subtitle">Profil dan kontak</div>
            </div>
        </div>
        <div style="display:flex;gap:14px;align-items:center;margin-bottom:18px;">
            <div style="width:64px;height:64px;border-radius:50%;background:#FFE4E6;color:#E31E24;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700;">
                <?= e(initials($sales['nama'])) ?>
            </div>
            <div>
                <div style="font-size:18px;font-weight:700;"><?= e($sales['nama']) ?></div>
                <div style="color:#6B7280;font-size:13px;">Sales <?= e($sales['wilayah']) ?> · Kapasitas <?= $sales['kapasitas'] ?> pelanggan</div>
            </div>
        </div>
        <table style="width:100%;font-size:13px;">
            <tr><td style="padding:6px 0;color:#6B7280;width:120px;">Email</td><td>: <?= e($sales['email'] ?: '-') ?></td></tr>
            <tr><td style="padding:6px 0;color:#6B7280;">Telepon</td><td>: <?= e($sales['telepon'] ?: '-') ?></td></tr>
            <tr><td style="padding:6px 0;color:#6B7280;">NIK</td><td>: <?= e($sales['nik'] ?: '-') ?></td></tr>
            <tr><td style="padding:6px 0;color:#6B7280;">Bergabung</td><td>: <?= format_date($sales['tanggal_bergabung']) ?></td></tr>
            <tr><td style="padding:6px 0;color:#6B7280;">Lokasi Basis</td><td>: <?= format_number($sales['latitude'], 6) ?>, <?= format_number($sales['longitude'], 6) ?></td></tr>
        </table>
    </div>
    <!-- Distance chart -->
    <div class="chart-card">
        <div class="chart-title">Distribusi Jarak ke Pelanggan</div>
        <div class="chart-subtitle">Pelanggan terurut berdasarkan jarak</div>
        <div class="chart-canvas-wrap">
            <canvas id="chartJarak"></canvas>
        </div>
    </div>
</div>

<!-- Customer list -->
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Daftar Pelanggan</div>
            <div class="card-subtitle"><?= count($custs) ?> pelanggan yang dialokasikan ke sales ini</div>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:50px">No</th>
                    <th>Nama Pelanggan</th>
                    <th>Wilayah</th>
                    <th>Potensi</th>
                    <th class="text-right">Skor</th>
                    <th class="text-right">Jarak (km)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($custs)): ?>
                <tr><td colspan="7" style="text-align:center;padding:32px;color:#9CA3AF;">Belum ada pelanggan teralokasi.</td></tr>
                <?php else: $no=1; foreach ($custs as $p):
                    $km = haversine($sales['latitude'], $sales['longitude'], $p['latitude'], $p['longitude']);
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><strong><?= e($p['nama']) ?></strong></td>
                    <td><?= e($p['wilayah']) ?></td>
                    <td>
                        <?php $badge = $p['potensi']==='Tinggi'?'badge-red':($p['potensi']==='Sedang'?'badge-yellow':'badge-gray'); ?>
                        <span class="badge <?= $badge ?>"><?= e($p['potensi']) ?></span>
                    </td>
                    <td class="text-right"><?= (int)$p['skor_potensi'] ?></td>
                    <td class="text-right"><?= format_number($km, 2) ?></td>
                    <td><span class="badge badge-<?= $p['status']==='Aktif'?'green':($p['status']==='Prospek'?'blue':'yellow') ?>"><?= e($p['status']) ?></span></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Mini map -->
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Peta Cakupan Wilayah</div>
            <div class="card-subtitle">Lokasi sales (merah) dan pelanggan teralokasi</div>
        </div>
    </div>
    <div id="mapDetail" style="height:400px;border-radius:8px;"></div>
</div>

<?php
$page_js = 'detail_sales.js';
$js_data = [
    'sales' => $sales,
    'pelanggan' => $custs,
];
include 'includes/footer.php';
?>
<script>window.__detailData = <?= json_encode($js_data, JSON_UNESCAPED_UNICODE) ?>;</script>
