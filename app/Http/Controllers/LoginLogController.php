<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LoginLogController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', LoginLog::class);

        $query = LoginLog::with('user');

        // Filtrer par statut
        if ($request->filled('status')) {
            if ($request->status === 'success') {
                $query->successful();
            } elseif ($request->status === 'failed') {
                $query->failed();
            }
        }

        // Filtrer par utilisateur
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filtrer par date
        if ($request->filled('date')) {
            $query->whereDate('login_at', $request->date);
        }

        // Filtrer par rôle (snapshot au moment de la connexion, voir LogSuccessfulLogin)
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $logs = $query->orderBy('login_at', 'desc')->paginate(50)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $roles = LoginLog::query()->whereNotNull('role')->distinct()->orderBy('role')->pluck('role');

        $stats = [
            'total' => LoginLog::count(),
            'success' => LoginLog::successful()->count(),
            'failed' => LoginLog::failed()->count(),
            'today' => LoginLog::whereDate('login_at', today())->count(),
        ];

        return view('login_logs.index', compact('logs', 'users', 'roles', 'stats'));
    }

    public function show(LoginLog $loginLog)
    {
        Gate::authorize('view', $loginLog);
        $loginLog->load('user');

        return view('login_logs.show', compact('loginLog'));
    }
}
