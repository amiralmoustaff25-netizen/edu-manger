<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Créer des Frais par Classe
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <a href="{{ route('classroom-fees.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                            ← Retour
                        </a>
                    </div>

                    <form action="{{ route('classroom-fees.store') }}" method="POST">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-2xl">
                            <div>
                                <label for="classroom_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Classe</label>
                                <select name="classroom_id" id="classroom_id" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Sélectionner une classe</option>
                                    @foreach($classrooms as $classroom)
                                        <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="fee_type_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type de frais</label>
                                <select name="fee_type_id" id="fee_type_id" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Sélectionner un type</option>
                                    @foreach($feeTypes as $feeType)
                                        <option value="{{ $feeType->id }}">{{ $feeType->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="school_year_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Année scolaire</label>
                                <select name="school_year_id" id="school_year_id" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Sélectionner une année</option>
                                    @foreach($schoolYears as $schoolYear)
                                        <option value="{{ $schoolYear->id }}">{{ $schoolYear->year_string }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Montant (FCFA)</label>
                                <input type="number" name="amount" id="amount" required min="0" step="0.01" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div class="mt-6 flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                Créer les frais
                            </button>
                            <a href="{{ route('classroom-fees.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
