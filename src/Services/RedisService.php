<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Services;

final class RedisService extends BaseService
{
    public function name(): string
    {
        return 'redis';
    }

    public function definition(array $context): array
    {
        $options = $this->options($context);

        return [
            'image' => $this->stringOption($options, 'image', 'redis:alpine'),
            'ports' => [$this->stringOption($options, 'port', '${FORWARD_REDIS_PORT:-6379}').':6379'],
            'volumes' => ['environments-redis:/data'],
            'networks' => [$this->network($context)],
            'healthcheck' => $this->healthcheck(['CMD', 'redis-cli', 'ping']),
            'restart' => $this->profile($context) === 'production' ? 'unless-stopped' : 'no',
        ];
    }

    public function volumes(array $context): array
    {
        return ['environments-redis' => ['driver' => 'local']];
    }
}
