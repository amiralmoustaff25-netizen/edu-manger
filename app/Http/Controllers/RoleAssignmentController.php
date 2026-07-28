<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserPermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        if ($requestedRoles->contains('super-admin') && ! $user->hasRole('super-admin') && $request->input('confirm_super_admin') !== '1') {
            return back()->withErrors(['confirm_super_admin' => 'L’attribution du rôle Super-Admin nécessite une confirmation renforcée.'])->withInput();
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
}
