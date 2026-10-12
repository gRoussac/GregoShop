#!/usr/bin/env bash
# Apply empty-shop adjustments to Composer-installed ps_apiresources tests.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../../.." && pwd)"
MODULE_DIR="${ROOT}/modules/ps_apiresources"
PATCH_FILE="${ROOT}/tests/Resources/ci/ps_apiresources-empty-shop.patch"

if [[ ! -d "${MODULE_DIR}/tests/Integration" ]]; then
  echo "ps_apiresources tests not found at ${MODULE_DIR}" >&2
  exit 1
fi

# Idempotent: skip when already applied.
if grep -q 'requires-demo-catalog' "${MODULE_DIR}/tests/Integration/phpunit-ci.xml" 2>/dev/null; then
  echo "ps_apiresources empty-shop patch already applied"
  exit 0
fi

patch -p1 -d "${MODULE_DIR}" < "${PATCH_FILE}"
echo "Applied ps_apiresources empty-shop patch"
