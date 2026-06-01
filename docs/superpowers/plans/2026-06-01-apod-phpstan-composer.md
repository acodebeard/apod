# APOD PHPStan Composer Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make PHPStan a repo-owned APOD development tool through Composer without tracking `vendor/`.

**Architecture:** Add Composer dev dependency metadata, a PHPStan configuration, and a small wrapper script. Keep APOD standalone at runtime while letting local development and CI install and run the same PHPStan version.

**Tech Stack:** Composer, PHPStan, Bash, GitHub Actions.

---

### Task 1: Composer Dependency

**Files:**
- Create: `composer.json`
- Create: `composer.lock`
- Modify: `.gitignore`

- [x] Add `phpstan/phpstan` as a Composer dev dependency.
- [x] Add a `phpstan` Composer script that runs the local PHPStan binary.
- [x] Keep `vendor/` ignored.

### Task 2: PHPStan Configuration

**Files:**
- Create: `phpstan.neon`
- Create: `tools/phpstan/bootstrap.php`
- Create: `scripts/phpstan.sh`
- Modify: `app/pages/image.php`
- Modify: `app/partials/header.php`
- Modify: `scripts/check-file-modes.sh`

- [x] Configure PHPStan at level 3 for the current procedural include structure.
- [x] Load known APOD constants and function includes for static analysis.
- [x] Define safe include defaults for page partial variables PHPStan cannot infer.
- [x] Add a wrapper script that prefers `vendor/bin/phpstan`.

### Task 3: Documentation And CI

**Files:**
- Create: `docs/phpstan.md`
- Modify: `AGENTS.md`
- Modify: `.github/workflows/ci.yml`

- [x] Document APOD's PHPStan baseline and how to read stricter findings.
- [x] Add PHPStan to local verification instructions.
- [x] Install Composer dependencies in CI and run `scripts/phpstan.sh`.

### Task 4: Verification

**Files:**
- Run checks only.

- [x] Run `composer install`.
- [x] Run `scripts/phpstan.sh`.
- [x] Run the existing APOD verification scripts.
- [ ] Commit and push the update to the active PR branch.
