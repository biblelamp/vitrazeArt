<?php
// events.php version 0.4 by 9-Apr-26

require_once __DIR__ . '/functions.php';

// read files
$all_events = readBlocks('data/events.txt');
$events     = filterByDate($all_events);   // only future
$authors    = readBlocks('data/authors.txt');

shuffle($authors);
$number_authors = 10;

$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts = explode('/', $uri);

$selected_slug = null;
$event_item = null;
$first_item = null;

if (count($parts) >= 5 && $parts[0] === 'events') {
    $year  = $parts[1] ?? '';
    $month = str_pad($parts[2] ?? '', 2, '0', STR_PAD_LEFT);
    $day   = str_pad($parts[3] ?? '', 2, '0', STR_PAD_LEFT);
    $code  = strtolower($parts[4] ?? '');

    if ($year && $month && $day && $code) {
        $selected_slug = "{$year}-{$month}-{$day}-{$code}";

        // seek event by href
        foreach ($all_events as $key => $item) {
            if ($code === $item[4]) {
                $event_item = $item;
                break;
            }
        }
        // remove found item
        foreach ($events as $key => $item) {
            if ($code === $item[4]) {
                unset($events[$key]); // remove found item
                break;
            }
        }
    }
}

if (count($parts) == 1) {
    $first_item = $events[0];
    unset($events[0]);
}

// 404 page
if (!$event_item && count($parts) > 1) {
    $event_item = ['', '', 'ошибка 404: анонс не найден', '', ''];
}

// read event from file /data/events/...
$event_detail = [];
if ($selected_slug) {
    $detail_path = "data/events/{$selected_slug}.txt";
    if (file_exists($detail_path)) {
        $event_detail = readBlocks($detail_path);
    }
}

// title & description
$title = 'Анонсы – Пражские витражи';
$description = 'Анонсы творческих мероприятий сообщества Пражские витражи.';
if ($event_item) {
    $title = htmlspecialchars($event_item[2]) . ' – ' . $title;
    $description = htmlspecialchars($event_item[3]);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= $description ?>">
  <title><?= $title ?></title>
  <!-- Open Graph meta tags -->
  <meta property="og:title" content="<?= $title ?>">
  <meta property="og:description" content="<?= $description ?>">
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

    <!-- Left column – events + reports -->
    <div class="col-lg-8">
      <!-- Events -->
      <?php if ($first_item): ?>
        <h2 class="h3 mb-4 pb-2 border-bottom">анонсы</h2>
      <?php endif; ?>
      <div class="mb-4">

<?php if ($event_item):
          $date_time = explode(" ", $event_item[0] ?? '') ?? [];
          $place     = explode(",", $event_item[1] ?? '') ?? [];
          $title     = $event_item[2] ?? 'Без названия';
          $desc      = $event_item[3] ?? '';
          $name      = $event_item[4] ?? '';
          $image     = generateUrl($date_time[0], 'images/events', $name, 'jpg');
          $href      = generateUrl($date_time[0], 'events', $name);
        ?>
        <div class="card mb-4 border-0 shadow-sm overflow-hidden">
          <div class="row g-0">
            <?php if ($image): ?>
            <div class="col-md-4">
              <img src="<?= htmlspecialchars($image) ?>" class="img-fluid announce-img" alt="<?= htmlspecialchars($title) ?>">
            </div>
            <?php endif; ?>
            <div class="col-md-8">
              <div class="card-body">
                <?php if ($place): ?>
                <div class="text-muted small mb-2">
                  <i class="bi bi-calendar-event"></i> <?= formatDateRu($date_time[0]) ?> · <?= htmlspecialchars($date_time[1]) ?>
                  · <?= htmlspecialchars(trim($place[0])) ?>
                  <i class="bi bi-geo-alt"></i> <a href="<?= htmlspecialchars(trim($place[2])) ?>" target="_blank">
                    <?= htmlspecialchars(trim($place[1])) ?>
                  </a>
                </div>
                <hr class="my-3">
                <?php endif; ?>
                <h5 class="card-title fs-4 mb-3"><?= htmlspecialchars($title) ?></h5>
              </div>
            </div>
          </div>
          <div class="card-body pt-3 p-lg-4">
              <?php if (!empty($event_detail)): ?>
              <div class="report-content">
                <?php foreach ($event_detail as $block): ?>
                  <p class="mb-3"><?= nl2br(parseMarkdown(implode("\n", $block))) ?></p>
                <?php endforeach; ?>
              </div>
              <?php else: ?>
                <p class="text-muted">request path: <?= $uri ?></p>
              <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
        <?php if ($first_item):
          $date_time = explode(" ", $first_item[0] ?? '') ?? [];
          $place     = explode(",", $first_item[1] ?? '') ?? [];
          $title     = $first_item[2] ?? 'Без названия';
          $desc      = $first_item[3] ?? '';
          $name      = $first_item[4] ?? '';
          $image     = generateUrl($date_time[0], 'images/events', $name, 'jpg');
          $href      = generateUrl($date_time[0], 'events', $name);
        ?>
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
                <p class="card-text text-muted mb-3"><?= htmlspecialchars($desc) ?></p>
                <a href="<?= htmlspecialchars($href) ?>" class="btn btn-sm btn-outline-primary">подробнее <i class="bi bi-arrow-right"></i></a>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

<?php foreach ($events as $item):
          $date_time = explode(" ", $item[0]) ?? '';
          $place     = explode(",", $item[1] ?? '') ?? [];
          $title     = $item[2] ?? 'Без названия';
          $desc      = $item[3] ?? '';
          $name      = $item[4] ?? '';
          $image     = generateUrl($date_time[0], 'images/events', $name, 'jpg');
          $href      = generateUrl($date_time[0], 'events', $name);
        ?>
        <div class="border-bottom py-3">
          <div class="text-muted small mb-1">
             <i class="bi bi-calendar-event"></i> <?= formatDateRu($date_time[0]) ?> · <?= htmlspecialchars($date_time[1]) ?>
             <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($place[0]) ?>
          </div>
          <h5 class="mb-1"><?= htmlspecialchars($title) ?></h5>
          <p class="text-muted mb-2"><?= htmlspecialchars($desc) ?>… <a href="<?= htmlspecialchars($href) ?>">подробнее <i class="bi bi-arrow-right"></i></a></p>
        </div>
<?php endforeach; ?>

      </div>

      <div class="text-center mt-5">
        <a href="/reports" class="btn btn-outline-primary">отчёты и репортажи <i class="bi bi-arrow-right"></i></a>
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
            <img src="<?= getThumbnail($image) ?>" class="person-img" loading="lazy" decoding="async" alt="<?= htmlspecialchars($name) ?>">
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