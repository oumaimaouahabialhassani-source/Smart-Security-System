<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level module gate: `module:{name}` maps onto the capability
 * matrix in UserRole, so a role that must not see a module is stopped
 * before the controller runs. Registered under the "module" alias.
 */
class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $role = $request->user()?->role;

        $allowed = match ($module) {
            'visitors' => $role?->canViewVisitors(),
            'access' => $role?->canViewAccess(),
            'devices' => $role?->canViewDevices(),
            'biometrics' => $role?->canViewBiometrics(),
            'reports' => $role?->canViewReports(),
            'audit' => $role?->canViewAuditLogs(),
            'settings' => $role?->canManageSettings(),
            default => false,
        };

        abort_unless((bool) $allowed, 403);

        return $next($request);
    }
}
