<?php
-// web vitrazeArt.cz © 2026 version 0.1.1 by 22-mar-26

// reading blocks of lines from a file (delimiter: empty line)
function readBlocks($filePath) {
    if (!file_exists($filePath)) return [];
    $content = file_get_contents($filePath);
    $blocks = preg_split('/\n\s*\n/', trim($content), -1, PREG_SPLIT_NO_EMPTY);
    $items = [];
    foreach ($blocks as $block) {
        $lines = array_filter(array_map('trim', explode("\n", $block)));
        $items[] = $lines;
    }
    return $items;
}
// date conversion YYYY-MM-DD -> d месяц ГОД
function formatDateRu($dateStr) {
    $months = [
        '01' => 'января',
        '02' => 'февраля',
        '03' => 'марта',
        '04' => 'апреля',
        '05' => 'мая',
        '06' => 'июня',
        '07' => 'июля',
        '08' => 'августа',
        '09' => 'сентября',
        '10' => 'октября',
        '11' => 'ноября',
        '12' => 'декабря'
    ];

    $date = DateTime::createFromFormat('Y-m-d', $dateStr);
    if (!$date) {
        return null; // or throws
    }

    $day = $date->format('j');
    $month = $months[$date->format('m')];
    $year = $date->format('Y');
    $currentYear = date('Y');

    if ($year == $currentYear) {
        return "$day $month";
    } else {
        return "$day $month $year";
    }
}

// read all files
$announces = readBlocks('data/announces.txt');
$reports   = readBlocks('data/reports.txt');
$authors   = readBlocks('data/authors.txt');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Пражские витражи — анонсы, репортажи, авторы</title>
  <link href="/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/css/bootstrap-icons.min.css">
  <style>
    .person-img { 
      width: 85px;
      height: 85px;
      object-fit: cover;
      border-radius: 50%;
    }
    .sidebar-sticky {
      position: sticky;
      top: 76px;
    }
    .announce-img {
      height: 180px;
      object-fit: cover;
      border-radius: 14px;
    }
    footer a i {
      transition: all 0.2s ease;
    }
    footer a:hover i {
      color: #0d6efd; /* bootstrap primary */
      transform: translateY(-2px);
    }
    @media (min-width: 768px) {
      .announce-img {
        height: 100%;
      }
    }
  </style>
</head>
<body class="bg-light">

  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
    <div class="container-fluid px-4 px-lg-5">
      <a class="navbar-brand fs-3 text-dark" href="/">
        <img src="images/logo.jpg" alt="Пражские витражи" style="height:45px">
        пражские витражи
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarContent">
        <form class="d-flex ms-auto my-2 my-lg-0" style="max-width: 420px; width: 100%;">
          <input class="form-control me-2 rounded-pill" type="search" placeholder="поиск по анонсам, событиям и авторам..." aria-label="Поиск">
          <button class="btn btn-outline-secondary rounded-pill" type="submit"><i class="bi bi-search"></i></button>
        </form>
      </div>
    </div>
  </nav>

  <main class="container-fluid px-4 px-lg-5 my-4 my-lg-5">
    <div class="row g-4 g-lg-5">

    <!-- Left column – announcements + reports -->
    <div class="col-lg-8">
      <h2 class="h3 mb-4 pb-2 border-bottom">анонсы</h2>

      <!-- Announcements -->
      <div class="mb-5">

<?php foreach ($announces as $index => $item):
          $datetime = explode(" ", $item[0]) ?? '';
          $place    = $item[1] ?? '';
          $title    = $item[2] ?? 'Без названия';
          $desc     = $item[3] ?? '';
          $image    = $item[4] ?? '';
          $href     = $item[5] ?? '';
        ?>
        <?php if ($index === 0): ?>
        <div class="card mb-4 border-0 shadow-sm overflow-hidden">
          <div class="row g-0">
            <div class="col-md-4">
              <img src="<?= htmlspecialchars($image) ?>" class="img-fluid announce-img" alt="<?= htmlspecialchars($title) ?>">
            </div>
            <div class="col-md-8">
              <div class="card-body">
                <div class="text-muted small mb-2">
                  <?= formatDateRu($datetime[0]) ?> · <?= htmlspecialchars($datetime[1]) ?> · <?= htmlspecialchars($place) ?>
                </div>
                <h5 class="card-title fs-4 mb-3"><?= htmlspecialchars($title) ?></h5>
                <p class="card-text text-muted mb-3"><?= htmlspecialchars($desc) ?></p>
                <a href="<?= $href ?>" class="btn btn-sm btn-outline-primary">подробнее <i class="bi bi-arrow-right"></i></a>
              </div>
            </div>
          </div>
        </div>
        <?php else: ?>
        <div class="border-bottom py-3">
          <div class="text-muted small mb-1">
             <?= formatDateRu($datetime[0]) ?> · <?= htmlspecialchars($place) ?>
          </div>
          <h5 class="mb-1"><?= htmlspecialchars($title) ?></h5>
          <p class="text-muted mb-2"><?= htmlspecialchars($desc) ?>… <a href="<?= $href ?>">подробнее <i class="bi bi-arrow-right"></i></a></p>
          
        </div>
        <?php endif; ?>
        <?php endforeach; ?>

      </div>

      <!-- Reports -->
      <h2 class="h3 mb-4 pb-2 border-bottom">прошедшее</h2>

      <div class="list-group list-group-flush border rounded shadow-sm">
<?php foreach ($reports as $item): 
          $date   = $item[0] ?? '';
          $title  = $item[1] ?? '';
          $desc   = $item[2] ?? '';
          $href   = $item[3] ?? '';
        ?>
        <a href="<?= $href ?>" class="list-group-item list-group-item-action px-4 py-3">
          <div class="d-flex w-100 justify-content-between">
            <h5 class="mb-1"><?= htmlspecialchars($title) ?></h5>
            <small class="text-muted"><?= formatDateRu($date) ?></small>
          </div>
          <p class="mb-1 text-muted small"><?= htmlspecialchars($desc) ?></p>
        </a>
        <?php endforeach; ?>
      </div>

      <div class="text-center mt-5">
        <a href="#" class="btn btn-outline-primary">всё прошедшее <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>

    <!-- Right column – Authors -->
    <div class="col-lg-4">
      <div class="sidebar-sticky">
        <h2 class="h3 mb-4 pb-2 border-bottom">авторы</h2>
        <div class="list-group list-group-flush">
<?php foreach ($authors as $item): 
            $name     = $item[0] ?? '';
            $nickname = $item[1] ?? '';
            $role     = $item[2] ?? '';
            $image    = $item[3] ?? '';
            $link     = $item[4] ?? '#';
          ?>
          <a href="<?= htmlspecialchars($link) ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
            <img src="<?= htmlspecialchars($image) ?>" class="person-img" alt="<?= htmlspecialchars($name) ?>">
            <div>
              <h6 class="mb-0 fw-bold"><?= htmlspecialchars($name) ?></h6>
              <small class="text-muted"><?= htmlspecialchars($role) ?></small>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
        <div class="text-center mt-5">
          <a href="#" class="btn btn-outline-primary">все наши авторы <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>
    </div>
    </div>
  </main>

  <footer class="bg-white border-top py-4 mt-5 text-center text-muted small">
    <div class="container">
      <div class="mb-3">
        © 2026 
        <a href="https://t.me/vitraze" target="blank" class="text-decoration-none">
          Пражские витражи
        </a> · все цвета творчества
      </div>
      <div class="d-flex justify-content-center gap-3 fs-3">
        <a href="https://www.facebook.com/groups/vitraze" target="_blank" class="text-muted" title="Пражские витражи в facebook">
          <i class="bi bi-facebook"></i>
        </a>
        <a href="https://t.me/vitraze" target="_blank" class="text-muted" title="Пражские витражи в телеграм">
          <i class="bi bi-telegram"></i>
        </a>
        <a href="https://threads.net/@javageek.cz" target="_blank" class="text-muted" title="Пражские витражи в threads">
          <i class="bi bi-threads"></i>
        </a>
      </div>
    </div>
  </footer>

  <script src="/js/bootstrap.bundle.min.js"></script>
</body>
</html>