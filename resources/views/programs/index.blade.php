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
                </tr>
            </thead>
            <tbody>
                @foreach ($programs as $program)
                    <tr>
                        <td class="px-4 py-2">{{ $program->classroom?->name }}</td>
                        <td class="px-4 py-2">{{ $program->subject?->nom }}</td>
                        <td class="px-4 py-2">{{ $program->status }}</td>
                        <td class="px-4 py-2">{{ $program->teacher?->name }}</td>
                        <td class="px-4 py-2">{{ $program->progressPercentage }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
