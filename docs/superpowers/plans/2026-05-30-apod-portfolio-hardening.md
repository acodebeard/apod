# APOD Portfolio Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Harden the APOD recreation for public portfolio use by fixing web-root security, moving archive rendering server-side, improving image/lightbox state handling, and adding repeatable verification.

**Architecture:** Keep `index.php` and `.htaccess` at the Apache web root for the existing `/apod` deployment. Keep application internals in `app/`, public CSS/JS in `assets/`, source metadata in `data/`, and local utilities in `scripts/`. Generated APOD media stays out of git.

**Tech Stack:** Plain PHP 8.2, Apache `.htaccess`, JSON metadata, static CSS/JS, GitHub Actions, shell verification scripts.

---

## Review Findings Incorporated

Five focused review agents inspected the current repo before this update.

- Resource inclusion review: security rewrites are ordered too late, cache busting is random/inconsistent, WebP/WOFF2 MIME and cache headers are weak, and required UI chrome currently depends on ignored `images/` assets.
- Archive/gallery review: archive results and pagination are JavaScript-rendered through `innerHTML`, gallery links derive slugs from titles, relative links can break under trailing-slash URLs, and view state is visual-only.
- Image/lightbox review: valid stored-slug image pages can render as "Image not found", explanations are output without escaping, `aria-describedby` points at a missing target, lightbox alt text is lost, background content is not inert while modal state is open, and document-wide swipe navigation remains active in the lightbox.
- Accessibility/security review: `.git` is publicly reachable from the web root, production error display leaks paths, mobile nav CSS and JS breakpoints disagree, the nav toggle points at a missing controlled id, and canonical/social URLs trust `HTTP_HOST`.
- Repo/plan review: the previous plan still preserved title-derived prev/next slugs, deprecated-lightbox verification could not pass as written, file executable modes are noisy, `AGENTS.md` is stale about repo state, minified asset drift is unchecked, and direct `apod.local.json` access should be blocked once server rendering replaces the client fetch.

---

## File Structure

- Modify `.htaccess`: block hidden paths before real-file passthrough, move deny rules before terminal rewrites, add MIME/cache rules, and block active data after server rendering lands.
- Modify `app/includes/pretty-errors.php`: disable display errors outside explicit local debug mode.
- Create `app/includes/apod-data.php`: central APOD data loading, slug lookup, pagination, URL, and escaping helpers.
- Create `app/includes/assets.php`: deterministic asset version helper using `filemtime`.
- Modify `index.php`: load helpers, use deterministic asset URLs, stop random cache-busting, expose shared page state.
- Modify `app/includes/conditions.php`: use configured canonical host and deterministic asset URLs.
- Modify `app/partials/header.php`: remove ignored image dependency, align mobile nav state, and fix controlled ids.
- Modify `assets/css/critical.css` and `assets/css/critical.min.css`: remove ignored image dependencies, add CSS hamburger styling, and keep reduced-motion behavior.
- Modify `app/pages/gallery.php`: render archive cards and pagination in PHP.
- Modify `assets/js/apod.js` and `assets/js/apod.min.js`: keep only view-toggle enhancement.
- Modify `app/pages/image.php`: use shared data helpers, stored slugs, escaped explanation, and safer swipe behavior.
- Modify `app/includes/lightbox.php`: add modal naming, preserved alt text, inert background state, fallback `aria-hidden`, focus restoration, and closed-state cleanup.
- Create `.github/workflows/ci.yml`: run PHP, source-only, file-mode, and asset-sync checks.
- Create `scripts/php-lint.sh`: local and CI PHP syntax checks.
- Create `scripts/check-source-only.sh`: ensure generated media and local utilities are untracked.
- Create `scripts/check-file-modes.sh`: ensure only intended scripts are executable.
- Create `scripts/check-assets.sh`: ensure minified JS matches source.
- Create `docs/accessibility-checklist.md`: manual accessibility review checklist.
- Delete `assets/js/lightbox.js.deprecated` after confirming no runtime references remain.
- Modify `README.md` and `AGENTS.md`: document repo status, verification, accessibility, and source-only constraints.

---

### Task 1: Fix Web-Root Security And Routing Order

**Files:**
- Modify: `.htaccess`

- [ ] **Step 1: Move deny rules before real-file passthrough**

Rewrite the top of `.htaccess` so the first rules after `RewriteBase /apod/` are:

```apache
# Block hidden files and directories such as .git before real-file passthrough.
RewriteRule (^|/)\. - [F,L]

# Keep application internals and utility scripts out of direct web access.
RewriteRule ^(app|scripts)/ - [F,L]
RewriteRule ^audit\.php$ - [F,L]
RewriteRule ^(README|AGENTS)\.md$ - [F,L]
RewriteRule ^data/(apod\.json|stopwords\.txt)$ - [F,L]

# Basic bot blocking. Keep before static and pretty-route short circuits.
RewriteCond %{HTTP_USER_AGENT} libwww-perl|python-requests|wget|curl [NC]
RewriteRule ^ - [F,L]

# Reject suspicious requests before static and pretty-route short circuits.
RewriteCond %{REQUEST_URI} ^.{2000,} [OR]
RewriteCond %{QUERY_STRING} (\.\./|etc/passwd|input_file|mosConfig_) [NC]
RewriteRule ^ - [F,L]
```

Keep the existing static asset passthrough and pretty routes below this block.

- [ ] **Step 2: Add explicit MIME types**

Add this block above the `mod_expires` section:

```apache
AddType image/webp .webp
AddType image/svg+xml .svg
AddType font/woff2 .woff2
AddType application/json .json
```

- [ ] **Step 3: Strengthen static cache headers**

Replace the existing `mod_expires` block with:

```apache
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/webp "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/svg+xml "access plus 1 year"
  ExpiresByType font/woff2 "access plus 1 year"
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
  ExpiresByType application/json "access plus 5 minutes"
</IfModule>
```

- [ ] **Step 4: Verify blocked and allowed routes**

Run:

```bash
for url in \
  http://localhost/apod/ \
  http://localhost/apod/gallery \
  http://localhost/apod/assets/css/critical.min.css \
  http://localhost/apod/data/apod.local.json \
  http://localhost/apod/.git/HEAD \
  http://localhost/apod/.git/config \
  http://localhost/apod/app/pages/gallery.php \
  http://localhost/apod/scripts/generate-share-images.php \
  http://localhost/apod/audit.php \
  'http://localhost/apod/gallery?x=../etc/passwd'
do
  printf '%s -> ' "$url"
  curl -A 'Mozilla/5.0' -sS -o /dev/null -w '%{http_code}\n' "$url"
done
```

Expected:

```text
/apod/, /gallery, /assets/css/critical.min.css, and /data/apod.local.json return 200
.git, app, scripts, audit.php, and suspicious query return 403
```

- [ ] **Step 5: Verify bot blocking**

Run:

```bash
curl -A 'python-requests' -sS -o /dev/null -w '%{http_code}\n' http://localhost/apod/gallery
curl -A 'wget' -sS -o /dev/null -w '%{http_code}\n' http://localhost/apod/gallery
```

Expected:

```text
403
403
```

- [ ] **Step 6: Commit**

Run:

```bash
git add .htaccess
git commit -m "Fix web-root security rules"
```

---

### Task 2: Disable Production Error Disclosure

**Files:**
- Modify: `app/includes/pretty-errors.php`
- Modify: `README.md`
- Modify: `AGENTS.md`

- [ ] **Step 1: Replace unconditional display errors**

Replace the top of `app/includes/pretty-errors.php` with:

```php
<?php
$apodDebug = filter_var(getenv('APOD_DEBUG') ?: '0', FILTER_VALIDATE_BOOLEAN);

ini_set('log_errors', '1');
ini_set('display_errors', $apodDebug ? '1' : '0');
error_reporting(E_ALL);

if (!$apodDebug) {
  return;
}
```

Keep the existing debug rendering code below this guard.

- [ ] **Step 2: Document local debug mode**

Add this to `README.md` under Local Development:

```markdown
Set `APOD_DEBUG=1` only in local development to render friendly PHP errors. Production should leave `APOD_DEBUG` unset or set to `0` so errors are logged server-side instead of displayed.
```

Add the same rule in shorter form to `AGENTS.md` under Coding Guidelines:

```markdown
- Do not enable displayed PHP errors in production. Use `APOD_DEBUG=1` only for local debugging.
```

- [ ] **Step 3: Verify syntax and production mode**

Run:

```bash
php -l app/includes/pretty-errors.php
curl -A 'Mozilla/5.0' -sS http://localhost/apod/ | rg 'php-error|Fatal error|Warning' || true
```

Expected: PHP syntax passes, and the curl command prints nothing.

- [ ] **Step 4: Commit**

Run:

```bash
git add app/includes/pretty-errors.php README.md AGENTS.md
git commit -m "Disable production error display"
```

---

### Task 3: Add Repo Verification Scripts And CI

**Files:**
- Create: `.github/workflows/ci.yml`
- Create: `scripts/php-lint.sh`
- Create: `scripts/check-source-only.sh`
- Create: `scripts/check-file-modes.sh`
- Create: `scripts/check-assets.sh`
- Modify: `README.md`
- Modify: `AGENTS.md`

- [ ] **Step 1: Create PHP lint script**

Create `scripts/php-lint.sh`:

```bash
#!/usr/bin/env bash
set -euo pipefail

php -l index.php
find app scripts -name '*.php' -print0 | xargs -0 -n1 php -l
```

- [ ] **Step 2: Create source-only guard**

Create `scripts/check-source-only.sh`:

```bash
#!/usr/bin/env bash
set -euo pipefail

tracked_generated="$(git ls-files | grep -E '^(images|thumbs)/|^audit\.php$' || true)"

if [[ -n "$tracked_generated" ]]; then
  printf 'Generated or local-only files are tracked:\n%s\n' "$tracked_generated" >&2
  exit 1
fi

printf 'Source-only check passed.\n'
```

- [ ] **Step 3: Create file-mode guard**

Create `scripts/check-file-modes.sh`:

```bash
#!/usr/bin/env bash
set -euo pipefail

allowed_executable='^(scripts/(generate-share-images\.php|php-lint\.sh|check-source-only\.sh|check-file-modes\.sh|check-assets\.sh))$'

bad_modes="$(
  git ls-files --stage \
    | awk -v allowed="$allowed_executable" '$1 == "100755" && $4 !~ allowed { print $4 }'
)"

if [[ -n "$bad_modes" ]]; then
  printf 'Unexpected executable tracked files:\n%s\n' "$bad_modes" >&2
  exit 1
fi

printf 'File-mode check passed.\n'
```

- [ ] **Step 4: Create asset sync guard**

Create `scripts/check-assets.sh`:

```bash
#!/usr/bin/env bash
set -euo pipefail

expected='const thumbBtn=document.getElementById("thumbViewBtn"),listBtn=document.getElementById("listViewBtn"),apodGallery=document.getElementById("apodGallery");thumbBtn&&listBtn&&apodGallery&&(thumbBtn.addEventListener("click",()=>{apodGallery.classList.remove("list-view"),apodGallery.classList.add("grid-view"),thumbBtn.classList.add("active"),thumbBtn.setAttribute("aria-pressed","true"),listBtn.classList.remove("active"),listBtn.setAttribute("aria-pressed","false")}),listBtn.addEventListener("click",()=>{apodGallery.classList.add("list-view"),apodGallery.classList.remove("grid-view"),thumbBtn.classList.remove("active"),thumbBtn.setAttribute("aria-pressed","false"),listBtn.classList.add("active"),listBtn.setAttribute("aria-pressed","true")}));'

actual="$(tr -d '\n' < assets/js/apod.min.js)"

if [[ "$actual" != "$expected" ]]; then
  printf 'assets/js/apod.min.js does not match the expected minified view-toggle script.\n' >&2
  exit 1
fi

printf 'Asset sync check passed.\n'
```

- [ ] **Step 5: Make scripts executable and normalize file modes**

Run:

```bash
chmod +x scripts/generate-share-images.php scripts/php-lint.sh scripts/check-source-only.sh scripts/check-file-modes.sh scripts/check-assets.sh
find . -path ./.git -prune -o -type f ! -path './scripts/generate-share-images.php' ! -path './scripts/php-lint.sh' ! -path './scripts/check-source-only.sh' ! -path './scripts/check-file-modes.sh' ! -path './scripts/check-assets.sh' -exec chmod 644 {} +
```

- [ ] **Step 6: Create CI workflow**

Create `.github/workflows/ci.yml`:

```yaml
name: CI

on:
  push:
  pull_request:

jobs:
  checks:
    runs-on: ubuntu-latest

    steps:
      - name: Check out repository
        uses: actions/checkout@v4

      - name: Set up PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          coverage: none

      - name: Run PHP syntax checks
        run: scripts/php-lint.sh

      - name: Verify source-only repository
        run: scripts/check-source-only.sh

      - name: Verify tracked file modes
        run: scripts/check-file-modes.sh

      - name: Verify minified assets
        run: scripts/check-assets.sh
```

- [ ] **Step 7: Run verification locally**

Run:

```bash
scripts/php-lint.sh
scripts/check-source-only.sh
scripts/check-file-modes.sh
```

Do not run `scripts/check-assets.sh` until Task 7 replaces `assets/js/apod.min.js`.

- [ ] **Step 8: Document checks**

Add this section to `README.md`:

```markdown
## Verification

Run local checks before committing:

```bash
scripts/php-lint.sh
scripts/check-source-only.sh
scripts/check-file-modes.sh
scripts/check-assets.sh
curl -A 'Mozilla/5.0' -I http://localhost/apod/
```
```

Update `AGENTS.md` verification instructions to prefer the four scripts above.

- [ ] **Step 9: Update repository status in AGENTS**

In `AGENTS.md`, replace the old repository status with:

```markdown
- Repository: public GitHub repository at `https://github.com/acodebeard/apod`, default branch `main`.
```

Remove the working note that says there is no git history.

- [ ] **Step 10: Commit**

Run:

```bash
git add .github/workflows/ci.yml scripts/php-lint.sh scripts/check-source-only.sh scripts/check-file-modes.sh scripts/check-assets.sh README.md AGENTS.md
git commit -m "Add repository verification checks"
```

---

### Task 4: Fix Resource Inclusion And Source-Only UI Chrome

**Files:**
- Create: `app/includes/assets.php`
- Modify: `index.php`
- Modify: `app/includes/conditions.php`
- Modify: `app/partials/header.php`
- Modify: `assets/css/critical.css`
- Modify: `assets/css/critical.min.css`
- Modify: `README.md`

- [ ] **Step 1: Create deterministic asset helper**

Create `app/includes/assets.php`:

```php
<?php

function apod_asset_url(string $path): string
{
  $relative = ltrim($path, '/');
  $file = APOD_ROOT . '/' . $relative;
  $version = is_file($file) ? (string) filemtime($file) : '1';

  return APOD_BASE_PATH . '/' . $relative . '?v=' . rawurlencode($version);
}
```

- [ ] **Step 2: Load asset helper**

In `index.php`, add this after `pretty-errors.php`:

```php
require_once APOD_APP . '/includes/assets.php';
```

Replace `$cb = mt_rand(2, 20000);` with:

```php
$assetVersion = 'unused';
```

- [ ] **Step 3: Replace hard-coded CSS and font URLs**

In `index.php`, replace the CSS/font URLs with:

```php
<link rel="preload" href="<?= apod_asset_url('assets/css/fonts/lexend-400.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= apod_asset_url('assets/css/fonts/lexend-700.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= apod_asset_url('assets/css/fonts/zen-dots.woff2') ?>" as="font" type="font/woff2" crossorigin>

<link href="<?= apod_asset_url('assets/css/critical.min.css') ?>" rel="stylesheet" type="text/css">
<link href="<?= apod_asset_url('assets/css/main.min.css') ?>" rel="stylesheet" type="text/css" media="print" onload="this.media='all';">
<noscript><link href="<?= apod_asset_url('assets/css/main.min.css') ?>" rel="stylesheet" type="text/css"></noscript>
```

- [ ] **Step 4: Replace conditional stylesheet URLs**

In `app/includes/conditions.php`, replace stylesheet echoes with:

```php
echo '<link rel="stylesheet" href="' . apod_asset_url('assets/css/lightbox.min.css') . '" type="text/css">';
```

and:

```php
echo '<link rel="stylesheet" href="' . apod_asset_url('assets/css/gallery.min.css') . '" type="text/css">';
```

- [ ] **Step 5: Remove ignored image dependency from header**

In `app/partials/header.php`, replace:

```php
<img class="flex-none" src="/apod/images/nasa-logo.svg" width="65" height="65" alt="NASA Logo">
```

with:

```php
<span class="site-mark flex-none" aria-hidden="true">APOD</span>
```

- [ ] **Step 6: Remove ignored background and hamburger image dependencies**

In `assets/css/critical.css`, replace:

```css
background:url('/apod/images/webb-deep.webp') center no-repeat #232323;
background-size: cover;
background-blend-mode: overlay;
background-attachment: fixed;
```

with:

```css
background: #232323;
```

Add:

```css
.site-mark {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 65px;
  height: 65px;
  border: 2px solid var(--global-light);
  border-radius: 50%;
  color: var(--global-light);
  font-size: 0.85rem;
  font-weight: 700;
  letter-spacing: 0;
}

.site-header.is-stuck .site-mark {
  width: 35px;
  height: 35px;
  font-size: 0.65rem;
}
```

Replace the hamburger background image rule with CSS lines:

```css
.header-button button {
  background: transparent;
  border: none;
  color: transparent;
  position: relative;
  width: 40px;
  height: 40px;
}

.header-button button::before,
.header-button button::after,
.header-button button span {
  content: "";
  position: absolute;
  left: 8px;
  right: 8px;
  height: 3px;
  background: #fff;
}

.header-button button::before {
  top: 11px;
}

.header-button button span {
  top: 18px;
}

.header-button button::after {
  top: 25px;
}
```

- [ ] **Step 7: Add the hamburger span**

In `app/partials/header.php`, change the mobile nav button content to:

```html
<span aria-hidden="true"></span>
```

Keep its `aria-label`.

- [ ] **Step 8: Manually update `critical.min.css`**

Apply the same CSS changes to `assets/css/critical.min.css`. Keep it functionally identical to `assets/css/critical.css`.

- [ ] **Step 9: Verify no tracked code depends on ignored UI images**

Run:

```bash
! rg -n '/apod/images/(nasa-logo|webb-deep|icons/)' index.php app assets README.md AGENTS.md
```

Expected: no output.

- [ ] **Step 10: Verify resources load**

Run:

```bash
curl -A 'Mozilla/5.0' -sS http://localhost/apod/ | rg '/apod/assets/css/critical.min.css\\?v=|/apod/assets/css/main.min.css\\?v='
curl -A 'Mozilla/5.0' -I http://localhost/apod/assets/css/fonts/lexend-400.woff2
```

Expected: HTML contains deterministic `?v=` asset URLs, and the font request returns `200`.

- [ ] **Step 11: Commit**

Run:

```bash
git add app/includes/assets.php index.php app/includes/conditions.php app/partials/header.php assets/css/critical.css assets/css/critical.min.css README.md
git commit -m "Fix asset loading and source-only UI chrome"
```

---

### Task 5: Centralize APOD Data And Canonical URLs

**Files:**
- Create: `app/includes/apod-data.php`
- Modify: `index.php`
- Modify: `app/includes/conditions.php`

- [ ] **Step 1: Create APOD data helpers**

Create `app/includes/apod-data.php`:

```php
<?php

function apod_load_entries(string $path = APOD_DATA_FILE): array
{
  $json = file_get_contents($path);
  $entries = json_decode($json, true);

  return is_array($entries) ? $entries : [];
}

function apod_slugify(string $text): string
{
  $text = strtolower($text);
  $text = preg_replace('/[^\w\s-]/', '', $text);
  $text = preg_replace('/\s+/', '-', $text);
  $text = preg_replace('/-+/', '-', $text);

  return trim($text, '-');
}

function apod_entry_slug(array $entry): string
{
  return (string) ($entry['slug'] ?? apod_slugify((string) ($entry['title'] ?? '')));
}

function apod_image_entries(array $entries): array
{
  return array_values(array_filter($entries, fn($entry) => ($entry['media_type'] ?? '') === 'image'));
}

function apod_find_by_slug(array $entries, string $slug): ?array
{
  foreach ($entries as $entry) {
    if (apod_entry_slug($entry) === $slug) {
      return $entry;
    }
  }

  return null;
}

function apod_find_index_by_slug(array $entries, string $slug): ?int
{
  foreach ($entries as $index => $entry) {
    if (apod_entry_slug($entry) === $slug) {
      return $index;
    }
  }

  return null;
}

function apod_paginate_entries(array $entries, int $page, int $perPage = 12): array
{
  $totalItems = count($entries);
  $totalPages = max(1, (int) ceil($totalItems / $perPage));
  $currentPage = min(max(1, $page), $totalPages);
  $offset = ($currentPage - 1) * $perPage;

  return [
    'items' => array_slice($entries, $offset, $perPage),
    'current_page' => $currentPage,
    'total_pages' => $totalPages,
    'total_items' => $totalItems,
  ];
}

function apod_h(string $value): string
{
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
```

- [ ] **Step 2: Load helpers in front controller**

In `index.php`, add:

```php
require_once APOD_APP . '/includes/apod-data.php';
```

- [ ] **Step 3: Use helpers for data and routing**

Replace the JSON loading and image-entry setup in `index.php` with:

```php
$apodData = apod_load_entries();
$imageEntries = apod_image_entries($apodData);
```

Replace the image page lookup block with:

```php
if ($page === 'image') {
  $entry = apod_find_by_slug($apodData, (string) $slug);

  if ($entry === null) {
    http_response_code(404);
    $page = '404';
  }
}
```

Add gallery page state before rendering:

```php
$galleryPage = max(1, (int) ($_GET['p'] ?? 1));
```

- [ ] **Step 4: Configure canonical host**

In `index.php`, add:

```php
define('APOD_CANONICAL_ORIGIN', 'https://alanfullbeard.com');
```

In `app/includes/conditions.php`, replace host-derived `$baseUrl` construction with:

```php
$baseUrl = APOD_CANONICAL_ORIGIN;
```

- [ ] **Step 5: Verify data helpers and host handling**

Run:

```bash
php -l index.php
php -l app/includes/apod-data.php
curl -A 'Mozilla/5.0' -H 'Host: attacker.example' -sS http://localhost/apod/image/the-veins-of-heaven | rg 'alanfullbeard.com/apod/image/the-veins-of-heaven'
```

Expected: syntax passes and canonical/social/JSON-LD URLs use `alanfullbeard.com`, not `attacker.example`.

- [ ] **Step 6: Commit**

Run:

```bash
git add app/includes/apod-data.php index.php app/includes/conditions.php
git commit -m "Centralize APOD data helpers"
```

---

### Task 6: Render Archive Server-Side

**Files:**
- Modify: `app/pages/gallery.php`
- Modify: `assets/js/apod.js`
- Modify: `assets/js/apod.min.js`
- Modify: `.htaccess`
- Modify: `README.md`

- [ ] **Step 1: Replace gallery markup with server-rendered cards**

Replace `app/pages/gallery.php` with:

```php
<?php
$pagination = apod_paginate_entries($imageEntries, $galleryPage, 12);
$galleryItems = $pagination['items'];
$currentPage = $pagination['current_page'];
$totalPages = $pagination['total_pages'];
$startPage = max(1, $currentPage - 3);
$endPage = min($totalPages, $startPage + 6);
$startPage = max(1, $endPage - 6);
?>

<div class="gallery-block">
  <div class="gallery-nav">
    <div class="view-toggle" role="group" aria-label="Gallery view">
      <button aria-label="Show list view" aria-pressed="false" id="listViewBtn" class="tab-button" type="button">
        <span>List View</span>
      </button>
      <button aria-label="Show gallery view" aria-pressed="true" id="thumbViewBtn" class="tab-button active" type="button">
        <span>Gallery View</span>
      </button>
    </div>

    <nav class="pagination-controls margin-left-auto" aria-label="Gallery pages">
      <?php if ($currentPage > 1): ?>
        <a class="prev-next-button" rel="prev" href="/apod/gallery?p=<?= $currentPage - 1 ?>">Previous</a>
      <?php endif; ?>

      <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
        <a
          class="page-button<?= $i === $currentPage ? ' active' : '' ?>"
          href="/apod/gallery?p=<?= $i ?>"
          <?= $i === $currentPage ? 'aria-current="page"' : '' ?>>
          <?= $i ?>
        </a>
      <?php endfor; ?>

      <?php if ($currentPage < $totalPages): ?>
        <a class="prev-next-button" rel="next" href="/apod/gallery?p=<?= $currentPage + 1 ?>">Next</a>
      <?php endif; ?>
    </nav>
  </div>

  <div id="apodGallery" class="apod-grid grid-view">
    <?php foreach ($galleryItems as $index => $entry): ?>
      <a class="apod-card" href="/apod/image/<?= apod_h(apod_entry_slug($entry)) ?>">
        <div class="apod-thumb" style="aspect-ratio: 16/9; overflow: hidden;">
          <img
            src="<?= apod_h((string) ($entry['url_thumb'] ?? '')) ?>"
            alt="<?= apod_h((string) ($entry['title'] ?? 'Astronomy Picture of the Day')) ?>"
            width="480"
            height="270"
            <?= $index === 0 && $currentPage === 1 ? 'fetchpriority="high"' : 'loading="lazy"' ?>
            decoding="async"
            style="object-fit: cover; width: 100%; height: 100%;">
        </div>
        <div class="apod-meta">
          <p class="apod-date">
            <time datetime="<?= apod_h((string) ($entry['date'] ?? '')) ?>">
              <?= apod_h((string) ($entry['date'] ?? '')) ?>
            </time>
          </p>
          <div class="apod-title"><?= apod_h((string) ($entry['title'] ?? 'Untitled')) ?></div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<script defer src="<?= apod_asset_url('assets/js/apod.min.js') ?>"></script>
```

- [ ] **Step 2: Replace gallery JavaScript with view-toggle enhancement only**

Replace `assets/js/apod.js` with:

```js
const thumbBtn = document.getElementById('thumbViewBtn');
const listBtn = document.getElementById('listViewBtn');
const apodGallery = document.getElementById('apodGallery');

if (thumbBtn && listBtn && apodGallery) {
  thumbBtn.addEventListener('click', () => {
    apodGallery.classList.remove('list-view');
    apodGallery.classList.add('grid-view');
    thumbBtn.classList.add('active');
    thumbBtn.setAttribute('aria-pressed', 'true');
    listBtn.classList.remove('active');
    listBtn.setAttribute('aria-pressed', 'false');
  });

  listBtn.addEventListener('click', () => {
    apodGallery.classList.add('list-view');
    apodGallery.classList.remove('grid-view');
    thumbBtn.classList.remove('active');
    thumbBtn.setAttribute('aria-pressed', 'false');
    listBtn.classList.add('active');
    listBtn.setAttribute('aria-pressed', 'true');
  });
}
```

- [ ] **Step 3: Replace minified gallery script**

Replace `assets/js/apod.min.js` with:

```js
const thumbBtn=document.getElementById("thumbViewBtn"),listBtn=document.getElementById("listViewBtn"),apodGallery=document.getElementById("apodGallery");thumbBtn&&listBtn&&apodGallery&&(thumbBtn.addEventListener("click",()=>{apodGallery.classList.remove("list-view"),apodGallery.classList.add("grid-view"),thumbBtn.classList.add("active"),thumbBtn.setAttribute("aria-pressed","true"),listBtn.classList.remove("active"),listBtn.setAttribute("aria-pressed","false")}),listBtn.addEventListener("click",()=>{apodGallery.classList.add("list-view"),apodGallery.classList.remove("grid-view"),thumbBtn.classList.remove("active"),thumbBtn.setAttribute("aria-pressed","false"),listBtn.classList.add("active"),listBtn.setAttribute("aria-pressed","true")}));
```

- [ ] **Step 4: Block direct data access**

In `.htaccess`, replace:

```apache
RewriteRule ^data/(apod\.json|stopwords\.txt)$ - [F,L]
```

with:

```apache
RewriteRule ^data/ - [F,L]
```

- [ ] **Step 5: Verify no DOM-rendered archive remains**

Run:

```bash
! rg -n 'innerHTML|insertAdjacentHTML|createElement|fetch\(' assets/js app/pages/gallery.php
```

Expected: no output.

- [ ] **Step 6: Verify archive rendering and canonical slugs**

Run:

```bash
curl -A 'Mozilla/5.0' -sS http://localhost/apod/gallery | rg 'apod-card|pagination-controls|aria-pressed="true"'
curl -A 'Mozilla/5.0' -sS http://localhost/apod/gallery?p=2 | rg 'aria-current="page"'
for slug in major-lunar-standstill-20242025 herbigharo-24 wolfrayet-star-124-stellar-wind-machine; do
  curl -A 'Mozilla/5.0' -sS "http://localhost/apod/gallery" | rg "/apod/image/$slug"
done
curl -A 'Mozilla/5.0' -sS -o /dev/null -w '%{http_code}\n' http://localhost/apod/data/apod.local.json
```

Expected: gallery HTML contains cards, pagination, pressed state, all three stored slugs, and direct data access returns `403`.

- [ ] **Step 7: Run asset sync check**

Run:

```bash
scripts/check-assets.sh
```

Expected: `Asset sync check passed.`

- [ ] **Step 8: Update README**

Replace the current technical debt note about JavaScript-rendered gallery with:

```markdown
- Gallery cards and pagination are rendered server-side. JavaScript is limited to view-mode enhancement.
```

- [ ] **Step 9: Commit**

Run:

```bash
git add app/pages/gallery.php assets/js/apod.js assets/js/apod.min.js .htaccess README.md
git commit -m "Render archive server-side"
```

---

### Task 7: Fix Image Page Data Flow And Lightbox Modal State

**Files:**
- Modify: `app/pages/image.php`
- Modify: `app/includes/lightbox.php`
- Modify: `assets/css/lightbox.css`
- Modify: `assets/css/lightbox.min.css`

- [ ] **Step 1: Remove duplicate data loading and title-derived lookup**

In `app/pages/image.php`, remove:

```php
$dataJson = file_get_contents(APOD_DATA_FILE);
$apodData = json_decode($dataJson, true);
```

Remove the local `slugify()` function and all title-derived lookup loops.

- [ ] **Step 2: Use stored slugs for current, previous, and next entries**

At the top of `app/pages/image.php`, use:

```php
$slug = (string) ($slug ?? '');
$currentIndex = apod_find_index_by_slug($apodData, $slug);

if ($currentIndex === null || $entry === null) {
  echo '<p>Image not found.</p>';
  return;
}

$title = (string) ($entry['title'] ?? 'Astronomy Picture of the Day');
$date = (string) ($entry['date'] ?? '');
$credit = (string) ($entry['credit'] ?? $entry['copyright'] ?? 'NASA / APOD');
$explanation = nl2br(apod_h((string) ($entry['explanation'] ?? '')));
$total = count($apodData);

$prevIndex = ($currentIndex + 1) % $total;
$prevItem = $apodData[$prevIndex];
$prevTitle = (string) ($prevItem['title'] ?? 'Untitled');
$prevSlug = apod_entry_slug($prevItem);

if ($currentIndex > 0) {
  $nextIndex = $currentIndex - 1;
  $nextItem = $apodData[$nextIndex];
  $nextTitle = (string) ($nextItem['title'] ?? 'Untitled');
  $nextSlug = apod_entry_slug($nextItem);
} else {
  $nextSlug = null;
  $nextTitle = 'Something Cool';
}
```

- [ ] **Step 3: Add matching explanation id**

Change:

```php
<section class="apod-explanation">
```

to:

```php
<section id="explanation-<?= apod_h($slug) ?>" class="apod-explanation">
```

- [ ] **Step 4: Make lightbox trigger carry alt text**

In `app/includes/lightbox.php`, add `data-alt`:

```php
data-alt="<?= $escapedTitle ?>"
```

on the `.lightbox-trigger` anchor.

- [ ] **Step 5: Name the dialog**

In `app/includes/lightbox.php`, change the lightbox wrapper to:

```html
<div id="lightbox" class="lightbox" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="lightbox-title">
```

Inside `.lightbox-content`, before the close button, add:

```php
<h2 id="lightbox-title" class="visually-hidden"><?= $escapedTitle ?></h2>
```

- [ ] **Step 6: Add inert background state**

In `app/includes/lightbox.php`, replace the open and close handlers with:

```js
const pageSiblings = Array.from(document.body.children).filter((child) => child !== lightbox);

function setBackgroundInert(isInert) {
  pageSiblings.forEach((element) => {
    if (isInert) {
      element.setAttribute('aria-hidden', 'true');
      element.inert = true;
    } else {
      element.removeAttribute('aria-hidden');
      element.inert = false;
    }
  });
}

function openLightbox(e) {
  e.preventDefault();
  lastFocused = document.activeElement;

  const trigger = e.currentTarget;
  imgEl.src = trigger.dataset.full;
  imgEl.alt = trigger.dataset.alt || trigger.querySelector('img')?.alt || '';

  lightbox.classList.add('open');
  lightbox.removeAttribute('aria-hidden');
  setBackgroundInert(true);
  closeBtn.focus();
  document.addEventListener('keydown', onKeyDown);
  document.body.classList.add('no-scroll');
}

function closeLightbox() {
  lightbox.classList.remove('open');
  lightbox.setAttribute('aria-hidden', 'true');
  imgEl.src = '';
  imgEl.alt = '';
  setBackgroundInert(false);
  document.removeEventListener('keydown', onKeyDown);
  document.body.classList.remove('no-scroll');
  lastFocused && lastFocused.focus();
}
```

- [ ] **Step 7: Suppress swipe navigation during modal state**

In the swipe handler in `app/pages/image.php`, add this as the first line of `handleTouchEnd`:

```js
if (document.getElementById('lightbox')?.classList.contains('open')) return;
```

Also change the listener scope from `document` to the image article:

```js
const swipeRegion = document.querySelector('.apod-article');
swipeRegion?.addEventListener('touchstart', handleTouchStart, false);
swipeRegion?.addEventListener('touchend', handleTouchEnd, false);
```

- [ ] **Step 8: Verify mismatched stored slugs render real content**

Run:

```bash
for slug in major-lunar-standstill-20242025 herbigharo-24 wolfrayet-star-124-stellar-wind-machine; do
  printf '%s -> ' "$slug"
  curl -A 'Mozilla/5.0' -sS "http://localhost/apod/image/$slug" | rg -q 'Image not found' && echo fail || echo pass
done
```

Expected:

```text
major-lunar-standstill-20242025 -> pass
herbigharo-24 -> pass
wolfrayet-star-124-stellar-wind-machine -> pass
```

- [ ] **Step 9: Verify lightbox and explanation semantics**

Run:

```bash
php -l app/pages/image.php
php -l app/includes/lightbox.php
curl -A 'Mozilla/5.0' -sS http://localhost/apod/image/the-veins-of-heaven | rg 'id="explanation-the-veins-of-heaven"|aria-describedby="explanation-the-veins-of-heaven"|aria-labelledby="lightbox-title"|data-alt="The Veins of Heaven"'
```

Expected: syntax passes and the curl command prints all four patterns.

- [ ] **Step 10: Commit**

Run:

```bash
git add app/pages/image.php app/includes/lightbox.php assets/css/lightbox.css assets/css/lightbox.min.css
git commit -m "Fix image detail and lightbox state"
```

---

### Task 8: Fix Header Navigation Accessibility

**Files:**
- Modify: `app/partials/header.php`
- Modify: `assets/css/main.css`
- Modify: `assets/css/main.min.css`

- [ ] **Step 1: Add missing controlled id**

In `app/partials/header.php`, change:

```html
<div class="header-right" aria-hidden="true">
```

to:

```html
<div id="site-nav" class="header-right" aria-hidden="true" hidden>
```

- [ ] **Step 2: Align breakpoint logic**

In `app/partials/header.php`, replace every `window.innerWidth < 640` check with:

```js
window.innerWidth < 950
```

- [ ] **Step 3: Keep closed nav hidden**

In `openNav()`, add:

```js
navMenu.hidden = false;
navMenu.inert = false;
```

In `closeNav()`, add:

```js
navMenu.hidden = true;
navMenu.inert = true;
```

For desktop state in `updateAriaHidden()` and `handleResize()`, remove `hidden` and `inert`:

```js
navMenu.hidden = false;
navMenu.inert = false;
```

- [ ] **Step 4: Mirror hidden state in CSS**

In `assets/css/main.css`, add:

```css
.header-right[hidden] {
  display: none;
}
```

Apply the same rule to `assets/css/main.min.css`.

- [ ] **Step 5: Verify markup and breakpoints**

Run:

```bash
php -l app/partials/header.php
curl -A 'Mozilla/5.0' -sS http://localhost/apod/ | rg 'aria-controls="site-nav"|id="site-nav"'
! rg -n 'innerWidth < 640|innerWidth >= 640' app/partials/header.php
```

Expected: syntax passes, markup contains matching `aria-controls` and id, and no 640px JavaScript breakpoint remains.

- [ ] **Step 6: Commit**

Run:

```bash
git add app/partials/header.php assets/css/main.css assets/css/main.min.css
git commit -m "Fix header navigation accessibility"
```

---

### Task 9: Remove Deprecated Lightbox Script

**Files:**
- Delete: `assets/js/lightbox.js.deprecated`
- Modify: `README.md`

- [ ] **Step 1: Confirm runtime does not reference deprecated file**

Run:

```bash
! rg -n 'lightbox\.js\.deprecated|lightbox\.js' index.php app assets/css assets/js README.md AGENTS.md
```

Expected: the only matches are in `README.md` and the current plan if they still mention the deprecation note.

- [ ] **Step 2: Remove deprecated note from README**

Delete the README line:

```markdown
- `assets/js/lightbox.js.deprecated` is retained temporarily while confirming no behavior depends on the old standalone lightbox script.
```

- [ ] **Step 3: Delete deprecated file**

Run:

```bash
rm assets/js/lightbox.js.deprecated
```

- [ ] **Step 4: Confirm no runtime references remain**

Run:

```bash
! rg -n 'lightbox\.js' index.php app assets README.md AGENTS.md
```

Expected: no output.

- [ ] **Step 5: Smoke test lightbox markup still renders**

Run:

```bash
curl -A 'Mozilla/5.0' -sS http://localhost/apod/image/the-veins-of-heaven | rg 'lightbox-trigger|id="lightbox"'
```

Expected: command prints the trigger and dialog markup.

- [ ] **Step 6: Commit**

Run:

```bash
git add -A assets/js/lightbox.js.deprecated README.md
git commit -m "Remove deprecated lightbox script"
```

---

### Task 10: Add Accessibility Checklist And Final Verification

**Files:**
- Create: `docs/accessibility-checklist.md`
- Modify: `README.md`
- Modify: `AGENTS.md`

- [ ] **Step 1: Create accessibility checklist**

Create `docs/accessibility-checklist.md`:

```markdown
# Accessibility Review Checklist

Use this checklist before public portfolio updates.

## Keyboard

- The skip link appears on focus and moves focus to `#main`.
- Header navigation can be opened, used, and closed with keyboard only at widths below 950px.
- Closed mobile navigation is not reachable by keyboard.
- Gallery view toggle buttons expose current state with `aria-pressed`.
- Gallery pagination links are reachable, named, and expose the current page with `aria-current="page"`.
- Image previous/next navigation works with keyboard only.
- Lightbox opens, makes background content inert, closes with Escape, and returns focus to the opener.
- Document-wide swipe navigation does not fire while the lightbox is open.

## Screen Readers

- Pages expose one clear main landmark.
- Headings follow a logical order.
- Links and buttons have meaningful names without relying on visual context.
- Image cards expose title/date context.
- APOD image alt text matches the displayed title.
- Lightbox dialog has an accessible name and preserves image alt text.
- ARIA states change when menus or dialogs open and close.

## Visual And Motion

- Focus indicators are visible against dark backgrounds.
- Text remains readable at 200% zoom.
- Layout does not require horizontal scrolling at common mobile widths.
- Color contrast is sufficient for text, controls, and focus states.
- `prefers-reduced-motion` disables nonessential motion.
- Fixed backgrounds and sticky UI do not create motion or readability problems.
```

- [ ] **Step 2: Link checklist from README and AGENTS**

Add this sentence to `README.md`:

```markdown
Accessibility review notes live in `docs/accessibility-checklist.md`.
```

Add this sentence to `AGENTS.md` under Verification:

```markdown
Use `docs/accessibility-checklist.md` for manual accessibility review before portfolio-facing changes.
```

- [ ] **Step 3: Run full verification**

Run:

```bash
scripts/php-lint.sh
scripts/check-source-only.sh
scripts/check-file-modes.sh
scripts/check-assets.sh
for url in \
  http://localhost/apod/ \
  http://localhost/apod/gallery \
  'http://localhost/apod/gallery?p=2' \
  http://localhost/apod/about \
  http://localhost/apod/image/the-veins-of-heaven \
  http://localhost/apod/image/major-lunar-standstill-20242025 \
  http://localhost/apod/image/herbigharo-24 \
  http://localhost/apod/image/wolfrayet-star-124-stellar-wind-machine \
  http://localhost/apod/random \
  http://localhost/apod/.git/HEAD \
  http://localhost/apod/data/apod.local.json \
  http://localhost/apod/app/pages/gallery.php
do
  printf '%s -> ' "$url"
  curl -A 'Mozilla/5.0' -sS -o /dev/null -w '%{http_code} %{redirect_url}\n' "$url"
done
```

Expected:

```text
All scripts pass
/apod/, /gallery, /gallery?p=2, /about, and all image URLs return 200
/random returns 302
/.git/HEAD, /data/apod.local.json, and /app/pages/gallery.php return 403
```

- [ ] **Step 4: Commit**

Run:

```bash
git add docs/accessibility-checklist.md README.md AGENTS.md
git commit -m "Add accessibility review checklist"
```

- [ ] **Step 5: Push and verify public repo**

Run:

```bash
git push
gh repo view acodebeard/apod --json nameWithOwner,isPrivate,url,homepageUrl,defaultBranchRef
git ls-files | rg '^(images|thumbs)/|^audit\.php$' || true
```

Expected:

```text
"isPrivate": false
No tracked images, thumbs, or audit.php are printed
```

---

## Self-Review

- Review coverage: the plan includes the five-agent findings on resources, archive/gallery behavior, image/lightbox state, accessibility/security, and repo maintainability.
- Security coverage: `.git` exposure, unreachable deny rewrites, production error display, suspicious queries, bot blocking, direct data access, and host-header-derived canonical URLs are covered.
- Accessibility coverage: server-rendered archive markup, view-toggle state, mobile nav hidden/inert state, matching `aria-controls`, lightbox naming, preserved alt text, missing `aria-describedby` target, background inert state, focus restoration, swipe suppression, and manual checklist are covered.
- Source-only coverage: generated media remains ignored; required UI chrome no longer depends on tracked image files; file-mode and tracked-file checks are added.
- Placeholder scan: no placeholder markers or incomplete implementation steps remain.
