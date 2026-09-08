<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Services;

final class MeilisearchService extends BaseService
{
    public function name(): string
    {
        return 'meilisearch';
    }

    public function definition(array $context): array
    {
        $options = $this->options($context);

        return [
            'image' => $this->stringOption($options, 'image', 'getmeili/meilisearch:latest'),
            'ports' => [$this->stringOption($options, 'port', '${FORWARD_MEILISEARCH_PORT:-7700}').':7700'],
            'environment' => [
                'MEILI_NO_ANALYTICS' => true,
                'MEILI_MASTER_KEY' => $this->stringOption($options, 'key', '${MEILISEARCH_KEY:-}'),
            ],
            'volumes' => ['environments-meilisearch:/meili_data'],
            'networks' => [$this->network($context)],
            'healthcheck' => $this->healthcheck(['CMD', 'wget', '--no-verbose', '--spider', 'http://127.0.0.1:7700/health']),
        ];
    }

    public function volumes(array $context): array
    {
        return ['environments-meilisearch' => ['driver' => 'local']];
    }
}
