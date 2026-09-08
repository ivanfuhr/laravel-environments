<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Services;

final class OctaneService extends BaseService
{
    public function name(): string
    {
        return 'octane';
    }

    public function definition(array $context): array
    {
        $options = $this->options($context);
        $server = $this->stringOption($options, 'server', 'frankenphp');

        return [
            'build' => [
                'context' => './docker/php',
                'dockerfile' => 'Dockerfile.octane',
                'args' => [
                    'PHP_VERSION' => $this->phpVersion($context),
                ],
            ],
            'command' => "php artisan octane:start --server={$server} --host=0.0.0.0 --port=80",
            'ports' => [$this->stringOption($options, 'port', '${APP_PORT:-80}').':80'],
            'volumes' => ['.:/var/www/html'],
            'networks' => [$this->network($context)],
            'restart' => $this->profile($context) === 'production' ? 'unless-stopped' : 'no',
            'healthcheck' => $this->healthcheck(['CMD-SHELL', 'curl -f http://localhost/health || exit 1']),
        ];
    }

    public function volumes(array $context): array
    {
        return [];
    }
}
