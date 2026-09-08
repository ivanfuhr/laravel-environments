<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Commands;

use Illuminate\Console\Command;
use IvanFuhr\LaravelEnvironments\Compose\ComposeBuilder;
use IvanFuhr\LaravelEnvironments\Support\StubPublisher;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'environments:install')]
final class InstallCommand extends Command
{
    protected $signature = 'environments:install
        {--profile= : Environment profile to generate (development|production)}
        {--path= : Output path for the compose file}';

    protected $description = 'Publish Docker stubs/config and generate a compose file';

    public function handle(ComposeBuilder $builder, StubPublisher $publisher): int
    {
        $profile = $this->stringOption('profile')
            ?? $this->stringFromConfig('environments.default_profile', 'development');
        $path = $this->stringOption('path')
            ?? $this->stringFromConfig('environments.compose.filename', 'docker-compose.yml');

        $this->callSilent('vendor:publish', [
            '--tag' => 'environments-config',
            '--force' => true,
        ]);

        $publisher->publish(base_path('docker'));

        $written = $builder->write($profile, $path);

        $this->components->info("Laravel Environments installed using [{$profile}] profile.");
        $this->line("  Compose written to <fg=green>{$written}</>");
        $this->newLine();
        $this->line('  <fg=gray>➜</> <options=bold>php artisan environments:up</>');

        return self::SUCCESS;
    }

    private function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function stringFromConfig(string $key, string $default): string
    {
        $value = config($key, $default);

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
