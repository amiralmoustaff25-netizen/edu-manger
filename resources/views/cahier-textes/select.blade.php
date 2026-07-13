@extends('layouts.app')

@section('content')
<div class="p-6 max-w-xl mx-auto">
    <h1 class="text-2xl font-semibold mb-6">Sélection du cahier de textes</h1>
    <form action="{{ route('cahier-textes.index') }}" method="GET" class="space-y-4">
        <div>
            <label class="block text-sm font-medium">Classe</label>
            <select name="classroom_id" class="w-full border rounded p-2">
                @foreach (App\Models\Classroom::all() as $classroom)
                    <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium">Matière</label>
            <select name="subject_id" class="w-full border rounded p-2">
                @foreach (App\Models\Matiere::all() as $subject)
                    <option value="{{ $subject->id }}">{{ $subject->nom }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Accéder au cahier</button>
    </form>
</div>
@endsection
