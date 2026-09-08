<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Tests\Unit;

use FilesystemIterator;
use IvanFuhr\LaravelEnvironments\Compose\ComposeBuilder;
use IvanFuhr\LaravelEnvironments\Exceptions\InvalidConfigurationException;
use IvanFuhr\LaravelEnvironments\Exceptions\UnknownProfileException;
use IvanFuhr\LaravelEnvironments\ServiceRegistry;
use IvanFuhr\LaravelEnvironments\Services\BaseService;
use IvanFuhr\LaravelEnvironments\Support\DockerComposeRunner;
use IvanFuhr\LaravelEnvironments\Tests\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Process\Process;

final class CoverageEdgeCasesTest extends TestCase
{
    public function test_generate_uses_config_defaults_when_options_omitted(): void
    {
        $path = $this->composePath();
        config()->set('environments.compose.filename', $path);

        $this->artisan('environments:generate')->assertSuccessful();

        $this->assertFileExists($path);
    }

    public function test_install_uses_config_defaults_when_options_omitted(): void
    {
        $path = $this->composePath();
        config()->set('environments.compose.filename', $path);
        config()->set('environments.default_profile', 'development');

        $this->artisan('environments:install')->assertSuccessful();

        $this->assertFileExists($path);

        $docker = base_path('docker');
        if (is_dir($docker)) {
            $this->deleteDirectory($docker);
        }
    }

    public function test_up_and_down_fall_back_when_compose_filename_is_invalid(): void
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
                        return 0;
                    }

                    public function setTimeout(?float $timeout): static
                    {
                        return $this;
                    }
                };
            },
        ));

        config()->set('environments.compose.filename', '');

        $this->artisan('environments:up')->assertSuccessful();
        $this->artisan('environments:down')->assertSuccessful();

        $this->assertSame('docker-compose.yml', $calls[0][3]);
        $this->assertSame('docker-compose.yml', $calls[1][3]);
    }

    public function test_builder_rejects_profiles_with_only_non_string_services(): void
    {
        config()->set('environments.profiles.broken', [
            'services' => [1, null, false],
        ]);

        $this->expectException(InvalidConfigurationException::class);

        (new ComposeBuilder(
            $this->app->make(ServiceRegistry::class),
            config('environments'),
        ))->build('broken');
    }

    public function test_builder_falls_back_when_nested_config_keys_are_missing(): void
    {
        $config = config('environments');
        $config['network'] = 'not-an-array';

        $builder = new ComposeBuilder($this->app->make(ServiceRegistry::class), $config);
        $compose = $builder->build('development');

        $this->assertSame('environments', array_key_first($compose['networks']));
        $this->assertSame('bridge', $compose['networks']['environments']['driver']);
    }

    public function test_base_service_normalizes_invalid_options(): void
    {
        $probe = new class extends BaseService
        {
            public function name(): string
            {
                return 'probe';
            }

            public function definition(array $context): array
            {
                return [
                    'options' => $this->options($context),
                    'bool' => $this->boolOption($this->options($context), 'x', true),
                    'int' => $this->intOption($this->options($context), 'n', 9),
                    'string' => $this->stringOption($this->options($context), 's', 'fallback'),
                ];
            }

            public function volumes(array $context): array
            {
                return [];
            }
        };

        $this->assertSame([
            'options' => [],
            'bool' => true,
            'int' => 9,
            'string' => 'fallback',
        ], $probe->definition(['options' => 'bad']));

        $result = $probe->definition(['options' => [0 => 'skip', 'x' => 'nope', 'n' => 'nope', 's' => '']]);

        $this->assertSame(['x' => 'nope', 'n' => 'nope', 's' => ''], $result['options']);
        $this->assertTrue($result['bool']);
        $this->assertSame(9, $result['int']);
        $this->assertSame('fallback', $result['string']);
    }

    public function test_compose_builder_rebinding_handles_non_array_config(): void
    {
        $this->app->forgetInstance(ComposeBuilder::class);
        config()->set('environments', 'invalid');

        $builder = $this->app->make(ComposeBuilder::class);

        $this->expectException(UnknownProfileException::class);
        $builder->build('development');
    }

    public function test_docker_compose_runner_creates_real_process(): void
    {
        $runner = new DockerComposeRunner(sys_get_temp_dir());
        $exit = $runner->run(['version']);

        $this->assertContains($exit, [0, 1, 125]);
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
