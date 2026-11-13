#!/usr/bin/env bash
set -euo pipefail

echo "=== PHP Unit Tests + Coverage ==="
./vendor/bin/pest --testsuite=Unit --coverage --min=80

echo ""
echo "=== Asset Build Check ==="
npm run tokens
npm run production

echo ""
echo "=== Storybook Smoke Tests ==="
# Start PHP story server in background
php -S localhost:6007 storybook-server/index.php &
PHP_PID=$!

# Wait for server to be ready
for i in $(seq 1 10); do
    if curl -s http://localhost:6007/?id=atoms/button > /dev/null 2>&1; then
        break
    fi
    sleep 0.5
done

# Run Playwright storybook tests
npx playwright test tests/e2e/specs/storybook/ --config=tests/e2e/playwright.config.ts

# Clean up
kill $PHP_PID 2>/dev/null || true

echo ""
echo "=== All CI Tests Passed ==="
