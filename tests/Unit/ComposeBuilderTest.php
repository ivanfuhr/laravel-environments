<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments\Tests\Unit;

use IvanFuhr\LaravelEnvironments\Compose\ComposeBuilder;
use IvanFuhr\LaravelEnvironments\Exceptions\InvalidConfigurationException;
use IvanFuhr\LaravelEnvironments\Exceptions\UnknownProfileException;
use IvanFuhr\LaravelEnvironments\Exceptions\UnknownServiceException;
use IvanFuhr\LaravelEnvironments\ServiceRegistry;
use IvanFuhr\LaravelEnvironments\Services\AppService;
use IvanFuhr\LaravelEnvironments\Services\CaddyService;
use IvanFuhr\LaravelEnvironments\Services\CallableService;
use IvanFuhr\LaravelEnvironments\Services\HorizonService;
use IvanFuhr\LaravelEnvironments\Services\MailpitService;
use IvanFuhr\LaravelEnvironments\Services\MeilisearchService;
use IvanFuhr\LaravelEnvironments\Services\MinioService;
use IvanFuhr\LaravelEnvironments\Services\MysqlService;
use IvanFuhr\LaravelEnvironments\Services\NginxService;
use IvanFuhr\LaravelEnvironments\Services\OctaneService;
use IvanFuhr\LaravelEnvironments\Services\PgsqlService;
use IvanFuhr\LaravelEnvironments\Services\QueueService;
use IvanFuhr\LaravelEnvironments\Services\RedisService;
use IvanFuhr\LaravelEnvironments\Services\SchedulerService;
use IvanFuhr\LaravelEnvironments\Services\SeleniumService;
use IvanFuhr\LaravelEnvironments\Services\SoketiService;
use IvanFuhr\LaravelEnvironments\Tests\TestCase;
use Symfony\Component\Yaml\Yaml;

final class ComposeBuilderTest extends TestCase
{
    public function test_it_builds_development_compose_with_expected_services(): void
    {
        $compose = $this->builder()->build('development');

        $this->assertSame('laravel', $compose['name']);
        $this->assertArrayHasKey('app', $compose['services']);
        $this->assertArrayHasKey('nginx', $compose['services']);
        $this->assertArrayHasKey('mysql', $compose['services']);
        $this->assertArrayHasKey('redis', $compose['services']);
        $this->assertArrayHasKey('mailpit', $compose['services']);
        $this->assertArrayHasKey('environments-mysql', $compose['volumes']);
        $this->assertArrayHasKey('environments-redis', $compose['volumes']);
        $this->assertSame('bridge', $compose['networks']['environments']['driver']);
        $this->assertArrayHasKey('mysql', $compose['services']['app']['depends_on']);
        $this->assertTrue($compose['services']['app']['environment']['XDEBUG_MODE'] === 'develop,debug');
        $this->assertContains('.:/var/www/html', $compose['services']['app']['volumes']);
    }

    public function test_it_builds_production_compose_with_scaling_and_workers(): void
    {
        $compose = $this->builder()->build('production');

        $this->assertArrayHasKey('queue', $compose['services']);
        $this->assertArrayHasKey('scheduler', $compose['services']);
        $this->assertSame(2, $compose['services']['app']['deploy']['replicas']);
        $this->assertSame(2, $compose['services']['queue']['deploy']['replicas']);
        $this->assertSame('off', $compose['services']['app']['environment']['XDEBUG_MODE']);
        $this->assertArrayNotHasKey('volumes', $compose['services']['app']);
        $this->assertTrue($compose['services']['nginx']['ports'][1] === '${APP_SSL_PORT:-443}:443');
        $this->assertSame('unless-stopped', $compose['services']['app']['restart']);
    }

    public function test_it_dumps_valid_yaml(): void
    {
        $yaml = $this->builder()->toYaml('development');
        $parsed = Yaml::parse($yaml);

        $this->assertIsArray($parsed);
        $this->assertArrayHasKey('services', $parsed);
        $this->assertArrayHasKey('mysql', $parsed['services']);
    }

    public function test_it_writes_compose_file_to_disk(): void
    {
        $path = sys_get_temp_dir().'/env-compose-'.uniqid('', true).'.yml';
        $written = $this->builder()->write('development', $path);

        $this->assertSame($path, $written);
        $this->assertFileExists($path);
        $this->assertStringContainsString('mysql:', (string) file_get_contents($path));
        unlink($path);
    }

    public function test_it_supports_custom_registered_services(): void
    {
        $registry = $this->app->make(ServiceRegistry::class);
        $registry->register(new CallableService(
            'typesense',
            fn (array $context): array => [
                'image' => 'typesense/typesense:latest',
                'networks' => [$context['network']],
            ],
            fn (): array => ['environments-typesense' => ['driver' => 'local']],
        ));

        config()->set('environments.profiles.development.services', ['app', 'typesense']);

        $builder = new ComposeBuilder($registry, config('environments'));
        $compose = $builder->build('development');

        $this->assertArrayHasKey('typesense', $compose['services']);
        $this->assertSame('typesense/typesense:latest', $compose['services']['typesense']['image']);
        $this->assertArrayHasKey('environments-typesense', $compose['volumes']);
    }

    public function test_it_throws_for_unknown_profile(): void
    {
        $this->expectException(UnknownProfileException::class);
        $this->builder()->build('staging');
    }

    public function test_it_throws_for_unknown_service(): void
    {
        config()->set('environments.profiles.development.services', ['app', 'does-not-exist']);

        $this->expectException(UnknownServiceException::class);
        $this->builder()->build('development');
    }

    public function test_it_throws_when_profile_has_no_services(): void
    {
        config()->set('environments.profiles.empty', ['services' => []]);

        $this->expectException(InvalidConfigurationException::class);
        $this->builder()->build('empty');
    }

    public function test_it_omits_volumes_section_when_none_are_defined(): void
    {
        config()->set('environments.profiles.slim', [
            'services' => ['mailpit'],
        ]);

        $compose = $this->builder()->build('slim');

        $this->assertArrayNotHasKey('volumes', $compose);
        $this->assertArrayHasKey('mailpit', $compose['services']);
    }

    public function test_built_in_services_emit_definitions(): void
    {
        $services = [
            new AppService,
            new NginxService,
            new CaddyService,
            new MysqlService,
            new PgsqlService,
            new RedisService,
            new MeilisearchService,
            new MailpitService,
            new MinioService,
            new SoketiService,
            new SeleniumService,
            new QueueService,
            new SchedulerService,
            new HorizonService,
            new OctaneService,
        ];

        $context = [
            'profile' => 'production',
            'network' => 'environments',
            'php_version' => '8.4',
            'options' => [
                'ssl' => true,
                'replicas' => 2,
                'xdebug' => false,
                'mount_source' => false,
            ],
        ];

        foreach ($services as $service) {
            $definition = $service->definition($context);
            $this->assertNotSame('', $service->name());
            $this->assertIsArray($definition);
            $this->assertArrayHasKey('networks', $definition);
            $this->assertIsArray($service->volumes($context));
        }
    }

    public function test_callable_service_without_volumes_callback_returns_empty_volumes(): void
    {
        $service = new CallableService('custom', fn (): array => ['image' => 'busybox']);

        $this->assertSame('custom', $service->name());
        $this->assertSame(['image' => 'busybox'], $service->definition([]));
        $this->assertSame([], $service->volumes([]));
    }

    public function test_registry_lists_registered_services(): void
    {
        $registry = $this->app->make(ServiceRegistry::class);

        $this->assertTrue($registry->has('mysql'));
        $this->assertContains('redis', $registry->names());
        $this->assertArrayHasKey('app', $registry->all());
        $this->assertSame('mysql', $registry->get('mysql')->name());
    }

    private function builder(): ComposeBuilder
    {
        return new ComposeBuilder(
            $this->app->make(ServiceRegistry::class),
            config('environments'),
        );
    }
}
