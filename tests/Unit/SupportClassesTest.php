<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Tests\Unit;

use FilesystemIterator;
use IvanFuhr\LaravelEnvironments\Support\DockerComposeRunner;
use IvanFuhr\LaravelEnvironments\Support\PackageMetadata;
use IvanFuhr\LaravelEnvironments\Support\StubPublisher;
use IvanFuhr\LaravelEnvironments\Tests\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Process\Process;

final class SupportClassesTest extends TestCase
{
    public function test_package_metadata_version_falls_back_for_unknown_packages(): void
    {
        $this->assertSame('dev', PackageMetadata::version('ivanfuhr/does-not-exist-package'));
        $this->assertNotSame('', PackageMetadata::version());
    }

    public function test_stub_publisher_copies_docker_stubs(): void
    {
        $destination = sys_get_temp_dir().'/env-stubs-'.uniqid('', true);
        $publisher = new StubPublisher;

        $publisher->publish($destination);

        $this->assertFileExists($destination.'/php/Dockerfile');
        $this->assertFileExists($destination.'/php/Dockerfile.prod');
        $this->assertFileExists($destination.'/nginx/default.conf');
        $this->assertFileExists($destination.'/caddy/Caddyfile');

        $this->deleteDirectory($destination);
    }

    public function test_docker_compose_runner_invokes_process_factory(): void
    {
        $received = [];
        $chunks = [];

        $runner = new DockerComposeRunner('/tmp', function (array $command, ?string $cwd) use (&$received): Process {
            $received = ['command' => $command, 'cwd' => $cwd];

            return new class(['true']) extends Process
            {
                public function run(?callable $callback = null, array $env = []): int
                {
                    if ($callback !== null) {
                        $callback(Process::OUT, 'ok');
                    }

                    return 0;
                }

                public function setTimeout(?float $timeout): static
                {
                    return $this;
                }
            };
        });

        $exit = $runner->run(['up', '-d'], 'docker-compose.yml', function (string $type, string $buffer) use (&$chunks): void {
            $chunks[] = [$type, $buffer];
        });

        $this->assertSame(0, $exit);
        $this->assertSame(['docker', 'compose', '-f', 'docker-compose.yml', 'up', '-d'], $received['command']);
        $this->assertSame('/tmp', $received['cwd']);
        $this->assertSame([[Process::OUT, 'ok']], $chunks);
    }

    public function test_docker_compose_runner_works_without_output_callback(): void
    {
        $runner = new DockerComposeRunner('/tmp', fn (): Process => new class(['true']) extends Process
        {
            public function run(?callable $callback = null, array $env = []): int
            {
                return 7;
            }

            public function setTimeout(?float $timeout): static
            {
                return $this;
            }
        });

        $this->assertSame(7, $runner->run(['ps']));
    }

    private function deleteDirectory(string $directory): void
    {
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
