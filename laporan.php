<?php
require_once 'config.php';
require_login();
$pageTitle = 'Laporan';
$current_page = 'laporan';

$pdo = db();

// Overall stats
$stats = [
    'total_sales' => (int)$pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn(),
    'total_pelanggan' => (int)$pdo->query("SELECT COUNT(*) FROM pelanggan")->fetchColumn(),
    'total_teralokasi' => (int)$pdo->query("SELECT COUNT(*) FROM pelanggan WHERE sales_id IS NOT NULL")->fetchColumn(),
    'total_optimasi' => (int)$pdo->query("SELECT COUNT(*) FROM optimasi_run")->fetchColumn(),
    'total_jarak' => (float)$pdo->query("SELECT COALESCE(SUM(total_jarak), 0) FROM optimasi_run")->fetchColumn(),
];

// Sales summary per sales
$sales_summary = $pdo->query("
    SELECT s.id, s.nama, s.wilayah, s.kapasitas, s.status,
           (SELECT COUNT(*) FROM pelanggan p WHERE p.sales_id = s.id) AS jml_pelanggan
    FROM sales s ORDER BY jml_pelanggan DESC
")->fetchAll();

// Latest run
$latest_run = $pdo->query("SELECT * FROM optimasi_run ORDER BY created_at DESC LIMIT 1")->fetch();

// Recent runs
$runs = $pdo->query("
    SELECT r.*, p.nama AS param_nama
    FROM optimasi_run r LEFT JOIN parameter_alokasi p ON p.id = r.parameter_id
    ORDER BY r.created_at DESC LIMIT 20
")->fetchAll();

include 'includes/header.php';
?>
<div class="page-header">
    <div class="page-title">
        <h2>Laporan Alokasi Sales</h2>
        <p>Ringkasan kinerja tim sales, alokasi pelanggan, dan riwayat optimasi Telkom Madiun.</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-outline" onclick="window.print()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Cetak Laporan
        </button>
        <button class="btn btn-primary" onclick="exportLaporan()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/></svg>
            Ekspor Laporan
        </button>
    </div>
</div>

<!-- Overview stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon red"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
        <div class="stat-label">Total Sales</div>
        <div class="stat-value"><?= $stats['total_sales'] ?></div>
        <div class="stat-sub">Sales terdaftar</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
        <div class="stat-label">Total Pelanggan</div>
        <div class="stat-value"><?= $stats['total_pelanggan'] ?></div>
        <div class="stat-sub">Pelanggan terdata</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
        <div class="stat-label">Teralokasi</div>
        <div class="stat-value"><?= $stats['total_teralokasi'] ?></div>
        <div class="stat-sub"><?= $stats['total_pelanggan'] > 0 ? round($stats['total_teralokasi'] / $stats['total_pelanggan'] * 100, 1) : 0 ?>% dari total</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14"/></svg></div>
        <div class="stat-label">Run Optimasi</div>
        <div class="stat-value"><?= $stats['total_optimasi'] ?></div>
        <div class="stat-sub">Total jarak <?= format_number($stats['total_jarak'], 2) ?> km</div>
    </div>
</div>

<?php if ($latest_run): ?>
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Status Optimasi Terakhir</div>
            <div class="card-subtitle">Run: <?= e($latest_run['kode']) ?> · <?= format_date($latest_run['created_at']) ?></div>
        </div>
        <span class="badge badge-<?= $latest_run['status']==='Selesai'?'green':($latest_run['status']==='Berjalan'?'yellow':'red') ?>"><?= e($latest_run['status']) ?></span>
    </div>
    <div class="kpi-row">
        <div class="kpi-box">
            <div class="kpi-label">Pelanggan Teralokasi</div>
            <div class="kpi-value"><?= (int)$latest_run['total_teralokasi'] ?>/<?= (int)$latest_run['total_pelanggan'] ?></div>
        </div>
        <div class="kpi-box">
            <div class="kpi-label">Total Jarak (km)</div>
            <div class="kpi-value"><?= format_number($latest_run['total_jarak'], 2) ?></div>
        </div>
        <div class="kpi-box">
            <div class="kpi-label">Durasi (ms)</div>
            <div class="kpi-value"><?= (int)$latest_run['durasi_ms'] ?></div>
        </div>
        <div class="kpi-box">
            <div class="kpi-label">Catatan</div>
            <div class="kpi-value" style="font-size:12px;text-align:left;line-height:1.4;"><?= e(substr($latest_run['catatan'] ?? '-', 0, 60)) ?></div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="charts-grid">
    <div class="chart-card">
        <div class="chart-title">Alokasi Pelanggan per Sales</div>
        <div class="chart-subtitle">Jumlah pelanggan yang ditangani</div>
        <div class="chart-canvas-wrap">
            <canvas id="chartSalesAlloc"></canvas>
        </div>
    </div>
    <div class="chart-card">
        <div class="chart-title">Tren Total Jarak per Run</div>
        <div class="chart-subtitle">20 run optimasi terakhir</div>
        <div class="chart-canvas-wrap">
            <canvas id="chartTrenJarak"></canvas>
        </div>
    </div>
</div>

<!-- Sales summary table -->
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Ringkasan Performa Sales</div>
            <div class="card-subtitle">Per sales, jumlah pelanggan dan utilisasi kapasitas</div>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:50px">No</th>
                    <th>Nama Sales</th>
                    <th>Wilayah</th>
                    <th class="text-right">Kapasitas</th>
                    <th class="text-right">Pelanggan</th>
                    <th>Utilisasi</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; foreach ($sales_summary as $s):
                    $util = $s['kapasitas'] > 0 ? min(100, $s['jml_pelanggan'] / $s['kapasitas'] * 100) : 0;
                    $barColor = $util >= 90 ? '' : ($util >= 70 ? 'green' : 'blue');
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><strong><?= e($s['nama']) ?></strong></td>
                    <td><?= e($s['wilayah']) ?></td>
                    <td class="text-right"><?= (int)$s['kapasitas'] ?></td>
                    <td class="text-right"><?= (int)$s['jml_pelanggan'] ?></td>
                    <td style="min-width:160px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div class="progress-bar <?= $barColor ?>" style="flex:1;">
                                <div class="progress-fill" style="width: <?= $util ?>%"></div>
                            </div>
                            <span style="font-size:12px;font-weight:600;min-width:40px;"><?= format_number($util, 0) ?>%</span>
                        </div>
                    </td>
                    <td>
                        <?php if ($s['status']==='Aktif'): ?><span class="badge badge-green">Aktif</span>
                        <?php elseif ($s['status']==='Cuti'): ?><span class="badge badge-yellow">Cuti</span>
                        <?php else: ?><span class="badge badge-gray">Nonaktif</span><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Run history table -->
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Riwayat Run Optimasi</div>
            <div class="card-subtitle">20 run terakhir</div>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Parameter</th>
                    <th>Status</th>
                    <th class="text-right">Teralokasi</th>
                    <th class="text-right">Jarak (km)</th>
                    <th class="text-right">Durasi</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($runs)): ?>
                <tr><td colspan="7" style="text-align:center;padding:24px;color:#9CA3AF;">Belum ada riwayat.</td></tr>
                <?php else: foreach ($runs as $r): ?>
                <tr>
                    <td><strong><?= e($r['kode']) ?></strong></td>
                    <td><?= e($r['param_nama'] ?: '-') ?></td>
                    <td><span class="badge badge-<?= $r['status']==='Selesai'?'green':($r['status']==='Berjalan'?'yellow':'red') ?>"><?= e($r['status']) ?></span></td>
                    <td class="text-right"><?= (int)$r['total_teralokasi'] ?>/<?= (int)$r['total_pelanggan'] ?></td>
                    <td class="text-right"><?= format_number($r['total_jarak'], 2) ?></td>
                    <td class="text-right"><?= (int)$r['durasi_ms'] ?> ms</td>
                    <td><?= format_date($r['created_at']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$page_js = 'laporan.js';
$chart_data = [
    'sales_labels' => array_column($sales_summary, 'nama'),
    'sales_jml' => array_column($sales_summary, 'jml_pelanggan'),
    'run_labels' => array_map(fn($r) => substr($r['kode'], 0, 13), array_slice($runs, 0, 20)),
    'run_jarak' => array_map(fn($r) => (float)$r['total_jarak'], array_slice($runs, 0, 20)),
];
include 'includes/footer.php';
?>
<script>window.__laporanData = <?= json_encode($chart_data, JSON_UNESCAPED_UNICODE) ?>;</script>
