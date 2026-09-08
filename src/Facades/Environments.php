<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Facades;

use Illuminate\Support\Facades\Facade;
use IvanFuhr\LaravelEnvironments\Contracts\EnvironmentService;
use IvanFuhr\LaravelEnvironments\ServiceRegistry;

/**
 * @method static void register(EnvironmentService $service)
 * @method static bool has(string $name)
 * @method static EnvironmentService get(string $name)
 * @method static list<string> names()
 * @method static array<string, EnvironmentService> all()
 *
 * @see ServiceRegistry
 */
final class Environments extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ServiceRegistry::class;
    }
}
