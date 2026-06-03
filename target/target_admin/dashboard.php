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

// ── Read Submissions CSV (Legacy Registrations) ──
$csvFile = dirname(__DIR__) . '/data/submissions.csv';
$submissions = [];
$totalLegacySubmissions = 0;

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
    $totalLegacySubmissions = count($submissions);
}

// Reverse — newest first
$submissions = array_reverse($submissions);

// ── Read Legacy Clicks CSV ──
$legacyClicksFile = dirname(__DIR__) . '/data/clicks.csv';
$legacyClicks = [];
$totalLegacyClicks = 0;

if (file_exists($legacyClicksFile) && ($handle = fopen($legacyClicksFile, 'r')) !== false) {
    $header = fgetcsv($handle); // skip header
    while (($row = fgetcsv($handle)) !== false) {
        if (isset($row[1]) && $row[1] !== '') {
            $legacyClicks[] = [
                'timestamp' => $row[0],
                'query'     => trim($row[1])
            ];
        }
    }
    fclose($handle);
    $totalLegacyClicks = count($legacyClicks);
}

// ── Read V2 Visits CSV (New Visits) ──
$v2VisitsFile = dirname(__DIR__) . '/data/v2_visits.csv';
$v2Visits = [];
$totalV2Visits = 0;

if (file_exists($v2VisitsFile) && ($handle = fopen($v2VisitsFile, 'r')) !== false) {
    $header = fgetcsv($handle); // skip header
    while (($row = fgetcsv($handle)) !== false) {
        if (isset($row[1]) && $row[1] !== '') {
            $v2Visits[] = [
                'timestamp' => $row[0],
                'query'     => trim($row[1])
            ];
        }
    }
    fclose($handle);
    $totalV2Visits = count($v2Visits);
}

// ── Read V2 Copies CSV (New Copies) ──
$v2CopiesFile = dirname(__DIR__) . '/data/v2_copies.csv';
$v2Copies = [];
$totalV2Copies = 0;

if (file_exists($v2CopiesFile) && ($handle = fopen($v2CopiesFile, 'r')) !== false) {
    $header = fgetcsv($handle); // skip header
    while (($row = fgetcsv($handle)) !== false) {
        if (isset($row[1]) && $row[1] !== '') {
            $v2Copies[] = [
                'timestamp' => $row[0],
                'query'     => trim($row[1])
            ];
        }
    }
    fclose($handle);
    $totalV2Copies = count($v2Copies);
}

// ── Read V2 CTA Taps CSV (New CTA Clicks) ──
$v2CtaFile = dirname(__DIR__) . '/data/v2_cta_taps.csv';
$v2CtaTaps = [];
$totalV2CtaTaps = 0;

if (file_exists($v2CtaFile) && ($handle = fopen($v2CtaFile, 'r')) !== false) {
    $header = fgetcsv($handle); // skip header
    while (($row = fgetcsv($handle)) !== false) {
        if (isset($row[1]) && $row[1] !== '') {
            $v2CtaTaps[] = [
                'timestamp' => $row[0],
                'query'     => trim($row[1])
            ];
        }
    }
    fclose($handle);
    $totalV2CtaTaps = count($v2CtaTaps);
}

// ── Daily Stats ──
$todayLegacySubmissions = 0;
$todayLegacyClicks = 0;
$todayV2Visits = 0;
$todayV2Copies = 0;
$todayV2CtaTaps = 0;
$today = date('Y-m-d');

foreach ($submissions as $s) {
    if (strpos($s['timestamp'], $today) === 0) {
        $todayLegacySubmissions++;
    }
}

foreach ($legacyClicks as $c) {
    if (strpos($c['timestamp'], $today) === 0) {
        $todayLegacyClicks++;
    }
}

foreach ($v2Visits as $v) {
    if (strpos($v['timestamp'], $today) === 0) {
        $todayV2Visits++;
    }
}

foreach ($v2Copies as $cp) {
    if (strpos($cp['timestamp'], $today) === 0) {
        $todayV2Copies++;
    }
}

foreach ($v2CtaTaps as $ct) {
    if (strpos($ct['timestamp'], $today) === 0) {
        $todayV2CtaTaps++;
    }
}

// ── Aggregate Legacy Query Analytics ──
$legacyQueryStats = [];

foreach ($legacyClicks as $c) {
    $q = $c['query'];
    if (!isset($legacyQueryStats[$q])) {
        $legacyQueryStats[$q] = ['clicks' => 0, 'submissions' => 0];
    }
    $legacyQueryStats[$q]['clicks']++;
}

foreach ($submissions as $s) {
    $q = $s['query'];
    if ($q !== '—' && $q !== '') {
        if (!isset($legacyQueryStats[$q])) {
            $legacyQueryStats[$q] = ['clicks' => 0, 'submissions' => 0];
        }
        $legacyQueryStats[$q]['submissions']++;
    }
}

uasort($legacyQueryStats, function ($a, $b) {
    if ($a['submissions'] === $b['submissions']) {
        return $b['clicks'] - $a['clicks'];
    }
    return $b['submissions'] - $a['submissions'];
});

// ── Aggregate V2 Query Analytics ──
$v2QueryStats = [];

foreach ($v2Visits as $v) {
    $q = $v['query'];
    if (!isset($v2QueryStats[$q])) {
        $v2QueryStats[$q] = ['clicks' => 0, 'copies' => 0, 'cta_taps' => 0];
    }
    $v2QueryStats[$q]['clicks']++;
}

foreach ($v2Copies as $cp) {
    $q = $cp['query'];
    if (!isset($v2QueryStats[$q])) {
        $v2QueryStats[$q] = ['clicks' => 0, 'copies' => 0, 'cta_taps' => 0];
    }
    $v2QueryStats[$q]['copies']++;
}

foreach ($v2CtaTaps as $ct) {
    $q = $ct['query'];
    if (!isset($v2QueryStats[$q])) {
        $v2QueryStats[$q] = ['clicks' => 0, 'copies' => 0, 'cta_taps' => 0];
    }
    $v2QueryStats[$q]['cta_taps']++;
}

uasort($v2QueryStats, function ($a, $b) {
    if ($a['cta_taps'] === $b['cta_taps']) {
        return $b['clicks'] - $a['clicks'];
    }
    return $b['cta_taps'] - $a['cta_taps'];
});

// ── Read Edu Visits CSV ──
$eduVisitsFile = dirname(__DIR__) . '/data/edu_visits.csv';
$eduVisits = [];
$totalEduVisits = 0;

if (file_exists($eduVisitsFile) && ($handle = fopen($eduVisitsFile, 'r')) !== false) {
    $header = fgetcsv($handle);
    while (($row = fgetcsv($handle)) !== false) {
        if (isset($row[1]) && $row[1] !== '') {
            $eduVisits[] = ['timestamp' => $row[0], 'query' => trim($row[1])];
        }
    }
    fclose($handle);
    $totalEduVisits = count($eduVisits);
}

// ── Edu Daily Stats ──
$todayEduVisits = 0;

foreach ($eduVisits as $v) {
    if (strpos($v['timestamp'], $today) === 0) $todayEduVisits++;
}

// ── Edu per-query aggregation ──
$eduQueryStats = [];

foreach ($eduVisits as $v) {
    $q = $v['query'];
    if (!isset($eduQueryStats[$q])) {
        $eduQueryStats[$q] = ['visits' => 0];
    }
    $eduQueryStats[$q]['visits']++;
}

uasort($eduQueryStats, function ($a, $b) {
    return $b['visits'] - $a['visits'];
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
    <style>
        /* ── Collapsible Sections (V2 and Legacy V1) ── */
        .toggle-older-btn {
            background: var(--hunter-green) !important;
            border: 1px solid rgba(233, 182, 49, 0.3) !important;
            color: var(--ruffian-gold) !important;
            padding: 9px 20px !important;
            font-family: 'Montserrat', Arial, sans-serif !important;
            font-size: 11px !important;
            font-weight: 500 !important;
            letter-spacing: 0.1em !important;
            text-transform: uppercase !important;
            cursor: pointer !important;
            transition: all 0.2s ease-in-out !important;
            position: relative !important;
            z-index: 10 !important;
            display: inline-flex !important;
            align-items: center !important;
            border-radius: 0 !important;
            outline: none !important;
            box-shadow: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
        }

        .toggle-older-btn:hover {
            background: linear-gradient(rgba(233, 182, 49, 0.08), rgba(233, 182, 49, 0.08)), var(--hunter-green) !important;
            border-color: var(--ruffian-gold) !important;
        }

        .older-sections-collapsed {
            display: none !important;
            opacity: 0 !important;
        }

        .older-sections-expanded {
            display: block !important;
            opacity: 1 !important;
        }

        .chevron-rotate {
            transform: rotate(180deg) !important;
        }
    </style>
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

    <!-- ═══════════════════════════════════════
       SECTION 3: EDU FLOW
       ═══════════════════════════════════════ -->
    <div class="dash-section-title">Edu Oqim (3 Qadamli Ta'lim Sahifasi) Statistikasi</div>

    <!-- Stats Cards (Edu Flow) -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-value"><?php echo $totalEduVisits; ?></div>
            <div class="stat-label">Jami kirishlar (Edu)</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $todayEduVisits; ?></div>
            <div class="stat-label">Bugungi kirishlar (Edu)</div>
        </div>
    </div>

    <!-- Edu Query Performance Table -->
    <div class="table-container" style="margin-bottom: 36px;">
        <div class="table-header-row">
            <h2 class="table-title">Edu Postlar samaradorligi (Edu Query Performance)</h2>
            <span class="table-count"><?php echo count($eduQueryStats); ?> ta post</span>
        </div>

        <?php if (empty($eduQueryStats)): ?>
            <div class="table-empty">
                <p>Hozircha edu oqimi bo'yicha hech qanday tashriflar kelmagan.</p>
            </div>
        <?php else: ?>
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="th-num" style="width: 60px;">#</th>
                            <th>Post / Query nomi (igtrgt)</th>
                            <th style="text-align: right; padding-right: 24px; width: 180px;">Kirishlar (Visits)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $idx = 1;
                        foreach ($eduQueryStats as $qName => $stats):
                        ?>
                        <tr>
                            <td class="td-num"><?php echo $idx++; ?></td>
                            <td style="font-weight: 400; color: var(--ruffian-gold); letter-spacing: 0.05em;">
                                <?php echo htmlspecialchars($qName); ?>
                            </td>
                            <td style="text-align: right; padding-right: 24px; font-weight: 600; color: var(--ruffian-gold);">
                                <?php echo $stats['visits']; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Divider and Collapse Toggle for V2 and V1 Legacy sections -->
    <div class="toggle-divider-container" style="text-align: center; margin: 48px 0; position: relative;">
        <hr class="toggle-divider" style="border: 0; border-top: 1px solid rgba(233, 182, 49, 0.2); position: absolute; top: 50%; left: 0; right: 0; margin: 0; z-index: 1;">
        <button id="toggle-older-btn" class="toggle-older-btn">
            Ko'proq ko'rish
            <svg class="chevron-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 6px; transition: transform 0.3s ease; vertical-align: middle;"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </button>
    </div>

    <!-- Wrapper for Older Sections (V2 and Legacy V1) -->
    <div id="older-sections-wrapper" class="older-sections-collapsed">

        <!-- ═══════════════════════════════════════
           SECTION 1: NEW ONE-STEP FLOW
           ═══════════════════════════════════════ -->
        <div class="dash-section-title">Yangi Oqim (Bir Bosqichli Funnel) Ko'rsatkichlari</div>

        <!-- Stats Cards (New V2 Funnel) -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalV2Visits; ?></div>
                <div class="stat-label">Jami kirishlar (V2)</div>
                <div style="font-size: 10px; color: var(--text-muted); margin-top: 6px; font-family: 'Montserrat', Arial, sans-serif; letter-spacing: 0.05em;">Bugun: <?php echo $todayV2Visits; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalV2Copies; ?></div>
                <div class="stat-label">Promokod nusxalashlar</div>
                <div style="font-size: 10px; color: var(--text-muted); margin-top: 6px; font-family: 'Montserrat', Arial, sans-serif; letter-spacing: 0.05em;">Bugun: <?php echo $todayV2Copies; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalV2CtaTaps; ?></div>
                <div class="stat-label">Direktga o'tishlar</div>
                <div style="font-size: 10px; color: var(--text-muted); margin-top: 6px; font-family: 'Montserrat', Arial, sans-serif; letter-spacing: 0.05em;">Bugun: <?php echo $todayV2CtaTaps; ?></div>
            </div>
        </div>

        <!-- Huni Tahlili Section -->
        <div class="funnel-container">
            <div class="funnel-header">
                <h2 class="table-title" style="margin: 0; border: none; padding: 0;">Konversiya Hunisi (Funnel Analytics)</h2>
                <span class="table-count">Real vaqt rejimida</span>
            </div>
            
            <div class="funnel-stages">
                <!-- Stage 1: Visits -->
                <div class="funnel-stage">
                    <span class="funnel-stage-num">01</span>
                    <div class="funnel-stage-value"><?php echo $totalV2Visits; ?></div>
                    <div class="funnel-stage-label">Kirishlar</div>
                    <div class="funnel-stage-sub">Sahifaga tashriflar</div>
                </div>
                
                <!-- Arrow 1 -->
                <div class="funnel-arrow">
                    <?php 
                    $copyCr = $totalV2Visits > 0 ? round(($totalV2Copies / $totalV2Visits) * 100, 1) : 0;
                    ?>
                    <div class="funnel-arrow-cr"><?php echo $copyCr; ?>% CR</div>
                    <div class="funnel-arrow-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </div>
                </div>
                
                <!-- Stage 2: Copies -->
                <div class="funnel-stage">
                    <span class="funnel-stage-num">02</span>
                    <div class="funnel-stage-value"><?php echo $totalV2Copies; ?></div>
                    <div class="funnel-stage-label">Nusxalashlar</div>
                    <div class="funnel-stage-sub">Promokod ko'chirilishi</div>
                </div>
                <!-- Stage 3: CTA Taps -->
                <div class="funnel-stage">
                    <span class="funnel-stage-num">03</span>
                    <div class="funnel-stage-value"><?php echo $totalV2CtaTaps; ?></div>
                    <div class="funnel-stage-label">Direktga o'tishlar</div>
                    <div class="funnel-stage-sub">Instagram Direktga yozganlar</div>
                </div>
            </div>
            
            <div style="margin-top: 24px; text-align: center; font-family: 'Montserrat', Arial, sans-serif; font-size: 12px; color: var(--text-muted); letter-spacing: 0.05em;">
                Umumiy Kirishlar ➔ Direktga o'tish konversiyasi (CR): <strong style="color: var(--ruffian-gold); font-size: 14px;"><?php echo $overallCr; ?>%</strong>
            </div>
        </div>

        <!-- Query Performance Section (New V2 Funnel) -->
        <div class="table-container" style="margin-bottom: 36px;">
            <div class="table-header-row">
                <h2 class="table-title">Yangi Postlar samaradorligi (V2 Funnel Analytics)</h2>
                <span class="table-count"><?php echo count($v2QueryStats); ?> ta post</span>
            </div>

            <?php if (empty($v2QueryStats)): ?>
                <div class="table-empty">
                    <p>Hozircha yangi oqim (V2) bo'yicha hech qanday tashriflar kelmagan.</p>
                </div>
            <?php else: ?>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="th-num">#</th>
                                <th>Post / Query nomi (igtrgt)</th>
                                <th>Kirishlar (Visits)</th>
                                <th>Nusxalashlar (Copies)</th>
                                <th>Direktga o'tishlar (CTA Taps)</th>
                                <th>Konversiya (Visits → CTA)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $idx = 1;
                            foreach ($v2QueryStats as $qName => $stats): 
                                $cr = $stats['clicks'] > 0 ? round(($stats['cta_taps'] / $stats['clicks']) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td class="td-num"><?php echo $idx++; ?></td>
                                <td style="font-weight: 400; color: var(--ruffian-gold); letter-spacing: 0.05em;">
                                    <?php echo htmlspecialchars($qName); ?>
                                </td>
                                <td><?php echo $stats['clicks']; ?></td>
                                <td><?php echo $stats['copies']; ?></td>
                                <td><?php echo $stats['cta_taps']; ?></td>
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


        <!-- ═══════════════════════════════════════
           SECTION 2: LEGACY TWO-STEP FLOW
           ═══════════════════════════════════════ -->
        <div class="dash-section-title">Eski Oqim (Ikki Bosqichli Oqim) Statistikasi</div>

        <!-- Stats Cards (Legacy Flow) -->
        <div class="stats-row" style="opacity: 0.85;">
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalLegacySubmissions; ?></div>
                <div class="stat-label">Jami arizalar (Tarixiy)</div>
                <div style="font-size: 10px; color: var(--text-muted); margin-top: 6px; font-family: 'Montserrat', Arial, sans-serif; letter-spacing: 0.05em;">Bugun: <?php echo $todayLegacySubmissions; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalLegacyClicks; ?></div>
                <div class="stat-label">Jami bosishlar (Tarixiy)</div>
                <div style="font-size: 10px; color: var(--text-muted); margin-top: 6px; font-family: 'Montserrat', Arial, sans-serif; letter-spacing: 0.05em;">Bugun: <?php echo $todayLegacyClicks; ?></div>
            </div>
        </div>

        <!-- Legacy Query Performance Section -->
        <div class="table-container" style="margin-bottom: 36px;">
            <div class="table-header-row">
                <h2 class="table-title">Eski Postlar samaradorligi (Legacy Query Performance)</h2>
                <span class="table-count"><?php echo count($legacyQueryStats); ?> ta post</span>
            </div>

            <?php if (empty($legacyQueryStats)): ?>
                <div class="table-empty">
                    <p>Eski oqim (V1) bo'yicha hech qanday ma'lumot topilmadi.</p>
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
                            foreach ($legacyQueryStats as $qName => $stats): 
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

        <!-- Legacy Submissions Table -->
        <div class="table-container" style="margin-bottom: 36px;">
            <div class="table-header-row">
                <h2 class="table-title">Barcha arizalar (Tarixiy ro'yxat)</h2>
                <span class="table-count"><?php echo $totalLegacySubmissions; ?> ta</span>
            </div>

            <?php if ($totalLegacySubmissions === 0): ?>
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
                                <td class="td-num"><?php echo $totalLegacySubmissions - $i; ?></td>
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

    </div>

    <!-- Footer -->
    <footer class="dash-footer">
        <p>Ruffian Admin &middot; <?php echo date('Y'); ?></p>
    </footer>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggleBtn = document.getElementById('toggle-older-btn');
    var wrapper = document.getElementById('older-sections-wrapper');
    var chevron = toggleBtn ? toggleBtn.querySelector('.chevron-icon') : null;
    
    if (toggleBtn && wrapper) {
        toggleBtn.addEventListener('click', function() {
            if (wrapper.classList.contains('older-sections-collapsed')) {
                wrapper.classList.remove('older-sections-collapsed');
                wrapper.classList.add('older-sections-expanded');
                toggleBtn.innerHTML = 'Yopish <svg class="chevron-icon chevron-rotate" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 6px; transition: transform 0.3s ease; vertical-align: middle;"><polyline points="6 9 12 15 18 9"></polyline></svg>';
            } else {
                wrapper.classList.remove('older-sections-expanded');
                wrapper.classList.add('older-sections-collapsed');
                toggleBtn.innerHTML = 'Ko\'proq ko\'rish <svg class="chevron-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 6px; transition: transform 0.3s ease; vertical-align: middle;"><polyline points="6 9 12 15 18 9"></polyline></svg>';
            }
        });
    }
});
</script>

</body>
</html>
