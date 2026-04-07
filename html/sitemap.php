<?php
// sitemap.php version 0.4 by 6-Apr-26

header('Content-Type: application/xml; charset=utf-8');

require_once __DIR__ . '/functions.php';

$baseUrl = 'https://vitrazeart.cz';
$nick_idx = 1;
$name_event_idx = 4;
$name_report_idx = 3;

// static pages
$urls = [
    '/',
    '/about/',
    '/events/',
    '/reports/',
    '/authors/'
];

// add events pages
$events = readBlocks('data/events.txt');
foreach ($events as $item) {
    $date_time = explode(" ", $item[0] ?? '') ?? [];
    $name      = $item[$name_event_idx] ?? '';
    $urls[]    = generateUrl($date_time[0], 'events', $name);
}

// add reports pages
$reports = readBlocks('data/reports.txt');
foreach ($reports as $item) {
    $date   = $item[0] ?? '';
    $name   = $item[$name_report_idx] ?? '';
    $urls[] = generateUrl($date, 'reports', $name);
}

// add authors pages
$authors = readBlocks('data/authors.txt');
foreach ($authors as $item) {
    $nickname = explode(" ", $item[$nick_idx] ?? '') ?? [];
    if (count($nickname) == 1) {
        $urls[] = '/authors/' . urlencode($nickname[0]);
    }
}

// XML header
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

<?php foreach ($urls as $url): ?>
    <url>
        <loc><?= htmlspecialchars($baseUrl . $url) ?></loc>
        <changefreq>weekly</changefreq>
        <priority><?= $url === '/' ? '1.0' : '0.8' ?></priority>
    </url>
<?php endforeach; ?>

</urlset>