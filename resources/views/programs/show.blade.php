<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">{{ $program->subject?->nom }} – {{ $program->classroom?->name }}</h2>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                    Année : {{ $program->schoolYear?->year_string }} – Enseignant : {{ $program->teacher?->name }} – Statut :
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-slate-700 dark:text-gray-200">{{ $program->status }}</span>
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('programs.index') }}" class="px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-slate-600 dark:text-gray-200 dark:hover:bg-slate-700 text-sm font-semibold">
                    Retour à la liste
                </a>

                @can('update', $program)
                    <a href="{{ route('programs.edit', $program) }}" class="px-4 py-2 rounded-md bg-yellow-500 text-white hover:bg-yellow-600 text-sm font-semibold">
                        Modifier
                    </a>
                @endcan

                @can('delete', $program)
                    <form action="{{ route('programs.destroy', $program) }}" method="POST" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Supprimer le programme', message: 'Le programme {{ addslashes((string) $program->subject?->nom) }} – {{ addslashes((string) $program->classroom?->name) }} sera supprimé définitivement.', confirmLabel: 'Supprimer' })">
                        @csrf
                        @method('DELETE')
                        <x-danger-button type="submit">Supprimer</x-danger-button>
                    </form>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @php
                $canSubmit = auth()->user()->can('update', $program) && \App\Support\ProgramStatus::canTransition($program->status, 'soumis');
                $canValidateDirecteur = auth()->user()->can('validateDirecteur', $program);
                $canReject = auth()->user()->can('reject', $program);
            @endphp

            @if($canSubmit || $canValidateDirecteur || $canReject)
                <x-card class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Workflow</h3>
                    <div class="flex flex-wrap items-end gap-3">
                        @if($canSubmit)
                            <form action="{{ route('programs.submit', $program) }}" method="POST" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Soumettre le programme', message: 'Le programme sera soumis à l’administrateur pour validation.', confirmLabel: 'Soumettre' })">
                                @csrf
                                <x-primary-button type="submit">Soumettre</x-primary-button>
                            </form>
                        @endif

                        @if($canValidateDirecteur)
                            <form action="{{ route('programs.validate-directeur', $program) }}" method="POST" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Valider le programme', message: 'Le programme sera validé définitivement par l’administrateur.', confirmLabel: 'Valider' })">
                                @csrf
                                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-green-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 transition">
                                    Valider (Administrateur)
                                </button>
                            </form>
                        @endif

                        @if($canReject)
                            <form action="{{ route('programs.reject', $program) }}" method="POST" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Rejeter le programme', message: 'Le programme sera rejeté. Le motif saisi sera conservé.', confirmLabel: 'Rejeter' })" class="flex flex-wrap items-center gap-2">
                                @csrf
                                <label for="motif-rejet" class="sr-only">Motif du rejet</label>
                                <input id="motif-rejet" type="text" name="motif" required placeholder="Motif du rejet..." class="px-3 py-2 rounded-md border border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                <x-danger-button type="submit">Rejeter</x-danger-button>
                            </form>
                        @endif
                    </div>
                </x-card>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <x-card title="Chapitres">
                    @if($program->chapters && $program->chapters->isNotEmpty())
                        <ul class="space-y-3">
                            @foreach($program->chapters as $chapter)
                                <li class="border border-gray-200 dark:border-slate-700 rounded-lg p-3">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $chapter->titre }} <span class="text-xs text-gray-500">({{ $chapter->type }})</span></div>
                                    @if($chapter->children && $chapter->children->isNotEmpty())
                                        <ul class="mt-2 ml-4 space-y-1">
                                            @foreach($chapter->children as $child)
                                                <li class="text-sm text-gray-700 dark:text-gray-300">{{ $child->titre }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-500 dark:text-gray-400">Aucun chapitre dans ce programme.</p>
                    @endif
                </x-card>

                <x-card title="Historique">
                    @if($program->history && $program->history->isNotEmpty())
                        <ul class="space-y-3">
                            @foreach($program->history as $event)
                                <li class="border-l-4 border-indigo-500 pl-3 dark:border-indigo-400">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $event->action }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $event->description }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $event->user?->name }} – {{ $event->created_at?->format('d/m/Y H:i') }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-500 dark:text-gray-400">Aucun historique.</p>
                    @endif
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>
