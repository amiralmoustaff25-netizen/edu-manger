@extends('layouts.app')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-semibold mb-4">{{ $program->subject?->nom }} - {{ $program->classroom?->name }}</h1>
    <p class="text-sm text-gray-600">Statut : {{ $program->status }}</p>
    <ul class="mt-4 space-y-2">
        @foreach ($program->chapters as $chapter)
            <li class="border rounded p-3">{{ $chapter->titre }} ({{ $chapter->type }})</li>
        @endforeach
    </ul>
</div>
@endsection
