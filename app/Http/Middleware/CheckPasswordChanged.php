<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;

class CheckPasswordChanged
{
    /**
     * Handle an incoming request.
     * Redirects students to their account page if they are still using the temporary LRN password.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Only check for students
        if ($user instanceof Student) {
            // Skip account pages so the student can complete the change there.
            if (
                $user->usesDefaultPassword()
                && !$request->routeIs('student.account', 'student.account.password')
            ) {
                \Log::info('Student requires temporary password change', [
                    'student_id' => $user->id,
                    'id_no' => $user->id_no
                ]);
                return redirect()->route('student.account')
                    ->with('info', 'Please change your temporary password before continuing.');
            }
        }

        return $next($request);
    }
}