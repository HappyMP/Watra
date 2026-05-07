#!/usr/bin/env bash
set -euo pipefail

echo "=== PHPStan ==="
vendor/bin/phpstan analyse

echo "=== ECS (dry-run) ==="
vendor/bin/ecs check

echo "=== PHPUnit ==="
vendor/bin/phpunit

echo "=== Behat ==="
vendor/bin/behat --colors

echo ""
echo "=== All checks passed ==="
