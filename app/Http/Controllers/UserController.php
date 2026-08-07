<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Support\UserRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('voir-utilisateurs');

        $users = User::query()
            ->with(['creator', 'roles'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('matricule', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->role($request->string('role')->toString()))
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
            'roles' => UserRoles::ALL,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('creer-utilisateur');

        return view('users.create', [
            'user' => new User(['is_active' => true]),
            'roles' => UserRoles::CREATABLE_VIA_USER_FORM,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Combiner nom et prénom pour le champ name
        $validated['name'] = trim($validated['nom'] . ' ' . $validated['prenom']);
        
        $validated['matricule'] = User::generateMatricule($validated['role']);
        $validated['password'] = Hash::make('password');
        $validated['created_by'] = auth()->id();
        $validated['password_must_change'] = true;
        $validated['is_active'] = $request->boolean('is_active', true);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create($validated);
            $user->syncRoles([$validated['role']]);

            return $user;
        });

        return redirect()
            ->route('users.index')
            ->with('success', 'Utilisateur créé. Matricule : '.$user->matricule.' | Mot de passe temporaire : password');
    }

    public function edit(User $user): View
    {
        $this->authorize('modifier-utilisateur', $user);
        $this->ensureActorCanTargetSuperAdmin($user);

        return view('users.edit', [
            'user' => $user,
            'roles' => UserRoles::assignableBy(auth()->user()),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active');

        $user->update($validated);
        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')->with('success', 'Utilisateur modifié avec succès.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('supprimer-utilisateur', $user);
        $this->ensureActorCanTargetSuperAdmin($user);

        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }

        $user->update(['is_active' => false]);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Utilisateur désactivé et archivé.');
    }

    public function toggle(User $user): RedirectResponse
    {
        $this->authorize('activer-desactiver-utilisateur', $user);
        $this->ensureActorCanTargetSuperAdmin($user);

        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => 'Vous ne pouvez pas désactiver votre propre compte.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active ? 'Utilisateur activé.' : 'Utilisateur désactivé.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $this->authorize('reinitialiser-mot-de-passe-utilisateur', $user);
        $this->ensureActorCanTargetSuperAdmin($user);

        $user->update([
            'password' => Hash::make('password'),
            'password_must_change' => true,
        ]);

        return back()->with('success', 'Mot de passe réinitialisé. Nouveau mot de passe temporaire : password');
    }

    /**
     * Seul un super-admin peut agir (modifier, désactiver, supprimer, réinitialiser
     * le mot de passe) sur un compte super-admin. La permission 'modifier-utilisateur'
     * etc. est accordée au rôle admin sans restriction par cible (Spatie court-circuite
     * les Policies via Gate::before dès que l'acteur possède la permission), donc ce
     * contrôle doit être explicite ici plutôt que délégué à une Policy.
     */
    private function ensureActorCanTargetSuperAdmin(User $target): void
    {
        abort_if(
            $target->hasRole('super-admin') && ! auth()->user()->hasRole('super-admin'),
            403,
            "Seul un super-administrateur peut agir sur un compte super-administrateur."
        );
    }
}
