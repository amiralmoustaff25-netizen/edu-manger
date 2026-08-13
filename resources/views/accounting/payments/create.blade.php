<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Enregistrer un Paiement
        </h2>
    </x-slot>

    @php
        // Si la soumission précédente a échoué, retrouve le matricule à partir de
        // registration_id (seul lien disponible : le champ "matricule" n'est pas
        // lui-même posté, il ne sert qu'à déclencher la recherche) pour relancer
        // automatiquement la recherche et révéler le formulaire pré-rempli.
        $oldMatricule = old('registration_id')
            ? optional(optional(\App\Models\Registration::find(old('registration_id')))->user)->matricule
            : request('matricule');
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <a href="{{ route('payments.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                            ← Retour aux paiements
                        </a>
                    </div>

                    @if ($errors->any())
                        <div class="mb-6 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-900/20 dark:text-red-200">
                            <p class="font-semibold">Le paiement n'a pas pu être enregistré :</p>
                            <ul class="mt-2 list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Recherche par matricule -->
                    <div class="mb-8 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Rechercher un élève par matricule</h3>
                        <div class="flex gap-4">
                            <div class="flex-1 relative">
                                <input type="text" id="matricule" placeholder="Entrez le matricule..." aria-label="Matricule de l'élève"
                                    value="{{ $oldMatricule }}"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pl-10"
                                    onkeydown="if (event.key === 'Enter') { event.preventDefault(); searchStudent(); }"
                                    autofocus>
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>
                            <button onclick="searchStudent()" id="search-btn" class="px-6 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 flex items-center gap-2">
                                <span>🔍</span>
                                <span>Rechercher</span>
                            </button>
                        </div>
                        <div id="search-loading" class="hidden mt-4 flex items-center gap-2 text-indigo-600 dark:text-indigo-400">
                            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Recherche en cours...</span>
                        </div>
                    </div>

                    <!-- Informations de l'élève (chargées dynamiquement) -->
                    <div id="student-info" class="hidden mb-8">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-20 h-20 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                        <span class="text-3xl">👤</span>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Matricule</p>
                                        <p class="font-bold text-gray-900 dark:text-white" id="student-matricule">-</p>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Nom complet</p>
                                    <p class="font-bold text-gray-900 dark:text-white" id="student-name">-</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Classe</p>
                                    <p class="font-bold text-gray-900 dark:text-white" id="student-class">-</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Année scolaire</p>
                                    <p class="font-bold text-gray-900 dark:text-white" id="student-year">-</p>
                                </div>
                            </div>
                            
                            <!-- Situation financière -->
                            <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="bg-white dark:bg-gray-800 rounded p-4 text-center">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Total attendu</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white" id="total-expected">0 FCFA</p>
                                </div>
                                <div class="bg-white dark:bg-gray-800 rounded p-4 text-center">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Déjà payé</p>
                                    <p class="text-2xl font-bold text-green-600 dark:text-green-400" id="total-paid">0 FCFA</p>
                                </div>
                                <div class="bg-white dark:bg-gray-800 rounded p-4 text-center">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Reste à payer</p>
                                    <p class="text-2xl font-bold text-red-600 dark:text-red-400" id="remaining-balance">0 FCFA</p>
                                </div>
                                <div class="bg-white dark:bg-gray-800 rounded p-4 text-center">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Impayés</p>
                                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400" id="total-overdue">0 FCFA</p>
                                </div>
                            </div>
                            <div id="next-due-alert" class="hidden mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded text-sm text-amber-800 dark:text-amber-200"></div>
                        </div>
                    </div>

                    <!-- Formulaire de paiement -->
                    <form id="payment-form" action="{{ route('payments.store') }}" method="POST" class="hidden">
                        @csrf
                        <input type="hidden" name="registration_id" id="registration_id">
                        <input type="hidden" name="selected_fees" id="selected_fees">
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Frais à payer -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Frais à payer</h3>
                                <div id="fees-list" class="space-y-3 max-h-96 overflow-y-auto">
                                    <!-- Les frais seront chargés dynamiquement -->
                                </div>
                                
                                <div class="mt-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4">
                                    <div class="flex justify-between items-center">
                                        <span class="font-semibold text-gray-900 dark:text-white">Total sélectionné:</span>
                                        <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400" id="selected-total">0 FCFA</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Détails du paiement -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Détails du paiement</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label for="amount_paid" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Montant reçu (FCFA)</label>
                                        <input type="number" name="amount_paid" id="amount_paid" required min="0" step="0.01"
                                            value="{{ old('amount_paid') }}"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            oninput="calculateChange()">
                                        <x-input-error :messages="$errors->get('amount_paid')" class="mt-2" />
                                    </div>

                                    <div id="change-section" class="hidden">
                                        <label for="change-amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Monnaie à rendre (FCFA)</label>
                                        <input type="text" id="change-amount" readonly
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm bg-gray-100 dark:bg-gray-600">
                                    </div>

                                    <div>
                                        <label for="payment_method" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mode de paiement</label>
                                        <select name="payment_method" id="payment_method" required
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="espèces" @selected(old('payment_method') === 'espèces')>Espèces</option>
                                            <option value="carte" @selected(old('payment_method') === 'carte')>Carte bancaire</option>
                                            <option value="chèque" @selected(old('payment_method') === 'chèque')>Chèque</option>
                                            <option value="virement" @selected(old('payment_method') === 'virement')>Virement bancaire</option>
                                            <option value="mobile" @selected(old('payment_method') === 'mobile')>Mobile money</option>
                                        </select>
                                        <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
                                    </div>

                                    <div>
                                        <label for="payment_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type de paiement</label>
                                        <select name="payment_type" id="payment_type" required
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="mensualité" @selected(old('payment_type') === 'mensualité')>Mensualité</option>
                                            <option value="inscription" @selected(old('payment_type') === 'inscription')>Inscription</option>
                                            <option value="cantine" @selected(old('payment_type') === 'cantine')>Cantine</option>
                                            <option value="transport" @selected(old('payment_type') === 'transport')>Transport</option>
                                            <option value="autre" @selected(old('payment_type') === 'autre')>Autre</option>
                                        </select>
                                        <x-input-error :messages="$errors->get('payment_type')" class="mt-2" />
                                    </div>

                                    <div>
                                        <label for="month" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mois concerné</label>
                                        <select name="month" id="month" required
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            @foreach(config('edu.school_months') as $m)
                                                <option value="{{ $m }}" @selected(old('month') === $m)>{{ $m }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('month')" class="mt-2" />
                                    </div>

                                    <div>
                                        <label for="payment_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date de paiement</label>
                                        <input type="date" name="payment_date" id="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <x-input-error :messages="$errors->get('payment_date')" class="mt-2" />
                                    </div>

                                    <div>
                                        <label for="reference" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Référence (optionnel)</label>
                                        <input type="text" name="reference" id="reference" value="{{ old('reference') }}"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                                    </div>

                                    <div>
                                        <label for="comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Commentaire (optionnel)</label>
                                        <textarea name="comment" id="comment" rows="2"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('comment') }}</textarea>
                                        <x-input-error :messages="$errors->get('comment')" class="mt-2" />
                                    </div>
                                </div>

                                <div class="mt-6 flex gap-2">
                                    <button type="button" onclick="openConfirmModal()" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                        ✅ Valider le paiement
                                    </button>
                                    <button type="button" onclick="resetForm()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                        Annuler
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Modal de confirmation -->
                    <div id="confirm-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                            <div class="fixed inset-0 bg-gray-500 dark:bg-slate-900 bg-opacity-75 transition-opacity" onclick="closeConfirmModal()"></div>
                            <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                                <div class="bg-white dark:bg-gray-800 px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">Récapitulatif du paiement</h3>
                                    <div class="mt-4 space-y-3 text-sm text-gray-700 dark:text-gray-300">
                                        <p><span class="font-medium">Élève :</span> <span id="confirm-student">-</span></p>
                                        <p><span class="font-medium">Montant versé :</span> <span id="confirm-amount" class="font-bold text-green-600">0 FCFA</span></p>
                                        <p><span class="font-medium">Total dû pour la sélection :</span> <span id="confirm-total">0 FCFA</span></p>
                                        <p><span class="font-medium">Reste après paiement :</span> <span id="confirm-remaining">0 FCFA</span></p>
                                        <p><span class="font-medium">Mode :</span> <span id="confirm-method">-</span></p>
                                        <p><span class="font-medium">Date :</span> <span id="confirm-date">-</span></p>
                                        <div>
                                            <p class="font-medium mb-1">Frais concernés :</p>
                                            <ul id="confirm-fees" class="list-disc list-inside text-gray-600 dark:text-gray-400"></ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                    <button type="button" onclick="submitPaymentForm()" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 sm:ml-3 sm:w-auto">
                                        Confirmer et enregistrer
                                    </button>
                                    <button type="button" onclick="closeConfirmModal()" class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-600 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 sm:mt-0 sm:w-auto">
                                        Annuler
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const canOverridePaidFees = @json(auth()->user()->hasAnyRole(['super-admin', 'manager-comptable']));
        let selectedFees = [];
        let studentData = null;
        let allFees = [];

        // Fonctionne au premier chargement et après une navigation PJAX (voir le
        // commentaire équivalent dans resources/views/dashboard.blade.php).
        (function (fn) {
            if (document.readyState !== 'loading') {
                fn();
            } else {
                document.addEventListener('DOMContentLoaded', fn);
            }
        })(() => {
            const matricule = document.getElementById('matricule');
            if (matricule && matricule.value.trim()) {
                searchStudent();
            }
        });

        function searchStudent() {
            const matricule = document.getElementById('matricule').value.trim();
            if (!matricule) {
                alert('Veuillez entrer un matricule');
                return;
            }

            document.getElementById('search-loading').classList.remove('hidden');
            document.getElementById('search-btn').disabled = true;

            fetch(`/api/students/by-matricule/${encodeURIComponent(matricule)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    studentData = data;
                    displayStudentInfo(data);
                    loadStudentFees(data.registration_id);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erreur lors de la recherche de l\'élève');
                })
                .finally(() => {
                    document.getElementById('search-loading').classList.add('hidden');
                    document.getElementById('search-btn').disabled = false;
                });
        }

        function displayStudentInfo(data) {
            document.getElementById('student-info').classList.remove('hidden');
            document.getElementById('student-matricule').textContent = data.matricule || '-';
            document.getElementById('student-name').textContent = data.user?.name || '-';
            document.getElementById('student-class').textContent = data.classroom?.name || 'Non assigné';
            document.getElementById('student-year').textContent = data.school_year?.year_string || '-';
            document.getElementById('registration_id').value = data.registration_id;

            const situation = data.situation || {};
            document.getElementById('total-expected').textContent = formatCurrency(situation.expected || 0);
            document.getElementById('total-paid').textContent = formatCurrency(situation.paid || 0);
            document.getElementById('remaining-balance').textContent = formatCurrency(situation.remaining || 0);
            document.getElementById('total-overdue').textContent = formatCurrency(situation.overdue || 0);

            const nextDue = document.getElementById('next-due-alert');
            if (situation.next_due) {
                nextDue.textContent = `Prochaine échéance : ${situation.next_due.description} - ${formatCurrency(situation.next_due.amount)} (avant le ${situation.next_due.due_date})`;
                nextDue.classList.remove('hidden');
            } else {
                nextDue.classList.add('hidden');
            }
        }

        function loadStudentFees(registrationId) {
            fetch(`/api/students/${registrationId}/fees`)
                .then(response => response.json())
                .then(data => {
                    allFees = data.fees || [];
                    displayFeesList(allFees);
                    document.getElementById('payment-form').classList.remove('hidden');
                    document.getElementById('remaining-balance').textContent = formatCurrency(data.total_due || 0);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erreur lors du chargement des frais');
                });
        }

        function displayFeesList(fees) {
            const container = document.getElementById('fees-list');
            container.innerHTML = '';
            selectedFees = [];
            updateHiddenFields();

            if (fees.length === 0) {
                container.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center py-4">Aucun frais à payer</p>';
                return;
            }

            fees.forEach((fee, index) => {
                const div = document.createElement('div');
                div.className = 'bg-white dark:bg-gray-800 rounded p-4 border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600 transition-colors';

                const priorityColors = {
                    'high': 'border-l-4 border-l-red-500',
                    'medium': 'border-l-4 border-l-yellow-500',
                    'low': 'border-l-4 border-l-green-500',
                    'none': 'border-l-4 border-l-gray-300',
                };

                if (fee.priority && fee.priority !== 'none') {
                    div.className += ' ' + (priorityColors[fee.priority] || '');
                }

                const isPaid = fee.status === 'paid';
                const isPartial = fee.status === 'partial';
                const statusLabel = isPaid ? '✓ Payé' : (isPartial ? `Partiel (${formatCurrency(fee.paid_amount || 0)} payé)` : 'À payer');
                const statusColor = isPaid ? 'text-green-600 dark:text-green-400' : (isPartial ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400');

                div.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" id="fee-${index}"
                                ${(isPaid && !canOverridePaidFees) ? 'disabled' : ''}
                                onchange="toggleFee(${index})"
                                class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-indigo-600 focus:ring-indigo-500 h-5 w-5">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">${fee.description}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">${fee.type}</p>
                                ${fee.priority === 'high' ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 mt-1">Prioritaire</span>' : ''}
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-gray-900 dark:text-white">${formatCurrency(fee.remaining_amount || fee.amount || 0)}</p>
                            <p class="text-xs ${statusColor}">${statusLabel}</p>
                        </div>
                    </div>
                `;
                container.appendChild(div);
            });
        }

        function toggleFee(index) {
            const checkbox = document.getElementById(`fee-${index}`);
            const fee = allFees[index];

            if (!fee) return;

            if (checkbox.checked) {
                if (fee.status === 'paid' && !canOverridePaidFees) {
                    checkbox.checked = false;
                    return;
                }
                selectedFees.push(fee);
            } else {
                selectedFees = selectedFees.filter(f => f.id !== fee.id);
            }
            updateSelectedTotal();
        }

        function updateSelectedTotal() {
            const total = selectedFees.reduce((sum, fee) => sum + (parseFloat(fee.remaining_amount || fee.amount) || 0), 0);
            document.getElementById('selected-total').textContent = formatCurrency(total);
            document.getElementById('amount_paid').value = total;
            updateHiddenFields();
            calculateChange();
        }

        function updateHiddenFields() {
            const payload = selectedFees.map(f => ({
                id: f.id,
                code: f.code,
                description: f.description,
                month: f.month,
                amount: parseFloat(f.remaining_amount || f.amount) || 0,
            }));

            document.getElementById('selected_fees').value = JSON.stringify(payload);

            const months = selectedFees.map(f => f.month).filter(Boolean);
            const codes = selectedFees.map(f => f.code).filter(Boolean);

            document.getElementById('month').value = months.length === 1 ? months[0] : 'Multiple';
            document.getElementById('payment_type').value = codes.length === 1 ? codes[0] : 'multiple';
        }

        function calculateChange() {
            const amountPaid = parseFloat(document.getElementById('amount_paid').value) || 0;
            const selectedTotal = selectedFees.reduce((sum, fee) => sum + (parseFloat(fee.remaining_amount || fee.amount) || 0), 0);

            if (amountPaid > selectedTotal && selectedTotal > 0) {
                const change = amountPaid - selectedTotal;
                document.getElementById('change-section').classList.remove('hidden');
                document.getElementById('change-amount').value = formatCurrency(change);
            } else {
                document.getElementById('change-section').classList.add('hidden');
            }
        }

        function openConfirmModal() {
            const amountPaid = parseFloat(document.getElementById('amount_paid').value) || 0;
            const selectedTotal = selectedFees.reduce((sum, fee) => sum + (parseFloat(fee.remaining_amount || fee.amount) || 0), 0);

            if (selectedFees.length === 0 && selectedTotal === 0) {
                alert('Veuillez sélectionner au moins un frais.');
                return;
            }

            if (amountPaid <= 0) {
                alert('Veuillez saisir un montant reçu.');
                return;
            }

            const remaining = Math.max(0, selectedTotal - amountPaid);

            document.getElementById('confirm-student').textContent = studentData?.user?.name || '-';
            document.getElementById('confirm-amount').textContent = formatCurrency(amountPaid);
            document.getElementById('confirm-total').textContent = formatCurrency(selectedTotal);
            document.getElementById('confirm-remaining').textContent = formatCurrency(remaining);
            document.getElementById('confirm-method').textContent = document.getElementById('payment_method').selectedOptions[0].text;
            document.getElementById('confirm-date').textContent = document.getElementById('payment_date').value;

            const feesList = document.getElementById('confirm-fees');
            feesList.innerHTML = '';
            selectedFees.forEach(fee => {
                const li = document.createElement('li');
                li.textContent = `${fee.description} - ${formatCurrency(parseFloat(fee.remaining_amount || fee.amount) || 0)}`;
                feesList.appendChild(li);
            });

            document.getElementById('confirm-modal').classList.remove('hidden');
        }

        function closeConfirmModal() {
            document.getElementById('confirm-modal').classList.add('hidden');
        }

        function submitPaymentForm() {
            closeConfirmModal();
            const submitButton = document.querySelector('button[onclick="submitPaymentForm()"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Enregistrement...';
            }
            // requestSubmit(), pas submit() : submit() ne déclenche jamais l'événement
            // 'submit' (spec DOM), donc jamais interceptable par pjax.js — le formulaire
            // rechargeait toujours la page entière malgré l'extension du PJAX aux actions.
            document.getElementById('payment-form').requestSubmit();
        }

        function resetForm() {
            document.getElementById('student-info').classList.add('hidden');
            document.getElementById('payment-form').classList.add('hidden');
            document.getElementById('confirm-modal').classList.add('hidden');
            document.getElementById('matricule').value = '';
            selectedFees = [];
            allFees = [];
            studentData = null;
            updateHiddenFields();
            document.getElementById('fees-list').innerHTML = '';
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('fr-FR').format(amount || 0) + ' FCFA';
        }
    </script>
</x-app-layout>
