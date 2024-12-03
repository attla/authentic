<?php

namespace Attla\Authentic;

use Attla\Authentic\Middlewares\Authorized;
use Attla\Authentic\Middlewares\Unauthorized;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * The middleware aliases
     *
     * @var array
     */
    protected $middlewareAliases = [
        'authz'        => Authorized::class,
        'authorized'   => Authorized::class,
        'unauthorized' => Unauthorized::class,
    ];

    /**
     * List of registrable commands
     *
     * @var array
     */
    protected $commands = [
        Commands\CacheAbilities::class,
    ];

    /** {@inheritdoc} */
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
    }

    /** {@inheritdoc} */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $this->aliasMiddleware();

        $this->registerDynamodbProvider();
        $this->registerGate();
        $this->extendAuthGuard();
    }

    /**
     * Extend laravel auth
     *
     * @return void
     */
    protected function extendAuthGuard()
    {
        $this->app['auth']->extend('stateless', function ($app, $name, array $config) {
            $guard = new AuthnGuard(
                $name,
                $app['auth']->createUserProvider($config['provider'] ?? null),
                $app['request']
            );
            $app->refresh('request', $guard, 'setRequest');

            return $guard->setDispatcher($app['events'])
                ->setRememberDuration($config['remember'] ?? 0);
        });
    }

    /**
     * Register auth providers
     *
     * @return void
     */
    protected function registerDynamodbProvider()
    {
        $this->app['auth']->provider('dynamodb', function ($app, array $config) {
            return new AuthnProvider(
                $app['hash'],
                $config['model'],
                $config['gsi'] ?? null,
            );
        });
    }

    /**
     * Register permissions gate
     *
     * @return void
     */
    protected function registerGate()
    {
        $this->app->singleton(GateContract::class, function ($app) {
            return new Gate($app['auth']->userResolver());
        });
    }

    /**
     * Alias the middleware
     *
     * @return void
     */
    protected function aliasMiddleware()
    {
        $router = $this->app['router'];
        $method = method_exists($router, 'aliasMiddleware') ? 'aliasMiddleware' : 'middleware';

        foreach ($this->middlewareAliases as $alias => $middleware) {
            $router->$method($alias, $middleware);
        }
    }

    /**
     * Bind some aliases
     *
     * @return void
     */
    protected function registerAliases()
    {
        // $this->app->alias('authentic', Guard::class);
    }
}
