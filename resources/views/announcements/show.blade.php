<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Détail de la notification') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-100 dark:border-slate-700">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="px-2 py-1 text-xs font-semibold rounded-md
                            @if($announcement->type === 'urgent') bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300
                            @elseif($announcement->type === 'important') bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                            @else bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 @endif">
                            {{ $announcement->displayTypeLabel() }}
                        </span>
                        <span class="px-2 py-1 text-xs font-semibold rounded-md bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-gray-300">
                            {{ $announcement->status }}
                        </span>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ $announcement->title }}</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700 dark:text-gray-300 mb-6">
                        <p><span class="font-semibold">Auteur :</span> {{ $announcement->author?->name ?? 'N/A' }}</p>
                        <p><span class="font-semibold">Date de création :</span> {{ $announcement->created_at?->format('d/m/Y H:i') }}</p>
                        <p><span class="font-semibold">Publication :</span> {{ $announcement->published_at?->format('d/m/Y H:i') ?? 'Non publiée' }}</p>
                        <p><span class="font-semibold">Expiration :</span> {{ $announcement->expires_at?->format('d/m/Y H:i') ?? 'Aucune' }}</p>
                        <p><span class="font-semibold">Destinataires :</span> {{ $announcement->recipient_count }}</p>
                        <p><span class="font-semibold">Lectures :</span> {{ $announcement->read_count }}</p>
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-slate-700/50 rounded-md text-gray-800 dark:text-gray-200">
                        {!! nl2br(e($announcement->content)) !!}
                    </div>

                    @if($announcement->attachment)
                        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                            Pièce jointe : <a href="{{ Storage::url($announcement->attachment) }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 underline">Voir le fichier</a>
                        </p>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('announcements.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 dark:bg-slate-700 dark:text-gray-200 dark:hover:bg-slate-600">
                    {{ __('Retour à l\'historique') }}
                </a>

                @if($announcement->isDraft() || $announcement->isScheduled())
                    <a href="{{ route('announcements.edit', $announcement) }}" class="px-4 py-2 bg-indigo-100 text-indigo-700 rounded-md hover:bg-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-300 dark:hover:bg-indigo-900/60">
                        {{ __('Modifier') }}
                    </a>
                @endif

                @if(! $announcement->isArchived())
                    <form action="{{ route('announcements.archive', $announcement) }}" method="POST" class="inline" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Archiver', message: 'Cette notification sera archivée.', confirmLabel: 'Archiver' })">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-amber-100 text-amber-700 rounded-md hover:bg-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:hover:bg-amber-900/60">
                            {{ __('Archiver') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
