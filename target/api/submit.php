<?php
/**
 * Ruffian Target Promotion — API Endpoint
 * Receives Instagram username, generates unique 5-digit code, saves to CSV.
 *
 * Hardened for shared hosting (cPanel / unlimitedwebhosting.co.uk):
 *   - Reads JSON body AND falls back to form-encoded POST
 *   - Returns JSON with correct Content-Type even on errors
 *   - Uses __DIR__ for reliable path resolution
 *   - File locking for concurrent writes
 */

// Always output JSON — catch fatal errors too
ob_start();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
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
$instagram = '';
$igtrgt = '';
$rawBody = file_get_contents('php://input');

if (!empty($rawBody)) {
    $input = json_decode($rawBody, true);
    if (is_array($input)) {
        if (isset($input['instagram'])) {
            $instagram = $input['instagram'];
        }
        if (isset($input['igtrgt'])) {
            $igtrgt = $input['igtrgt'];
        }
    }
}

// Fallback: standard form-encoded POST
if (empty($instagram) && isset($_POST['instagram'])) {
    $instagram = $_POST['instagram'];
}
if (empty($igtrgt) && isset($_POST['igtrgt'])) {
    $igtrgt = $_POST['igtrgt'];
}

if (empty($instagram)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Instagram username is required']);
    exit();
}

// ── Clean and validate username ──
$instagram = trim($instagram);
$instagram = ltrim($instagram, '@');
$instagram = strtolower($instagram);

if (!preg_match('/^[a-z0-9._]{1,30}$/', $instagram)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid Instagram username']);
    exit();
}

// ── Clean and validate query parameter ──
$igtrgt = trim($igtrgt);
$igtrgt = strtolower($igtrgt);
if ($igtrgt !== '') {
    if (!preg_match('/^[a-z0-9._-]{1,50}$/', $igtrgt)) {
        $igtrgt = '';
    }
}

// ── CSV file path (relative to this script) ──
$csvFile = dirname(__DIR__) . '/data/submissions.csv';

// Ensure data directory exists
$dataDir = dirname($csvFile);
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0755, true);
}

// Ensure CSV file exists with header
if (!file_exists($csvFile)) {
    @file_put_contents($csvFile, "code,timestamp,instagram_username,query\n");
}

// Check the file is writable
if (!is_writable($csvFile)) {
    // Try to make it writable
    @chmod($csvFile, 0666);
    if (!is_writable($csvFile)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Data file is not writable. Set permissions to 666.']);
        exit();
    }
}

// ── Read existing data ──
$existingCodes = [];
$existingUserCode = null;

if (($handle = @fopen($csvFile, 'r')) !== false) {
    $header = fgetcsv($handle); // skip header row
    while (($row = fgetcsv($handle)) !== false) {
        if (isset($row[0]) && $row[0] !== '') {
            $existingCodes[$row[0]] = true;
        }
        if (isset($row[2]) && strtolower(trim($row[2])) === $instagram) {
            $existingUserCode = $row[0];
        }
    }
    fclose($handle);
}

// ── If user already registered, return their existing code ──
if ($existingUserCode !== null) {
    echo json_encode([
        'success' => true,
        'code' => $existingUserCode,
        'existing' => true
    ]);
    exit();
}

// ── Use static promo code ──
$code = 'RUFFIAN-FP31FI8';

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
    fputcsv($fp, [$code, $timestamp, $instagram, $igtrgt]);
    fflush($fp);
    flock($fp, LOCK_UN);
}
fclose($fp);

echo json_encode([
    'success' => true,
    'code' => $code,
    'existing' => false
]);
