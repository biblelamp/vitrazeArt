<?php
// vitrazeArt.cz © 2026 version 0.2.2 by 25-Mar-26

require_once __DIR__ . '/functions.php';

// read all files
$announces = filterByDate(readBlocks('data/announces.txt'));
$reports   = readBlocks('data/reports.txt');
$authors   = readBlocks('data/authors.txt');
// shuffle the elements of the array
shuffle($authors);
// number of authors on the page
$number_authors = 6;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Пражские витражи — анонсы, репортажи, авторы</title>
  <link href="/css/bootstrap.min.css" rel="stylesheet"><!-- v5.3.8 -->
  <link href="/css/style.css" rel="stylesheet"><!-- my styles -->
  <link rel="stylesheet" href="/css/bootstrap-icons.min.css"><!-- v1.13.1 -->
  <link rel="icon" href="/images/favicon.ico" type="image/x-icon">
</head>
<body class="bg-light">

  <?php include 'header.html'; ?>

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
              <a href="<?= $href ?>">
                <img src="<?= htmlspecialchars($image) ?>" class="img-fluid announce-img" alt="<?= htmlspecialchars($title) ?>">
              </a>
            </div>
            <div class="col-md-8">
              <div class="card-body">
                <div class="text-muted small mb-2">
                  <?= formatDateRu($datetime[0]) ?> · <?= htmlspecialchars($datetime[1]) ?> · <?= parseMarkdownLinks($place) ?>
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
             <?= formatDateRu($datetime[0]) ?> · <?= htmlspecialchars($datetime[1]) ?> · <?= parseMarkdownLinks($place) ?>
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

    <!-- right column – Authors -->
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