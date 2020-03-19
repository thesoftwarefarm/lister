<?php

namespace TsfCorp\Lister;

use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use TsfCorp\Lister\Facades\ListerFilter;

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
        $this->loadViewsFrom(__DIR__ . '/../views', 'lister');

        $this->publishes([
            __DIR__ . '/../views' => resource_path('views/vendor/lister'),
        ], 'views');

        $this->publishes([__DIR__ . '/config/lister.php' => config_path('lister.php')]);
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Lister::class, function ($app) {
            return new Lister($app->make(Request::class), $app->make(Connection::class));
        });

        $this->app->singleton(ListerFilterFactory::class, function () {
            return new ListerFilterFactory();
        });

        $this->app->alias(Lister::class, 'lister');
        $this->app->alias(ListerFilterFactory::class, 'listerfilter');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'lister', 'listerfilter'
        ];
    }
}