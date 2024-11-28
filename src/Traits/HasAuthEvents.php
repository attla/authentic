<?php

namespace Attla\Authentic\Traits;

use Illuminate\Auth\Events\{
    Attempting,
    Authenticated,
    CurrentDeviceLogout,
    Failed,
    Lockout,
    Login,
    Logout,
    OtherDeviceLogout,
    PasswordReset,
    PasswordResetLinkSent,
    Registered,
    Validated,
    Verified
};
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;

trait HasAuthEvents
{
    /**
     * The event dispatcher instance
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Register an authentication attempt event listener
     *
     * @param string $event
     * @param mixed $callback
     * @return void
     */
    public function listen($event, $callback)
    {
        $this->events?->listen($event, $callback);
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
     * Fire the current device logout event if the dispatcher is set
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return void
     */
    protected function fireCurrentDeviceLogoutEvent($user)
    {
        $this->events?->dispatch(new CurrentDeviceLogout($this->name, $user));
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
     * Fire the lockout event if the dispatcher is set
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    protected function fireLockoutEvent(Request $request)
    {
        $this->events?->dispatch(new Lockout($request));
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
     * Fire the password reset event if the dispatcher is set
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return void
     */
    protected function firePasswordResetEvent($user)
    {
        $this->events?->dispatch(new PasswordReset($user));
    }

    /**
     * Fire the password reset link sent event if the dispatcher is set
     *
     * @param \Illuminate\Contracts\Auth\CanResetPassword $user
     * @return void
     */
    protected function firePasswordResetLinkSentEvent($user)
    {
        $this->events?->dispatch(new PasswordResetLinkSent($user));
    }

    /**
     * Fire the registered event if the dispatcher is set
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return void
     */
    protected function fireRegisteredEvent($user)
    {
        $this->events?->dispatch(new Registered($user));
    }

    /**
     * Fire the validated event if the dispatcher is set
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return void
     */
    protected function fireValidatedEvent($user)
    {
        $this->events?->dispatch(new Validated($this->name, $user));
    }

    /**
     * Fire the verified event if the dispatcher is set
     *
     * @param \Illuminate\Contracts\Auth\MustVerifyEmail $user
     * @return void
     */
    protected function fireVerifiedEvent($user)
    {
        $this->events?->dispatch(new Verified($user));
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
     * @return $this
     */
    public function setDispatcher(Dispatcher $events)
    {
        $this->events = $events;
        return $this;
    }
}
