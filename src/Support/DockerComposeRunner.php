<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Support;

use Symfony\Component\Process\Process;

final class DockerComposeRunner
{
    /**
     * @param  (callable(list<string>, ?string): Process)|null  $processFactory
     */
    public function __construct(
        private readonly ?string $workingDirectory = null,
        private $processFactory = null,
    ) {}

    /**
     * @param  list<string>  $arguments
     */
    public function run(array $arguments, ?string $composeFile = null, ?callable $output = null): int
    {
        $command = array_merge(
            ['docker', 'compose'],
            $composeFile !== null ? ['-f', $composeFile] : [],
            $arguments,
        );

        $cwd = $this->workingDirectory ?? (function_exists('base_path') ? base_path() : getcwd());
        $cwd = is_string($cwd) ? $cwd : null;

        $process = $this->processFactory !== null
            ? ($this->processFactory)($command, $cwd)
            : new Process($command, $cwd);

        $process->setTimeout(null);

        return $process->run(function (string $type, string $buffer) use ($output): void {
            if ($output !== null) {
                $output($type, $buffer);
            }
        });
    }
}
