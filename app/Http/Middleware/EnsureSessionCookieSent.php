<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the session cookie is sent on the current request scheme (HTTP or HTTPS).
 * When the user visits via HTTP, we must not set Secure on the cookie or the browser
 * won't send it back, causing 419 CSRF errors on form submit.
 */
class EnsureSessionCookieSent
{
    public function handle(Request $request, Closure $next): Response
    {
        $secure = config('session.secure');

        if ($secure === true && ! $request->secure()) {
            Config::set('session.secure', false);
        }

        return $next($request);
    }
}
