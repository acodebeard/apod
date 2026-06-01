# APOD Video Support Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Render APOD video entries without loading third-party video players until the user explicitly activates them.

**Architecture:** Add a small PHP media-rendering helper that branches by `media_type`. Image entries keep the existing responsive image and lightbox behavior; video entries render a local poster image, a real activation button, and an iframe with only `data-src` until JavaScript loads it on user action.

**Tech Stack:** Plain PHP includes, static CSS, small vanilla JavaScript enhancement, existing shell/PHP check scripts.

---

### Task 1: Rendering Contract

**Files:**
- Create: `scripts/check-video-support.php`
- Create: `app/includes/media.php`
- Modify: `app/includes/lightbox.php`

- [x] Write a PHP check that renders fixture image and video entries.
- [x] Verify it fails before the media helper exists.
- [x] Implement media helpers so video entries are thumbnail-first and image entries still use lightbox markup.

### Task 2: Detail Page Integration

**Files:**
- Modify: `index.php`
- Modify: `app/pages/image.php`
- Modify: `app/includes/conditions.php`

- [x] Load media helpers from the front controller.
- [x] Replace direct lightbox include with media rendering.
- [x] Use stored slugs for previous/next navigation.
- [x] Load the video enhancement script only for video detail pages.

### Task 3: Video Enhancement And Styling

**Files:**
- Create: `assets/js/video.js`
- Create: `assets/js/video.min.js`
- Modify: `assets/css/main.css`
- Modify: `assets/css/main.min.css`
- Modify: `scripts/check-assets.sh`

- [x] Add a click handler that copies iframe `data-src` to `src` only after activation.
- [x] Add minimal poster/button/frame styling.
- [x] Include the new video asset in static asset checks.

### Task 4: Verification

**Files:**
- Run checks only.

- [x] Run `php scripts/check-video-support.php`.
- [x] Run `scripts/php-lint.sh`.
- [x] Run `scripts/check-source-only.sh`.
- [x] Run `scripts/check-file-modes.sh`.
- [x] Run `scripts/check-assets.sh`.
- [x] Smoke test local image and video fixture behavior where possible.
