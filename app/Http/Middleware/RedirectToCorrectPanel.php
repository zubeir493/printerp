<?php

namespace App\Http\Middleware;

use App\UserRole;
use Closure;
use Illuminate\Http\Request;

class RedirectToCorrectPanel
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): mixed  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        if ($user && ($user->role?->value ?? $user->role) !== UserRole::Admin->value) {
            $role = $user->role instanceof UserRole ? $user->role : UserRole::from($user->role);
            return new \Illuminate\Http\RedirectResponse($role->getRedirectPath());
        }

        return $next($request);
    }
}
