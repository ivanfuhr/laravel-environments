<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Services;

final class QueueService extends BaseService
{
    public function name(): string
    {
        return 'queue';
    }

    public function definition(array $context): array
    {
        $options = $this->options($context);
        $definition = [
            'build' => [
                'context' => './docker/php',
                'dockerfile' => $this->profile($context) === 'production' ? 'Dockerfile.prod' : 'Dockerfile',
                'args' => [
                    'PHP_VERSION' => $this->phpVersion($context),
                ],
            ],
            'command' => $this->stringOption($options, 'command', 'php artisan queue:work --sleep=3 --tries=3 --max-time=3600'),
            'volumes' => ['.:/var/www/html'],
            'networks' => [$this->network($context)],
            'depends_on' => [
                'app' => ['condition' => 'service_started'],
            ],
            'restart' => $this->profile($context) === 'production' ? 'unless-stopped' : 'no',
        ];

        $replicas = $this->intOption($options, 'replicas', 1);

        if ($replicas > 1) {
            $definition['deploy'] = ['replicas' => $replicas];
        }

        return $definition;
    }

    public function volumes(array $context): array
    {
        return [];
    }
}
