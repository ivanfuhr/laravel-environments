<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Profile
    |--------------------------------------------------------------------------
    |
    | Profiles group the services and overrides used when generating Compose
    | files. Use "development" locally and "production" for deployable stacks.
    |
    */

    'default_profile' => env('ENVIRONMENTS_PROFILE', 'development'),

    /*
    |--------------------------------------------------------------------------
    | Compose Output
    |--------------------------------------------------------------------------
    */

    'compose' => [
        'filename' => env('ENVIRONMENTS_COMPOSE_FILE', 'docker-compose.yml'),
        'project_name' => env('ENVIRONMENTS_PROJECT', 'laravel'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared Defaults
    |--------------------------------------------------------------------------
    */

    'php' => [
        'version' => env('ENVIRONMENTS_PHP_VERSION', '8.4'),
    ],

    'network' => [
        'name' => env('ENVIRONMENTS_NETWORK', 'environments'),
        'driver' => 'bridge',
    ],

    'webserver' => env('ENVIRONMENTS_WEBSERVER', 'nginx'),

    /*
    |--------------------------------------------------------------------------
    | Service Defaults
    |--------------------------------------------------------------------------
    |
    | Defaults merged into each service before profile overrides. Keys match
    | registered service names (mysql, redis, app, …).
    |
    */

    'services' => [
        'app' => [
            'image' => null,
            'build' => true,
            'port' => '${APP_PORT:-80}',
            'vite_port' => '${VITE_PORT:-5173}',
            'workers' => 1,
            'memory_limit' => '256M',
        ],
        'nginx' => [
            'image' => 'nginx:1.27-alpine',
            'port' => '${APP_PORT:-80}',
            'ssl_port' => '${APP_SSL_PORT:-443}',
            'ssl' => false,
        ],
        'caddy' => [
            'image' => 'caddy:2-alpine',
            'port' => '${APP_PORT:-80}',
            'ssl_port' => '${APP_SSL_PORT:-443}',
            'ssl' => true,
        ],
        'mysql' => [
            'image' => 'mysql:8.4',
            'port' => '${FORWARD_DB_PORT:-3306}',
            'database' => '${DB_DATABASE:-laravel}',
            'username' => '${DB_USERNAME:-laravel}',
            'password' => '${DB_PASSWORD:-password}',
            'root_password' => '${DB_PASSWORD:-password}',
        ],
        'pgsql' => [
            'image' => 'postgres:16-alpine',
            'port' => '${FORWARD_DB_PORT:-5432}',
            'database' => '${DB_DATABASE:-laravel}',
            'username' => '${DB_USERNAME:-laravel}',
            'password' => '${DB_PASSWORD:-password}',
        ],
        'redis' => [
            'image' => 'redis:alpine',
            'port' => '${FORWARD_REDIS_PORT:-6379}',
        ],
        'meilisearch' => [
            'image' => 'getmeili/meilisearch:latest',
            'port' => '${FORWARD_MEILISEARCH_PORT:-7700}',
            'key' => '${MEILISEARCH_KEY:-}',
        ],
        'mailpit' => [
            'image' => 'axllent/mailpit:latest',
            'port' => '${FORWARD_MAILPIT_PORT:-1025}',
            'dashboard_port' => '${FORWARD_MAILPIT_DASHBOARD_PORT:-8025}',
        ],
        'minio' => [
            'image' => 'minio/minio:latest',
            'port' => '${FORWARD_MINIO_PORT:-9000}',
            'console_port' => '${FORWARD_MINIO_CONSOLE_PORT:-8900}',
            'root_user' => 'sail',
            'root_password' => 'password',
        ],
        'soketi' => [
            'image' => 'quay.io/soketi/soketi:latest-16-alpine',
            'port' => '${FORWARD_SOKETI_PORT:-6001}',
        ],
        'selenium' => [
            'image' => 'selenium/standalone-chrome',
        ],
        'queue' => [
            'command' => 'php artisan queue:work --sleep=3 --tries=3 --max-time=3600',
            'replicas' => 1,
        ],
        'scheduler' => [
            'command' => 'php artisan schedule:work',
        ],
        'horizon' => [
            'command' => 'php artisan horizon',
        ],
        'octane' => [
            'server' => 'frankenphp',
            'port' => '${APP_PORT:-80}',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Profiles
    |--------------------------------------------------------------------------
    |
    | Each profile lists enabled services and optional per-service overrides.
    |
    */

    'profiles' => [
        'development' => [
            'services' => ['app', 'nginx', 'mysql', 'redis', 'mailpit'],
            'app' => [
                'xdebug' => true,
                'mount_source' => true,
            ],
            'nginx' => [
                'ssl' => false,
            ],
        ],
        'production' => [
            'services' => ['app', 'nginx', 'mysql', 'redis', 'queue', 'scheduler'],
            'app' => [
                'xdebug' => false,
                'mount_source' => false,
                'replicas' => 2,
            ],
            'nginx' => [
                'ssl' => true,
            ],
            'queue' => [
                'replicas' => 2,
            ],
        ],
    ],

];
