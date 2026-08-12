<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserPermissionService;
use App\Support\UserRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAssignmentController extends Controller
{
    private const SEARCHABLE_ROLES = [
        'super-admin', 'admin', 'manager-comptable', 'comptable', 'surveillant',
        'professeur', 'parent', 'eleve',
    ];

    public function index(Request $request): View
    {
        $this->authorize('modifier-utilisateur');

        $user = null;
        $search = $request->string('search')->trim();

        if ($search !== '') {
            $user = User::query()
                ->with('roles.permissions')
                ->where(function ($query) use ($search) {
                    $query->where('matricule', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                ->first();
        }

        return view('users.roles.index', [
            'user' => $user,
            'search' => $search,
            'roles' => $this->availableRoles(),
            'permissions' => $this->groupedPermissions(),
            'effectivePermissions' => $user?->effectivePermissionNames()->toArray() ?? [],
            'directPermissions' => $user?->directGrantedPermissionNames()->toArray() ?? [],
            'revokedPermissions' => $user?->revokedPermissionNames()->toArray() ?? [],
            'isSuperAdminTarget' => $user?->hasRole('super-admin') ?? false,
        ]);
    }

    public function update(Request $request, User $user, UserPermissionService $service): RedirectResponse
    {
        $this->authorize('modifier-utilisateur', $user);

        // Seul un super-admin peut toucher aux accès d'un compte super-admin, ou
        // attribuer le rôle super-admin. Ce contrôle doit être explicite : Spatie
        // court-circuite les Policies via Gate::before dès que l'acteur possède la
        // permission plate 'modifier-utilisateur' (accordée au rôle admin).
        abort_if(
            $user->hasRole('super-admin') && ! auth()->user()->hasRole('super-admin'),
            403,
            "Seul un super-administrateur peut modifier les accès d'un compte super-administrateur."
        );

        $validated = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => [Rule::in($this->availableRoleNames())],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'confirm_super_admin' => ['nullable', 'in:1'],
        ]);

        $requestedRoles = collect($validated['roles'] ?? [])->unique()->values();
        $requestedPermissions = $request->has('permissions')
            ? collect($validated['permissions'] ?? [])->unique()->values()
            : $user->effectivePermissionNames();

        $this->ensureLastSuperAdminNotRemoved($user, $requestedRoles);
        $this->ensureBusinessRoleTransitionIsSafe($user, $requestedRoles);

        if ($requestedRoles->contains('super-admin') && ! $user->hasRole('super-admin')) {
            abort_unless(auth()->user()->hasRole('super-admin'), 403, "Seul un super-administrateur peut attribuer le rôle Super-Admin.");

            if ($request->input('confirm_super_admin') !== '1') {
                return back()->withErrors(['confirm_super_admin' => 'L’attribution du rôle Super-Admin nécessite une confirmation renforcée.'])->withInput();
            }
        }

        $service->apply($user, $requestedRoles->toArray(), $requestedPermissions->toArray(), auth()->id());

        $this->syncPrimaryRoleColumn($user);

        return redirect()
            ->route('users.roles.index', ['search' => $user->matricule])
            ->with('success', 'Les accès de '.$user->name.' ont été mis à jour.');
    }

    private function availableRoles()
    {
        $roles = Role::query()
            ->whereIn('name', self::SEARCHABLE_ROLES)
            ->with('permissions')
            ->get();

        $order = array_flip(self::SEARCHABLE_ROLES);

        return $roles->sortBy(fn ($role) => $order[$role->name] ?? PHP_INT_MAX)->values();
    }

    private function availableRoleNames(): array
    {
        return self::SEARCHABLE_ROLES;
    }

    private function groupedPermissions(): array
    {
        $modules = config('permissions.modules_map', []);
        $labels = config('permissions.labels', []);
        $all = Permission::all();
        $grouped = [];

        foreach ($modules as $module => $permissionNames) {
            foreach ($permissionNames as $name) {
                $permission = $all->firstWhere('name', $name);
                if (! $permission) {
                    continue;
                }

                $grouped[$module][] = [
                    'name' => $permission->name,
                    'label' => $labels[$permission->name] ?? $permission->name,
                ];
            }
        }

        return $grouped;
    }

    /**
     * Cette page permet d'attribuer/retirer des rôles indépendamment du
     * formulaire d'édition générique (UpdateUserRequest) : elle doit répliquer
     * les mêmes garde-fous métier, sinon elle offre un second chemin pour
     * casser un compte professeur/élève/parent (voir UserRoles).
     */
    private function ensureBusinessRoleTransitionIsSafe(User $user, Collection $requestedRoles): void
    {
        $hasDedicatedProfile = [
            'professeur' => fn () => $user->teacher !== null,
            'eleve' => fn () => $user->registrations()->exists(),
            'parent' => fn () => $user->parentProfile !== null,
        ];

        foreach ($hasDedicatedProfile as $role => $hasProfile) {
            $adding = $requestedRoles->contains($role) && ! $user->hasRole($role);

            if ($adding && ! $hasProfile()) {
                abort(422, "Le rôle {$role} ne peut être attribué que depuis son module dédié (fiche complète requise), pas depuis cette page.");
            }
        }

        if (in_array($user->role, UserRoles::DEDICATED_PROFILE_ROLES, true) && ! $requestedRoles->contains($user->role)) {
            $blockingReason = UserRoles::activeBusinessLinkBlockingRoleChange($user);

            if ($blockingReason) {
                abort(422, $blockingReason);
            }
        }
    }

    private function ensureLastSuperAdminNotRemoved(User $user, \Illuminate\Support\Collection $requestedRoles): void
    {
        if (! $user->hasRole('super-admin')) {
            return;
        }

        if ($requestedRoles->contains('super-admin')) {
            return;
        }

        $remainingSuperAdmins = User::role('super-admin')->where('id', '!=', $user->id)->where('is_active', true)->count();

        if ($remainingSuperAdmins === 0) {
            abort(422, 'Impossible de retirer le dernier Super-Admin actif.');
        }
    }

    private function syncPrimaryRoleColumn(User $user): void
    {
        $user->syncPrimaryRoleColumn();
    }
}
