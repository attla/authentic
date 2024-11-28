<?php

namespace Attla\Authentic\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;

interface StatelessGuard extends Guard
{
    /**
     * Attempt to authenticate a user using the given credentials
     *
     * @param array|object $credentials
     * @param bool $remember
     * @return StatelessToken|false
     */
    public function attempt(array|object $credentials = [], $remember = false);

    /**
     * Log a user into the application
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param bool $remember
     * @return StatelessToken|false
     */
    public function login(Authenticatable $user, $remember = false);

    /**
     * Log the given user ID into the application
     *
     * @param mixed $id
     * @param bool $remember
     * @return StatelessToken|false
     */
    public function loginUsingId($id, $remember = false);

    /**
     * Log a user into the application without sessions or cookies
     *
     * @param array|object $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|false
     */
    public function once(array|object $credentials = []);

    /**
     * Log the given user ID into the application without sessions or cookies
     *
     * @param mixed $id
     * @return \Illuminate\Contracts\Auth\Authenticatable|false
     */
    public function onceUsingId($id);

    /**
     * Log the user out of the application
     *
     * @return void
     */
    public function logout();
}
