<?php

namespace Attla\Authentic;

use Attla\Support\Attempt;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AuthzRepository
{
    /**
     * All of the defined abilities
     *
     * @var array
     */
    protected $abilities = [];

    /**
     * All of the defined roles
     *
     * @var array
     */
    protected $roles = [];

    /**
     * Create a new authorization repository
     *
     * @param array $abilities
     * @param array $roles
     * @return void
     */
    public function __construct(
        array $abilities = [],
        array $roles = [],
    ) {
        $this->abilities = $this->format($abilities);
        $this->roles = $this->format($roles);
    }

    /**
     * Determine if a given ability has been defined
     *
     * @param string|array $ability
     * @return bool
     */
    public function has($ability)
    {
        $abilities = Arr::flatten(is_array($ability) ? $ability : func_get_args());

        foreach ($abilities as $ability) {
            list($key, $action) = $this->formatAbility($ability);

            if (is_null($action)) {
                return in_array($key, $this->roles)
                    || in_array($key, $this->abilities);
            }

            $group = Arr::get($this->abilities, $key);

            if (
                is_null($group)
                || $action !== '*' && !in_array($action, $group)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Format a slot
     *
     * @param array $slog
     * @return array
     */
    protected function format($items)
    {
        $slot = [];
        $items = Arr::flatten(is_array($items) ? $items : func_get_args());

        foreach ($items as $item) {
            list($key, $value) = $this->formatAbility($item);

            if (is_null($value)) {
                $slot[] = $key;
                continue;
            }

            $current = Arr::get($slot, $key) ?: [];
            if (!in_array($value, $current)) {
                $current[] = $value;
            }

            Arr::set($slot, $key, $current);
        }

        return $slot;
    }

    /**
     * Format the ability to [$groupKey, $action]
     *
     * @param string $ability
     * @return string[]
     */
    protected function formatAbility($ability)
    {
        $ability = Ability::format($ability);
        if (strpos($ability, '.') === false) {
            return [$ability, null];
        }

        $lastDotPos = strrpos($ability, '.');

        return [
            substr($ability, 0, $lastDotPos),
            substr($ability, $lastDotPos + 1),
        ];
    }

    /**
     * Create a new instance from the given user
     *
     * @param object $user
     * @return static
     */
    public static function fromUser($user)
    {
        $roles = Attempt::resolve(fn() => Arr::wrap($user->role))
            ->default([])
            ->get();

        $abilities = Attempt::resolve(fn() => Arr::wrap($user->permissions))
            ->default([])
            ->get();

        return new static($abilities, $roles);
    }

    /**
     * Get all of the defined abilities
     *
     * @return array
     */
    public function abilities()
    {
        return $this->abilities;
    }

    /**
     * Get all of the defined roles
     *
     * @return array
     */
    public function roles()
    {
        return $this->roles;
    }
}
