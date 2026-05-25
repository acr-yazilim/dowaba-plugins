<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Tests;

use Dowaba\LaravelBridge\DowabaBridgeServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            DowabaBridgeServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('dowaba.url', 'https://dowaba.test');
        $app['config']->set('dowaba.client_id', 'dosc_test_client');
        $app['config']->set('dowaba.client_secret', 'dosec_test_secret');
        $app['config']->set('dowaba.redirect_uri', 'https://app.test/dowaba/auth/callback');
        $app['config']->set('dowaba.scopes', 'openid profile email');
        $app['config']->set('dowaba.widget.site_id', 42);
        $app['config']->set('dowaba.widget.secret', 'test-widget-secret-very-long-string');
    }
}
