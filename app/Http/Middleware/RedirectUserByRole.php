<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectUserByRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && $request->routeIs('dashboard')) {
            $user = auth()->user();

            if ($user->hasRole('admin')) {
                return redirect()->route('admin.dashboard');
            }

            if ($user->hasRole('operations')) {
                return redirect()->route('operations.command-center');
            }

            if ($user->hasRole('supervisor')) {
                return redirect()->route('field.election-mode');
            }

            if ($user->hasRole('delegate')) {
                return redirect()->route('field.election-mode');
            }

            abort(403);
        }

        return $next($request);
    }
}
