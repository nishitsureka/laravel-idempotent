<?php
namespace Nishit\LaravelIdempotent;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Nishit\LaravelIdempotent\Middleware\Idempotent;

class LaravelIdempotentServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/idempotent.php', 'idempotent');
    }

    public function boot(Router $router)
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/idempotent.php' => config_path('idempotent.php'),
        ], 'config');
        
        $router->aliasMiddleware('idempotent', Idempotent::class);
    }
}