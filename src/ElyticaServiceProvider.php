<?php

declare(strict_types=1);

namespace Elytica\Socialite;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory;

class ElyticaServiceProvider extends ServiceProvider
{
    use PublishesMigrations;

    public function boot(): void
    {
        $this->app->booted(function () {
            $this->extendSocialite();
        });
        $this->registerMigrations(__DIR__ . '/../database/migrations');
        $this->publishes([
            __DIR__ . '/../config/elytica.php' => config_path('elytica.php'),
        ], 'config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/elytica.php', 'services'
        );
    }

    protected function extendSocialite(): void
    {
        $socialite = $this->app->make(Factory::class);
        $socialite->extend('elytica_service', function ($app) use ($socialite) {
            $config = $app['config']['services.elytica_service'];
            $provider = $socialite->buildProvider(ElyticaProvider::class, $config);
            $provider->setBaseUrl($config['base_url'] ?? 'https://service.elytica.com');

            return $provider;
        });
    }
}
