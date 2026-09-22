<?php

namespace App\Http\Middleware;

use App\Support\Installer;
use Closure;
use Illuminate\Http\Request;

class RedirectIfInstalled
{
    /** Block the wizard once the app is installed. */
    public function handle(Request $request, Closure $next)
    {
        if (Installer::isInstalled()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Application is already installed.'], 403);
            }

            return redirect('/');
        }

        return $next($request);
    }
}
