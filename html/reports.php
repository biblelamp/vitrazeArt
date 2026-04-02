<?php
// reports.php version 0.2 by 29-Mar-26

require_once __DIR__ . '/functions.php';

// read files
$events  = filterByDate(readBlocks('data/events.txt'));
$reports = readBlocks('data/reports.txt');

// parse URL: /reports/2026/03/24/drc
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts = explode('/', $uri);

$selected_slug = null;
$report_item = null;

if (count($parts) >= 5 && $parts[0] === 'reports') {
    $year  = $parts[1] ?? '';
    $month = str_pad($parts[2] ?? '', 2, '0', STR_PAD_LEFT);
    $day   = str_pad($parts[3] ?? '', 2, '0', STR_PAD_LEFT);
    $code  = strtolower($parts[4] ?? '');

    if ($year && $month && $day && $code) {
        $selected_slug = "{$year}-{$month}-{$day}-{$code}";
        
        // Находим выбранный отчёт в списке
        foreach ($reports as $key => $item) {
            if ('/' . $uri === $item[3]) {
                $report_item = $item;
                unset($reports[$key]); // убираем из списка остальных
                break;
            }
        }
    }
}

// 404 если отчёт запрошен, но не найден
if ($selected_slug && !$report_item) {
    $report_item = [
        '404',
        'Отчёт не найден',
        'К сожалению, такого отчёта не существует…',
        '',
        ''
    ];
}

// Читаем подробный текст отчёта из отдельного файла
$report_detail = [];
if ($selected_slug) {
    $detail_path = "data/reports/{$selected_slug}.txt";
    if (file_exists($detail_path)) {
        $report_detail = readBlocks($detail_path);
    }
}

$title = $report_item 
    ? htmlspecialchars($report_item[1] ?? 'Отчёт') . ' — Пражские витражи' 
    : 'Отчёты — Пражские витражи';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Отчёты и репортажи с мероприятий сообщества Пражские витражи.">
  <title><?= $title ?></title>
  <!-- Open Graph meta tags -->
  <meta property="og:title" content="<?= $title ?>">
  <meta property="og:description" content="Отчёты и репортажи с мероприятий сообщества Пражские витражи.">
  <meta property="og:image" content="/images/logo.jpg">
  <meta property="og:url" content="https://vitrazeart.cz/reports/">
  <meta property="og:type" content="website">
  <link href="/css/bootstrap.min.css" rel="stylesheet">
  <link href="/css/style.css" rel="stylesheet">
  <link rel="stylesheet" href="/css/bootstrap-icons.min.css">
  <link rel="icon" href="/images/favicon.ico" type="image/x-icon">
  <style>
    .report-content p { margin-bottom: 1.25rem; }
    .report-card:hover { 
      transform: translateY(-3px); 
      transition: all 0.2s; 
    }
  </style>
</head>
<body class="bg-light">

  <?php include 'header.html'; ?>

  <main class="container-fluid px-4 px-lg-5 my-4 my-lg-5">
    <div class="row g-4 g-lg-5">

      <!-- Left column -->
      <div class="col-lg-8">

       <!-- Card of report -->
        <?php if ($report_item): 
            $date  = $report_item[0] ?? '';
            $title = $report_item[1] ?? 'Без названия';
        ?>
       <div class="card border-0 shadow-sm mb-5">
          <div class="card-body p-4 p-lg-4">
            <small class="text-muted"><?= formatDateRu($date) ?></small>
            <h1 class="h3 mt-2 mb-3"><?= htmlspecialchars($title) ?></h1>
            <?php if (!empty($report_detail)): ?>
              <hr class="my-4">
              <div class="report-content">
                <?php foreach ($report_detail as $block): ?>
                  <p class="mb-3"><?= nl2br(parseMarkdown(implode("\n", $block))) ?></p>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p class="text-muted">Не найден файл: <?= $detail_path ?></p>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

        <!-- Остальные отчёты — стиль как на index.php -->
        <h2 class="h3 mb-4 pb-2 border-bottom">
          <?= $report_item ? 'остальные отчёты и репортажи' : 'отчёты и репортажи' ?>
        </h2>

        <div class="list-group list-group-flush border rounded shadow-sm">
          <?php foreach ($reports as $item): 
              $date   = $item[0] ?? '';
              $title  = $item[1] ?? 'Без названия';
              $desc   = $item[2] ?? '';
              $href   = $item[3] ?? '#';
          ?>
            <a href="<?= htmlspecialchars($href) ?>" class="list-group-item list-group-item-action px-4 py-3">
              <div class="d-flex w-100 justify-content-between">
                <h5 class="mb-1"><?= htmlspecialchars($title) ?></h5>
                <small class="text-muted"><?= formatDateRu($date) ?></small>
              </div>
              <p class="mb-1 text-muted small"><?= htmlspecialchars($desc) ?></p>
            </a>
          <?php endforeach; ?>
        </div>

        <?php if ($selected_slug): ?>
          <div class="text-center mt-5">
            <a href="/reports/" class="btn btn-outline-primary">все отчёты <i class="bi bi-arrow-right"></i></a>
          </div>
        <?php endif; ?>

      </div>

      <!-- Right colums — Events -->
      <div class="col-lg-4">
        <div class="sidebar-sticky">
          <h2 class="h3 mb-4 pb-2 border-bottom">анонсы</h2>
          <div class="list-group list-group-flush border rounded shadow-sm">
            <?php foreach ($events as $item):
                $date_time = explode(" ", $item[0] ?? '') ?? [];
                $place     = explode(",", $item[1] ?? '') ?? [];
                $title     = $item[2] ?? '';
                $desc      = $item[3] ?? '';
                $href      = $item[5] ?? '#';
            ?>
              <a href="<?= htmlspecialchars($href) ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                <div>
                  <small class="text-muted">
                    <?= formatDateRu($date_time[0]) ?> · <?= htmlspecialchars($date_time[1] ?? '') ?>
                    <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($place[0] ?? '') ?>
                  </small>
                  <h5 class="mb-1"><?= htmlspecialchars($title) ?></h5>
                </div>
              </a>
            <?php endforeach; ?>
          </div>

          <div class="text-center mt-5">
            <a href="/events" class="btn btn-outline-primary">все анонсы <i class="bi bi-arrow-right"></i></a>
          </div>
        </div>
      </div>

    </div>
  </main>

  <?php include 'footer.html'; ?>

  <script src="/js/bootstrap.bundle.min.js"></script>
</body>
</html>