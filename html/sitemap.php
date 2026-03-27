<?php
// sitemap.php version 0.1 by 27-Mar-26

header('Content-Type: application/xml; charset=utf-8');

require_once __DIR__ . '/functions.php';

$baseUrl = 'https://vitrazeart.cz';

// static pages
$urls = [
    '/',
    '/reports',
    '/authors'
];

// add all authors pages
$authors = readBlocks('data/authors.txt');
foreach ($authors as $item) {
    $nickname = explode(" ", $item[1] ?? '') ?? [];
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