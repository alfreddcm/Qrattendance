<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {

         if (!Auth::check()) {
            return redirect('/')->with('message', 'Please login to access this page.');
        }

        $user = Auth::user();
        $userRole = $user->role;
        
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
                                
            default:
                Auth::logout();
                return redirect('/')
                    ->with('error', 'Invalid user role. Please contact administrator.');
        }
    }
}
