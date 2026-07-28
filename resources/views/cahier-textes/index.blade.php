@extends('layouts.app')

@section('content')
<div class="p-6" x-data="cahierTexte({{ $program->id ?? 0 }}, '{{ now()->toDateString() }}')">
    <div class="grid lg:grid-cols-[1fr_320px] gap-6">
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="sticky top-0 bg-white border-b px-4 py-3">
                <div class="flex flex-wrap gap-3 items-center">
                    <label class="text-sm">Classe</label>
                    <select class="border rounded p-2">
                        <option>CM1 A</option>
                    </select>
                    <label class="text-sm">Matière</label>
                    <select class="border rounded p-2">
                        <option>Mathématiques</option>
                    </select>
                    <label class="text-sm">Date</label>
                    <input type="date" value="{{ now()->toDateString() }}" class="border rounded p-2">
                </div>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left">☐</th>
                        <th class="px-4 py-3 text-left">Chapitre</th>
                        <th class="px-4 py-3 text-left">Leçon</th>
                        <th class="px-4 py-3 text-left">Titre</th>
                        <th class="px-4 py-3 text-left">Objectifs</th>
                        <th class="px-4 py-3 text-left">V.H.</th>
                        <th class="px-4 py-3 text-left">Statut</th>
                        <th class="px-4 py-3 text-left">Remarque</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($program->chapters ?? [] as $chapter)
                        <tr class="odd:bg-white even:bg-gray-50">
                            <td class="px-4 py-3"><input type="checkbox" value="{{ $chapter->id }}" @click="toggleChapter({{ $chapter->id }})"></td>
                            <td class="px-4 py-3">{{ $chapter->titre }}</td>
                            <td class="px-4 py-3">{{ $chapter->type }}</td>
                            <td class="px-4 py-3">{{ $chapter->titre }}</td>
                            <td class="px-4 py-3">{{ $chapter->description }}</td>
                            <td class="px-4 py-3">{{ $chapter->volume_horaire_prevu }}</td>
                            <td class="px-4 py-3">À faire</td>
                            <td class="px-4 py-3"><input type="text" class="border rounded p-1" @blur="saveRemark({{ $chapter->id }}, $event.target.value)"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <aside class="space-y-4">
            <div class="bg-white shadow rounded p-4">
                <canvas id="donut-chart"></canvas>
            </div>
            <div class="bg-white shadow rounded p-4">
                <canvas id="line-chart"></canvas>
            </div>
        </aside>
    </div>
</div>

@push('scripts')
    @vite(['resources/js/cahier-textes.js'])
@endpush
@endsection
