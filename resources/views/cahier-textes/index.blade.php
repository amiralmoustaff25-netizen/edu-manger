@php
    $completionsToday = ($program->chapters ?? collect())->mapWithKeys(function ($chapter) {
        $done = $chapter->relationLoaded('completions') || $chapter->exists
            ? $chapter->completions->contains(fn ($completion) => optional($completion->date_traitement)->toDateString() === now()->toDateString())
            : false;

        return [$chapter->id => $done];
    });
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                Cahier de texte — {{ $program->classroom?->name ?? '—' }} · {{ $program->subject?->nom ?? '—' }}
            </h2>
            <a href="{{ route('cahier-textes.select') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                Changer de classe / matière
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8" x-data="cahierTexte({{ $program->id ?? 0 }}, '{{ now()->toDateString() }}', @js($completionsToday))">
            <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-6">
                <div class="bg-white dark:bg-slate-800 shadow rounded-lg overflow-hidden">
                    <div class="sticky top-0 bg-white dark:bg-slate-800 border-b dark:border-slate-700 px-4 py-3">
                        <div class="flex flex-wrap gap-3 items-center">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Classe :</span>
                            <span class="rounded-md border dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 px-3 py-1.5 text-sm font-medium text-gray-800">{{ $program->classroom?->name ?? '—' }}</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Matière :</span>
                            <span class="rounded-md border dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 px-3 py-1.5 text-sm font-medium text-gray-800">{{ $program->subject?->nom ?? '—' }}</span>
                            <label class="text-sm text-gray-700 dark:text-gray-300">Date</label>
                            <input type="date" x-model="date" class="border dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded p-2">
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">☐</th>
                                <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">Chapitre</th>
                                <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">Leçon</th>
                                <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">Titre</th>
                                <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">Objectifs</th>
                                <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">V.H.</th>
                                <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">Statut</th>
                                <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">Remarque</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($program->chapters ?? [] as $chapter)
                                <tr class="odd:bg-white even:bg-gray-50 dark:odd:bg-slate-800 dark:even:bg-slate-700/50 text-gray-800 dark:text-gray-200">
                                    <td class="px-4 py-3"><input type="checkbox" value="{{ $chapter->id }}" :checked="completedToday[{{ $chapter->id }}]" @click="toggleChapter({{ $chapter->id }})"></td>
                                    <td class="px-4 py-3">{{ $chapter->titre }}</td>
                                    <td class="px-4 py-3">{{ $chapter->type }}</td>
                                    <td class="px-4 py-3">{{ $chapter->titre }}</td>
                                    <td class="px-4 py-3">{{ $chapter->description }}</td>
                                    <td class="px-4 py-3">{{ $chapter->volume_horaire_prevu }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                              :class="completedToday[{{ $chapter->id }}] ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-gray-300'"
                                              x-text="completedToday[{{ $chapter->id }}] ? 'Fait aujourd\'hui' : 'À faire'"></span>
                                    </td>
                                    <td class="px-4 py-3"><input type="text" class="border dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded p-1" @blur="saveRemark({{ $chapter->id }}, $event.target.value)"></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">Aucun chapitre pour cette classe et cette matière.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
                <aside class="space-y-4">
                    <div class="bg-white dark:bg-slate-800 shadow rounded p-4">
                        <canvas id="donut-chart"></canvas>
                    </div>
                    <div class="bg-white dark:bg-slate-800 shadow rounded p-4">
                        <canvas id="line-chart"></canvas>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    @push('scripts')
        @vite(['resources/js/cahier-textes.js'])
    @endpush
</x-app-layout>
