<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        \Log::info('=== RoleMiddleware CHECK ===', [
            'url' => $request->url(),
            'auth_check' => Auth::check(),
            'session_id' => $request->session()->getId(),
            'has_session' => $request->hasSession(),
            'required_roles' => $roles
        ]);

         if (!Auth::check()) {
            \Log::warning('RoleMiddleware: Not authenticated, redirecting to login');
            return redirect('/')->with('message', 'Please login to access this page.');
        }

        $user = Auth::user();
        $userRole = $user->role;

        \Log::info('RoleMiddleware: User authenticated', [
            'user_id' => $user->id,
            'user_role' => $userRole,
            'user_class' => class_basename(get_class($user))
        ]);
        
         if (empty($roles)) {
            return $next($request);
        }

         if (in_array($userRole, $roles)) {
            return $next($request);
        } 

        return $this->redirectToUserDashboard($userRole);
    }


    public static function redirectToUserDashboard(string $role = null, string $message = 'You do not have permission to access the requested page.')
    {
        if ($role === null) {
            if (Auth::check()) {
                $role = Auth::user()->role;
                $message = 'Page not found.';
            } else {
                return redirect('/')
                    ->with('message', 'Page not found. Please login to access the system.');
            }
        }

        switch ($role) {
            case 'admin':
                return redirect()->route('admin.dashboard')
                    ->with('error', $message . ' Redirected to your dashboard.');

            case 'teacher':
                return redirect()->route('teacher.dashboard')
                    ->with('error', $message . ' Redirected to your dashboard.');

            case 'student':
                return redirect()->route('student.dashboard')
                    ->with('error', $message . ' Redirected to your dashboard.');

            default:
                Auth::logout();
                return redirect('/')
                    ->with('error', 'Invalid user role. Please contact administrator.');
        }
    }
}
