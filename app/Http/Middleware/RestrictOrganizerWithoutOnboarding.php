<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RestrictOrganizerWithoutOnboarding
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (
            $user &&
            $user->hasRole('Organizer') &&
            !$user->onboarding_completed_at &&
            !$request->is('organization-home') &&
            !$request->routeIs('users.onboarding') &&
            !$request->routeIs('users.onboarding.save')
        ) {
            return redirect('organization-home')
                ->with('statusblock', __('Please complete onboarding from the organization home page.'));
        }

        return $next($request);
    }
}
