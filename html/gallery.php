<?php
// gallery.php version 0.2 by 11-Apr-26

require_once __DIR__ . '/functions.php';

// read files
$authors = readBlocks('data/authors.txt');

// parse url
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts = explode('/', $uri);

$author_uname = null;
$author_item = null;
$gallery_item_name = null;
$number_authors = 10;

// if exists author
if (count($parts) > 1) {
    $author_uname = strtolower($parts[1]);
    // if exists item of gallery
    if (count($parts) > 2) {
        $gallery_item_name = strtolower($parts[2]);
    }
}

// get list of galleries if not define author
$authors_names = [];
if (count($parts) == 1) {
    $iterator = new DirectoryIterator('data/gallery/');
    foreach ($iterator as $file) {
        if ($file->isDir() && !$file->isDot()) {
            $authors_names[] = $file->getFilename();
        }
    }
}

// get authors who have galleries
$authors_with_gallery = [];
foreach ($authors as $key => $item) {
    if (in_array($item[1], $authors_names)) {
        $authors_with_gallery[] = $item;
    }
}

// find selected author & read its gallery items (files)
$gallery_items = [];
if ($author_uname) {
    foreach ($authors as $key => $item) {
        $nick = explode(" ", $item[1] ?? '') ?? [];
        if ($nick[0] === $author_uname) {
            $author_item = $item;
            unset($authors[$key]); // remove found item
            break;
        }
    }
    // read author's gallery items
    $iterator = new DirectoryIterator('data/gallery/' . $author_uname);
    foreach ($iterator as $file) {
        if ($file->isFile() && !$file->isDot() && $file->getExtension() === 'txt') {
            $file_name = $file->getBasename('.txt');
            $detail_path = "data/gallery/{$author_uname}/{$file_name}.txt";
            if (file_exists($detail_path)) {
                $gallery_item = readBlocks($detail_path);
                $gallery_items[$file_name] = $gallery_item;
            }
        }
    }
    $gallery_items = sortGalleryItemsByDate($gallery_items);
}

// 404 page
if ($author_item == null && $author_uname != null) {
    $author_item = ['ошибочка 404', $author_uname . ' unknown', 'вы кого тут ищете? нету такого…'];
}

// shuffle the list of authors
shuffle($authors);

// image for preview
$og_image = $author_item ? '/images/authors/' . $author_item[1] . '.jpg' : '/images/logo.jpg';

// title & description
$title = 'Галереи – Пражские витражи';
$description = 'Работы русскоязычных поэтов, художников, музыкантов и других людей творчества, живущих в Праге и Чехии.';
if ($author_item) {
    $title = htmlspecialchars($author_item[0]) . ' – ' . $title;
    $description = empty($author_detail) ? htmlspecialchars($author_item[0]) . ' – ' . $description : htmlspecialchars($author_detail[0][0]);
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

<?php if ($author_item && !$gallery_item_name):
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
              <p class="text-muted mb-4">@<a href="/authors/<?= $nickname[0] ?>"><?= htmlspecialchars(rawurldecode($nickname[0])) ?></a>
                <i class="bi bi-collection"></i> галерея работ</a>
              </p>
            </div>
          </div>
        </div>
      </div>
<?php elseif ($gallery_item_name): 
            $gallery_detail = $gallery_items[$gallery_item_name];
            $header = explode(" ", $gallery_detail[0][0] ?? '') ?? [];
            $title  = implode(' ', array_slice($header, 2));
            $image  = 'images/gallery/' . $author_uname . '/' . $gallery_item_name . '.jpg';
            unset($gallery_detail[0]);
      ?>
      <!-- detail of selected item of gallery -->
      <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-4 p-lg-5">
<?php if (is_file($image)): ?>
          <img src="/<?= $image ?>" class="img-fluid rounded-4 shadow-sm mx-auto d-block"
         style="max-height: 82vh; object-fit: contain;" alt="<?= $title ?>">
<?php else: ?>
          <h1 class="h3 fw-bold mb-2"><?= $title ?></h1>
          <hr class="my-4">
<?php endif; ?>
<?php foreach ($gallery_detail as $block): ?>
          <div class="mt-4">
            <p class="mb-3"><?= nl2br(parseMarkdown(implode("\n", $block))) ?></p>
          </div>
<?php endforeach; ?>
        </div>
      </div>
<?php endif; ?>

      <!-- Карточки авторов (все или остальные) -->
      <h2 class="h3 mb-4 pb-2 border-bottom">
        <?= $author_uname ? 'галерея · ' . $author_item[0] : 'галереи' ?>
      </h2>

      <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-2">
<?php if (empty($gallery_items)): ?>
<?php foreach ($authors_with_gallery as $item):
            $name     = $item[0] ?? '';
            $nickname = explode(" ", $item[1] ?? '') ?? [];
            $role     = $item[2] ?? '';
            $image    = '/images/authors/' . (count($nickname) > 1 ? $nickname[1] : $nickname[0]) . '.jpg';
            $href     = '/gallery/' . $nickname[0];
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
<?php else: ?>
<?php foreach ($gallery_items as $key => $item):
            $header = explode(" ", $item[0][0] ?? '') ?? [];
            $icon   = $header[0] ?? '';
            $date   = $header[1] ?? '';
            $title  = implode(' ', array_slice($header, 2));
            $href   = '/gallery/' . $author_uname . '/' . $key;
        ?>
        <div class="col">
          <a href="<?= htmlspecialchars($href) ?>" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm author-card">
              <div class="card-body text-center p-4">
                <h5 class="card-title mb-1"><i class="bi <?= $icon ?>"></i> <?= htmlspecialchars($title) ?></h5>
                <p class="card-text text-muted small"><?= formatDateRu($date) ?></p>
              </div>
            </div>
          </a>
        </div>
<?php endforeach; ?>
<?php endif; ?>
      </div>

<?php if ($author_uname): ?>
      <div class="text-center mt-5">
        <a href="/gallery/" class="btn btn-outline-primary">все галереи <i class="bi bi-arrow-right"></i></a>
      </div>
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