@csrf

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">Nom complet</label>
        <input id="name" name="name" value="{{ old('name', $user->name) }}" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
        <input id="email" name="email" value="{{ old('email', $user->email) }}" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div>
        <label for="role" class="block text-sm font-medium text-gray-700">Rôle</label>
        <select id="role" name="role" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach($roles as $role)
                <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>{{ $role }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('role')" class="mt-2" />
    </div>

    <div>
        <label for="contract_started_at" class="block text-sm font-medium text-gray-700">Début de contrat</label>
        <input id="contract_started_at" name="contract_started_at" value="{{ old('contract_started_at', optional($user->contract_started_at)->format('Y-m-d')) }}" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('contract_started_at')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true)) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
            <span class="text-sm font-medium text-gray-700">Compte actif</span>
        </label>
    </div>
</div>

@if(! $user->exists)
    <div class="mt-5 rounded-md bg-indigo-50 p-4 text-sm text-indigo-800">
        Le matricule sera généré automatiquement et le mot de passe temporaire sera <strong>password</strong>.
    </div>
@endif

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
        {{ $user->exists ? 'Enregistrer les modifications' : 'Créer le compte' }}
    </button>
    <a href="{{ route('users.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Annuler</a>
</div>
