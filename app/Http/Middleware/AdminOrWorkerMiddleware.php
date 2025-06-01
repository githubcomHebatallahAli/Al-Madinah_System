<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminOrWorkerMiddleware
{

public function handle(Request $request, Closure $next): Response
{
    if (Auth::guard('admin')->check()) {
        Auth::shouldUse('admin');
        return $next($request);
    }

    if (Auth::guard('worker')->check()) {
        Auth::shouldUse('worker');
        return $next($request);
    }

    return response()->json(['message' => 'Unauthenticated.'], 401);
}

    }

