<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Services;

/**
 * Third parties can register one-off services without writing a class file.
 */
final class CallableService extends BaseService
{
    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $definition
     * @param  callable(array<string, mixed>): array<string, array<string, mixed>>|null  $volumes
     */
    public function __construct(
        private readonly string $serviceName,
        private $definition,
        private $volumes = null,
    ) {}

    public function name(): string
    {
        return $this->serviceName;
    }

    public function definition(array $context): array
    {
        return ($this->definition)($context);
    }

    public function volumes(array $context): array
    {
        if ($this->volumes === null) {
            return [];
        }

        return ($this->volumes)($context);
    }
}
