<?php

declare(strict_types=1);

function apod_h(?string $value): string
{
  return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * @phpstan-param ApodEntry $entry
 */
function apod_media_type(array $entry): string
{
  return strtolower((string)($entry['media_type'] ?? 'image'));
}

/**
 * @phpstan-param ApodEntry $entry
 */
function apod_render_media(array $entry): string
{
  if (apod_media_type($entry) === 'video') {
    return apod_render_video_media($entry);
  }

  ob_start();
  include APOD_APP . '/includes/lightbox.php';
  return (string)ob_get_clean();
}

/**
 * @phpstan-param ApodEntry $entry
 */
function apod_video_embed_url(array $entry): string
{
  $url = (string)($entry['url_video'] ?? $entry['url'] ?? '');
  if ($url === '') {
    return '';
  }

  $parts = parse_url($url);
  $scheme = strtolower((string)($parts['scheme'] ?? ''));
  $host = strtolower((string)($parts['host'] ?? ''));
  $allowedHosts = [
    'www.youtube.com',
    'youtube.com',
    'www.youtube-nocookie.com',
    'youtube-nocookie.com',
    'player.vimeo.com',
    'apod.nasa.gov',
  ];

  if ($scheme !== 'https' || !in_array($host, $allowedHosts, true)) {
    return '';
  }

  return $url;
}

/**
 * @phpstan-param ApodEntry $entry
 */
function apod_video_poster_url(array $entry): string
{
  foreach ([1200, 980, 640, 440] as $width) {
    if (!empty($entry['url_main'][$width])) {
      return (string)$entry['url_main'][$width];
    }
  }

  if (!empty($entry['url_thumb'])) {
    return (string)$entry['url_thumb'];
  }

  return APOD_BASE_PATH . '/images/placeholder.webp';
}

/**
 * @phpstan-param ApodEntry $entry
 */
function apod_render_video_media(array $entry): string
{
  $title = (string)($entry['title'] ?? 'Astronomy Picture of the Day video');
  $slug = (string)($entry['slug'] ?? preg_replace('/[^a-z0-9-]+/', '-', strtolower($title)));
  $posterUrl = apod_video_poster_url($entry);
  $embedUrl = apod_video_embed_url($entry);
  $frameId = 'apod-video-frame-' . $slug;
  $panelId = 'apod-video-panel-' . $slug;

  $safeTitle = apod_h($title);
  $safePoster = apod_h($posterUrl);
  $safeEmbed = apod_h($embedUrl);
  $safeFrameId = apod_h($frameId);
  $safePanelId = apod_h($panelId);

  $button = '';
  $frame = '';
  $fallback = '';

  if ($embedUrl !== '') {
    $button = <<<HTML
        <button
          class="apod-video-play"
          type="button"
          data-apod-video-load
          aria-expanded="false"
          aria-controls="{$safePanelId}">
          <span aria-hidden="true" class="apod-video-play-icon"></span>
          <span>Load video player</span>
        </button>
HTML;

    $frame = <<<HTML
    <div id="{$safePanelId}" class="apod-video-frame" tabindex="-1" hidden>
      <iframe
        id="{$safeFrameId}"
        title="Video: {$safeTitle}"
        data-src="{$safeEmbed}"
        allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        allowfullscreen
        referrerpolicy="strict-origin-when-cross-origin"></iframe>
    </div>
HTML;

    $fallback = <<<HTML
      <noscript>
        <p class="apod-video-fallback">
          JavaScript is disabled. <a href="{$safeEmbed}" target="_blank" rel="noopener">Open the video for {$safeTitle}</a>.
        </p>
      </noscript>
HTML;
  }

  return <<<HTML
  <div class="apod-media apod-video" data-apod-video>
    <div class="apod-video-poster">
      <img
        src="{$safePoster}"
        alt="{$safeTitle} video poster"
        width="1200"
        height="675"
        decoding="async">
{$button}
    </div>

{$frame}
{$fallback}
  </div>
HTML;
}
