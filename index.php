<?php
define('APOD_ROOT', __DIR__);
define('APOD_APP', APOD_ROOT . '/app');
define('APOD_DATA_FILE', APOD_ROOT . '/data/apod.local.json');
define('APOD_BASE_PATH', '/apod');

require_once APOD_APP . '/includes/pretty-errors.php';
require_once APOD_APP . '/includes/assets.php';

$page = $_GET['page'] ?? 'gallery';
$slug = $_GET['slug'] ?? null;

// Only allow known pages
$allowedPages = ['gallery', 'image', 'about', 'random'];
if (!in_array($page, $allowedPages)) {
  http_response_code(404);
  $page = '404';
}
// Slug validation for image pages
// Load and decode the APOD dataset
$dataJson = file_get_contents(APOD_DATA_FILE);
$apodData = json_decode($dataJson, true);

// pick random image
$imageEntries = array_filter($apodData, fn($e) => ($e['media_type'] ?? '') === 'image');
$rand = $imageEntries[array_rand($imageEntries)];
$randomUrl = APOD_BASE_PATH . "/image/{$rand['slug']}";

if ($page === 'random') {
  header("Location: {$randomUrl}", true, 302);
  exit;
}

if ($page === 'image') {

  $validSlugs = array_column($apodData, 'slug');

  $entry = null;
  foreach ($apodData as $item) {
    if (($item['slug'] ?? '') === $slug) {
      $entry = $item;
      break;
    }
  }
  if (!in_array($slug, $validSlugs)) {
    http_response_code(404);
    $page = '404';
  }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Astronomy Picture of the Day</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Explore NASA's Astronomy Picture of the Day archive.">
  <!-- preload fonts -->
  <!-- Lexend 400 (Regular) -->
  <link rel="preload" href="<?= APOD_BASE_PATH ?>/assets/css/fonts/lexend-400.woff2" as="font" type="font/woff2" crossorigin>

  <!-- Lexend 700 (Bold) -->
  <link rel="preload" href="<?= APOD_BASE_PATH ?>/assets/css/fonts/lexend-700.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="<?= APOD_BASE_PATH ?>/assets/css/fonts/zen-dots.woff2" as="font" type="font/woff2" crossorigin>

  <link href="<?= apod_asset_url('assets/css/critical.min.css') ?>" rel="stylesheet" type="text/css">
  <link href="<?= apod_asset_url('assets/css/main.min.css') ?>" rel="stylesheet" type="text/css" media="print" onload="this.media='all';">
  <noscript><link href="<?= apod_asset_url('assets/css/main.min.css') ?>" rel="stylesheet" type="text/css"></noscript>
  <?php include APOD_APP . '/includes/conditions.php'; ?>
</head>

<body>

  <?php include APOD_APP . '/partials/header.php'; ?>

  <main id="main" tabindex="-1" class="flex-wrap">
    <?php
    // Page Content
    include APOD_APP . '/pages/' . $page . '.php';

    // header text
    $pagesWithHeaderText = ['image', 'gallery', 'about'];
    if (in_array($page, $pagesWithHeaderText)) {
      include APOD_APP . '/partials/header-text.php';
    }

    ?>
  </main>

  <?php include APOD_APP . '/partials/footer.php'; ?>

</body>

</html>
