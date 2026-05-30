#!/usr/bin/env bash
set -euo pipefail

php -l index.php
find app scripts -name '*.php' -print0 | xargs -0 -n1 php -l
