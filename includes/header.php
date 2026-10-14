<?php
/**
 * Shared layout: sidebar + header + main open
 * Expects $current_page variable to highlight active nav item
 */
if (!function_exists('nav_icon_svg')) {
    function nav_icon_svg($name) {
        $icons = [
            'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
            'sales'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            'pelanggan' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            'map'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>',
            'param'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
            'haversine' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20z"/></svg>',
            'ilp'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
            'hasil'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
            'laporan'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
            'users'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
            'settings'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v6M4.22 4.22l4.24 4.24m7.08 7.08l4.24 4.24M1 12h6m6 0h6M4.22 19.78l4.24-4.24m7.08-7.08l4.24-4.24"/></svg>',
            'logout'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
        ];
        return $icons[$name] ?? $icons['dashboard'];
    }
}

$navItems = [
    ['key' => 'dashboard',  'label' => 'Dashboard',              'url' => 'dashboard.php',           'icon' => 'dashboard'],
    ['key' => 'sales',      'label' => 'Data Sales',             'url' => 'data_sales.php',          'icon' => 'sales'],
    ['key' => 'pelanggan',  'label' => 'Data Pelanggan',         'url' => 'data_pelanggan.php',      'icon' => 'pelanggan'],
    ['key' => 'peta',       'label' => 'Peta Potensi Wilayah',  'url' => 'peta_potensi.php',        'icon' => 'map'],
    ['key' => 'param',      'label' => 'Parameter Alokasi',      'url' => 'parameter_alokasi.php',   'icon' => 'param'],
    ['key' => 'haversine',  'label' => 'Perhitungan Haversine',  'url' => 'perhitungan_haversine.php','icon' => 'haversine'],
    ['key' => 'ilp',        'label' => 'Optimasi ILP',           'url' => 'optimasi_ilp.php',        'icon' => 'ilp'],
    ['key' => 'hasil',      'label' => 'Hasil Alokasi',          'url' => 'hasil_alokasi.php',       'icon' => 'hasil'],
    ['key' => 'laporan',    'label' => 'Laporan',                'url' => 'laporan.php',             'icon' => 'laporan'],
    ['key' => 'users',      'label' => 'Manajemen Pengguna',    'url' => 'manajemen_pengguna.php',  'icon' => 'users'],
    ['key' => 'settings',   'label' => 'Pengaturan',             'url' => 'pengaturan.php',          'icon' => 'settings'],
];

$user = current_user();
$initials = $user ? initials($user['nama']) : 'AD';
$unread = 3; // demo notifications
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Dashboard') ?> · Alokasi Sales Telkom Madiun</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%23E31E24'/><text x='50' y='68' font-size='60' font-weight='bold' text-anchor='middle' fill='white' font-family='Arial'>T</text></svg>">
</head>
<body>
<div class="app-layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="logo-icon">T</div>
            <div class="brand-text">
                <h1>Alokasi Sales</h1>
                <p>Telkom Madiun</p>
            </div>
        </div>
        <nav class="nav-menu">
            <?php foreach ($navItems as $item): ?>
                <a href="<?= $item['url'] ?>" class="nav-item <?= ($current_page ?? '') === $item['key'] ? 'active' : '' ?>">
                    <span class="nav-icon"><?= nav_icon_svg($item['icon']) ?></span>
                    <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="user-profile">
            <div class="avatar-sm"><?= e($initials) ?></div>
            <div class="user-info">
                <h4><?= e($user['nama'] ?? 'Guest') ?></h4>
                <p><?= e($user['peran'] ?? '-') ?></p>
            </div>
            <a href="logout.php" class="icon-btn" title="Keluar" style="width:32px;height:32px;margin-left:auto;">
                <?= nav_icon_svg('logout') ?>
            </a>
        </div>
    </aside>

    <div class="main-area">
        <header class="app-header">
            <div class="breadcrumb">
                <span>Telkom Madiun</span>
                <span class="sep">/</span>
                <span class="current"><?= e($pageTitle ?? 'Dashboard') ?></span>
            </div>
            <div class="header-right">
                <div class="search-box">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" placeholder="Cari data..." id="globalSearch">
                </div>
                <button class="icon-btn" title="Notifikasi">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <?php if ($unread): ?><span class="badge-dot"></span><?php endif; ?>
                </button>
                <div class="admin-badge">
                    <span class="status-dot"></span>
                    <span><?= e($user['nama'] ?? 'Admin') ?></span>
                </div>
            </div>
        </header>
        <main class="page-body">
            <?php foreach (get_flash() as $f): ?>
                <div class="alert alert-<?= e($f['type']) === 'error' ? 'error' : 'success' ?>">
                    <?= e($f['msg']) ?>
                </div>
            <?php endforeach; ?>
