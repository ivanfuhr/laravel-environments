<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Services;

use IvanFuhr\LaravelEnvironments\Contracts\EnvironmentService;

abstract class BaseService implements EnvironmentService
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function options(array $context): array
    {
        $options = $context['options'] ?? [];

        if (! is_array($options)) {
            return [];
        }

        $normalized = [];

        foreach ($options as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function network(array $context): string
    {
        return $this->asString($context['network'] ?? null, 'environments');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function profile(array $context): string
    {
        return $this->asString($context['profile'] ?? null, 'development');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function phpVersion(array $context): string
    {
        return $this->asString($context['php_version'] ?? null, '8.4');
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function stringOption(array $options, string $key, string $default): string
    {
        return $this->asString($options[$key] ?? null, $default);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function boolOption(array $options, string $key, bool $default): bool
    {
        $value = $options[$key] ?? null;

        return is_bool($value) ? $value : $default;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function intOption(array $options, string $key, int $default): int
    {
        $value = $options[$key] ?? null;

        return is_int($value) ? $value : $default;
    }

    /**
     * @param  list<string>  $test
     * @return array<string, mixed>
     */
    protected function healthcheck(array $test, int $retries = 3, string $timeout = '5s'): array
    {
        return [
            'test' => $test,
            'retries' => $retries,
            'timeout' => $timeout,
        ];
    }

    protected function asString(mixed $value, string $default): string
    {
        return is_string($value) && $value !== '' ? $value : $default;
    }
}
