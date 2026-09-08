#!/usr/bin/env bash
set -euo pipefail

# Run PHPUnit with coverage. Prefer local pcov/xdebug; otherwise use the
# laravel-environments-php:8.4 image (see docker/quality.Dockerfile).

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

# Ensure writable dirs for non-root Docker coverage runs.
mkdir -p coverage .phpunit.cache
if [[ ! -w coverage || ! -w .phpunit.cache ]]; then
  docker run --rm -v "$ROOT":/app -w /app alpine \
    sh -c "rm -rf coverage .phpunit.cache && mkdir -p coverage .phpunit.cache && chown -R $(id -u):$(id -g) coverage .phpunit.cache"
fi

run_phpunit() {
  ./vendor/bin/phpunit --colors=always \
    --coverage-text \
    --coverage-clover=coverage/clover.xml \
    --coverage-html=coverage/html
}

if php -m 2>/dev/null | grep -qi pcov || php -m 2>/dev/null | grep -qi xdebug; then
  run_phpunit
else
  if ! docker image inspect laravel-environments-php:8.4 >/dev/null 2>&1; then
    docker build -t laravel-environments-php:8.4 -f docker/quality.Dockerfile .
  fi
  docker run --rm \
    --user "$(id -u):$(id -g)" \
    -e HOME=/tmp \
    -v "$ROOT":/app \
    -v /var/run/docker.sock:/var/run/docker.sock \
    -v "$(command -v docker)":/usr/bin/docker \
    -w /app \
    laravel-environments-php:8.4 \
    bash -lc './vendor/bin/phpunit --colors=always --coverage-text --coverage-clover=coverage/clover.xml --coverage-html=coverage/html'
fi

bash scripts/check-coverage.sh coverage/clover.xml
