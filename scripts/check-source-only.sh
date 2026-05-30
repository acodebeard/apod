#!/usr/bin/env bash
set -euo pipefail

tracked="$(
  git ls-files \
    | rg '^(images|thumbs)/|^audit\.php$' || true
)"

if [[ -n "$tracked" ]]; then
  printf 'Generated or local-only files are tracked:\n%s\n' "$tracked" >&2
  exit 1
fi

printf 'Source-only check passed.\n'
