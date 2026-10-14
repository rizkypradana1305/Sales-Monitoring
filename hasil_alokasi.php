<?php
require_once 'config.php';
require_login();
$pageTitle = 'Hasil Alokasi';
$current_page = 'hasil';

$pdo = db();

$latest_run = $pdo->query("SELECT * FROM optimasi_run ORDER BY created_at DESC LIMIT 1")->fetch();

$results = [];
if ($latest_run) {
    $stmt = $pdo->prepare("
        SELECT ha.*, p.nama AS pelanggan_nama, p.alamat, p.wilayah, p.potensi, p.skor_potensi, p.produk,
               s.nama AS sales_nama, s.wilayah AS sales_wilayah
        FROM hasil_alokasi ha
        JOIN pelanggan p ON p.id = ha.pelanggan_id
        JOIN sales s ON s.id = ha.sales_id
        WHERE ha.run_id = ?
        ORDER BY s.nama, ha.jarak_km
    ");
    $stmt->execute([$latest_run['id']]);
    $results = $stmt->fetchAll();
}

// Group by sales
$by_sales = [];
foreach ($results as $r) {
    $by_sales[$r['sales_nama']][] = $r;
}

// Stats
$total_alokasi = count($results);
$total_jarak = array_sum(array_map(fn($r) => (float)$r['jarak_km'], $results));
$avg_jarak = $total_alokasi > 0 ? $total_jarak / $total_alokasi : 0;

include 'includes/header.php';
?>
<div class="page-header">
    <div class="page-title">
        <h2>Hasil Alokasi Pelanggan</h2>
        <p>Hasil akhir optimasi alokasi pelanggan ke tim sales berdasarkan jarak Haversine dan kapasitas sales.</p>
    </div>
    <div class="header-actions">
        <a href="optimasi_ilp.php" class="btn btn-outline">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
            Jalankan Optimasi Baru
        </a>
        <button class="btn btn-primary" onclick="exportHasil()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/></svg>
            Ekspor Hasil
        </button>
    </div>
</div>

<?php if ($latest_run): ?>
<div class="alert alert-info">
    <strong>Run Aktif:</strong> <?= e($latest_run['kode']) ?> · <?= format_date($latest_run['created_at']) ?> · <?= e($latest_run['status']) ?> · Durasi <?= (int)$latest_run['durasi_ms'] ?> ms
</div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon red"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
        <div class="stat-label">Pelanggan Teralokasi</div>
        <div class="stat-value"><?= format_number($total_alokasi) ?></div>
        <div class="stat-sub">Total assignment</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/></svg></div>
        <div class="stat-label">Total Jarak</div>
        <div class="stat-value"><?= format_number($total_jarak, 2) ?></div>
        <div class="stat-sub">km gabungan</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
        <div class="stat-label">Rata-rata Jarak</div>
        <div class="stat-value"><?= format_number($avg_jarak, 2) ?></div>
        <div class="stat-sub">km per pelanggan</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
        <div class="stat-label">Sales Aktif</div>
        <div class="stat-value"><?= format_number(count($by_sales)) ?></div>
        <div class="stat-sub">Menerima pelanggan</div>
    </div>
</div>

<?php if (empty($results)): ?>
<div class="card">
    <div class="empty-state">
        <div class="empty-icon">📋</div>
        <p style="font-size:16px;font-weight:600;margin-bottom:6px;">Belum ada hasil alokasi</p>
        <p style="margin-bottom:16px;">Jalankan optimasi ILP terlebih dahulu untuk menghasilkan alokasi pelanggan.</p>
        <a href="optimasi_ilp.php" class="btn btn-primary">Jalankan Optimasi</a>
    </div>
</div>
<?php else: ?>

<!-- Summary chart -->
<div class="charts-grid">
    <div class="chart-card">
        <div class="chart-title">Distribusi Pelanggan per Sales</div>
        <div class="chart-subtitle">Berdasarkan hasil alokasi terbaru</div>
        <div class="chart-canvas-wrap">
            <canvas id="chartDistribusi"></canvas>
        </div>
    </div>
    <div class="chart-card">
        <div class="chart-title">Total Jarak per Sales</div>
        <div class="chart-subtitle">Akumulasi jarak tempuh (km)</div>
        <div class="chart-canvas-wrap">
            <canvas id="chartJarak"></canvas>
        </div>
    </div>
</div>

<!-- Detail per sales -->
<?php foreach ($by_sales as $sales_nama => $custs):
    $total_km = array_sum(array_map(fn($r) => (float)$r['jarak_km'], $custs));
    $avg_skor = array_sum(array_map(fn($r) => (int)$r['skor_potensi'], $custs)) / count($custs);
?>
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title"><?= e($sales_nama) ?></div>
            <div class="card-subtitle"><?= count($custs) ?> pelanggan · Total jarak <?= format_number($total_km, 2) ?> km · Rata-rata skor potensi <?= format_number($avg_skor, 1) ?></div>
        </div>
        <a href="detail_sales.php?id=<?= $custs[0]['sales_id'] ?>" class="btn btn-outline btn-sm">Lihat Detail Sales</a>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:50px">No</th>
                    <th>Pelanggan</th>
                    <th>Alamat</th>
                    <th>Potensi</th>
                    <th class="text-right">Jarak (km)</th>
                    <th class="text-right">Skor</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($custs as $r): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><strong><?= e($r['pelanggan_nama']) ?></strong></td>
                    <td style="max-width:280px"><?= e($r['alamat']) ?></td>
                    <td>
                        <?php
                            $badge = $r['potensi']==='Tinggi'?'badge-red':($r['potensi']==='Sedang'?'badge-yellow':'badge-gray');
                        ?>
                        <span class="badge <?= $badge ?>"><?= e($r['potensi']) ?></span>
                    </td>
                    <td class="text-right"><?= format_number($r['jarak_km'], 2) ?></td>
                    <td class="text-right"><?= (int)$r['skor_potensi'] ?></td>
                    <td><span class="badge badge-green"><?= e($r['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; endif; ?>

<?php
$page_js = 'hasil_alokasi.js';
$chart_data = [
    'sales_labels' => array_keys($by_sales),
    'pelanggan_counts' => array_map(fn($c) => count($c), array_values($by_sales)),
    'jarak_totals' => array_map(fn($c) => round(array_sum(array_map(fn($r) => (float)$r['jarak_km'], $c)), 2), array_values($by_sales)),
];
include 'includes/footer.php';
?>
<script>window.__hasilData = <?= json_encode($chart_data, JSON_UNESCAPED_UNICODE) ?>;</script>
