<?php
// functions.php version 0.11 by 11-Apr-26

/**
 * Reading blocks of lines from a file (delimiter: empty line)
 *
 * @param string $filePath
 * @return array of string
 */
function readBlocks($filePath): array {
    if (!file_exists($filePath)) {
        return [];
    }

    $content = file_get_contents($filePath);
    $blocks = preg_split('/\n\s*\n/', trim($content), -1, PREG_SPLIT_NO_EMPTY);
    $items = [];
    foreach ($blocks as $block) {
        $lines = array_filter(array_map('trim', explode("\n", $block)));
        $items[] = $lines;
    }

    return $items;
}
/**
 * Filter the list of events to exclude past events
 *
 * support formats:
 *   YYYY-MM-DD
 *   YYYY-M-D
 *   YYYY-MM-DD,DD1
 *   YYYY-M-D,D1
 */
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
/**
 * Return last day from YYYY-MM-DD or YYYY-MM-DD,DD
 *
 * 2026-04-01,05 → 2026-04-05
 * 2026-04-01    → 2026-04-01
 */
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
/**
 * Formats a date or date range into Russian readable format.
 *
 * Supported input formats:
 * - YYYY-MM-DD          → "1 апреля"
 * - YYYY-M-D            → "1 апреля"
 * - YYYY-MM-DD,DD2      → "1-5 апреля"
 * - YYYY-M-D,D2         → "1-5 апреля"
 * - YYYY-MM             → "Апрель 2025" (month name capitalized)
 * - YYYY                → "2025"
 *
 * If the year is the current year, it is omitted.
 *
 * @param string $dateStr Input date string
 * @return string|null Formatted date or null on invalid input
 */
function formatDateRu(string $dateStr): ?string {
    $months = [
        '01' => 'января', '02' => 'февраля', '03' => 'марта', '04' => 'апреля',
        '05' => 'мая',   '06' => 'июня',    '07' => 'июля',  '08' => 'августа',
        '09' => 'сентября', '10' => 'октября', '11' => 'ноября', '12' => 'декабря'
    ];

    $monthsCapitalized = [
        '01' => 'Январь', '02' => 'Февраль', '03' => 'Март',     '04' => 'Апрель',
        '05' => 'Май',    '06' => 'Июнь',    '07' => 'Июль',    '08' => 'Август',
        '09' => 'Сентябрь', '10' => 'Октябрь', '11' => 'Ноябрь', '12' => 'Декабрь'
    ];

    $dateStr = trim($dateStr);
    if (empty($dateStr)) {
        return null;
    }

    // Split by comma for range support (e.g. "2025-04-01,05")
    $parts = array_map('trim', explode(',', $dateStr, 2));
    $mainDate = $parts[0];
    $endDay = $parts[1] ?? null;

    // Determine input format
    if (preg_match('/^\d{4}$/', $mainDate)) {
        // YYYY only
        return $mainDate;
    }

    if (preg_match('/^\d{4}-\d{1,2}$/', $mainDate)) {
        // YYYY-MM only
        [$year, $month] = explode('-', $mainDate);
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        
        if (!isset($monthsCapitalized[$month])) {
            return null;
        }

        $result = $monthsCapitalized[$month] . ' ' . $year;
        return $result;
    }

    // Full date: YYYY-MM-DD or YYYY-M-D
    if (!preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $mainDate)) {
        return null; // invalid format
    }

    // Normalize to Y-m-d with leading zeros
    $normalized = preg_replace_callback('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', 
        fn($m) => sprintf('%d-%02d-%02d', $m[1], $m[2], $m[3]), 
        $mainDate
    );

    $date = DateTime::createFromFormat('Y-m-d', $normalized);
    if (!$date) {
        return null;
    }

    $day   = (int)$date->format('j');
    $month = $months[$date->format('m')];
    $year  = $date->format('Y');
    $currentYear = date('Y');

    // Build result
    if ($endDay !== null) {
        $endDay = (int)$endDay;
        $range = ($day === $endDay) ? $day : "$day-$endDay";
        $result = "$range $month";
    } else {
        $result = "$day $month";
    }

    if ($year != $currentYear) {
        $result .= " $year";
    }

    return $result;
}
/**
 * Sorts the gallery items array by date (newest first)
 * Supports date formats: YYYY, YYYY-MM, YYYY-MM-DD
 *
 * @param array $gallery_items  Array in format [ 'filename' => [ [header], ... ], ... ]
 * @return array                 Sorted array (by date descending)
 */
function sortGalleryItemsByDate(array $gallery_items): array {
    uasort($gallery_items, function ($a, $b) {
        $dateA = getNormalizedDate($a[0][0] ?? '');
        $dateB = getNormalizedDate($b[0][0] ?? '');

        // If dates are equal, preserve original order (stable sort)
        if ($dateA === $dateB) {
            return 0;
        }

        return $dateB <=> $dateA; // DESC - newest on top
    });

    return $gallery_items;
}
/**
 * Normalizes date from the first line of the gallery item file
 * into YYYY-MM-DD format for correct comparison
 *
 * @param string $headerLine  First line of the txt file (e.g. "bi-book-half 2023 Title...")
 * @return string             Normalized date in YYYY-MM-DD format
 */
function getNormalizedDate(string $headerLine): string {
    // Example: "bi-music-note-beamed 2025-5-15 Прогулка по Праге"
    $parts = explode(' ', trim($headerLine));
    
    // Date is always the second element
    $rawDate = $parts[1] ?? '';

    if (empty($rawDate)) {
        return '0000-00-00'; // Items without date go to the end
    }

    // Full format: YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $rawDate)) {
        $date = DateTime::createFromFormat('Y-m-d', $rawDate);
        return $date ? $date->format('Y-m-d') : '0000-00-00';
    }

    // Format: YYYY-MM
    if (preg_match('/^\d{4}-\d{1,2}$/', $rawDate)) {
        $date = DateTime::createFromFormat('Y-m', $rawDate);
        return $date ? $date->format('Y-m-01') : '0000-00-00';
    }

    // Format: YYYY only
    if (preg_match('/^\d{4}$/', $rawDate)) {
        return $rawDate . '-01-01';
    }

    // Unknown format
    return '0000-00-00';
}
/**
 * Generate a URL based on the date, section, name, and file extension
 * 
 * @param string $date
 * @param string $section
 * @param string $name
 * @param string|null $ext  Расширение (с точкой или без)
 * @return string
 */
function generateUrl(string $date, string $section, string $name, ?string $ext = null): string {
    // Extract only the date part (before comma or space)
    $datePart = explode(',', $date)[0];
    $datePart = trim(explode(' ', $datePart)[0]);

    // Strict check for YYYY-MM-DD format
    if (!preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $datePart, $parts)) {
        throw new InvalidArgumentException("Invalid date format: $date. Expected YYYY-MM-DD");
    }

    $year  = (int)$parts[1];
    $month = (int)$parts[2];
    $day   = (int)$parts[3];

    // Форматируем с ведущими нулями
    $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
    $dayStr   = str_pad($day, 2, '0', STR_PAD_LEFT);

    // Flat URL with extension (example: /section/2026-04-05-name.jpg)
    if ($ext !== null && $ext !== '') {
        // Added dot if not exists
        if (!str_starts_with($ext, '.')) {
            $ext = '.' . $ext;
        }
        return "/{$section}/{$year}-{$monthStr}-{$dayStr}-{$name}" . $ext;
    }

    // Hierarchical URL (example: /section/2026/04/05/name)
    return "/{$section}/{$year}/{$monthStr}/{$dayStr}/{$name}";
}
/**
 * Returns the path to the resized thumbnail version of the image.
 * 
 * The thumbnail is saved in a 'thumbnails' folder next to the original image.
 * The filename remains the same as the original.
 * 
 * @param string $imagePath  Full path to the original JPG file
 * @param int    $width      Desired width of the thumbnail (height is proportional)
 * @param int    $quality    JPEG quality (60-95, default 95)
 * @return string            Path to the thumbnail (or original if failed)
 */
function getThumbnail($imagePath, $width = 85, $quality = 95) {
    // root-relative → make absolute
    $absolutePath = $_SERVER['DOCUMENT_ROOT'] . $imagePath;

    $pathInfo    = pathinfo($absolutePath);
    $originalDir = $pathInfo['dirname'];
    $filename    = $pathInfo['basename'];
    $cacheDir    = $originalDir . '/thumbnails';
    $thumbPath   = $cacheDir . '/' . $filename;

    // If thumbnail already exists - return it
    if (file_exists($thumbPath)) {
        return str_replace($_SERVER['DOCUMENT_ROOT'], '', $thumbPath);
    }

    if (!file_exists($absolutePath)) {
        error_log("getThumbnail: Original file not found: $absolutePath");
        return $imagePath;
    }

    // ==================== Create thumbnails directory ====================
    if (!is_dir($cacheDir)) {
        if (!mkdir($cacheDir, 0755, true)) {
            $error = error_get_last();
            error_log("getThumbnail: Failed to create directory '$cacheDir'. Error: " . ($error['message'] ?? 'unknown'));
            
            // Optional: show error only during development
            if (ini_get('display_errors')) {
                trigger_error("Cannot create thumbnails folder: $cacheDir<br>Check write permissions!", E_USER_WARNING);
            }
            
            return $imagePath; // fallback to original
        }
    }

    // Check if we can write to the folder
    if (!is_writable($cacheDir)) {
        error_log("getThumbnail: Directory exists but is not writable: $cacheDir");
        return $imagePath;
    }

    // ================ Create thumbnail use Imagick ===================
    try {
        $image = new Imagick($absolutePath);

        // The best resize method
        $image->resizeImage($width, 0, Imagick::FILTER_LANCZOS, 1);

        // Optimizaion
        $image->setImageCompression(Imagick::COMPRESSION_JPEG);
        $image->setImageCompressionQuality($quality);

        $image->writeImage($thumbPath);
        $image->destroy();

    } catch (Exception $e) {
        error_log("[getThumbnail] Imagick Error: " . $e->getMessage());
    }

    return str_replace($_SERVER['DOCUMENT_ROOT'], '', $thumbPath);
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

            return '<figure class="my-3"><blockquote class="bg-light p-3 border-start rounded-3 border-4">' . $quote . '</blockquote></figure>';
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
// get word in correct form
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