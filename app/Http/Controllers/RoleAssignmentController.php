<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserRoleHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('modifier-utilisateur', $user);

        $validated = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => [Rule::in($this->availableRoleNames())],
            'direct_permissions' => ['nullable', 'array'],
            'direct_permissions.*' => ['string', 'exists:permissions,name'],
            'confirm_super_admin' => ['nullable', 'in:1'],
        ]);

        $requestedRoles = collect($validated['roles'] ?? [])->unique()->values();
        $requestedPermissions = collect($validated['direct_permissions'] ?? [])->unique()->values();

        $this->ensureLastSuperAdminNotRemoved($user, $requestedRoles);

        if ($requestedRoles->contains('super-admin') && ! $user->hasRole('super-admin') && $request->input('confirm_super_admin') !== '1') {
            return back()->withErrors(['confirm_super_admin' => 'L’attribution du rôle Super-Admin nécessite une confirmation renforcée.'])->withInput();
        }

        DB::transaction(function () use ($user, $requestedRoles, $requestedPermissions) {
            $previousRoles = $user->roles->pluck('name');
            $previousDirectPermissions = $user->permissions->pluck('name');

            $user->syncRoles($requestedRoles->toArray());
            $user->syncPermissions($requestedPermissions->toArray());

            $this->syncPrimaryRoleColumn($user);

            $this->logChanges($user, $previousRoles, $requestedRoles, $previousDirectPermissions, $requestedPermissions);

            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        });

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
        $primary = $user->roles->first()?->name;

        if ($primary && in_array($primary, self::SEARCHABLE_ROLES, true)) {
            $user->update(['role' => $primary]);
        }
    }

    private function logChanges(
        User $user,
        \Illuminate\Support\Collection $previousRoles,
        \Illuminate\Support\Collection $newRoles,
        \Illuminate\Support\Collection $previousDirectPermissions,
        \Illuminate\Support\Collection $newDirectPermissions
    ): void {
        $now = now();

        foreach ($newRoles->diff($previousRoles) as $role) {
            UserRoleHistory::create([
                'user_id' => $user->id,
                'changed_by' => auth()->id(),
                'action' => 'assigned',
                'role' => $role,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($previousRoles->diff($newRoles) as $role) {
            UserRoleHistory::create([
                'user_id' => $user->id,
                'changed_by' => auth()->id(),
                'action' => 'removed',
                'role' => $role,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($newDirectPermissions->diff($previousDirectPermissions) as $permission) {
            UserRoleHistory::create([
                'user_id' => $user->id,
                'changed_by' => auth()->id(),
                'action' => 'direct_permission_assigned',
                'permission' => $permission,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($previousDirectPermissions->diff($newDirectPermissions) as $permission) {
            UserRoleHistory::create([
                'user_id' => $user->id,
                'changed_by' => auth()->id(),
                'action' => 'direct_permission_removed',
                'permission' => $permission,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
