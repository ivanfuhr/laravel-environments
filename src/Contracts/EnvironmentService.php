<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Contracts;

interface EnvironmentService
{
    public function name(): string;

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function definition(array $context): array;

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, array<string, mixed>>
     */
    public function volumes(array $context): array;
}
