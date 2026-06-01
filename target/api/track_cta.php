<?php
/**
 * Ruffian Target Promotion — CTA Tracking API Endpoint
 * Receives CTA button taps and logs them to cta_taps.csv.
 *
 * Hardened for shared hosting:
 *   - Reads JSON body AND falls back to form-encoded POST
 *   - Returns JSON with correct Content-Type even on errors
 *   - Uses __DIR__ for reliable path resolution
 *   - File locking for concurrent writes
 */

// Always output JSON — catch fatal errors too
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

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// ── Read input: try JSON body first, fall back to $_POST ──
$igtrgt = '';
$rawBody = file_get_contents('php://input');

if (!empty($rawBody)) {
    $input = json_decode($rawBody, true);
    if (is_array($input) && isset($input['igtrgt'])) {
        $igtrgt = $input['igtrgt'];
    }
}

// Fallback: standard form-encoded POST
if (empty($igtrgt) && isset($_POST['igtrgt'])) {
    $igtrgt = $_POST['igtrgt'];
}

// Clean and validate query parameter (optional, can be empty for organic)
$igtrgt = trim($igtrgt);
$igtrgt = strtolower($igtrgt);

if ($igtrgt !== '') {
    if (!preg_match('/^[a-z0-9._-]{1,50}$/', $igtrgt)) {
        $igtrgt = 'invalid';
    }
} else {
    $igtrgt = 'organic';
}

// ── CSV file path (relative to this script) ──
$csvFile = dirname(__DIR__) . '/data/cta_taps.csv';

// Ensure data directory exists
$dataDir = dirname($csvFile);
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0755, true);
}

// Ensure CSV file exists with header
if (!file_exists($csvFile)) {
    @file_put_contents($csvFile, "timestamp,query\n");
}

// Check the file is writable
if (!is_writable($csvFile)) {
    @chmod($csvFile, 0666);
    if (!is_writable($csvFile)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Data file is not writable. Set permissions to 666.']);
        exit();
    }
}

// ── Timestamp ──
$timestamp = date('Y-m-d H:i:s');

// ── Append to CSV with file locking ──
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

echo json_encode([
    'success' => true,
    'query' => $igtrgt,
    'timestamp' => $timestamp
]);
