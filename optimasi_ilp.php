<?php
require_once 'config.php';
require_login();
$pageTitle = 'Optimasi ILP';
$current_page = 'ilp';

$pdo = db();

// Handle run request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_optimization'])) {
    $param_id = (int)$_POST['parameter_id'];
    try {
        $result = run_optimization($param_id);
        flash('success', "Optimasi berhasil! Teralokasi: {$result['total_alokasi']} pelanggan, Total jarak: {$result['total_jarak']} km" . ($result['unassigned'] > 0 ? ", Tidak teralokasi: {$result['unassigned']}" : ''));
    } catch (Exception $e) {
        flash('error', 'Optimasi gagal: ' . $e->getMessage());
    }
    redirect('optimasi_ilp.php');
}

$param_list = $pdo->query("SELECT * FROM parameter_alokasi ORDER BY id DESC")->fetchAll();
$latest_run = $pdo->query("
    SELECT r.*, p.nama AS param_nama
    FROM optimasi_run r
    LEFT JOIN parameter_alokasi p ON p.id = r.parameter_id
    ORDER BY r.created_at DESC LIMIT 1
")->fetch();

$all_runs = $pdo->query("
    SELECT r.*, p.nama AS param_nama
    FROM optimasi_run r
    LEFT JOIN parameter_alokasi p ON p.id = r.parameter_id
    ORDER BY r.created_at DESC LIMIT 10
")->fetchAll();

include 'includes/header.php';
?>
<div class="page-header">
    <div class="page-title">
        <h2>Optimasi Integer Linear Programming</h2>
        <p>Konfigurasi dan jalankan algoritma optimasi ILP untuk mengalokasikan pelanggan ke sales berdasarkan jarak Haversine, potensi, dan kapasitas.</p>
    </div>
</div>

<div class="charts-grid">
    <!-- Solver configuration -->
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Konfigurasi Solver</div>
                <div class="card-subtitle">Pilih parameter dan jalankan optimasi</div>
            </div>
        </div>
        <form method="POST" action="">
            <div class="form-group">
                <label>Parameter Alokasi</label>
                <select class="form-control" name="parameter_id" required>
                    <option value="">- Pilih Parameter -</option>
                    <?php foreach ($param_list as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= e($p['nama']) ?> (Jarak:<?= (int)$p['bobot_jarak'] ?>%, Potensi:<?= (int)$p['bobot_potensi'] ?>%, Mode:<?= e($p['mode_solver']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($latest_run): ?>
            <div style="background:#F0FDF4;padding:14px;border-radius:8px;margin-bottom:14px;border:1px solid #BBF7D0;">
                <div style="font-size:12px;color:#15803D;font-weight:600;margin-bottom:6px;">✓ Run Terakhir: <?= e($latest_run['kode']) ?></div>
                <div style="font-size:13px;color:#1F2937;">
                    Status: <strong><?= e($latest_run['status']) ?></strong> · 
                    Teralokasi: <strong><?= (int)$latest_run['total_teralokasi'] ?>/<?= (int)$latest_run['total_pelanggan'] ?></strong> · 
                    Jarak: <strong><?= format_number($latest_run['total_jarak'], 2) ?> km</strong> · 
                    Durasi: <strong><?= (int)$latest_run['durasi_ms'] ?> ms</strong>
                </div>
            </div>
            <?php endif; ?>
            <button type="submit" name="run_optimization" value="1" class="btn btn-primary btn-block btn-lg" onclick="return confirm('Jalankan optimasi? Ini akan mengalokasikan ulang seluruh pelanggan.')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Jalankan Optimasi ILP
            </button>
        </form>
    </div>

    <!-- Status -->
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Status Solver</div>
                <div class="card-subtitle">Informasi terkini tentang engine optimasi</div>
            </div>
        </div>
        <?php if ($latest_run): ?>
        <div style="margin-bottom: 16px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                <span style="font-size:13px;color:#6B7280;">Progress Alokasi</span>
                <span style="font-size:13px;font-weight:600;"><?= (int)$latest_run['total_teralokasi'] ?>/<?= (int)$latest_run['total_pelanggan'] ?></span>
            </div>
            <div class="progress-bar <?= $latest_run['total_teralokasi'] == $latest_run['total_pelanggan'] ? 'green' : '' ?>">
                <div class="progress-fill" style="width: <?= $latest_run['total_pelanggan'] > 0 ? ($latest_run['total_teralokasi'] / $latest_run['total_pelanggan'] * 100) : 0 ?>%"></div>
            </div>
        </div>
        <div class="kpi-row" style="grid-template-columns:1fr 1fr;">
            <div class="kpi-box">
                <div class="kpi-label">Total Jarak</div>
                <div class="kpi-value"><?= format_number($latest_run['total_jarak'], 2) ?> km</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-label">Durasi</div>
                <div class="kpi-value"><?= (int)$latest_run['durasi_ms'] ?> ms</div>
            </div>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">⚙</div>
            <p>Belum ada optimasi dijalankan.<br>Pilih parameter lalu klik "Jalankan Optimasi ILP".</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Mathematical model -->
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Model Matematis ILP</div>
            <div class="card-subtitle">Integer Linear Programming formulation untuk alokasi optimal</div>
        </div>
    </div>

    <div class="formula-block">
        <div class="formula-label">Fungsi Objektif (Maksimisasi Skor)</div>
        <div class="formula-text">max ΣΣ x<sub>ij</sub> · (w₁ · (1 - d<sub>ij</sub>/R) + w₂ · p<sub>i</sub>/100)</div>
    </div>
    <div class="formula-block">
        <div class="formula-label">Kendala Kapasitas Sales</div>
        <div class="formula-text">Σ<sub>i</sub> x<sub>ij</sub> ≤ K<sub>j</sub>     ∀j ∈ Sales</div>
    </div>
    <div class="formula-block">
        <div class="formula-label">Kendala Unik (1 pelanggan = 1 sales)</div>
        <div class="formula-text">Σ<sub>j</sub> x<sub>ij</sub> = 1     ∀i ∈ Pelanggan</div>
    </div>
    <div class="formula-block">
        <div class="formula-label">Kendala Biner</div>
        <div class="formula-text">x<sub>ij</sub> ∈ {0, 1}</div>
    </div>
    <div class="formula-block">
        <div class="formula-label">Notasi</div>
        <div class="formula-text" style="font-weight:400;font-size:13px;line-height:1.7;">
            • x<sub>ij</sub> = 1 jika pelanggan i dialokasikan ke sales j<br>
            • d<sub>ij</sub> = jarak Haversine pelanggan i ke sales j<br>
            • p<sub>i</sub> = skor potensi pelanggan i (0-100)<br>
            • K<sub>j</sub> = kapasitas maksimum sales j<br>
            • w₁, w₂ = bobot jarak dan bobot potensi<br>
            • R = radius maksimum (km)
        </div>
    </div>
</div>

<!-- History -->
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Riwayat Optimasi</div>
            <div class="card-subtitle">10 run optimasi terakhir</div>
        </div>
        <a href="hasil_alokasi.php" class="btn btn-outline btn-sm">Lihat Hasil</a>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Parameter</th>
                    <th>Status</th>
                    <th class="text-right">Teralokasi</th>
                    <th class="text-right">Total Jarak</th>
                    <th class="text-right">Durasi</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($all_runs)): ?>
                <tr><td colspan="7" style="text-align:center;padding:32px;color:#9CA3AF;">Belum ada riwayat optimasi.</td></tr>
                <?php else: foreach ($all_runs as $r): ?>
                <tr>
                    <td><strong><?= e($r['kode']) ?></strong></td>
                    <td><?= e($r['param_nama'] ?: '-') ?></td>
                    <td><span class="badge badge-<?= $r['status']==='Selesai'?'green':($r['status']==='Berjalan'?'yellow':'red') ?>"><?= e($r['status']) ?></span></td>
                    <td class="text-right"><?= (int)$r['total_teralokasi'] ?>/<?= (int)$r['total_pelanggan'] ?></td>
                    <td class="text-right"><?= format_number($r['total_jarak'], 2) ?> km</td>
                    <td class="text-right"><?= (int)$r['durasi_ms'] ?> ms</td>
                    <td><?= format_date($r['created_at']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
