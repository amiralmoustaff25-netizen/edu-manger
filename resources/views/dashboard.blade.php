<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    Tableau de bord
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $activeYear ? 'Année scolaire active : '.$activeYear->year_string : 'Aucune année scolaire active' }}
                </p>
            </div>
            @role('super-admin|admin')
                <div class="flex gap-2">
                    <a href="{{ route('registrations.create') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                        Nouvelle inscription
                    </a>
                    <a href="{{ route('registrations.reinscription') }}" class="inline-flex items-center justify-center rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-700 dark:bg-gray-600 dark:hover:bg-gray-500">
                        Réinscription
                    </a>
                </div>
            @endrole
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900 p-4 text-sm text-red-700 dark:text-red-200">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900 p-4 text-sm text-green-700 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-card padding="p-5">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Élèves inscrits</p>
                    <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['students'] }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tous les comptes élèves</p>
                </x-card>

                <x-card padding="p-5">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Classes actives</p>
                    <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['classrooms'] }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Classes créées dans le système</p>
                </x-card>

                @role('super-admin|manager-comptable|comptable')
                    <x-card padding="p-5">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Paiements complets</p>
                        <p class="mt-3 text-3xl font-bold text-emerald-700 dark:text-emerald-400">{{ $stats['paid_this_month'] }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ce mois-ci</p>
                    </x-card>

                    <x-card padding="p-5">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Paiements partiels</p>
                        <p class="mt-3 text-3xl font-bold text-amber-700 dark:text-amber-400">{{ $stats['partial_payments'] }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">À suivre par la comptabilité</p>
                    </x-card>
                @endrole
            </div>

            @role('super-admin|manager-comptable|comptable')
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 xl:col-span-2">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Évolution des paiements</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Suivi des encaissements par mois.</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-right">
                            <div>
                                <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Encaissé</p>
                                <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ number_format($stats['monthly_revenue'], 0, ',', ' ') }} FCFA</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Reste</p>
                                <p class="text-lg font-bold text-amber-700 dark:text-amber-400">{{ number_format($stats['remaining_balance'], 0, ',', ' ') }} FCFA</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <div id="paymentsChart" class="h-64"></div>
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Élève</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Classe</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Mois</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Montant</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Statut</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                @forelse($recentPayments as $payment)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $payment->registration->user->name ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $payment->registration->classroom->name ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $payment->month ?? $payment->month_paid ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-gray-100">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                                        <td class="px-4 py-3">
                                            <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $payment->status === 'partiel' ? 'bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-200' : 'bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200' }}">
                                                {{ ucfirst($payment->status ?? 'complet') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Aucun paiement enregistré pour le moment.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Alertes à traiter</h3>
                    <div class="mt-5 space-y-3">
                        <div class="flex items-center justify-between rounded-md bg-amber-50 dark:bg-amber-900 px-4 py-3">
                            <span class="text-sm font-medium text-amber-900 dark:text-amber-200">Paiements partiels</span>
                            <span class="text-sm font-bold text-amber-900 dark:text-amber-200">{{ $alerts['partial_payments'] }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-md bg-sky-50 dark:bg-sky-900 px-4 py-3">
                            <span class="text-sm font-medium text-sky-900 dark:text-sky-200">Élèves sans classe</span>
                            <span class="text-sm font-bold text-sky-900 dark:text-sky-200">{{ $alerts['students_without_class'] }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-md bg-violet-50 dark:bg-violet-900 px-4 py-3">
                            <span class="text-sm font-medium text-violet-900 dark:text-violet-200">Classes sans enseignant</span>
                            <span class="text-sm font-bold text-violet-900 dark:text-violet-200">{{ $alerts['classrooms_without_teacher'] }}</span>
                        </div>
                        @if($alerts['missing_active_year'])
                            <div class="rounded-md bg-red-50 dark:bg-red-900 px-4 py-3 text-sm font-medium text-red-800 dark:text-red-200">
                                Aucune année scolaire active.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endrole

            @role('super-admin|manager-comptable|comptable')
                <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Enregistrer un paiement</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Accéder au formulaire de paiement optimisé avec matricule.</p>
                        </div>
                        <a href="{{ route('payments.create') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                            Nouveau Paiement
                        </a>
                    </div>
                </div>
            @endrole

            <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Inscriptions récentes</h3>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $registrations->count() }} affichée(s)</span>
                </div>
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Matricule</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Élève</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Classe</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Année</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @forelse($registrations as $reg)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $reg->matricule }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $reg->user->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $reg->classroom->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $reg->schoolYear->year_string ?? $reg->academic_year ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $reg->status === 'pending' ? 'bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-200' : 'bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200' }}">
                                            {{ $reg->status === 'pending' ? 'En attente' : 'Active' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Aucune inscription trouvée.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        @vite(['resources/js/charts/payments-chart.js'])
        <script>
            // Fonctionne au premier chargement (attend DOMContentLoaded, le document est
            // encore en cours de lecture à cet instant) et après une navigation PJAX (le
            // document est déjà "complete" quand ce script est réinjecté, donc on exécute
            // immédiatement au lieu d'attendre un DOMContentLoaded qui ne se reproduira
            // jamais — voir resources/js/pjax.js:executePageScripts).
            (function (fn) {
                if (document.readyState !== 'loading') {
                    fn();
                } else {
                    document.addEventListener('DOMContentLoaded', fn);
                }
            })(function () {
                if (typeof window.initPaymentsChart === 'function') {
                    var options = {
                        series: [{
                            name: 'Encaissements',
                            data: @json($monthlyRevenue->pluck('amount')->values())
                        }],
                        chart: {
                            type: 'bar',
                            height: 250,
                            toolbar: {
                                show: false
                            }
                        },
                        colors: ['#6366F1'],
                        plotOptions: {
                            bar: {
                                borderRadius: 4,
                                horizontal: false,
                                columnWidth: '55%',
                            }
                        },
                        dataLabels: {
                            enabled: false
                        },
                        stroke: {
                            show: true,
                            width: 2,
                            colors: ['transparent']
                        },
                        xaxis: {
                            categories: @json($monthlyRevenue->pluck('label')->values()),
                        },
                        yaxis: {
                            title: {
                                text: 'Montant (FCFA)'
                            }
                        },
                        fill: {
                            opacity: 0.9
                        },
                        tooltip: {
                            y: {
                                formatter: function (val) {
                                    return new Intl.NumberFormat('fr-FR').format(val) + " FCFA"
                                }
                            }
                        }
                    };

                    window.initPaymentsChart('paymentsChart', options);
                }
            });
        </script>
    @endpush
</x-app-layout>
