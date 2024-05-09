<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Auth;

class ApiAuthenticate extends Middleware
{
    public function handle($request, Closure $next, ...$guards)
    {
        foreach ($guards as $guard) {
            if ( ! Auth::guard($guard)->check()) {
                return response()->json(['error' => 'unauthenticated'], 403);
            }
        }

        return $next($request);
    }
}
