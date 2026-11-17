<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // If specific roles are passed (e.g. 'ADMIN', 'ACCOUNTANT')
        if (!empty($roles) && !in_array($user->role, $roles, true)) {
            abort(403, 'Access denied. Your role (' . $user->role . ') is not authorized for this section.');
        }

        // Enforcement for OWNER (Strictly Read-Only for ERP data, but can send Chat messages)
        if ($user->role === 'OWNER') {
            if (!in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
                // Allow logout, lock, switch-account, setup-pin, chat/send
                if (!$request->is('logout') && !$request->is('lock') && !$request->is('switch-account') && !$request->is('setup-pin') && !$request->is('chat/send') && !$request->is('chat/*')) {
                    if ($request->expectsJson()) {
                        return response()->json(['error' => 'Company Owner profile is in Read-Only Overview mode. Data modifications are disabled.'], 403);
                    }
                    return back()->with('error', 'Company Owner profile is in Read-Only Overview mode. Data modifications are disabled.');
                }
            }
        }

        // Enforcement for MANAGER (Can view all, but can only write to Petty Cash daily expenses and Chat)
        if ($user->role === 'MANAGER') {
            if (!in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
                $allowedWriteRoutes = [
                    'petty-cash',
                    'petty-cash/*',
                    'chat/send',
                    'chat/*',
                    'logout',
                    'lock',
                    'switch-account',
                    'setup-pin',
                    'ai-copilot/*',
                ];
                $matched = false;
                foreach ($allowedWriteRoutes as $pattern) {
                    if ($request->is($pattern)) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    if ($request->expectsJson()) {
                        return response()->json(['error' => 'Manager access is limited to recording Daily Expenses in Petty Cash. Full editing is reserved for Mirza Sohail.'], 403);
                    }
                    return back()->with('error', 'Manager access is limited to recording Daily Expenses in Petty Cash. Full editing is reserved for Mirza Sohail.');
                }
            }
        }

        return $next($request);
    }
}
