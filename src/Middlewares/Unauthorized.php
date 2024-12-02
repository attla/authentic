<?php

namespace Attla\Authentic\Middlewares;

use Core\Response;
use Illuminate\Http\Request;

class Unauthorized
{
    /**
     * Handle an incoming request
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return \Core\Response
     */
    public function handle(Request $request, \Closure $next)
    {
        return Response::unauthorized();
    }
}
