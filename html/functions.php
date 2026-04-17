<?php
// functions.php version 0.13 by 17-Apr-26

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
 *   YYYY-MM-DD-DD1
 *   YYYY-M-D-D1
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
 * Return last day from date string for filtering future events.
 *
 * Supported formats:
 *   2026-04-01           → 2026-04-01
 *   2026-4-1             → 2026-04-01
 *   2026-04-01,05        → 2026-04-05
 *   2026-04-01-05        → 2026-04-05
 *   2026-4-1,5           → 2026-04-05
 *   2026-4-1-5           → 2026-04-05
 */
function getEffectiveDate(string $dateStr): ?string {
    $dateStr = trim($dateStr);
    if (empty($dateStr)) {
        return null;
    }

    // Leave only date part (remove time if present)
    $datePart = preg_split('/\s+/', $dateStr, 2)[0];

    // Unified regex for all variants
    if (!preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})(?:[,-](\d{1,2}))?$/', $datePart, $m)) {
        throw new InvalidArgumentException("Invalid date format: $datePart");
    }

    $year   = (int)$m[1];
    $month  = str_pad($m[2], 2, '0', STR_PAD_LEFT);
    $day    = str_pad($m[3], 2, '0', STR_PAD_LEFT);
    $endDay = $m[4] ?? null;

    // If we have end day (either via comma or hyphen)
    if ($endDay !== null) {
        $endDay = str_pad($endDay, 2, '0', STR_PAD_LEFT);
        return sprintf('%04d-%s-%s', $year, $month, $endDay);
    }

    // Single date
    return sprintf('%04d-%s-%s', $year, $month, $day);
}
/**
 * Formats a date or date range into Russian readable format.
 *
 * Supported input formats:
 * - YYYY                  → "2025"
 * - YYYY-MM               → "Апрель 2025"
 * - YYYY-MM-DD            → "1 апреля"
 * - YYYY-M-D              → "1 апреля"
 * - YYYY-MM-DD-DD         → "1-5 апреля"     (range with hyphen)
 * - YYYY-M-D-D            → "1-5 апреля"
 * - YYYY-MM-DD,DD         → "1 и 5 апреля"   (separate days with "и")
 * - YYYY-M-D,D            → "1 и 5 апреля"
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

    // Remove time if present
    $datePart = preg_split('/\s+/', $dateStr, 2)[0];

    // === YYYY only ===
    if (preg_match('/^\d{4}$/', $datePart)) {
        return $datePart;
    }

    // === YYYY-MM only ===
    if (preg_match('/^\d{4}-\d{1,2}$/', $datePart)) {
        [$year, $month] = explode('-', $datePart);
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);

        if (!isset($monthsCapitalized[$month])) {
            throw new InvalidArgumentException("Invalid date format: $datePart. Expected YYYY-MM");
        }

        return $monthsCapitalized[$month] . ' ' . $year;
    }

    // === Full date with optional range: YYYY-M-D[-|,]D ===
    if (!preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})(?:([-,])(\d{1,2}))?$/', $datePart, $m)) {
        throw new InvalidArgumentException("Invalid date format: $datePart. Expected YYYY-MM-DD or range");
    }

    $year       = (int)$m[1];
    $monthNum   = str_pad($m[2], 2, '0', STR_PAD_LEFT);
    $day1       = (int)$m[3];
    $separator  = $m[4] ?? null;     // '-' or ','
    $day2       = isset($m[5]) ? (int)$m[5] : null;

    if (!isset($months[$monthNum])) {
        throw new InvalidArgumentException("Invalid month in date: $datePart");
    }

    $monthName   = $months[$monthNum];
    $currentYear = date('Y');

    // Build result
    if ($day2 === null) {
        // Single day
        $result = "$day1 $monthName";
    } elseif ($separator === '-') {
        // Range: 1-5 апреля
        $result = ($day1 === $day2) 
            ? "$day1 $monthName" 
            : "$day1-$day2 $monthName";
    } else {
        // Separate days with comma: 1 и 5 апреля
        $result = "$day1 и $day2 $monthName";
    }

    // Add year if not current
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
 * @return array Sorted array (by date descending)
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
 * Generate a URL based on the date, section, name, and file extension.
 * 
 * Supported date formats:
 *   2026-04-01
 *   2026-04-01,05
 *   2026-04-01-05
 *   2026-4-1
 *   2026-4-1,5
 *   2026-4-1-5
 * 
 * Uses the first (start) date for URL generation.
 * 
 * @param string $date
 * @param string $section
 * @param string $name
 * @param string|null $ext  extension for jpg images (with or without dot) or null for no extension
 * @return string
 */
function generateUrl(string $date, string $section, string $name, ?string $ext = null): string {
    $dateStr = trim($date);
    if (empty($dateStr)) {
        throw new InvalidArgumentException("Date cannot be empty");
    }

    // Extract only the date part (before space or time)
    $datePart = preg_split('/\s+/', $dateStr, 2)[0];

    // Unified regex to support both comma and hyphen range
    // Captures only the main date (YYYY-MM-DD)
    if (!preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})(?:[,-]\d{1,2})?$/', $datePart, $parts)) {
        throw new InvalidArgumentException("Invalid date format: $date. Expected YYYY-MM-DD or range");
    }

    $year  = (int)$parts[1];
    $month = (int)$parts[2];
    $day   = (int)$parts[3];

    // Форматируем с ведущими нулями
    $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
    $dayStr   = str_pad($day, 2, '0', STR_PAD_LEFT);

    // Flat URL with extension (example: /section/2026-04-05-name.jpg)
    if ($ext !== null && $ext !== '') {
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
/**
 * Parse Markdown tags in the given text and convert them to HTML.
 * Supported Markdown features:
 * - Horizontal separator: `---` → `<hr>`
 * - Headings: `#` to `######` → `<h1>` to `<h6>`
 * - Blockquotes: Lines starting with `>` → wrapped in `<blockquote>`
 * - Links: `[name](url)` → `<a href="url">name</a>`
 * - Bold: `**text**` → `<strong>text</strong>`
 * - Italic: `*text*` → `<em>text</em>`
 * Note: The function uses recursion to handle nested blockquotes and Markdown inside them.
 * @param string $text Input text with Markdown
 * @return string HTML output with Markdown converted to HTML tags  
 */ 
function parseMarkdown($text) {
    // Horizontal separator
    $text = preg_replace('/\n*\s*---\s*\n*/', '<hr>', $text);

    // ==================== HEADINGS H1-H6 ====================
    $text = preg_replace_callback(
        '/^(#{1,6})\s+(.+?)$/m',
        function ($matches) {
            $level = strlen($matches[1]);        // количество #
            $content = trim($matches[2]);

            // Inline Markdown внутри заголовка
            // Links
            $content = preg_replace_callback('/\[(.*?)\]\((.*?)\)/', function ($m) {
                $name = $m[1];
                $url  = $m[2];
                return '<a href="' . $url . '"' . 
                       (str_starts_with($url, 'http') ? ' target="_blank" rel="noopener"' : '') . 
                       '>' . $name . '</a>';
            }, $content);

            // Bold **text**
            $content = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $content);

            // Italic *text*
            $content = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $content);

            return "<h{$level} class=\"mt-4 mb-3\">{$content}</h{$level}>";
        },
        $text
    );

    // ==================== BLOCKQUOTES ====================
    $text = preg_replace_callback(
        '/(?:^|\n)(>.*(?:\n>.*)*)/m',
        function ($matches) {
            $quote = $matches[1];
            $quote = preg_replace('/^>\s?/m', '', $quote);
            $quote = parseMarkdown($quote);   // рекурсия для поддержки markdown внутри цитаты

            return '<blockquote class="mb-0 bg-light p-3 pe-5 border-start rounded-3 border-4 d-inline-block position-relative">' 
                   . $quote 
                   . '<i class="bi bi-quote position-absolute top-0 end-0 me-2 mt-2 fs-4 text-muted opacity-50"></i></blockquote>';
        },
        $text
    );

    // ==================== INLINE ELEMENTS ====================

    // Links [name](url)
    $text = preg_replace_callback('/\[(.*?)\]\((.*?)\)/', function ($matches) {
        $name = $matches[1];
        $url  = $matches[2];
        return '<a href="' . $url . '"' . 
               (str_starts_with($url, 'http') ? ' target="_blank" rel="noopener"' : '') . 
               '>' . $name . '</a>';
    }, $text);

    // Bold **text**
    $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);

    // Italic *text*
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