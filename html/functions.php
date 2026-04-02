<?php
// functions.php version 0.6 by 1-Apr-26

// reading blocks of lines from a file (delimiter: empty line)
function readBlocks($filePath) {
    if (!file_exists($filePath)) 
        return [];

    $content = file_get_contents($filePath);
    $blocks = preg_split('/\n\s*\n/', trim($content), -1, PREG_SPLIT_NO_EMPTY);
    $items = [];
    foreach ($blocks as $block) {
        $lines = array_filter(array_map('trim', explode("\n", $block)));
        $items[] = $lines;
    }
    return $items;
}
// filter list of events to exclude past events
// support formats:
//   YYYY-MM-DD
//   YYYY-M-D
//   YYYY-MM-DD,DD1
//   YYYY-M-D,D1
function filterByDate(array $data): array {
    $today = date('Y-m-d');
    $result = [];

    foreach ($data as $item) {
        if (empty($item) || !isset($item[0]) || trim($item[0]) === '') {
            continue;
        }

        $effectiveDate = getEffectiveDate($item[0]);

        if ($effectiveDate !== null && $effectiveDate >= $today) {
            $result[] = $item;
        }
    }
    return $result;
}
// return last day in format YYYY-MM-DD
// 2026-04-01,05 → 2026-04-05
// 2026-04-01    → 2026-04-01
function getEffectiveDate(string $dateStr): ?string {
    $dateStr = trim($dateStr);
    if (empty($dateStr)) {
        return null;
    }

    // Убираем время (всё после первого пробела)
    $datePart = preg_split('/\s+/', $dateStr, 2)[0];

    // Разделяем на основную дату и возможный диапазон дней
    $parts = array_map('trim', explode(',', $datePart));
    $mainPart = $parts[0];
    $endDay   = $parts[1] ?? null;

    // Нормализация даты (поддержка 2026-3-6 и 2026-03-06)
    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $mainPart, $m)) {
        $normalized = sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
    } else {
        return null;
    }

    $date = DateTime::createFromFormat('Y-m-d', $normalized);
    if (!$date) {
        return null;
    }

    if ($endDay !== null) {
        $endDay = (int)$endDay;
        $year   = $date->format('Y');
        $month  = $date->format('m');
        return sprintf('%s-%s-%02d', $year, $month, $endDay);
    }

    return $date->format('Y-m-d');
}
// date conversion 
// Поддерживает:
//   YYYY-MM-DD     →  1 апреля
//   YYYY-M-D       →  1 апреля
//   YYYY-MM-DD,DD1 →  1-5 апреля
//   YYYY-M-D,D1    →  1-5 апреля
function formatDateRu($dateStr) {
    $months = [
        '01' => 'января',  '02' => 'февраля', '03' => 'марта', '04' => 'апреля',
        '05' => 'мая',     '06' => 'июня',    '07' => 'июля',  '08' => 'августа',
        '09' => 'сентября','10'=> 'октября',  '11'=> 'ноября', '12'=> 'декабря'
    ];

    // Нормализуем строку: убираем лишние пробелы и разделяем по запятой
    $dateStr = trim($dateStr);
    $parts = array_map('trim', explode(',', $dateStr));
    $mainDate = $parts[0];
    $endDay   = $parts[1] ?? null;

    // Приводим дату к формату с ведущими нулями (Y-m-d)
    $normalized = preg_replace_callback('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', 
        function ($m) {
            return sprintf('%d-%02d-%02d', $m[1], $m[2], $m[3]);
        }, 
    $mainDate);

    $date = DateTime::createFromFormat('Y-m-d', $normalized);
    if (!$date) {
        return null; // incorrect date
    }

    $day   = (int)$date->format('j');
    $month = $months[$date->format('m')];
    $year  = $date->format('Y');
    $currentYear = date('Y');

    // Формируем строку
    if ($endDay !== null) {
        $endDay = (int)$endDay;
        $range = ($day === $endDay) ? $day : $day . '-' . $endDay;
        $result = $range . ' ' . $month;
    } else {
        $result = $day . ' ' . $month;
    }

    if ($year != $currentYear) {
        $result .= ' ' . $year;
    }

    return $result;
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