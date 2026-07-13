@extends('layouts.app')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-semibold mb-4">Tableau de bord cahier de textes</h1>
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded shadow p-4">Programmes total : {{ $programs->count() }}</div>
        <div class="bg-white rounded shadow p-4">Avancement moyen : {{ round($programs->avg('progressPercentage'), 2) }}%</div>
        <div class="bg-white rounded shadow p-4">Programmes en retard : {{ $programs->filter(fn ($program) => $program->isDelayed())->count() }}</div>
        <div class="bg-white rounded shadow p-4">Programmes validés : {{ $programs->whereIn('status', ['valide_surveillant', 'valide_directeur'])->count() }}</div>
    </div>
    <div class="bg-white rounded shadow p-4">
        <table class="min-w-full divide-y divide-gray-200">
            <thead><tr><th>Classe</th><th>Matière</th><th>Prof</th><th>Statut</th><th>Avancement</th><th>Dernière saisie</th></tr></thead>
            <tbody>
                @foreach ($programs as $program)
                    <tr><td>{{ $program->classroom?->name }}</td><td>{{ $program->subject?->nom }}</td><td>{{ $program->teacher?->name }}</td><td>{{ $program->status }}</td><td>{{ $program->progressPercentage }}%</td><td>{{ optional($program->updated_at)->diffForHumans() }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
