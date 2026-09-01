<?php
// app/Http/Middleware/LogOAuthRequests.php
namespace App\Http\Middleware;

use Closure;

class LogOAuthRequests
{
    public function handle($request, Closure $next)
    {
        if (str_contains($request->path(), 'auth/google')) {
            logger()->debug('OAuth Request', [
                'full_url' => $request->fullUrl(),
                'query_params' => $request->query(),
                'headers' => $request->headers->all()
            ]);
        }

        return $next($request);
    }
}