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
            return redirect()->route('student.dashboard');
        } elseif ($user->hasRole(['super-admin', 'admin'])) {
            return redirect()->route('dashboard');
        } elseif ($user->hasRole('professeur')) {
            return redirect()->route('dashboard');
        } elseif ($user->hasRole('parent')) {
            return redirect()->route('dashboard');
        } else {
            return redirect()->route('dashboard');
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
