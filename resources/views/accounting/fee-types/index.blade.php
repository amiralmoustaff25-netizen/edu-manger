<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Gestion des Types de Frais
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6 flex justify-between items-center">
                        <a href="{{ route('accounting.dashboard') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                            ← Retour au Dashboard
                        </a>
                        <x-drawer title="Nouveau type de frais" trigger-label="+ Nouveau Type de Frais" :open="$errors->any()">
                            @if ($errors->any())
                                <div class="mb-6 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-900/20 dark:text-red-200">
                                    <p class="font-semibold">Le type de frais n'a pas pu être enregistré :</p>
                                    <ul class="mt-2 list-disc pl-5">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form action="{{ route('fee-types.store') }}" method="POST" class="space-y-6">
                                @csrf

                                <div>
                                    <label for="drawer-fee-type-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom</label>
                                    <input type="text" name="name" id="drawer-fee-type-name" value="{{ old('name') }}" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>

                                <div>
                                    <label for="drawer-fee-type-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                    <textarea name="description" id="drawer-fee-type-description" rows="3" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" name="is_recurring" id="drawer-fee-type-is_recurring" {{ old('is_recurring') ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <label for="drawer-fee-type-is_recurring" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                        Ce type de frais est récurrent (mensuel, trimestriel, etc.)
                                    </label>
                                </div>

                                <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-slate-700">
                                    <x-primary-button type="submit">Créer le type de frais</x-primary-button>
                                </div>
                            </form>
                        </x-drawer>
                    </div>

                    <!-- Tableau des types de frais -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nom</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Récurrent</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($feeTypes as $feeType)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $feeType->name }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                            {{ $feeType->description ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($feeType->is_recurring)
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Oui</span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400">Non</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('fee-types.edit', $feeType) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-2">Modifier</a>
                                            <form action="{{ route('fee-types.destroy', $feeType) }}" method="POST" class="inline" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Supprimer ce type de frais', message: 'Cette action est irréversible et supprimera aussi les frais de classe associés. Confirmez-vous la suppression ?', confirmLabel: 'Supprimer' })">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                            Aucun type de frais trouvé
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
