# APOD Recreation

A PHP recreation of NASA's Astronomy Picture of the Day experience, focused on accessibility, readable server-rendered pages, and a lightweight no-database architecture.

Live site: `https://alanfullbeard.com/apod`

## Project Priorities

- Accessible browsing for keyboard, screen reader, low-vision, zoomed-layout, and reduced-motion users.
- Simple deployment under `/apod` on Apache/PHP.
- No database dependency; APOD metadata is read from JSON.
- Source-only repository. Generated APOD media is intentionally not tracked.

## Structure

```text
.
├── app/
│   ├── includes/     # shared PHP includes and head/lightbox helpers
│   ├── pages/        # route-level page views
│   └── partials/     # header, footer, and shared layout fragments
├── assets/
│   ├── css/          # source and minified stylesheets
│   └── js/           # source and minified scripts
├── data/             # APOD JSON metadata and content helpers
├── scripts/          # local maintenance utilities
├── images/           # generated media, ignored by git
├── thumbs/           # generated thumbnails, ignored by git
├── .htaccess         # Apache routing and access rules
└── index.php         # front controller
```

## Local Development

Place the project at `/opt/lampp/htdocs/apod` or another Apache document path that serves it at `/apod`.

Useful checks after PHP changes:

```bash
scripts/php-lint.sh
scripts/check-source-only.sh
scripts/check-file-modes.sh
scripts/check-assets.sh
curl -A 'Mozilla/5.0' -I http://localhost/apod/
```

## Media Policy

`images/` and `thumbs/` are generated local assets and are excluded from git. The JSON data may reference those paths, but the media files themselves should be generated or deployed separately.

The share-image utility lives at:

```bash
php scripts/generate-share-images.php
```

## Current Technical Debt

- Gallery results are still rendered by JavaScript. Future work should move result and navigation markup to PHP to align with the project's no-JavaScript-rendering rule.
- `assets/js/lightbox.js.deprecated` is retained temporarily while confirming no behavior depends on the old standalone lightbox script.
