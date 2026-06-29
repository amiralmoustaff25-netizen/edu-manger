<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = Auth::user();

        // Redirection selon le rôle
        if ($user->hasRole('eleve')) {
            return redirect()->intended(route('student.dashboard', absolute: false));
        } elseif ($user->hasRole(['super-admin', 'admin'])) {
            return redirect()->intended(route('dashboard', absolute: false));
        } elseif ($user->hasRole('professeur')) {
            return redirect()->intended(route('dashboard', absolute: false));
        } elseif ($user->hasRole('parent')) {
            return redirect()->intended(route('dashboard', absolute: false));
        } else {
            return redirect()->intended(route('dashboard', absolute: false));
        }
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
