@extends('layouts.app')

@section('content')
<div class="container mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Mes enfants</h1>

    @if($parent->students->isEmpty())
        <p>Aucun enfant associé à ce compte.</p>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            @foreach($parent->students as $student)
                <div class="p-4 border rounded">
                    <h2 class="font-bold">{{ $student->name }}</h2>
                    <p>Matricule: {{ $student->matricule }}</p>
                    <div class="mt-2">
                        <a href="{{ route('parents.children.profile', ['student' => $student->id]) }}" class="text-blue-600">Voir le profil</a>
                        <span class="mx-2">|</span>
                        <a href="{{ route('parents.children.notes', ['student' => $student->id]) }}" class="text-blue-600">Notes</a>
                        <span class="mx-2">|</span>
                        <a href="{{ route('parents.children.bulletins', ['student' => $student->id]) }}" class="text-blue-600">Bulletins</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
