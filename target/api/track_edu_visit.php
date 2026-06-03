<?php
/**
 * Ruffian Edu Flow — Visit Tracking API Endpoint
 * Logs educational page visits to edu_visits.csv.
 *
 * Hardened for shared hosting:
 *   - Reads JSON body AND falls back to form-encoded POST
 *   - Returns JSON with correct Content-Type even on errors
 *   - Uses __DIR__ for reliable path resolution
 *   - File locking for concurrent writes
 */

ob_start();
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Server internal error']);
    }
});

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// ── Read input ──
$igtrgt = '';
$rawBody = file_get_contents('php://input');

if (!empty($rawBody)) {
    $input = json_decode($rawBody, true);
    if (is_array($input) && isset($input['igtrgt'])) {
        $igtrgt = $input['igtrgt'];
    }
}

if (empty($igtrgt) && isset($_POST['igtrgt'])) {
    $igtrgt = $_POST['igtrgt'];
}

$igtrgt = trim(strtolower($igtrgt));
if ($igtrgt !== '') {
    if (!preg_match('/^[a-z0-9._-]{1,50}$/', $igtrgt)) {
        $igtrgt = 'invalid';
    }
} else {
    $igtrgt = 'organic';
}

// ── CSV file ──
$csvFile = dirname(__DIR__) . '/data/edu_visits.csv';

$dataDir = dirname($csvFile);
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0755, true);
}

if (!file_exists($csvFile)) {
    @file_put_contents($csvFile, "timestamp,query\n");
}

if (!is_writable($csvFile)) {
    @chmod($csvFile, 0666);
    if (!is_writable($csvFile)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Data file is not writable.']);
        exit();
    }
}

$timestamp = date('Y-m-d H:i:s');

$fp = @fopen($csvFile, 'a');
if ($fp === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not open data file']);
    exit();
}

if (flock($fp, LOCK_EX)) {
    fputcsv($fp, [$timestamp, $igtrgt]);
    fflush($fp);
    flock($fp, LOCK_UN);
}
fclose($fp);

echo json_encode(['success' => true, 'query' => $igtrgt, 'timestamp' => $timestamp]);
