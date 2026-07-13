@extends('layouts.app')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-semibold mb-4">Éditer le programme</h1>
    <form action="{{ route('programs.update', $program) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label class="block text-sm font-medium">Titre du chapitre</label>
            <input type="text" name="chapters[0][titre]" value="{{ $program->chapters->first()?->titre ?? '' }}" class="w-full border rounded p-2" required>
            <input type="hidden" name="chapters[0][type]" value="chapitre">
            <input type="hidden" name="chapters[0][volume_horaire_prevu]" value="2.5">
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Enregistrer</button>
    </form>
</div>
@endsection
