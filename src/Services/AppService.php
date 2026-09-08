<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Services;

final class AppService extends BaseService
{
    public function name(): string
    {
        return 'app';
    }

    public function definition(array $context): array
    {
        $options = $this->options($context);
        $phpVersion = $this->phpVersion($context);
        $profile = $this->profile($context);
        $mountSource = $this->boolOption($options, 'mount_source', $profile === 'development');

        $definition = [
            'build' => [
                'context' => './docker/php',
                'dockerfile' => $profile === 'production' ? 'Dockerfile.prod' : 'Dockerfile',
                'args' => [
                    'PHP_VERSION' => $phpVersion,
                ],
            ],
            'image' => $this->stringOption($options, 'image', "laravel-environments-{$phpVersion}/app"),
            'extra_hosts' => ['host.docker.internal:host-gateway'],
            'environment' => [
                'APP_ENV' => $profile === 'production' ? 'production' : 'local',
                'PHP_MEMORY_LIMIT' => $this->stringOption($options, 'memory_limit', '256M'),
                'XDEBUG_MODE' => $this->boolOption($options, 'xdebug', false) ? 'develop,debug' : 'off',
            ],
            'networks' => [$this->network($context)],
            'restart' => $profile === 'production' ? 'unless-stopped' : 'no',
            'healthcheck' => $this->healthcheck(['CMD-SHELL', 'php -v || exit 1']),
        ];

        if ($mountSource) {
            $definition['volumes'] = ['.:/var/www/html'];
            $vite = $this->stringOption($options, 'vite_port', '${VITE_PORT:-5173}');
            $definition['ports'] = ["{$vite}:{$vite}"];
        }

        $replicas = $this->intOption($options, 'replicas', 1);

        if ($replicas > 1) {
            $definition['deploy'] = [
                'replicas' => $replicas,
            ];
        }

        return $definition;
    }

    public function volumes(array $context): array
    {
        return [];
    }
}
