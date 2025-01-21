<?php

namespace Attla\Authentic\Middlewares;

use Illuminate\Http\Request;
use Attla\Support\Envir;

class AllowServer
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
        $response = $next($request);

        $response->headers->set('Access-Control-Allow-Origin', Envir::getConfig('authentic.flow.server'));
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        return $response;
    }
}
