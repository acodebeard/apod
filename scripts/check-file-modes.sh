#!/usr/bin/env bash
set -euo pipefail

allowed_executable='^(scripts/(generate-share-images\.php|php-lint\.sh|check-source-only\.sh|check-file-modes\.sh|check-assets\.sh|check-video-support\.php|phpstan\.sh))$'

bad_modes="$(
  git ls-files --stage \
    | awk -v allowed="$allowed_executable" '$1 == "100755" && $4 !~ allowed { print $4 }'
)"

if [[ -n "$bad_modes" ]]; then
  printf 'Unexpected executable tracked files:\n%s\n' "$bad_modes" >&2
  exit 1
fi

printf 'File-mode check passed.\n'
