<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge;

use Dowaba\LaravelBridge\Console\InstallCommand;
use Dowaba\LaravelBridge\Console\RotateClientSecretCommand;
use Dowaba\LaravelBridge\Console\TestConnectionCommand;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class DowabaBridgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/dowaba.php', 'dowaba');

        $this->app->singleton(DowabaManager::class, fn ($app) => new DowabaManager($app));
        $this->app->alias(DowabaManager::class, 'dowaba');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'dowaba');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->registerBladeComponents();
        $this->registerPublishables();
        $this->registerConsoleCommands();
    }

    private function registerBladeComponents(): void
    {
        Blade::componentNamespace('Dowaba\\LaravelBridge\\Blade', 'dowaba');
    }

    private function registerPublishables(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/dowaba.php' => config_path('dowaba.php'),
        ], 'dowaba-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'dowaba-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/dowaba'),
        ], 'dowaba-views');

        $this->publishes([
            __DIR__.'/../resources/assets/dowaba-bridge.js' => public_path('vendor/dowaba/dowaba-bridge.js'),
        ], 'dowaba-assets');
    }

    private function registerConsoleCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallCommand::class,
            TestConnectionCommand::class,
            RotateClientSecretCommand::class,
        ]);
    }
}
