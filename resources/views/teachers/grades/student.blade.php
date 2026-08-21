<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Saisie des Notes par Matricule') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('professeur.notes.index') }}" class="inline-flex items-center text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                ← {{ __('Retour à la Saisie des Notes') }}
            </a>

            @if(session('error'))
                <div class="rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-800 dark:bg-red-900/30 dark:text-red-300">{{ session('error') }}</div>
            @endif
            @if(session('success'))
                <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-800 dark:bg-green-900/30 dark:text-green-300">{{ session('success') }}</div>
            @endif

            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-1">{{ __('Rechercher un élève') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ __('Saisissez le matricule de l\'élève : sa classe et ses matières sont détectées automatiquement.') }}</p>

                <form action="{{ route('professeur.notes.eleve') }}" method="GET" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label for="matricule" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Matricule de l\'élève') }}</label>
                        <input type="text" name="matricule" id="matricule" value="{{ old('matricule', request('matricule')) }}" autofocus placeholder="Ex. EL-26-0001" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="w-52">
                        <label for="periode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Période') }}</label>
                        <select name="periode" id="periode" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="trimestre_1" @selected($periode === 'trimestre_1')>{{ __('Trimestre 1') }}</option>
                            <option value="trimestre_2" @selected($periode === 'trimestre_2')>{{ __('Trimestre 2') }}</option>
                            <option value="trimestre_3" @selected($periode === 'trimestre_3')>{{ __('Trimestre 3') }}</option>
                        </select>
                    </div>
                    <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium">{{ __('Rechercher') }}</button>
                </form>
            </div>

            @if($student && $classroom)
                @php
                    // Type d'évaluation par défaut de ce cycle (1er élément de la liste
                    // autorisée) : celui que le <select> ci-dessous soumettra tant que
                    // l'utilisateur ne le change pas — le préremplissage doit chercher la
                    // note existante sous CE type, pas un type fixe ('devoir') qui n'existe
                    // même pas en primaire (seul 'composition' y est autorisé, la note déjà
                    // saisie ne se préremplissait donc jamais).
                    $defaultEvaluationType = \App\Support\EvaluationTypeScope::allowedFor($classroom->cycle)[0];
                @endphp
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg" x-data="{
                    usesBareme: {{ $usesBaremeSystem ? 'true' : 'false' }},
                    rows: {{ $assignments->values()->map(fn ($a) => ['coef' => (float) ($a->matiere->coefficient ?? 1), 'bareme' => (float) ($baremes[$a->matiere_id] ?? 20), 'valeur' => old('grades.'.$a->matiere_id.'.valeur', optional($existingNotes->get($a->matiere_id.'_'.$defaultEvaluationType.'_1'))->valeur)])->toJson() }},
                    get average() {
                        // Primaire avec barèmes (sunuBulletin) : somme des points obtenus / somme
                        // des barèmes des matières notées × 20 — jamais valeur × barème, qui
                        // n'aurait aucun sens (voir GradeCalculationService::computeAverageDataPrimaire).
                        // Sinon (collège/lycée) : moyenne pondérée par coefficient classique.
                        let total = 0, weight = 0;
                        this.rows.forEach(r => {
                            if (r.valeur === null || r.valeur === '' || isNaN(r.valeur)) { return; }
                            if (this.usesBareme) { total += parseFloat(r.valeur); weight += r.bareme; }
                            else { total += parseFloat(r.valeur) * r.coef; weight += r.coef; }
                        });
                        if (weight <= 0) { return '—'; }
                        return this.usesBareme ? ((total / weight) * 20).toFixed(2) : (total / weight).toFixed(2);
                    }
                }">
                    <div class="p-6 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between flex-wrap gap-2">
                        <div>
                            <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $student->name }}</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Matricule') }} : {{ $student->matricule }} · {{ __('Classe') }} : {{ $classroom->name }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Moyenne pondérée (aperçu)') }}</p>
                                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400" x-text="average + ' / 20'"></p>
                            </div>
                            {{-- Aperçu du bulletin réel (bulletins.show), déjà recalculé en direct à
                                 partir des notes/matières enregistrées à l'instant — pas besoin de
                                 quitter la saisie pour voir où en est l'élève. Reflète les notes déjà
                                 enregistrées, pas la saisie en cours non sauvegardée ci-dessous. --}}
                            @can('generer-bulletins')
                                <a href="{{ route('bulletins.show', [$student, $periode]) }}" target="_blank" class="inline-flex items-center rounded-md border border-indigo-300 dark:border-indigo-700 px-3 py-2 text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30">
                                    {{ __('Aperçu du bulletin') }} ↗
                                </a>
                            @endcan
                        </div>
                    </div>

                    <form action="{{ route('professeur.notes.eleve.store') }}" method="POST" class="p-6">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $student->id }}">
                        <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
                        <input type="hidden" name="periode" value="{{ $periode }}">

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                                <thead class="bg-gray-50 dark:bg-slate-700/50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Matière') }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $usesBaremeSystem ? __('Barème') : __('Coef.') }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __("Type d'évaluation") }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Note') }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Appréciation') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                                    @foreach($assignments as $index => $assignment)
                                        @php
                                            $existing = $existingNotes->get($assignment->matiere_id.'_'.$defaultEvaluationType.'_1');
                                            $maxValeur = $usesBaremeSystem ? (float) ($baremes[$assignment->matiere_id] ?? 20) : 20;
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-200">{{ $assignment->matiere->nom }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $usesBaremeSystem ? $maxValeur : $assignment->matiere->coefficient }}</td>
                                            <td class="px-4 py-3">
                                                <input type="hidden" name="grades[{{ $index }}][matiere_id]" value="{{ $assignment->matiere_id }}">
                                                <select name="grades[{{ $index }}][type_evaluation]" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    @foreach(\App\Support\EvaluationTypeScope::allowedFor($classroom->cycle) as $type)
                                                        <option value="{{ $type }}">{{ __(\App\Support\EvaluationTypeScope::LABELS[$type]) }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="number" x-model="rows[{{ $index }}].valeur" name="grades[{{ $index }}][valeur]" min="0" max="{{ $maxValeur }}" step="0.5" value="{{ old('grades.'.$index.'.valeur', $existing?->valeur) }}" class="w-24 rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0-{{ $maxValeur }}">
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="text" name="grades[{{ $index }}][appreciation]" value="{{ old('grades.'.$index.'.appreciation', $existing?->appreciation) }}" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ __('Optionnelle') }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 flex justify-between items-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Les notes non saisies seront ignorées.') }}</p>
                            <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium shadow-sm">{{ __('Enregistrer les notes') }}</button>
                        </div>
                    </form>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
