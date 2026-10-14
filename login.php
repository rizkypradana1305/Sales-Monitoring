<?php
require_once 'config.php';
if (is_logged_in()) redirect('dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email && $password) {
        if (login($email, $password)) {
            redirect('dashboard.php');
        }
        $error = 'Email atau password salah. Silakan coba lagi.';
    } else {
        $error = 'Mohon isi email dan password.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk · Alokasi Sales Telkom Madiun</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%23E31E24'/><text x='50' y='68' font-size='60' font-weight='bold' text-anchor='middle' fill='white' font-family='Arial'>T</text></svg>">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-split">
        <!-- Left hero panel -->
        <div class="auth-hero">
            <div class="brand-row">
                <div class="brand-logo">T</div>
                <div class="brand-text">
                    Telkom Madiun
                    <small>Witel Jatim Barat</small>
                </div>
            </div>
            <div class="auth-hero-body">
                <h1>Sistem Alokasi Sales<br>Berdasarkan Potensi<br>Wilayah Madiun</h1>
                <p>Alokasikan pelanggan ke tim sales secara optimal menggunakan jarak Haversine dan Integer Linear Programming dengan memperhatikan kapasitas setiap sales.</p>
            </div>
            <div class="auth-tags">
                <span class="auth-tag">Haversine</span>
                <span class="auth-tag">ILP</span>
                <span class="auth-tag">Kapasitas Sales</span>
            </div>
        </div>

        <!-- Right form panel -->
        <div class="auth-form-side">
            <div class="auth-card">
                <h2>Selamat Datang</h2>
                <p class="subtitle">Masuk untuk mengelola alokasi sales Telkom Madiun.</p>

                <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">Email / Username</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="nama@telkom.co.id" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                    </div>
                    <div class="checkbox-row">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember" value="1"> Ingat saya
                        </label>
                        <a href="lupa_password.php" class="forgot-link">Lupa password?</a>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block btn-lg">Masuk</button>
                </form>

                <div class="divider-text">atau</div>

                <button type="button" class="btn btn-outline btn-block" onclick="showToast('SSO Telkom belum dikonfigurasi. Hubungi admin sistem.', 'info')">
                    Masuk dengan SSO Telkom
                </button>

                <p class="auth-footer">© 2026 Telkom Madiun. Seluruh hak dilindungi.</p>

                <div style="margin-top: 24px; padding: 14px; background: #F9FAFB; border-radius: 8px; font-size: 12px; color: #6B7280;">
                    <strong style="color:#1F2937">Demo Akun:</strong><br>
                    Email: <code>admin@telkom.co.id</code><br>
                    Password: <code>admin123</code>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
