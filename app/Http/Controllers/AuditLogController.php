<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', AuditLog::class);

        $query = AuditLog::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        // Listes de filtres dérivées des données réelles plutôt que d'une liste
        // en dur : chaque module ajoute ses propres actions/modèles au fil du
        // temps (voir AuditLogService), une liste figée se désynchroniserait vite.
        $actions = AuditLog::query()->distinct()->orderBy('action')->pluck('action');
        $modelTypes = AuditLog::query()->distinct()->orderBy('model_type')->pluck('model_type');

        return view('audit_logs.index', compact('logs', 'users', 'actions', 'modelTypes'));
    }

    public function show(AuditLog $auditLog)
    {
        Gate::authorize('view', $auditLog);
        $auditLog->load('user');

        return view('audit_logs.show', compact('auditLog'));
    }
}
