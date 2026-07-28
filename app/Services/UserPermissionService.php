<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPermissionOverride;
use App\Models\UserRoleHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class UserPermissionService
{
    public function apply(User $user, array $requestedRoles, array $requestedEffectivePermissions, ?int $changedBy): void
    {
        DB::transaction(function () use ($user, $requestedRoles, $requestedEffectivePermissions, $changedBy) {
            $previousRoles = $user->roles->pluck('name');
            $previousEffective = $user->effectivePermissionNames();

            $requestedRoleCollection = collect($requestedRoles)->unique()->values();
            $requestedEffectiveCollection = collect($requestedEffectivePermissions)->unique()->values();

            $user->syncRoles($requestedRoleCollection->toArray());
            $user->load('roles.permissions');

            $this->syncDirectPermissionsAndRevokes($user, $requestedRoleCollection, $requestedEffectiveCollection, $changedBy);

            $user->refresh();
            $newEffective = $user->effectivePermissionNames();

            $this->logChanges($user, $previousRoles, $requestedRoleCollection, $previousEffective, $newEffective, $changedBy);

            $user->forgetPermissionCache();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    private function syncDirectPermissionsAndRevokes(User $user, Collection $requestedRoles, Collection $requestedEffective, ?int $changedBy): void
    {
        $allKnown = collect(config('permissions.modules_map', []))->flatten()->unique()->values();

        $rolePermissionNames = Permission::whereHas('roles', fn ($query) => $query->whereIn('name', $requestedRoles))
            ->pluck('name')
            ->unique()
            ->values();

        $currentDirectNames = $user->getDirectPermissions()->pluck('name');
        $currentRevokeNames = $user->permissionOverrides()
            ->where('type', 'revoke')
            ->with('permission')
            ->get()
            ->pluck('permission.name');

        $directToSync = $currentDirectNames->filter(fn ($name) => ! $allKnown->contains($name))->values();
        $revokesToKeep = $currentRevokeNames->filter(fn ($name) => $allKnown->contains($name))->values();

        foreach ($allKnown as $name) {
            $inherited = $rolePermissionNames->contains($name);
            $checked = $requestedEffective->contains($name);

            if ($checked) {
                if (! $inherited) {
                    $directToSync->push($name);
                }
                $revokesToKeep = $revokesToKeep->reject(fn ($r) => $r === $name)->values();
            } else {
                if ($inherited) {
                    $revokesToKeep->push($name);
                }
                $directToSync = $directToSync->reject(fn ($d) => $d === $name)->values();
            }
        }

        $user->syncPermissions($directToSync->unique()->values()->toArray());

        $user->permissionOverrides()
            ->where('type', 'revoke')
            ->whereHas('permission', fn ($query) => $query->whereIn('name', $allKnown))
            ->delete();

        $revokesToKeep = $revokesToKeep->unique()->values();
        foreach ($revokesToKeep as $name) {
            $permission = Permission::findByName($name);
            $user->permissionOverrides()->create([
                'permission_id' => $permission->id,
                'type' => 'revoke',
                'created_by' => $changedBy,
            ]);
        }
    }

    private function logChanges(
        User $user,
        Collection $previousRoles,
        Collection $newRoles,
        Collection $previousEffective,
        Collection $newEffective,
        ?int $changedBy
    ): void {
        $now = now();

        foreach ($newRoles->diff($previousRoles) as $role) {
            UserRoleHistory::create([
                'user_id' => $user->id,
                'changed_by' => $changedBy,
                'action' => 'assigned',
                'role' => $role,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($previousRoles->diff($newRoles) as $role) {
            UserRoleHistory::create([
                'user_id' => $user->id,
                'changed_by' => $changedBy,
                'action' => 'removed',
                'role' => $role,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($newEffective->diff($previousEffective) as $permission) {
            UserRoleHistory::create([
                'user_id' => $user->id,
                'changed_by' => $changedBy,
                'action' => 'permission_granted',
                'permission' => $permission,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($previousEffective->diff($newEffective) as $permission) {
            UserRoleHistory::create([
                'user_id' => $user->id,
                'changed_by' => $changedBy,
                'action' => 'permission_revoked',
                'permission' => $permission,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
