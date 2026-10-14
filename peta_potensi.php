<?php
require_once 'config.php';
require_login();
$pageTitle = 'Peta Potensi Wilayah';
$current_page = 'peta';

$pdo = db();
$sales = $pdo->query("SELECT * FROM sales WHERE status = 'Aktif' ORDER BY nama")->fetchAll();
$pelanggan = $pdo->query("SELECT p.*, s.nama AS sales_nama FROM pelanggan p LEFT JOIN sales s ON s.id = p.sales_id ORDER BY p.id")->fetchAll();

// Wilayah summary
$wilayah_summary = $pdo->query("
    SELECT wilayah, COUNT(*) AS jml,
           AVG(latitude) AS lat, AVG(longitude) AS lng,
           SUM(CASE WHEN potensi='Tinggi' THEN 1 ELSE 0 END) AS tinggi
    FROM pelanggan GROUP BY wilayah
")->fetchAll();

include 'includes/header.php';
?>
<div class="page-header">
    <div class="page-title">
        <h2>Peta Potensi Wilayah Madiun</h2>
        <p>Visualisasi sebaran pelanggan dan lokasi sales di wilayah Telkom Madiun. Klik marker untuk melihat detail.</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-outline" onclick="toggleHeatmap()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg>
            Toggle Heatmap
        </button>
        <button class="btn btn-primary" onclick="resetMapView()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
            Reset View
        </button>
    </div>
</div>

<div class="map-card" style="display:grid;grid-template-columns:1fr 280px;">
    <div class="map-container" id="map"></div>
    <div class="map-sidebar">
        <h4>Filter Tampilan</h4>
        <div class="filter-group">
            <label><input type="checkbox" id="showSales" checked> Tampilkan Sales (<?= count($sales) ?>)</label>
        </div>
        <div class="filter-group">
            <label><input type="checkbox" id="showPelanggan" checked> Tampilkan Pelanggan (<?= count($pelanggan) ?>)</label>
        </div>
        <div class="filter-group">
            <label>Filter Potensi:</label>
            <select class="table-select" id="filterPotensi" style="width:100%">
                <option value="">Semua Potensi</option>
                <option value="Tinggi">Tinggi</option>
                <option value="Sedang">Sedang</option>
                <option value="Rendah">Rendah</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Filter Wilayah:</label>
            <select class="table-select" id="filterWilayah" style="width:100%">
                <option value="">Semua Wilayah</option>
                <?php foreach ($wilayah_summary as $w): ?>
                <option value="<?= e($w['wilayah']) ?>"><?= e($w['wilayah']) ?> (<?= $w['jml'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <h4 style="margin-top:18px">Ringkasan Wilayah</h4>
        <div style="font-size:12px;color:#6B7280;line-height:1.6;">
            <?php foreach ($wilayah_summary as $w): ?>
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #F3F4F6;">
                <span><?= e($w['wilayah']) ?></span>
                <strong><?= $w['jml'] ?> plgn</strong>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="map-legend">
    <span class="legend-item"><span class="legend-circle" style="background:#E31E24"></span> Sales (basis)</span>
    <span class="legend-item"><span class="legend-circle" style="background:#22C55E"></span> Potensi Tinggi</span>
    <span class="legend-item"><span class="legend-circle" style="background:#F59E0B"></span> Potensi Sedang</span>
    <span class="legend-item"><span class="legend-circle" style="background:#9CA3AF"></span> Potensi Rendah</span>
</div>

<?php
$page_js = 'peta_potensi.js';
include 'includes/footer.php';
?>
<script>
    window.__salesData = <?= json_encode($sales, JSON_UNESCAPED_UNICODE) ?>;
    window.__pelangganData = <?= json_encode($pelanggan, JSON_UNESCAPED_UNICODE) ?>;
</script>
