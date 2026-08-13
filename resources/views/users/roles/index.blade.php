<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Attribution des rôles et accès</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Recherchez un utilisateur, consultez ses rôles et modifiez ses accès.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 dark:bg-green-900/20 p-4 text-sm text-green-800 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 dark:bg-red-900/20 p-4 text-sm text-red-800 dark:text-red-200">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                <form method="GET" action="{{ route('users.roles.index') }}" class="flex flex-col gap-4 md:flex-row md:items-end">
                    <div class="flex-1">
                        <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rechercher par matricule, nom, prénom ou email</label>
                        <input id="search" name="search" value="{{ $search }}" type="text" placeholder="Ex : PROF-260015" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">Rechercher</x-primary-button>
                        <a href="{{ route('users.roles.index') }}" class="rounded-md border border-gray-300 dark:border-slate-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700">Réinitialiser</a>
                    </div>
                </form>
            </section>

            @if ($search !== '' && ! $user)
                <div class="rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-900/20 p-6 text-center text-sm text-amber-800 dark:text-amber-200">
                    Aucun utilisateur trouvé pour <strong>{{ $search }}</strong>.
                </div>
            @endif

            @if ($user)
                <form id="role-assignment-form" method="POST" action="{{ route('users.roles.update', $user) }}" x-data="roleAssignment()" x-init="init()" @submit="return prepareSubmit($event)">
                    @csrf
                    @method('PATCH')

                    <section class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $user->name }}</h3>
                                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400 space-y-0.5">
                                    <p>Matricule : <span class="font-mono">{{ $user->matricule ?? '—' }}</span></p>
                                    <p>Email : {{ $user->email ?? '—' }}</p>
                                    <p>Rôle principal : <span class="font-medium text-gray-700 dark:text-gray-300">{{ $user->getRoleNames()->first() ?? 'Sans rôle' }}</span></p>
                                </div>
                            </div>
                            <div class="flex flex-col items-start gap-2 md:items-end">
                                <span @class([
                                    'inline-flex items-center rounded-full px-3 py-0.5 text-xs font-medium',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200' => $user->is_active,
                                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' => ! $user->is_active,
                                ])>
                                    {{ $user->is_active ? 'Compte actif' : 'Compte désactivé' }}
                                </span>
                                @if (! $user->is_active)
                                    <p class="text-xs text-amber-600 dark:text-amber-300">L’utilisateur ne pourra pas se connecter même s’il possède des rôles.</p>
                                @endif
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Rôles</h3>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($roles as $role)
                                @php
                                    $sensitive = in_array($role->name, config('permissions.sensitive_roles', []), true);
                                    $rolePermissions = $role->permissions->pluck('name');
                                @endphp
                                <label class="relative flex items-start gap-3 rounded-lg border border-gray-200 dark:border-slate-700 p-4 cursor-pointer hover:border-indigo-300 dark:hover:border-indigo-500">
                                    <input type="checkbox" name="roles[]" value="{{ $role->name }}" class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        :checked="roles.includes('{{ $role->name }}')"
                                        @change="toggleRole('{{ $role->name }}', $event.target.checked, @js($rolePermissions->values()))"
                                        {{ $role->name === 'super-admin' && ! $user->hasRole('super-admin') ? 'data-super-admin=1' : '' }}>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-white">{{ ucfirst(str_replace('-', ' ', $role->name)) }}</p>
                                        @if ($sensitive)
                                            <p class="mt-1 text-xs text-amber-600 dark:text-amber-300">Accès sensibles</p>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <input type="hidden" name="confirm_super_admin" :value="confirmSuperAdmin ? '1' : ''">
                    </section>

                    <section class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700" x-data="{ open: true }">
                        <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-left">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Permissions détaillées</h3>
                            <span x-text="open ? '−' : '+'" class="text-xl text-gray-500 dark:text-gray-400"></span>
                        </button>
                        <div x-show="open" x-collapse class="mt-4 space-y-6">
                            @if ($isSuperAdminTarget)
                                <div class="rounded border border-amber-200 bg-amber-50 dark:bg-amber-900/20 p-3 text-sm text-amber-800 dark:text-amber-200">
                                    Cet utilisateur est Super-Admin : toutes les permissions sont effectives par bypass global. Les cases ci-dessous sont données à titre indicatif.
                                </div>
                            @endif
                            @foreach ($permissions as $module => $modulePermissions)
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">{{ config('permissions.modules.'.$module, $module) }}</h4>
                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                        @foreach ($modulePermissions as $permission)
                                            @php
                                                $isSensitive = in_array($permission['name'], config('permissions.sensitive_permissions', []), true);
                                            @endphp
                                            <label class="flex items-start gap-2 rounded border border-gray-200 dark:border-slate-700 p-2 text-sm {{ $isSensitive ? 'bg-amber-50 dark:bg-amber-900/10' : '' }}">
                                                <input type="checkbox" name="permissions[]" value="{{ $permission['name'] }}" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                    :checked="isChecked('{{ $permission['name'] }}')"
                                                    @change="togglePermission('{{ $permission['name'] }}', $event.target.checked)"
                                                    :disabled="isSuperAdminTarget">
                                                <span class="text-gray-700 dark:text-gray-300">{{ $permission['label'] }}</span>
                                                <span class="ml-auto text-xs text-gray-500 dark:text-gray-400" x-text="originLabel('{{ $permission['name'] }}')"></span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Résumé des modifications</h3>
                            <span class="text-sm font-medium text-indigo-600 dark:text-indigo-300" x-text="(addedRoles.length + removedRoles.length + addedPermissions.length + removedPermissions.length) + ' modification(s) en attente'"></span>
                        </div>
                        <div class="space-y-4">
                            <template x-if="addedRoles.length || removedRoles.length || addedPermissions.length || removedPermissions.length">
                                <div class="space-y-3">
                                    <template x-if="addedRoles.length">
                                        <div class="rounded border border-green-200 bg-green-50 dark:bg-green-900/20 p-3">
                                            <p class="text-sm font-semibold text-green-900 dark:text-green-200">Rôles ajoutés</p>
                                            <ul class="mt-1 text-sm text-green-800 dark:text-green-300 list-disc pl-5">
                                                <template x-for="role in addedRoles" :key="role"><li x-text="label(role)"></li></template>
                                            </ul>
                                        </div>
                                    </template>
                                    <template x-if="removedRoles.length">
                                        <div class="rounded border border-red-200 bg-red-50 dark:bg-red-900/20 p-3">
                                            <p class="text-sm font-semibold text-red-900 dark:text-red-200">Rôles retirés</p>
                                            <ul class="mt-1 text-sm text-red-800 dark:text-red-300 list-disc pl-5">
                                                <template x-for="role in removedRoles" :key="role"><li x-text="label(role)"></li></template>
                                            </ul>
                                        </div>
                                    </template>
                                    <template x-if="addedPermissions.length">
                                        <div class="rounded border border-green-200 bg-green-50 dark:bg-green-900/20 p-3">
                                            <p class="text-sm font-semibold text-green-900 dark:text-green-200">Permissions ajoutées</p>
                                            <ul class="mt-1 text-sm text-green-800 dark:text-green-300 list-disc pl-5">
                                                <template x-for="name in addedPermissions" :key="name"><li x-text="permissionLabel(name)"></li></template>
                                            </ul>
                                        </div>
                                    </template>
                                    <template x-if="removedPermissions.length">
                                        <div class="rounded border border-red-200 bg-red-50 dark:bg-red-900/20 p-3">
                                            <p class="text-sm font-semibold text-red-900 dark:text-red-200">Permissions retirées</p>
                                            <ul class="mt-1 text-sm text-red-800 dark:text-red-300 list-disc pl-5">
                                                <template x-for="name in removedPermissions" :key="name"><li x-text="permissionLabel(name)"></li></template>
                                            </ul>
                                        </div>
                                    </template>
                                    <template x-if="addedSensitive.length">
                                        <div class="rounded border border-amber-300 bg-amber-100 dark:bg-amber-900/30 p-3 text-sm text-amber-900 dark:text-amber-200">
                                            <p class="font-semibold">Attention : rôles sensibles accordés</p>
                                            <ul class="mt-1 list-disc pl-5">
                                                <template x-for="role in addedSensitive" :key="role"><li x-text="label(role)"></li></template>
                                            </ul>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="!(addedRoles.length || removedRoles.length || addedPermissions.length || removedPermissions.length)">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Aucune modification en attente.</p>
                            </template>
                        </div>
                    </section>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('users.roles.index') }}" class="rounded-md border border-gray-300 dark:border-slate-600 px-5 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700">Annuler</a>
                        <button type="submit" class="rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Enregistrer les modifications</button>
                    </div>
                </form>

                @php
                    $history = \App\Models\UserRoleHistory::where('user_id', $user->id)->latest()->take(20)->with('changedBy')->get();
                @endphp
                @if ($history->isNotEmpty())
                    <section class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 mt-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Historique des accès</h3>
                        <ul class="space-y-3">
                            @foreach ($history as $event)
                                <li class="border-l-4 border-indigo-500 dark:border-indigo-400 pl-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        @if ($event->action === 'assigned')
                                            Rôle <span class="font-semibold">{{ $event->role }}</span> attribué
                                        @elseif ($event->action === 'removed')
                                            Rôle <span class="font-semibold">{{ $event->role }}</span> retiré
                                        @elseif ($event->action === 'direct_permission_assigned')
                                            Permission <span class="font-semibold">{{ $event->permission }}</span> ajoutée exceptionnellement
                                        @elseif ($event->action === 'direct_permission_removed')
                                            Permission <span class="font-semibold">{{ $event->permission }}</span> retirée exceptionnellement
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $event->changedBy?->name ?? 'Système' }} — {{ $event->created_at->format('d/m/Y H:i') }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            @endif
        </div>
    </div>

    <script>
        window.roleLabels = @js($roles->mapWithKeys(fn ($role) => [$role->name => ucfirst(str_replace('-', ' ', $role->name))]));
        window.rolePermissions = @js($roles->mapWithKeys(fn ($role) => [$role->name => $role->permissions->pluck('name')->values()]));
        window.permissionLabels = @js(collect($permissions)->flatten(1)->mapWithKeys(fn ($p) => [$p['name'] => $p['label']]));
        window.userRoles = @js($user?->getRoleNames()->toArray() ?? []);
        window.effectivePermissions = @js($effectivePermissions);
        window.directPermissions = @js($directPermissions);
        window.revokedPermissions = @js($revokedPermissions);
        window.isSuperAdminTarget = @js($isSuperAdminTarget);
        window.sensitiveRoles = @js(config('permissions.sensitive_roles', []));
        window.sensitivePermissions = @js(config('permissions.sensitive_permissions', []));

        function roleAssignment() {
            return {
                roles: [...window.userRoles],
                initialRoles: [...window.userRoles],
                rolePermissions: window.rolePermissions,
                permissionLabels: window.permissionLabels,
                sensitiveRoles: window.sensitiveRoles,
                sensitivePermissions: window.sensitivePermissions,
                isSuperAdminTarget: window.isSuperAdminTarget,
                manualPermissions: new Set(window.directPermissions),
                manualRevokes: new Set(window.revokedPermissions),
                confirmSuperAdmin: false,
                addedRoles: [],
                removedRoles: [],
                addedPermissions: [],
                removedPermissions: [],
                addedSensitive: [],
                init() {
                    this.computeDelta();
                },
                get inheritedPermissions() {
                    const perms = new Set();
                    this.roles.forEach(r => (this.rolePermissions[r] || []).forEach(p => perms.add(p)));
                    return perms;
                },
                get effectivePermissions() {
                    if (this.isSuperAdminTarget) {
                        return new Set(Object.keys(this.permissionLabels));
                    }
                    const inherited = this.inheritedPermissions;
                    const effective = new Set([...inherited, ...this.manualPermissions]);
                    this.manualRevokes.forEach(p => effective.delete(p));
                    return effective;
                },
                isChecked(name) {
                    return this.effectivePermissions.has(name);
                },
                isInherited(name) {
                    return this.inheritedPermissions.has(name);
                },
                originLabel(name) {
                    if (this.isSuperAdminTarget) return 'Super-Admin';
                    if (this.isChecked(name)) {
                        return this.isInherited(name) ? 'rôle' : 'personnalisé';
                    }
                    if (this.isInherited(name)) return 'restreint manuellement';
                    return '';
                },
                toggleRole(role, checked, perms) {
                    if (checked && !this.roles.includes(role)) this.roles.push(role);
                    if (!checked) this.roles = this.roles.filter(r => r !== role);
                    if (role === 'super-admin' && checked && !this.initialRoles.includes('super-admin')) {
                        this.confirmSuperAdmin = confirm('Vous allez attribuer le rôle Super-Admin. Confirmez-vous ?');
                    }
                    this.computeDelta();
                },
                togglePermission(name, checked) {
                    const inherited = this.isInherited(name);
                    if (checked) {
                        this.manualRevokes.delete(name);
                        if (!inherited) this.manualPermissions.add(name);
                    } else {
                        this.manualPermissions.delete(name);
                        if (inherited) this.manualRevokes.add(name);
                    }
                    this.computeDelta();
                },
                computeDelta() {
                    this.addedRoles = this.roles.filter(r => !this.initialRoles.includes(r));
                    this.removedRoles = this.initialRoles.filter(r => !this.roles.includes(r));

                    const current = this.effectivePermissions;
                    const initial = new Set(window.effectivePermissions);
                    this.addedPermissions = [...current].filter(p => !initial.has(p));
                    this.removedPermissions = [...initial].filter(p => !current.has(p));
                    this.addedSensitive = this.addedRoles.filter(r => this.sensitiveRoles.includes(r));
                },
                label(role) {
                    return window.roleLabels[role] || role;
                },
                permissionLabel(name) {
                    return this.permissionLabels[name] || name;
                },
                prepareSubmit(e) {
                    if (this.addedSensitive.length && !confirm('Des rôles sensibles vont être attribués. Confirmez-vous l’enregistrement ?')) {
                        return false;
                    }
                    return true;
                }
            };
        }
    </script>
</x-app-layout>
