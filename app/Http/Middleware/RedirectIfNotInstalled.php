<?php

namespace App\Http\Middleware;

use App\Support\Installer;
use Closure;
use Illuminate\Http\Request;

class RedirectIfNotInstalled
{
    /**
     * WordPress-style: send web visitors to the installer
     * until installation is complete. API routes get JSON 503.
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('install*')) {
            return $next($request);
        }

        if (!Installer::isInstalled()) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Application is not installed yet. Visit /install to complete setup.'], 503);
            }

            // Only hijack safe web navigation, not POST webhooks etc.
            if ($request->isMethod('get') && !$request->is('install*')) {
                return redirect()->route('install.index');
            }
        }

        return $next($request);
    }
}
