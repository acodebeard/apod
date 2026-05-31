#!/usr/bin/env php
<?php

declare(strict_types=1);

define('APOD_ROOT', dirname(__DIR__));
define('APOD_APP', APOD_ROOT . '/app');
define('APOD_BASE_PATH', '/apod');

require_once APOD_APP . '/includes/media.php';

function assert_contains(string $needle, string $haystack, string $message): void
{
    if (!str_contains($haystack, $needle)) {
        fwrite(STDERR, "FAIL: {$message}\nMissing: {$needle}\n");
        exit(1);
    }
}

function assert_not_contains(string $needle, string $haystack, string $message): void
{
    if (str_contains($haystack, $needle)) {
        fwrite(STDERR, "FAIL: {$message}\nUnexpected: {$needle}\n");
        exit(1);
    }
}

function assert_iframe_has_no_src(string $html, string $message): void
{
    if (preg_match('/<iframe\b[^>]*\ssrc\s*=/i', $html) === 1) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$videoEntry = [
    'date' => '2025-01-05',
    'title' => 'Rocket Launch as Seen from the International Space Station',
    'slug' => 'rocket-launch-as-seen-from-the-international-space-station',
    'media_type' => 'video',
    'url' => 'https://www.youtube.com/embed/B1R3dTdcpSU?rel=0',
    'thumbnail_url' => 'https://img.youtube.com/vi/B1R3dTdcpSU/0.jpg',
    'url_thumb' => '/apod/thumbs/2025-01-05.webp',
    'url_main' => [
        '640' => '/apod/images/main/640/apod-2025-01-05-poster.webp',
        '980' => '/apod/images/main/980/apod-2025-01-05-poster.webp',
    ],
];

$imageEntry = [
    'date' => '2025-07-11',
    'title' => 'The Veins of Heaven',
    'slug' => 'the-veins-of-heaven',
    'media_type' => 'image',
    'url_full' => '/apod/images/full/apod-2025-07-11-full.jpg',
    'url_thumb' => '/apod/thumbs/2025-07-11.webp',
    'url_main' => [
        '440' => '/apod/images/main/440/apod-2025-07-11-full.webp',
        '640' => '/apod/images/main/640/apod-2025-07-11-full.webp',
        '980' => '/apod/images/main/980/apod-2025-07-11-full.webp',
        '1200' => '/apod/images/main/1200/apod-2025-07-11-full.webp',
    ],
];

$blockedVideoEntry = $videoEntry;
$blockedVideoEntry['url'] = 'http://evil.example/embed';
$blockedVideoEntry['url_video'] = 'javascript:alert(1)';

$videoHtml = apod_render_media($videoEntry);
assert_contains('data-apod-video', $videoHtml, 'Video entries render the video component wrapper.');
assert_contains('data-src="https://www.youtube.com/embed/B1R3dTdcpSU?rel=0"', $videoHtml, 'Video embed URL is stored in data-src.');
assert_iframe_has_no_src($videoHtml, 'Video iframe does not load third-party source initially.');
assert_contains('Load video player', $videoHtml, 'Video entries expose an activation control.');
assert_contains('aria-expanded="false"', $videoHtml, 'Video activation control exposes collapsed state.');
assert_contains('<iframe', $videoHtml, 'Video entries include an iframe placeholder.');
assert_not_contains('lightbox-trigger', $videoHtml, 'Video entries do not use lightbox markup.');

$blockedVideoHtml = apod_render_media($blockedVideoEntry);
assert_not_contains('<iframe', $blockedVideoHtml, 'Blocked video URLs do not render iframe placeholders.');
assert_not_contains('javascript:alert', $blockedVideoHtml, 'Blocked video URLs are not exposed in markup.');

$imageHtml = apod_render_media($imageEntry);
assert_contains('lightbox-trigger', $imageHtml, 'Image entries retain lightbox trigger markup.');
assert_contains('data-full="/apod/images/full/apod-2025-07-11-full.jpg"', $imageHtml, 'Image entries retain full-size image URL.');
assert_not_contains('data-apod-video', $imageHtml, 'Image entries do not render video wrappers.');

fwrite(STDOUT, "Video support check passed.\n");
