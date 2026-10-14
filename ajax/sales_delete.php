<?php
require_once __DIR__ . '/../config.php';
require_login();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok' => false, 'msg' => 'Metode tidak diizinkan'], 405);

$body = json_decode(file_get_contents('php://input'), true);
$id = (int)($body['id'] ?? 0);
if ($id <= 0) json_response(['ok' => false, 'msg' => 'ID tidak valid']);

try {
    // Set pelanggan.sales_id to NULL first (foreign key constraint)
    db()->prepare("UPDATE pelanggan SET sales_id = NULL WHERE sales_id = ?")->execute([$id]);
    $stmt = db()->prepare("DELETE FROM sales WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() > 0) {
        json_response(['ok' => true, 'msg' => 'Sales berhasil dihapus']);
    } else {
        json_response(['ok' => false, 'msg' => 'Sales tidak ditemukan']);
    }
} catch (Exception $e) {
    json_response(['ok' => false, 'msg' => 'Database error: ' . $e->getMessage()], 500);
}
