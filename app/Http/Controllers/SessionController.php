<?php

namespace App\Http\Controllers;

use App\Models\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('gerer-sessions-actives');

        // Driver 'database' uniquement : sur file/redis/etc. la table 'sessions'
        // n'existe pas et cet écran n'a pas de source de données à afficher.
        $available = config('session.driver') === 'database';

        $sessions = $available
            ? Session::with('user.roles')
                ->whereNotNull('user_id')
                ->orderByDesc('last_activity')
                ->get()
            : collect();

        return view('sessions.index', [
            'sessions' => $sessions,
            'available' => $available,
            'currentSessionId' => $request->session()->getId(),
        ]);
    }

    public function destroy(string $sessionId): RedirectResponse
    {
        Gate::authorize('gerer-sessions-actives');

        if ($sessionId === request()->session()->getId()) {
            return back()->withErrors(['session' => 'Vous ne pouvez pas déconnecter de force votre propre session actuelle. Utilisez "Se déconnecter" depuis le menu profil.']);
        }

        $deleted = Session::whereKey($sessionId)->delete();

        if (! $deleted) {
            return back()->withErrors(['session' => 'Cette session est introuvable — elle a peut-être déjà expiré.']);
        }

        return back()->with('success', 'Session déconnectée de force.');
    }
}
