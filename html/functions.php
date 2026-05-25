<?php
// functions.php version 0.16 by 25-May-26

/**
 * Return array of slug from cookie 'seen_events'
 */
function getSeenSlugs(): array {
    if (empty($_COOKIE['seen_events'])) return [];
    return json_decode($_COOKIE['seen_events'], true) ?? [];
}
/**
 * Clean seen_slugs — keeps only the actual ones
 * @param array $seenSlugs — array of seen slugs
 * @param array $allEvents — array from readBlocks('data/events.txt')
 * @return array
 */
function cleanSeenSlugs(array $seenSlugs, array $allEvents): array {
    $actualSlugs = array_map(fn($item) => $item[4] ?? '', $allEvents);
    return array_values(array_intersect($seenSlugs, $actualSlugs));
}
/**
 * Save list of slug in cookie for 1 year
 * @param array $slugs — array of slugs to save
 */
function saveSeenSlugs(array $slugs): void {
    setcookie(
        'seen_events',
        json_encode(array_values($slugs)),
        time() + 60 * 60 * 24 * 365,
        '/',
        '',
        true,
        true
    );
}
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
 * Filter the list of events to exclude past events.
 *
 * Uses getEventDateRange() to support all date formats including ranges.
 */
function filterByDate(array $data): array {
    $today = date('Y-m-d');
    $result = [];

    foreach ($data as $item) {
        if (empty($item) || !isset($item[0]) || trim($item[0]) === '') {
            continue;
        }

        [$startDate, $endDate] = getEventDateRange($item[0]);

        // Use end date for filtering: show event if it hasn't finished yet
        $effectiveDate = !empty($endDate) ? $endDate : $startDate;

        if ($effectiveDate !== '' && $effectiveDate >= $today) {
            $result[] = $item;
        }
    }
    return $result;
}
/**
 * Parses the date string from events.txt and returns the start and end dates.
 *
 * Supported formats:
 *   - "2026-04-18 19:00"           → single day
 *   - "2026-04-24,26 19:00"        → range with comma (same month)
 *   - "2026-05-16-17 10:00"        → range with hyphen (same month)
 *   - "2026-05-21,23,06-03 19:00"  → days + another month
 *
 * @param string $dateTimeLine  Raw first line from event, e.g. "2026-04-24,26 19:00"
 * @return array                [start_date, end_date] in YYYY-MM-DD format
 */
function getEventDateRange(string $dateTimeLine): array {
    if (empty($dateTimeLine)) {
        return ['', ''];
    }

    $datePart = trim(explode(' ', $dateTimeLine)[0]);
    $year = substr($datePart, 0, 4);

    // Case: hyphenated range YYYY-MM-DD-DD (exactly 3 hyphens)
    if (substr_count($datePart, '-') === 3 && strpos($datePart, ',') === false) {
        $parts = explode('-', $datePart);
        $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
        $start = "$year-$month-" . str_pad($parts[2], 2, '0', STR_PAD_LEFT);
        $end   = "$year-$month-" . str_pad($parts[3], 2, '0', STR_PAD_LEFT);
        return [$start, $end];
    }

    // Case: comma-separated (may include cross-month entries like 06-03)
    if (strpos($datePart, ',') !== false) {
        // Strip YYYY- prefix, split by comma
        $withoutYear = substr($datePart, 5); // "05-21,23,06-03"
        $parts = explode(',', $withoutYear);

        $baseMonth = str_pad(substr($parts[0], 0, 2), 2, '0', STR_PAD_LEFT);

        // Parse start date from first part (MM-DD)
        $firstDay = str_pad(substr($parts[0], 3), 2, '0', STR_PAD_LEFT);
        $start = "$year-$baseMonth-$firstDay";

        // Parse end date from last part
        $last = end($parts);
        if (strpos($last, '-') !== false) {
            // Cross-month: "06-03" → full date
            [$endMonth, $endDay] = explode('-', $last);
            $end = "$year-" . str_pad($endMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($endDay, 2, '0', STR_PAD_LEFT);
        } else {
            // Same month: just a day number
            $end = "$year-$baseMonth-" . str_pad($last, 2, '0', STR_PAD_LEFT);
        }

        return [$start, $end];
    }

    // Case: single date YYYY-MM-DD
    return [$datePart, $datePart];
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
 * - YYYY-MM-DD,DD,MM-DD   → "1, 5 апреля, 3 июня"
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
            throw new InvalidArgumentException("Invalid date format: $datePart");
        }
        return $monthsCapitalized[$month] . ' ' . $year;
    }

    $year = substr($datePart, 0, 4);
    $currentYear = date('Y');
    $withoutYear = substr($datePart, 5); // strip "YYYY-"

    // === Hyphenated range: MM-DD-DD (3 hyphens total in full string) ===
    if (substr_count($datePart, '-') === 3 && strpos($datePart, ',') === false) {
        $parts = explode('-', $datePart);
        $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
        $day1  = (int)$parts[2];
        $day2  = (int)$parts[3];
        $result = ($day1 === $day2)
            ? "$day1 " . $months[$month]
            : "$day1-$day2 " . $months[$month];
        if ($year != $currentYear) $result .= " $year";
        return $result;
    }

    // === Comma-separated (same or cross-month) ===
    if (strpos($datePart, ',') !== false) {
        $parts = explode(',', $withoutYear); // e.g. ["05-21", "23", "06-03"]

        $baseMonth = str_pad(substr($parts[0], 0, 2), 2, '0', STR_PAD_LEFT);
        $baseDay   = (int)substr($parts[0], 3);

        // Group days by month
        $grouped = []; // ['05' => [21, 23], '06' => [3]]
        $currentMonth = $baseMonth;

        foreach ($parts as $part) {
            if (strpos($part, '-') !== false) {
                // New month specified: "06-03"
                [$m, $d] = explode('-', $part);
                $currentMonth = str_pad($m, 2, '0', STR_PAD_LEFT);
                $grouped[$currentMonth][] = (int)$d;
            } else {
                // Day in current month
                $grouped[$currentMonth][] = (int)$part;
            }
        }

        // Format each month group
        $parts_out = [];
        foreach ($grouped as $month => $days) {
            $dayList = implode(', ', $days);
            $parts_out[] = $dayList . ' ' . $months[$month];
        }

        $result = implode(', ', $parts_out);
        if ($year != $currentYear) $result .= " $year";
        return $result;
    }

    // === Single date: MM-DD ===
    if (preg_match('/^(\d{1,2})-(\d{1,2})$/', $withoutYear, $m)) {
        $month = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        $day   = (int)$m[2];
        if (!isset($months[$month])) {
            throw new InvalidArgumentException("Invalid month in date: $datePart");
        }
        $result = "$day " . $months[$month];
        if ($year != $currentYear) $result .= " $year";
        return $result;
    }

    throw new InvalidArgumentException("Invalid date format: $datePart");
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
 * Generate a URL based on the event date line.
 * 
 * Uses getEventDateRange() to correctly handle all date formats:
 *   - "2026-04-18 19:00"
 *   - "2026-04-24,26 19:00"
 *   - "2026-05-16-17 10:00"
 * 
 * Always uses the START date for URL generation.
 * 
 * @param string $dateTimeLine  First line of event (with date and time)
 * @param string $section       Section name (events, images/events, etc.)
 * @param string $name          Event code/slug
 * @param string|null $ext      File extension (e.g. 'jpg') or null for folder URL
 * @return string               Generated URL
 */
function generateUrl(string $dateTimeLine, string $section, string $name, ?string $ext = null): string {
    if (empty($dateTimeLine)) {
        throw new InvalidArgumentException("Date line cannot be empty");
    }

    // Get start and end dates using the shared function
    [$startDate, $endDate] = getEventDateRange($dateTimeLine);

    if (empty($startDate)) {
        throw new InvalidArgumentException("Could not parse date from: $dateTimeLine");
    }

    // Use only the start date for URL
    $datePart = $startDate;   // YYYY-MM-DD

    [$year, $month, $day] = explode('-', $datePart);

    $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
    $dayStr   = str_pad($day, 2, '0', STR_PAD_LEFT);

    // With file extension (for images)
    if ($ext !== null && $ext !== '') {
        if (!str_starts_with($ext, '.')) {
            $ext = '.' . $ext;
        }
        return "/{$section}/{$year}-{$monthStr}-{$dayStr}-{$name}" . $ext;
    }

    // Hierarchical URL (for event pages)
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