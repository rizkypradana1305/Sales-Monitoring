<?php
require_once 'config.php';
require_login();
require_role(['Administrator']);
$pageTitle = 'Pengaturan';
$current_page = 'settings';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'] ?? 'general';

    if ($section === 'general') {
        set_setting('app_name', trim($_POST['app_name'] ?? 'Alokasi Sales Telkom Madiun'));
        set_setting('app_version', trim($_POST['app_version'] ?? '2.6.0'));
        set_setting('primary_color', trim($_POST['primary_color'] ?? '#E31E24'));
        set_setting('map_default_lat', trim($_POST['map_default_lat'] ?? '-7.6298'));
        set_setting('map_default_lng', trim($_POST['map_default_lng'] ?? '111.5239'));
        set_setting('map_default_zoom', trim($_POST['map_default_zoom'] ?? '13'));
        set_setting('map_provider', trim($_POST['map_provider'] ?? 'OpenStreetMap'));
        flash('success', 'Pengaturan umum berhasil disimpan.');
    } elseif ($section === 'email') {
        set_setting('mail_host', trim($_POST['mail_host'] ?? ''));
        set_setting('mail_port', trim($_POST['mail_port'] ?? '587'));
        set_setting('mail_username', trim($_POST['mail_username'] ?? ''));
        if (!empty($_POST['mail_password'])) {
            set_setting('mail_password', $_POST['mail_password']);
        }
        flash('success', 'Pengaturan email berhasil disimpan.');
    } elseif ($section === 'system') {
        set_setting('notification_enabled', isset($_POST['notification_enabled']) ? '1' : '0');
        set_setting('auto_allocate_on_create', isset($_POST['auto_allocate_on_create']) ? '1' : '0');
        flash('success', 'Pengaturan sistem berhasil disimpan.');
    }
    redirect('pengaturan.php');
}

$settings = [];
foreach ($pdo->query("SELECT `key`, value FROM pengaturan")->fetchAll() as $r) {
    $settings[$r['key']] = $r['value'];
}

include 'includes/header.php';
?>
<div class="page-header">
    <div class="page-title">
        <h2>Pengaturan Sistem</h2>
        <p>Konfigurasi aplikasi, integrasi email, dan preferensi sistem alokasi sales Telkom Madiun.</p>
    </div>
</div>

<!-- Tabs -->
<div style="display:flex;gap:8px;margin-bottom:20px;border-bottom:1px solid #E5E7EB;">
    <button class="tab-btn active" data-tab="general" onclick="switchTab('general')">Umum</button>
    <button class="tab-btn" data-tab="email" onclick="switchTab('email')">Email</button>
    <button class="tab-btn" data-tab="system" onclick="switchTab('system')">Sistem</button>
    <button class="tab-btn" data-tab="about" onclick="switchTab('about')">Tentang</button>
</div>

<!-- General -->
<div class="tab-content" id="tab-general">
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Pengaturan Umum</div>
                <div class="card-subtitle">Informasi dasar aplikasi</div>
            </div>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="section" value="general">
            <div class="form-row">
                <div class="form-group">
                    <label>Nama Aplikasi</label>
                    <input type="text" class="form-control" name="app_name" value="<?= e($settings['app_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Versi Aplikasi</label>
                    <input type="text" class="form-control" name="app_version" value="<?= e($settings['app_version'] ?? '2.6.0') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Warna Utama</label>
                    <div style="display:flex;gap:10px;align-items:center;">
                        <input type="color" name="primary_color" value="<?= e($settings['primary_color'] ?? '#E31E24') ?>" style="width:60px;height:40px;border:1px solid #E5E7EB;border-radius:6px;cursor:pointer;">
                        <input type="text" class="form-control" name="primary_color_text" value="<?= e($settings['primary_color'] ?? '#E31E24') ?>" id="colorText" style="flex:1;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Provider Peta</label>
                    <select class="form-control" name="map_provider">
                        <option value="OpenStreetMap" <?= ($settings['map_provider']??'')==='OpenStreetMap'?'selected':'' ?>>OpenStreetMap</option>
                        <option value="Mapbox" <?= ($settings['map_provider']??'')==='Mapbox'?'selected':'' ?>>Mapbox</option>
                        <option value="GoogleMaps" <?= ($settings['map_provider']??'')==='GoogleMaps'?'selected':'' ?>>Google Maps</option>
                    </select>
                </div>
            </div>
            <div class="form-row-3">
                <div class="form-group">
                    <label>Default Latitude</label>
                    <input type="text" class="form-control" name="map_default_lat" value="<?= e($settings['map_default_lat'] ?? '-7.6298') ?>">
                </div>
                <div class="form-group">
                    <label>Default Longitude</label>
                    <input type="text" class="form-control" name="map_default_lng" value="<?= e($settings['map_default_lng'] ?? '111.5239') ?>">
                </div>
                <div class="form-group">
                    <label>Default Zoom</label>
                    <input type="number" class="form-control" name="map_default_zoom" value="<?= e($settings['map_default_zoom'] ?? '13') ?>" min="1" max="19">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
            </div>
        </form>
    </div>
</div>

<!-- Email -->
<div class="tab-content" id="tab-email" style="display:none;">
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Konfigurasi Email SMTP</div>
                <div class="card-subtitle">Digunakan untuk reset password dan notifikasi sistem</div>
            </div>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="section" value="email">
            <div class="form-row">
                <div class="form-group">
                    <label>SMTP Host</label>
                    <input type="text" class="form-control" name="mail_host" value="<?= e($settings['mail_host'] ?? '') ?>" placeholder="smtp.telkom.co.id">
                </div>
                <div class="form-group">
                    <label>SMTP Port</label>
                    <input type="number" class="form-control" name="mail_port" value="<?= e($settings['mail_port'] ?? '587') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Username Email</label>
                    <input type="text" class="form-control" name="mail_username" value="<?= e($settings['mail_username'] ?? '') ?>" placeholder="noreply@telkom.co.id">
                </div>
                <div class="form-group">
                    <label>Password Email <?= isset($settings['mail_password']) ? '(sudah diset)' : '' ?></label>
                    <input type="password" class="form-control" name="mail_password" placeholder="••••••••">
                </div>
            </div>
            <div class="alert alert-info" style="margin-top:10px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                Email digunakan untuk fitur reset password dan notifikasi otomatis.
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Pengaturan Email</button>
            </div>
        </form>
    </div>
</div>

<!-- System -->
<div class="tab-content" id="tab-system" style="display:none;">
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Pengaturan Sistem</div>
                <div class="card-subtitle">Preferensi perilaku sistem</div>
            </div>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="section" value="system">
            <div style="display:flex;flex-direction:column;gap:14px;">
                <label class="setting-toggle">
                    <div>
                        <div style="font-weight:600;font-size:14px;">Notifikasi Aktif</div>
                        <div style="font-size:12px;color:#6B7280;">Kirim notifikasi email untuk aktivitas penting</div>
                    </div>
                    <input type="checkbox" name="notification_enabled" <?= ($settings['notification_enabled']??'0')==='1'?'checked':'' ?>>
                </label>
                <label class="setting-toggle">
                    <div>
                        <div style="font-weight:600;font-size:14px;">Auto-Allocate saat Tambah Pelanggan</div>
                        <div style="font-size:12px;color:#6B7280;">Otomatis menjalankan optimasi lokal saat pelanggan baru ditambahkan</div>
                    </div>
                    <input type="checkbox" name="auto_allocate_on_create" <?= ($settings['auto_allocate_on_create']??'0')==='1'?'checked':'' ?>>
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Pengaturan Sistem</button>
            </div>
        </form>
    </div>
</div>

<!-- About -->
<div class="tab-content" id="tab-about" style="display:none;">
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Tentang Aplikasi</div>
                <div class="card-subtitle">Informasi versi dan pengembang</div>
            </div>
        </div>
        <div style="text-align:center;padding:30px;">
            <div style="width:80px;height:80px;background:#E31E24;color:white;border-radius:20px;display:flex;align-items:center;justify-content:center;font-size:42px;font-weight:800;margin:0 auto 16px;">T</div>
            <h2 style="font-size:22px;font-weight:700;margin-bottom:4px;"><?= e($settings['app_name'] ?? APP_NAME) ?></h2>
            <p style="color:#6B7280;">Versi <?= e($settings['app_version'] ?? APP_VERSION) ?></p>
            <p style="margin-top:20px;color:#6B7280;max-width:560px;margin-left:auto;margin-right:auto;">
                Sistem Alokasi Sales Berdasarkan Potensi Wilayah Madiun. Menggunakan metode Haversine untuk perhitungan jarak dan Integer Linear Programming (ILP) untuk optimasi penugasan pelanggan ke sales dengan memperhatikan kapasitas masing-masing.
            </p>
            <div style="display:flex;justify-content:center;gap:8px;margin-top:24px;flex-wrap:wrap;">
                <span class="badge badge-red">Haversine</span>
                <span class="badge badge-blue">ILP</span>
                <span class="badge badge-green">Kapasitas Sales</span>
                <span class="badge badge-yellow">Madiun</span>
            </div>
            <p style="margin-top:24px;font-size:12px;color:#9CA3AF;">© 2026 Telkom Madiun. Seluruh hak dilindungi.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Tim Pengembang</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;font-size:13px;">
            <div style="padding:14px;border:1px solid #E5E7EB;border-radius:8px;">
                <strong>Product Owner</strong><br>
                <span style="color:#6B7280;">Witel Jatim Barat</span>
            </div>
            <div style="padding:14px;border:1px solid #E5E7EB;border-radius:8px;">
                <strong>Tim Sales</strong><br>
                <span style="color:#6B7280;">Telkom Madiun</span>
            </div>
        </div>
    </div>
</div>

<style>
.tab-btn {
    background: transparent; border: none; padding: 10px 18px;
    font-size: 14px; font-weight: 600; color: #6B7280;
    cursor: pointer; border-bottom: 2px solid transparent;
    transition: all 0.15s;
}
.tab-btn:hover { color: #1F2937; }
.tab-btn.active { color: #E31E24; border-bottom-color: #E31E24; }
.setting-toggle {
    display: flex; justify-content: space-between; align-items: center;
    padding: 14px; border: 1px solid #E5E7EB; border-radius: 8px;
    cursor: pointer;
}
.setting-toggle:hover { background: #F9FAFB; }
.setting-toggle input[type="checkbox"] {
    width: 40px; height: 22px; accent-color: #E31E24;
    transform: scale(1.3);
}
</style>
<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
    document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
    document.getElementById('tab-' + tab).style.display = '';
}
document.querySelector('input[type="color"]')?.addEventListener('input', function() {
    document.getElementById('colorText').value = this.value;
});
</script>
<?php include 'includes/footer.php'; ?>
