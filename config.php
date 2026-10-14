<?php
/**
 * Config - Database connection and core helpers
 * Sales Territory Optimizer - Telkom Madiun
 */

// ============================================================
// Database Configuration
// ============================================================
define('DB_HOST', getenv('MYSQLHOST') ?: '');
define('DB_PORT', getenv('MYSQLPORT') ?: '3306');
define('DB_NAME', getenv('MYSQLDATABASE') ?: '');
define('DB_USER', getenv('MYSQLUSER') ?: '');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
define('DB_CHARSET', 'utf8mb4');
// ============================================================
// App Configuration
// ============================================================
define('APP_NAME', 'Alokasi Sales Telkom Madiun');
define('APP_VERSION', '2.6.0');
define('APP_URL', 'http://localhost/sales_territory_app');
define('PRIMARY_COLOR', '#E31E24');

// Default Madiun coordinates
define('MADIUN_LAT', -7.6298);
define('MADIUN_LNG', 111.5239);
define('MADIUN_ZOOM', 13);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (comment out in production)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// ============================================================
// PDO Database Connection
// ============================================================
function db() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("Koneksi database gagal: " . $e->getMessage() . "<br><br>Pastikan:<br>1. MySQL/MariaDB berjalan<br>2. Database <code>sales_territory</code> sudah diimport dari <code>database/schema.sql</code>");
        }
    }
    return $pdo;
}

// ============================================================
// Auth helpers
// ============================================================
function current_user() {
    if (!isset($_SESSION['user_id'])) return null;
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_role($roles) {
    $user = current_user();
    if (!$user || !in_array($user['peran'], (array)$roles)) {
        http_response_code(403);
        die('<h2>403 - Akses Ditolak</h2><p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>');
    }
}

function login($email, $password) {
    $stmt = db()->prepare("SELECT * FROM users WHERE email = ? AND status = 'Aktif'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nama'] = $user['nama'];
        $_SESSION['user_peran'] = $user['peran'];
        return true;
    }
    return false;
}

function logout() {
    session_unset();
    session_destroy();
}

// ============================================================
// Utility helpers
// ============================================================
function e($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function flash($type, $msg) {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function get_flash() {
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function format_date($date) {
    if (!$date) return '-';
    $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($date);
    return date('d', $t) . ' ' . $months[(int)date('n', $t)] . ' ' . date('Y', $t);
}

function format_number($n, $dec = 0) {
    return number_format((float)$n, $dec, ',', '.');
}

function initials($name) {
    $parts = explode(' ', trim($name));
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) $out .= strtoupper(substr($p, 0, 1));
    return $out ?: '?';
}

// ============================================================
// Haversine distance (km)
// ============================================================
function haversine($lat1, $lon1, $lat2, $lon2) {
    $R = 6371; // km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c;
}

// ============================================================
// Settings helper
// ============================================================
function get_setting($key, $default = '') {
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    try {
        $stmt = db()->prepare("SELECT value FROM pengaturan WHERE `key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $cache[$key] = $row ? $row['value'] : $default;
    } catch (Exception $e) {
        $cache[$key] = $default;
    }
    return $cache[$key];
}

function set_setting($key, $value) {
    $stmt = db()->prepare("INSERT INTO pengaturan (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
    $stmt->execute([$key, $value]);
}

// ============================================================
// JSON response
// ============================================================
function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================
// Optimization: Greedy assignment with weighted score
// Uses Haversine distance + potential score weighted by parameters
// ============================================================
function run_optimization($parameter_id) {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Load parameter
        $stmt = $pdo->prepare("SELECT * FROM parameter_alokasi WHERE id = ?");
        $stmt->execute([$parameter_id]);
        $param = $stmt->fetch();
        if (!$param) throw new Exception("Parameter tidak ditemukan");

        // Load active sales
        $sales = $pdo->query("SELECT * FROM sales WHERE status = 'Aktif' ORDER BY id")->fetchAll();
        if (!$sales) throw new Exception("Tidak ada sales aktif");

        // Load all customers
        $customers = $pdo->query("SELECT * FROM pelanggan ORDER BY id")->fetchAll();
        if (!$customers) throw new Exception("Tidak ada pelanggan");

        // Create optimization run
        $kode = 'RUN-' . date('Ymd-His');
        $stmt = $pdo->prepare("INSERT INTO optimasi_run (kode, status, total_pelanggan, parameter_id) VALUES (?, 'Berjalan', ?, ?)");
        $stmt->execute([$kode, count($customers), $parameter_id]);
        $run_id = $pdo->lastInsertId();

        // Clear previous results for fresh run
        $pdo->prepare("DELETE FROM hasil_haversine WHERE run_id = ?")->execute([$run_id]);
        $pdo->prepare("DELETE FROM hasil_alokasi WHERE run_id = ?")->execute([$run_id]);

        $startMs = microtime(true);

        // Calculate all Haversine distances
        $wJarak = (float)$param['bobot_jarak'];
        $wPotensi = (float)$param['bobot_potensi'];
        $maxKapasitas = (int)$param['maksimum_kapasitas'];
        $toleransiJarak = (float)$param['toleransi_jarak'];
        $radiusKm = (float)$param['radius_km'];

        $distances = []; // [pelanggan_id][sales_id] = jarak_km
        $ins_haversine = $pdo->prepare("INSERT INTO hasil_haversine (pelanggan_id, sales_id, jarak_km, run_id) VALUES (?, ?, ?, ?)");

        foreach ($customers as $c) {
            $distances[$c['id']] = [];
            foreach ($sales as $s) {
                $km = haversine($c['latitude'], $c['longitude'], $s['latitude'], $s['longitude']);
                $distances[$c['id']][$s['id']] = $km;
                $ins_haversine->execute([$c['id'], $s['id'], round($km, 3), $run_id]);
            }
        }

        // Track sales capacity usage
        $kapasitas_terpakai = array_fill_keys(array_column($sales, 'id'), 0);
        $sales_capacity = [];
        foreach ($sales as $s) {
            $cap = min($s['kapasitas'], $maxKapasitas);
            $sales_capacity[$s['id']] = $cap;
        }

        // Build candidate list with weighted score for each customer-sales pair
        // Score = wJarak * (1 - jarak/radius) + wPotensi * (potensi/100)
        $candidates = [];
        foreach ($customers as $c) {
            foreach ($sales as $s) {
                $km = $distances[$c['id']][$s['id']];
                if ($km > $radiusKm) continue; // skip out-of-radius
                $jarak_score = max(0, 1 - ($km / max($radiusKm, 0.001)));
                $potensi_score = (int)$c['skor_potensi'] / 100;
                $score = ($wJarak * $jarak_score + $wPotensi * $potensi_score) / ($wJarak + $wPotensi);
                $candidates[] = [
                    'pelanggan_id' => $c['id'],
                    'sales_id' => $s['id'],
                    'jarak_km' => $km,
                    'skor' => $score,
                ];
            }
        }

        // Sort by score desc (higher score = better fit)
        usort($candidates, function ($a, $b) {
            return $b['skor'] <=> $a['skor'];
        });

        // Assign greedily respecting capacity
        $assigned_customers = []; // customer_id => true
        $total_jarak = 0;
        $total_alokasi = 0;
        $ins_alokasi = $pdo->prepare("INSERT INTO hasil_alokasi (pelanggan_id, sales_id, jarak_km, skor_potensi, status, run_id) VALUES (?, ?, ?, ?, 'Optimal', ?)");

        foreach ($candidates as $cand) {
            $pid = $cand['pelanggan_id'];
            $sid = $cand['sales_id'];
            if (isset($assigned_customers[$pid])) continue;
            if ($kapasitas_terpakai[$sid] >= $sales_capacity[$sid]) continue;

            $ins_alokasi->execute([
                $pid,
                $sid,
                round($cand['jarak_km'], 3),
                (int)$customers[array_search($pid, array_column($customers, 'id'))]['skor_potensi'],
                $run_id
            ]);
            // Update pelanggan.sales_id
            $pdo->prepare("UPDATE pelanggan SET sales_id = ? WHERE id = ?")->execute([$sid, $pid]);
            $kapasitas_terpakai[$sid]++;
            $assigned_customers[$pid] = true;
            $total_jarak += $cand['jarak_km'];
            $total_alokasi++;
        }

        // Customers that couldn't be assigned
        $unassigned = count($customers) - $total_alokasi;

        $durasi = (int)round((microtime(true) - $startMs) * 1000);

        // Update run status
        $stmt = $pdo->prepare("UPDATE optimasi_run SET status = 'Selesai', total_jarak = ?, total_teralokasi = ?, durasi_ms = ?, catatan = ? WHERE id = ?");
        $catatan = "Mode: " . $param['mode_solver'] . "; Bobot jarak: $wJarak; Bobot potensi: $wPotensi; Radius: $radiusKm km";
        if ($unassigned > 0) $catatan .= "; Tidak teralokasi: $unassigned";
        $stmt->execute([round($total_jarak, 2), $total_alokasi, $durasi, $catatan, $run_id]);

        $pdo->commit();
        return ['run_id' => $run_id, 'total_alokasi' => $total_alokasi, 'total_jarak' => round($total_jarak, 2), 'unassigned' => $unassigned];
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
