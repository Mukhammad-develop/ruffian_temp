<?php
/**
 * Ruffian Target Admin — CSV Download Handler
 */

session_start();

if (!isset($_SESSION['ruffian_admin_auth']) || $_SESSION['ruffian_admin_auth'] !== true) {
    header('Location: index.php');
    exit();
}

$csvFile = dirname(__DIR__) . '/data/submissions.csv';

if (!file_exists($csvFile)) {
    header('Location: dashboard.php');
    exit();
}

$filename = 'ruffian_submissions_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($csvFile));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($csvFile);
exit();
