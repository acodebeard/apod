#!/usr/bin/env bash
set -euo pipefail

for file in assets/js/apod.js assets/js/apod.min.js; do
  if [[ ! -s "$file" ]]; then
    printf 'Missing or empty asset: %s\n' "$file" >&2
    exit 1
  fi

  node --check "$file" >/dev/null
done

printf 'Asset check passed.\n'
