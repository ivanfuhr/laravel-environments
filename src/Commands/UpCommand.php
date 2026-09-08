<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Commands;

use Illuminate\Console\Command;
use IvanFuhr\LaravelEnvironments\Support\DockerComposeRunner;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'environments:up')]
final class UpCommand extends Command
{
    protected $signature = 'environments:up
        {--detach : Run containers in the background}
        {--build : Build images before starting}
        {--file= : Compose file path}';

    protected $description = 'Start the configured Docker Compose environment';

    public function handle(DockerComposeRunner $runner): int
    {
        $args = ['up'];

        if ($this->option('detach')) {
            $args[] = '-d';
        }

        if ($this->option('build')) {
            $args[] = '--build';
        }

        return $runner->run($args, $this->composeFile(), function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });
    }

    private function composeFile(): string
    {
        $file = $this->option('file');

        if (is_string($file) && $file !== '') {
            return $file;
        }

        $configured = config('environments.compose.filename', 'docker-compose.yml');

        return is_string($configured) && $configured !== '' ? $configured : 'docker-compose.yml';
    }
}
