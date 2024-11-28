<?php

namespace Attla\Authentic;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * The middleware aliases
     *
     * @var array
     */
    protected $middlewareAliases = [
        // 'authn' => AuthnMiddleware::class,
        // 'authz' => AuthzMiddleware::class,
    ];

    /** {@inheritdoc} */
    public function register()
    {
    }

    /** {@inheritdoc} */
    public function boot()
    {
        // $path = realpath(__DIR__.'/../../config/config.php');

        // $this->publishes([$path => config_path('authentic.php')], 'config');
        // $this->mergeConfigFrom($path, 'authentic');

        // $this->aliasMiddleware();

        $this->registerDynamodbProvider();
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
