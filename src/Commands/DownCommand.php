<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Commands;

use Illuminate\Console\Command;
use IvanFuhr\LaravelEnvironments\Support\DockerComposeRunner;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'environments:down')]
final class DownCommand extends Command
{
    protected $signature = 'environments:down
        {--volumes : Remove named volumes declared in the compose file}
        {--file= : Compose file path}';

    protected $description = 'Stop the configured Docker Compose environment';

    public function handle(DockerComposeRunner $runner): int
    {
        $args = ['down'];

        if ($this->option('volumes')) {
            $args[] = '--volumes';
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
