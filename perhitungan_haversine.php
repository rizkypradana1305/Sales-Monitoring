<?php
require_once 'config.php';
require_login();
$pageTitle = 'Perhitungan Haversine';
$current_page = 'haversine';

$pdo = db();

// Get latest run
$run = $pdo->query("SELECT * FROM optimasi_run ORDER BY created_at DESC LIMIT 1")->fetch();
$jarak_data = [];
$matrix = [];
$pelanggan_list = [];
$sales_list = [];

if ($run) {
    $pelanggan_list = $pdo->query("SELECT id, nama, wilayah, latitude, longitude FROM pelanggan ORDER BY id")->fetchAll();
    $sales_list = $pdo->query("SELECT id, nama, wilayah, latitude, longitude FROM sales WHERE status='Aktif' ORDER BY id")->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM hasil_haversine WHERE run_id = ? ORDER BY pelanggan_id, jarak_km");
    $stmt->execute([$run['id']]);
    $jarak_data = $stmt->fetchAll();
    // Build matrix
    foreach ($jarak_data as $r) {
        $matrix[$r['pelanggan_id']][$r['sales_id']] = (float)$r['jarak_km'];
    }
} else {
    // Calculate fresh if no run
    $pelanggan_list = $pdo->query("SELECT id, nama, wilayah, latitude, longitude FROM pelanggan ORDER BY id LIMIT 10")->fetchAll();
    $sales_list = $pdo->query("SELECT id, nama, wilayah, latitude, longitude FROM sales WHERE status='Aktif' ORDER BY id")->fetchAll();
    foreach ($pelanggan_list as $p) {
        foreach ($sales_list as $s) {
            $km = haversine($p['latitude'], $p['longitude'], $s['latitude'], $s['longitude']);
            $matrix[$p['id']][$s['id']] = round($km, 3);
        }
    }
}

// Stats
$total_pairs = count($jarak_data);
$avg_jarak = 0;
$min_jarak = INF; $max_jarak = 0;
if ($total_pairs > 0) {
    foreach ($jarak_data as $r) {
        $avg_jarak += (float)$r['jarak_km'];
        if ((float)$r['jarak_km'] < $min_jarak) $min_jarak = (float)$r['jarak_km'];
        if ((float)$r['jarak_km'] > $max_jarak) $max_jarak = (float)$r['jarak_km'];
    }
    $avg_jarak = $avg_jarak / $total_pairs;
}

include 'includes/header.php';
?>
<div class="page-header">
    <div class="page-title">
        <h2>Perhitungan Jarak Haversine</h2>
        <p>Menghitung jarak spherical antara koordinat pelanggan dan sales menggunakan formula Haversine untuk menentukan kedekatan spasial.</p>
    </div>
    <div class="header-actions">
        <a href="optimasi_ilp.php" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Jalankan Optimasi Baru
        </a>
    </div>
</div>

<!-- Stat cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon red"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/></svg></div>
        <div class="stat-label">Total Pasangan</div>
        <div class="stat-value"><?= format_number($total_pairs) ?></div>
        <div class="stat-sub">Pelanggan × Sales</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg></div>
        <div class="stat-label">Jarak Minimum</div>
        <div class="stat-value"><?= format_number($min_jarak === INF ? 0 : $min_jarak, 2) ?></div>
        <div class="stat-sub">Kilometer</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg></div>
        <div class="stat-label">Jarak Rata-rata</div>
        <div class="stat-value"><?= format_number($avg_jarak, 2) ?></div>
        <div class="stat-sub">Kilometer</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg></div>
        <div class="stat-label">Jarak Maksimum</div>
        <div class="stat-value"><?= format_number($max_jarak, 2) ?></div>
        <div class="stat-sub">Kilometer</div>
    </div>
</div>

<!-- Formula explanation -->
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Formula Haversine</div>
            <div class="card-subtitle">Menghitung jarak spherical antara dua titik di permukaan bumi</div>
        </div>
    </div>
    <div class="formula-block">
        <div class="formula-label">Rumus Haversine</div>
        <div class="formula-text">d = 2R · arcsin(√(sin²(Δlat/2) + cos(lat₁)·cos(lat₂)·sin²(Δlon/2)))</div>
    </div>
    <div class="formula-block">
        <div class="formula-label">Keterangan</div>
        <div class="formula-text" style="font-weight:400;font-size:13px;line-height:1.7;">
            • R = 6.371 km (jari-jari bumi)<br>
            • Δlat = lat₂ − lat₁ (dalam radian)<br>
            • Δlon = lon₂ − lon₁ (dalam radian)<br>
            • Hasil: jarak spherical terpendek antara dua koordinat
        </div>
    </div>
</div>

<!-- Distance Matrix -->
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Matriks Jarak Pelanggan × Sales</div>
            <div class="card-subtitle">Sel hijau = jarak terdekat, sel merah = jarak terjauh (per baris pelanggan)</div>
        </div>
    </div>
    <div class="table-wrap">
        <table class="matrix-table">
            <thead>
                <tr>
                    <th style="text-align:left;padding-left:16px;">Pelanggan</th>
                    <?php foreach ($sales_list as $s): ?>
                        <th title="<?= e($s['nama']) ?>"><?= e(initials($s['nama'])) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pelanggan_list as $p): 
                    if (!isset($matrix[$p['id']])) continue;
                    $row = $matrix[$p['id']];
                    $min_val = min($row);
                    $max_val = max($row);
                ?>
                <tr>
                    <td style="text-align:left;padding-left:16px;">
                        <div style="font-weight:600;font-size:12px;"><?= e($p['nama']) ?></div>
                        <div style="font-size:10px;color:#9CA3AF;"><?= e($p['wilayah']) ?></div>
                    </td>
                    <?php foreach ($sales_list as $s): 
                        $v = $row[$s['id']] ?? null;
                        $cls = '';
                        if ($v !== null) {
                            if (abs($v - $min_val) < 0.001) $cls = 'min-cell';
                            elseif (abs($v - $max_val) < 0.001) $cls = 'max-cell';
                        }
                    ?>
                    <td class="<?= $cls ?>" title="<?= e($p['nama']) ?> → <?= e($s['nama']) ?>: <?= $v ? number_format($v, 2, ',', '.') . ' km' : '-' ?>">
                        <?= $v !== null ? number_format($v, 2, ',', '.') : '-' ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (count($pelanggan_list) > 15): ?>
    <div style="padding: 12px 16px; background:#F9FAFB; font-size:12px; color:#6B7280; border-top:1px solid #E5E7EB;">
        Menampilkan 15 baris pertama dari <?= count($pelanggan_list) ?> pelanggan. Jalankan optimasi baru untuk melihat semua.
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
