<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Tests\Feature;

use FilesystemIterator;
use IvanFuhr\LaravelEnvironments\Facades\Environments;
use IvanFuhr\LaravelEnvironments\ServiceRegistry;
use IvanFuhr\LaravelEnvironments\Services\CallableService;
use IvanFuhr\LaravelEnvironments\Tests\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class PackageConventionsTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach ([config_path('environments.php'), base_path('environments')] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        $docker = base_path('docker');
        if (is_dir($docker)) {
            $this->deleteDirectory($docker);
        }

        parent::tearDown();
    }

    public function test_about_command_includes_package_section(): void
    {
        $this->artisan('about')
            ->expectsOutputToContain('Laravel Environments')
            ->assertSuccessful();
    }

    public function test_config_and_docker_publish_tags_are_registered(): void
    {
        $this->artisan('vendor:publish', [
            '--provider' => \IvanFuhr\LaravelEnvironments\EnvironmentsServiceProvider::class,
            '--tag' => 'environments-config',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertFileExists(config_path('environments.php'));

        $this->artisan('vendor:publish', [
            '--tag' => 'environments-docker',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertFileExists(base_path('docker/php/Dockerfile'));

        $this->artisan('vendor:publish', [
            '--tag' => 'environments-bin',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertFileExists(base_path('environments'));
    }

    public function test_facade_registers_custom_services(): void
    {
        Environments::register(new CallableService(
            'typesense',
            fn (array $context): array => [
                'image' => 'typesense/typesense:latest',
                'networks' => [$context['network']],
            ],
        ));

        $this->assertTrue(Environments::has('typesense'));
        $this->assertSame(
            $this->app->make(ServiceRegistry::class)->get('typesense'),
            Environments::get('typesense'),
        );
    }

    private function deleteDirectory(string $directory): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            $path = $file->getPathname();
            $file->isDir() ? rmdir($path) : unlink($path);
        }

        rmdir($directory);
    }
}
