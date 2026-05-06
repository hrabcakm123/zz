<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token || $token !== env('API_TOKEN')) {
            return response()->json(['error' => 'Unauthorized – invalid or missing token'], 401);
        }

        return $next($request);
    }
}
