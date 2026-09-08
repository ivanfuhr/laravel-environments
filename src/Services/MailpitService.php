<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Services;

final class MailpitService extends BaseService
{
    public function name(): string
    {
        return 'mailpit';
    }

    public function definition(array $context): array
    {
        $options = $this->options($context);

        return [
            'image' => $this->stringOption($options, 'image', 'axllent/mailpit:latest'),
            'ports' => [
                $this->stringOption($options, 'port', '${FORWARD_MAILPIT_PORT:-1025}').':1025',
                $this->stringOption($options, 'dashboard_port', '${FORWARD_MAILPIT_DASHBOARD_PORT:-8025}').':8025',
            ],
            'networks' => [$this->network($context)],
        ];
    }

    public function volumes(array $context): array
    {
        return [];
    }
}
