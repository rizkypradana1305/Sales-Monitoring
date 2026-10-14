<?php
require_once 'config.php';
require_login();
$pageTitle = 'Dashboard';
$current_page = 'dashboard';

// Stat queries
$pdo = db();
$total_sales = (int)$pdo->query("SELECT COUNT(*) FROM sales WHERE status = 'Aktif'")->fetchColumn();
$total_pelanggan = (int)$pdo->query("SELECT COUNT(*) FROM pelanggan")->fetchColumn();
$total_teralokasi = (int)$pdo->query("SELECT COUNT(*) FROM pelanggan WHERE sales_id IS NOT NULL")->fetchColumn();
$total_wilayah = (int)$pdo->query("SELECT COUNT(DISTINCT wilayah) FROM pelanggan")->fetchColumn();

// Sales utilization
$sales_util = $pdo->query("
    SELECT s.nama, s.kapasitas,
           COALESCE((SELECT COUNT(*) FROM pelanggan p WHERE p.sales_id = s.id), 0) AS terpakai
    FROM sales s WHERE s.status = 'Aktif' ORDER BY s.nama LIMIT 8
")->fetchAll();

// Pelanggan per wilayah
$wilayah_stats = $pdo->query("
    SELECT wilayah, COUNT(*) AS jml,
           SUM(CASE WHEN potensi='Tinggi' THEN 1 ELSE 0 END) AS tinggi,
           SUM(CASE WHEN potensi='Sedang' THEN 1 ELSE 0 END) AS sedang,
           SUM(CASE WHEN potensi='Rendah' THEN 1 ELSE 0 END) AS rendah
    FROM pelanggan GROUP BY wilayah ORDER BY jml DESC
")->fetchAll();

// Latest optimization runs
$recent_runs = $pdo->query("
    SELECT * FROM optimasi_run ORDER BY created_at DESC LIMIT 5
")->fetchAll();

// Status pelanggan
$status_pie = $pdo->query("
    SELECT status, COUNT(*) AS jml FROM pelanggan GROUP BY status
")->fetchAll();

include 'includes/header.php';
?>
<div class="page-header">
    <div class="page-title">
        <h2>Selamat datang kembali, <?= e($user['nama']) ?>!</h2>
        <p>Ringkasan kinerja tim sales dan alokasi pelanggan di wilayah Telkom Madiun per <?= format_date('now') ?>.</p>
    </div>
    <div class="header-actions">
        <a href="peta_potensi.php" class="btn btn-outline">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/></svg>
            Lihat Peta
        </a>
        <a href="optimasi_ilp.php" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Jalankan Optimasi
        </a>
    </div>
</div>

<!-- Stat cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon red">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </div>
        <div class="stat-label">Total Sales Aktif</div>
        <div class="stat-value"><span data-counter="<?= $total_sales ?>"><?= $total_sales ?></span></div>
        <div class="stat-sub">Dari total <?= $total_sales + 1 ?> sales terdaftar</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
        </div>
        <div class="stat-label">Total Pelanggan</div>
        <div class="stat-value"><span data-counter="<?= $total_pelanggan ?>"><?= $total_pelanggan ?></span></div>
        <div class="stat-sub">Tersebar di <?= $total_wilayah ?> wilayah</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="stat-label">Pelanggan Teralokasi</div>
        <div class="stat-value"><span data-counter="<?= $total_teralokasi ?>"><?= $total_teralokasi ?></span></div>
        <div class="stat-sub"><?= $total_pelanggan > 0 ? round($total_teralokasi / $total_pelanggan * 100, 1) : 0 ?>% dari total pelanggan</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        </div>
        <div class="stat-label">Wilayah Cakupan</div>
        <div class="stat-value"><span data-counter="<?= $total_wilayah ?>"><?= $total_wilayah ?></span></div>
        <div class="stat-sub">Area layanan Telkom Madiun</div>
    </div>
</div>

<!-- Charts -->
<div class="charts-grid">
    <div class="chart-card">
        <div class="chart-title">Alokasi Pelanggan per Sales</div>
        <div class="chart-subtitle">Jumlah pelanggan yang ditangani setiap sales aktif</div>
        <div class="chart-canvas-wrap">
            <canvas id="chartSales"></canvas>
        </div>
    </div>
    <div class="chart-card">
        <div class="chart-title">Distribusi Pelanggan per Wilayah</div>
        <div class="chart-subtitle">Berdasarkan potensi pelanggan</div>
        <div class="chart-canvas-wrap">
            <canvas id="chartWilayah"></canvas>
        </div>
    </div>
</div>

<div class="charts-grid">
    <div class="chart-card">
        <div class="chart-title">Status Pelanggan</div>
        <div class="chart-subtitle">Distribusi seluruh pelanggan berdasarkan status layanan</div>
        <div class="chart-canvas-wrap">
            <canvas id="chartStatus"></canvas>
        </div>
    </div>
    <div class="chart-card">
        <div class="chart-title">Utilisasi Kapasitas Sales</div>
        <div class="chart-subtitle">Persentase kapasitas terpakai vs kapasitas maksimal</div>
        <div style="margin-top: 18px;">
            <?php foreach ($sales_util as $i => $s):
                $util = $s['kapasitas'] > 0 ? ($s['terpakai'] / $s['kapasitas']) * 100 : 0;
                $barColor = $util >= 90 ? 'red' : ($util >= 70 ? 'yellow' : 'green');
            ?>
            <div class="bar-row" style="margin-bottom: 14px;">
                <div class="bar-label"><?= e($s['nama']) ?></div>
                <div class="bar-track">
                    <div class="bar-fill <?= $barColor ?>" style="width: <?= min($util, 100) ?>%"></div>
                </div>
                <div class="bar-value"><?= $s['terpakai'] ?>/<?= $s['kapasitas'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="chart-legend">
            <span class="legend-item"><span class="legend-dot" style="background:#22C55E"></span> &lt; 70%</span>
            <span class="legend-item"><span class="legend-dot" style="background:#F59E0B"></span> 70-90%</span>
            <span class="legend-item"><span class="legend-dot" style="background:#E31E24"></span> &gt;= 90%</span>
        </div>
    </div>
</div>

<!-- Recent runs -->
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Riwayat Optimasi Terbaru</div>
            <div class="card-subtitle">5 run optimasi alokasi pelanggan terakhir</div>
        </div>
        <a href="laporan.php" class="btn btn-outline btn-sm">Lihat Semua</a>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kode Run</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th class="text-right">Teralokasi</th>
                    <th class="text-right">Total Jarak (km)</th>
                    <th class="text-right">Durasi (ms)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_runs)): ?>
                <tr><td colspan="6" style="text-align:center; padding: 32px; color: #9CA3AF;">Belum ada riwayat optimasi. Jalankan optimasi pertama Anda!</td></tr>
                <?php else: foreach ($recent_runs as $r): ?>
                <tr>
                    <td><strong><?= e($r['kode']) ?></strong></td>
                    <td><span class="badge badge-<?= $r['status'] === 'Selesai' ? 'green' : ($r['status'] === 'Berjalan' ? 'yellow' : 'red') ?>"><?= e($r['status']) ?></span></td>
                    <td><?= format_date($r['created_at']) ?></td>
                    <td class="text-right"><?= format_number($r['total_teralokasi']) ?></td>
                    <td class="text-right"><?= format_number($r['total_jarak'], 2) ?></td>
                    <td class="text-right"><?= format_number($r['durasi_ms']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$chartData = [
    'sales' => [
        'labels' => array_column($sales_util, 'nama'),
        'data'   => array_column($sales_util, 'terpakai'),
    ],
    'wilayah' => [
        'labels' => array_column($wilayah_stats, 'wilayah'),
        'tinggi' => array_column($wilayah_stats, 'tinggi'),
        'sedang' => array_column($wilayah_stats, 'sedang'),
        'rendah' => array_column($wilayah_stats, 'rendah'),
    ],
    'status' => [
        'labels' => array_column($status_pie, 'status'),
        'data'   => array_column($status_pie, 'jml'),
    ],
];
$page_js = 'dashboard.js';
include 'includes/footer.php';
?>
<script>
    window.__chartData = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE) ?>;
</script>
