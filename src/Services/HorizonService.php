<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Services;

final class HorizonService extends BaseService
{
    public function name(): string
    {
        return 'horizon';
    }

    public function definition(array $context): array
    {
        $options = $this->options($context);

        return [
            'build' => [
                'context' => './docker/php',
                'dockerfile' => $this->profile($context) === 'production' ? 'Dockerfile.prod' : 'Dockerfile',
                'args' => [
                    'PHP_VERSION' => $this->phpVersion($context),
                ],
            ],
            'command' => $this->stringOption($options, 'command', 'php artisan horizon'),
            'volumes' => ['.:/var/www/html'],
            'networks' => [$this->network($context)],
            'depends_on' => [
                'app' => ['condition' => 'service_started'],
                'redis' => ['condition' => 'service_started'],
            ],
            'restart' => $this->profile($context) === 'production' ? 'unless-stopped' : 'no',
        ];
    }

    public function volumes(array $context): array
    {
        return [];
    }
}
