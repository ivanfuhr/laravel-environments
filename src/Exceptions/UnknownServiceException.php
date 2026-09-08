<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Exceptions;

use InvalidArgumentException;

final class UnknownServiceException extends InvalidArgumentException
{
    public static function forName(string $name): self
    {
        return new self("Unknown environment service [{$name}].");
    }
}
