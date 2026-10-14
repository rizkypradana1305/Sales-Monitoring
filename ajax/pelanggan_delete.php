<?php
require_once __DIR__ . '/../config.php';
require_login();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok' => false, 'msg' => 'Metode tidak diizinkan'], 405);

$body = json_decode(file_get_contents('php://input'), true);
$id = (int)($body['id'] ?? 0);
if ($id <= 0) json_response(['ok' => false, 'msg' => 'ID tidak valid']);

try {
    db()->prepare("DELETE FROM pelanggan WHERE id = ?")->execute([$id]);
    json_response(['ok' => true, 'msg' => 'Pelanggan berhasil dihapus']);
} catch (Exception $e) {
    json_response(['ok' => false, 'msg' => 'Database error: ' . $e->getMessage()], 500);
}
