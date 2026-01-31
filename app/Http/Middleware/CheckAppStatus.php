<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAppStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if application is closed via ENV
        if (env('APP_CLOSED', false)) {
            // Allow access to the maintenance page itself to avoid redirect loops if we were redirecting (but we are rendering direct view)
            // But we should allow bypass for asset loading if needed, though view is self-contained ideally.
            // Returning the view directly stops the request chain.
            return response()->view('maintenance');
        }

        return $next($request);
    }
}
