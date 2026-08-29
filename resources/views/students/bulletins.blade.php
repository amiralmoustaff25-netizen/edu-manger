<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Mes Bulletins') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-100 dark:border-slate-700">
                    <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">📄 {{ __('Bulletins scolaires') }}</h4>

                    @php($cycle = $user->latestRegistration?->classroom?->cycle)
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        {{ __('Sélectionne une période pour consulter ou télécharger ton bulletin.') }}
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        @foreach(\App\Support\AcademicPeriods::forCycle($cycle) as $period => $label)
                            <div class="rounded-md border border-gray-200 dark:border-slate-700 p-4">
                                <p class="font-medium text-gray-800 dark:text-gray-200 mb-3">{{ $label }}</p>
                                @if(in_array($period, $publishedPeriods, true))
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('bulletins.show', [$user, $period]) }}" class="px-3 py-1.5 bg-indigo-600 text-white text-xs rounded-md hover:bg-indigo-700">{{ __('Voir') }}</a>
                                        <a href="{{ route('bulletins.pdf', [$user, $period]) }}" target="_blank" class="px-3 py-1.5 bg-gray-200 text-gray-700 text-xs rounded-md hover:bg-gray-300 dark:bg-slate-700 dark:text-gray-200 dark:hover:bg-slate-600">{{ __('PDF') }}</a>
                                    </div>
                                @else
                                    <p class="text-xs text-gray-500 dark:text-gray-400 italic">{{ __('Pas encore disponible') }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
