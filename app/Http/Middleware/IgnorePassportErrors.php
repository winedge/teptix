<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IgnorePassportErrors
{
    /**
     * Handle an incoming request and suppress Passport/OAuth errors for web routes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Remove Authorization header if it exists and is malformed for web routes
        if ($request->headers->has('Authorization')) {
            $authHeader = $request->header('Authorization');

            // Check if it's a Bearer token but potentially malformed
            if (strpos($authHeader, 'Bearer ') === 0) {
                $token = substr($authHeader, 7);

                // A valid JWT has exactly 2 dots (3 parts)
                if (substr_count($token, '.') !== 2) {
                    // Remove the malformed authorization header
                    $request->headers->remove('Authorization');
                }
            }
        }

        return $next($request);
    }
}
