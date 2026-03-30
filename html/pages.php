<?php
// pages.cz © 2026 version 0.1 by 29-Mar-26

require_once __DIR__ . '/functions.php';

// parse URL: /about -> data/about.txt
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts = explode('/', $uri);

$title = null;
$content = null;

if (count($parts) > 0) {
    $detail_path = "data/{$parts[0]}.txt";
    if (file_exists($detail_path)) {
        $content = readBlocks($detail_path);
        $title = $content[0][0];
        unset($content[0]);
    }
}

// read authors
$authors = readBlocks('data/authors.txt');

// shuffle the elements of the array
shuffle($authors);

// number of authors on the page
$number_authors = 10;
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
  <meta property="og:image" content="https://vitrazeart.cz/images/logo.jpg">
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

    <!-- Left column – page -->
    <div class="col-lg-8">
      <!-- About Us -->
      <h2 class="h3 mb-4 pb-2 border-bottom"><?= $title ?></h2>
      <div class="mb-4">
        <div class="card mb-4 border-0 shadow-sm overflow-hidden">
          <div class="card-body">
          <?php foreach ($content as $block): ?>
            <p><?= nl2br(parseMarkdown(implode("\n", $block))) ?></p>
          <?php endforeach; ?>
          </div>
        </div>
      </div>
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