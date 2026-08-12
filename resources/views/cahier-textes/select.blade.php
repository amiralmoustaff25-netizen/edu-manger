@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-xl p-6">
    <h1 class="mb-6 text-2xl font-semibold text-gray-900 dark:text-white">Sélection du cahier de textes</h1>
    <section class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
        <form action="{{ route('cahier-textes.index') }}" method="GET" class="space-y-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                Classe
                <select name="classroom_id" class="mt-1 w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach (App\Models\Classroom::all() as $classroom)
                        <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                Matière
                <select name="subject_id" class="mt-1 w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach (App\Models\Matiere::all() as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->nom }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Accéder au cahier</button>
        </form>
    </section>
</div>
@endsection
