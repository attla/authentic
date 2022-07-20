<?php

namespace Attla\Authentic;

use Illuminate\Support\Facades\Facade as BaseFacade;

/**
 * @method static \Illuminate\Contracts\Auth\Authenticatable loginUsingId(mixed $id, bool $remember = false)
 * @method static \Illuminate\Contracts\Auth\Authenticatable|null user()
 * @method static \Illuminate\Contracts\Auth\Authenticatable authenticate()
 * @method static bool attempt(array $credentials = [], bool $remember = false)
 * @method static bool hasUser()
 * @method static bool check()
 * @method static bool guest()
 * @method static bool once(array $credentials = [])
 * @method static bool onceUsingId(mixed $id)
 * @method static bool validate(array $credentials = [])
 * @method static int|string|null id()
 * @method static void login(\Illuminate\Contracts\Auth\Authenticatable $user, bool $remember = false)
 * @method static void logout()
 * @method static void setUser(\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static void clearUserDataFromStorage()
 * @method static void attempting(mixed $callback)
 * @method static string getName()
 * @method static self setRememberDuration(int $minutes)
 *
 * @see \Attla\Authentic\Guard
 */
class Facade extends BaseFacade
{
    /**
     * Get the registered name of the component
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return static::$app['auth']->guard('authentic');
    }
}
