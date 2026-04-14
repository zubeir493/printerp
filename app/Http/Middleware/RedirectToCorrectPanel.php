<?php

namespace App\Http\Middleware;

use App\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToCorrectPanel
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        if ($user && $user->role !== UserRole::Admin) {
            return redirect($user->role->getRedirectPath());
        }

        return $next($request);
    }
}
