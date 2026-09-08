#!/usr/bin/env sh
set -e

if [ -f composer.json ] && [ ! -d vendor ]; then
  composer install --prefer-dist --no-interaction
fi

exec "$@"
