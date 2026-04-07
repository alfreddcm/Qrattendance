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
            \Log::info('=== LOGIN REQUEST STARTED ===', [
                'username' => $request->input('username'),
                'ip' => $request->ip(),
                'time' => now()
            ]);

            if (Auth::check()) {
                \Log::info('User already authenticated, redirecting');
                return $this->redirectToDashboard();
            }

            // Validate input
            $request->validate([
                'username' => 'required|string|max:255',
                'password' => 'required|string|min:1',
                'app_mode' => 'nullable|string|max:50',
            ]);

            $identifier = $request->input('username');
            $password = $request->input('password');

            \Log::info('Attempting teacher/admin login', ['identifier' => $identifier]);

            // Try teacher/admin login first
            $credentials = ['username' => $identifier, 'password' => $password];
            if (Auth::attempt($credentials)) {
                if ($request->input('app_mode') === 'student-pwa' && Auth::user()?->role !== 'student') {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()->withErrors([
                        'login' => 'Only student accounts can sign in from the installed app. Use the web portal for admin or teacher access.',
                    ])->withInput($request->except('password'));
                }

                $request->session()->regenerate();

                \Log::info('✓ TEACHER/ADMIN LOGIN SUCCESS', [
                    'user_id' => Auth::id(),
                    'username' => Auth::user()->username,
                    'role' => Auth::user()->role,
                    'ip' => $request->ip()
                ]);

                $redirect = $this->redirectToDashboard();
                \Log::info('Teacher/Admin redirect response', ['status' => $redirect->getStatusCode()]);
                return $redirect;
            }

            \Log::info('Teacher/admin login failed, attempting student login');

            // Try student login (by id_no or stud_code)
            $student = \App\Models\Student::where('id_no', $identifier)
                ->orWhere('stud_code', $identifier)
                ->first();

            if (!$student) {
                \Log::warning('Student not found', ['identifier' => $identifier]);
            } else {
                \Log::info('Student found', [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'has_password' => !is_null($student->password)
                ]);

                if (!$student->password) {
                    \Log::warning('Student has no password', ['student_id' => $student->id]);
                } else {
                    $passwordMatch = \Hash::check($password, $student->password);
                    \Log::info('Password hash check', ['match' => $passwordMatch]);

                    if ($passwordMatch) {
                        $studentOnlyApp = $request->input('app_mode') === 'student-pwa';

                        Auth::login($student);

                        if ($studentOnlyApp) {
                            \Log::info('Student PWA login confirmed', [
                                'student_id' => $student->id,
                                'id_no' => $student->id_no,
                            ]);
                        }

                        $request->session()->regenerate();
                        $request->session()->save();

                        \Log::info('✓ STUDENT LOGIN SUCCESS', [
                            'student_id' => $student->id,
                            'id_no' => $student->id_no,
                            'name' => $student->name,
                            'role' => Auth::user()?->role,
                            'ip' => $request->ip()
                        ]);

                        return $this->redirectToDashboard();
                    }
                }
            }

            \Log::warning('Failed login attempt', [
                'identifier' => $identifier,
                'student_exists' => $student ? 'yes' : 'no',
                'ip' => $request->ip()
            ]);

            return back()->withErrors([
                'login' => 'Invalid login credentials.',
            ])->withInput($request->except('password'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Validation exception', ['errors' => $e->errors()]);
            return back()->withErrors($e->errors())->withInput($request->except('password'));
        } catch (\Exception $e) {
            \Log::error('❌ LOGIN ERROR: ' . $e->getMessage(), [
                'identifier' => $request->username ?? 'unknown',
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
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

            if ($user instanceof \App\Models\Student && $user->usesDefaultPassword()) {
                return redirect()->route('student.account')
                    ->with('info', 'Please change your temporary password before continuing.');
            }

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