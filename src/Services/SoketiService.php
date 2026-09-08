<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Services;

final class SoketiService extends BaseService
{
    public function name(): string
    {
        return 'soketi';
    }

    public function definition(array $context): array
    {
        $options = $this->options($context);

        return [
            'image' => $this->stringOption($options, 'image', 'quay.io/soketi/soketi:latest-16-alpine'),
            'ports' => [$this->stringOption($options, 'port', '${FORWARD_SOKETI_PORT:-6001}').':6001'],
            'environment' => [
                'SOKETI_DEBUG' => '${SOKETI_DEBUG:-1}',
                'SOKETI_DEFAULT_APP_ID' => '${PUSHER_APP_ID:-app-id}',
                'SOKETI_DEFAULT_APP_KEY' => '${PUSHER_APP_KEY:-app-key}',
                'SOKETI_DEFAULT_APP_SECRET' => '${PUSHER_APP_SECRET:-app-secret}',
            ],
            'networks' => [$this->network($context)],
        ];
    }

    public function volumes(array $context): array
    {
        return [];
    }
}
