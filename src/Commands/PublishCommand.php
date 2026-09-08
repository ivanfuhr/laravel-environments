<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Commands;

use Illuminate\Console\Command;
use IvanFuhr\LaravelEnvironments\Support\StubPublisher;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'environments:publish')]
final class PublishCommand extends Command
{
    protected $signature = 'environments:publish
        {--path= : Directory to publish Docker stubs into}';

    protected $description = 'Publish Dockerfiles, nginx configs, and entrypoint scripts';

    public function handle(StubPublisher $publisher): int
    {
        $pathOption = $this->option('path');
        $path = is_string($pathOption) && $pathOption !== '' ? $pathOption : base_path('docker');

        $publisher->publish($path);

        $this->call('vendor:publish', [
            '--tag' => 'environments-config',
            '--force' => true,
        ]);

        $this->components->info("Docker stubs published to [{$path}].");

        return self::SUCCESS;
    }
}
