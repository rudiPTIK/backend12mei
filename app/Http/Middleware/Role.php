<?php

namespace App\Http\Middleware;

use App\enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Role 
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role): Response
    {
        if ($request->user()->role == UserRole::tryFrom($role)){
            return $next($request);
        }
        abort(403, "akun tidak memiliki level akses yang cukup untuk mengakses halaman ini");

    }
}
