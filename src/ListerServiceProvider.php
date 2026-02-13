<?php

namespace TsfCorp\Lister;

use Illuminate\Support\ServiceProvider;

class ListerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/views', 'lister');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/lister.php' => config_path('lister.php')
            ], 'lister-config');

            $this->publishes([
                __DIR__ . '/views' => resource_path('views/vendor/lister'),
            ], 'lister-views');
        }
    }
}
