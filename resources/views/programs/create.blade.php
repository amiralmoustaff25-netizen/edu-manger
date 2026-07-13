@extends('layouts.app')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-semibold mb-4">Créer un programme</h1>
    <form action="{{ route('programs.store') }}" method="POST" class="space-y-4">
        @csrf
        <div class="grid grid-cols-2 gap-4">
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
        </div>
        <input type="hidden" name="teacher_id" value="{{ auth()->id() }}">
        <input type="hidden" name="school_year_id" value="{{ App\Models\SchoolYear::where('is_active', true)->first()?->id }}">
        <div>
            <label class="block text-sm font-medium">Chapitre</label>
            <input type="text" name="chapters[0][titre]" class="w-full border rounded p-2" required>
            <input type="hidden" name="chapters[0][type]" value="chapitre">
            <input type="hidden" name="chapters[0][volume_horaire_prevu]" value="2.5">
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Enregistrer</button>
    </form>
</div>
@endsection
