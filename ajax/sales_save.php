<?php
require_once __DIR__ . '/../config.php';
require_login();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok' => false, 'msg' => 'Metode tidak diizinkan'], 405);

$id = (int)($_POST['id'] ?? 0);
$nama = trim($_POST['nama'] ?? '');
$email = trim($_POST['email'] ?? '');
$telepon = trim($_POST['telepon'] ?? '');
$nik = trim($_POST['nik'] ?? '');
$wilayah = trim($_POST['wilayah'] ?? '');
$kapasitas = max(1, min(50, (int)($_POST['kapasitas'] ?? 15)));
$status = in_array($_POST['status'] ?? '', ['Aktif','Cuti','Nonaktif']) ? $_POST['status'] : 'Aktif';
$lat = (float)($_POST['latitude'] ?? -7.6298);
$lng = (float)($_POST['longitude'] ?? 111.5239);
$tgl = $_POST['tanggal_bergabung'] ?? null;

if (!$nama || !$wilayah) json_response(['ok' => false, 'msg' => 'Nama dan wilayah wajib diisi']);

try {
    if ($id > 0) {
        $stmt = db()->prepare("UPDATE sales SET nama=?, email=?, telepon=?, nik=?, wilayah=?, kapasitas=?, status=?, latitude=?, longitude=?, tanggal_bergabung=? WHERE id=?");
        $stmt->execute([$nama, $email ?: null, $telepon ?: null, $nik ?: null, $wilayah, $kapasitas, $status, $lat, $lng, $tgl ?: null, $id]);
        json_response(['ok' => true, 'msg' => 'Sales berhasil diperbarui']);
    } else {
        $stmt = db()->prepare("INSERT INTO sales (nama, email, telepon, nik, wilayah, kapasitas, status, latitude, longitude, tanggal_bergabung) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$nama, $email ?: null, $telepon ?: null, $nik ?: null, $wilayah, $kapasitas, $status, $lat, $lng, $tgl ?: null]);
        json_response(['ok' => true, 'msg' => 'Sales baru berhasil ditambahkan']);
    }
} catch (Exception $e) {
    json_response(['ok' => false, 'msg' => 'Database error: ' . $e->getMessage()], 500);
}
