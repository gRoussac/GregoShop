#!/usr/bin/env bash
# Apply empty-shop adjustments to Composer-installed ps_apiresources tests.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../../.." && pwd)"
MODULE_DIR="${ROOT}/modules/ps_apiresources"
PATCH_FILE="${ROOT}/tests/Resources/ci/ps_apiresources-empty-shop.patch"
VERSIONED_TEST="${MODULE_DIR}/tests/Integration/ApiPlatform/VersionedEndpointsTest.php"

if [[ ! -d "${MODULE_DIR}/tests/Integration" ]]; then
  echo "ps_apiresources tests not found at ${MODULE_DIR}" >&2
  exit 1
fi

has_group_attribute() {
  grep -q "#\\[Group('requires-demo-catalog')\\]" "${VERSIONED_TEST}" 2>/dev/null
}

has_phpunit_exclude() {
  grep -q 'requires-demo-catalog' "${MODULE_DIR}/tests/Integration/phpunit-ci.xml" 2>/dev/null
}

# Idempotent: skip when exclude list + PHPUnit 10 Group attribute are both present.
# Docblock @group alone is not enough: PHPUnit ignores it when the class has attributes.
if has_phpunit_exclude && has_group_attribute; then
  echo "ps_apiresources empty-shop patch already applied"
  exit 0
fi

# Upgrade path: previous patch left the exclude list but VersionedEndpointsTest still lacked #[Group].
if has_phpunit_exclude && ! has_group_attribute; then
  php -r '
    $path = $argv[1];
    $code = file_get_contents($path);
    if (!str_contains($code, "use PHPUnit\\Framework\\Attributes\\Group;")) {
        $code = str_replace(
            "use PHPUnit\\Framework\\Attributes\\RunTestsInSeparateProcesses;",
            "use PHPUnit\\Framework\\Attributes\\Group;\nuse PHPUnit\\Framework\\Attributes\\RunTestsInSeparateProcesses;",
            $code
        );
    }
    if (!str_contains($code, "#[Group('\''requires-demo-catalog'\'')]")) {
        $code = str_replace(
            "#[RunTestsInSeparateProcesses]\nclass VersionedEndpointsTest",
            "#[RunTestsInSeparateProcesses]\n#[Group('\''requires-demo-catalog'\'')]\nclass VersionedEndpointsTest",
            $code
        );
    }
    $code = preg_replace("/\n \*\n \* @group requires-demo-catalog/", "", $code) ?? $code;
    file_put_contents($path, $code);
  ' "${VERSIONED_TEST}"
  echo "Upgraded VersionedEndpointsTest to #[Group('requires-demo-catalog')]"
  exit 0
fi

patch -p1 -d "${MODULE_DIR}" < "${PATCH_FILE}"
echo "Applied ps_apiresources empty-shop patch"
