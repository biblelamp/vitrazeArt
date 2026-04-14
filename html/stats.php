<?php
// stats.cz © 2026 version 0.2 by 14-Apr-26

$dir = '/var/lib/awstats/';
$domain = 'vitrazeArt.cz';

// ===== FILE LIST =====
$files = glob($dir . "awstats*.$domain.txt");

$fileMap = [];

foreach ($files as $f) {
    if (preg_match('/awstats(\d{2})(\d{4})\./', $f, $m)) {
        $key = "{$m[2]}-{$m[1]}";
        $fileMap[$key] = $f;
    }
}

krsort($fileMap);

$selectedKey = $_GET['file'] ?? array_key_first($fileMap);
$file = $fileMap[$selectedKey] ?? null;

if (!$file) {
    die("File not found: $selectedKey");
}

$content = file_get_contents($file);

// ===== FUNCTIONS =====
function extractBlock($content, $name)
{
    return preg_match('/BEGIN_' . preg_quote($name, '/') . '\b(.*?)END_' . preg_quote($name, '/') . '\b/s', $content, $m)
    ? trim($m[1]) : '';
}

function nf($n)
{
    return number_format($n, 0, '.', ' ');
}

function formatBytes($b)
{
    $u = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($b > 1024 && $i < 3) {
        $b /= 1024;
        $i++;
    }
    return round($b, 2) . ' ' . $u[$i];
}

// ===== GENERAL =====
$general = extractBlock($content, 'GENERAL');

preg_match('/TotalVisits\s+(\d+)/', $general, $visits);
preg_match('/TotalUnique\s+(\d+)/', $general, $unique);

$totalVisits = (int)($visits[1] ?? 0);
$totalUnique = (int)($unique[1] ?? 0);

// ===== DOMAIN =====
$domain = extractBlock($content, 'DOMAIN');
$totalPages = 0;
$totalHits = 0;
$totalBandwidth = 0;

foreach (explode("\n", $domain) as $line) {
    $p = preg_split('/\s+/', trim($line));
    if (count($p) >= 4) {
        $totalPages += (int)$p[1];
        $totalHits += (int)$p[2];
        $totalBandwidth += (int)$p[3];
    }
}

// ===== ROBOTS =====
$robot = extractBlock($content, 'ROBOT');
$robots = [];
$totalRobotHits = 0;

foreach (explode("\n", $robot) as $line) {
    $p = preg_split('/\s+/', trim($line));
    if (count($p) > 1) {
        $robots[$p[0]] = (int)$p[1];
        $totalRobotHits += (int)$p[1];
    }
}
arsort($robots);
$topRobots = array_slice($robots, 0, 5, true);

// ===== SIDER =====
$sider = extractBlock($content, 'SIDER');
$vizitPages = [];

foreach (explode("\n", $sider) as $line) {
    $p = preg_split('/\s+/', $line);
    if (count($p) > 1) {
        $vizitPages[$p[0]] = (int)$p[1];
    }
}
arsort($vizitPages);
$topVizitPages = array_slice($vizitPages, 0, 12, true);

// ===== SIDER_404 =====
$sider_404 = extractBlock($content, 'SIDER_404');
$pages404 = [];

foreach (explode("\n", $sider_404) as $line) {
    $p = preg_split('/\s+/', $line);
    if (count($p) > 1) {
        $pages404[$p[0]] = (int)$p[1];
    }
}
arsort($pages404);
$topPages404 = array_slice($pages404, 0, 12, true);

// ===== ERRORS =====
$e = extractBlock($content, 'ERRORS');
$errors = [];

foreach (explode("\n", $e) as $line) {
    $p = preg_split('/\s+/', trim($line));
    if (count($p) >= 2) {
        $errors[$p[0]] = (int)$p[1];
    }
}
arsort($errors);
$topErrors = array_slice($errors, 0, 10, true);

// ===== ORIGIN =====
$o = extractBlock($content, 'ORIGIN');
$origin = [];

foreach (explode("\n", $o) as $line) {
    $p = preg_split('/\s+/', trim($line));
    if (count($p) >= 2) {
        $origin[$p[0]] = (int)$p[1];
    }
}

// ===== SEARCH =====
$se = extractBlock($content, 'SEREFERRALS');
$search = [];

foreach (explode("\n", $se) as $line) {
    $p = preg_split('/\s+/', trim($line));
    if (count($p) >= 2) {
        $search[$p[0]] = (int)$p[1];
    }
}
arsort($search);
$topSearch = array_slice($search, 0, 5, true);

// ===== REFERRERS =====
$rf = extractBlock($content, 'PAGEREFS');
$refs = [];

foreach (explode("\n", $rf) as $line) {
    $p = preg_split('/\s+/', trim($line));
    if (count($p) >= 2) {
        if ($p[0] == '-') continue;
        $refs[$p[0]] = (int)$p[1];
    }
}

$domains = [];

foreach ($refs as $url => $hits) {
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) continue;

    if (strpos($host, 'facebook') !== false) $host = 'facebook.com';
    elseif (strpos($host, 'threads') !== false) $host = 'threads.net';
    elseif (strpos($host, 'telegram') !== false || strpos($host, 't.me') !== false) $host = 'telegram';
    elseif (strpos($host, 'google') !== false) $host = 'google';
    elseif (strpos($host, 'yandex') !== false) $host = 'yandex';

    $domains[$host] = ($domains[$host] ?? 0) + $hits;
}

arsort($domains);
$topDomains = array_slice($domains, 0, 10, true);

// ===== DAY STATISTICS =====
$dayBlock = extractBlock($content, 'DAY');
$days = [];

foreach (explode("\n", $dayBlock) as $line) {
    $line = trim($line);
    if (empty($line)) continue;
    
    $p = preg_split('/\s+/', $line);
    if (count($p) >= 5) {
        $date = $p[0]; // например 20260321
        $days[$date] = [
            'date'      => substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2),
            'pages'     => (int)$p[1],
            'hits'      => (int)$p[2],
            'bandwidth' => (int)$p[3],
            'visits'    => (int)$p[4],
        ];
    }
}
krsort($days);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Stats</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet"><!-- v5.3.8 -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body class="bg-light">

    <div class="container my-4">

        <h3>📊 Статистика</h3>

        <!-- 1. Выбор файла (без изменений) -->
        <div class="mb-3">
            <?php foreach ($fileMap as $key => $f): ?>
                <?php if ($key == $selectedKey): ?>
                    <span class="btn btn-primary btn-sm fw-bold px-3">
                        <strong><?= $key ?></strong>
                    </span>
                <?php else: ?>
                    <a href="?file=<?= $key ?>" class="btn btn-outline-primary btn-sm">
                        <?= $key ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- KPI -->
        <div class="row g-3 mb-4">
            <?php
            $kpis = [
                '👥 Unique' => $totalUnique,
                '🚶 Visits' => $totalVisits,
                '📄 Pages' => $totalPages,
                '⚡ Hits' => $totalHits,
                '🤖 Robots' => $totalRobotHits,
                '🌐 Traffic' => formatBytes($totalBandwidth),
            ];
            foreach ($kpis as $k => $v):
            ?>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <div><?= $k ?></div>
                            <div class="fw-bold"><?= is_numeric($v) ? nf($v) : $v ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ====================== ВТОРАЯ СТРОКА ====================== -->
        <!-- Day statistics + Visit pages -->
        <div class="row g-4 mb-4">
            
            <!-- Day Statistics (широкая колонка) -->
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-body">
                        <h5>📅 Day statistics</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th class="text-end">Pages</th>
                                        <th class="text-end">Hits</th>
                                        <th class="text-end">Visits</th>
                                        <th class="text-end">Bandwidth</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($days as $d): ?>
                                    <tr>
                                        <td><strong><?= $d['date'] ?></strong></td>
                                        <td class="text-end"><?= nf($d['pages']) ?></td>
                                        <td class="text-end"><?= nf($d['hits']) ?></td>
                                        <td class="text-end"><?= nf($d['visits']) ?></td>
                                        <td class="text-end"><?= formatBytes($d['bandwidth']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visit pages -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5>📄 Visit pages</h5>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($topVizitPages as $u => $h): ?>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span><?= htmlspecialchars($u) ?></span>
                                    <span class="badge bg-success"><?= nf($h) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====================== ТРЕТЬЯ СТРОКА ====================== -->
        <div class="row g-4">

            <!-- 1-я колонка: Not found pages -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5>⚠️ Not found pages</h5>
                        <ul class="list-group list-group-flush">
                        <?php foreach ($topPages404 as $u => $h): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><?= htmlspecialchars($u) ?></span>
                                <span class="badge bg-danger"><?= nf($h) ?></span>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 2-я колонка: Errors + Robots -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5>❗ Errors</h5>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($topErrors as $c => $n): ?>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span><?= $c ?></span>
                                    <span class="badge bg-danger"><?= nf($n) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5>🤖 Robots</h5>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($topRobots as $b => $h): ?>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span><?= $b ?></span>
                                    <span class="badge bg-secondary"><?= nf($h) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 3-я колонка: Sources + Refferers + Search engines -->
            <div class="col-lg-4">
                
                <!-- Sources -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5>🌐 Sources</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">Direct <span class="badge bg-primary"><?= nf($origin['From0'] ?? 0) ?></span></li>
                            <li class="list-group-item d-flex justify-content-between">Search <span class="badge bg-success"><?= nf($origin['From2'] ?? 0) ?></span></li>
                            <li class="list-group-item d-flex justify-content-between">Referrer <span class="badge bg-warning text-dark"><?= nf($origin['From3'] ?? 0) ?></span></li>
                        </ul>
                    </div>
                </div>

                <!-- Refferers -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5>🔗 Refferers</h5>
                        <ul class="list-group list-group-flush">
                        <?php foreach ($topDomains as $d => $h): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><?= $d ?></span>
                                <span class="badge bg-warning text-dark"><?= nf($h) ?></span>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Search engines -->
                <div class="card">
                    <div class="card-body">
                        <h5>🔍 Search engines</h5>
                        <ul class="list-group list-group-flush">
                        <?php foreach ($topSearch as $e => $h): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><?= $e ?></span>
                                <span class="badge bg-success"><?= nf($h) ?></span>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>