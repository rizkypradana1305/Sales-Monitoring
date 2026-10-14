<?php
require_once __DIR__ . '/../config.php';
require_login();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok' => false, 'msg' => 'Metode tidak diizinkan'], 405);

$id = (int)($_POST['id'] ?? 0);
$nama = trim($_POST['nama'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');
$wilayah = trim($_POST['wilayah'] ?? '');
$potensi = in_array($_POST['potensi'] ?? '', ['Rendah','Sedang','Tinggi']) ? $_POST['potensi'] : 'Sedang';
$skor = max(0, min(100, (int)($_POST['skor_potensi'] ?? 50)));
$produk = trim($_POST['produk'] ?? '');
$status = in_array($_POST['status'] ?? '', ['Prospek','Aktif','Pending']) ? $_POST['status'] : 'Prospek';
$sales_id = $_POST['sales_id'] !== '' ? (int)$_POST['sales_id'] : null;
$lat = (float)($_POST['latitude'] ?? -7.6298);
$lng = (float)($_POST['longitude'] ?? 111.5239);

if (!$nama || !$wilayah) json_response(['ok' => false, 'msg' => 'Nama dan wilayah wajib diisi']);

try {
    if ($id > 0) {
        $stmt = db()->prepare("UPDATE pelanggan SET nama=?, alamat=?, wilayah=?, potensi=?, skor_potensi=?, produk=?, status=?, sales_id=?, latitude=?, longitude=? WHERE id=?");
        $stmt->execute([$nama, $alamat ?: null, $wilayah, $potensi, $skor, $produk ?: null, $status, $sales_id, $lat, $lng, $id]);
        json_response(['ok' => true, 'msg' => 'Pelanggan berhasil diperbarui']);
    } else {
        $stmt = db()->prepare("INSERT INTO pelanggan (nama, alamat, wilayah, potensi, skor_potensi, produk, status, sales_id, latitude, longitude) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$nama, $alamat ?: null, $wilayah, $potensi, $skor, $produk ?: null, $status, $sales_id, $lat, $lng]);
        json_response(['ok' => true, 'msg' => 'Pelanggan baru berhasil ditambahkan']);
    }
} catch (Exception $e) {
    json_response(['ok' => false, 'msg' => 'Database error: ' . $e->getMessage()], 500);
}
