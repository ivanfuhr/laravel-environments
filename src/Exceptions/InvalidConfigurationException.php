<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Exceptions;

use RuntimeException;

final class InvalidConfigurationException extends RuntimeException
{
    public static function missingServices(string $profile): self
    {
        return new self("Profile [{$profile}] does not define any services.");
    }
}
