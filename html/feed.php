<?php
require_once __DIR__ . '/functions.php';

// Настройки сайта
define('SITE_URL',   'https://vitrazeart.cz');
define('SITE_TITLE', 'Пражские витражи — анонсы, репортажи, авторы');
define('SITE_DESC',  'Сообщество русскоязычных поэтов, художников и музыкантов в Праге и Чехии. Публикации, авторы, творчество и культурная жизнь.');
define('SITE_LANG',  'ru');

// Экранирование для XML
function xe(string $s): string {
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

// RFC 2822 дата из YYYY-MM-DD
function rfc_date(string $ymd): string {
    return (new DateTimeImmutable($ymd, new DateTimeZone('Europe/Prague')))
        ->format(DateTimeInterface::RSS);
}

// Читаем данные той же функцией, что и index.php
$reports = readBlocks('data/reports.txt');

// $item[0] = дата, [1] = заголовок, [2] = описание, [3] = путь
$updated = !empty($reports) ? rfc_date($reports[0][0]) : date(DateTimeInterface::RSS);

header('Content-Type: application/rss+xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:dc="http://purl.org/dc/elements/1.1/">
  <channel>
    <title><?= xe(SITE_TITLE) ?></title>
    <link><?= xe(SITE_URL) ?></link>
    <description><?= xe(SITE_DESC) ?></description>
    <language><?= SITE_LANG ?></language>
    <lastBuildDate><?= $updated ?></lastBuildDate>
    <atom:link href="<?= xe(SITE_URL) ?>/feed.xml"
               rel="self"
               type="application/rss+xml"/>
<?php foreach ($reports as $item):
    $item  = array_values($item);  // сброс индексов после array_filter в readBlocks
    $date  = $item[0] ?? '';
    $title = $item[1] ?? '';
    $desc  = $item[2] ?? '';
    $link  = SITE_URL . ($item[3] ?? '');
?>
    <item>
      <title><?= xe($title) ?></title>
      <link><?= xe($link) ?></link>
      <description><?= xe($desc) ?></description>
      <pubDate><?= rfc_date($date) ?></pubDate>
      <guid isPermaLink="true"><?= xe($link) ?></guid>
    </item>
<?php endforeach; ?>
  </channel>
</rss>