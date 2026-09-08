<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Services;

final class MinioService extends BaseService
{
    public function name(): string
    {
        return 'minio';
    }

    public function definition(array $context): array
    {
        $options = $this->options($context);

        return [
            'image' => $this->stringOption($options, 'image', 'minio/minio:latest'),
            'ports' => [
                $this->stringOption($options, 'port', '${FORWARD_MINIO_PORT:-9000}').':9000',
                $this->stringOption($options, 'console_port', '${FORWARD_MINIO_CONSOLE_PORT:-8900}').':8900',
            ],
            'environment' => [
                'MINIO_ROOT_USER' => $this->stringOption($options, 'root_user', 'sail'),
                'MINIO_ROOT_PASSWORD' => $this->stringOption($options, 'root_password', 'password'),
            ],
            'volumes' => ['environments-minio:/data'],
            'networks' => [$this->network($context)],
            'command' => 'minio server /data --console-address ":8900"',
            'healthcheck' => $this->healthcheck(['CMD', 'mc', 'ready', 'local']),
        ];
    }

    public function volumes(array $context): array
    {
        return ['environments-minio' => ['driver' => 'local']];
    }
}
