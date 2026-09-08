<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Support;

use Composer\InstalledVersions;

final class PackageMetadata
{
    public static function version(string $package = 'ivanfuhr/laravel-environments'): string
    {
        if (! InstalledVersions::isInstalled($package)) {
            return 'dev';
        }

        return InstalledVersions::getPrettyVersion($package) ?: 'dev';
    }
}
