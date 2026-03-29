<?php
// authors.php version 0.5 by 29-Mar-26

require_once __DIR__ . '/functions.php';

// read files
$events  = filterByDate(readBlocks('data/events.txt'));
$authors = readBlocks('data/authors.txt');

// parse url
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts = explode('/', $uri);

$selected_nick = null;
$author_item = null;

if (count($parts) === 2 && $parts[0] === 'authors' && !empty($parts[1])) {
    $selected_nick = strtolower(trim($parts[1]));
} elseif (count($parts) === 1 && str_starts_with($parts[0], '@')) {
    $selected_nick = strtolower(substr($parts[0], 1));
}
// find selected author
if ($selected_nick) {
    foreach ($authors as $key => $item) {
        $nick = explode(" ", $item[1] ?? '') ?? [];
        if ($nick[0] === $selected_nick) {
            $author_item = $item;
            unset($authors[$key]); // remove found item
            break;
        }
    }
}
// 404 page
if ($author_item == null && $selected_nick != null) {
    $author_item = ['ошибочка 404', $selected_nick . ' unknown', 'вы кого тут ищете? нету такого…'];
}
// read /data/authors/<selected_nick>.txt
$author_detail = [];
if ($selected_nick) {
    $detail_path = "data/authors/{$selected_nick}.txt";
    if (file_exists($detail_path)) {
        $author_detail = readBlocks($detail_path);
    }
}
// shuffle the list of authors
shuffle($authors);
if ($selected_nick) {
    $authors = array_slice($authors, 0, 4);
}
// image for preview
$og_image = $author_item ? '/images/authors/' . $author_item[1] . '.jpg' : '/images/logo.jpg';
// title
$title = ($author_item ? htmlspecialchars($author_item[0] ?? 'Автор') . ' — ' : '') . 'Авторы — Пражские витражи';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Русскоязычные поэты, художники, музыканты и другие творческие люди, живущие в Праге и по всей Чехии">
  <title><?= $title ?></title>
  <!-- Open Graph meta tags -->
  <meta property="og:title" content="<?= $title ?>">
  <meta property="og:description" content="Русскоязычные поэты, художники, музыканты и другие творческие люди, живущие в Праге и по всей Чехии">
  <meta property="og:image" content="<?= $og_image ?>">
  <meta property="og:url" content="https://vitrazeart.cz/">
  <meta property="og:type" content="website">
  <link href="/css/bootstrap.min.css" rel="stylesheet"><!-- v5.3.8 -->
  <link href="/css/style.css" rel="stylesheet"><!-- my styles -->
  <link rel="stylesheet" href="/css/bootstrap-icons.min.css"><!-- v1.13.1 -->
  <link rel="icon" href="/images/favicon.ico" type="image/x-icon">
  <style>
    .person-img-lg { 
      width: 200px; 
      height: 200px; 
      object-fit: cover; 
      border-radius: 50%;
    }
    .author-card:hover { 
      transform: translateY(-3px); 
      transition: all 0.2s; 
    }
  </style>
</head>
<body class="bg-light">

  <?php include 'header.html'; ?>

  <main class="container-fluid px-4 px-lg-5 my-4 my-lg-5">
    <div class="row g-4 g-lg-5">

    <!-- Left columns — author(s) -->
    <div class="col-lg-8">

<?php if ($author_item):
            $name     = $author_item[0] ?? '';
            $nickname = explode(" ", $author_item[1] ?? '') ?? [];
            $role     = $author_item[2] ?? '';
            $image    = '/images/authors/' . (count($nickname) > 1 ? $nickname[1] : $nickname[0]) . '.jpg';
            $href     = '/authors/' . $nickname[0];
      ?>
      <!-- detail of selected author -->
      <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-4 p-lg-5">
          <div class="row align-items-center g-4 g-lg-5">
            <div class="col-md-4 text-center text-md-start">
              <img src="<?= htmlspecialchars($image) ?>" class="person-img-lg mb-3" alt="<?= htmlspecialchars($name) ?>">
            </div>
            <div class="col-md-8">
              <h1 class="h3 fw-bold mb-2"><?= htmlspecialchars($name) ?></h1>
              <p class="lead text-muted mb-3"><?= htmlspecialchars($role) ?></p>
              <p class="text-muted mb-4">@<?= htmlspecialchars(rawurldecode($nickname[0])) ?></p>
            </div>
          </div>
<?php if (!empty($author_detail)): ?>
            <hr class="my-4">
            <div class="mt-4">
<?php foreach ($author_detail as $block): ?>
                <p class="mb-3"><?= nl2br(parseMarkdown(implode("\n", $block))) ?></p>
<?php endforeach; ?>
            </div>
<?php endif; ?>
        </div>
      </div>
<?php endif; ?>

      <!-- Карточки авторов (все или остальные) -->
      <h2 class="h3 mb-4 pb-2 border-bottom">
        <?= $author_item ? 'другие авторы' : 'авторы' ?>
      </h2>

      <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-2">
<?php foreach ($authors as $item):
            $name     = $item[0] ?? '';
            $nickname = explode(" ", $item[1] ?? '') ?? [];
            $role     = $item[2] ?? '';
            $image    = '/images/authors/' . (count($nickname) > 1 ? $nickname[1] : $nickname[0]) . '.jpg';
            $href     = '/authors/' . $nickname[0];
        ?>
        <div class="col">
          <a href="<?= htmlspecialchars($href) ?>" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm author-card">
              <div class="card-body text-center p-4">
                <img src="<?= htmlspecialchars($image) ?>" class="person-img mb-3" alt="<?= htmlspecialchars($name) ?>">
                <h5 class="card-title mb-1"><?= htmlspecialchars($name) ?></h5>
                <p class="card-text text-muted small"><?= htmlspecialchars($role) ?></p>
              </div>
            </div>
          </a>
        </div>
<?php endforeach; ?>
      </div>

<?php if ($selected_nick): ?>
      <div class="text-center mt-5">
        <a href="/authors/" class="btn btn-outline-primary">все авторы <i class="bi bi-arrow-right"></i></a>
      </div>
<?php endif; ?>
    </div>

    <!-- right column — events -->
    <div class="col-lg-4">
      <div class="sidebar-sticky">
        <h2 class="h3 mb-4 pb-2 border-bottom">анонсы</h2>
        <div class="list-group list-group-flush border rounded shadow-sm">
<?php foreach ($events as $item):
            $datetime = explode(" ", $item[0] ?? '') ?? [];
            $place    = explode(",", $item[1] ?? '') ?? [];
            $title    = $item[2] ?? '';
            $desc     = $item[3] ?? '';
            $href     = $item[5] ?? '#';
          ?>
          <a href="<?= htmlspecialchars($href) ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
            <div>
              <small class="text-muted">
                <?= formatDateRu($datetime[0]) ?> · <?= htmlspecialchars($datetime[1]) ?>
                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($place[0]) ?>
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