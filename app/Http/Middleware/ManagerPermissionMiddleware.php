<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManagerPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        $user = Auth::user();
        if ($user && $user->hasRole('Manager')) {
            if (!$user->hasManagerPermission($permission)) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => __('Unauthorized access.')], 403);
                }
                abort(403, __('Unauthorized access. You do not have permission to view this page.'));
            }
        }
        return $next($request);
    }
}
