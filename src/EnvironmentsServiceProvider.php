<?php

declare(strict_types=1);

namespace IvanFuhr\LaravelEnvironments;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;
use IvanFuhr\LaravelEnvironments\Commands\DownCommand;
use IvanFuhr\LaravelEnvironments\Commands\GenerateCommand;
use IvanFuhr\LaravelEnvironments\Commands\InstallCommand;
use IvanFuhr\LaravelEnvironments\Commands\PublishCommand;
use IvanFuhr\LaravelEnvironments\Commands\UpCommand;
use IvanFuhr\LaravelEnvironments\Compose\ComposeBuilder;
use IvanFuhr\LaravelEnvironments\Contracts\EnvironmentService;
use IvanFuhr\LaravelEnvironments\Services\AppService;
use IvanFuhr\LaravelEnvironments\Services\CaddyService;
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
use IvanFuhr\LaravelEnvironments\Support\DockerComposeRunner;
use IvanFuhr\LaravelEnvironments\Support\PackageMetadata;

final class EnvironmentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/environments.php', 'environments');

        $this->app->singleton(ServiceRegistry::class, function (): ServiceRegistry {
            $registry = new ServiceRegistry;

            foreach ($this->defaultServices() as $service) {
                $registry->register($service);
            }

            return $registry;
        });

        $this->app->singleton(ComposeBuilder::class, function (Application $app): ComposeBuilder {
            $repository = $app->make('config');
            $config = [];

            if (is_object($repository) && method_exists($repository, 'get')) {
                $resolved = $repository->get('environments', []);
                $config = is_array($resolved) ? $resolved : [];
            }

            /** @var array<string, mixed> $config */
            return new ComposeBuilder($app->make(ServiceRegistry::class), $config);
        });

        $this->app->singleton(DockerComposeRunner::class);
    }

    public function boot(): void
    {
        AboutCommand::add('Laravel Environments', function (): array {
            $profile = config('environments.default_profile', 'development');
            $profile = is_string($profile) && $profile !== '' ? $profile : 'development';

            return [
                'Version' => PackageMetadata::version(),
                'Profile' => $profile,
                'Services' => implode(', ', $this->app->make(ServiceRegistry::class)->names()),
            ];
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                GenerateCommand::class,
                PublishCommand::class,
                UpCommand::class,
                DownCommand::class,
            ]);

            // Tagged publish groups per https://laravel.com/docs/packages#publishing-file-groups
            $this->publishes([
                __DIR__.'/../config/environments.php' => config_path('environments.php'),
            ], 'environments-config');

            $this->publishes([
                __DIR__.'/../stubs/docker' => base_path('docker'),
            ], 'environments-docker');

            $this->publishes([
                __DIR__.'/../bin/environments' => base_path('environments'),
            ], 'environments-bin');
        }
    }

    /**
     * @return list<EnvironmentService>
     */
    private function defaultServices(): array
    {
        return [
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
    }
}
