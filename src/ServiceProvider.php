<?php

namespace Attla\Authentic;

use Attla\Authentic\Middlewares\AllowServer;
use Attla\Support\Envir;
use Attla\Authentic\Middlewares\Authorized;
use Attla\Authentic\Middlewares\MultipleAuth;
use Attla\Authentic\Middlewares\Unauthorized;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Str;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Package name
     *
     * @var string
     */
    private const NAME = 'authentic';

    /**
     * The middlewares
     *
     * @var array
     */
    protected $middlewares = [
        MultipleAuth::class,
    ];

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
        $this->mergeConfigFrom($this->configPath(), static::NAME);

        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
    }

    /** {@inheritdoc} */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->configPath() => $this->app->configPath(static::NAME . '.php'),
            ], 'config');

            return;
        }

        $this->registerMiddlewares();
        $this->registerRoutes();

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
                ->setRememberDuration(Envir::getConfig(
                    static::NAME. '.flow.remember',
                    $config['remember'] ?? 0) * 60
                );
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
     * Register middlewares
     *
     * @return void
     */
    protected function registerMiddlewares()
    {
        $this->aliasMiddleware();
        $kernel = $this->app->make(Kernel::class);

        foreach ($this->middlewares as $middleware) {
            $kernel->pushMiddleware($middleware);
        }
    }

    /**
     * Register flow routes
     *
     * @return void
     */
    protected function registerRoutes()
    {
        $router = $this->app['router'];
        $prefix = static::NAME. '.flow.';
        $router->group(Envir::getConfig($prefix. 'route-group', [
            'as'         => 'authentic.',
            'namespace'  => 'Attla\\Authentic\\Controllers',
            'controller' => 'FlowController',
        ]), function () use ($router, $prefix) {
            $route = Envir::getConfig($prefix. 'route') ?: 'sign';
            $middlewares = array_merge([AllowServer::class], Envir::getConfig($prefix. 'middlewares') ?: []);

            $this->registerRoute(
                $router,
                $route,
                $route,
                $middlewares,
                $route,
                'post'
            );
        });

    }

    /**
     * Register route
     *
     * @param \Illuminate\Contracts\Routing\Registrar $router
     * @param string $name
     * @param string $path
     * @param array $middlewares
     * @param string $action
     * @param string $method
     *
     * @return void
     */
    protected function registerRoute(
        $router,
        $name,
        $path,
        array $middlewares = [],
        $action = null,
        string $method = 'get'
    ) {
        $router->{$method}('/' . trim(trim($path), '/?'), [
            'uses' => trim(Str::camel($action ?: $name), '/?=_-'),
            'as' => trim($name, '/?=_-'),
        ])->middleware($middlewares[$name] ?? []);
    }

    /**
     * Get config path
     *
     * @return string
     */
    protected function configPath()
    {
        return __DIR__ . '/../config/' . static::NAME . '.php';
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
