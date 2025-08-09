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
            
            $credentials = $request->only('username', 'password');

            if (Auth::attempt($credentials)) {
                 $request->session()->regenerate();
                
                \Log::info('User logged in successfully', [
                    'user_id' => Auth::id(),
                    'username' => Auth::user()->username,
                    'ip' => $request->ip()
                ]);
                
                return $this->redirectToDashboard();
            }

             \Log::warning('Failed login attempt', [
                'username' => $request->username,
                'ip' => $request->ip()
            ]);

            return back()->withErrors([
                'login' => 'Invalid username or password.',
            ])->withInput($request->except('password'));
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput($request->except('password'));
        } catch (\Exception $e) {
            \Log::error('Login error: ' . $e->getMessage(), [
                'username' => $request->username ?? 'unknown',
                'ip' => $request->ip()
            ]);
            
            return back()->with('error', 'An unexpected error occurred during login. Please try again.')->withInput($request->except('password'));
        }
    }

   
    private function redirectToDashboard()
    {
        $user = Auth::user();
        
        if ($user->role === 'admin') {
            return redirect('/admin/dashboard');
        } else if ($user->role === 'teacher') {
            return redirect('/teacher/dashboard');
        }
        
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