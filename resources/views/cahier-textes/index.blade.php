@extends('layouts.app')

@section('content')
<div class="p-6" x-data="cahierTexte({{ $program->id ?? 0 }}, '{{ now()->toDateString() }}')">
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-6">
        <div class="bg-white dark:bg-slate-800 shadow rounded-lg overflow-hidden">
            <div class="sticky top-0 bg-white dark:bg-slate-800 border-b dark:border-slate-700 px-4 py-3">
                <div class="flex flex-wrap gap-3 items-center">
                    <label class="text-sm text-gray-700 dark:text-gray-300">Classe</label>
                    <select class="border dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded p-2">
                        <option>CM1 A</option>
                    </select>
                    <label class="text-sm text-gray-700 dark:text-gray-300">Matière</label>
                    <select class="border dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded p-2">
                        <option>Mathématiques</option>
                    </select>
                    <label class="text-sm text-gray-700 dark:text-gray-300">Date</label>
                    <input type="date" value="{{ now()->toDateString() }}" class="border dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded p-2">
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
                    @foreach ($program->chapters ?? [] as $chapter)
                        <tr class="odd:bg-white even:bg-gray-50 dark:odd:bg-slate-800 dark:even:bg-slate-700/50 text-gray-800 dark:text-gray-200">
                            <td class="px-4 py-3"><input type="checkbox" value="{{ $chapter->id }}" @click="toggleChapter({{ $chapter->id }})"></td>
                            <td class="px-4 py-3">{{ $chapter->titre }}</td>
                            <td class="px-4 py-3">{{ $chapter->type }}</td>
                            <td class="px-4 py-3">{{ $chapter->titre }}</td>
                            <td class="px-4 py-3">{{ $chapter->description }}</td>
                            <td class="px-4 py-3">{{ $chapter->volume_horaire_prevu }}</td>
                            <td class="px-4 py-3">À faire</td>
                            <td class="px-4 py-3"><input type="text" class="border dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded p-1" @blur="saveRemark({{ $chapter->id }}, $event.target.value)"></td>
                        </tr>
                    @endforeach
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

@push('scripts')
    @vite(['resources/js/cahier-textes.js'])
@endpush
@endsection
