<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Handle an incoming request.
     * Protects admin routes with a simple password stored in ADMIN_PASSWORD env var.
     * If ADMIN_PASSWORD is not set, admin is accessible without a password.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $adminPassword = config('app.admin_password');

        // If no admin password is configured, allow access
        if (empty($adminPassword)) {
            return $next($request);
        }

        // Check session for already-authenticated admin
        if (session('admin_authenticated') === true) {
            return $next($request);
        }

        // Handle login form submission
        if ($request->isMethod('POST') && $request->routeIs('admin.login*')) {
            if ($request->input('password') === $adminPassword) {
                session(['admin_authenticated' => true]);
                return redirect()->route('admin.dashboard');
            }
            return back()->withErrors(['password' => 'Incorrect admin password.']);
        }

        // Redirect to admin login page
        if (!$request->routeIs('admin.login*')) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
