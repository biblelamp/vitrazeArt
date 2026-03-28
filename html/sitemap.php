<?php
// sitemap.php version 0.2 by 27-Mar-26

header('Content-Type: application/xml; charset=utf-8');

require_once __DIR__ . '/functions.php';

$baseUrl = 'https://vitrazeart.cz';
$nick_idx = 1;
$url_event_idx = 5;
$url_report_idx = 3;

// static pages
$urls = [
    '/',
    '/reports',
    '/authors'
];

// add all authors pages
$authors = readBlocks('data/authors.txt');
foreach ($authors as $item) {
    $nickname = explode(" ", $item[$nick_idx] ?? '') ?? [];
    if (count($nickname) == 1) {
        $urls[] = '/authors/' . urlencode($nickname[0]);
    }
}

// TODO add events pages
$events = readBlocks('data/events.txt');
foreach ($events as $item) {
    if (str_starts_with($item[$url_event_idx], '/events')) {
        $urls[] = $item[$url_event_idx];
    }
}

// add all reports pages
$reports = readBlocks('data/reports.txt');
foreach ($reports as $item) {
    if (str_starts_with($item[$url_report_idx], '/reports')) {
        $urls[] = $item[$url_report_idx];
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