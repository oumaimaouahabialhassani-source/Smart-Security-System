<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Logout must never dead-end on "419 Page Expired". When the
        // session already expired (second tab, double-click, timeout),
        // the POST carries a stale CSRF token; validating it would
        // block the one action the user obviously wants. Worst case of
        // exempting it: someone can force a logout — an annoyance, not
        // a breach. The logout handler still invalidates the session
        // and regenerates the token.
        $middleware->validateCsrfTokens(except: ['logout']);

        $middleware->alias([
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'ai.bot' => \App\Http\Middleware\EnsureAiBotAccess::class,
            'module' => \App\Http\Middleware\EnsureModuleAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
