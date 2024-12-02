<?php

namespace Attla\Authentic;

use Attla\Support\Invoke;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Collection;

use function Illuminate\Support\enum_value;

class Gate
{
    use HandlesAuthorization;

    /**
     * The user resolver callable
     *
     * @var callable
     */
    protected $userResolver;

    /**
     * The authorization repository
     *
     * @var AuthzRepository
     */
    protected $slot;

    /**
     * The default denial response
     *
     * @var \Illuminate\Auth\Access\Response|null
     */
    protected $defaultDenialResponse;

    /**
     * Create a new gate instance
     *
     * @param callable $userResolver
     * @return void
     */
    public function __construct(
        callable $userResolver
    ) {
        $this->userResolver = $userResolver;
        $this->slot = AuthzRepository::fromUser($this->resolveUser());
    }

    /**
     * Perform an on-demand authorization check
     * Throw an authorization exception if the condition or callback is false
     *
     * @param \Illuminate\Auth\Access\Response|\Closure|bool $condition
     * @param string|null $message
     * @param string|null $code
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function allowIf($condition, $message = null, $code = null)
    {
        return $this->authorizeOnDemand($condition, $message, $code, true);
    }

    /**
     * Perform an on-demand authorization check
     * Throw an authorization exception if the condition or callback is true
     *
     * @param \Illuminate\Auth\Access\Response|\Closure|bool $condition
     * @param string|null $message
     * @param string|null $code
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function denyIf($condition, $message = null, $code = null)
    {
        return $this->authorizeOnDemand($condition, $message, $code, false);
    }

    /**
     * Authorize a given condition or callback
     *
     * @param \Illuminate\Auth\Access\Response|\Closure|bool $condition
     * @param string|null $message
     * @param string|null $code
     * @param bool $allowWhenResponseIs
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function authorizeOnDemand($condition, $message, $code, $allowWhenResponseIs)
    {
        $user = $this->resolveUser();

        $response = false;
        if ($condition instanceof \Closure) {
            try {
                $response = $this->canBeCalledWithUser($user, $condition)
                    ? $condition($user)
                    : new Response(false, $message, $code);
            } catch (\Exception) {}
        } else {
            $response = $condition;
        }

        return with($response instanceof Response ? $response : new Response(
            $response === $allowWhenResponseIs, $message, $code
        ))->authorize();
    }

    /**
     * Determine if all of the given abilities should be granted for the current user
     *
     * @param iterable|\BackedEnum|string $abilities
     * @param array|mixed $arguments
     * @return bool
     */
    public function check($abilities, $arguments = [])
    {
        return (new Collection($abilities))->every(
            fn ($ability) => $this->inspect($ability, $arguments)->allowed()
        );
    }

    /**
     * Determine if all of the given abilities should be granted for the current user
     *
     * @param iterable|\BackedEnum|string $ability
     * @param array|mixed $arguments
     * @return bool
     */
    public function allows($ability, $arguments = [])
    {
        return $this->check($ability, $arguments);
    }

    /**
     * Determine if any of the given abilities should be denied for the current user
     *
     * @param iterable|\BackedEnum|string $ability
     * @param array|mixed $arguments
     * @return bool
     */
    public function denies($ability, $arguments = [])
    {
        return !$this->allows($ability, $arguments);
    }

    /**
     * Determine if any one of the given abilities should be granted for the current user
     *
     * @param iterable|\BackedEnum|string $abilities
     * @param array|mixed $arguments
     * @return bool
     */
    public function any($abilities, $arguments = [])
    {
        return (new Collection($abilities))->contains(
            fn ($ability) => $this->check($ability, $arguments)
        );
    }

    /**
     * Determine if all of the given abilities should be denied for the current user
     *
     * @param iterable|\BackedEnum|string $abilities
     * @param array|mixed $arguments
     * @return bool
     */
    public function none($abilities, $arguments = [])
    {
        return !$this->any($abilities, $arguments);
    }

    /**
     * Determine if the given ability should be granted for the current user
     *
     * @param \BackedEnum|string $ability
     * @param array|mixed $arguments
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize($ability, $arguments = [])
    {
        return $this->inspect($ability, $arguments)->authorize();
    }

    /**
     * Inspect the user for the given ability
     *
     * @param \BackedEnum|string $ability
     * @param array|mixed $arguments
     * @return \Illuminate\Auth\Access\Response
     */
    public function inspect($ability, $arguments = [])
    {
        try {
            $result = $this->raw($ability, $arguments);

            if ($result instanceof Response) {
                return $result;
            }

            return $result
                ? Response::allow()
                : ($this->defaultDenialResponse ?? Response::deny());
        } catch (AuthorizationException $e) {
            return $e->toResponse();
        }
    }

    /**
     * Get the raw result from the authorization callback
     *
     * @param string $ability
     * @param array|mixed $arguments
     * @return bool
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function raw($ability, $arguments = [])
    {
        if ($ability instanceof \Closure) {
            return $this->allowIf($ability);
        }

        return $this->slot->has(enum_value($ability));
    }

    /**
     * Determine whether the callback/method can be called with the given user
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable|null $user
     * @param \Closure|string|array $class
     * @param string|null $method
     * @return bool
     */
    protected function canBeCalledWithUser($user, $class, $method = null)
    {
        if (!is_null($user)) {
            return true;
        }

        if (!is_null($method)) {
            return Invoke::methodAllowsGuests($class, $method);
        }

        if (is_array($class)) {
            $className = is_string($class[0]) ? $class[0] : get_class($class[0]);

            return Invoke::methodAllowsGuests($className, $class[1]);
        }

        return Invoke::callbackAllowsGuests($class);
    }

    /**
     * Get a gate instance for the given user
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable|mixed $user
     * @return static
     */
    public function forUser($user)
    {
        return new static(fn () => $user);
    }

    /**
     * Resolve the user from the user resolver
     *
     * @return mixed
     */
    protected function resolveUser()
    {
        return call_user_func($this->userResolver);
    }

    /**
     * Get all of the defined abilities
     *
     * @return array
     */
    public function abilities()
    {
        return $this->slot->abilities();
    }

    /**
     * Get all of the defined policies
     *
     * @return array
     */
    public function roles()
    {
        return $this->slot->roles();
    }

    /**
     * Set the default denial response
     *
     * @param \Illuminate\Auth\Access\Response $response
     * @return $this
     */
    public function defaultDenialResponse(Response $response)
    {
        $this->defaultDenialResponse = $response;
        return $this;
    }
}
