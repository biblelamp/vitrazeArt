<?php
// authors.php version 0.2 by 24-Mar-26

require_once __DIR__ . '/functions.php';

// read files
$announces = filterByDate(readBlocks('data/announces.txt'));
$authors   = readBlocks('data/authors.txt');

// parse url
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts = explode('/', $uri);

$selected_nick = null;
$author_detail = null;

if (count($parts) === 2 && $parts[0] === 'authors' && !empty($parts[1])) {
    $selected_nick = strtolower(trim($parts[1]));
} elseif (count($parts) === 1 && str_starts_with($parts[0], '@')) {
    $selected_nick = strtolower(substr($parts[0], 1));
    header("Location: /authors/$selected_nick", true, 301);
    exit;
}
// find selected author
if ($selected_nick) {
    foreach ($authors as $item) {
        $nick = isset($item[1]) ? strtolower(trim($item[1])) : '';
        if ($nick === $selected_nick) {
            $author_detail = $item;
            break;
        }
    }
}
// read /data/authors/<selected_nick>.txt
$detail_blocks = [];
if ($author_detail && !empty($selected_nick)) {
    $detail_path = "data/authors/{$selected_nick}.txt";
    if (file_exists($detail_path)) {
        $detail_blocks = readBlocks($detail_path);
    }
}

$other_authors = [];
$shown_nicks = $selected_nick ? [$selected_nick] : [];
foreach ($authors as $item) {
    $nick = isset($item[1]) ? strtolower(trim($item[1])) : '';
    if ($nick && !in_array($nick, $shown_nicks)) { // && count($other_authors) < 4) {
        $other_authors[] = $item;
        $shown_nicks[] = $nick;
    }
}
if ($selected_nick) {
    $other_authors = array_slice($authors, 0, 4);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>
    <?= $author_detail ? htmlspecialchars($author_detail[0] ?? 'Автор') . ' — ' : '' ?>
    Авторы — Пражские витражи
  </title>
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
    .sidebar-sticky { position: sticky; top: 76px; }
    .author-card:hover { transform: translateY(-3px); transition: all 0.2s; }
  </style>
</head>
<body class="bg-light">

  <?php include 'header.html'; ?>

  <main class="container-fluid px-4 px-lg-5 my-4 my-lg-5">
    <div class="row g-4 g-lg-5">

    <!-- Left columns — author(s) -->
    <div class="col-lg-8">

      <?php if ($author_detail): ?>
        <!-- Подробная информация о выбранном авторе -->
        <div class="card border-0 shadow-sm mb-5">
          <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center g-4 g-lg-5">
              <div class="col-md-4 text-center text-md-start">
                <img src="<?= htmlspecialchars('/images/authors/' . $author_detail[1] . '.jpg' ?? '/images/authors/default-m.jpg') ?>"
                     class="person-img-lg mb-3" alt="<?= htmlspecialchars($author_detail[0] ?? '') ?>">
              </div>
              <div class="col-md-8">
                <h1 class="h3 fw-bold mb-2"><?= htmlspecialchars($author_detail[0] ?? '—') ?></h1>
                <p class="lead text-muted mb-3"><?= htmlspecialchars($author_detail[2] ?? '') ?></p>
                <?php if (!empty($author_detail[1])): ?>
                  <p class="text-muted mb-4">@<?= htmlspecialchars($author_detail[1]) ?></p>
                <?php endif; ?>
                <?php if (!empty($author_detail[4])): ?>
                  <a href="<?= htmlspecialchars($author_detail[4]) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                    профиль <i class="bi bi-box-arrow-up-right"></i>
                  </a>
                <?php endif; ?>
              </div>
            </div>

            <?php if (!empty($detail_blocks)): ?>
              <hr class="my-4">
              <div class="mt-4">
                <?php foreach ($detail_blocks as $block): ?>
                  <p class="mb-3"><?= nl2br(htmlspecialchars(implode("\n", $block))) ?></p>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Карточки авторов (все или остальные) -->
      <h2 class="h3 mb-4 pb-2 border-bottom">
        <?= $author_detail ? 'Другие авторы' : 'Наши авторы' ?>
      </h2>

      <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-2">
        <?php foreach ($other_authors as $item):
          $name     = $item[0] ?? '—';
          $nickname = $item[1] ?? '';
          $role     = $item[2] ?? '';
          $image    = '/images/authors/' . $nickname . '.jpg';
          $link     = '/authors/@' . $nickname;
        ?>
          <div class="col">
            <a href="/authors/<?= urlencode($nickname) ?>" class="text-decoration-none">
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

      <?php if (count($authors) > count($other_authors) + ($author_detail ? 1 : 0)): ?>
        <div class="text-center mt-5">
          <a href="/authors/" class="btn btn-outline-primary">все авторы <i class="bi bi-arrow-right"></i></a>
        </div>
      <?php endif; ?>

    </div>

    <!-- right column — Announces -->
    <div class="col-lg-4">
      <div class="sidebar-sticky">
        <h2 class="h3 mb-4 pb-2 border-bottom">анонсы</h2>

        <div class="list-group list-group-flush border rounded shadow-sm">
          <?php foreach ($announces as $item):
            $datetime = explode(" ", $item[0] ?? '') ?? [];
            $place    = explode(",", $item[1] ?? '') ?? [];
            $title    = $item[2] ?? '';
            $desc     = $item[3] ?? '';
            $href     = $item[5] ?? '#';
          ?>
            <a href="<?= $href ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
            <!--<a href="<?= $href ?>" class="list-group-item list-group-item-action px-4 py-3">-->
              <div>
                <small class="text-muted">
                  <?= formatDateRu($datetime[0]) ?> · <?= htmlspecialchars($datetime[1]) ?> · <?= htmlspecialchars($place[0]) ?>
                </small>
                <h5 class="mb-1"><?= htmlspecialchars($title) ?></h5>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
        <div class="text-center mt-5">
          <a href="/" class="btn btn-outline-primary">все анонсы <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>
    </div>
    </div>
  </main>

  <?php include 'footer.html'; ?>

  <script src="/js/bootstrap.bundle.min.js"></script>
</body>
</html>