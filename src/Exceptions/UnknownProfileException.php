<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Exceptions;

use InvalidArgumentException;

final class UnknownProfileException extends InvalidArgumentException
{
    public static function forName(string $name): self
    {
        return new self("Unknown environment profile [{$name}].");
    }
}
