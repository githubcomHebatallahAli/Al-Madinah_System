<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminOrWorkerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    //         public function handle(Request $request, Closure $next): Response
    // {
    //     if (Auth::guard('admin')->check() || Auth::guard('worker')->check()) {
    //         return $next($request);
    //     }

    //     return response()->json(['message' => 'Unauthenticated.'], 401);
    // }

public function handle(Request $request, Closure $next): Response
{
    if (Auth::guard('admin')->check()) {
        Auth::setUser(Auth::guard('admin')->user());
        return $next($request);
    }

    if (Auth::guard('worker')->check()) {
        Auth::setUser(Auth::guard('worker')->user());
        return $next($request);
    }

    return response()->json(['message' => 'Unauthenticated.'], 401);
}


    }

