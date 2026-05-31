#!/usr/bin/env bash
set -euo pipefail

js_files=(
  assets/js/apod.js
  assets/js/apod.min.js
  assets/js/video.js
  assets/js/video.min.js
)

for file in "${js_files[@]}"; do
  if [[ ! -s "$file" ]]; then
    printf 'Missing or empty asset: %s\n' "$file" >&2
    exit 1
  fi

  node --check "$file" >/dev/null
done

if rg -n 'innerHTML|outerHTML|insertAdjacentHTML|document\.write' "${js_files[@]}"; then
  printf 'Public JavaScript assets must not write HTML strings.\n' >&2
  exit 1
fi

printf 'Asset check passed.\n'
