<?php
/**
 * Ruffian Target Admin — Dashboard
 * Displays submissions table, stats, download and logout.
 */

session_start();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

if (!isset($_SESSION['ruffian_admin_auth']) || $_SESSION['ruffian_admin_auth'] !== true) {
    header('Location: index.php');
    exit();
}

// ── Read Submissions CSV ──
$csvFile = dirname(__DIR__) . '/data/submissions.csv';
$submissions = [];
$totalCount = 0;

if (file_exists($csvFile) && ($handle = fopen($csvFile, 'r')) !== false) {
    $header = fgetcsv($handle); // skip header
    while (($row = fgetcsv($handle)) !== false) {
        if (isset($row[0]) && $row[0] !== '') {
            $submissions[] = [
                'code'      => $row[0],
                'timestamp' => isset($row[1]) ? $row[1] : '—',
                'username'  => isset($row[2]) ? $row[2] : '—',
                'query'     => (isset($row[3]) && trim($row[3]) !== '') ? trim($row[3]) : '—',
            ];
        }
    }
    fclose($handle);
    $totalCount = count($submissions);
}

// Reverse — newest first
$submissions = array_reverse($submissions);

// ── Read Clicks CSV ──
$clicksFile = dirname(__DIR__) . '/data/clicks.csv';
$clicks = [];
$totalClicks = 0;

if (file_exists($clicksFile) && ($handle = fopen($clicksFile, 'r')) !== false) {
    $header = fgetcsv($handle); // skip header
    while (($row = fgetcsv($handle)) !== false) {
        if (isset($row[1]) && $row[1] !== '') {
            $clicks[] = [
                'timestamp' => $row[0],
                'query'     => trim($row[1])
            ];
        }
    }
    fclose($handle);
    $totalClicks = count($clicks);
}

// ── Stats ──
$todayCount = 0;
$todayClicks = 0;
$today = date('Y-m-d');

foreach ($submissions as $s) {
    if (strpos($s['timestamp'], $today) === 0) {
        $todayCount++;
    }
}

foreach ($clicks as $c) {
    if (strpos($c['timestamp'], $today) === 0) {
        $todayClicks++;
    }
}

// ── Aggregate Query Analytics ──
$queryStats = [];

// Track clicks per query
foreach ($clicks as $c) {
    $q = $c['query'];
    if (!isset($queryStats[$q])) {
        $queryStats[$q] = ['clicks' => 0, 'submissions' => 0];
    }
    $queryStats[$q]['clicks']++;
}

// Track submissions per query
foreach ($submissions as $s) {
    $q = $s['query'];
    if ($q !== '—' && $q !== '') {
        if (!isset($queryStats[$q])) {
            $queryStats[$q] = ['clicks' => 0, 'submissions' => 0];
        }
        $queryStats[$q]['submissions']++;
    }
}

// Sort queries by submissions desc, then clicks desc
uasort($queryStats, function ($a, $b) {
    if ($a['submissions'] === $b['submissions']) {
        return $b['clicks'] - $a['clicks'];
    }
    return $b['submissions'] - $a['submissions'];
});
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RUFFIAN — Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=Lora:ital,wght@0,400;1,400&family=Montserrat:wght@300;400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-body">

<div class="dashboard-wrapper">

    <!-- Header -->
    <header class="dash-header">
        <div class="dash-header-inner">
            <div class="dash-brand">RUFFIAN</div>
            <nav class="dash-nav">
                <a href="download.php" class="dash-nav-link">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    CSV yuklash
                </a>
                <a href="logout.php" class="dash-nav-link dash-nav-logout">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Chiqish
                </a>
            </nav>
        </div>
    </header>

    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-value"><?php echo $totalCount; ?></div>
            <div class="stat-label">Jami arizalar</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $todayCount; ?></div>
            <div class="stat-label">Bugun arizalar</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $totalClicks; ?></div>
            <div class="stat-label">Jami bosishlar</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $todayClicks; ?></div>
            <div class="stat-label">Bugun bosishlar</div>
        </div>
    </div>

    <!-- Query Performance Section -->
    <div class="table-container" style="margin-bottom: 36px;">
        <div class="table-header-row">
            <h2 class="table-title">Postlar samaradorligi (Query Performance)</h2>
            <span class="table-count"><?php echo count($queryStats); ?> ta post</span>
        </div>

        <?php if (empty($queryStats)): ?>
            <div class="table-empty">
                <p>Hozircha hech qanday promo-postlardan bosishlar kelmagan.</p>
            </div>
        <?php else: ?>
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="th-num">#</th>
                            <th>Post / Query nomi (igtrgt)</th>
                            <th>Bosishlar (Clicks)</th>
                            <th>Arizalar (Submissions)</th>
                            <th>Konversiya (CR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $idx = 1;
                        foreach ($queryStats as $qName => $stats): 
                            $cr = $stats['clicks'] > 0 ? round(($stats['submissions'] / $stats['clicks']) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td class="td-num"><?php echo $idx++; ?></td>
                            <td style="font-weight: 400; color: var(--ruffian-gold); letter-spacing: 0.05em;">
                                <?php echo htmlspecialchars($qName); ?>
                            </td>
                            <td><?php echo $stats['clicks']; ?></td>
                            <td><?php echo $stats['submissions']; ?></td>
                            <td style="font-weight: 600; color: <?php echo $cr > 0 ? 'var(--ruffian-gold)' : 'var(--text-muted)'; ?>;">
                                <?php echo $cr; ?>%
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Table -->
    <div class="table-container">
        <div class="table-header-row">
            <h2 class="table-title">Barcha arizalar</h2>
            <span class="table-count"><?php echo $totalCount; ?> ta</span>
        </div>

        <?php if ($totalCount === 0): ?>
            <div class="table-empty">
                <p>Hozircha hech qanday ariza yo'q.</p>
            </div>
        <?php else: ?>
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="th-num">#</th>
                            <th class="th-code">Promokod</th>
                            <th class="th-user">Instagram</th>
                            <th>Manba (Source)</th>
                            <th class="th-time">Vaqt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $i => $s): ?>
                        <tr>
                            <td class="td-num"><?php echo $totalCount - $i; ?></td>
                            <td class="td-code"><?php echo htmlspecialchars($s['code']); ?></td>
                            <td class="td-user">
                                <a href="https://instagram.com/<?php echo htmlspecialchars($s['username']); ?>" target="_blank" rel="noopener">
                                    @<?php echo htmlspecialchars($s['username']); ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($s['query'] !== '—' && $s['query'] !== ''): ?>
                                    <span style="display: inline-block; padding: 2px 8px; font-size: 11px; background: rgba(233, 182, 49, 0.1); border: 1px solid rgba(233, 182, 49, 0.25); color: var(--ruffian-gold); letter-spacing: 0.03em;">
                                        <?php echo htmlspecialchars($s['query']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 11px;">to'g'ridan-to'g'ri</span>
                                <?php endif; ?>
                            </td>
                            <td class="td-time"><?php echo htmlspecialchars($s['timestamp']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="dash-footer">
        <p>Ruffian Admin &middot; <?php echo date('Y'); ?></p>
    </footer>

</div>

</body>
</html>
