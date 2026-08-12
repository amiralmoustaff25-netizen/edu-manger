@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl p-6">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Éditer le programme</h1>
        <a href="{{ route('programs.show', $program) }}" class="rounded-md border border-gray-300 dark:border-slate-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700">Retour</a>
    </div>

    <section class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
        <form action="{{ route('programs.update', $program) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                Titre du chapitre
                <input type="text" name="chapters[0][titre]" value="{{ $program->chapters->first()?->titre ?? '' }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <input type="hidden" name="chapters[0][type]" value="chapitre">
                <input type="hidden" name="chapters[0][volume_horaire_prevu]" value="2.5">
            </label>
            <div class="flex justify-end">
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Enregistrer</button>
            </div>
        </form>
    </section>
</div>
@endsection
