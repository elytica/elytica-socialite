<?php

namespace Elytica\Socialite;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Contracts\Factory;

class ElyticaServiceProvider extends ServiceProvider
{
    use PublishesMigrations;
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->booted(function () {
            $this->extendSocialite();
        });
        $this->registerMigrations(__DIR__ . '/../database/migrations');
        $this->publishes([
            __DIR__ . '/../config/elytica.php' => config_path('elytica.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__ . '/../config/elytica.php', 'services'
        );
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Extend Socialite with the Elytica Service Provider.
     */
    protected function extendSocialite(): void
    {
        $socialite = $this->app->make(Factory::class);
        $socialite->extend('elytica_service', function ($app) use ($socialite) {
            $config = $app['config']['services.elytica_service'];
            return $socialite->buildProvider(ElyticaProvider::class, $config);
        });
    }
}

