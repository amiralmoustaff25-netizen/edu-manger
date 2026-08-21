<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Emploi du temps') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (! $schoolYear)
                <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 p-6 text-amber-800 dark:text-amber-200">
                    {{ __('Aucune année scolaire active.') }}
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Choisissez une classe de primaire pour consulter ou modifier son emploi du temps.') }}</p>
                <div class="rounded-lg bg-white dark:bg-slate-800 shadow-sm divide-y dark:divide-slate-700">
                    @forelse ($classrooms as $classroom)
                        <a href="{{ route('timetable.edit', $classroom) }}" class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 dark:hover:bg-slate-700">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $classroom->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Titulaire') }} : {{ $classroom->teacher?->name ?? __('Aucun') }}</p>
                            </div>
                            <span class="text-indigo-600 dark:text-indigo-400 text-sm font-semibold">{{ __('Ouvrir') }} →</span>
                        </a>
                    @empty
                        <p class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('Aucune classe de primaire pour cette année scolaire.') }}</p>
                    @endforelse
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
