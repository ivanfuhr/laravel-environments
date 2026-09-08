<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Services;

final class MysqlService extends BaseService
{
    public function name(): string
    {
        return 'mysql';
    }

    public function definition(array $context): array
    {
        $options = $this->options($context);

        return [
            'image' => $this->stringOption($options, 'image', 'mysql:8.4'),
            'ports' => [$this->stringOption($options, 'port', '${FORWARD_DB_PORT:-3306}').':3306'],
            'environment' => [
                'MYSQL_ROOT_PASSWORD' => $this->stringOption($options, 'root_password', '${DB_PASSWORD:-password}'),
                'MYSQL_ROOT_HOST' => '%',
                'MYSQL_DATABASE' => $this->stringOption($options, 'database', '${DB_DATABASE:-laravel}'),
                'MYSQL_USER' => $this->stringOption($options, 'username', '${DB_USERNAME:-laravel}'),
                'MYSQL_PASSWORD' => $this->stringOption($options, 'password', '${DB_PASSWORD:-password}'),
            ],
            'volumes' => ['environments-mysql:/var/lib/mysql'],
            'networks' => [$this->network($context)],
            'healthcheck' => $this->healthcheck(['CMD', 'mysqladmin', 'ping', '-p${DB_PASSWORD:-password}']),
            'restart' => $this->profile($context) === 'production' ? 'unless-stopped' : 'no',
        ];
    }

    public function volumes(array $context): array
    {
        return ['environments-mysql' => ['driver' => 'local']];
    }
}
