<?php
$jsonLd = '';

$metaTags = '';
// 1) Build absolute site base (so social bots get a full URL)
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'];
$baseUrl  = "{$scheme}://{$host}";

// 2) Derive the share‐image path & URL
$date         = $entry['date'] ?? '';                     // e.g. "2025-07-27"
$shareRelPath = APOD_BASE_PATH . "/images/share/apod-{$date}-full.webp";
$shareUrl     = "{$baseUrl}{$shareRelPath}";              // absolute

// 3) Sanitize everything once
$safeShareUrl = htmlspecialchars($shareUrl, ENT_QUOTES, 'UTF-8');
$safeFullUrl = $safeShareUrl;
$safePageUrl  = htmlspecialchars("{$baseUrl}{$_SERVER['REQUEST_URI']}", ENT_QUOTES, 'UTF-8');
$safeTitle    = htmlspecialchars($entry['title']       ?? '', ENT_QUOTES, 'UTF-8');
$safeDesc     = htmlspecialchars($entry['explanation'] ?? '', ENT_QUOTES, 'UTF-8');
$dt = $date;
//$safeFullUrl = htmlspecialchars($fullUrl, ENT_QUOTES, 'UTF-8');

$t = $safeTitle;
$d = $safeDesc;
$u = $safeShareUrl;
$p = $safePageUrl;

if (isset($page)) {
  switch ($page) {
    case 'image':

      $jsonLd = <<<JSONLD
      <script type="application/ld+json">
      {
        "@context":"https://schema.org",
        "@type":"ImageObject",
        "name":"{$t}",
        "description":"{$d}",
        "contentUrl":"{$u}",
        "url":"{$p}",
        "datePublished":"{$dt}",
        "dateModified":"{$dt}",
        "author":{"@type":"Organization","name":"NASA"},
        "license":"https://apod.nasa.gov/apod/lib/ApodCopyright.php"
      }
      </script>
      JSONLD;
      $metaTags = <<<HTML
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Domine:wght@400..700&display=swap" rel="stylesheet">
      <meta name="description" content="{$safeDesc}">
      <link rel="canonical" href="{$safePageUrl}">

      <!-- Open Graph -->
      <meta property="og:type"        content="article">
      <meta property="og:title"       content="{$safeTitle}">
      <meta property="og:description" content="{$safeDesc}">
      <meta property="og:url"         content="{$safePageUrl}">
      <meta property="og:image"       content="{$safeFullUrl}">
      <meta property="og:image:alt"   content="{$safeTitle}">
      <meta property="og:site_name"   content="APOD">

      <!-- Twitter Card -->
      <meta name="twitter:card"        content="summary_large_image">
      <meta name="twitter:site"        content="@NASA_APOD">
      <meta name="twitter:title"       content="{$safeTitle}">
      <meta name="twitter:description" content="{$safeDesc}">
      <meta name="twitter:image"       content="{$safeFullUrl}">
      <meta name="twitter:image:alt"   content="{$safeTitle}">

      <!-- Pinterest Rich Pin -->
      <meta property="pin:description"   content="{$safeDesc}">
      <meta property="og:image:width"    content="1200">
      <meta property="og:image:height"   content="630">
      HTML;
      echo $metaTags;
      echo '<link rel="stylesheet" href="/apod/assets/css/lightbox.min.css?cb=' . $cb . '" type="text/css">';
      break;

    case 'home':
    case 'gallery':
      $p = htmlspecialchars($pageUrl ?? '', ENT_QUOTES, 'UTF-8');
      $jsonLd = <<<JSONLD
        <script type="application/ld+json">
        {
          "@context":"https://schema.org",
          "@type":"CollectionPage",
          "name":"APOD Gallery",
          "description":"A gallery of Astronomy Pictures of the Day",
          "url":"{$p}"
        }
        </script>
        JSONLD;
      echo '<link rel="stylesheet" href="/apod/assets/css/gallery.min.css?cb=' . $cb . '" type="text/css">';
      break;

    case 'about':
      $p = htmlspecialchars($pageUrl ?? '', ENT_QUOTES, 'UTF-8');
      $jsonLd = <<<JSONLD
      <script type="application/ld+json">
      {
        "@context":"https://schema.org",
        "@type":"AboutPage",
        "name":"About APOD",
        "description":"Learn about the Astronomy Picture of the Day project and its mission.",
        "url":"{$p}"
      }
      </script>
      JSONLD;
      break;

      default:
        echo 'nope';
        break;
  }
}

// Echo it (or blank if none)
echo $jsonLd;
