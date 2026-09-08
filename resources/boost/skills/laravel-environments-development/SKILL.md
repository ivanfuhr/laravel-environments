---
name: laravel-environments-development
description: Generate, extend, and operate Laravel Environments Docker Compose stacks for development and production profiles, including custom services and publish tags.
---

# Laravel Environments Development

## When to use this skill

Use this skill when installing or configuring `ivanfuhr/laravel-environments`, generating `docker-compose.yml`, choosing development vs production profiles, registering custom Compose services, or debugging Docker stubs published by the package.

## Goals

- Keep Compose generation driven by `config/environments.php` + the service registry.
- Prefer Artisan commands over hand-editing generated Compose unless the user asks for a one-off override.
- Support both **development** (local DX: Mailpit, Vite ports, source mounts) and **production** (workers, scheduler, SSL, replicas, healthchecks).

## Install & publish

```bash
composer require ivanfuhr/laravel-environments --dev
php artisan environments:install --profile=development
```

Publish tags (Laravel package groups):

| Tag | Contents |
| --- | --- |
| `environments-config` | `config/environments.php` |
| `environments-docker` | Dockerfiles, nginx/Caddy stubs, entrypoints under `docker/` |
| `environments-bin` | `environments` CLI wrapper in the app root |

```bash
php artisan vendor:publish --tag=environments-config
php artisan vendor:publish --provider="IvanFuhr\LaravelEnvironments\EnvironmentsServiceProvider"
```

## Generate Compose

```bash
php artisan environments:generate --profile=development
php artisan environments:generate --profile=production
php artisan environments:generate --profile=production --stdout
```

After changing `profiles.*.services` or `services.*`, regenerate Compose before `up`.

## Operate the stack

```bash
php artisan environments:up --detach --build
php artisan environments:down
# optional wrapper:
./vendor/bin/environments up -d
```

## Configuration map

Key keys in `config/environments.php`:

- `default_profile` — `development` or `production`
- `compose.filename` / `compose.project_name`
- `php.version`
- `network.name` / `network.driver`
- `webserver` — `nginx` or `caddy`
- `profiles.{name}.services` — ordered list of registered service names
- `services.{name}.*` — per-service defaults (image, ports, ssl, replicas, workers, …)

Never store closures in this config (breaks `config:cache`).

## Extending with a custom service

Implement `IvanFuhr\LaravelEnvironments\Contracts\EnvironmentService` or use `CallableService`, register it, then add the name to a profile:

```php
use IvanFuhr\LaravelEnvironments\Facades\Environments;
use IvanFuhr\LaravelEnvironments\Services\CallableService;

Environments::register(new CallableService(
    'typesense',
    fn (array $context): array => [
        'image' => 'typesense/typesense:latest',
        'networks' => [$context['network']],
    ],
    fn (): array => ['environments-typesense' => ['driver' => 'local']],
));
```

```php
// config/environments.php
'profiles' => [
    'development' => [
        'services' => ['app', 'nginx', 'mysql', 'redis', 'typesense'],
    ],
],
```

`definition(array $context)` receives at least: `profile`, `network`, `php_version`, `options` (merged service + profile options).

## Built-in services

`app`, `nginx`, `caddy`, `mysql`, `pgsql`, `redis`, `meilisearch`, `mailpit`, `minio`, `soketi`, `selenium`, `queue`, `scheduler`, `horizon`, `octane`.

## Agent checklist

1. Confirm the package is installed and config/stubs are published when needed.
2. Edit `config/environments.php` (or register services) instead of inventing parallel Compose setups.
3. Run `environments:generate` for the target profile.
4. Use `environments:up` / `down` (or the bin wrapper) to operate the stack.
5. For production, verify SSL, workers/scheduler, replicas, and that source mounts / debug tooling are not enabled unintentionally.
