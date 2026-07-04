<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const ROLES = [
        'super-admin',
        'admin',
        'manager-comptable',
        'comptable',
        'professeur',
        'parent',
        'eleve',
    ];

    public function index(Request $request)
    {
        $users = User::query()
            ->with('creator')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('matricule', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')))
            ->when($request->filled('status'), function ($query) use ($request) {
                if ($request->string('status')->toString() === 'active') {
                    $query->where('is_active', true);
                }

                if ($request->string('status')->toString() === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'roles' => self::ROLES,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function create()
    {
        return view('users.create', [
            'user' => new User(['is_active' => true]),
            'roles' => self::ROLES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);
        $validated['matricule'] = User::generateMatricule($validated['role']);
        $validated['password'] = Hash::make('password');
        $validated['created_by'] = auth()->id();
        $validated['password_must_change'] = true;
        $validated['is_active'] = $request->boolean('is_active', true);

        $user = User::create($validated);
        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('users.index')
            ->with('success', 'Utilisateur créé. Matricule : '.$user->matricule.' | Mot de passe temporaire : password');
    }

    public function edit(User $user)
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => self::ROLES,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $this->validatedData($request, $user);
        $validated['is_active'] = $request->boolean('is_active');

        $user->update($validated);
        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')->with('success', 'Utilisateur modifié avec succès.');
    }

    public function destroy(User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }

        $user->update(['is_active' => false]);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Utilisateur désactivé et archivé.');
    }

    public function toggle(User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => 'Vous ne pouvez pas désactiver votre propre compte.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active ? 'Utilisateur activé.' : 'Utilisateur désactivé.');
    }

    public function resetPassword(User $user)
    {
        $user->update([
            'password' => Hash::make('password'),
            'password_must_change' => true,
        ]);

        return back()->with('success', 'Mot de passe réinitialisé. Nouveau mot de passe temporaire : password');
    }

    private function validatedData(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique(User::class)->ignore($user)],
            'role' => ['required', Rule::in(self::ROLES)],
            'contract_started_at' => ['nullable', 'date'],
        ]);
    }

    
}
