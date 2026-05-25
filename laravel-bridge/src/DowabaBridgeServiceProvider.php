<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge;

use Dowaba\LaravelBridge\Blade\ChatWindowComponent;
use Dowaba\LaravelBridge\Blade\ContactCreateFormComponent;
use Dowaba\LaravelBridge\Blade\ConversationListComponent;
use Dowaba\LaravelBridge\Blade\LoginButtonComponent;
use Dowaba\LaravelBridge\Blade\WidgetScriptComponent;
use Dowaba\LaravelBridge\Console\InstallCommand;
use Dowaba\LaravelBridge\Console\RotateClientSecretCommand;
use Dowaba\LaravelBridge\Console\TestConnectionCommand;
use Dowaba\LaravelBridge\Http\Middleware\EnsureDowabaConnected;
use Illuminate\Routing\Router;
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
        $this->registerMiddlewareAliases();
        $this->registerPublishables();
        $this->registerConsoleCommands();
    }

    private function registerBladeComponents(): void
    {
        Blade::componentNamespace('Dowaba\\LaravelBridge\\Blade', 'dowaba');

        Blade::component('dowaba::login-button', LoginButtonComponent::class);
        Blade::component('dowaba::widget-script', WidgetScriptComponent::class);
        Blade::component('dowaba::chat-window', ChatWindowComponent::class);
        Blade::component('dowaba::conversation-list', ConversationListComponent::class);
        Blade::component('dowaba::contact-create-form', ContactCreateFormComponent::class);
    }

    private function registerMiddlewareAliases(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('dowaba.connected', EnsureDowabaConnected::class);
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
