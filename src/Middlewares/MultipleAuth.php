<?php

namespace Attla\Authentic\Middlewares;

use Attla\Support\Arr as AttlaArr;
use Attla\Support\Envir;
use Illuminate\Http\Request;

class MultipleAuth
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
        $auth = '';

        if ($request->path() === Envir::getConfig('authentic.flow.route')) {
            $auth = $request->token;
        } else if (
            $uid = $request->header('uid')
            and !is_null($cookie = $request->cookie('__auth_'. $uid))
        ) {
            $auth = $cookie;
        }

        if ($auth) {
            $request->headers->set('Authorization', 'bearer '. $auth);
        }

        return $next($request);
    }

    /**
     * Create a new instance of the model
     *
     * @param array|object $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createModel(array|object $attributes = [])
    {
        $model = Envir::getConfig('auth.providers.users.model', 'App\Models\User');
        return new $model(is_array($attributes) ? $attributes : AttlaArr::toArray($attributes));
    }
}
