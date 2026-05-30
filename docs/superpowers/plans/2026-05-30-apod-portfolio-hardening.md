# APOD Portfolio Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the APOD recreation stronger as a public portfolio project by removing JavaScript-rendered results, tightening accessibility review, and adding repeatable repo checks.

**Architecture:** Keep the current Apache/PHP deployment shape: `index.php` remains the front controller, route views live in `app/pages/`, shared helpers live in `app/includes/`, and public CSS/JS lives in `assets/`. Move data access and slug logic into a small PHP helper so gallery and image pages share the same behavior.

**Tech Stack:** Plain PHP 8.2, Apache `.htaccess`, JSON metadata, static CSS/JS, GitHub Actions for syntax and source-only checks.

---

## File Structure

- Create `.github/workflows/ci.yml`: run syntax and source-only checks on pushes and pull requests.
- Create `scripts/php-lint.sh`: local and CI PHP syntax verification.
- Create `scripts/check-source-only.sh`: fail if generated media or local utility files are tracked.
- Create `app/includes/apod-data.php`: APOD data loading, slug lookup, and pagination helpers.
- Modify `index.php`: load helpers once, share validated state with pages, handle gallery page number.
- Modify `app/pages/gallery.php`: render APOD cards and pagination in PHP.
- Modify `assets/js/apod.js` and `assets/js/apod.min.js`: replace gallery rendering with view-toggle-only enhancement.
- Modify `app/pages/image.php`: use shared data helpers and remove duplicate data loading/slug logic.
- Create `docs/accessibility-checklist.md`: manual review checklist for keyboard, screen reader, visual, and motion checks.
- Remove `assets/js/lightbox.js.deprecated`: after verifying no references remain.
- Modify `README.md` and `AGENTS.md`: document checks and remaining project constraints.

---

### Task 1: Add Repeatable Repo Checks

**Files:**
- Create: `.github/workflows/ci.yml`
- Create: `scripts/php-lint.sh`
- Create: `scripts/check-source-only.sh`
- Modify: `README.md`
- Modify: `AGENTS.md`

- [ ] **Step 1: Create the PHP lint script**

Create `scripts/php-lint.sh`:

```bash
#!/usr/bin/env bash
set -euo pipefail

php -l index.php
find app scripts -name '*.php' -print0 | xargs -0 -n1 php -l
```

- [ ] **Step 2: Make the script executable**

Run:

```bash
chmod +x scripts/php-lint.sh
```

- [ ] **Step 3: Run PHP lint locally**

Run:

```bash
scripts/php-lint.sh
```

Expected: every PHP file reports `No syntax errors detected`.

- [ ] **Step 4: Create the source-only guard**

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

- [ ] **Step 5: Make the source-only script executable**

Run:

```bash
chmod +x scripts/check-source-only.sh
```

- [ ] **Step 6: Run the source-only guard locally**

Run:

```bash
scripts/check-source-only.sh
```

Expected: `Source-only check passed.`

- [ ] **Step 7: Add GitHub Actions CI**

Create `.github/workflows/ci.yml`:

```yaml
name: CI

on:
  push:
  pull_request:

jobs:
  php-and-source-checks:
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
```

- [ ] **Step 8: Document checks**

Add this section to `README.md`:

````markdown
## Verification

Run local checks before committing:

```bash
scripts/php-lint.sh
scripts/check-source-only.sh
curl -A 'Mozilla/5.0' -I http://localhost/apod/
```
````

Add `scripts/php-lint.sh` and `scripts/check-source-only.sh` to the verification section in `AGENTS.md`.

- [ ] **Step 9: Commit**

Run:

```bash
git add .github/workflows/ci.yml scripts/php-lint.sh scripts/check-source-only.sh README.md AGENTS.md
git commit -m "Add repository verification checks"
```

---

### Task 2: Centralize APOD Data Helpers

**Files:**
- Create: `app/includes/apod-data.php`
- Modify: `index.php`
- Modify: `app/pages/image.php`

- [ ] **Step 1: Create data helper file**

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

function apod_image_entries(array $entries): array
{
  return array_values(array_filter($entries, fn($entry) => ($entry['media_type'] ?? '') === 'image'));
}

function apod_find_by_slug(array $entries, string $slug): ?array
{
  foreach ($entries as $entry) {
    if (($entry['slug'] ?? apod_slugify($entry['title'] ?? '')) === $slug) {
      return $entry;
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
```

- [ ] **Step 2: Load helper from front controller**

In `index.php`, immediately after `require_once APOD_APP . '/includes/pretty-errors.php';`, add:

```php
require_once APOD_APP . '/includes/apod-data.php';
```

Replace:

```php
$dataJson = file_get_contents(APOD_DATA_FILE);
$apodData = json_decode($dataJson, true);
```

with:

```php
$apodData = apod_load_entries();
$imageEntries = apod_image_entries($apodData);
```

Remove the later duplicate `$imageEntries = array_filter(...)` assignment.

- [ ] **Step 3: Use helper for random entry**

Keep the random URL code as:

```php
$rand = $imageEntries[array_rand($imageEntries)];
$randomUrl = APOD_BASE_PATH . "/image/{$rand['slug']}";
```

- [ ] **Step 4: Use helper for image slug lookup**

In the `if ($page === 'image')` block, replace the manual `$validSlugs` and loop with:

```php
$entry = apod_find_by_slug($apodData, (string) $slug);

if ($entry === null) {
  http_response_code(404);
  $page = '404';
}
```

- [ ] **Step 5: Add gallery page state**

Before `$cb = mt_rand(2, 20000);`, add:

```php
$galleryPage = max(1, (int) ($_GET['p'] ?? 1));
```

- [ ] **Step 6: Run syntax checks**

Run:

```bash
php -l index.php
php -l app/includes/apod-data.php
php -l app/pages/image.php
```

Expected: all three files report `No syntax errors detected`.

- [ ] **Step 7: Commit**

Run:

```bash
git add index.php app/includes/apod-data.php app/pages/image.php
git commit -m "Centralize APOD data helpers"
```

---

### Task 3: Render Gallery And Pagination Server-Side

**Files:**
- Modify: `app/pages/gallery.php`
- Modify: `assets/js/apod.js`
- Modify: `assets/js/apod.min.js`
- Modify: `README.md`

- [ ] **Step 1: Replace JavaScript-rendered gallery markup**

Replace `app/pages/gallery.php` with:

```php
<?php
$pagination = apod_paginate_entries($imageEntries, $galleryPage, 12);
$galleryItems = $pagination['items'];
$currentPage = $pagination['current_page'];
$totalPages = $pagination['total_pages'];
?>

<div class="gallery-block">
  <div class="gallery-nav">
    <div class="view-toggle" role="presentation">
      <button aria-label="Toggle List Mode" id="listViewBtn" class="tab-button" type="button">
        <span>List View</span>
      </button>
      <button aria-label="Toggle Thumbnail Mode" id="thumbViewBtn" class="tab-button active" type="button">
        <span>Gallery View</span>
      </button>
    </div>

    <nav class="pagination-controls margin-left-auto" aria-label="Gallery pages">
      <?php if ($currentPage > 1): ?>
        <a class="prev-next-button" rel="prev" href="/apod/gallery?p=<?= $currentPage - 1 ?>">Previous</a>
      <?php endif; ?>

      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
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
      <a class="apod-card" href="/apod/image/<?= htmlspecialchars($entry['slug'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="apod-thumb" style="aspect-ratio: 16/9; overflow: hidden;">
          <img
            src="<?= htmlspecialchars($entry['url_thumb'], ENT_QUOTES, 'UTF-8') ?>"
            alt="<?= htmlspecialchars($entry['title'], ENT_QUOTES, 'UTF-8') ?>"
            width="480"
            height="270"
            <?= $index === 0 && $currentPage === 1 ? 'fetchpriority="high"' : 'loading="lazy"' ?>
            decoding="async"
            style="object-fit: cover; width: 100%; height: 100%;">
        </div>
        <div class="apod-meta">
          <p class="apod-date">
            <time datetime="<?= htmlspecialchars($entry['date'], ENT_QUOTES, 'UTF-8') ?>">
              <?= htmlspecialchars($entry['date'], ENT_QUOTES, 'UTF-8') ?>
            </time>
          </p>
          <div class="apod-title"><?= htmlspecialchars($entry['title'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<script defer src="/apod/assets/js/apod.min.js"></script>
```

- [ ] **Step 2: Replace gallery JavaScript with view toggle only**

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
    listBtn.classList.remove('active');
  });

  listBtn.addEventListener('click', () => {
    apodGallery.classList.add('list-view');
    apodGallery.classList.remove('grid-view');
    thumbBtn.classList.remove('active');
    listBtn.classList.add('active');
  });
}
```

- [ ] **Step 3: Update minified gallery script**

Run:

```bash
npx terser assets/js/apod.js -o assets/js/apod.min.js --compress --mangle
```

If `npx terser` is unavailable, replace `assets/js/apod.min.js` with this exact minified content:

```js
const thumbBtn=document.getElementById("thumbViewBtn"),listBtn=document.getElementById("listViewBtn"),apodGallery=document.getElementById("apodGallery");thumbBtn&&listBtn&&apodGallery&&(thumbBtn.addEventListener("click",()=>{apodGallery.classList.remove("list-view"),apodGallery.classList.add("grid-view"),thumbBtn.classList.add("active"),listBtn.classList.remove("active")}),listBtn.addEventListener("click",()=>{apodGallery.classList.add("list-view"),apodGallery.classList.remove("grid-view"),thumbBtn.classList.remove("active"),listBtn.classList.add("active")}));
```

- [ ] **Step 4: Verify forbidden DOM-rendering patterns are gone**

Run:

```bash
! rg -n 'innerHTML|insertAdjacentHTML|createElement|fetch\\(' assets/js app/pages/gallery.php
```

Expected: command exits with status `0` and prints no matches.

- [ ] **Step 5: Smoke test gallery pages**

Run:

```bash
curl -A 'Mozilla/5.0' -sS http://localhost/apod/gallery | rg 'apod-card|pagination-controls'
curl -A 'Mozilla/5.0' -sS http://localhost/apod/gallery?p=2 | rg 'aria-current="page"'
```

Expected: both commands print matching HTML.

- [ ] **Step 6: Update README technical debt**

Remove the current README note that says gallery results are still rendered by JavaScript. Add:

```markdown
- Gallery cards and pagination are rendered server-side. JavaScript is limited to view-mode enhancement.
```

- [ ] **Step 7: Commit**

Run:

```bash
git add app/pages/gallery.php assets/js/apod.js assets/js/apod.min.js README.md
git commit -m "Render gallery server-side"
```

---

### Task 4: Clean Up Image Detail Page Data Flow

**Files:**
- Modify: `app/pages/image.php`
- Modify: `app/includes/lightbox.php`

- [ ] **Step 1: Remove duplicate data loading from image page**

In `app/pages/image.php`, remove:

```php
$dataJson = file_get_contents(APOD_DATA_FILE);
$apodData = json_decode($dataJson, true);
```

The page should use `$apodData`, `$imageEntries`, `$entry`, and `$slug` provided by `index.php`.

- [ ] **Step 2: Remove local slug helper**

Delete the local `slugify()` function from `app/pages/image.php`. Replace all `slugify(...)` calls with `apod_slugify(...)`.

- [ ] **Step 3: Add a stable explanation ID**

Change:

```php
<section class="apod-explanation">
```

to:

```php
<section id="explanation-<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" class="apod-explanation">
```

- [ ] **Step 4: Improve lightbox alt text**

In `app/includes/lightbox.php`, change:

```js
imgEl.alt = trigger.dataset.alt || '';
```

to:

```js
imgEl.alt = trigger.dataset.alt || trigger.querySelector('img')?.alt || '';
```

- [ ] **Step 5: Run detail page checks**

Run:

```bash
php -l app/pages/image.php
php -l app/includes/lightbox.php
curl -A 'Mozilla/5.0' -sS http://localhost/apod/image/the-veins-of-heaven | rg 'id="explanation-the-veins-of-heaven"|aria-describedby="explanation-the-veins-of-heaven"'
```

Expected: syntax checks pass and the curl command prints both the section ID and the `aria-describedby` reference.

- [ ] **Step 6: Commit**

Run:

```bash
git add app/pages/image.php app/includes/lightbox.php
git commit -m "Clean up image detail data flow"
```

---

### Task 5: Add Accessibility Review Checklist

**Files:**
- Create: `docs/accessibility-checklist.md`
- Modify: `README.md`

- [ ] **Step 1: Create accessibility checklist**

Create `docs/accessibility-checklist.md`:

```markdown
# Accessibility Review Checklist

Use this checklist before public portfolio updates.

## Keyboard

- The skip link appears on focus and moves focus to `#main`.
- Header navigation can be opened, used, and closed with keyboard only.
- Gallery view toggle buttons are reachable and visibly focused.
- Gallery pagination links are reachable, named, and expose the current page with `aria-current="page"`.
- Image previous/next navigation works with keyboard only.
- Lightbox opens, traps focus on close controls, closes with Escape, and returns focus to the opener.

## Screen Readers

- Pages expose one clear main landmark.
- Headings follow a logical order.
- Links and buttons have meaningful names without relying on visual context.
- Image cards expose title/date context.
- APOD image alt text matches the displayed title.
- ARIA states change when menus or dialogs open and close.

## Visual And Motion

- Focus indicators are visible against dark backgrounds.
- Text remains readable at 200% zoom.
- Layout does not require horizontal scrolling at common mobile widths.
- Color contrast is sufficient for text, controls, and focus states.
- `prefers-reduced-motion` disables nonessential motion.
```

- [ ] **Step 2: Link checklist from README**

Add this sentence to the accessibility/project priorities area in `README.md`:

```markdown
Accessibility review notes live in `docs/accessibility-checklist.md`.
```

- [ ] **Step 3: Commit**

Run:

```bash
git add docs/accessibility-checklist.md README.md
git commit -m "Add accessibility review checklist"
```

---

### Task 6: Remove Deprecated Lightbox Script

**Files:**
- Delete: `assets/js/lightbox.js.deprecated`

- [ ] **Step 1: Confirm no references remain**

Run:

```bash
! rg -n 'lightbox\.js' .
```

Expected: command exits with status `0` and prints no matches.

- [ ] **Step 2: Delete deprecated file**

Run:

```bash
rm assets/js/lightbox.js.deprecated
```

- [ ] **Step 3: Smoke test lightbox page still renders**

Run:

```bash
curl -A 'Mozilla/5.0' -sS http://localhost/apod/image/the-veins-of-heaven | rg 'lightbox-trigger|id="lightbox"'
```

Expected: command prints the lightbox trigger and dialog markup.

- [ ] **Step 4: Commit**

Run:

```bash
git add -A assets/js/lightbox.js.deprecated
git commit -m "Remove deprecated lightbox script"
```

---

### Task 7: Push And Verify Public Repo

**Files:**
- No source file changes.

- [ ] **Step 1: Run full local verification**

Run:

```bash
scripts/php-lint.sh
scripts/check-source-only.sh
for url in \
  http://localhost/apod/ \
  http://localhost/apod/gallery \
  http://localhost/apod/gallery?p=2 \
  http://localhost/apod/about \
  http://localhost/apod/image/the-veins-of-heaven \
  http://localhost/apod/random \
  http://localhost/apod/app/pages/gallery.php
do
  printf '%s -> ' "$url"
  curl -A 'Mozilla/5.0' -sS -o /dev/null -w '%{http_code} %{redirect_url}\n' "$url"
done
```

Expected:

```text
scripts/php-lint.sh passes
scripts/check-source-only.sh passes
/apod/, /gallery, /gallery?p=2, /about, and /image/... return 200
/random returns 302
/app/pages/gallery.php returns 403
```

- [ ] **Step 2: Push changes**

Run:

```bash
git push
```

- [ ] **Step 3: Confirm GitHub repo remains public and source-only**

Run:

```bash
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

- Spec coverage: repo verification, server-side gallery rendering, accessibility review, deprecated file cleanup, and final public repo verification are covered.
- Placeholder scan: no placeholder markers or incomplete implementation steps remain.
- Type consistency: helper names are consistent across tasks: `apod_load_entries`, `apod_slugify`, `apod_image_entries`, `apod_find_by_slug`, and `apod_paginate_entries`.
