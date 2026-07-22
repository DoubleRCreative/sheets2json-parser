<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiDomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = config('domains.api');

        if (! in_array($request->getHost(), $allowed, true)) {
            abort(404);
        }

        // Force json response for all API requests
        $request->headers->set('accept', 'application/json');

        return $next($request);
    }
}
