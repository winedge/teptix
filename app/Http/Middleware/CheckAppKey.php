<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAppKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $appKey = $request->header('X-APP-KEY');
        $validKey = env('MOBILE_APP_KEY');

        if (!$appKey || $appKey !== $validKey) {
    return response()->view('errors.401', [], 401);
}

        return $next($request);
    }
}
