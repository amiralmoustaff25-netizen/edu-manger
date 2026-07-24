<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Mes Notes') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-100 dark:border-slate-700">
                    <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">📚 {{ __('Toutes mes notes') }}</h4>

                    @if($notes->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                                <thead class="bg-gray-50 dark:bg-slate-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('Date') }}</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('Matière') }}</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('Type') }}</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('Période') }}</th>
                                        <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">{{ __('Note') }}</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('Appréciation') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                                    @foreach($notes as $note)
                                        <tr>
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $note->created_at?->format('d/m/Y') }}</td>
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $note->matiere->nom ?? __('Non définie') }}</td>
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $note->type_evaluation }}</td>
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $note->periode }}</td>
                                            <td class="px-4 py-3 text-right font-bold {{ $note->valeur >= 10 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ number_format($note->valeur, 2, ',', ' ') }}/20</td>
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $note->appreciation ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 italic">{{ __('Aucune note disponible pour le moment.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
