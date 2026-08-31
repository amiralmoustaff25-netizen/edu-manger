
@csrf

@php
    // $student->nom (accesseur sur User) retire proprement le suffixe 'prenom' de
    // 'name' plutôt que de couper au premier espace, ce qui tronquerait un nom de
    // famille composé ("El Hadji Diop Amadou" → "El").
    $studentNom = $student->nom;
    $studentPrenom = $student->prenom;
@endphp

@php
    $formOptions = old('options', $student->latestRegistration?->options ?? []);
    $enrollmentFormData = [
        'photoPreview' => $student->profile_photo_url,
        'showDelete' => (bool) $student->profile_photo_path,
        'feeLibrary' => $feeLibrary ?? [],
        'canEditFees' => auth()->user() ? \App\Support\UserRoles::canEditRegistrationFees(auth()->user()) : false,
        'classroomId' => (string) old('classroom_id', $student->latestRegistration?->classroom_id ?? ''),
        'registrationFee' => (float) old('registration_fee_paid', $student->latestRegistration?->registration_fee_paid ?? 0),
        'monthlyFee' => (float) old('monthly_fee', $student->latestRegistration?->monthly_fee ?? 0),
        'optionCantine' => (bool) ($formOptions['cantine'] ?? false),
        'optionTransport' => (bool) ($formOptions['transport'] ?? false),
        'submitting' => false,
    ];
@endphp
<div x-data='@json($enrollmentFormData)' x-init="if (classroomId && feeLibrary[classroomId] && !canEditFees) {
    registrationFee = feeLibrary[classroomId].inscription ?? registrationFee;
    monthlyFee = feeLibrary[classroomId].mensualite ?? monthlyFee;
}" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    @can('upload-photo-eleve')
    <!-- Photo Upload Section -->
    <div class="lg:col-span-2 flex flex-col items-center">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Photo de profil</label>
        <div class="flex flex-col items-center gap-4">
            <img :src="photoPreview" alt="Preview" class="rounded-full object-cover border-2 border-gray-300 dark:border-slate-600" style="width: 150px; height: 150px;">
            <div class="flex flex-col gap-2">
                <input @change="
                    const file = $event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (e) => { photoPreview = e.target.result; showDelete = false; };
                        reader.readAsDataURL(file);
                    }
                " type="file" id="photo" name="photo" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-slate-800 dark:file:text-indigo-400 dark:hover:file:bg-slate-700">
                <x-input-error :messages="$errors->get('photo')" class="mt-1" />
                @if($student->exists && $student->profile_photo_path)
                    <div x-show="showDelete" class="flex items-center gap-2">
                        <input type="checkbox" id="supprimer_photo" name="supprimer_photo" value="1" @checked(old('supprimer_photo')) class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500">
                        <label for="supprimer_photo" class="text-sm text-gray-600 dark:text-gray-400">Supprimer la photo</label>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @else
    @if($student->profile_photo_path)
    <div class="lg:col-span-2 flex flex-col items-center">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Photo de profil</label>
        <img :src="photoPreview" alt="Preview" class="rounded-full object-cover border-2 border-gray-300 dark:border-slate-600" style="width: 150px; height: 150px;">
    </div>
    @endif
    @endcan

    <div>
        <label for="nom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom</label>
        <input id="nom" name="nom" type="text" value="{{ old('nom', $studentNom) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('nom')" class="mt-2" />
    </div>

    <div>
        <label for="prenom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prénom</label>
        <input id="prenom" name="prenom" type="text" value="{{ old('prenom', $studentPrenom) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('prenom')" class="mt-2" />
    </div>

    <div class="lg:col-span-2">
        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $student->email) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div>
        <label for="date_naissance" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date de naissance</label>
        <input id="date_naissance" name="date_naissance" type="date" value="{{ old('date_naissance', $student->date_naissance ? $student->date_naissance->format('Y-m-d') : '') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('date_naissance')" class="mt-2" />
    </div>

    <div>
        <label for="lieu_naissance" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lieu de naissance</label>
        <input id="lieu_naissance" name="lieu_naissance" type="text" value="{{ old('lieu_naissance', $student->lieu_naissance) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('lieu_naissance')" class="mt-2" />
    </div>

    <div>
        <label for="sexe" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sexe</label>
        <select id="sexe" name="sexe" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Sélectionner</option>
            <option value="M" @selected(old('sexe', $student->sexe) === 'M')>Masculin</option>
            <option value="F" @selected(old('sexe', $student->sexe) === 'F')>Féminin</option>
        </select>
        <x-input-error :messages="$errors->get('sexe')" class="mt-2" />
    </div>

    <div>
        <label for="nationalite" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nationalité</label>
        <input id="nationalite" name="nationalite" type="text" value="{{ old('nationalite', $student->nationalite) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('nationalite')" class="mt-2" />
    </div>

    <div>
        <label for="telephone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Téléphone</label>
        <input id="telephone" name="telephone" type="text" value="{{ old('telephone', $student->telephone) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('telephone')" class="mt-2" />
    </div>

    <div>
        <label for="cycle" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cycle</label>
        <select id="cycle" name="cycle" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Sélectionner</option>
            <option value="primaire" @selected(old('cycle', $student->cycle) === 'primaire')>Primaire</option>
            <option value="college" @selected(old('cycle', $student->cycle) === 'college')>Collège</option>
            <option value="lycee" @selected(old('cycle', $student->cycle) === 'lycee')>Lycée</option>
        </select>
        <x-input-error :messages="$errors->get('cycle')" class="mt-2" />
    </div>

    <div>
        <label for="classroom_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Classe</label>
        <select id="classroom_id" name="classroom_id" required x-model="classroomId" @change="if (!canEditFees) {
            registrationFee = feeLibrary[classroomId]?.inscription ?? 0;
            monthlyFee = feeLibrary[classroomId]?.mensualite ?? 0;
        } else if (feeLibrary[classroomId]) {
            registrationFee = feeLibrary[classroomId].inscription ?? registrationFee;
            monthlyFee = feeLibrary[classroomId].mensualite ?? monthlyFee;
        }" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Sélectionner</option>
            @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" @selected(old('classroom_id', $student->latestRegistration?->classroom_id) === $classroom->id)>{{ $classroom->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('classroom_id')" class="mt-2" />
    </div>

    <div class="lg:col-span-2">
        <label for="adresse" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Adresse</label>
        <textarea id="adresse" name="adresse" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('adresse', $student->adresse) }}</textarea>
        <x-input-error :messages="$errors->get('adresse')" class="mt-2" />
    </div>

    <div class="lg:col-span-2 rounded-lg border border-gray-200 bg-gray-50 p-5 dark:border-slate-700 dark:bg-slate-700/30">
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4">Contact d'urgence &amp; informations médicales</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label for="emergency_contact_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contact d'urgence (nom)</label>
                <input id="emergency_contact_name" name="emergency_contact_name" type="text" value="{{ old('emergency_contact_name', $student->emergency_contact_name) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <x-input-error :messages="$errors->get('emergency_contact_name')" class="mt-2" />
            </div>
            <div>
                <label for="emergency_contact_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contact d'urgence (téléphone)</label>
                <input id="emergency_contact_phone" name="emergency_contact_phone" type="text" value="{{ old('emergency_contact_phone', $student->emergency_contact_phone) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <x-input-error :messages="$errors->get('emergency_contact_phone')" class="mt-2" />
            </div>
            <div>
                <label for="allergies" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Allergies</label>
                <textarea id="allergies" name="allergies" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('allergies', $student->allergies) }}</textarea>
                <x-input-error :messages="$errors->get('allergies')" class="mt-2" />
            </div>
            <div>
                <label for="medical_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Informations médicales</label>
                <textarea id="medical_notes" name="medical_notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('medical_notes', $student->medical_notes) }}</textarea>
                <x-input-error :messages="$errors->get('medical_notes')" class="mt-2" />
            </div>
        </div>
    </div>

    @if($enrollment ?? false)
        <div class="lg:col-span-2 rounded-lg border border-indigo-200 bg-indigo-50 p-5 dark:border-indigo-800 dark:bg-indigo-900/20">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Informations de l'inscription</h3>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="registration_fee_paid" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Frais d'inscription payés</label>
                    <input id="registration_fee_paid" name="registration_fee_paid" type="number" min="0" step="0.01" x-model.number="registrationFee" :readonly="!canEditFees" :class="!canEditFees ? 'bg-gray-100 dark:bg-slate-700 cursor-not-allowed' : ''" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p x-show="!canEditFees" x-cloak class="mt-1 text-xs text-gray-500 dark:text-gray-400">Montant fixé par la bibliothèque des frais. Seul un Super Administrateur ou Manager Comptable peut le modifier.</p>
                    <x-input-error :messages="$errors->get('registration_fee_paid')" class="mt-2" />
                </div>
                <div>
                    <label for="monthly_fee" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Scolarité mensuelle</label>
                    <input id="monthly_fee" name="monthly_fee" type="number" min="0" step="0.01" x-model.number="monthlyFee" :readonly="!canEditFees" :class="!canEditFees ? 'bg-gray-100 dark:bg-slate-700 cursor-not-allowed' : ''" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p x-show="!canEditFees" x-cloak class="mt-1 text-xs text-gray-500 dark:text-gray-400">Montant fixé par la bibliothèque des frais. Seul un Super Administrateur ou Manager Comptable peut le modifier.</p>
                    <x-input-error :messages="$errors->get('monthly_fee')" class="mt-2" />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Options/services</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 rounded-md bg-white p-4 dark:bg-slate-800">
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
                            <input type="checkbox" name="options[internat]" value="1" @checked($formOptions['internat'] ?? false) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Internat</span>
                        </label>
                    </div>
                    <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-200">
                        Total mensuel estimé (mensualité + options sélectionnées) :
                        <span class="text-indigo-700 dark:text-indigo-400" x-text="(monthlyFee + (optionCantine ? (feeLibrary[classroomId]?.cantine ?? 0) : 0) + (optionTransport ? (feeLibrary[classroomId]?.transport ?? 0) : 0)).toLocaleString('fr-FR') + ' FCFA'"></span>
                    </p>
                </div>
                <div class="md:col-span-2 flex items-center justify-between gap-4 rounded-md bg-white p-4 dark:bg-slate-800">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Compte élève actif</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Année scolaire : {{ $activeYear->year_string }}</p>
                    </div>
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $student->exists ? $student->is_active : false)) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 rounded-lg border border-gray-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Informations du parent</h3>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="parent_nom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom</label>
                    <input id="parent_nom" name="parent_nom" type="text" value="{{ old('parent_nom') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('parent_nom')" class="mt-2" />
                </div>
                <div>
                    <label for="parent_prenom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prénom</label>
                    <input id="parent_prenom" name="parent_prenom" type="text" value="{{ old('parent_prenom') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('parent_prenom')" class="mt-2" />
                </div>
                <div>
                    <label for="parent_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                    <input id="parent_email" name="parent_email" type="email" value="{{ old('parent_email') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('parent_email')" class="mt-2" />
                </div>
                <div>
                    <label for="parent_telephone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Téléphone</label>
                    <input id="parent_telephone" name="parent_telephone" type="text" value="{{ old('parent_telephone') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('parent_telephone')" class="mt-2" />
                </div>
                <div>
                    <label for="parent_profession" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Profession</label>
                    <input id="parent_profession" name="parent_profession" type="text" value="{{ old('parent_profession') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('parent_profession')" class="mt-2" />
                </div>
                <div>
                    <label for="parent_lien_parente" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lien de parenté</label>
                    <select id="parent_lien_parente" name="parent_lien_parente" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Sélectionner</option>
                        <option value="Pere" @selected(old('parent_lien_parente') === 'Pere')>Père</option>
                        <option value="Mere" @selected(old('parent_lien_parente') === 'Mere')>Mère</option>
                        <option value="Tuteur" @selected(old('parent_lien_parente') === 'Tuteur')>Tuteur</option>
                        <option value="Tutrice" @selected(old('parent_lien_parente') === 'Tutrice')>Tutrice</option>
                        <option value="Autre" @selected(old('parent_lien_parente') === 'Autre')>Autre</option>
                    </select>
                    <x-input-error :messages="$errors->get('parent_lien_parente')" class="mt-2" />
                </div>
                <div class="md:col-span-2">
                    <label for="parent_adresse" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Adresse</label>
                    <textarea id="parent_adresse" name="parent_adresse" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('parent_adresse') }}</textarea>
                    <x-input-error :messages="$errors->get('parent_adresse')" class="mt-2" />
                </div>
                <div class="md:col-span-2 flex flex-wrap gap-6 rounded-md bg-gray-50 p-4 dark:bg-slate-700/50">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="parent_est_responsable_financier" value="1" @checked(old('parent_est_responsable_financier')) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Responsable financier</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="parent_est_contact_urgence" value="1" @checked(old('parent_est_contact_urgence')) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Contact d'urgence</span>
                    </label>
                </div>
            </div>
        </div>
    @endif

    @if($parents->count() > 0)
        <div class="lg:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Parents associés</label>
            {{-- Au moins un emplacement par parent déjà lié à l'élève, sinon la sauvegarde du
                 formulaire (sync() sur la relation parents()) supprimerait silencieusement
                 tout lien au-delà des 2 premiers emplacements (perte de données). --}}
            @php
                $parentSlots = max($student->parents->count(), 2);
                // Liste brute inutilisable au-delà de quelques dizaines de familles : remplacée
                // par x-searchable-select (filtrage côté client, sans dépendance JS ajoutée).
                $parentOptions = $parents->map(fn ($p) => ['value' => $p->id, 'label' => trim("{$p->nom} {$p->prenom}")])->values()->all();
            @endphp
            <div class="mt-2 space-y-3">
                @for($i = 0; $i < $parentSlots; $i++)
                    @php
                        $parent = $student->parents->get($i);
                        $currentParentId = old("parents.{$i}.parent_id", optional($parent)->id);
                    @endphp
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-4 border border-gray-200 dark:border-slate-700 rounded-md p-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Parent</label>
                            <x-searchable-select
                                name="parents[{{ $i }}][parent_id]"
                                :options="$parentOptions"
                                :selected="$currentParentId"
                                placeholder="Rechercher un parent..."
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lien de parenté</label>
                            @php $currentLien = old("parents.{$i}.lien_parente", optional(optional($parent)->pivot)->lien_parente); @endphp
                            <select name="parents[{{ $i }}][lien_parente]" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Sélectionner</option>
                                <option value="Pere" @selected($currentLien === 'Pere')>Père</option>
                                <option value="Mere" @selected($currentLien === 'Mere')>Mère</option>
                                <option value="Tuteur" @selected($currentLien === 'Tuteur')>Tuteur</option>
                                <option value="Tutrice" @selected($currentLien === 'Tutrice')>Tutrice</option>
                                <option value="Autre" @selected($currentLien === 'Autre')>Autre</option>
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="parents[{{ $i }}][est_responsable_financier]" value="1" @checked(old("parents.{$i}.est_responsable_financier", optional(optional($parent)->pivot)->est_responsable_financier)) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Resp. financier</span>
                            </label>
                        </div>
                        <div class="flex items-end gap-2">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="parents[{{ $i }}][est_contact_urgence]" value="1" @checked(old("parents.{$i}.est_contact_urgence", optional(optional($parent)->pivot)->est_contact_urgence)) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Contact urgence</span>
                            </label>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    @endif

    <div class="lg:col-span-2 mt-6 flex items-center gap-3 border-t border-gray-200 dark:border-slate-700 pt-4">
        <x-primary-button type="submit" x-on:click="submitting = true" ::disabled="submitting" ::class="submitting ? 'opacity-60 cursor-not-allowed' : ''">
            <span x-show="!submitting">
                @if($student->exists)
                    Enregistrer les modifications
                @else
                    {{-- _form.blade.php n'est inclus que par students/edit.blade.php (élève existant)
                         et registrations/create.blade.php ($enrollment=true) : ce cas est toujours vrai
                         ici, il n'existe pas de route students.create/store autonome. --}}
                    Créer et inscrire l'élève
                @endif
            </span>
            <span x-show="submitting" x-cloak>Enregistrement…</span>
        </x-primary-button>
        <a href="{{ $student->exists ? route('students.show', $student) : route('students.index') }}" class="rounded-md border border-gray-300 dark:border-slate-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700">Annuler</a>
    </div>
</div>
