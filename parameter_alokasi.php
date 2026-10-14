<?php
require_once 'config.php';
require_login();
$pageTitle = 'Parameter Alokasi';
$current_page = 'param';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $nama = trim($_POST['nama'] ?? 'Parameter Default');
    $bobot_jarak = max(0, min(100, (float)($_POST['bobot_jarak'] ?? 60)));
    $bobot_potensi = max(0, min(100, (float)($_POST['bobot_potensi'] ?? 40)));
    $maks = max(1, min(50, (int)($_POST['maksimum_kapasitas'] ?? 15)));
    $toleransi = max(0, (float)($_POST['toleransi_jarak'] ?? 30));
    $radius = max(1, (float)($_POST['radius_km'] ?? 25));
    $mode = in_array($_POST['mode_solver'] ?? '', ['Greedy','ILP','Balanced']) ? $_POST['mode_solver'] : 'Greedy';

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE parameter_alokasi SET nama=?, bobot_jarak=?, bobot_potensi=?, maksimum_kapasitas=?, toleransi_jarak=?, radius_km=?, mode_solver=? WHERE id=?");
        $stmt->execute([$nama, $bobot_jarak, $bobot_potensi, $maks, $toleransi, $radius, $mode, $id]);
        flash('success', "Parameter \"$nama\" berhasil diperbarui.");
    } else {
        $stmt = $pdo->prepare("INSERT INTO parameter_alokasi (nama, bobot_jarak, bobot_potensi, maksimum_kapasitas, toleransi_jarak, radius_km, mode_solver) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$nama, $bobot_jarak, $bobot_potensi, $maks, $toleransi, $radius, $mode]);
        flash('success', "Parameter baru \"$nama\" ditambahkan.");
    }
    redirect('parameter_alokasi.php');
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM parameter_alokasi WHERE id = ?")->execute([$id]);
    flash('success', 'Parameter berhasil dihapus.');
    redirect('parameter_alokasi.php');
}

$params = $pdo->query("SELECT * FROM parameter_alokasi ORDER BY id DESC")->fetchAll();

include 'includes/header.php';
?>
<div class="page-header">
    <div class="page-title">
        <h2>Parameter Alokasi</h2>
        <p>Konfigurasi parameter yang digunakan dalam perhitungan jarak Haversine dan algoritma optimasi ILP untuk alokasi pelanggan ke sales.</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="openParamModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Parameter
        </button>
    </div>
</div>

<!-- Formula reference card -->
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Rumus Skor Alokasi</div>
            <div class="card-subtitle">Bobot digunakan untuk menghitung skor kecocokan antara pelanggan dan sales</div>
        </div>
    </div>
    <div class="formula-block">
        <div class="formula-label">Skor Kecocokan (Sales - Pelanggan)</div>
        <div class="formula-text">Skor = (Bobot_Jarak × Normalisasi_Jarak) + (Bobot_Potensi × Skor_Potensi / 100)</div>
    </div>
    <div class="formula-block">
        <div class="formula-label">Normalisasi Jarak</div>
        <div class="formula-text">Normalisasi_Jarak = max(0, 1 - Jarak / Radius_Km)</div>
    </div>
    <div class="formula-block">
        <div class="formula-label">Kapasitas Maksimum per Sales</div>
        <div class="formula-text">Σ pelanggan_per_sales ≤ Maksimum_Kapasitas</div>
    </div>
</div>

<!-- Parameters table -->
<div class="table-card">
    <div class="table-toolbar">
        <div class="toolbar-left">
            <strong>Daftar Parameter Alokasi (<?= count($params) ?>)</strong>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:50px">No</th>
                    <th>Nama Parameter</th>
                    <th class="text-right">Bobot Jarak</th>
                    <th class="text-right">Bobot Potensi</th>
                    <th class="text-right">Maks. Kapasitas</th>
                    <th class="text-right">Radius (km)</th>
                    <th class="text-right">Toleransi (km)</th>
                    <th>Mode Solver</th>
                    <th>Diubah</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($params)): ?>
                <tr><td colspan="10" style="text-align:center;padding:32px;color:#9CA3AF">Belum ada parameter. Buat baru untuk memulai.</td></tr>
                <?php else: $no = 1; foreach ($params as $p): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><strong><?= e($p['nama']) ?></strong></td>
                    <td class="text-right"><?= format_number($p['bobot_jarak'], 0) ?>%</td>
                    <td class="text-right"><?= format_number($p['bobot_potensi'], 0) ?>%</td>
                    <td class="text-right"><?= (int)$p['maksimum_kapasitas'] ?></td>
                    <td class="text-right"><?= format_number($p['radius_km'], 1) ?></td>
                    <td class="text-right"><?= format_number($p['toleransi_jarak'], 1) ?></td>
                    <td><span class="badge badge-blue"><?= e($p['mode_solver']) ?></span></td>
                    <td><?= format_date($p['updated_at']) ?></td>
                    <td class="text-right">
                        <div class="cell-actions">
                            <button class="act-edit" onclick='openParamModal(<?= json_encode($p, JSON_UNESCAPED_UNICODE|JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Edit</button>
                            <a class="act-delete" href="?delete=<?= $p['id'] ?>" onclick="return confirm('Hapus parameter ini?')">Hapus</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function openParamModal(p) {
    const param = (typeof p === 'object' && p.id) ? p : null;
    openModal(`
        <div class="modal-overlay" style="display:flex">
            <div class="modal modal-lg">
                <div class="modal-header">
                    <h3>${param ? 'Edit Parameter' : 'Tambah Parameter Alokasi'}</h3>
                    <button class="modal-close" data-close-modal>&times;</button>
                </div>
                <div class="modal-body">
                    <form id="paramForm" method="POST" action="parameter_alokasi.php">
                        <input type="hidden" name="id" value="${param ? param.id : ''}">
                        <div class="form-group">
                            <label>Nama Parameter <span style="color:#E31E24">*</span></label>
                            <input type="text" class="form-control" name="nama" value="${param ? escapeHtml(param.nama) : ''}" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Bobot Jarak (%) <span style="color:#E31E24">*</span></label>
                                <input type="number" class="form-control" name="bobot_jarak" min="0" max="100" step="5" value="${param ? param.bobot_jarak : 60}" required>
                                <small style="font-size:11px;color:#9CA3AF">0-100, semakin tinggi semakin prioritas jarak dekat</small>
                            </div>
                            <div class="form-group">
                                <label>Bobot Potensi (%) <span style="color:#E31E24">*</span></label>
                                <input type="number" class="form-control" name="bobot_potensi" min="0" max="100" step="5" value="${param ? param.bobot_potensi : 40}" required>
                                <small style="font-size:11px;color:#9CA3AF">0-100, semakin tinggi semakin prioritas potensi tinggi</small>
                            </div>
                        </div>
                        <div class="form-row-3">
                            <div class="form-group">
                                <label>Maks. Kapasitas <span style="color:#E31E24">*</span></label>
                                <input type="number" class="form-control" name="maksimum_kapasitas" min="1" max="50" value="${param ? param.maksimum_kapasitas : 15}" required>
                            </div>
                            <div class="form-group">
                                <label>Radius (km) <span style="color:#E31E24">*</span></label>
                                <input type="number" class="form-control" name="radius_km" min="1" max="100" step="1" value="${param ? param.radius_km : 25}" required>
                            </div>
                            <div class="form-group">
                                <label>Toleransi (km)</label>
                                <input type="number" class="form-control" name="toleransi_jarak" min="0" max="100" step="1" value="${param ? param.toleransi_jarak : 30}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Mode Solver</label>
                            <select class="form-control" name="mode_solver">
                                <option value="Greedy" ${param && param.mode_solver==='Greedy'?'selected':''}>Greedy (cepat, optimal lokal)</option>
                                <option value="ILP" ${param && param.mode_solver==='ILP'?'selected':''}>ILP - Integer Linear Programming</option>
                                <option value="Balanced" ${param && param.mode_solver==='Balanced'?'selected':''}>Balanced (seimbang)</option>
                            </select>
                        </div>
                        <div style="background:#FEF2F2;padding:12px;border-radius:8px;font-size:12px;color:#7F1D1D;margin-top:8px;">
                            <strong>Catatan:</strong> Total bobot jarak + bobot potensi idealnya = 100. Saat ini: <span id="totalBobot">100</span>%
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline" data-close-modal>Batal</button>
                    <button class="btn btn-primary" onclick="document.getElementById('paramForm').submit()">${param ? 'Simpan Perubahan' : 'Simpan Parameter'}</button>
                </div>
            </div>
        </div>
    `);
    // Auto update total bobot
    setTimeout(() => {
        const j = document.querySelector('[name="bobot_jarak"]');
        const p = document.querySelector('[name="bobot_potensi"]');
        const t = document.getElementById('totalBobot');
        const upd = () => { t.textContent = (Number(j.value)||0) + (Number(p.value)||0); t.style.color = Number(t.textContent) === 100 ? '#15803D' : '#B91C1C'; };
        j.addEventListener('input', upd); p.addEventListener('input', upd); upd();
    }, 50);
}
</script>
<?php include 'includes/footer.php'; ?>
