<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->onboarding_completed_at) {
            return redirect()->route('setup');
        }

        return $next($request);
    }
}
