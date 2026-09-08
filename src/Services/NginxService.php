<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Services;

final class NginxService extends BaseService
{
    public function name(): string
    {
        return 'nginx';
    }

    public function definition(array $context): array
    {
        $options = $this->options($context);
        $ports = [
            $this->stringOption($options, 'port', '${APP_PORT:-80}').':80',
        ];

        if ($this->boolOption($options, 'ssl', false)) {
            $ports[] = $this->stringOption($options, 'ssl_port', '${APP_SSL_PORT:-443}').':443';
        }

        return [
            'image' => $this->stringOption($options, 'image', 'nginx:1.27-alpine'),
            'ports' => $ports,
            'volumes' => [
                './docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro',
                './public:/var/www/html/public:ro',
            ],
            'depends_on' => [
                'app' => ['condition' => 'service_started'],
            ],
            'networks' => [$this->network($context)],
            'restart' => $this->profile($context) === 'production' ? 'unless-stopped' : 'no',
            'healthcheck' => $this->healthcheck(['CMD', 'wget', '-qO-', 'http://localhost/health']),
        ];
    }

    public function volumes(array $context): array
    {
        return [];
    }
}
