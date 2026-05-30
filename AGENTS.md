# AGENTS.md

## Project Overview

This project recreates NASA's Astronomy Picture of the Day site at `/apod`.

- Production URL: `https://alanfullbeard.com/apod`
- Local URL: `http://localhost/apod`
- Stack: plain PHP, Apache rewrite rules, static CSS/JS, JSON data files, local image assets
- Data storage: no database; APOD entries are loaded from JSON
- Repository: public GitHub repository at `https://github.com/acodebeard/apod`, default branch `main`.

## Directory Map

- `index.php` is the front controller. It reads `page` and `slug`, validates known pages, loads APOD data, and includes the selected PHP page.
- `.htaccess` owns Apache routing for pretty URLs such as `/apod/image/<slug>`, `/apod/gallery`, and `/apod/about`.
- `data/apod.local.json` is the app's active local dataset. It includes slugs plus local asset paths such as `url_full`, `url_thumb`, and `url_main`.
- `data/apod.json` appears to be a separate APOD dataset/source file. Do not assume it is what the live UI reads.
- `app/pages/` contains page-level views: `gallery.php`, `image.php`, `about.php`, and `404.php`.
- `app/partials/` contains shared layout fragments: `header.php`, `footer.php`, and `header-text.php`.
- `app/includes/` contains smaller PHP includes such as metadata conditions, pretty error handling, and lightbox markup.
- `assets/css/` and `assets/js/` contain source and minified public assets. The pages currently serve the `.min.*` files.
- `scripts/` contains local maintenance utilities, including `generate-share-images.php`.
- `images/` and `thumbs/` contain generated image assets and are ignored by git. Avoid touching them unless the task is explicitly about content or media generation.

## Routing And Data Conventions

- The app is deployed under `/apod`, not domain root. Keep internal asset and page URLs compatible with that base path unless the task is to change deployment structure.
- Pretty routes are handled by Apache rewrite rules in `.htaccess`; PHP files should not rely on direct public access as the primary navigation path.
- Image pages use APOD slugs. Prefer the stored `slug` field in `data/apod.local.json` when possible, and keep PHP and JS slug behavior consistent if either changes.
- The gallery currently fetches `data/apod.local.json` client-side and renders entries with JavaScript. This is legacy behavior; future result and navigation rendering should move server-side.
- Image detail pages render server-side from the same JSON data and local image path fields.
- Preserve NASA/APOD credit, copyright, and metadata behavior when changing templates.

## Coding Guidelines

- Keep changes small and consistent with the existing procedural PHP style.
- Use `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` for user-visible or data-derived HTML attributes and text.
- Keep accessibility attributes intact when editing navigation, lightbox, gallery controls, or media markup.
- Accessibility is a core project goal. Account for as many access needs as practical, including keyboard-only navigation, screen readers, low vision, zoomed layouts, reduced motion preferences, and clear focus states.
- Do not introduce a database, framework, package manager, or build system without explicit approval.
- Do not rename or reorganize image folders casually; JSON entries and templates depend on current paths.
- If editing served CSS or JS, remember that pages load minified files. Either keep the matching `.min.css` or `.min.js` file in sync, or intentionally change the template to load the unminified file during development.
- Do not use JavaScript to render returned results or navigation markup. Render HTML server-side in PHP; JavaScript may only enhance existing markup by toggling classes, attributes, or state. Avoid innerHTML, insertAdjacentHTML, template-string markup, and similar DOM-writing patterns.

## Verification

Run local checks before committing:

```bash
scripts/php-lint.sh
scripts/check-source-only.sh
scripts/check-file-modes.sh
scripts/check-assets.sh
```

For browser smoke tests, check:

- `http://localhost/apod`
- `http://localhost/apod/gallery`
- `http://localhost/apod/about`
- `http://localhost/apod/image/<known-slug>`
- `http://localhost/apod/random`

For accessibility review, include keyboard traversal, focus visibility, landmark and heading structure, meaningful link/button names, image alt text, screen-reader-only text behavior, ARIA state changes, color contrast, zoom behavior, and reduced-motion handling where relevant.

Note: `.htaccess` blocks default `curl` and `wget` user agents. If using `curl` for local smoke tests, set a browser-like user agent:

```bash
curl -A 'Mozilla/5.0' -I http://localhost/apod/
```

## Working Notes

- Treat `data/apod.local.json`, generated images, and minified assets as high-churn files. Avoid broad formatting or regeneration unless the task calls for it.
- Prefer local verification before production checks. Production at `alanfullbeard.com/apod` may differ from the current working tree until files are deployed.
