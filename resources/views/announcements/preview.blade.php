<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Aperçu avant publication') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-100 dark:border-slate-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ $announcement->title }}</h3>

                    <div class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
                        <p><span class="font-semibold">Type :</span> {{ $announcement->displayTypeLabel() }}</p>
                        <p><span class="font-semibold">Priorité :</span> {{ $announcement->priorityLabel() }}</p>
                        <p><span class="font-semibold">Ciblage :</span> {{ ucfirst($announcement->target_mode) }}</p>

                        @if($announcement->classroom)
                            <p><span class="font-semibold">Classe :</span> {{ $announcement->classroom->name }}</p>
                        @endif

                        @if(!empty($announcement->target_roles))
                            <p><span class="font-semibold">Rôles :</span> {{ collect($announcement->target_roles)->map(fn($r) => ucfirst($r))->implode(', ') }}</p>
                        @endif

                        @if(!empty($announcement->target_user_ids))
                            <p><span class="font-semibold">Utilisateurs spécifiques :</span> {{ count($announcement->target_user_ids) }} sélectionné(s)</p>
                        @endif

                        <p><span class="font-semibold">Destinataires estimés :</span> <span class="text-indigo-600 dark:text-indigo-400 font-bold">{{ $recipientCount }}</span></p>

                        <p><span class="font-semibold">Publication :</span>
                            @if($announcement->published_at && $announcement->published_at->isFuture())
                                {{ $announcement->published_at->format('d/m/Y H:i') }}
                            @else
                                Immédiate
                            @endif
                        </p>

                        @if($announcement->expires_at)
                            <p><span class="font-semibold">Expiration :</span> {{ $announcement->expires_at->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>

                    <div class="mt-6 p-4 bg-gray-50 dark:bg-slate-700/50 rounded-md text-gray-800 dark:text-gray-200">
                        {!! nl2br(e($announcement->content)) !!}
                    </div>

                    @if($announcement->attachment)
                        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                            Pièce jointe : <a href="{{ Storage::url($announcement->attachment) }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 underline">Voir</a>
                        </p>
                    @endif
                </div>
            </div>

            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-900 rounded-lg p-4 text-amber-800 dark:text-amber-200 text-sm">
                Vérifiez les destinataires avant de confirmer. Une fois publiée, la notification sera visible par les utilisateurs concernés.
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('announcements.edit', $announcement) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 dark:bg-slate-700 dark:text-gray-200 dark:hover:bg-slate-600">
                    {{ __('Modifier') }}
                </a>

                <form action="{{ route('announcements.publish', $announcement) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        {{ __('Confirmer et publier') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
