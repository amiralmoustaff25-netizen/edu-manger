<x-app-layout>
    <x-slot name="header">
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
            <div class="flex flex-wrap gap-3">
                @can('enregistrer-paiement')
                    <a href="{{ route('payments.create', ['matricule' => $student->matricule]) }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700">
                        Effectuer un paiement
                    </a>
                @endcan
                <a href="{{ route('students.edit', $student) }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-700">
                    Modifier
                </a>
                <a href="{{ route('students.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-slate-800 dark:border-slate-600 dark:text-gray-300 dark:hover:bg-slate-700">
                    Retour aux élèves
                </a>
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

            @if (session('success'))
                <div class="rounded-lg border border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-900/20 p-4 text-sm text-green-700 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-lg bg-white dark:bg-slate-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 dark:text-gray-100">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Classe actuelle</p>
                    <p class="mt-3 text-xl font-bold text-gray-900 dark:text-gray-100">{{ $currentRegistration->classroom->name ?? 'Non inscrit' }}</p>
                </div>
                <div class="rounded-lg bg-white dark:bg-slate-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Statut</p>
                    <p class="mt-3 text-xl font-bold {{ $student->is_active ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400' }}">
                        {{ $student->is_active ? ($currentRegistration?->status === 'active' ? 'Actif' : 'En attente') : 'Inactif' }}
                    </p>
                </div>
                <div class="rounded-lg bg-white dark:bg-slate-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total payé</p>
                    <p class="mt-3 text-xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($financialSituation['paid'] ?? 0, 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="rounded-lg bg-white dark:bg-slate-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Reste à payer</p>
                    <p class="mt-3 text-xl font-bold text-amber-700 dark:text-amber-400">{{ number_format($financialSituation['remaining'] ?? 0, 0, ',', ' ') }} FCFA</p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-lg bg-white dark:bg-slate-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Montant total attendu</p>
                    <p class="mt-3 text-xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($financialSituation['expected'] ?? 0, 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="rounded-lg bg-white dark:bg-slate-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Impayés en retard</p>
                    <p class="mt-3 text-xl font-bold text-red-600 dark:text-red-400">{{ number_format($financialSituation['overdue'] ?? 0, 0, ',', ' ') }} FCFA</p>
                </div>
            </div>

            @if($currentRegistration)
                <div class="grid gap-6 lg:grid-cols-2">
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
                            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Mettre à jour la classe</button>
                        </form>
                    </div>

                    <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Changer le statut</h3>
                        <form method="POST" action="{{ route('students.status', $student) }}" class="mt-4 space-y-4">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="registration_id" value="{{ $currentRegistration->id }}">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Statut d’inscription</label>
                                <select id="status" name="status" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="pending" @selected($currentRegistration->status === 'pending')>En attente</option>
                                    <option value="active" @selected($currentRegistration->status === 'active')>Actif</option>
                                </select>
                            </div>
                            <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Mettre à jour le statut</button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
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
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $payment->status === 'partiel' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' }}">
                                            {{ ucfirst($payment->status ?? 'complet') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            @can('update', $payment)
                                                <a href="{{ route('payments.edit', $payment) }}" class="inline-flex whitespace-nowrap items-center px-2.5 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50 transition-colors">
                                                    Modifier
                                                </a>
                                            @endcan
                                            @can('delete', $payment)
                                                <form action="{{ route('payments.destroy', $payment) }}" method="POST" class="inline" onsubmit="return confirm('Confirmer la suppression de ce paiement ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex whitespace-nowrap items-center px-2.5 py-1 rounded-md text-xs font-medium bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50 transition-colors">
                                                        Supprimer
                                                    </button>
                                                </form>
                                            @endcan
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

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Dernières notes -->
                <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
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

            <div class="grid gap-6 lg:grid-cols-2">
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
