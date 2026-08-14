<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Promotion des élèves
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(!$activeYear)
                <div class="rounded-lg bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 p-4 text-sm text-amber-800 dark:text-amber-200">
                    Aucune année scolaire active. La promotion crée toujours les nouvelles inscriptions dans l'année active — activez-en une d'abord.
                </div>
            @endif

            <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-3">Choisir l'année source</h3>
                <form method="GET" action="{{ route('promotion.index') }}" class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[220px]">
                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Promouvoir les élèves actifs de l'année</label>
                        <select name="source_year_id" class="w-full border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 p-2 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Choisir --</option>
                            @foreach($schoolYears as $year)
                                <option value="{{ $year->id }}" {{ $sourceYear && $sourceYear->id === $year->id ? 'selected' : '' }}>{{ $year->year_string }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded transition">Afficher l'aperçu</button>
                </form>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Les nouvelles inscriptions sont toujours créées dans l'année active ({{ $activeYear->year_string ?? 'aucune' }}), jamais dans l'année source elle-même.
                </p>
            </div>

            @if($sourceYear)
                @if(empty($preview))
                    <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 text-sm text-gray-500 dark:text-gray-400">
                        Aucun élève actif trouvé pour l'année {{ $sourceYear->year_string }}.
                    </div>
                @else
                    <form method="POST" action="{{ route('promotion.store') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="source_year_id" value="{{ $sourceYear->id }}">

                        <div class="rounded-lg bg-white dark:bg-slate-800 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
                                <h3 class="font-semibold text-gray-700 dark:text-gray-200">Aperçu — {{ count($preview) }} élève(s) actif(s) en {{ $sourceYear->year_string }}</h3>
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded transition" onclick="return confirm('Confirmer la promotion de {{ count($preview) }} élève(s) ? Cette action crée les nouvelles inscriptions immédiatement.');">
                                    Valider la promotion
                                </button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                                    <thead class="bg-gray-50 dark:bg-slate-700/50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Élève</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Classe actuelle</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Décision</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Classe cible</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Motif (transfert/radiation)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                                        @foreach($preview as $row)
                                            <tr>
                                                <td class="px-4 py-2 whitespace-nowrap">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $row['student_name'] }}</div>
                                                    <div class="text-xs text-gray-400">{{ $row['student_matricule'] }}</div>
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $row['current_classroom'] }}</td>
                                                <td class="px-4 py-2 whitespace-nowrap">
                                                    <select name="decisions[{{ $row['registration_id'] }}][action]" class="border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded p-1 text-sm">
                                                        <option value="promote" {{ $row['suggested_action'] === 'promote' ? 'selected' : '' }}>Promouvoir</option>
                                                        <option value="repeat" {{ $row['suggested_action'] === 'repeat' ? 'selected' : '' }}>Redoubler</option>
                                                        <option value="transfer">Transférer (quitte l'établissement)</option>
                                                        <option value="graduate">Diplômé(e)</option>
                                                        <option value="expel">Radié(e)</option>
                                                        <option value="skip" {{ $row['suggested_action'] === 'manual' ? 'selected' : '' }}>Ignorer (ne rien faire)</option>
                                                    </select>
                                                    @if($row['suggested_action'] === 'manual')
                                                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Aucune suggestion automatique — choisir une classe cible ou une autre décision.</p>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap">
                                                    <select name="decisions[{{ $row['registration_id'] }}][classroom_id]" class="border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded p-1 text-sm">
                                                        <option value="">-- Aucune --</option>
                                                        @foreach($targetClassrooms as $classroom)
                                                            <option value="{{ $classroom->id }}" {{ $row['suggested_classroom_id'] === $classroom->id ? 'selected' : '' }}>{{ $classroom->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap">
                                                    <input type="text" name="decisions[{{ $row['registration_id'] }}][reason]" placeholder="Motif" class="border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded p-1 text-sm w-40">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </form>
                @endif
            @endif

        </div>
    </div>
</x-app-layout>
