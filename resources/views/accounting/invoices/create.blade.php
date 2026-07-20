<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Créer une Facture
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <a href="{{ route('invoices.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                            ← Retour
                        </a>
                    </div>

                    <form action="{{ route('invoices.store') }}" method="POST" x-data="{ items: [{ fee_type_id: '', description: '', quantity: 1, unit_price: 0 }] }">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="registration_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Élève</label>
                                <select name="registration_id" id="registration_id" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Sélectionner un élève</option>
                                    @foreach($registrations as $registration)
                                        <option value="{{ $registration->id }}">{{ $registration->user->name }} - {{ $registration->classroom?->name ?? 'Sans classe' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="due_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date d'échéance</label>
                                <input type="date" name="due_date" id="due_date" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <!-- Lignes de facture -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Lignes de facture</h3>
                            
                            <template x-for="(item, index) in items" :key="index">
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4">
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type de frais</label>
                                            <select :name="'items[' + index + '][fee_type_id]'" x-model="item.fee_type_id" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <option value="">Sélectionner</option>
                                                @foreach($feeTypes as $feeType)
                                                    <option value="{{ $feeType->id }}">{{ $feeType->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                            <input type="text" :name="'items[' + index + '][description]'" x-model="item.description" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantité</label>
                                            <input type="number" :name="'items[' + index + '][quantity]'" x-model="item.quantity" required min="1" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prix unitaire (FCFA)</label>
                                            <input type="number" :name="'items[' + index + '][unit_price]'" x-model="item.unit_price" required min="0" step="0.01" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                    </div>

                                    <div class="mt-2 flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            Total: <span x-text="(item.quantity * item.unit_price).toLocaleString('fr-FR')"></span> FCFA
                                        </span>
                                        <button type="button" x-show="items.length > 1" @click="items.splice(index, 1)" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 text-sm">
                                            Supprimer
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <button type="button" @click="items.push({ fee_type_id: '', description: '', quantity: 1, unit_price: 0 })" class="px-4 py-2 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200 text-sm">
                                + Ajouter une ligne
                            </button>
                        </div>

                        <!-- Total -->
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4 mb-6">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold text-gray-900 dark:text-white">Total de la facture:</span>
                                <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400" x-text="items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0).toLocaleString('fr-FR') + ' FCFA'"></span>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                Créer la facture
                            </button>
                            <a href="{{ route('invoices.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
