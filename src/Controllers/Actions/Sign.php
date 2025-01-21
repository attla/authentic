<?php

namespace Attla\Authentic\Controllers\Actions;

use Attla\Support\Envir;
use Attla\Support\Arr as AttlaArr;
use Core\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Auth;

class Sign extends \Core\Action
{
    protected $rules = [
        'token' => 'required|string',
    ];

    public function handle(Request $request): Response
    {
        if (!$user = Auth::user())
            return Response::unauthorized();

        $prefix = 'authentic.flow.';
        $userArray = AttlaArr::toArray($user);
        $search = array_map(fn($key) => '{'.$key.'}', array_keys($userArray));
        $replace = array_map(fn($val) => urlencode($val), array_values($userArray));
        $callback = Str::of(Envir::getConfig($prefix .'callback'))->replace($search, $replace, false);

        return Response::ok($callback)->withCookie(Cookie::make(
            '__auth_' . ($user->id ?: $user->uid), $request->token,
            Envir::getConfig($prefix .'remember'),
            null, null,
            true, true,
            false, 'None'
        ));
    }
}
