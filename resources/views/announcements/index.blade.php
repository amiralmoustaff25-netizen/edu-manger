<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Notifications & Communications') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ showForm: {{ $errors->any() ? 'true' : 'false' }} }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @can('creer-notification')
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">Notifications & Communications</h3>
                    <button type="button" x-on:click="showForm = !showForm" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                        <span x-show="!showForm">+ {{ __('Nouvelle notification') }}</span>
                        <span x-show="showForm" x-cloak>{{ __('Fermer le formulaire') }}</span>
                    </button>
                </div>

                <div x-show="showForm" x-cloak class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-100 dark:border-slate-700">
                        <form action="{{ route('announcements.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            @include('announcements._form')

                            <div class="mt-8 flex flex-wrap gap-3 border-t border-gray-200 dark:border-slate-700 pt-6">
                                <button type="submit" name="action" value="draft" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 dark:bg-slate-700 dark:text-gray-200 dark:hover:bg-slate-600">
                                    {{ __('Enregistrer le brouillon') }}
                                </button>
                                <button type="submit" name="action" value="preview" class="px-4 py-2 bg-indigo-100 text-indigo-700 rounded-md hover:bg-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-300 dark:hover:bg-indigo-900/60">
                                    {{ __('Prévisualiser') }}
                                </button>
                                <button type="submit" name="action" value="schedule" class="px-4 py-2 bg-amber-100 text-amber-700 rounded-md hover:bg-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:hover:bg-amber-900/60">
                                    {{ __('Programmer') }}
                                </button>
                                <button type="submit" name="action" value="publish" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                    {{ __('Publier maintenant') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endcan

            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">Historique</h3>

            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('announcements.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher..." aria-label="Rechercher une annonce"
                            class="w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <select name="status" aria-label="Filtrer par statut" class="w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tous les statuts</option>
                            @foreach(['draft' => 'Brouillon', 'scheduled' => 'Programmée', 'published' => 'Publiée', 'archived' => 'Archivée'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <select name="type" aria-label="Filtrer par type" class="w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tous les types</option>
                            @foreach(['information' => 'Information', 'important' => 'Important', 'urgent' => 'Urgent', 'reminder' => 'Rappel', 'announcement' => 'Annonce'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <select name="role" aria-label="Filtrer par rôle" class="w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tous les rôles</option>
                            @foreach($roles ?? [] as $role)
                                <option value="{{ $role }}" @selected(request('role') === $role)>{{ ucfirst($role) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm">Filtrer</button>
                        <a href="{{ route('announcements.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 dark:bg-slate-700 dark:text-gray-200 dark:hover:bg-slate-600 text-sm">Réinitialiser</a>
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Titre</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Ciblage</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Statut</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Publication</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Destinataires</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Lectures</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                            @forelse($announcements as $announcement)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200 font-medium">{{ Str::limit($announcement->title, 40) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $announcement->displayTypeLabel() }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ ucfirst($announcement->target_mode) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                        <span class="px-2 py-0.5 rounded text-xs font-semibold
                                            @if($announcement->status === 'published') bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300
                                            @elseif($announcement->status === 'scheduled') bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                                            @elseif($announcement->status === 'draft') bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-gray-300
                                            @else bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300 @endif">
                                            {{ $announcement->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $announcement->published_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $announcement->recipient_count }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $announcement->read_count }}</td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <a href="{{ route('announcements.show', $announcement) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Voir</a>
                                        @if($announcement->isDraft() || $announcement->isScheduled())
                                            <a href="{{ route('announcements.edit', $announcement) }}" class="ml-2 text-indigo-600 dark:text-indigo-400 hover:underline">Modifier</a>
                                        @endif
                                        @if($announcement->isDraft() || $announcement->isScheduled())
                                            <form action="{{ route('announcements.publish', $announcement) }}" method="POST" class="inline ml-2">
                                                @csrf
                                                <button type="submit" class="text-green-600 dark:text-green-400 hover:underline">Publier</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Aucune notification trouvée.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-100 dark:border-slate-700">
                    {{ $announcements->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
