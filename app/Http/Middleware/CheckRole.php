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
   public function handle(Request $request, Closure $next, ...$roles): Response
{
    if (auth()->check()) {
        $userRole = strtolower(auth()->user()->role);
        $roles = array_map('strtolower', $roles);
        if (in_array($userRole, $roles, true)) {
            return $next($request);
        }
    }
    return response()->json(['message' => 'Akses ditolak!'], 403);
}

}
