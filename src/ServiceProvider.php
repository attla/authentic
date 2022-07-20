<?php

namespace Attla\Authentic;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register the service provider
     *
     * @return void
     */
    public function register()
    {
        $this->app['auth']->extend('authentic', function ($app, $name, $config) {
            $provider = $app['auth']->createUserProvider($config['provider'] ?? null);

            if (!$provider instanceof EloquentUserProvider) {
                throw new \InvalidArgumentException(
                    'Authentication user provider is not accepted. '
                    . 'Authentic only accepts EloquentUserProvider instance.'
                );
            }

            $guard = new Guard(
                $name,
                $provider
            );

            $guard->setDispatcher($app['events']);
            $guard->setRequest($app->refresh('request', $guard, 'setRequest'));

            if (isset($config['remember'])) {
                $guard->setRememberDuration($config['remember']);
            }

            return $guard;
        });
    }
}
