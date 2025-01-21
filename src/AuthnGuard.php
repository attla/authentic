<?php

namespace Attla\Authentic;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Validation\UnauthorizedException;

class AuthnGuard implements Contracts\StatelessGuard
{
    use GuardHelpers;
    use Macroable;
    use Traits\HasAuthEvents;

    /**
     * The name of the Guard
     *
     * Corresponds to guard name in authentication configuration
     *
     * @var string
     */
    protected $name;

    /**
     * The user last attempted to retrieve
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
     * The number of secounds that the "remember me" cookie should be valid for
     *
     * @var int
     */
    protected $rememberDuration = 7200;

    /**
     * The request instance
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Create a new authentication guard
     *
     * @param string $name
     * @param \Illuminate\Contracts\Auth\UserProvider $provider
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct($name, UserProvider $provider, Request $request)
    {
        $this->name = $name;
        $this->setProvider($provider)->setRequest($request);
    }

    /**
     * Get the currently authenticated user
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if (!empty($this->user)) {
            return $this->user;
        }

        $token = Token::parse(Token::fromRequest());

        return $this->user = $token->isValid() ? $this->provider->createModel($token->get()) : null;
    }

    /**
     * Get the currently authenticated user or throws an exception
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable
     *
     * @throws \Illuminate\Validation\UnauthorizedException
     */
    public function userOrFail()
    {
        if (!$user = $this->user()) {
            throw new UnauthorizedException();
        }

        return $user;
    }

    /**
     * Get the ID for the currently authenticated user
     *
     * @return int|string|null
     */
    public function id()
    {
        return $this->user()?->getAuthIdentifier();
    }

    /**
     * Log a user into the application without sessions or cookies
     *
     * @param array|object $credentials
     * @return bool
     */
    public function once(array|object $credentials = [])
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
        if (!empty($user = $this->provider->retrieveById($id))) {
            $this->setUser($user);
            return $user;
        }

        return false;
    }

    /**
     * Validate a user's credentials
     *
     * @param array|object $credentials
     * @return bool
     */
    public function validate(array|object $credentials = [])
    {
        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

        return $this->hasValidCredentials($user, $credentials);
    }

    /**
     * Attempt to authenticate a user using the given credentials
     *
     * @param array|object $credentials
     * @param bool $remember
     * @return StatelessToken|false
     */
    public function attempt(array|object $credentials = [], $remember = false)
    {
        $this->fireAttemptEvent($credentials);
        if ($this->validate($credentials)) {
            return $this->login($this->lastAttempted);
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
        if ($validated = !empty($user) && $this->provider->validateCredentials($user, $credentials)) {
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
     * @return StatelessToken|false
     */
    public function loginUsingId($id, $remember = false)
    {
        if (!empty($user = $this->provider->retrieveById($id))) {
            return $this->login($user, $remember);
        }

        return false;
    }

    /**
     * Log a user into the application
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param bool $remember
     * @return StatelessToken|false
     */
    public function login(Authenticatable $user, $remember = false)
    {
        $this->fireLoginEvent($user, $remember);
        $this->setUser($user);

        return StatelessToken::fromUser($user, $this->rememberDuration);
    }

    /**
     * Log the user out of the application
     *
     * @return void
     */
    public function logout()
    {
        if ($user = $this->user()) {
            // TODO: revoke token...
            $this->fireLogoutEvent($user);
            $this->user = null;
        }
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
        $minutes > 0 && $this->rememberDuration = $minutes;
        return $this;
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
     * Set the current request instance
     *
     * @param \Illuminate\Http\Request $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        Token::setRequest($request);
        return $this;
    }

    /**
     * Set the user provider used by the guard
     *
     * @param \Illuminate\Contracts\Auth\UserProvider $provider
     * @return $this
     */
    public function setProvider(UserProvider $provider)
    {
        $this->provider = $provider;
        return $this;
    }

    /**
     * Determine if the user was authenticated via "remember me" cookie
     *
     * @return bool
     */
    public function viaRemember()
    {
        return false;
    }
}
