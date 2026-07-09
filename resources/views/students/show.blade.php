<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $student->name }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $student->matricule }} - {{ $student->email ?? 'Email non renseigné' }}</p>
            </div>
            <a href="{{ route('students.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Retour aux élèves
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-lg bg-white dark:bg-slate-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 dark:text-gray-100">
                    <p class="text-sm font-medium text-gray-500">Classe actuelle</p>
                    <p class="mt-3 text-xl font-bold text-gray-900">{{ $currentRegistration->classroom->name ?? 'Non inscrit' }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-500">Statut</p>
                    <p class="mt-3 text-xl font-bold {{ $student->is_active ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ $student->is_active ? ($currentRegistration?->status === 'active' ? 'Actif' : 'En attente') : 'Inactif' }}
                    </p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-500">Total payé</p>
                    <p class="mt-3 text-xl font-bold text-gray-900">{{ number_format($totalPaid, 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-500">Reste à payer</p>
                    <p class="mt-3 text-xl font-bold text-amber-700">{{ number_format($remainingBalance, 0, ',', ' ') }} FCFA</p>
                </div>
            </div>

            @if($currentRegistration)
                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Changer de classe</h3>
                        <form method="POST" action="{{ route('students.transfer', $student) }}" class="mt-4 space-y-4">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="registration_id" value="{{ $currentRegistration->id }}">
                            <div>
                                <label for="classroom_id" class="block text-sm font-medium text-gray-700">Nouvelle classe</label>
                                <select id="classroom_id" name="classroom_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach($classrooms as $classroom)
                                        <option value="{{ $classroom->id }}" @selected($currentRegistration->classroom_id === $classroom->id)>{{ $classroom->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Mettre à jour la classe</button>
                        </form>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Changer le statut</h3>
                        <form method="POST" action="{{ route('students.status', $student) }}" class="mt-4 space-y-4">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="registration_id" value="{{ $currentRegistration->id }}">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700">Statut d’inscription</label>
                                <select id="status" name="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="pending" @selected($currentRegistration->status === 'pending')>En attente</option>
                                    <option value="active" @selected($currentRegistration->status === 'active')>Actif</option>
                                </select>
                            </div>
                            <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Mettre à jour le statut</button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Historique des inscriptions</h3>
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Matricule inscription</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Année</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Classe</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Date</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Statut</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600">Mensualité</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($student->registrations as $registration)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $registration->matricule }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $registration->schoolYear->year_string ?? $registration->academic_year ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $registration->classroom->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $registration->registration_date }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $registration->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $registration->status === 'active' ? 'Actif' : 'En attente' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900">{{ number_format($registration->monthly_fee, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">Aucune inscription trouvée.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Historique des paiements</h3>
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Mois</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Classe</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600">Montant</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600">Reste</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($student->registrations->flatMap->payments as $payment)
                                <tr>
                                    <td class="px-4 py-3 text-gray-700">{{ $payment->month ?? $payment->month_paid ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $payment->registration->classroom->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                                    <td class="px-4 py-3 text-right font-medium text-amber-700">{{ number_format($payment->remaining_balance, 0, ',', ' ') }} FCFA</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $payment->status === 'partiel' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                            {{ ucfirst($payment->status ?? 'complet') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">Aucun paiement trouvé.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
