<?php

namespace Attla\Authentic;

use Attla\{
    Support\Arr as AttlaArr,
    Cookier\Facade as Cookier,
    DataToken\Facade as DataToken
};
use Illuminate\Auth\{
    EloquentUserProvider,
    GuardHelpers,
};
use Illuminate\Auth\Events\{
    Attempting,
    Authenticated,
    CurrentDeviceLogout,
    Failed,
    Login,
    Logout,
    OtherDeviceLogout,
    Validated,
};
use Illuminate\Contracts\{
    Auth\Authenticatable,
    Auth\StatefulGuard,
    Auth\UserProvider,
    Events\Dispatcher,
    Support\Arrayable,
    Support\Jsonable,
};
use Illuminate\Support\{
    Arr,
    Enumerable,
    Traits\Macroable,
};
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Guard implements StatefulGuard
{
    use GuardHelpers;
    use Macroable;

    /**
     * The name of the Guard
     *
     * Corresponds to guard name in authentication configuration
     *
     * @var string
     */
    protected $name;

    /**
     * The user we last attempted to retrieve
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    protected $lastAttempted;

    /**
     * Indicates if the user was authenticated via a recaller cookie
     *
     * @var bool
     */
    protected $viaRemember = false;

    /**
     * The number of minutes that the "remember me" cookie should be valid for
     *
     * @var int
     */
    protected $rememberDuration = 2628000;

    /**
     * The request instance
     *
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * The event dispatcher instance
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Create a new authentication guard
     *
     * @param string $name
     * @param \Illuminate\Contracts\Auth\UserProvider $provider
     * @return void
     */
    public function __construct($name, UserProvider $provider)
    {
        $this->name = $name;
        $this->provider = $provider;
    }

    /**
     * Get the currently authenticated user
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if (!is_null($this->user)) {
            return $this->user;
        }

        $user = null;

        if (
            is_object(
                $data = Cookier::get($this->getName())
                    ?: DataToken::decode($this->getRequest()?->bearerToken())
            )
        ) {
            $user = $this->createModel($data);
            $user->exists = true;
        }

        return $this->user = $user;
    }

    /**
     * Create a new instance of the model
     *
     * @param object $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function createModel(object $attributes)
    {
        $class = '\\' . ltrim($this->provider->getModel(), '\\');

        return new $class(AttlaArr::toArray($attributes));
    }

    /**
     * Get the currently authenticated user or throws an exception
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function userOrFail()
    {
        if (!$user = $this->user()) {
            throw new UnauthorizedHttpException();
        }

        return $user;
    }

    /**
     * Get the ID for the currently authenticated user
     *
     * @return int|null
     */
    public function id()
    {
        return $this->user()?->getAuthIdentifier();
    }

    /**
     * Log a user into the application without sessions or cookies
     *
     * @param array $credentials
     * @return bool
     */
    public function once(array $credentials = [])
    {
        $this->fireAttemptEvent($credentials);

        if ($this->validate($credentials)) {
            $this->setUser($this->lastAttempted);

            return true;
        }

        $this->fireFailedEvent($this->lastAttempted, $credentials);
        return false;
    }

    /**
     * Log the given user ID into the application without sessions or cookies
     *
     * @param mixed $id
     * @return \Illuminate\Contracts\Auth\Authenticatable|false
     */
    public function onceUsingId($id)
    {
        if (!is_null($user = $this->provider->retrieveById($id))) {
            $this->setUser($user);
            return $user;
        }

        return false;
    }

    /**
     * Validate a user's credentials
     *
     * @param array $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

        return $this->hasValidCredentials($user, $credentials);
    }

    /**
     * Attempt to authenticate a user using the given credentials
     *
     * @param array $credentials
     * @param bool $remember
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false)
    {
        $this->fireAttemptEvent($credentials);

        if ($this->validate($credentials)) {
            $this->login($this->lastAttempted);

            return true;
        }

        $this->fireFailedEvent($this->lastAttempted, $credentials);

        return false;
    }

    /**
     * Attempt to authenticate a user with credentials and additional callbacks
     *
     * @param array $credentials
     * @param array|callable $callbacks
     * @param bool $remember
     * @return bool
     */
    public function attemptWhen(array $credentials = [], $callbacks = null, $remember = false)
    {
        $this->fireAttemptEvent($credentials, $remember);

        if ($this->validate($credentials) && $this->shouldLogin($callbacks, $this->lastAttempted)) {
            $this->login($this->lastAttempted, $remember);

            return true;
        }

        $this->fireFailedEvent($this->lastAttempted, $credentials);

        return false;
    }

    /**
     * Determine if the user matches the credentials
     *
     * @param mixed $user
     * @param array $credentials
     * @return bool
     */
    protected function hasValidCredentials($user, $credentials)
    {
        $validated = !is_null($user) && $this->provider->validateCredentials($user, $credentials);

        if ($validated) {
            $this->fireValidatedEvent($user);
        }

        return $validated;
    }

    /**
     * Determine if the user should login by executing the given callbacks
     *
     * @param array|callable|null $callbacks
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return bool
     */
    protected function shouldLogin($callbacks, Authenticatable $user)
    {
        foreach (Arr::wrap($callbacks) as $callback) {
            if (!$callback($user, $this)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Log the given user ID into the application
     *
     * @param mixed $id
     * @param bool $remember
     * @return \Illuminate\Contracts\Auth\Authenticatable|false
     */
    public function loginUsingId($id, $remember = false)
    {
        if (!is_null($user = $this->provider->retrieveById($id))) {
            $this->login($user, $remember);
            return $user;
        }

        return false;
    }

    /**
     * Log a user into the application
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param bool $remember
     * @return void
     */
    public function login(Authenticatable $user, $remember = false)
    {
        $this->storeUser($user, $remember);
        $this->fireLoginEvent($user, $remember);
        $this->setUser($user);
    }

    /**
     * Store the user on cookie storage
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param bool $remember
     * @return void
     */
    protected function storeUser(Authenticatable $user, $remember = false)
    {
        Cookier::set(
            $this->getName(),
            DataToken::payload($user)
                ->sign($expiration = $remember ? $this->rememberDuration : 30)
                ->encode(),
            $expiration
        );
    }


    /**
     * Log the user out of the application
     *
     * @return void
     */
    public function logout()
    {
        if ($user = $this->user()) {
            $this->clearUserDataFromStorage();
            $this->fireLogoutEvent($user);
            $this->user = null;
        }
    }

    /**
     * Remove the user data from the cookies
     *
     * @return void
     */
    public function clearUserDataFromStorage()
    {
        Cookier::forget($this->getName());
    }

    /**
     * Register an authentication attempt event listener
     *
     * @param mixed $callback
     * @return void
     */
    public function attempting($callback)
    {
        $this->events?->listen(Attempting::class, $callback);
    }

    /**
     * Fire the attempt event with the arguments
     *
     * @param array $credentials
     * @param bool $remember
     * @return void
     */
    protected function fireAttemptEvent(array $credentials, $remember = false)
    {
        $this->events?->dispatch(new Attempting($this->name, $credentials, $remember));
    }

    /**
     * Fires the validated event if the dispatcher is set
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return void
     */
    protected function fireValidatedEvent($user)
    {
        $this->events?->dispatch(new Validated($this->name, $user));
    }

    /**
     * Fire the login event if the dispatcher is set
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param bool $remember
     * @return void
     */
    protected function fireLoginEvent($user, $remember = false)
    {
        $this->events?->dispatch(new Login($this->name, $user, $remember));
    }

    /**
     * Fire the authenticated event if the dispatcher is set
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return void
     */
    protected function fireAuthenticatedEvent($user)
    {
        $this->events?->dispatch(new Authenticated($this->name, $user));
    }

    /**
     * Fire the logout event if the dispatcher is set
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return void
     */
    protected function fireLogoutEvent($user)
    {
        $this->events?->dispatch(new Logout($this->name, $user));
    }

    /**
     * Fire the other device logout event if the dispatcher is set
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return void
     */
    protected function fireOtherDeviceLogoutEvent($user)
    {
        $this->events?->dispatch(new OtherDeviceLogout($this->name, $user));
    }

    /**
     * Fire the failed authentication attempt event with the given arguments
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable|null $user
     * @param array $credentials
     * @return void
     */
    protected function fireFailedEvent($user, array $credentials)
    {
        $this->events?->dispatch(new Failed($this->name, $user, $credentials));
    }

    /**
     * Get the last user we attempted to authenticate
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public function getLastAttempted()
    {
        return $this->lastAttempted;
    }

    /**
     * Get a identifier of the auth
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the number of minutes the remember me cookie should be valid for
     *
     * @param int $minutes
     * @return $this
     */
    public function setRememberDuration(int $minutes)
    {
        $this->rememberDuration = $minutes;
        return $this;
    }

    /**
     * Get the event dispatcher instance
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance
     *
     * @param \Illuminate\Contracts\Events\Dispatcher $events
     * @return void
     */
    public function setDispatcher(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Set the current user
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return $this
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
        $this->fireAuthenticatedEvent($user);
        return $this;
    }

    /**
     * Get the current request instance
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->request ?: Request::createFromGlobals();
    }

    /**
     * Set the current request instance
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Set the user provider used by the guard
     *
     * @param \Illuminate\Contracts\Auth\UserProvider $provider
     * @return void
     */
    public function setProvider(UserProvider $provider)
    {
        if (!$provider instanceof EloquentUserProvider) {
            throw new \InvalidArgumentException(
                'Authentication user provider is not accepted. '
                . 'Authentic only accepts EloquentUserProvider instance.'
            );
        }

        $this->provider = $provider;
    }

    /**
     * Determine if the user was authenticated via "remember me" cookie
     *
     * @return bool
     */
    public function viaRemember()
    {
    }
}
