<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Services;

final class CaddyService extends BaseService
{
    public function name(): string
    {
        return 'caddy';
    }

    public function definition(array $context): array
    {
        $options = $this->options($context);

        return [
            'image' => $this->stringOption($options, 'image', 'caddy:2-alpine'),
            'ports' => [
                $this->stringOption($options, 'port', '${APP_PORT:-80}').':80',
                $this->stringOption($options, 'ssl_port', '${APP_SSL_PORT:-443}').':443',
            ],
            'volumes' => [
                './docker/caddy/Caddyfile:/etc/caddy/Caddyfile:ro',
                './public:/var/www/html/public:ro',
            ],
            'depends_on' => [
                'app' => ['condition' => 'service_started'],
            ],
            'networks' => [$this->network($context)],
            'restart' => $this->profile($context) === 'production' ? 'unless-stopped' : 'no',
        ];
    }

    public function volumes(array $context): array
    {
        return [];
    }
}
