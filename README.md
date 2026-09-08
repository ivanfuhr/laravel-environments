# Laravel Environments

Extensible Docker Compose environments for Laravel — development **and** production.

Inspired by [Laravel Sail](https://github.com/laravel/sail), but designed as a proper package with a service registry so third parties can register drivers/services, and with first-class production profiles (workers, SSL, replicas, healthchecks).

## Requirements

- PHP 8.3+
- Laravel 11 / 12 / 13
- Docker + Docker Compose

## Installation

```bash
composer require ivanfuhr/laravel-environments --dev
php artisan environments:install --profile=development
```

The package uses [Laravel package discovery](https://laravel.com/docs/packages#package-discovery) (`extra.laravel.providers` / `aliases` in `composer.json`), so the service provider and `Environments` facade are registered automatically.

### Publishing resources

Publish groups follow the [Laravel packages guide](https://laravel.com/docs/packages#publishing-file-groups):

```bash
# Config only
php artisan vendor:publish --tag=environments-config

# Docker stubs (Dockerfiles, nginx, entrypoints)
php artisan vendor:publish --tag=environments-docker

# CLI wrapper into the app root
php artisan vendor:publish --tag=environments-bin

# Everything from this provider
php artisan vendor:publish --provider="IvanFuhr\LaravelEnvironments\EnvironmentsServiceProvider"
```

Or via the install helper:

```bash
php artisan environments:install --profile=development
php artisan environments:publish
```

## Generate compose files

```bash
# Development stack (app, nginx, mysql, redis, mailpit by default)
php artisan environments:generate --profile=development

# Production stack (app, nginx, mysql, redis, queue, scheduler + SSL/replicas)
php artisan environments:generate --profile=production

# Print YAML without writing a file
php artisan environments:generate --profile=production --stdout
```

Start / stop:

```bash
php artisan environments:up --detach --build
php artisan environments:down
```

Or use the thin wrapper: `./vendor/bin/environments up -d`

## Configuration

See `config/environments.php` after publish. Key knobs:

- `default_profile` — `development` or `production`
- `php.version` — PHP build arg
- `profiles.*.services` — which registered services to include
- `services.*` — per-service defaults (images, ports, replicas, SSL, …)

## Extensibility

Implement `IvanFuhr\LaravelEnvironments\Contracts\EnvironmentService` (or use `CallableService`) and register it via the facade or `ServiceRegistry`:

```php
use IvanFuhr\LaravelEnvironments\Facades\Environments;
use IvanFuhr\LaravelEnvironments\Services\CallableService;

public function boot(): void
{
    Environments::register(new CallableService(
        'typesense',
        fn (array $context) => [
            'image' => 'typesense/typesense:latest',
            'networks' => [$context['network']],
        ],
        fn () => ['environments-typesense' => ['driver' => 'local']],
    ));
}
```

Then add `'typesense'` to a profile's `services` list in `config/environments.php`.

Built-in services: `app`, `nginx`, `caddy`, `mysql`, `pgsql`, `redis`, `meilisearch`, `mailpit`, `minio`, `soketi`, `selenium`, `queue`, `scheduler`, `horizon`, `octane`.

## Commands

| Command | Purpose |
|---------|---------|
| `environments:install` | Publish config + Docker stubs and generate compose |
| `environments:generate` | Regenerate `docker-compose.yml` from config |
| `environments:publish` | Publish Dockerfiles / nginx / entrypoints |
| `environments:up` | `docker compose up` wrapper |
| `environments:down` | `docker compose down` wrapper |

## Laravel Boost

This package ships [Laravel Boost](https://laravel.com/docs/boost) AI guidelines and an agent skill so coding agents understand how to install, generate, and extend environments when the package is present:

- Guideline: `resources/boost/guidelines/core.blade.php` (always-on via `boost:install`)
- Skill: `resources/boost/skills/laravel-environments-development/SKILL.md` (on-demand)

In an application with Boost installed:

```bash
php artisan boost:install
# or later:
php artisan boost:update --discover
```

## Quality

```bash
composer check      # rector dry-run, pint, phpstan, phpunit
composer coverage   # PHPUnit with coverage (needs pcov/xdebug)
composer moat       # Laravel Moat readiness / GitHub audit
composer ci         # check + coverage + moat
```

## License

MIT
