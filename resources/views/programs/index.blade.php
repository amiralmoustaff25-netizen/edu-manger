@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-semibold">Programmes annuels</h1>
        <a href="{{ route('programs.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded">Nouveau programme</a>
    </div>

    <div class="bg-white shadow rounded p-4">
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-4 py-2 text-left">Classe</th>
                    <th class="px-4 py-2 text-left">Matière</th>
                    <th class="px-4 py-2 text-left">Statut</th>
                    <th class="px-4 py-2 text-left">Enseignant</th>
                    <th class="px-4 py-2 text-left">Avancement</th>
                    <th class="px-4 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($programs as $program)
                    <tr>
                        <td class="px-4 py-2">{{ $program->classroom?->name }}</td>
                        <td class="px-4 py-2">{{ $program->subject?->nom }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-slate-700 dark:text-gray-200">{{ $program->status }}</span>
                        </td>
                        <td class="px-4 py-2">{{ $program->teacher?->name }}</td>
                        <td class="px-4 py-2">{{ $program->progressPercentage }}%</td>
                        <td class="px-4 py-2">
                            <a href="{{ route('programs.show', $program) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium">Voir</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
