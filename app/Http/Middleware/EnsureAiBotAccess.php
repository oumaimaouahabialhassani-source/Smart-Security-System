<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the AI Security Bot module: administrators and security
 * officers only. Registered under the "ai.bot" alias.
 */
class EnsureAiBotAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->role->canUseAiBot(), 403);

        return $next($request);
    }
}
