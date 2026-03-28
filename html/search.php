<?php
// search.php version 0.1 by 27-Mar-26

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

// search by text
if (!empty($query)) {
    $searchDir = __DIR__ . '/data/authors';
    
    if (is_dir($searchDir)) {
        $files = glob($searchDir . '/*.txt');
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) continue;
            
            $filename = basename($file);
            $pageTitle = pathinfo($filename, PATHINFO_FILENAME);
            
            $lines = explode("\n", $content);
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                if (stripos($line, $query) !== false) {
                    // Выделяем найденный текст жирным
                    $highlighted = preg_replace(
                        '/(' . preg_quote($query, '/') . ')/iu',
                        '<strong>$1</strong>',
                        htmlspecialchars($line)
                    );
                    
                    // Обрезаем сниппет, если он слишком длинный
                    if (mb_strlen($line) > 180) {
                        $pos = mb_stripos($line, $query);
                        $start = max(0, $pos - 60);
                        $snippet = mb_substr($line, $start, 160);
                        $snippet = '...' . trim($snippet) . '...';
                        
                        // Повторно выделяем после обрезки
                        $highlighted = preg_replace(
                            '/(' . preg_quote($query, '/') . ')/iu',
                            '<strong>$1</strong>',
                            htmlspecialchars($snippet)
                        );
                    } else {
                        $highlighted = preg_replace(
                            '/(' . preg_quote($query, '/') . ')/iu',
                            '<strong>$1</strong>',
                            htmlspecialchars($line)
                        );
                    }
                    
                    $results[] = [
                        'title'     => $pageTitle,
                        'snippet'   => $highlighted,
                        'url'       => '/authors/' . $pageTitle,
                        'filename'  => $filename
                    ];
                    break; // один результат на файл
                }
            }
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
          <p class="text-muted mb-4">Найдено: <?= $totalResults ?> <?= $totalResults == 1 ? 'результат' : 'результатов' ?></p>
          
          <?php foreach ($results as $result): ?>
            <div class="card mb-4 shadow-sm">
              <div class="card-body">
                <h5 class="card-title mb-3">
                  <a href="<?= htmlspecialchars($result['url']) ?>" class="text-decoration-none text-primary">
                    <?= htmlspecialchars($result['title']) ?>
                  </a>
                </h5>
                <p class="card-text mb-3" style="line-height: 1.5;">
                  <?= $result['snippet'] ?>
                </p>
                <a href="<?= htmlspecialchars($result['url']) ?>" class="btn btn-outline-primary btn-sm">
                  Читать полностью <i class="bi bi-arrow-right"></i>
                </a>
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