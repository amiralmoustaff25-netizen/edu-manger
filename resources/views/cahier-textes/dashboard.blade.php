@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Tableau de bord cahier de textes</h1>

        <form method="GET" action="{{ route('cahier-textes.dashboard.index') }}" class="flex items-center gap-2">
            <label for="classroom_id" class="text-sm font-medium text-gray-600 dark:text-gray-300">Classe</label>
            <select
                id="classroom_id"
                name="classroom_id"
                onchange="this.form.submit()"
                class="border rounded p-2 text-sm bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-white"
            >
                <option value="">Toutes les classes</option>
                @foreach ($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" @selected((string) $selectedClassroomId === (string) $classroom->id)>
                        {{ $classroom->name }}
                    </option>
                @endforeach
            </select>
            @if ($selectedClassroomId)
                <a href="{{ route('cahier-textes.dashboard.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Réinitialiser</a>
            @endif
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded shadow p-4 text-gray-900 dark:text-white">Programmes total : {{ $programs->count() }}</div>
        <div class="bg-white dark:bg-gray-800 rounded shadow p-4 text-gray-900 dark:text-white">Avancement moyen : {{ round($programs->avg('progressPercentage'), 2) }}%</div>
        <div class="bg-white dark:bg-gray-800 rounded shadow p-4 text-gray-900 dark:text-white">Programmes en retard : {{ $programs->filter(fn ($program) => $program->isDelayed())->count() }}</div>
        <div class="bg-white dark:bg-gray-800 rounded shadow p-4 text-gray-900 dark:text-white">Programmes validés : {{ $programs->whereIn('status', ['valide_surveillant', 'valide_directeur'])->count() }}</div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded shadow p-4">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr class="text-gray-700 dark:text-gray-300">
                    <th class="text-left py-2 px-2">Classe</th>
                    <th class="text-left py-2 px-2">Matière</th>
                    <th class="text-left py-2 px-2">Prof</th>
                    <th class="text-left py-2 px-2">Statut</th>
                    <th class="text-left py-2 px-2">Avancement</th>
                    <th class="text-left py-2 px-2">Dernière saisie</th>
                </tr>
            </thead>
            <tbody class="text-gray-900 dark:text-gray-100">
                @forelse ($programs as $program)
                    <tr class="border-t border-gray-100 dark:border-gray-700">
                        <td class="py-2 px-2">{{ $program->classroom?->name }}</td>
                        <td class="py-2 px-2">{{ $program->subject?->nom }}</td>
                        <td class="py-2 px-2">{{ $program->teacher?->name }}</td>
                        <td class="py-2 px-2">{{ $program->status }}</td>
                        <td class="py-2 px-2">{{ $program->progressPercentage }}%</td>
                        <td class="py-2 px-2">{{ optional($program->updated_at)->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-4 px-2 text-center text-gray-500 dark:text-gray-400">Aucun programme pour cette classe.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
