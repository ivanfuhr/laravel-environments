<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Tests;

use IvanFuhr\LaravelEnvironments\EnvironmentsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            EnvironmentsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('environments.compose.filename', $this->composePath());
    }

    protected function composePath(): string
    {
        return sys_get_temp_dir().'/laravel-environments-'.md5(static::class).'.yml';
    }

    protected function tearDown(): void
    {
        $path = $this->composePath();

        if (is_file($path)) {
            unlink($path);
        }

        parent::tearDown();
    }
}
