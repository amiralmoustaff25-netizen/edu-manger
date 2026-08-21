<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Mes Notes') }} @if(auth()->id() !== $user->id) — {{ $user->name }} @endif
        </h2>
    </x-slot>

    <div class="py-8" x-data="{ activePeriod: '{{ $defaultPeriod }}' }">
        <div class="max-w-7xl mx-auto sm:px-4 lg:px-8 space-y-6">

            {{-- Onglets de période (trimestres pour le primaire, semestres pour collège/lycée) --}}
            <div class="flex flex-wrap gap-2">
                @foreach($periods as $code => $data)
                    <button type="button"
                            x-on:click="activePeriod = '{{ $code }}'"
                            :class="activePeriod === '{{ $code }}' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-slate-700'"
                            class="px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-colors">
                        {{ $data['label'] }}
                    </button>
                @endforeach
            </div>

            @foreach($periods as $code => $data)
                <div x-show="activePeriod === '{{ $code }}'" x-cloak class="space-y-6">
                    @if(empty($data['subjects']))
                        <div class="rounded-lg bg-white dark:bg-slate-800 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 p-8 text-center">
                            <p class="text-gray-500 dark:text-gray-400 italic">{{ __('Aucune note disponible.') }}</p>
                        </div>
                    @else
                        {{-- Jauge de moyenne générale --}}
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div class="lg:col-span-1 rounded-lg bg-white dark:bg-slate-800 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 p-6 flex flex-col items-center justify-center">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Moyenne générale') }} — {{ $data['label'] }}</p>
                                <div id="gauge-{{ $code }}" class="w-full" style="max-width:220px"></div>
                                @if($data['color'])
                                    <p class="mt-1 text-sm font-semibold text-gray-700 dark:text-gray-300">{{ number_format($data['general_average'], 2, ',', ' ') }} / 20</p>
                                    <span class="mt-2 inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold text-white" style="background-color: {{ $data['color']['color'] }}">
                                        {{ $data['color']['label'] }}
                                    </span>
                                @endif
                            </div>

                            {{-- Histogrammes par matière --}}
                            <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach($data['subjects'] as $subjectIndex => $subject)
                                    <div class="rounded-lg bg-white dark:bg-slate-800 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 p-4">
                                        <div class="flex items-center justify-between mb-1">
                                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $subject['matiere'] }}</p>
                                            @if($subject['colorBand'])
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold text-white" style="background-color: {{ $subject['colorBand']['color'] }}">
                                                    {{ $subject['average'] }}/20
                                                </span>
                                            @endif
                                        </div>
                                        <div id="bar-{{ $code }}-{{ $subjectIndex }}"></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Tableau détaillé des notes --}}
                        <div class="rounded-lg bg-white dark:bg-slate-800 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 overflow-hidden">
                            <div class="p-6 border-b border-gray-100 dark:border-slate-700">
                                <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200">📚 {{ __('Détail des notes') }} — {{ $data['label'] }}</h4>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                                    <thead class="bg-gray-50 dark:bg-slate-700">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('Matière') }}</th>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __("Type d'évaluation") }}</th>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('Date') }}</th>
                                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">{{ __('Note') }}</th>
                                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">{{ __('Coefficient') }}</th>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('Appréciation') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                                        @foreach($data['subjects'] as $subject)
                                            @foreach($subject['evaluations'] as $evaluation)
                                                <tr>
                                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $subject['matiere'] }}</td>
                                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $evaluation['type'] }}@if($evaluation['evaluation_number'] > 1) {{ $evaluation['evaluation_number'] }}@endif</td>
                                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $evaluation['date']?->format('d/m/Y') }}</td>
                                                    <td class="px-4 py-3 text-right font-bold {{ $evaluation['valeur'] >= 10 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ number_format($evaluation['valeur'], 2, ',', ' ') }}/20</td>
                                                    <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ $subject['coefficient'] !== null ? rtrim(rtrim(number_format($subject['coefficient'], 1), '0'), '.') : '—' }}</td>
                                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $evaluation['appreciation'] ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    @vite(['resources/js/charts/payments-chart.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var isDark = document.documentElement.classList.contains('dark');
            var textColor = isDark ? '#cbd5e1' : '#475569';

            @foreach($periods as $code => $data)
                @if(!empty($data['subjects']))
                    if (typeof window.initPaymentsChart === 'function') {
                        window.initPaymentsChart('gauge-{{ $code }}', {
                            chart: { type: 'radialBar', height: 220, foreColor: textColor },
                            series: [{{ $data['general_average'] ? round(($data['general_average'] / 20) * 100, 1) : 0 }}],
                            labels: ['{{ number_format($data['general_average'] ?? 0, 2, ',', ' ') }} / 20'],
                            colors: ['{{ $data['color']['color'] ?? '#6366f1' }}'],
                            plotOptions: {
                                radialBar: {
                                    hollow: { size: '60%' },
                                    dataLabels: {
                                        value: {
                                            fontSize: '20px',
                                            color: textColor,
                                            formatter: function () { return '{{ number_format($data['general_average'] ?? 0, 2, ',', ' ') }}/20'; }
                                        }
                                    }
                                }
                            }
                        });
                    }

                    @foreach($data['subjects'] as $subjectIndex => $subject)
                        if (typeof window.initPaymentsChart === 'function') {
                            window.initPaymentsChart('bar-{{ $code }}-{{ $subjectIndex }}', {
                                chart: { type: 'bar', height: 180, toolbar: { show: false }, foreColor: textColor },
                                series: [{ name: '{{ __('Note') }}', data: @json(collect($subject['evaluations'])->map(fn ($e) => $e['valeur'])->all()) }],
                                xaxis: { categories: @json(collect($subject['evaluations'])->map(fn ($e) => $e['type'].($e['evaluation_number'] > 1 ? ' '.$e['evaluation_number'] : ''))->all()) },
                                yaxis: { min: 0, max: 20 },
                                colors: ['#6366f1'],
                                plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
                                dataLabels: { enabled: true },
                            });
                        }
                    @endforeach
                @endif
            @endforeach
        });
    </script>
</x-app-layout>
