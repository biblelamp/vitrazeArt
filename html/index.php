<?php
// vitrazeArt.cz © 2026 version 0.5.1 by 7-Apr-26

require_once __DIR__ . '/functions.php';

// read all files
$events  = filterByDate(readBlocks('data/events.txt'));
$reports = readBlocks('data/reports.txt');
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
  <meta property="og:title" content="Пражские витражи – анонсы, репортажи, авторы">
  <meta property="og:description" content="Творческое сообщество в Праге и Чехии: поэты, художники, музыканты. Найди своих или опубликуй своё творчество.">
  <meta property="og:image" content="/images/logo.jpg">
  <meta property="og:url" content="https://vitrazeart.cz/">
  <meta property="og:type" content="website">
  <link href="/css/bootstrap.min.css" rel="stylesheet"><!-- v5.3.8 -->
  <link href="/css/style.css" rel="stylesheet"><!-- my styles -->
  <link rel="stylesheet" href="/css/bootstrap-icons.min.css"><!-- v1.13.1 -->
  <link rel="icon" href="/images/favicon.ico" type="image/x-icon">
  <link rel="alternate" type="application/rss+xml" title="Пражские витражи — RSS" href="https://vitrazeart.cz/feed.xml">
</head>
<body class="bg-light">

  <?php include 'header.html'; ?>

  <main class="container-fluid px-4 px-lg-5 my-4 my-lg-5">
    <div class="row g-4 g-lg-5">

    <!-- Left column – events + reports -->
    <div class="col-lg-8">
      <!-- About Us -->
      <h2 class="h3 mb-4 pb-2 border-bottom">кто мы</h2>
      <div class="mb-4">
        <div class="card mb-4 border-0 shadow-sm overflow-hidden">
          <div class="card-body">
            <p><strong>Пражские витражи</strong> – сообщество русскоязычных поэтов, писателей, художников, сонграйтеров, музыкантов и других творческих людей в Праге и по всей Чехии. На сайте публикуются <a href="/events/">анонсы</a> предстоящих и <a href="/reports/">репортажи</a> о прошедших мероприятиях, а также <a href="/authors/">страницы авторов</a> с их <a href="/gallery/">произведениями</a>.</p>
            <p>Мы объединяем и поддерживаем всех, кто желает делиться творчеством и ищет единомышленников. Проект открыт и для начинающих, и для опытных авторов. Живёте в Чехии и занимаетесь творчеством? <a href="/about">Присоединяйтесь</a>!</p>
          </div>
        </div>
      </div>

      <h2 class="h3 mb-4 pb-2 border-bottom">анонсы</h2>

      <!-- Events -->
      <div class="mb-4">

<?php foreach ($events as $index => $item):
          $date_time   = explode(" ", $item[0] ?? '') ?? [];
          $place       = explode(",", $item[1] ?? '') ?? [];
          $title       = $item[2] ?? '';
          $description = $item[3] ?? '';
          $name        = $item[4] ?? '';
          $image       = generateUrl($date_time[0], 'images/events', $name, 'jpg');
          $href        = generateUrl($date_time[0], 'events', $name);
        ?>
<?php if ($index === 0): ?>
        <div class="card mb-4 border-0 shadow-sm overflow-hidden">
          <div class="row g-0">
            <div class="col-md-4">
              <a href="<?= $href ?>">
                <img src="<?= htmlspecialchars($image) ?>" class="img-fluid announce-img" alt="<?= htmlspecialchars($title) ?>">
              </a>
            </div>
            <div class="col-md-8">
              <div class="card-body">
                <div class="text-muted small mb-2">
                  <i class="bi bi-calendar-event"></i> <?= formatDateRu($date_time[0]) ?> · <?= htmlspecialchars($date_time[1]) ?>
                  <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($place[0]) ?>
                </div>
                <h5 class="card-title fs-4 mb-3"><?= htmlspecialchars($title) ?></h5>
                <p class="card-text text-muted mb-3"><?= htmlspecialchars($description) ?></p>
                <a href="<?= $href ?>" class="btn btn-sm btn-outline-primary">подробнее <i class="bi bi-arrow-right"></i></a>
              </div>
            </div>
          </div>
        </div>
<?php else: ?>
        <div class="border-bottom py-3">
          <div class="text-muted small mb-1">
             <i class="bi bi-calendar-event"></i> <?= formatDateRu($date_time[0]) ?> · <?= htmlspecialchars($date_time[1]) ?>
             <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($place[0]) ?>
          </div>
          <h5 class="mb-1"><?= htmlspecialchars($title) ?></h5>
          <p class="text-muted mb-2"><?= htmlspecialchars($description) ?>… <a href="<?= $href ?>">подробнее <i class="bi bi-arrow-right"></i></a></p>
        </div>
<?php endif; ?>
<?php endforeach; ?>
      </div>

      <!-- Reports -->
      <h2 class="h3 mb-4 pb-2 border-bottom">прошедшее</h2>

      <div class="list-group list-group-flush border rounded shadow-sm">
<?php foreach ($reports as $item): 
          $date        = $item[0] ?? '';
          $title       = $item[1] ?? '';
          $description = $item[2] ?? '';
          $name        = $item[3] ?? '';
          $href        = generateUrl($date, 'reports', $name)
        ?>
        <a href="<?= $href ?>" class="list-group-item list-group-item-action px-4 py-3">
          <div class="d-flex w-100 justify-content-between">
            <h5 class="mb-1"><?= htmlspecialchars($title) ?></h5>
            <small class="text-muted"><?= formatDateRu($date) ?></small>
          </div>
          <p class="mb-1 text-muted small"><?= htmlspecialchars($description) ?></p>
        </a>
<?php endforeach; ?>
      </div>

      <div class="text-center mt-5">
        <a href="/reports/" class="btn btn-outline-primary">всё прошедшее <i class="bi bi-arrow-right"></i></a>
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