<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Services;

final class PgsqlService extends BaseService
{
    public function name(): string
    {
        return 'pgsql';
    }

    public function definition(array $context): array
    {
        $options = $this->options($context);

        return [
            'image' => $this->stringOption($options, 'image', 'postgres:16-alpine'),
            'ports' => [$this->stringOption($options, 'port', '${FORWARD_DB_PORT:-5432}').':5432'],
            'environment' => [
                'POSTGRES_DB' => $this->stringOption($options, 'database', '${DB_DATABASE:-laravel}'),
                'POSTGRES_USER' => $this->stringOption($options, 'username', '${DB_USERNAME:-laravel}'),
                'POSTGRES_PASSWORD' => $this->stringOption($options, 'password', '${DB_PASSWORD:-password}'),
            ],
            'volumes' => ['environments-pgsql:/var/lib/postgresql/data'],
            'networks' => [$this->network($context)],
            'healthcheck' => $this->healthcheck(['CMD', 'pg_isready', '-U', '${DB_USERNAME:-laravel}']),
            'restart' => $this->profile($context) === 'production' ? 'unless-stopped' : 'no',
        ];
    }

    public function volumes(array $context): array
    {
        return ['environments-pgsql' => ['driver' => 'local']];
    }
}
