<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments;

use IvanFuhr\LaravelEnvironments\Contracts\EnvironmentService;
use IvanFuhr\LaravelEnvironments\Exceptions\UnknownServiceException;

final class ServiceRegistry
{
    /** @var array<string, EnvironmentService> */
    private array $services = [];

    public function register(EnvironmentService $service): void
    {
        $this->services[$service->name()] = $service;
    }

    public function has(string $name): bool
    {
        return isset($this->services[$name]);
    }

    public function get(string $name): EnvironmentService
    {
        if (! $this->has($name)) {
            throw UnknownServiceException::forName($name);
        }

        return $this->services[$name];
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->services);
    }

    /**
     * @return array<string, EnvironmentService>
     */
    public function all(): array
    {
        return $this->services;
    }
}
