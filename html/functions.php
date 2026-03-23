<?php
// reading blocks of lines from a file (delimiter: empty line)
function readBlocks($filePath) {
    if (!file_exists($filePath)) return [];
    $content = file_get_contents($filePath);
    $blocks = preg_split('/\n\s*\n/', trim($content), -1, PREG_SPLIT_NO_EMPTY);
    $items = [];
    foreach ($blocks as $block) {
        $lines = array_filter(array_map('trim', explode("\n", $block)));
        $items[] = $lines;
    }
    return $items;
}
// filter list of announcements to exclude past events
function filterByDate(array $data): array {
    $today = date('Y-m-d');
    $result = [];
    foreach ($data as $item) {
        if (!isset($item[0])) {
            continue; // skip invalid elements
        }
        $date = $item[0];
        // keep only date >= today
        if ($date >= $today) {
            $result[] = $item;
        }
    }
    return $result;
}
// date conversion YYYY-MM-DD -> d месяц ГОД
function formatDateRu($dateStr) {
    $months = [
        '01' => 'января',
        '02' => 'февраля',
        '03' => 'марта',
        '04' => 'апреля',
        '05' => 'мая',
        '06' => 'июня',
        '07' => 'июля',
        '08' => 'августа',
        '09' => 'сентября',
        '10' => 'октября',
        '11' => 'ноября',
        '12' => 'декабря'
    ];

    $date = DateTime::createFromFormat('Y-m-d', $dateStr);
    if (!$date) {
        return null; // or throws
    }

    $day = $date->format('j');
    $month = $months[$date->format('m')];
    $year = $date->format('Y');
    $currentYear = date('Y');

    if ($year == $currentYear) {
        return "$day $month";
    } else {
        return "$day $month $year";
    }
}
// parse markdown links in text
function parseMarkdownLinks($text) {
    return preg_replace_callback(
        '/\[(.*?)\]\((.*?)\)/',
        function ($matches) {
            $label = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
            $url   = htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8');
            return '<a href="' . $url . '" target="_blank" class="text-decoration-none">' . $label . '</a>';
        },
        $text
    );
}
?>