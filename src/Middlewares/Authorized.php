<?php

namespace Attla\Authentic\Middlewares;

use Attla\Authentic\Ability;
use Core\Response;
use Illuminate\Http\Request;

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
        $ability = Ability::fromRoute($request->route());

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
}
