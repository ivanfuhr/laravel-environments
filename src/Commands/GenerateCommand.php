<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Commands;

use Illuminate\Console\Command;
use IvanFuhr\LaravelEnvironments\Compose\ComposeBuilder;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'environments:generate')]
final class GenerateCommand extends Command
{
    protected $signature = 'environments:generate
        {--profile= : Environment profile to generate}
        {--path= : Output path for the compose file}
        {--stdout : Print YAML to stdout instead of writing a file}';

    protected $description = 'Generate a docker-compose.yml from the environments configuration';

    public function handle(ComposeBuilder $builder): int
    {
        $profile = $this->stringOption('profile');

        if ($this->option('stdout')) {
            $this->output->write($builder->toYaml($profile));

            return self::SUCCESS;
        }

        $path = $this->stringOption('path')
            ?? $this->stringFromConfig('environments.compose.filename', 'docker-compose.yml');
        $written = $builder->write($profile, $path);

        $this->components->info("Compose file generated at [{$written}].");

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
