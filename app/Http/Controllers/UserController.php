<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\SuperAdminProtectionService;
use App\Support\UserRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly SuperAdminProtectionService $superAdminProtection)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('voir-utilisateurs');

        $users = User::query()
            // withTrashed() : sans ça, le filtre "Archivés" ne peut structurellement
            // jamais rien retourner (même bug déjà corrigé pour le module Parents).
            ->withTrashed()
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
                $status = $request->string('status')->toString();

                if ($status === 'active') {
                    $query->whereNull('deleted_at')->where('is_active', true);
                }

                if ($status === 'inactive') {
                    $query->whereNull('deleted_at')->where('is_active', false);
                }

                if ($status === 'archived') {
                    $query->whereNotNull('deleted_at');
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

        $role = $validated['role'];
        unset($validated['role']);

        $validated['matricule'] = User::generateMatricule($role);
        $temporaryPassword = Str::password(12);
        $validated['password'] = Hash::make($temporaryPassword);
        $validated['created_by'] = auth()->id();
        $validated['password_must_change'] = true;
        $validated['is_active'] = $request->boolean('is_active', true);

        $user = DB::transaction(function () use ($validated, $role) {
            $user = User::create($validated);
            $user->syncRoles([$role]);
            $user->syncPrimaryRoleColumn();

            return $user;
        });

        return redirect()
            ->route('users.index')
            ->with('success', 'Utilisateur créé. Matricule : '.$user->matricule.' | Mot de passe temporaire : '.$temporaryPassword);
    }

    public function edit(User $user): View
    {
        $this->authorize('modifier-utilisateur', $user);
        $this->superAdminProtection->ensureCanTarget($user);

        return view('users.edit', [
            'user' => $user,
            'roles' => UserRoles::editableRolesFor(auth()->user(), $user),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $validated['name'] = trim($validated['nom'].' '.$validated['prenom']);
        $validated['is_active'] = $request->boolean('is_active');

        $role = $validated['role'];
        unset($validated['role']);

        DB::transaction(function () use ($user, $validated, $role) {
            $user->update($validated);
            $user->syncRoles([$role]);
            $user->syncPrimaryRoleColumn();
        });

        return redirect()->route('users.index')->with('success', 'Utilisateur modifié avec succès.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('supprimer-utilisateur', $user);
        $this->superAdminProtection->ensureCanTarget($user);

        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }

        $blockingReason = UserRoles::activeBusinessLinkBlockingRoleChange($user);

        if ($blockingReason) {
            return back()->withErrors(['user' => $blockingReason]);
        }

        // L'archivage effectif de l'email (contrainte UNIQUE en base) est géré au
        // niveau du modèle (voir User::booted()) : il s'applique quel que soit le
        // chemin de suppression, pas seulement celui-ci.
        $user->update(['is_active' => false]);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Utilisateur désactivé et archivé.');
    }

    public function restore(int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);

        $this->authorize('supprimer-utilisateur', $user);
        $this->superAdminProtection->ensureCanTarget($user);

        DB::transaction(function () use ($user) {
            $user->restore();
            $user->update(['is_active' => true]);
        });

        return redirect()->route('users.index')->with('success', 'Utilisateur restauré.');
    }

    public function toggle(User $user): RedirectResponse
    {
        $this->authorize('activer-desactiver-utilisateur', $user);
        $this->superAdminProtection->ensureCanTarget($user);

        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => 'Vous ne pouvez pas désactiver votre propre compte.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active ? 'Utilisateur activé.' : 'Utilisateur désactivé.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $this->authorize('reinitialiser-mot-de-passe-utilisateur', $user);
        $this->superAdminProtection->ensureCanTarget($user);

        $temporaryPassword = Str::password(12);

        $user->update([
            'password' => Hash::make($temporaryPassword),
            'password_must_change' => true,
        ]);

        return back()->with('success', 'Mot de passe réinitialisé. Nouveau mot de passe temporaire : '.$temporaryPassword);
    }

}
