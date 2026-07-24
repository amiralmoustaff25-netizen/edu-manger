@php
    $targetMode = old('target_mode', $announcement->target_mode ?? 'all');
@endphp

<div x-data="{ targetMode: '{{ $targetMode }}', action: 'publish' }">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="md:col-span-2">
            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Titre</label>
            <input type="text" id="title" name="title" value="{{ old('title', $announcement->title ?? '') }}" required
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <x-input-error :messages="$errors->get('title')" class="mt-2" />
        </div>

        <div class="md:col-span-2">
            <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Message</label>
            <textarea id="content" name="content" rows="5" required
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('content', $announcement->content ?? '') }}</textarea>
            <x-input-error :messages="$errors->get('content')" class="mt-2" />
        </div>

        <div>
            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
            <select id="type" name="type" required
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach(['information' => 'Information', 'important' => 'Important', 'urgent' => 'Urgent', 'reminder' => 'Rappel', 'announcement' => 'Annonce'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', $announcement->type ?? 'information') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('type')" class="mt-2" />
        </div>

        <div>
            <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Priorité</label>
            <select id="priority" name="priority" required
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach(['normal' => 'Normale', 'important' => 'Importante', 'urgent' => 'Urgente'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('priority', $announcement->priority ?? 'normal') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('priority')" class="mt-2" />
        </div>
    </div>

    <div class="mt-6 border-t border-gray-200 dark:border-slate-700 pt-6">
        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Ciblage des destinataires</h3>

        <div class="space-y-3">
            <label class="flex items-center">
                <input type="radio" name="target_mode" value="all" x-model="targetMode" class="text-indigo-600 focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Tous les utilisateurs</span>
            </label>

            <label class="flex items-center">
                <input type="radio" name="target_mode" value="roles" x-model="targetMode" class="text-indigo-600 focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Par rôle(s)</span>
            </label>

            <label class="flex items-center">
                <input type="radio" name="target_mode" value="classroom" x-model="targetMode" class="text-indigo-600 focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Par classe</span>
            </label>

            <label class="flex items-center">
                <input type="radio" name="target_mode" value="users" x-model="targetMode" class="text-indigo-600 focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Utilisateurs spécifiques</span>
            </label>
        </div>

        <div x-show="targetMode === 'roles'" class="mt-4" x-cloak>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rôles concernés</label>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                @foreach($roles as $role)
                    <label class="flex items-center">
                        <input type="checkbox" name="target_roles[]" value="{{ $role }}" @checked(in_array($role, old('target_roles', $announcement->target_roles ?? []))) class="text-indigo-600 focus:ring-indigo-500 rounded">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ ucfirst($role) }}</span>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('target_roles')" class="mt-2" />
        </div>

        <div x-show="targetMode === 'classroom'" class="mt-4 space-y-4" x-cloak>
            <div>
                <label for="classroom_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Classe</label>
                <select id="classroom_id" name="classroom_id"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Sélectionner une classe</option>
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected(old('classroom_id', $announcement->classroom_id ?? '') == $classroom->id)>{{ $classroom->name }} ({{ $classroom->schoolYear?->year_string ?? 'N/A' }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('classroom_id')" class="mt-2" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Destinataires dans la classe</label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    @foreach(['eleve' => 'Élèves', 'professeur' => 'Professeurs', 'parent' => 'Parents'] as $value => $label)
                        <label class="flex items-center">
                            <input type="checkbox" name="target_roles[]" value="{{ $value }}" @checked(in_array($value, old('target_roles', $announcement->target_roles ?? []))) class="text-indigo-600 focus:ring-indigo-500 rounded">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Vous pouvez cocher plusieurs rôles. Les autres rôles sélectionnés seront ajoutés globalement.</p>
                <x-input-error :messages="$errors->get('target_roles')" class="mt-2" />
            </div>
        </div>

        <div x-show="targetMode === 'users'" class="mt-4" x-cloak>
            <label for="target_user_ids" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Utilisateurs</label>
            <select id="target_user_ids" name="target_user_ids[]" multiple size="8"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected(in_array($user->id, old('target_user_ids', $announcement->target_user_ids ?? [])))>{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Maintenez Ctrl/Cmd pour sélectionner plusieurs utilisateurs.</p>
            <x-input-error :messages="$errors->get('target_user_ids')" class="mt-2" />
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6 border-t border-gray-200 dark:border-slate-700 pt-6">
        <div>
            <label for="published_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date de publication</label>
            <input type="datetime-local" id="published_at" name="published_at"
                value="{{ old('published_at', isset($announcement) && $announcement->published_at ? $announcement->published_at->format('Y-m-d\\TH:i') : '') }}"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Laissez vide pour publier immédiatement.</p>
            <x-input-error :messages="$errors->get('published_at')" class="mt-2" />
        </div>

        <div>
            <label for="expires_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date d'expiration</label>
            <input type="datetime-local" id="expires_at" name="expires_at"
                value="{{ old('expires_at', isset($announcement) && $announcement->expires_at ? $announcement->expires_at->format('Y-m-d\\TH:i') : '') }}"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Facultatif.</p>
            <x-input-error :messages="$errors->get('expires_at')" class="mt-2" />
        </div>

        <div>
            <label for="attachment" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pièce jointe</label>
            <input type="file" id="attachment" name="attachment"
                class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-900/40 dark:file:text-indigo-300 hover:file:bg-indigo-100">
            @if(isset($announcement) && $announcement->attachment)
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Fichier actuel : <a href="{{ Storage::url($announcement->attachment) }}" target="_blank" class="underline">Voir</a></p>
            @endif
            <x-input-error :messages="$errors->get('attachment')" class="mt-2" />
        </div>
    </div>
</div>
