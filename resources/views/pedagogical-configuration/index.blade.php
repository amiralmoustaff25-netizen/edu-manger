<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100">Configuration pédagogique</h2></x-slot>
    <div class="py-8" x-data="{ tab: new URLSearchParams(location.search).get('tab') || 'overview' }" x-init="$watch('tab', value => {
        // Reflète l'onglet actif dans l'URL (sans recharger la page) : le Referer d'un
        // formulaire soumis depuis cette page inclut alors le bon onglet, donc back()
        // (utilisé par tous les store*() de ce contrôleur) y ramène l'utilisateur au lieu
        // de retomber sur 'Vue générale' par défaut après chaque enregistrement.
        const url = new URL(window.location);
        url.searchParams.set('tab', value);
        history.replaceState(history.state, '', url);
    })">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="GET" class="flex items-end gap-3 rounded-lg bg-white p-4 shadow-sm dark:bg-slate-800"><div><label for="school_year_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Année scolaire</label><select name="school_year_id" id="school_year_id" class="mt-1 rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white">@foreach($schoolYears as $year)<option value="{{ $year->id }}" @selected($schoolYear?->id === $year->id)>{{ $year->year_string }}</option>@endforeach</select></div><button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Afficher</button></form>
            @if(! $schoolYear)<div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 p-6 text-amber-800 dark:text-amber-200">Créez puis activez une année scolaire avant de configurer la pédagogie.</div>@else
            <div class="overflow-x-auto rounded-lg bg-white shadow-sm dark:bg-slate-800"><nav class="flex min-w-max border-b border-gray-200 dark:border-slate-700">@foreach(['overview' => 'Vue générale', 'assignments' => 'Affectations', 'subjects' => 'Matières & coefficients', 'evaluations' => 'Évaluations', 'periods' => 'Périodes', 'grades' => 'Notes & verrouillage'] as $key => $label)<button @click="tab = '{{ $key }}'" :class="tab === '{{ $key }}' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500'" class="border-b-2 px-4 py-3 text-sm font-medium">{{ $label }}</button>@endforeach</nav></div>

            <section x-show="tab === 'overview'" class="space-y-6"><div class="grid grid-cols-1 gap-4 md:grid-cols-4"><div class="rounded-lg bg-indigo-600 p-5 text-white"><p class="text-sm">Année active</p><p class="mt-1 text-2xl font-bold">{{ $schoolYear->year_string }}</p></div><div class="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-800"><p class="text-sm text-gray-500 dark:text-gray-400">Classes</p><p class="text-2xl font-bold dark:text-white">{{ $classrooms->count() }}</p></div><div class="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-800"><p class="text-sm text-gray-500 dark:text-gray-400">Matières</p><p class="text-2xl font-bold dark:text-white">{{ $configuredSubjects->count() }}</p></div><div class="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-800"><p class="text-sm text-gray-500 dark:text-gray-400">Affectations</p><p class="text-2xl font-bold dark:text-white">{{ $assignments->count() }}</p></div></div><div class="rounded-lg bg-white p-6 shadow-sm dark:bg-slate-800"><h3 class="font-semibold text-gray-900 dark:text-white">Configuration à compléter</h3><div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">@foreach($issues as $issue)<button @click="tab = '{{ $issue['tab'] }}'" class="flex items-center justify-between rounded-md border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-4 py-3 text-left text-sm text-amber-900 dark:text-amber-200"><span>{{ $issue['label'] }}</span><strong>{{ $issue['count'] }}</strong></button>@endforeach</div></div></section>

            <section x-show="tab === 'assignments'" class="rounded-lg bg-white p-6 shadow-sm dark:bg-slate-800"><div class="flex items-center justify-between"><div><h3 class="font-semibold text-gray-900 dark:text-white">Affectations pédagogiques</h3><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Professeur, classe, matière et année scolaire.</p></div><a href="{{ route('pedagogical-configuration.assignments', ['school_year_id' => $schoolYear->id]) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Gérer les affectations</a></div></section>

            <section x-show="tab === 'subjects'" class="space-y-6">
                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-slate-800" x-data="{ cycle: '' }">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Matière &amp; coefficient</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Choisissez une matière existante ou saisissez-en une nouvelle, puis son coefficient — pour un cycle donné ou pour tous les cycles, en une seule étape. Pour le <strong>primaire</strong>, renseignez aussi le <strong>barème</strong> (note maximale, ex. Mathématiques /80). Pour le <strong>lycée</strong>, précisez la <strong>série</strong> si le coefficient en dépend (ex. Maths coef. 4 en Série S, coef. 2 en Série L).</p>
                    @error('subject_name')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    @error('matiere')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    <form method="POST" action="{{ route('pedagogical-configuration.subjects.store') }}" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                        @csrf
                        <input type="hidden" name="school_year_id" value="{{ $schoolYear->id }}">
                        <select name="matiere_id" aria-label="Matière existante" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"><option value="">— Matière existante —</option>@foreach($matieres as $matiere)<option value="{{ $matiere->id }}">{{ $matiere->nom }}</option>@endforeach</select>
                        <input name="subject_name" placeholder="Ou nouvelle matière : Ex. Philosophie" aria-label="Ou nouvelle matière" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                        <select name="cycle" x-model="cycle" aria-label="Cycle" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"><option value="">Tous les cycles</option><option value="primaire">Primaire</option><option value="college">Collège</option><option value="lycee">Lycée</option></select>
                        <input x-show="cycle === 'lycee'" x-cloak name="serie" placeholder="Série (Ex. L, S, ES)" aria-label="Série (lycée uniquement)" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                        <input name="coefficient" type="number" min="0.1" step="0.1" value="1" aria-label="Coefficient" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                        <input x-show="cycle === 'primaire'" x-cloak name="bareme" type="number" min="1" step="1" placeholder="Barème (primaire)" aria-label="Barème (primaire uniquement)" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Enregistrer</button>
                    </form>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-slate-800">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Matières</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Coefficient de base (utilisé par défaut, sauf s'il est surchargé par cycle/série ci-dessus pour l'année sélectionnée).</p>
                    <div class="mt-4 divide-y dark:divide-slate-700">
                        @forelse($matieres as $matiere)
                            <div x-data="{ editing: false }" class="py-2 text-sm dark:text-gray-200">
                                <div x-show="! editing" class="flex items-center justify-between gap-3">
                                    <span>{{ $matiere->nom }}</span>
                                    <div class="flex items-center gap-3">
                                        <strong>Coef. de base {{ $matiere->coefficient }} · Barème /{{ rtrim(rtrim(number_format($matiere->bareme, 2), '0'), '.') }}</strong>
                                        <button type="button" @click="editing = true" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Modifier</button>
                                        <form method="POST" action="{{ route('pedagogical-configuration.matieres.destroy', $matiere) }}" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Supprimer la matière', message: 'Supprimer « {{ addslashes($matiere->nom) }} » ? Impossible si des notes, affectations ou configurations de coefficient/barème y sont déjà rattachées.', confirmLabel: 'Supprimer' })">
                                            @csrf @method('DELETE')
                                            <button class="text-xs font-semibold text-red-600 dark:text-red-400 hover:underline">Supprimer</button>
                                        </form>
                                    </div>
                                </div>
                                <form x-show="editing" method="POST" action="{{ route('pedagogical-configuration.matieres.update', $matiere) }}" class="grid grid-cols-1 gap-2 md:grid-cols-5">
                                    @csrf @method('PATCH')
                                    <input name="nom" value="{{ $matiere->nom }}" aria-label="Nom de la matière" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white md:col-span-2" required>
                                    <input name="coefficient" type="number" min="0.1" step="0.1" value="{{ $matiere->coefficient }}" aria-label="Coefficient de base" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" required>
                                    <input name="bareme" type="number" min="1" step="1" value="{{ $matiere->bareme }}" aria-label="Barème de base" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <button class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white">Enregistrer</button>
                                        <button type="button" @click="editing = false" class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:underline">Annuler</button>
                                    </div>
                                </form>
                            </div>
                        @empty
                            <p class="py-4 text-sm text-gray-500 dark:text-gray-400">Aucune matière créée pour le moment.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-slate-800">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Coefficients par cycle</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Surcharges enregistrées pour l'année scolaire sélectionnée (créées via le formulaire ci-dessus).</p>
                    <div class="mt-4 divide-y dark:divide-slate-700">
                        @forelse($configuredSubjects as $configuration)
                            <div class="flex justify-between py-3 text-sm dark:text-gray-200"><span>{{ $configuration->matiere?->nom ?? 'Matière' }} — {{ $configuration->cycle ?: 'Tous cycles' }}@if($configuration->serie) (Série {{ $configuration->serie }})@endif</span><strong>Coef. {{ $configuration->coefficient }}@if($configuration->bareme) · Barème /{{ rtrim(rtrim(number_format($configuration->bareme, 2), '0'), '.') }}@endif</strong></div>
                        @empty
                            <p class="py-4 text-sm text-gray-500 dark:text-gray-400">Aucun coefficient spécifique configuré.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <section x-show="tab === 'evaluations'" class="rounded-lg bg-white p-6 shadow-sm dark:bg-slate-800"><h3 class="font-semibold text-gray-900 dark:text-white">Types d’évaluation</h3><form method="POST" action="{{ route('pedagogical-configuration.evaluation-types.store') }}" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-4">@csrf<input name="name" placeholder="Ex. Composition" aria-label="Nom du type d'évaluation" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" required><input name="default_coefficient" type="number" step="0.1" min="0.1" value="1" aria-label="Coefficient par défaut" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" required><select name="default_scale" aria-label="Barème par défaut" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"><option>20</option><option>10</option><option>40</option><option>100</option></select><button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Ajouter</button></form><div class="mt-5 divide-y dark:divide-slate-700">@forelse(\App\Models\EvaluationType::orderBy('position')->get() as $type)<div class="flex justify-between py-3 text-sm dark:text-gray-200"><span>{{ $type->name }}</span><span>Coef. {{ $type->default_coefficient }} · /{{ $type->default_scale }}</span></div>@empty<p class="py-4 text-sm text-gray-500 dark:text-gray-400">Aucun type d’évaluation configuré.</p>@endforelse</div></section>

            <section x-show="tab === 'periods'" class="rounded-lg bg-white p-6 shadow-sm dark:bg-slate-800"><h3 class="font-semibold text-gray-900 dark:text-white">Périodes scolaires</h3><form method="POST" action="{{ route('pedagogical-configuration.periods.store') }}" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">@csrf<input type="hidden" name="school_year_id" value="{{ $schoolYear->id }}"><input name="name" placeholder="Trimestre 1" aria-label="Nom de la période" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" required><input name="starts_at" type="date" aria-label="Date de début de la période" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" required><input name="ends_at" type="date" aria-label="Date de fin de la période" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" required><input name="grade_entry_starts_at" type="date" aria-label="Date de début de saisie des notes" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"><input name="grade_entry_ends_at" type="date" aria-label="Date de fin de saisie des notes" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"><button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Ajouter la période</button></form><div class="mt-5 divide-y dark:divide-slate-700">@forelse($periods as $period)<div class="flex items-center justify-between py-3 text-sm dark:text-gray-200"><span>{{ $period->name }} · {{ $period->starts_at->format('d/m/Y') }} — {{ $period->ends_at->format('d/m/Y') }}</span><form method="POST" action="{{ route('pedagogical-configuration.periods.toggle', $period) }}">@csrf @method('PATCH')<button class="rounded-md px-3 py-1 {{ $period->grade_entry_open ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-gray-300' }}">{{ $period->grade_entry_open ? 'Saisie ouverte' : 'Saisie fermée' }}</button></form></div>@empty<p class="py-4 text-sm text-gray-500 dark:text-gray-400">Aucune période configurée.</p>@endforelse</div></section>

            <section x-show="tab === 'grades'" class="rounded-lg bg-white p-6 shadow-sm dark:bg-slate-800"><h3 class="font-semibold text-gray-900 dark:text-white">Règles de gestion des notes</h3>@php($settings = $schoolYear->gradeSetting) <form method="POST" action="{{ route('pedagogical-configuration.settings.update', $schoolYear) }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">@csrf @method('PUT')<select name="organization_mode" aria-label="Mode d'organisation" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"><option value="trimesters" @selected($settings?->organization_mode === 'trimesters')>Trimestres</option><option value="semesters" @selected($settings?->organization_mode === 'semesters')>Semestres</option></select><select name="default_scale" aria-label="Barème par défaut" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white">@foreach([10,20,40,100] as $scale)<option value="{{ $scale }}" @selected(($settings?->default_scale ?? 20) === $scale)>Barème /{{ $scale }}</option>@endforeach</select><input type="number" name="minimum_grade" step="0.1" value="{{ $settings?->minimum_grade ?? 0 }}" aria-label="Note minimale" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white">@foreach(['allow_decimals' => 'Autoriser les décimales', 'allow_appreciations' => 'Autoriser les appréciations', 'allow_edit_after_submission' => 'Modification après soumission', 'administrative_validation_required' => 'Validation administrative obligatoire', 'lock_after_validation' => 'Verrouillage après validation'] as $field => $label)<label class="flex items-center gap-2 text-sm dark:text-gray-200"><input type="checkbox" name="{{ $field }}" value="1" @checked($settings?->$field ?? ($field === 'allow_decimals' || $field === 'allow_appreciations'))>{{ $label }}</label>@endforeach<button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Enregistrer les règles</button></form>
                {{-- Verrouillage par lot : notes.validate/notes.reopen existaient côté serveur (testés, journalisés) mais n'étaient reliées à aucune vue avant ce correctif — un directeur n'avait aucun moyen de verrouiller ou rouvrir les notes d'une classe/matière/période. --}}
                <div class="mt-8 border-t border-gray-200 pt-6 dark:border-slate-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Verrouillage des notes par lot</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Verrouiller empêche toute modification des notes correspondantes ; rouvrir permet à nouveau leur saisie.</p>
                    @can('validateNotes', \App\Models\Note::class)
                    <form method="POST" action="{{ route('notes.validate') }}" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Verrouiller les notes', message: 'Les notes correspondantes seront verrouillées : elles ne pourront plus être modifiées tant qu\'elles ne seront pas rouvertes.', confirmLabel: 'Verrouiller' })" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-5">
                        @csrf
                        <select name="classroom_id" required aria-label="Classe" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"><option value="">Classe</option>@foreach($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select>
                        <select name="matiere_id" required aria-label="Matière" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"><option value="">Matière</option>@foreach(\App\Models\Matiere::orderBy('nom')->get() as $matiere)<option value="{{ $matiere->id }}">{{ $matiere->nom }}</option>@endforeach</select>
                        <select name="type_evaluation" required aria-label="Type d'évaluation" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"><option value="">Type d'évaluation</option>@foreach(\App\Support\EvaluationTypeScope::LABELS as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
                        <select name="periode" required aria-label="Période" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"><option value="">Période</option><option value="trimestre_1">Trimestre 1</option><option value="trimestre_2">Trimestre 2</option><option value="trimestre_3">Trimestre 3</option></select>
                        <button type="submit" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">🔒 Verrouiller</button>
                    </form>
                    @endcan
                    @can('reopen', \App\Models\Note::class)
                    <form method="POST" action="{{ route('notes.reopen') }}" x-on:submit.prevent="$dispatch('open-confirmation', { form: $event.target, title: 'Rouvrir les notes', message: 'Les notes correspondantes seront rouvertes et redeviendront modifiables.', confirmLabel: 'Rouvrir' })" class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-5">
                        @csrf
                        <select name="classroom_id" required aria-label="Classe" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"><option value="">Classe</option>@foreach($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select>
                        <select name="matiere_id" required aria-label="Matière" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"><option value="">Matière</option>@foreach(\App\Models\Matiere::orderBy('nom')->get() as $matiere)<option value="{{ $matiere->id }}">{{ $matiere->nom }}</option>@endforeach</select>
                        <select name="type_evaluation" required aria-label="Type d'évaluation" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"><option value="">Type d'évaluation</option>@foreach(\App\Support\EvaluationTypeScope::LABELS as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
                        <select name="periode" required aria-label="Période" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"><option value="">Période</option><option value="trimestre_1">Trimestre 1</option><option value="trimestre_2">Trimestre 2</option><option value="trimestre_3">Trimestre 3</option></select>
                        <button type="submit" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-slate-600 dark:text-gray-200 dark:hover:bg-slate-700">🔓 Rouvrir</button>
                    </form>
                    @endcan
                </div>
            </section>
            @endif
        </div>
    </div>
</x-app-layout>
