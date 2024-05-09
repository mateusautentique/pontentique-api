<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if ( ! Auth::check()) {
            return response()->json(['error' => 'unauthorized'], 403);
        }

        return $next($request);
    }
}
