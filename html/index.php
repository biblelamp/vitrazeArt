<?php
// сайт vitrazeArt © 2026, версия 0.1 от 22 Марта 2026г

// Функция для чтения блоков из файла (разделитель — пустая строка)
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

// Читаем все три файла
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
    rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" 
    crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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

    <!-- Левая колонка – анонсы + репортажи -->
    <div class="col-lg-8">
      <h2 class="h3 mb-4 pb-2 border-bottom">анонсы</h2>

      <!-- Горизонтальные карточки анонсов -->
      <div class="mb-5">

<?php foreach ($announces as $item): 
          $datetime = explode(" ", $item[0]) ?? '';
          $place    = $item[1] ?? '';
          $title    = $item[2] ?? 'Без названия';
          $desc     = $item[3] ?? '';
          $image    = $item[4] ?? '';
          $href     = $item[5] ?? '';
        ?>
        <div class="card mb-4 border-0 shadow-sm overflow-hidden">
          <div class="row g-0">
            <div class="col-md-4">
              <img src="<?= htmlspecialchars($image) ?>" class="img-fluid announce-img" alt="<?= htmlspecialchars($title) ?>">
            </div>
            <div class="col-md-8">
              <div class="card-body">
                <div class="text-muted small mb-2"><?= formatDateRu($datetime[0]) ?> · <?= htmlspecialchars($datetime[1]) ?> · <?= htmlspecialchars($place) ?></div>
                <h5 class="card-title fs-4 mb-3"><?= htmlspecialchars($title) ?></h5>
                <p class="card-text text-muted mb-3"><?= htmlspecialchars($desc) ?></p>
                <a href="<?= $href ?>" class="btn btn-sm btn-outline-primary">подробнее →</a>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

      <!-- Пример анонса 1
        <div class="card mb-4 border-0 shadow-sm overflow-hidden">
          <div class="row g-0">
            <div class="col-md-4">
              <img src="file:///C:\Users\lamp\vitrazeArt\html\images\announces\2026-03-24-drc.jpg?w=800" class="img-fluid announce-img" alt="">
            </div>
            <div class="col-md-8">
              <div class="card-body">
                <div class="text-muted small mb-2">28 марта 2026 · 19:00 · Арт-кафе «Слово»</div>
                <h5 class="card-title fs-4 mb-3">Вечер новой поэзии «Голоса марта»</h5>
                <p class="card-text text-muted mb-3">Открытый микрофон, специальные гости, атмосфера живого слова. Вход свободный, приходите с собственными текстами.</p>
                <a href="#" class="btn btn-sm btn-outline-primary">Подробнее →</a>
              </div>
            </div>
          </div>
        </div>-->

      </div>

      <!-- Репортажи ниже (список) -->
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

      <!--<div class="list-group list-group-flush border rounded shadow-sm">
        <a href="#" class="list-group-item list-group-item-action px-4 py-3">
          <div class="d-flex w-100 justify-content-between">
            <h5 class="mb-1 fw-bold">«Зима в словах»: поэтический марафон 2026</h5>
            <small class="text-muted">15 марта</small>
          </div>
          <p class="mb-1 text-muted">120 участников, 80+ текстов, яркие моменты и фоторепортаж.</p>
        </a>-->

      <div class="text-center mt-5">
        <a href="#" class="btn btn-outline-primary">всё прошедшее →</a>
      </div>
    </div>

    <!-- Правая колонка – авторы -->
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
          <!--<a href="#" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
            <img src="file:///C:\Users\lamp\vitrazeArt\html\images\authors\inna-p.jpg?w=200" class="person-img" alt="">
            <div>
              <h6 class="mb-0 fw-bold">Анна К.</h6>
              <small class="text-muted">Поэтесса, переводчица</small>
            </div>
          </a>-->
        </div>
        <div class="text-center mt-5">
          <a href="#" class="btn btn-outline-primary">полная галерея авторов →</a>
        </div>
      </div>
    </div>
    </div>
  </main>

  <footer class="bg-white border-top py-4 mt-5 text-center text-muted small">
    <div class="container">
      © 2026 <a href="https://t.me/vitraze">Пражские витражи</a> · все цвета творчества
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" 
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" 
    crossorigin="anonymous">
  </script>
</body>
</html>