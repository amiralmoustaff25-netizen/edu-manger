<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Tableau de bord
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $activeYear ? 'Année scolaire active : '.$activeYear->year_string : 'Aucune année scolaire active' }}
                </p>
            </div>
            @role('super-admin|admin')
                <div class="flex gap-2">
                    <a href="{{ route('registrations.create') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                        Nouvelle inscription
                    </a>
                    <a href="{{ route('parents.create') }}" class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                        Nouveau parent
                    </a>
                </div>
            @endrole
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-500">Élèves inscrits</p>
                    <p class="mt-3 text-3xl font-bold text-gray-900">{{ $stats['students'] }}</p>
                    <p class="mt-1 text-xs text-gray-500">Tous les comptes élèves</p>
                </div>

                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-500">Classes actives</p>
                    <p class="mt-3 text-3xl font-bold text-gray-900">{{ $stats['classrooms'] }}</p>
                    <p class="mt-1 text-xs text-gray-500">Classes créées dans le système</p>
                </div>

                @role('super-admin|admin')
                    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                        <p class="text-sm font-medium text-gray-500">Parents inscrits</p>
                        <p class="mt-3 text-3xl font-bold text-indigo-700">{{ $stats['parents'] }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $stats['active_parents'] }} actifs</p>
                    </div>
                @endrole

                @role('manager-comptable|comptable')
                    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                        <p class="text-sm font-medium text-gray-500">Paiements complets</p>
                        <p class="mt-3 text-3xl font-bold text-emerald-700">{{ $stats['paid_this_month'] }}</p>
                        <p class="mt-1 text-xs text-gray-500">Ce mois-ci</p>
                    </div>

                    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                        <p class="text-sm font-medium text-gray-500">Paiements partiels</p>
                        <p class="mt-3 text-3xl font-bold text-amber-700">{{ $stats['partial_payments'] }}</p>
                        <p class="mt-1 text-xs text-gray-500">À suivre par la comptabilité</p>
                    </div>
                @endrole
            </div>

            @role('manager-comptable|comptable')
            <div class="grid gap-6 xl:grid-cols-3">
                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 xl:col-span-2">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Vue financière</h3>
                            <p class="mt-1 text-sm text-gray-500">Suivi rapide des encaissements et des restes à payer.</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-right">
                            <div>
                                <p class="text-xs font-medium uppercase text-gray-500">Encaissé</p>
                                <p class="text-lg font-bold text-gray-900">{{ number_format($stats['monthly_revenue'], 0, ',', ' ') }} FCFA</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase text-gray-500">Reste</p>
                                <p class="text-lg font-bold text-amber-700">{{ number_format($stats['remaining_balance'], 0, ',', ' ') }} FCFA</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Élève</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Classe</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Mois</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Montant</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Statut</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse($recentPayments as $payment)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-900">{{ $payment->registration->user->name ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $payment->registration->classroom->name ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $payment->month ?? $payment->month_paid ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                                        <td class="px-4 py-3">
                                            <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $payment->status === 'partiel' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                                {{ ucfirst($payment->status ?? 'complet') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">Aucun paiement enregistré pour le moment.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Alertes à traiter</h3>
                    <div class="mt-5 space-y-3">
                        <div class="flex items-center justify-between rounded-md bg-amber-50 px-4 py-3">
                            <span class="text-sm font-medium text-amber-900">Paiements partiels</span>
                            <span class="text-sm font-bold text-amber-900">{{ $alerts['partial_payments'] }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-md bg-sky-50 px-4 py-3">
                            <span class="text-sm font-medium text-sky-900">Élèves sans classe</span>
                            <span class="text-sm font-bold text-sky-900">{{ $alerts['students_without_class'] }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-md bg-violet-50 px-4 py-3">
                            <span class="text-sm font-medium text-violet-900">Classes sans enseignant</span>
                            <span class="text-sm font-bold text-violet-900">{{ $alerts['classrooms_without_teacher'] }}</span>
                        </div>
                        @if($alerts['missing_active_year'])
                            <div class="rounded-md bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                                Aucune année scolaire active.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endrole

            @role('manager-comptable|comptable')
                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Enregistrer un paiement</h3>
                    <form action="{{ route('payments.store') }}" method="POST" class="mt-5 grid gap-4 md:grid-cols-4">
                        @csrf
                        <div class="md:col-span-2">
                            <label for="registration_id" class="block text-sm font-medium text-gray-700">Inscription</label>
                            <select id="registration_id" name="registration_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Sélectionner un élève</option>
                                @foreach($registrations as $registration)
                                    <option value="{{ $registration->id }}">
                                        {{ $registration->matricule }} - {{ $registration->user->name ?? 'N/A' }} ({{ $registration->classroom->name ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="amount_paid" class="block text-sm font-medium text-gray-700">Montant payé</label>
                            <input id="amount_paid" type="number" name="amount_paid" step="0.01" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label for="month" class="block text-sm font-medium text-gray-700">Mois</label>
                            <input id="month" type="text" name="month" required placeholder="Ex: Octobre" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="md:col-span-4">
                            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                                Valider le paiement
                            </button>
                        </div>
                    </form>
                </div>
            @endrole

            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Inscriptions récentes</h3>
                    <span class="text-sm text-gray-500">{{ $registrations->count() }} affichée(s)</span>
                </div>
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Matricule</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Élève</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Classe</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Année</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($registrations as $reg)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $reg->matricule }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $reg->user->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $reg->classroom->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $reg->schoolYear->year_string ?? $reg->academic_year ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $reg->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                            {{ $reg->status === 'pending' ? 'En attente' : 'Active' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">Aucune inscription trouvée.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
