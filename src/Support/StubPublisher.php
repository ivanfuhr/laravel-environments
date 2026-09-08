<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Support;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final readonly class StubPublisher
{
    private string $stubsPath;

    public function __construct(?string $stubsPath = null)
    {
        $this->stubsPath = $stubsPath ?? dirname(__DIR__, 2).'/stubs/docker';
    }

    public function publish(string $destination): void
    {
        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->stubsPath, FilesystemIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $relative = mb_substr($file->getPathname(), mb_strlen($this->stubsPath) + 1);
            $target = $destination.DIRECTORY_SEPARATOR.$relative;
            $targetDir = dirname($target);

            if (! is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            copy($file->getPathname(), $target);
        }
    }
}
