<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{
     public function showLoginForm()
    {
         if (Auth::check()) {
            return $this->redirectToDashboard();
        }
        
        return view('welcome');
    }

    public function login(Request $request)
    {
        try {
            if (Auth::check()) {
                return $this->redirectToDashboard();
            }

            // Validate input
            $request->validate([
                'username' => 'required|string|max:255',
                'password' => 'required|string|min:1',
            ]);

            $identifier = $request->input('username');
            $password = $request->input('password');

            // Try teacher/admin login first
            $credentials = ['username' => $identifier, 'password' => $password];
            if (Auth::attempt($credentials)) {
                $request->session()->regenerate();

                \Log::info('User logged in successfully', [
                    'user_id' => Auth::id(),
                    'username' => Auth::user()->username,
                    'role' => Auth::user()->role,
                    'ip' => $request->ip()
                ]);

                return $this->redirectToDashboard();
            }

            // Try student login (by id_no or stud_code)
            $student = \App\Models\Student::where('id_no', $identifier)
                ->orWhere('stud_code', $identifier)
                ->first();

            if ($student && $student->password && \Hash::check($password, $student->password)) {
                Auth::login($student);
                $request->session()->regenerate();

                \Log::info('Student logged in successfully', [
                    'student_id' => $student->id,
                    'id_no' => $student->id_no,
                    'ip' => $request->ip()
                ]);

                return $this->redirectToDashboard();
            }

            \Log::warning('Failed login attempt', [
                'identifier' => $identifier,
                'ip' => $request->ip()
            ]);

            return back()->withErrors([
                'login' => 'Invalid login credentials.',
            ])->withInput($request->except('password'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput($request->except('password'));
        } catch (\Exception $e) {
            \Log::error('Login error: ' . $e->getMessage(), [
                'identifier' => $request->username ?? 'unknown',
                'ip' => $request->ip()
            ]);

            return back()->with('error', 'An unexpected error occurred during login. Please try again.')->withInput($request->except('password'));
        }
    }

    private function redirectToDashboard()
    {
        $user = Auth::user();

        \Log::debug('redirectToDashboard called', [
            'user_id' => $user?->id,
            'user_class' => $user ? class_basename(get_class($user)) : 'null',
            'role' => $user?->role,
            'is_student' => $user instanceof \App\Models\Student,
            'is_user' => $user instanceof \App\Models\User,
        ]);

        if (!$user) {
            \Log::warning('redirectToDashboard: No authenticated user');
            return redirect('/');
        }

        if ($user->role === 'admin') {
            \Log::info('Redirecting admin user', ['user_id' => $user->id]);
            return redirect('/admin/dashboard');
        } else if ($user->role === 'teacher') {
            \Log::info('Redirecting teacher user', ['user_id' => $user->id]);
            return redirect('/teacher/dashboard');
        } else if ($user->role === 'student') {
            \Log::info('Redirecting student user', ['student_id' => $user->id, 'id_no' => $user->id_no ?? 'unknown']);
            return redirect('/student/dashboard');
        }

        \Log::warning('Unknown role', ['user_id' => $user->id, 'role' => $user->role]);
        return redirect('/');
    }

    public function logout(Request $request)
    {
        try {
            $userId = Auth::id();
            $username = Auth::user()?->username ?? 'unknown';
            
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            \Log::info('User logged out successfully', [
                'user_id' => $userId,
                'username' => $username,
                'ip' => $request->ip()
            ]);
            
            return redirect('/')->with('success', 'You have been logged out successfully.');
            
        } catch (\Exception $e) {
            \Log::error('Logout error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);
            
             Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect('/')->with('warning', 'Logout completed, but an error occurred during the process.');
        }
    }
}