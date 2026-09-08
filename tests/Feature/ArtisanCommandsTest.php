<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Tests\Feature;

use FilesystemIterator;
use IvanFuhr\LaravelEnvironments\Compose\ComposeBuilder;
use IvanFuhr\LaravelEnvironments\Support\DockerComposeRunner;
use IvanFuhr\LaravelEnvironments\Tests\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

final class ArtisanCommandsTest extends TestCase
{
    public function test_generate_command_writes_compose_file(): void
    {
        $path = $this->composePath();

        $this->artisan('environments:generate', [
            '--profile' => 'development',
            '--path' => $path,
        ])->assertSuccessful();

        $this->assertFileExists($path);
        $parsed = Yaml::parseFile($path);
        $this->assertArrayHasKey('mysql', $parsed['services']);
    }

    public function test_generate_command_can_print_to_stdout(): void
    {
        $this->artisan('environments:generate', [
            '--profile' => 'production',
            '--stdout' => true,
        ])
            ->expectsOutputToContain('queue:')
            ->assertSuccessful();
    }

    public function test_publish_command_publishes_stubs(): void
    {
        $destination = sys_get_temp_dir().'/env-publish-'.uniqid('', true);

        $this->artisan('environments:publish', [
            '--path' => $destination,
        ])->assertSuccessful();

        $this->assertFileExists($destination.'/php/Dockerfile');
        $this->deleteDirectory($destination);
    }

    public function test_install_command_publishes_and_generates(): void
    {
        $path = $this->composePath();
        $dockerPath = sys_get_temp_dir().'/env-install-docker-'.uniqid('', true);

        // Point StubPublisher destination via publish path used inside install:
        // InstallCommand publishes to base_path('docker'); override by generating only.
        $this->app->instance(
            \IvanFuhr\LaravelEnvironments\Support\StubPublisher::class,
            new \IvanFuhr\LaravelEnvironments\Support\StubPublisher,
        );

        config()->set('environments.compose.filename', $path);

        $this->artisan('environments:install', [
            '--profile' => 'development',
            '--path' => $path,
        ])->assertSuccessful();

        $this->assertFileExists($path);

        $baseDocker = base_path('docker');
        if (is_dir($baseDocker)) {
            $this->deleteDirectory($baseDocker);
        }

        unset($dockerPath);
    }

    public function test_up_and_down_commands_delegate_to_runner(): void
    {
        $calls = [];

        $this->app->instance(DockerComposeRunner::class, new DockerComposeRunner(
            sys_get_temp_dir(),
            function (array $command) use (&$calls): Process {
                $calls[] = $command;

                return new class(['true']) extends Process
                {
                    public function run(?callable $callback = null, array $env = []): int
                    {
                        if ($callback !== null) {
                            $callback(Process::OUT, "running\n");
                        }

                        return 0;
                    }

                    public function setTimeout(?float $timeout): static
                    {
                        return $this;
                    }
                };
            },
        ));

        $this->artisan('environments:up', [
            '--detach' => true,
            '--build' => true,
            '--file' => 'compose.yml',
        ])->assertSuccessful();

        $this->artisan('environments:down', [
            '--volumes' => true,
            '--file' => 'compose.yml',
        ])->assertSuccessful();

        $this->assertSame(
            ['docker', 'compose', '-f', 'compose.yml', 'up', '-d', '--build'],
            $calls[0],
        );
        $this->assertSame(
            ['docker', 'compose', '-f', 'compose.yml', 'down', '--volumes'],
            $calls[1],
        );
    }

    public function test_compose_builder_is_bound_in_container(): void
    {
        $builder = $this->app->make(ComposeBuilder::class);

        $this->assertInstanceOf(ComposeBuilder::class, $builder);
        $this->assertArrayHasKey('services', $builder->build());
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($directory);
    }
}
