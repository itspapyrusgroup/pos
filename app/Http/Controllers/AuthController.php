<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route($this->landingRouteName(Auth::user()));
        }

        return view('login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $throttleKey = Str::lower((string) $request->input('login')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->withErrors(['login' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik."])
                ->withInput();
        }

        $user = \App\Models\User::query()
            ->where('email', $credentials['login'])
            ->orWhere('username', $credentials['login'])
            ->first();

        // Mitigate timing attacks by always running a hash check even when user is not found.
        $passwordIsValid = $user
            ? Hash::check($credentials['password'], $user->password)
            : Hash::check($credentials['password'], '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

        if (!$user || !$passwordIsValid) {
            RateLimiter::hit($throttleKey, 60);
            return back()->withErrors(['login' => 'Email/Username atau password salah.'])->withInput();
        }

        if (!$user->status) {
            RateLimiter::hit($throttleKey, 60);
            return back()->withErrors(['login' => 'Email/Username atau password salah.'])->withInput();
        }

        Auth::login($user, (bool) ($credentials['remember'] ?? false));
        $request->session()->regenerate();
        RateLimiter::clear($throttleKey);

        return redirect()->route($this->landingRouteName($user));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function landingRouteName($user): string
    {
        if ($user && $user->hasPermission('dashboard.view') && Route::has('dashboard')) {
            return 'dashboard';
        }

        return Route::has('home') ? 'home' : 'login';
    }
}
