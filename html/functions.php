<?php
// functions.php version 0.5 by 29-Mar-26

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
// parse Markdown
function parseMarkdown($text) {
    // horizontal separator (---)
    $text = preg_replace('/\n*\s*---\s*\n*/', '<hr>', $text);

    // blockquote (bloks begins with >)
    $text = preg_replace_callback(
        '/(?:^|\n)(>.*(?:\n>.*)*)/m',
        function ($matches) {
            $quote = $matches[1];

            // убираем символы > в начале каждой строки
            $quote = preg_replace('/^>\s?/m', '', $quote);

            // рекурсивно парсим markdown внутри цитаты (чтобы работали жирный, курсив, ссылки и т.д.)
            $quote = parseMarkdown($quote);   // внимание: рекурсия!

            return '<figure class="my-3"><blockquote class="border-0 bg-light p-3 rounded-3">' . $quote . '</blockquote></figure>';
        },
        $text
    );

    // links [name](url)
    $text = preg_replace_callback('/\[(.*?)\]\((.*?)\)/', function ($matches) {
        $name = $matches[1];
        $url = $matches[2];
        return '<a href="' . $url . '"' . (str_starts_with($url, 'http')? ' target="_blank"' : '') . '>' . $name . '</a>';
    }, $text);

    // italic **text**
    $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);

    // bold *text*
    $text = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $text);

    return $text;
}
// clean Markdown
function cleanMarkdown($text) {
    $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);     // **жирный**
    $text = preg_replace('/\*(.+?)\*/', '$1', $text);         // *курсив*
    $text = preg_replace('/__(.+?)__/', '$1', $text);         // __жирный__
    $text = preg_replace('/_(.+?)_/', '$1', $text);           // _курсив_
    $text = preg_replace('/`(.+?)`/', '$1', $text);           // `код`
    $text = preg_replace('/\[(.+?)\]\(.+?\)/', '$1', $text);  // [текст](ссылка)
    $text = preg_replace('/^#+\s*/m', '', $text);             // заголовки # ## ###
    $text = preg_replace('/^\s*[-*+]\s+/m', '', $text);       // списки
    return trim($text);
}
// convert Name Lastname -> Name L.
function shortName(string $fullName): string {
    $fullName = trim($fullName);

    if ($fullName === '') {
        return '';
    }

    // divide by space(s)
    $parts = preg_split('/\s+/', $fullName);

    $firstName = $parts[0];

    // if only one name
    if (count($parts) < 2 || (count($parts) > 1 && mb_strlen($parts[1]) == 1)) {
        return $firstName;
    }

    $lastName = $parts[1];

    // get first letter of lastname
    $initial = mb_substr($lastName, 0, 1);

    return $firstName . ' ' . $initial . '.';
}
// 
function getPageWordForm($number): string {
    $number = abs($number) % 100;
    $lastDigit = $number % 10;

    if ($number >= 11 && $number <= 19) {
        return 'страниц';
    }

    if ($lastDigit == 1) {
        return 'страница';
    }

    if ($lastDigit >= 2 && $lastDigit <= 4) {
        return 'страницы';
    }

    return 'страниц';
}
?>