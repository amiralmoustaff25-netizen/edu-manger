<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Enregistrer un Paiement
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <a href="{{ route('payments.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                            ← Retour aux paiements
                        </a>
                    </div>

                    <!-- Recherche par matricule -->
                    <div class="mb-8 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Rechercher un élève par matricule</h3>
                        <div class="flex gap-4">
                            <input type="text" id="matricule" placeholder="Entrez le matricule..." 
                                class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                x-data="{ matricule: '' }"
                                x-model="matricule"
                                @keyup.enter="searchStudent()">
                            <button onclick="searchStudent()" class="px-6 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                🔍 Rechercher
                            </button>
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
                            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-white dark:bg-gray-800 rounded p-4 text-center">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Total dû</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white" id="total-due">0 FCFA</p>
                                </div>
                                <div class="bg-white dark:bg-gray-800 rounded p-4 text-center">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Payé</p>
                                    <p class="text-2xl font-bold text-green-600 dark:text-green-400" id="total-paid">0 FCFA</p>
                                </div>
                                <div class="bg-white dark:bg-gray-800 rounded p-4 text-center">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Reste à payer</p>
                                    <p class="text-2xl font-bold text-red-600 dark:text-red-400" id="remaining-balance">0 FCFA</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulaire de paiement -->
                    <form id="payment-form" action="{{ route('payments.store') }}" method="POST" class="hidden">
                        @csrf
                        <input type="hidden" name="registration_id" id="registration_id">
                        
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
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Montant reçu (FCFA)</label>
                                        <input type="number" name="amount_paid" id="amount_paid" required min="0" step="0.01"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            oninput="calculateChange()">
                                    </div>

                                    <div id="change-section" class="hidden">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Monnaie à rendre (FCFA)</label>
                                        <input type="text" id="change-amount" readonly
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm bg-gray-100 dark:bg-gray-600">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mode de paiement</label>
                                        <select name="payment_method" id="payment_method" required
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="espèces">Espèces</option>
                                            <option value="carte">Carte bancaire</option>
                                            <option value="chèque">Chèque</option>
                                            <option value="virement">Virement bancaire</option>
                                            <option value="mobile">Mobile money</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type de paiement</label>
                                        <select name="payment_type" id="payment_type" required
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="mensualité">Mensualité</option>
                                            <option value="inscription">Inscription</option>
                                            <option value="cantine">Cantine</option>
                                            <option value="transport">Transport</option>
                                            <option value="autre">Autre</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mois concerné</label>
                                        <select name="month" id="month" required
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            @foreach(['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'] as $m)
                                                <option value="{{ $m }}">{{ $m }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date de paiement</label>
                                        <input type="date" name="payment_date" id="payment_date" value="{{ now()->format('Y-m-d') }}" required
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Référence (optionnel)</label>
                                        <input type="text" name="reference" id="reference"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Commentaire (optionnel)</label>
                                        <textarea name="comment" id="comment" rows="2"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                    </div>
                                </div>

                                <div class="mt-6 flex gap-2">
                                    <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                        ✅ Valider le paiement
                                    </button>
                                    <button type="button" onclick="resetForm()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                        Annuler
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let selectedFees = [];
        let studentData = null;

        function searchStudent() {
            const matricule = document.getElementById('matricule').value.trim();
            if (!matricule) {
                alert('Veuillez entrer un matricule');
                return;
            }

            fetch(`/api/students/by-matricule/${matricule}`)
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
                });
        }

        function displayStudentInfo(data) {
            document.getElementById('student-info').classList.remove('hidden');
            document.getElementById('student-matricule').textContent = data.matricule || '-';
            document.getElementById('student-name').textContent = data.user.name;
            document.getElementById('student-class').textContent = data.classroom?.name || 'Non assigné';
            document.getElementById('student-year').textContent = data.school_year?.year_string || '-';
            document.getElementById('registration_id').value = data.registration_id;
            
            // Calculer les totaux
            const totalDue = data.monthly_fee * 9; // 9 mois d'école
            const totalPaid = data.payments?.reduce((sum, p) => sum + p.amount, 0) || 0;
            const remaining = totalDue - totalPaid;
            
            document.getElementById('total-due').textContent = formatCurrency(totalDue);
            document.getElementById('total-paid').textContent = formatCurrency(totalPaid);
            document.getElementById('remaining-balance').textContent = formatCurrency(remaining);
        }

        function loadStudentFees(registrationId) {
            fetch(`/api/students/${registrationId}/fees`)
                .then(response => response.json())
                .then(fees => {
                    displayFeesList(fees);
                    document.getElementById('payment-form').classList.remove('hidden');
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

            fees.forEach((fee, index) => {
                const div = document.createElement('div');
                div.className = 'bg-white dark:bg-gray-800 rounded p-4 border border-gray-200 dark:border-gray-700';
                div.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" id="fee-${index}" 
                                ${fee.status === 'paid' ? 'disabled' : ''}
                                onchange="toggleFee(${index}, ${fee.amount}, '${fee.description}')"
                                class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-indigo-600">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">${fee.description}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">${fee.type}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-gray-900 dark:text-white">${formatCurrency(fee.amount)}</p>
                            <p class="text-xs ${fee.status === 'paid' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">
                                ${fee.status === 'paid' ? 'Payé' : 'À payer'}
                            </p>
                        </div>
                    </div>
                `;
                container.appendChild(div);
            });
        }

        function toggleFee(index, amount, description) {
            const checkbox = document.getElementById(`fee-${index}`);
            if (checkbox.checked) {
                selectedFees.push({ index, amount, description });
            } else {
                selectedFees = selectedFees.filter(f => f.index !== index);
            }
            updateSelectedTotal();
        }

        function updateSelectedTotal() {
            const total = selectedFees.reduce((sum, fee) => sum + fee.amount, 0);
            document.getElementById('selected-total').textContent = formatCurrency(total);
            document.getElementById('amount_paid').value = total;
        }

        function calculateChange() {
            const amountPaid = parseFloat(document.getElementById('amount_paid').value) || 0;
            const selectedTotal = selectedFees.reduce((sum, fee) => sum + fee.amount, 0);
            
            if (amountPaid > selectedTotal && selectedTotal > 0) {
                const change = amountPaid - selectedTotal;
                document.getElementById('change-section').classList.remove('hidden');
                document.getElementById('change-amount').value = formatCurrency(change);
            } else {
                document.getElementById('change-section').classList.add('hidden');
            }
        }

        function resetForm() {
            document.getElementById('student-info').classList.add('hidden');
            document.getElementById('payment-form').classList.add('hidden');
            document.getElementById('matricule').value = '';
            selectedFees = [];
            studentData = null;
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
        }
    </script>
</x-app-layout>
