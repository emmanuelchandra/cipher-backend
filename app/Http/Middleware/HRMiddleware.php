<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HRMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || (!$request->user()->isHR() && !$request->user()->isAdmin())) {
            return response()->json(['message' => 'Forbidden. HR access required.'], 403);
        }

        return $next($request);
    }
}
