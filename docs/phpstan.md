# PHPStan In APOD

APOD uses PHPStan as a development-only Composer dependency.

Run it locally with:

```bash
composer install
scripts/phpstan.sh
```

You can also run the Composer script directly:

```bash
composer run phpstan
```

## Current Baseline

`phpstan.neon` starts at `level: 3`. That is intentional.

At this level PHPStan checks useful basics without requiring a full type-model cleanup of this procedural PHP app. It catches missing symbols, undefined variables, and common structural mistakes while staying quiet on the JSON `mixed` data that APOD still uses heavily.

## Bootstrap

`tools/phpstan/bootstrap.php` defines the same constants that `index.php` defines at runtime, then loads helper function files:

- `app/includes/assets.php`
- `app/includes/media.php`

This lets PHPStan understand project functions like `apod_asset_url()` and `apod_render_media()` while analyzing files directly.

## Include Context

Apache runs APOD through `index.php`, which defines variables before including page and partial files. PHPStan analyzes each file directly, so include files need safe local defaults for variables they receive from the front controller.

Current examples:

- `app/pages/image.php` ensures `$apodData` is an array before iterating it.
- `app/partials/header.php` ensures `$randomUrl` has a string fallback.

This is runtime hardening, not only static-analysis noise reduction.

## Why Not Level Max Yet

`level=max` currently reports many `mixed` findings because APOD reads JSON with `json_decode()` and passes those arrays through procedural includes. A stricter future pass should introduce documented APOD entry shapes and narrow decoded data before templates use it.

Do not suppress those findings with a baseline as a first move. Prefer small type improvements where they clarify real data flow.
