<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role): Response
    {
        // Bisa case-insensitive biar aman
        if (Auth::check() && strtolower(Auth::user()->role) == strtolower($role)) {
            return $next($request);
        }

        return response()->json(['message' => 'Akses ditolak!'], 403);
    }
}
