<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Services;

final class SeleniumService extends BaseService
{
    public function name(): string
    {
        return 'selenium';
    }

    public function definition(array $context): array
    {
        $options = $this->options($context);

        return [
            'image' => $this->stringOption($options, 'image', 'selenium/standalone-chrome'),
            'extra_hosts' => ['host.docker.internal:host-gateway'],
            'volumes' => ['/dev/shm:/dev/shm'],
            'networks' => [$this->network($context)],
        ];
    }

    public function volumes(array $context): array
    {
        return [];
    }
}
