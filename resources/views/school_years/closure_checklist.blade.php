<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Vérifications avant clôture</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $schoolYear->year_string }}</p>
            </div>
            <a href="{{ route('school-years.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700">
                Retour
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="rounded-lg bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 p-4 text-sm text-blue-800 dark:text-blue-200">
                Ce rapport est en lecture seule : il liste les points à examiner avant de clôturer l'année, mais ne bloque pas encore la clôture elle-même.
            </div>

            @php
                $categoryLabels = [
                    'comptabilite' => 'Comptabilité',
                    'pedagogie' => 'Pédagogie',
                    'administration' => 'Administration',
                ];
            @endphp

            @foreach($checklist as $categoryKey => $items)
                <div class="rounded-lg bg-white dark:bg-slate-800 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200">{{ $categoryLabels[$categoryKey] ?? $categoryKey }}</h3>
                    </div>
                    <ul class="divide-y divide-gray-200 dark:divide-slate-700">
                        @foreach($items as $item)
                            <li class="px-6 py-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex items-start gap-3">
                                        @switch($item['status'])
                                            @case('anomaly')
                                                <span class="mt-0.5 inline-flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-300" title="Anomalie">✕</span>
                                                @break
                                            @case('ok')
                                                <span class="mt-0.5 inline-flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-300" title="OK">✓</span>
                                                @break
                                            @default
                                                <span class="mt-0.5 inline-flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-gray-400" title="Non applicable">–</span>
                                        @endswitch
                                        <div>
                                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $item['label'] }}</p>
                                            @if($item['note'])
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $item['note'] }}</p>
                                            @endif
                                            @if(!empty($item['items']))
                                                <ul class="mt-2 flex flex-wrap gap-1">
                                                    @foreach($item['items'] as $sample)
                                                        <li class="inline-flex items-center rounded-full bg-gray-100 dark:bg-slate-700 px-2 py-0.5 text-xs text-gray-700 dark:text-gray-300">{{ $sample }}</li>
                                                    @endforeach
                                                    @if($item['items_truncated'])
                                                        <li class="inline-flex items-center px-2 py-0.5 text-xs text-gray-500 dark:text-gray-400">+ autres…</li>
                                                    @endif
                                                </ul>
                                            @endif
                                        </div>
                                    </div>
                                    @if($item['status'] === 'anomaly')
                                        <span class="flex-shrink-0 text-sm font-semibold text-red-600 dark:text-red-400">{{ $item['count'] }}</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

        </div>
    </div>
</x-app-layout>
