<?php
// sitemap.php version 0.5 by 7-Apr-26

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
    '/authors/',
    '/gallery/'
];
$static_pages = count($urls);

// add events pages
$events = readBlocks('data/events.txt');
foreach ($events as $item) {
    $date_time = explode(" ", $item[0] ?? '') ?? [];
    $name      = $item[$name_event_idx] ?? '';
    $urls[]    = generateUrl($date_time[0], 'events', $name);
}
$event_pages = count($urls) - $static_pages;

// add reports pages
$reports = readBlocks('data/reports.txt');
foreach ($reports as $item) {
    $date   = $item[0] ?? '';
    $name   = $item[$name_report_idx] ?? '';
    $urls[] = generateUrl($date, 'reports', $name);
}
$report_pages = count($urls) - $static_pages - $event_pages;

// add authors pages
$authors = readBlocks('data/authors.txt');
foreach ($authors as $item) {
    $nickname = explode(" ", $item[$nick_idx] ?? '') ?? [];
    if (count($nickname) == 1) {
        $urls[] = '/authors/' . urlencode($nickname[0]);
    }
}
$author_pages = count($urls) - $static_pages - $event_pages - $report_pages;

// add gallery pages
$iterator = new DirectoryIterator('data/gallery/');
foreach ($iterator as $file) {
    if ($file->isDir() && !$file->isDot()) {
        $urls[] = '/gallery/' . $file->getFilename();
    }
}
$gallery_pages = count($urls) - $static_pages - $event_pages - $report_pages - $author_pages;

// TODO add galleries items

// XML header
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- <?= count($urls) ?> pages -->
    <!-- <?= $static_pages ?> static pages -->
    <!-- <?= $event_pages ?> event pages -->
    <!-- <?= $report_pages ?> report pages -->
    <!-- <?= $author_pages ?> author pages -->
    <!-- <?= $gallery_pages ?> gallery pages -->
<?php foreach ($urls as $url): ?>
    <url>
        <loc><?= htmlspecialchars($baseUrl . $url) ?></loc>
        <changefreq>weekly</changefreq>
        <priority><?= $url === '/' ? '1.0' : '0.8' ?></priority>
    </url>
<?php endforeach; ?>

</urlset>