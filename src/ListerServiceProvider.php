<?php

namespace TsfCorp\Lister;

use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class ListerServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([__DIR__ . '/config/lister.php' => config_path('lister.php')]);
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('lister', function ($app) {
            return new Lister($app->make(Request::class), $app->make(Connection::class));
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'lister',
        ];
    }
}