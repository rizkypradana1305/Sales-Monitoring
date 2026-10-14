<?php
require_once 'config.php';
$sent = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        // Check if email exists (demo always says yes)
        $stmt = db()->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            // In real app, send reset email. For demo, mark as sent.
            $sent = true;
        } else {
            $sent = true; // Don't reveal if email exists
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password · Alokasi Sales Telkom Madiun</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%23E31E24'/><text x='50' y='68' font-size='60' font-weight='bold' text-anchor='middle' fill='white' font-family='Arial'>T</text></svg>">
</head>
<body>
<div class="forgot-wrap">
    <div class="forgot-card">
        <div class="forgot-icon">?</div>
        <h2 style="font-size: 22px; font-weight: 700; margin-bottom: 8px;">Lupa Password?</h2>
        <?php if ($sent): ?>
            <div class="alert alert-success" style="margin-bottom: 18px;">
                Tautan reset password telah dikirim ke email Anda. Periksa kotak masuk (dan folder spam).
            </div>
            <p style="color: #6B7280; font-size: 13px; margin-bottom: 24px;">
                Jika tidak menerima email dalam 5 menit, hubungi admin sistem di <strong>0813-3000-3000</strong>.
            </p>
            <a href="login.php" class="btn btn-outline btn-block">Kembali ke Login</a>
        <?php else: ?>
            <p style="color: #6B7280; font-size: 14px; margin-bottom: 22px;">
                Masukkan email akun Anda. Kami akan mengirim tautan untuk mengatur ulang password.
            </p>
            <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="nama@telkom.co.id" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg">Kirim Tautan Reset</button>
            </form>
            <div style="margin-top: 16px; text-align: center;">
                <a href="login.php" class="btn btn-outline btn-block">Kembali ke Login</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
