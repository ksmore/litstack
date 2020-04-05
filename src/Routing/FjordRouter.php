<?php

namespace Fjord\Routing;

use Form;
use ReflectionClass;
use ReflectionMethod;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;
use Fjord\Support\Facades\Package;

class FjordRouter
{
    protected $middlewares = [
        'web',
        'fjord.auth:fjord'
    ];

    /**
     * Initialize defaults for a Fjord route.
     * Fjord Routes should always be created with
     * \Fjord\Support\Facades\FjordRoute.
     *
     * @return Illuminate\Support\Facades\Route $route
     */
    public function __call($method, $parameters)
    {
        $route = Route::prefix(config('fjord.route_prefix'))
            ->as('fjord.')
            ->middleware($this->middlewares);

        return $route->$method(...$parameters);
    }

    /**
     * Initialize defaults for a Fjord package route.
     * Routes for a Fjord package should always be created 
     * with \Fjord\Support\Facades\FjordRoute@package
     * 
     * @param string $package
     * @return Illuminate\Support\Facades\Route $route
     */
    public function package($package)
    {
        $package = Package::get($package);

        return Route::prefix($package->getRoutePrefix())
            ->as($package->getRouteAs())
            ->middleware($this->middlewares);
    }

    /**
     * Public route using Fjord route prefix.
     * 
     * @return Illuminate\Support\Facades\Route $route
     */
    public function public()
    {
        return Route::prefix(config('fjord.route_prefix'))
            ->as('fjord.')
            ->middleware('web');
    }

    /**
     * Register crud action routes.
     *
     * @param  string $crud
     * @param  string $namespace
     *
     * @return
     */
    public function extensionRoutes(string $class)
    {
        $reflection = new ReflectionClass($class);

        foreach($reflection->getMethods() as $method) {
            if(! Str::startsWith($method->name, 'make') && ! Str::endsWith($method->name, 'Route')) {
                continue;
            }

            $instance = with(new $class());
            call_user_func_array([$instance, $method->name], []);
        }
    }
}
