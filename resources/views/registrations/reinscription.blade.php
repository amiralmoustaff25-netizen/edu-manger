<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Réinscription</h2>
            <a href="{{ route('registrations.create') }}" class="inline-flex items-center justify-center rounded-md bg-gray-200 dark:bg-slate-700 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-300 dark:hover:bg-slate-600">
                Nouvelle inscription
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900 p-4 text-sm text-red-700 dark:text-red-200">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Rechercher un élève</h3>
                <form method="GET" action="{{ route('registrations.reinscription') }}" class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[16rem]">
                        <label for="matricule" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Matricule ou nom de l'élève</label>
                        <input type="text" id="matricule" name="matricule" value="{{ $searchedMatricule }}" placeholder="Ex. ELE-26-0001 ou Diallo" required
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <x-primary-button type="submit">
                        Rechercher
                    </x-primary-button>
                </form>
            </div>

            @if ($archived)
                <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900 p-4 text-sm text-amber-800 dark:text-amber-200">
                    L'élève portant le matricule « {{ $searchedMatricule }} » est archivé. Restaurez d'abord son dossier depuis la
                    <a href="{{ route('students.index', ['status' => 'archived', 'search' => $searchedMatricule]) }}" class="font-semibold underline hover:no-underline">liste des élèves archivés</a>
                    avant de le réinscrire.
                </div>
            @elseif ($matches->isNotEmpty())
                <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-3">Plusieurs élèves correspondent à « {{ $searchedMatricule }} », précisez :</h3>
                    <ul class="divide-y divide-gray-200 dark:divide-slate-700">
                        @foreach ($matches as $match)
                            <li class="py-2 flex items-center justify-between">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $match->name }} {{ $match->prenom }} — <span class="font-mono">{{ $match->matricule }}</span></span>
                                <a href="{{ route('registrations.reinscription', ['matricule' => $match->matricule]) }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Sélectionner</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @elseif ($searchedMatricule && ! $student)
                <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900 p-4 text-sm text-amber-800 dark:text-amber-200">
                    Aucun élève trouvé pour « {{ $searchedMatricule }} ».
                </div>
            @endif

            @if ($student && $alreadyRegistered)
                <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900 p-4 text-sm text-amber-800 dark:text-amber-200">
                    {{ $student->name }} est déjà inscrit(e) pour l'année scolaire active ({{ $activeYear->year_string }}).
                </div>
            @endif

            @if ($student && ! $alreadyRegistered)
                @php
                    $canEditFees = \App\Support\UserRoles::canEditRegistrationFees(auth()->user());
                    // MET-09 : préselectionner la classe supérieure configurée (promotion),
                    // plutôt que de rester par défaut sur l'ancienne classe de l'élève.
                    $defaultClassroomId = $lastRegistration?->classroom?->promotes_to_classroom_id ?? $lastRegistration?->classroom_id;
                    $defaultOptions = $lastRegistration?->options ?? [];
                    $reenrollFormData = [
                        'feeLibrary' => $feeLibrary,
                        'canEditFees' => $canEditFees,
                        'submitting' => false,
                        'classroomId' => (string) old('classroom_id', $defaultClassroomId ?? ''),
                        'registrationFee' => (float) old('registration_fee_paid', 0),
                        'monthlyFee' => (float) old('monthly_fee', $lastRegistration?->monthly_fee ?? 0),
                        'optionCantine' => (bool) old('options.cantine', $defaultOptions['cantine'] ?? false),
                        'optionTransport' => (bool) old('options.transport', $defaultOptions['transport'] ?? false),
                    ];
                @endphp
                <div
                    x-data='@json($reenrollFormData)'
                    x-init="if (classroomId && feeLibrary[classroomId] && !canEditFees) {
                        registrationFee = feeLibrary[classroomId].inscription ?? registrationFee;
                        monthlyFee = feeLibrary[classroomId].mensualite ?? monthlyFee;
                    }"
                    class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700"
                >
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Confirmer la réinscription</h3>

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Élève</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $student->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Matricule : {{ $student->matricule }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Dernière inscription</p>
                            @if($lastRegistration)
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $lastRegistration->classroom?->name ?? 'Non assigné' }} — {{ $lastRegistration->schoolYear?->year_string ?? $lastRegistration->academic_year }}
                                </p>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">Aucun historique trouvé.</p>
                            @endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('registrations.reinscription.store') }}" class="mt-6 space-y-5" x-on:submit="submitting = true">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $student->id }}">

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label for="classroom_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nouvelle classe</label>
                                <select id="classroom_id" name="classroom_id" required x-model="classroomId" @change="if (!canEditFees) {
                                    registrationFee = feeLibrary[classroomId]?.inscription ?? 0;
                                    monthlyFee = feeLibrary[classroomId]?.mensualite ?? 0;
                                } else if (feeLibrary[classroomId]) {
                                    registrationFee = feeLibrary[classroomId].inscription ?? registrationFee;
                                    monthlyFee = feeLibrary[classroomId].mensualite ?? monthlyFee;
                                }" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Sélectionner</option>
                                    @foreach($classrooms as $classroom)
                                        <option value="{{ $classroom->id }}" @selected(old('classroom_id', $defaultClassroomId) === $classroom->id)>{{ $classroom->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('classroom_id')" class="mt-2" />
                            </div>

                            <div class="flex items-end">
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Activer le compte pour {{ $activeYear->year_string }}</span>
                                </label>
                            </div>

                            <div>
                                <label for="registration_fee_paid" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Frais d'inscription</label>
                                <input id="registration_fee_paid" name="registration_fee_paid" type="number" min="0" step="0.01" x-model.number="registrationFee" :readonly="!canEditFees" :class="!canEditFees ? 'bg-gray-100 dark:bg-slate-700 cursor-not-allowed' : ''" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p x-show="!canEditFees" x-cloak class="mt-1 text-xs text-gray-500 dark:text-gray-400">Montant fixé par la bibliothèque des frais.</p>
                                <x-input-error :messages="$errors->get('registration_fee_paid')" class="mt-2" />
                            </div>
                            <div>
                                <label for="monthly_fee" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Scolarité mensuelle</label>
                                <input id="monthly_fee" name="monthly_fee" type="number" min="0" step="0.01" x-model.number="monthlyFee" :readonly="!canEditFees" :class="!canEditFees ? 'bg-gray-100 dark:bg-slate-700 cursor-not-allowed' : ''" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p x-show="!canEditFees" x-cloak class="mt-1 text-xs text-gray-500 dark:text-gray-400">Montant fixé par la bibliothèque des frais.</p>
                                <x-input-error :messages="$errors->get('monthly_fee')" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Options/services</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 rounded-md bg-gray-50 dark:bg-slate-700/50 p-4">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="options[cantine]" value="1" x-model="optionCantine" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">
                                            Cantine
                                            <template x-if="feeLibrary[classroomId]?.cantine">
                                                <span class="text-gray-400 dark:text-gray-500">(+<span x-text="feeLibrary[classroomId].cantine.toLocaleString('fr-FR')"></span> FCFA/mois)</span>
                                            </template>
                                        </span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="options[transport]" value="1" x-model="optionTransport" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">
                                            Transport
                                            <template x-if="feeLibrary[classroomId]?.transport">
                                                <span class="text-gray-400 dark:text-gray-500">(+<span x-text="feeLibrary[classroomId].transport.toLocaleString('fr-FR')"></span> FCFA/mois)</span>
                                            </template>
                                        </span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="options[internat]" value="1" @checked($defaultOptions['internat'] ?? false) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Internat</span>
                                    </label>
                                </div>
                                <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Total mensuel estimé :
                                    <span class="text-indigo-700 dark:text-indigo-400" x-text="(monthlyFee + (optionCantine ? (feeLibrary[classroomId]?.cantine ?? 0) : 0) + (optionTransport ? (feeLibrary[classroomId]?.transport ?? 0) : 0)).toLocaleString('fr-FR') + ' FCFA'"></span>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 border-t border-gray-200 dark:border-slate-700 pt-4">
                            <x-primary-button type="submit" ::disabled="submitting" ::class="submitting ? 'opacity-60 cursor-not-allowed' : ''">
                                <span x-show="!submitting">Confirmer la réinscription</span>
                                <span x-show="submitting" x-cloak>Enregistrement…</span>
                            </x-primary-button>
                            <a href="{{ route('dashboard') }}" class="rounded-md border border-gray-300 dark:border-slate-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700">Annuler</a>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
