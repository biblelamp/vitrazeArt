<?php
// search.php version 0.2 by 29-Mar-26

require_once __DIR__ . '/functions.php';

// read all files
$authors = readBlocks('data/authors.txt');

// shuffle the elements of the array
shuffle($authors);

// number of authors on the page
$number_authors = 10;

// get search request
$query = isset($_GET['text']) ? trim($_GET['text']) : '';
$results = [];

// ====================== 1. Анонсы (первый приоритет) ======================
if (!empty($query)) {
    $events_list = readBlocks('data/events.txt');

    foreach ($events_list as $item) {
        if (count($item) < 6) continue;

        $title       = trim($item[2] ?? '');
        $description = trim($item[3] ?? '');
        $url         = trim($item[5] ?? '');

        if (empty($title) || empty($url)) continue;

        $type = '<i class="bi bi-calendar-event"></i>';
        $snippet = null;

        // Поиск в подробном файле анонса
        $slug = substr(ltrim($url, '/'), strlen('events/'));
        $txt_file = __DIR__ . '/data/events/' . str_replace('/', '-', $slug) . '.txt';

        if (file_exists($txt_file)) {
            $content = file_get_contents($txt_file);
            if ($content) {
                $lines = explode("\n", $content);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;

                    if (mb_stripos($line, $query) !== false) {
                        $clean = cleanMarkdown($line);
                        $highlighted = preg_replace(
                            '/(' . preg_quote($query, '/') . ')/iu',
                            '<strong>$1</strong>',
                            htmlspecialchars($clean)
                        );

                        if (mb_strlen($clean) > 180) {
                            $pos = mb_stripos($clean, $query);
                            $start = max(0, $pos - 60);
                            $clean = '...' . mb_substr($clean, $start, 160) . '...';
                            $highlighted = preg_replace(
                                '/(' . preg_quote($query, '/') . ')/iu',
                                '<strong>$1</strong>',
                                htmlspecialchars($clean)
                            );
                        }
                        $snippet = $highlighted;
                        break;
                    }
                }
            }
        }

        // Поиск в описании
        if ($snippet === null && mb_stripos($description, $query) !== false) {
            $clean = cleanMarkdown($description);
            $snippet = preg_replace(
                '/(' . preg_quote($query, '/') . ')/iu',
                '<strong>$1</strong>',
                htmlspecialchars($clean)
            );
        }

        // Поиск в заголовке
        if ($snippet === null && mb_stripos($title, $query) !== false) {
            $clean = cleanMarkdown($title);
            $snippet = preg_replace(
                '/(' . preg_quote($query, '/') . ')/iu',
                '<strong>$1</strong>',
                htmlspecialchars($clean)
            );
        }

        if ($snippet !== null) {
            $results[] = [
                'title'   => $title,
                'snippet' => $snippet,
                'url'     => $url,
                'type'    => $type
            ];
        }
    }
}

// ====================== 2. Репортажи (второй приоритет) ======================
if (!empty($query)) {
    $reports_list = readBlocks('data/reports.txt');

    foreach ($reports_list as $item) {
        if (count($item) < 4) continue;

        $title       = trim($item[1] ?? '');
        $description = trim($item[2] ?? '');
        $url         = trim($item[3] ?? '');

        if (empty($title) || empty($url)) continue;

        $type = '<i class="bi bi-pencil-square"></i>';
        $snippet = null;

        // Поиск в подробном файле репортажа
        $slug = substr(ltrim($url, '/'), strlen('reports/'));
        $txt_file = __DIR__ . '/data/reports/' . str_replace('/', '-', $slug) . '.txt';

        if (file_exists($txt_file)) {
            $content = file_get_contents($txt_file);
            if ($content) {
                $lines = explode("\n", $content);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;

                    if (mb_stripos($line, $query) !== false) {
                        $clean = cleanMarkdown($line);
                        $highlighted = preg_replace(
                            '/(' . preg_quote($query, '/') . ')/iu',
                            '<strong>$1</strong>',
                            htmlspecialchars($clean)
                        );

                        if (mb_strlen($clean) > 180) {
                            $pos = mb_stripos($clean, $query);
                            $start = max(0, $pos - 60);
                            $clean = '...' . mb_substr($clean, $start, 160) . '...';
                            $highlighted = preg_replace(
                                '/(' . preg_quote($query, '/') . ')/iu',
                                '<strong>$1</strong>',
                                htmlspecialchars($clean)
                            );
                        }
                        $snippet = $highlighted;
                        break;
                    }
                }
            }
        }

        // Поиск в описании
        if ($snippet === null && mb_stripos($description, $query) !== false) {
            $clean = cleanMarkdown($description);
            $snippet = preg_replace(
                '/(' . preg_quote($query, '/') . ')/iu',
                '<strong>$1</strong>',
                htmlspecialchars($clean)
            );
        }

        // Поиск в заголовке
        if ($snippet === null && mb_stripos($title, $query) !== false) {
            $clean = cleanMarkdown($title);
            $snippet = preg_replace(
                '/(' . preg_quote($query, '/') . ')/iu',
                '<strong>$1</strong>',
                htmlspecialchars($clean)
            );
        }

        if ($snippet !== null) {
            $results[] = [
                'title'   => $title,
                'snippet' => $snippet,
                'url'     => $url,
                'type'    => $type
            ];
        }
    }
}

// ====================== 3. Авторы (третий приоритет) ======================
if (!empty($query)) {
    $authors_list = readBlocks('data/authors.txt');

    foreach ($authors_list as $item) {
        $name      = trim($item[0] ?? '');
        $nick_line = trim($item[1] ?? '');
        $role      = trim($item[2] ?? '');

        $main_nick = explode(' ', $nick_line)[0] ?? '';
        if (empty($main_nick) || empty($name)) continue;

        $url  = '/authors/' . $main_nick;
        $type = '<i class="bi bi-person-circle"></i>';
        $snippet = null;

        // Поиск в файле автора
        $txt_file = __DIR__ . '/data/authors/' . $main_nick . '.txt';

        if (file_exists($txt_file)) {
            $content = file_get_contents($txt_file);
            if ($content) {
                $lines = explode("\n", $content);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;

                    if (mb_stripos($line, $query) !== false) {
                        $clean = cleanMarkdown($line);
                        $highlighted = preg_replace(
                            '/(' . preg_quote($query, '/') . ')/iu',
                            '<strong>$1</strong>',
                            htmlspecialchars($clean)
                        );

                        if (mb_strlen($clean) > 180) {
                            $pos = mb_stripos($clean, $query);
                            $start = max(0, $pos - 60);
                            $clean = '...' . mb_substr($clean, $start, 160) . '...';
                            $highlighted = preg_replace(
                                '/(' . preg_quote($query, '/') . ')/iu',
                                '<strong>$1</strong>',
                                htmlspecialchars($clean)
                            );
                        }
                        $snippet = $highlighted;
                        break;
                    }
                }
            }
        }

        // Поиск в роли
        if ($snippet === null && mb_stripos($role, $query) !== false) {
            $clean = cleanMarkdown($role);
            $snippet = preg_replace(
                '/(' . preg_quote($query, '/') . ')/iu',
                '<strong>$1</strong>',
                htmlspecialchars($clean)
            );
        }

        // Поиск в имени автора
        if ($snippet === null && mb_stripos($name, $query) !== false) {
            $clean = cleanMarkdown($name);
            $snippet = preg_replace(
                '/(' . preg_quote($query, '/') . ')/iu',
                '<strong>$1</strong>',
                htmlspecialchars($clean)
            );
        }

        if ($snippet !== null) {
            $results[] = [
                'title'   => $name,
                'snippet' => $snippet,
                'url'     => $url,
                'type'    => $type
            ];
        }
    }
}

$totalResults = count($results);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Сообщество русскоязычных поэтов, художников и музыкантов в Праге и Чехии. Публикации, авторы, творчество и культурная жизнь.">
  <title>Пражские витражи — анонсы, репортажи, авторы</title>
  <!-- Open Graph meta tags -->
  <meta property="og:title" content="Пражские витражи — анонсы, репортажи, авторы">
  <meta property="og:description" content="Творческое сообщество в Праге и Чехии: поэты, художники, музыканты. Найди своих или опубликуй своё творчество.">
  <meta property="og:image" content="/images/logo.jpg">
  <meta property="og:url" content="https://vitrazeart.cz/">
  <meta property="og:type" content="website">
  <link href="/css/bootstrap.min.css" rel="stylesheet"><!-- v5.3.8 -->
  <link href="/css/style.css" rel="stylesheet"><!-- my styles -->
  <link rel="stylesheet" href="/css/bootstrap-icons.min.css"><!-- v1.13.1 -->
  <link rel="icon" href="/images/favicon.ico" type="image/x-icon">
</head>
<body class="bg-light">

  <?php include 'header.html'; ?>

  <main class="container-fluid px-4 px-lg-5 my-4 my-lg-5">
    <div class="row g-4 g-lg-5">
      
      <!-- Left column – Search Results -->
      <div class="col-lg-8">
        <h2 class="h3 mb-4 pb-2 border-bottom">результаты поиска</h2>

        <?php if (empty($query)): ?>
          <div class="alert alert-info py-4">
            <p class="mb-0">Введите запрос в строку поиска, чтобы что-то найти</p>
          </div>

        <?php elseif ($totalResults === 0): ?>
          <div class="alert alert-warning py-4">
            По запросу «<strong><?= htmlspecialchars($query) ?></strong>» ничего не найдено.<br>
            Попробуйте изменить формулировку запроса.
          </div>

        <?php else: ?>
          <p class="text-muted mb-4">Найдено: <?= $totalResults ?> <?= getPageWordForm($totalResults) ?></p>

          <?php foreach ($results as $result): ?>
            <div class="card mb-2 shadow-sm">
              <div class="card-body">
                <h5 class="card-title mb-1">
                  <?= $result['type'] ?> <a href="<?= htmlspecialchars($result['url']) ?>" class="text-decoration-none text-primary">
                    <?= htmlspecialchars($result['title']) ?>
                  </a>
                </h5>
                <p class="card-text mb-1" style="line-height: 1.5;">
                  <?= $result['snippet'] ?>
                </p>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    <!-- Right column – Authors -->
    <div class="col-lg-4">
      <div class="sidebar-sticky">
        <h2 class="h3 mb-4 pb-2 border-bottom">авторы</h2>
        <div class="list-group list-group-flush">
<?php foreach (array_slice($authors, 0, $number_authors) as $item):
            $name     = $item[0] ?? '';
            $nickname = explode(" ", $item[1] ?? '') ?? [];
            $role     = $item[2] ?? '';
            $image    = '/images/authors/' . (count($nickname) > 1 ? $nickname[1] : $nickname[0]) . '.jpg';
            $href     = '/authors/' . $nickname[0];
          ?>
          <a href="<?= $href ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
            <img src="<?= htmlspecialchars($image) ?>" class="person-img" alt="<?= htmlspecialchars($name) ?>">
            <div>
              <h5 class="mb-1"><?= shortName($name) ?></h5>
              <small class="text-muted"><?= htmlspecialchars($role) ?></small>
            </div>
          </a>
<?php endforeach; ?>
        </div>
        <div class="text-center mt-5">
          <a href="/authors/" class="btn btn-outline-primary">все авторы <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>
    </div>
    </div>
  </main>

  <?php include 'footer.html'; ?>

  <script src="/js/bootstrap.bundle.min.js"></script>
</body>
</html>