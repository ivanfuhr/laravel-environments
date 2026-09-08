@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Laravel Environments

- This package generates Docker Compose stacks for **development and production**. Prefer it over inventing ad-hoc Compose files when `ivanfuhr/laravel-environments` is installed.
- Install / bootstrap: `{{ $assist->artisanCommand('environments:install --profile=development') }}`.
- Regenerate Compose after config changes: `{{ $assist->artisanCommand('environments:generate') }}` (add `--profile=production` for production stacks, `--stdout` to preview YAML).
- Start / stop containers: `{{ $assist->artisanCommand('environments:up --detach') }}` and `{{ $assist->artisanCommand('environments:down') }}`.
- Publish groups (Laravel package tags): `environments-config`, `environments-docker`, `environments-bin`. Prefer `{{ $assist->artisanCommand('vendor:publish --tag=environments-config') }}` for config-only changes.
- Configure via published `config/environments.php` (`default_profile`, `php.version`, `profiles.*.services`, `services.*`). Do **not** put closures in that config file.
- Extend with custom services by registering on the `Environments` facade / `ServiceRegistry` singleton, then listing the service name in a profile's `services` array.
- Built-in services: `app`, `nginx`, `caddy`, `mysql`, `pgsql`, `redis`, `meilisearch`, `mailpit`, `minio`, `soketi`, `selenium`, `queue`, `scheduler`, `horizon`, `octane`.
- When this package is the Docker environment for the app, run PHP/Artisan/Composer/Node **inside** the generated Compose stack (or via `./vendor/bin/environments` / `environments:up`), not against a mismatched local PHP runtime.
