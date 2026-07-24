@extends('layouts.app')

@section('content')
<div class="p-6 max-w-7xl mx-auto">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $program->subject?->nom }} – {{ $program->classroom?->name }}</h1>
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                Année : {{ $program->schoolYear?->year_string }} – Enseignant : {{ $program->teacher?->name }} – Statut :
                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-slate-700 dark:text-gray-200">{{ $program->status }}</span>
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('programs.index') }}" class="px-4 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-slate-600 dark:text-gray-200 dark:hover:bg-slate-700">
                Retour à la liste
            </a>

            @can('update', $program)
                <a href="{{ route('programs.edit', $program) }}" class="px-4 py-2 rounded bg-yellow-500 text-white hover:bg-yellow-600">
                    Modifier
                </a>
            @endcan

            @can('delete', $program)
                <form action="{{ route('programs.destroy', $program) }}" method="POST" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Supprimer le programme', message: 'Le programme {{ addslashes((string) $program->subject?->nom) }} – {{ addslashes((string) $program->classroom?->name) }} sera supprimé définitivement.', confirmLabel: 'Supprimer' })">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700">
                        Supprimer
                    </button>
                </form>
            @endcan
        </div>
    </div>

    @php
        $canSubmit = auth()->user()->can('update', $program) && \App\Support\ProgramStatus::canTransition($program->status, 'soumis');
        $canValidateSurveillant = auth()->user()->can('validateSurveillant', $program);
        $canValidateDirecteur = auth()->user()->can('validateDirecteur', $program);
        $canReject = auth()->user()->can('reject', $program);
    @endphp

    @if($canSubmit || $canValidateSurveillant || $canValidateDirecteur || $canReject)
        <div class="bg-white dark:bg-slate-800 shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Workflow</h2>
            <div class="flex flex-wrap items-end gap-3">
                @if($canSubmit)
                    <form action="{{ route('programs.submit', $program) }}" method="POST" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Soumettre le programme', message: 'Le programme sera soumis au surveillant pour validation.', confirmLabel: 'Soumettre' })">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
                            Soumettre
                        </button>
                    </form>
                @endif

                @if($canValidateSurveillant)
                    <form action="{{ route('programs.validate-surveillant', $program) }}" method="POST" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Valider (Surveillant)', message: 'Le programme sera validé par le surveillant.', confirmLabel: 'Valider' })">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700">
                            Valider (Surveillant)
                        </button>
                    </form>
                @endif

                @if($canValidateDirecteur)
                    <form action="{{ route('programs.validate-directeur', $program) }}" method="POST" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Valider (Directeur)', message: 'Le programme sera validé définitivement par le directeur.', confirmLabel: 'Valider' })">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded bg-green-700 text-white hover:bg-green-800">
                            Valider (Directeur)
                        </button>
                    </form>
                @endif

                @if($canReject)
                    <form action="{{ route('programs.reject', $program) }}" method="POST" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Rejeter le programme', message: 'Le programme sera rejeté. Le motif saisi sera conservé.', confirmLabel: 'Rejeter' })" class="flex flex-wrap items-center gap-2">
                        @csrf
                        <input type="text" name="motif" required placeholder="Motif du rejet..." class="px-3 py-2 rounded border border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                        <button type="submit" class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700">
                            Rejeter
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-slate-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Chapitres</h2>
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
        </div>

        <div class="bg-white dark:bg-slate-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Historique</h2>
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
        </div>
    </div>
</div>
@endsection
