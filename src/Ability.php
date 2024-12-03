<?php

namespace Attla\Authentic;

use Attla\Support\ListBag;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;

class Ability extends ListBag
{
    /** @inheritdoc */
    public function __construct(object|array $data = [])
    {
        $this->data = array_map(
            fn($val) => $this->format($val),
            array_values(Arr::toArray($data))
        );
    }

    /** @inheritdoc */
    public function set($key, $value): void
    {
        parent::set($key, $this->format($value));
    }

    /**
     * Format the ability identifier
     *
     * @param string $ability
     * @return string
     */
    public static function format($ability)
    {
        return Str::snake(strtr($ability, '-:', '_.'));
    }

    /**
     * Create a new instance from cached or available abilities list
     *
     * @return static
     */
    public static function slot()
    {
        return new static(static::cached() ?: static::compile());
    }

    /**
     * The path of chached abilities
     *
     * @return string
     */
    public static function cachePath()
    {
        return storage_path('/authentic_abilities');
    }

    /**
     * Retrieve cached abilities list
     *
     * @return string[]
     */
    public static function cached()
    {
        if (is_file($path = static::cachePath())) {
            return include_once $path;
        }

        return [];
    }

    /**
     * Create a new instance from the current route list
     *
     * @return string[]
     */
    public static function compile()
    {
        $slot = [];
        $routes = Route::getRoutes();
        foreach ($routes as $route) {
            $slot[] = static::fromRoute($route);
        }

        return array_unique($slot);
    }

    /**
     * Get the ability from route
     *
     * @param \Illuminate\Routing\Route|object|string|null $route
     * @return string
     */
    public static function fromRoute($route)
    {
        if (empty($route)) {
            return '';
        }

        if (is_string($route)) {
            return $route;
        }

        if ($ability = $route->getName()) {
            return $ability;
        }

        $action = explode('@', $route->getActionName());

        if (count($action) == 2) {
            $method = $action[1];
            $parts = array_filter(array_map(function($item) {
                $part = array_filter(explode('controller', $item));
                return count($part) < 2 ? $part[0] ?? '' : $part;
            }, explode('\\', strtolower($action[0]))));

            do {
                $feature = array_pop($parts);
                if (is_array($feature)) {
                    $feature = Arr::first($feature);
                }
            } while (empty($feature));

            $feature = Str::plural($feature);
            return "$feature.$method";
        }

        return '';
    }
}
