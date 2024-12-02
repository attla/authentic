<?php

namespace Attla\Authentic\Middlewares;

use Core\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Authorized
{
    /**
     * Handle an incoming request
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        $unauthorized = Response::unauthorized();
        $ability = $this->getAbility($request);

        if (empty($ability) || is_null($user = $request->user())) {
            return $unauthorized;
        }

        if (
            strpos($ability, 'api.') === 0
            || strpos($ability, 'web.') === 0
        ) {
            $ability = substr($ability, 4);
        }

        if (
            !method_exists($user, 'cant')
            || $user->cant($ability)
        ) {
            return $unauthorized;
        }

        return $next($request);
    }

    /**
     * Retrieve the ability from current incoming request
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    protected function getAbility(Request $request)
    {
        $route = $request->route();
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
