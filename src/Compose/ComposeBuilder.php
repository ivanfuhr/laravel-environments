<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Compose;

use IvanFuhr\LaravelEnvironments\Exceptions\InvalidConfigurationException;
use IvanFuhr\LaravelEnvironments\Exceptions\UnknownProfileException;
use IvanFuhr\LaravelEnvironments\ServiceRegistry;
use Symfony\Component\Yaml\Yaml;

final class ComposeBuilder
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly ServiceRegistry $registry,
        private array $config,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(?string $profile = null): array
    {
        $profile ??= $this->stringConfig(['default_profile'], 'development');
        $profileConfig = $this->profileConfig($profile);
        $rawServices = $profileConfig['services'] ?? null;

        if (! is_array($rawServices) || $rawServices === []) {
            throw InvalidConfigurationException::missingServices($profile);
        }

        $serviceNames = [];

        foreach ($rawServices as $serviceName) {
            if (is_string($serviceName) && $serviceName !== '') {
                $serviceNames[] = $serviceName;
            }
        }

        if ($serviceNames === []) {
            throw InvalidConfigurationException::missingServices($profile);
        }

        $network = $this->stringConfig(['network', 'name'], 'environments');
        $networkDriver = $this->stringConfig(['network', 'driver'], 'bridge');

        $compose = [
            'name' => $this->stringConfig(['compose', 'project_name'], 'laravel'),
            'services' => [],
            'networks' => [
                $network => [
                    'driver' => $networkDriver,
                ],
            ],
            'volumes' => [],
        ];

        /** @var array<string, array{condition: string}> $dependsOn */
        $dependsOn = [];

        foreach ($serviceNames as $name) {
            $context = $this->contextFor($name, $profile, $profileConfig, $serviceNames, $network);
            $service = $this->registry->get($name);
            $compose['services'][$name] = $service->definition($context);

            foreach ($service->volumes($context) as $volumeName => $volume) {
                $compose['volumes'][$volumeName] = $volume;
            }

            if (! in_array($name, ['app', 'nginx', 'caddy', 'octane'], true)) {
                $dependsOn[$name] = ['condition' => 'service_started'];
            }
        }

        if (isset($compose['services']['app']) && $dependsOn !== []) {
            $existing = $compose['services']['app']['depends_on'] ?? [];
            $compose['services']['app']['depends_on'] = array_merge(
                is_array($existing) ? $existing : [],
                $dependsOn,
            );
        }

        if ($compose['volumes'] === []) {
            unset($compose['volumes']);
        }

        return $compose;
    }

    public function toYaml(?string $profile = null): string
    {
        return Yaml::dump($this->build($profile), 6, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
    }

    public function write(?string $profile = null, ?string $path = null): string
    {
        $path ??= $this->stringConfig(['compose', 'filename'], 'docker-compose.yml');
        $yaml = $this->toYaml($profile);
        file_put_contents($path, $yaml);

        return $path;
    }

    /**
     * @return array<string, mixed>
     */
    private function profileConfig(string $profile): array
    {
        $profiles = $this->config['profiles'] ?? null;

        if (! is_array($profiles) || ! array_key_exists($profile, $profiles) || ! is_array($profiles[$profile])) {
            throw UnknownProfileException::forName($profile);
        }

        /** @var array<string, mixed> $config */
        $config = $profiles[$profile];

        return $config;
    }

    /**
     * @param  array<string, mixed>  $profileConfig
     * @param  list<string>  $serviceNames
     * @return array<string, mixed>
     */
    private function contextFor(string $name, string $profile, array $profileConfig, array $serviceNames, string $network): array
    {
        $serviceDefaults = $this->config['services'] ?? [];
        $defaults = is_array($serviceDefaults) && is_array($serviceDefaults[$name] ?? null)
            ? $serviceDefaults[$name]
            : [];
        $overrides = is_array($profileConfig[$name] ?? null)
            ? $profileConfig[$name]
            : [];

        /** @var array<string, mixed> $defaults */
        /** @var array<string, mixed> $overrides */
        return [
            'name' => $name,
            'profile' => $profile,
            'network' => $network,
            'php_version' => $this->stringConfig(['php', 'version'], '8.4'),
            'webserver' => $this->stringConfig(['webserver'], 'nginx'),
            'enabled_services' => $serviceNames,
            'options' => array_replace_recursive($defaults, $overrides),
        ];
    }

    /**
     * @param  list<string>  $keys
     */
    private function stringConfig(array $keys, string $default): string
    {
        $value = $this->config;

        foreach ($keys as $key) {
            if (! is_array($value) || ! array_key_exists($key, $value)) {
                return $default;
            }

            $value = $value[$key];
        }

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
