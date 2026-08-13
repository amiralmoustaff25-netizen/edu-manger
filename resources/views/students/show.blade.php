<x-app-layout>
    <x-slot name="breadcrumbs">
        <x-breadcrumbs :items="[
            ['label' => 'Elèves', 'route' => 'students.index'],
            ['label' => $student->name],
        ]" />
    </x-slot>

    <x-slot name="header">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <img src="{{ $student->profile_photo_url }}"
                         alt="{{ $student->name }}"
                         class="w-12 h-12 rounded-full object-cover">
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">{{ $student->name }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $student->matricule }} - {{ $student->email ?? 'Email non renseigné' }}</p>
                    </div>
                </div>
                <a href="{{ route('students.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-slate-800 dark:border-slate-600 dark:text-gray-300 dark:hover:bg-slate-700">
                    Retour aux élèves
                </a>
            </div>

            {{-- Actions rapides : chaque action mène à son objectif en 1 clic --}}
            <div class="flex flex-wrap gap-2">
                @can('modifier-eleve', $student)
                    <a href="{{ route('students.edit', $student) }}" class="inline-flex items-center gap-1.5 rounded-md bg-amber-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-700">
                        ✏️ Modifier
                    </a>
                @endcan
                @can('enregistrer-paiement')
                    <a href="{{ route('payments.create', ['matricule' => $student->matricule]) }}" class="inline-flex items-center gap-1.5 rounded-md bg-green-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-green-700">
                        💳 Paiement
                    </a>
                @endcan
                <a href="#documents" class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    📄 Documents
                </a>
                <a href="#pedagogie" class="inline-flex items-center gap-1.5 rounded-md bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                    📚 Notes
                </a>
                @can('creer-inscription')
                    <a href="{{ route('registrations.reinscription', ['matricule' => $student->matricule]) }}" class="inline-flex items-center gap-1.5 rounded-md bg-slate-700 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
                        🔁 Réinscription
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-lg border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-900/20 p-4 text-sm text-red-700 dark:text-red-200">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Cartes de synthèse : statut, classe, solde en un coup d'œil --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-6">
                <x-card padding="p-5">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Classe actuelle</p>
                    <p class="mt-3 text-xl font-bold text-gray-900 dark:text-gray-100">{{ $currentRegistration->classroom->name ?? 'Non inscrit' }}</p>
                </x-card>
                <x-card padding="p-5">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Statut</p>
                    <div class="mt-3">
                        @if(!$student->is_active)
                            <x-badge color="red">Inactif</x-badge>
                        @elseif($currentRegistration?->status === 'active')
                            <x-badge color="green">Actif</x-badge>
                        @else
                            <x-badge color="amber">En attente</x-badge>
                        @endif
                    </div>
                </x-card>
                <x-card padding="p-5">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total payé</p>
                    <p class="mt-3 text-xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($financialSituation['paid'] ?? 0, 0, ',', ' ') }} FCFA</p>
                </x-card>
                <x-card padding="p-5">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Reste à payer</p>
                    <p class="mt-3 text-xl font-bold text-amber-700 dark:text-amber-400">{{ number_format($financialSituation['remaining'] ?? 0, 0, ',', ' ') }} FCFA</p>
                </x-card>
                <x-card padding="p-5">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Montant attendu</p>
                    <p class="mt-3 text-xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($financialSituation['expected'] ?? 0, 0, ',', ' ') }} FCFA</p>
                </x-card>
                <x-card padding="p-5">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Impayés en retard</p>
                    <p class="mt-3 text-xl font-bold text-red-600 dark:text-red-400">{{ number_format($financialSituation['overdue'] ?? 0, 0, ',', ' ') }} FCFA</p>
                </x-card>
            </div>

            @hasanyrole('super-admin|admin')
            @if($currentRegistration)
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Changer de classe</h3>
                        <form method="POST" action="{{ route('students.transfer', $student) }}" class="mt-4 space-y-4">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="registration_id" value="{{ $currentRegistration->id }}">
                            <div>
                                <label for="classroom_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nouvelle classe</label>
                                <select id="classroom_id" name="classroom_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach($classrooms as $classroom)
                                        <option value="{{ $classroom->id }}" @selected($currentRegistration->classroom_id === $classroom->id)>{{ $classroom->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <x-primary-button type="submit">Mettre à jour la classe</x-primary-button>
                        </form>
                    </div>

                    <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Changer le statut</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Statut actuel : <span class="font-medium">{{ $currentRegistration->formatted_status }}</span></p>
                        @php $allowedTransitions = \App\Support\StudentStatus::TRANSITIONS[$currentRegistration->status] ?? []; @endphp
                        @if (empty($allowedTransitions))
                            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Ce statut est définitif, aucune transition n’est possible.</p>
                        @else
                            <form method="POST" action="{{ route('students.status', $student) }}" class="mt-4 space-y-4">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="registration_id" value="{{ $currentRegistration->id }}">
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nouveau statut</label>
                                    <select id="status" name="status" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach ($allowedTransitions as $transition)
                                            <option value="{{ $transition }}">{{ \App\Support\StudentStatus::label($transition) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="status_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Motif (obligatoire pour suspension, retrait, exclusion, annulation)</label>
                                    <textarea id="status_reason" name="status_reason" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                </div>
                                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Mettre à jour le statut</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
            @endhasanyrole

            <div id="finance" class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Historique des inscriptions</h3>
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Matricule inscription</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Année</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Classe</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Date</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Statut</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Mensualité</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                            @forelse($student->registrations as $registration)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $registration->matricule }}</td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $registration->schoolYear->year_string ?? $registration->academic_year ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $registration->classroom->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $registration->registration_date }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $registration->status === 'active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' }}">
                                            {{ $registration->status === 'active' ? 'Actif' : 'En attente' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-gray-100">{{ number_format($registration->monthly_fee, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Aucune inscription trouvée.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($currentRegistration)
                <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Dérogations tarifaires</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Réductions exceptionnelles appliquées à la mensualité de cette inscription, avec traçabilité complète.</p>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-slate-700">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nom</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Valeur</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Validité</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Motif</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Accordée par</th>
                                    @can('gerer-derogations-tarifaires')
                                        <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Actions</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                                @forelse($currentRegistration->discounts as $discount)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $discount->name }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                            {{ $discount->type === 'percentage' ? number_format($discount->value, 0) . ' %' : number_format($discount->value, 0, ',', ' ') . ' FCFA' }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                            {{ $discount->valid_from?->format('d/m/Y') ?? '—' }} → {{ $discount->valid_until?->format('d/m/Y') ?? 'indéfini' }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $discount->reason }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $discount->appliedBy?->name ?? '—' }}</td>
                                        @can('gerer-derogations-tarifaires')
                                            <td class="px-4 py-3 text-right">
                                                <form action="{{ route('discounts.destroy', $discount) }}" method="POST" onsubmit="return confirm('Supprimer cette dérogation ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:underline">Supprimer</button>
                                                </form>
                                            </td>
                                        @endcan
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">Aucune dérogation tarifaire.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @can('gerer-derogations-tarifaires')
                        <form action="{{ route('discounts.store', $currentRegistration) }}" method="POST" class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom de la dérogation</label>
                                <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ex: Bourse familiale">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                                    <select name="type" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="percentage">Pourcentage (%)</option>
                                        <option value="fixed">Montant fixe (FCFA)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valeur</label>
                                    <input type="number" name="value" min="0" step="0.01" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valide à partir de</label>
                                <input type="date" name="valid_from" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valide jusqu'au</label>
                                <input type="date" name="valid_until" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Motif (obligatoire, pour traçabilité)</label>
                                <textarea name="reason" required rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <x-primary-button type="submit">Accorder la dérogation</x-primary-button>
                            </div>
                        </form>
                    @endcan
                </div>
            @endif

            <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Historique des paiements</h3>
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Mois</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Classe</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Montant</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Reste</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Statut</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                            @forelse($student->registrations->flatMap->payments as $payment)
                                <tr>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $payment->month ?? $payment->month_paid ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $payment->registration->classroom->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-gray-100">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                                    <td class="px-4 py-3 text-right font-medium text-amber-700 dark:text-amber-400">{{ number_format($payment->remaining_balance, 0, ',', ' ') }} FCFA</td>
                                    <td class="px-4 py-3">
                                        @if($payment->isCancelled())
                                            <x-badge color="red">Annulé</x-badge>
                                        @else
                                            <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $payment->status === 'partiel' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' }}">
                                                {{ ucfirst($payment->status ?? 'complet') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            @unless($payment->isCancelled())
                                                @can('update', $payment)
                                                    <a href="{{ route('payments.edit', $payment) }}" class="inline-flex whitespace-nowrap items-center px-2.5 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50 transition-colors">
                                                        Modifier
                                                    </a>
                                                @endcan
                                                @if($payment->isValidated())
                                                    @can('cancel', $payment)
                                                        <button type="button" class="inline-flex whitespace-nowrap items-center px-2.5 py-1 rounded-md text-xs font-medium bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50 transition-colors"
                                                            x-on:click="$dispatch('open-cancel-payment', { action: '{{ route('payments.cancel', $payment) }}', receipt: '{{ addslashes($payment->receipt_number) }}' })">
                                                            Annuler
                                                        </button>
                                                    @endcan
                                                @else
                                                    @can('delete', $payment)
                                                        <form action="{{ route('payments.destroy', $payment) }}" method="POST" class="inline" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Supprimer le paiement', message: 'Confirmer la suppression de ce paiement ?', confirmLabel: 'Supprimer' })">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="inline-flex whitespace-nowrap items-center px-2.5 py-1 rounded-md text-xs font-medium bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50 transition-colors">
                                                                Supprimer
                                                            </button>
                                                        </form>
                                                    @endcan
                                                @endif
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Aucun paiement trouvé.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($student->emergency_contact_name || $student->emergency_contact_phone || $student->medical_notes || $student->allergies)
                <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Contact d'urgence & informations médicales</h3>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Contact d'urgence</p>
                            <p class="mt-1 text-gray-800 dark:text-gray-200">{{ $student->emergency_contact_name ?? '—' }} @if($student->emergency_contact_phone) ({{ $student->emergency_contact_phone }}) @endif</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Allergies</p>
                            <p class="mt-1 text-gray-800 dark:text-gray-200">{{ $student->allergies ?? '—' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Informations médicales</p>
                            <p class="mt-1 text-gray-800 dark:text-gray-200">{{ $student->medical_notes ?? '—' }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Historique de classe -->
            <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Historique de classe</h3>
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Année</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Classe</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                            @forelse($student->classHistories as $history)
                                <tr>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $history->schoolYear->year_string ?? $history->annee_scolaire ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $history->classroom->name ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Aucun changement de classe enregistré.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Parents -->
            <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Parents & responsables</h3>
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nom</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Lien</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Rôles</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Téléphone</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                            @forelse($student->parents as $parent)
                                <tr>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $parent->nom }} {{ $parent->prenom }}</td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $parent->pivot->lien_parente ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        @if($parent->pivot->est_responsable_financier)<span class="inline-flex px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">Resp. financier</span>@endif
                                        @if($parent->pivot->est_contact_urgence)<span class="inline-flex px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">Urgence</span>@endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $parent->telephone ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Aucun parent enregistré.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Documents -->
            <div id="documents" class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Documents</h3>
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Type</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Fichier</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Ajouté par</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Date</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                            @forelse($student->documents as $document)
                                <tr>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $document->type_label }}</td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $document->original_filename }}</td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $document->uploadedBy?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $document->created_at->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('students.documents.download', [$student, $document]) }}" class="inline-flex whitespace-nowrap items-center px-2.5 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50">
                                                Télécharger
                                            </a>
                                            @can('gerer-documents-eleve')
                                                <form action="{{ route('students.documents.destroy', [$student, $document]) }}" method="POST" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Supprimer le document', message: 'Supprimer définitivement « {{ addslashes($document->original_filename) }} » ?', confirmLabel: 'Supprimer' })">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex whitespace-nowrap items-center px-2.5 py-1 rounded-md text-xs font-medium bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50">
                                                        Supprimer
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Aucun document enregistré.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @can('gerer-documents-eleve')
                    <form action="{{ route('students.documents.store', $student) }}" method="POST" enctype="multipart/form-data" class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3 items-end">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type de document</label>
                            <select name="type" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach(\App\Support\StudentDocumentType::LABELS as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fichier (PDF, JPG ou PNG, 5 Mo max)</label>
                            <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-slate-700 dark:file:text-indigo-300">
                            <x-input-error :messages="$errors->get('file')" class="mt-2" />
                        </div>
                        <div class="md:col-span-3">
                            <x-primary-button type="submit">Ajouter le document</x-primary-button>
                        </div>
                    </form>
                @endcan
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Dernières notes -->
                <div id="pedagogie" class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Dernières notes</h3>
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-slate-700">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Matière</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Type / Période</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Note</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                                @forelse($student->notes->take(10) as $note)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $note->matiere->nom ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $note->type_evaluation }} — {{ $note->periode }}</td>
                                        <td class="px-4 py-3 text-right font-bold {{ $note->valeur >= 10 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ number_format($note->valeur, 2) }}/20</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Aucune note.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Bulletins -->
                <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Bulletins scolaires</h3>
                    <div class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        @foreach(['trimestre_1' => 'Trimestre 1', 'trimestre_2' => 'Trimestre 2', 'trimestre_3' => 'Trimestre 3'] as $period => $label)
                            <div class="rounded-md border border-gray-200 dark:border-slate-700 p-4">
                                <p class="font-medium text-gray-800 dark:text-gray-200 mb-3">{{ $label }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('bulletins.show', [$student, $period]) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs rounded-md hover:bg-indigo-700">
                                        Voir
                                    </a>
                                    <a href="{{ route('bulletins.pdf', [$student, $period]) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-gray-200 text-gray-700 text-xs rounded-md hover:bg-gray-300 dark:bg-slate-700 dark:text-gray-200 dark:hover:bg-slate-600">
                                        PDF
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($currentRegistration)
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Génération basée sur la classe {{ $currentRegistration->classroom->name ?? 'N/A' }}.</p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Présences -->
                <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Dernières présences / absences</h3>
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-slate-700">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Date</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Statut</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Classe</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Note</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                                @forelse($student->attendances->sortByDesc('date')->take(10) as $attendance)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $attendance->date?->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex px-2 py-1 text-xs rounded-full
                                                {{ match($attendance->status) {
                                                    'present' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
                                                    'late' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
                                                    'excused' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                                    default => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                } }}">{{ $attendance->status_label }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $attendance->classroom->name ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $attendance->notes ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Aucune donnée de présence.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sanctions -->
                <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Sanctions disciplinaires</h3>
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-slate-700">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Date</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Type</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                                @forelse($student->sanctions->sortByDesc('date_incident')->take(10) as $sanction)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $sanction->date_incident?->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $sanction->type_label }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $sanction->description ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Aucune sanction enregistrée.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
